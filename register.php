<?php
include 'functions.php';
include 'db_connect.php';

$register_error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $gender   = $_POST["gender"] ?? "";
    $email    = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($username === "" || $gender === "" || $email === "" || $password === "") {
        $register_error = "Toate câmpurile sunt obligatorii.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $register_error = "Adresa de email nu este validă.";
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $check->bind_param("ss", $username, $email);
        $check->execute();
        $check_result = $check->get_result();

        if ($check_result->num_rows > 0) {
            $register_error = "Username-ul sau emailul există deja.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("
                INSERT INTO users (username, gender, email, password, role)
                VALUES (?, ?, ?, ?, 'client')
            ");
            $stmt->bind_param("ssss", $username, $gender, $email, $hashed_password);

            if ($stmt->execute()) {
                header("Location: login.php?registered=1");
                exit;
            } else {
                $register_error = "A apărut o eroare la crearea contului.";
            }

            $stmt->close();
        }

        $check->close();
    }
}

include 'header.php';
?>

<body>

    <main class="register-main">
        <div class="register-card">
            <h1>Înregistrare</h1>

            <?php if (!empty($register_error)): ?>
                <p class="error-msg"><?= htmlspecialchars($register_error) ?></p>
            <?php endif; ?>

            <form action="register.php" method="post">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="Alege un username" required autofocus>
                </div>

                <div class="form-group radio-group">
                    <label>Sex</label>
                    <div class="radio-options">
                        <label><input type="radio" name="gender" value="m" required> Masculin</label>
                        <label><input type="radio" name="gender" value="f" required> Feminin</label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="adresa@email.com" required>
                </div>

                <div class="form-group">
                    <label for="password">Parolă</label>
					<input type="password" id="password" name="password" placeholder="Alege o parolă" required>
                </div>

                <button type="submit" class="btn btn-register">Înregistrează-te</button>
            </form>

            <div class="login-link">
                Ai deja cont? <a href="login.php">Autentifică-te</a>
            </div>
        </div>
    </main>

<?php include 'footer.php'; ?>

    <script src="resurse/js/script.js"></script>
</body>
</html>