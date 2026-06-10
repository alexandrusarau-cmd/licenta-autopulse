<?php
include 'functions.php';
include 'db_connect.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST['username'] ?? "");
    $password = $_POST['password'] ?? "";

    $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
    if (!$stmt) {
        die("Eroare SQL: " . $conn->error);
    }

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            session_regenerate_id(true);

            $_SESSION['loggedin'] = true;
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role'];

            header("Location: index.php");
            exit;
        } else {
            $error = "Username sau parolă incorectă!";
        }
    } else {
        $error = "Utilizator inexistent!";
    }

    $stmt->close();
}

include 'header.php';
?>

<body>
    <main class="login-main">
        <div class="login-card">
            <h1>Autentificare</h1>
            <?php if(!empty($error)): ?>
                <p class="error-msg"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>
            <form method="post" action="login.php">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="Introdu username" required autofocus>
                </div>
                <div class="form-group">
                    <label for="password">Parolă</label>
                    <input type="password" id="password" name="password" placeholder="Introdu parolă" required>
                </div>
					<button type="submit" class="btn btn-login">Login</button>
            </form>
            <div class="register-link">
                Nu ai cont? <a href="register.php">Înregistrează-te acum</a>
            </div>
        </div>
    </main>

<?php include 'footer.php'; ?>

    <script src="resurse/js/script.js"></script>
</body>
</html>
