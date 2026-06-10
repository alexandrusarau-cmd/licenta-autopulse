<?php
include 'functions.php';
include 'db_connect.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$error   = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old_password     = $_POST['old_password'] ?? '';
    $new_password     = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($old_password === '' || $new_password === '' || $confirm_password === '') {
        $error = "Toate câmpurile sunt obligatorii!";
    } elseif ($new_password !== $confirm_password) {
        $error = "Parolele noi nu coincid!";
    } else {
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");

        if (!$stmt) {
            die("Eroare SQL: " . $conn->error);
        }

        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $db_password = $user['password'];

            if (password_verify($old_password, $db_password)) {
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);

                $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");

                if (!$update_stmt) {
                    die("Eroare SQL update: " . $conn->error);
                }

                $update_stmt->bind_param("si", $new_hash, $user_id);

                if ($update_stmt->execute()) {
                    $success = "Parola a fost schimbată cu succes!";
                } else {
                    $error = "Eroare la actualizarea parolei: " . $conn->error;
                }

                $update_stmt->close();
            } else {
                $error = "Parola veche este incorectă!";
            }
        } else {
            $error = "Utilizatorul nu a fost găsit.";
        }

        $stmt->close();
    }
}

include 'header.php';
?>

<head>
</head>
<body>

    <main class="change-pass-main">
        <div class="change-pass-card">
            <h1>Schimbă Parola</h1>

            <?php if ($success): ?>
                <p class="success-msg"><?= htmlspecialchars($success) ?></p>
            <?php endif; ?>

            <?php if ($error): ?>
                <p class="error-msg"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>

            <form method="post" action="change_pass.php">
                <div class="form-group">
                    <label for="old_password">Parola veche</label>
                    <input type="password" id="old_password" name="old_password" placeholder="Introdu parola curentă" required>
                </div>

                <div class="form-group">
                    <label for="new_password">Parola nouă</label>
					<input type="password" id="new_password" name="new_password" placeholder="Introdu parola nouă" required>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirmă parola nouă</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Repetă parola nouă" required>
                </div>

                <button type="submit" class="btn primary">Schimbă Parola</button>
            </form>

            <div class="back-link">
                <a href="profile.php">Înapoi la profil</a>
            </div>
        </div>
    </main>

<?php include 'footer.php'; ?>

</body>
</html>