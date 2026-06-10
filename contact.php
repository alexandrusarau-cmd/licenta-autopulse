<?php
include 'functions.php';
include 'db_connect.php';

$succes = "";
$eroare = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nume    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $mesaj   = trim($_POST['message'] ?? '');

    if (empty($nume) || empty($email) || empty($mesaj)) {
        $eroare = "Toate câmpurile sunt obligatorii!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $eroare = "Adresa de email nu este validă!";
    } else {
        $stmt = $conn->prepare("INSERT INTO contacte (nume, email, mesaj, data_trimitere) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("sss", $nume, $email, $mesaj);
        $stmt->execute();
        $stmt->close();

        $to      = "alexandru.sarau@yahoo.com";
        $subject = "Mesaj nou de pe site - G&C Dealer Auto";
        $body    = "Nume: $nume\nEmail: $email\n\nMesaj:\n$mesaj";
        $headers = "From: $email\r\nReply-To: $email\r\n";

        if (mail($to, $subject, $body, $headers)) {
            $succes = "Mesajul tău a fost trimis cu succes! Îți vom răspunde cât mai curând.";
        } else {
            $eroare = "Eroare la trimiterea mesajului. Te rugăm să încerci din nou.";
        }
    }
}include 'header.php';
?>
<head>
    <style>
        .contact-main {
            padding: 4rem 5% 6rem;
            background: var(--bg-dark);
            min-height: calc(100vh - 140px);
        }

        .contact-card {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 3rem;
            max-width: 800px;
            margin: 0 auto;
            box-shadow: 0 8px 30px rgba(0,0,0,0.4);
        }

        .contact-card h2 {
            text-align: center;
            margin-bottom: 2rem;
            color: white;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--gray);
            font-weight: 500;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.9rem;
            border-radius: 8px;
            border: 1px solid #2a3443;
            background: #1a2230;
            color: white;
            font-size: 1rem;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 150px;
        }

        .btn.submit {
            width: 100%;
            padding: 1rem;
            font-size: 1.1rem;
            background: var(--accent-blue);
            color: white;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn.submit:hover {
            background: var(--accent-blue-hover);
            transform: translateY(-2px);
        }

        .success-msg {
            text-align: center;
            color: #4caf50;
            font-size: 1.2rem;
            margin: 1rem 0;
        }

        .error-msg {
            text-align: center;
            color: #f44336;
            font-size: 1.1rem;
            margin: 1rem 0;
        }

        .contact-info {
            text-align: center;
            margin-top: 3rem;
            color: var(--gray);
        }

        .contact-info a {
            color: var(--accent-blue);
            text-decoration: none;
        }

        .contact-info a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <main class="contact-main">
        <div class="contact-card">
            <h2>Contactează-ne</h2>

            <?php if ($succes): ?>
                <p class="success-msg"><?= htmlspecialchars($succes) ?></p>
            <?php endif; ?>

            <?php if ($eroare): ?>
                <p class="error-msg"><?= htmlspecialchars($eroare) ?></p>
            <?php endif; ?>

            <form method="post" class="contact-form">
                <div class="form-group">
                    <label for="name">Nume complet</label>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($nume ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">Adresa de email</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($email ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label for="message">Mesajul tău</label>
                    <textarea id="message" name="message" rows="6" required><?= htmlspecialchars($mesaj ?? '') ?></textarea>
                </div>

                <button type="submit" class="btn submit">Trimite mesajul</button>
            </form>

            <div class="contact-info">
                <p>
                    <strong>Telefon:</strong> <a href="tel:0771421469">0771 421 469</a><br>
                    <strong>Email:</strong> <a href="mailto:alexandru.sarau@yahoo.com">alexandru.sarau@yahoo.com</a>
                </p>
            </div>
        </div>
    </main>

<?php include 'footer.php'; ?>

</body>
</html>