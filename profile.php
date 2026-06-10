<?php
include 'functions.php';      
include 'db_connect.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

$stmt = $conn->prepare("SELECT username, email, gender FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    $username      = $user['username'];
    $email         = $user['email'];
    $gender        = $user['gender'];
} else {
    session_destroy();
    header("Location: login.php?error=cont_inexistent");
    exit;
}

$stmt->close();

include 'header.php';
?>
<body>
    <main class="profile-main">
        <div class="profile-card">
            <h1>Profilul tău</h1>

            <div class="profile-info">
                <div class="info-row">
                    <span class="label">Username:</span>
                    <span class="value"><?= htmlspecialchars($username) ?></span>
                </div>

                <div class="info-row">
							<span class="label">Parolă:</span>
							<span class="value">********</span>
							<small><a href="change_pass.php" class="change-link">(schimbă parola)</a></small>
                </div>

                <div class="info-row">
                    <span class="label">Sex:</span>
                    <span class="value">
                        <?php
                        switch(strtolower($gender ?? '')) {
                            case 'm': echo "Masculin"; break;
                            case 'f': echo "Feminin"; break;
                            default:  echo "Nedefinit"; break;
                        }
                        ?>
                    </span>
                </div>

                <div class="info-row">
                    <span class="label">Email:</span>
                    <span class="value"><?= htmlspecialchars($email) ?></span>
                </div>
            </div>

            <div class="profile-actions">
                <a href="rezervari-mele.php" class="btn primary">Vezi rezervările mele</a>
                <a href="logout.php" class="btn secondary">Deconectare</a>
            </div>
        </div>
    </main>

<?php include 'footer.php'; ?>

</body>
</html>