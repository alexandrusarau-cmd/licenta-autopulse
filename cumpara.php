<?php
include 'functions.php';
include 'db_connect.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=cumpara&id=" . ($_GET['id'] ?? ''));
    exit;
}

$user_id = (int)$_SESSION['user_id'];

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: masini.php?error=id_invalid");
    exit;
}

$car_id = (int)$_GET['id'];

$stmt = $conn->prepare("
    SELECT id, marca, model, pret_vanzare, pret_promo, status, poza_principala, an, kilometraj
    FROM cars 
    WHERE id = ?
");
$stmt->bind_param("i", $car_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    header("Location: masini.php?error=masina_inexistenta");
    exit;
}

$masina = $result->fetch_assoc();
$stmt->close();

$eroare = "";
$succes = "";

// stabilire pret final achizitie. daca are promotie, il luam pe acela, altfel cel de vanzare standard
$pret_final = (!empty($masina['pret_promo']) && $masina['pret_promo'] > 0) ? (float)$masina['pret_promo'] : (float)$masina['pret_vanzare'];

if (strtolower($masina['status']) === 'vandut') {
    $eroare = "Această mașină a fost deja vândută.";
} elseif ($masina['pret_vanzare'] <= 0) {
    $eroare = "Prețul de vânzare nu este setat pentru această mașină.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($eroare)) {
    $nume = trim($_POST['nume'] ?? '');
    $telefon = trim($_POST['telefon'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mesaj = trim($_POST['mesaj'] ?? '');

    if (empty($nume) || empty($telefon) || empty($email)) {
        $eroare = "Te rog completează numele, telefonul și emailul!";
    } else {
        $stmt = $conn->prepare("
            INSERT INTO sales (user_id, car_id, nume, telefon, email, mesaj, price, sale_date, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 'pending')
        ");
        
        $stmt->bind_param("iisssds", $user_id, $car_id, $nume, $telefon, $email, $mesaj, $pret_final);
        
        if ($stmt->execute()) {
            $succes = "Cererea ta de cumpărare a fost trimisă cu succes! Un consultant Autopulse te va contacta în cel mai scurt timp.";
        } else {
            $eroare = "A apărut o eroare la salvare: " . $stmt->error;
        }
        $stmt->close();
    }
}
include 'header.php';
?>

<head>
    <style>
        .cumpara-main { padding: 3rem 5% 5rem; background: var(--bg-dark); min-height: 80vh; }
        
        .cumpara-container {
            max-width: 850px;
            margin: 0 auto;
            background: var(--card-bg);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0,0,0,0.5);
        }

        .car-summary-grid {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 2rem;
            background: #1a2230;
            padding: 2rem;
            align-items: center;
            border-bottom: 1px solid #2a3443;
        }

        .car-summary-grid img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }

        .car-info-text h1 { font-size: 1.6rem; color: white; margin: 0 0 0.5rem; }
        .car-info-text .price-tag { font-size: 1.8rem; color: #4caf50; font-weight: 800; margin-bottom: 0.5rem; }
        .car-info-text .specs { color: var(--gray); font-size: 0.95rem; }

        .form-wrapper { padding: 2.5rem 3rem; }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
        .full-width { grid-column: span 2; }

        .form-group label { display: block; margin-bottom: 0.6rem; color: var(--gray); font-weight: 500; font-size: 0.9rem; }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 0.85rem 1rem;
            border-radius: 8px;
            border: 1px solid #2a3443;
            background: #0f141c;
            color: white;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        .form-group input:focus { border-color: var(--accent-blue); outline: none; }

        .btn-submit {
            background: #4caf50;
            color: white;
            padding: 1rem;
            font-size: 1.1rem;
            font-weight: 700;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            width: 100%;
            margin-top: 1rem;
            transition: transform 0.2s, background 0.3s;
        }
        .btn-submit:hover { background: #43a047; transform: translateY(-2px); }

        @media (max-width: 768px) {
            .car-summary-grid { grid-template-columns: 1fr; text-align: center; padding: 1.5rem; }
            .car-summary-grid img { height: 180px; width: 100%; }
            .form-wrapper { padding: 1.5rem; }
            .form-grid { grid-template-columns: 1fr; }
            .full-width { grid-column: span 1; }
            .car-info-text .price-tag { font-size: 1.5rem; }
        }
    </style>
</head>
<body>

<main class="cumpara-main">
    <div class="cumpara-container">
        <?php if (!empty($succes)): ?>
            <div class="form-wrapper" style="text-align:center;">
                <h2 class="success" style="color: #4caf50; font-weight: 700;"><?= $succes ?></h2>
                <br>
                <a href="masini.php" class="btn back" style="display:inline-block; border: 2px solid #4caf50; color: #4caf50; padding: 0.8rem 2rem; border-radius: 30px; text-decoration: none;">Înapoi la Flotă</a>
            </div>
        <?php else: ?>

            <div class="car-summary-grid">
                <img src="<?= htmlspecialchars($masina['poza_principala'] ?? 'resurse/imagini/placeholder-car.png') ?>" alt="Masina">
                <div class="car-info-text">
                    <h1>Cumpără <?= htmlspecialchars($masina['marca'] . ' ' . $masina['model']) ?></h1>
                    
                    <div class="price-tag">
                        <?php if (!empty($masina['pret_promo']) && $masina['pret_promo'] > 0): ?>
                            <span style="text-decoration: line-through; color: #888; font-size: 1.1rem; font-weight: normal; margin-right: 10px;">
                                <?= number_format($masina['pret_vanzare'], 2) ?> €
                            </span>
                            <span><?= number_format($masina['pret_promo'], 2) ?> €</span>
                        <?php else: ?>
                            <?= number_format($masina['pret_vanzare'], 2) ?> €
                        <?php endif; ?>
                    </div>
                    
                    <div class="specs">An fabricație: <?= $masina['an'] ?> • Rulaj: <?= number_format($masina['kilometraj']) ?> km</div>
                </div>
            </div>

            <div class="form-wrapper">
                <?php if ($eroare): ?> <p class="error" style="margin-bottom: 1.5rem; color: #ef4444; font-weight: 600;"><?= $eroare ?></p> <?php endif; ?>

                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Nume complet *</label>
                            <input type="text" name="nume" value="<?= htmlspecialchars($_SESSION['username'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Telefon *</label>
                            <input type="tel" name="telefon" placeholder="07xx xxx xxx" required>
                        </div>
                        <div class="form-group full-width">
                            <label>Adresă Email *</label>
                            <input type="email" name="email" value="<?= htmlspecialchars($_SESSION['email'] ?? '') ?>" required>
                        </div>
                        <div class="form-group full-width">
                            <label>Mesaj sau întrebări (opțional)</label>
                            <textarea name="mesaj" rows="3" placeholder="Ex: Detalii despre finanțare sau buy-back..."></textarea>
                        </div>
                    </div>
                    <button type="submit" class="btn-submit">Confirmă intenția de cumpărare</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include 'footer.php'; ?>

<script>
    function toggleMenu() { document.querySelector('.menu').classList.toggle('active'); }
</script>

</body>
</html>