<?php
include 'functions.php';
include 'db_connect.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

if (isset($_POST['anuleaza_id'])) {
    $rez_id = (int)$_POST['anuleaza_id'];
    $stmt = $conn->prepare("UPDATE rentals SET status = 'anulata' WHERE id = ? AND user_id = ? AND status = 'pending'");
    $stmt->bind_param("ii", $rez_id, $user_id);
    $stmt->execute();
    $stmt->close();
    header("Location: rezervari-mele.php?success=anulata");
    exit;
}

if (isset($_POST['anuleaza_cumparare_id'])) {
    $sale_id = (int)$_POST['anuleaza_cumparare_id'];
    $stmt = $conn->prepare("UPDATE sales SET status = 'anulata' WHERE id = ? AND user_id = ? AND status = 'pending'");
    $stmt->bind_param("ii", $sale_id, $user_id);
    $stmt->execute();
    $stmt->close();
    header("Location: rezervari-mele.php?success=cumparare_anulata");
    exit;
}

$stmt = $conn->prepare("
    SELECT r.id, r.start_date, r.end_date, r.price, r.status,
           c.marca, c.model, c.poza_principala, c.pret_inchiriere
    FROM rentals r
    JOIN cars c ON r.car_id = c.id
    WHERE r.user_id = ?
    ORDER BY r.start_date DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$rezervari = $stmt->get_result();
$stmt->close();

$stmt_sales = $conn->prepare("
    SELECT s.id, s.price, s.status, s.sale_date,
           c.marca, c.model, c.poza_principala
    FROM sales s
    JOIN cars c ON s.car_id = c.id
    WHERE s.user_id = ?
    ORDER BY s.sale_date DESC
");
$stmt_sales->bind_param("i", $user_id);
$stmt_sales->execute();
$cumparari = $stmt_sales->get_result();
$stmt_sales->close();

include 'header.php';
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <style>
        .rezervari-main {
            padding: 9rem 5% 6rem;
            background: var(--bg-dark);
            min-height: calc(100vh - 140px);
        }
        .sectiune-titlu {
            color: white;
            font-size: 1.5rem;
            text-align: left;
            max-width: 1400px;
            margin: 2rem auto 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #2a3443;
            font-weight: 600;
        }
        .rezervari-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }
        .rezervare-card {
            background: var(--card-bg);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(0,0,0,0.25);
            transition: transform 0.3s, border-color 0.3s;
            text-align: center;
            border: 1px solid #1e2633;
        }
        .rezervare-card:hover {
            transform: translateY(-4px);
            border-color: #2a3443;
        }
        .rezervare-img {
            width: 100%;
            height: 180px;
            object-fit: cover;
        }
        .rezervare-content {
            padding: 1.5rem;
        }
        .rezervare-title {
            font-size: 1.3rem;
            margin-bottom: 0.6rem;
            color: white;
            font-weight: 600;
        }
        .rezervare-detail {
            color: var(--gray);
            font-size: 0.9rem;
            margin: 0.4rem 0;
        }
        .rezervare-price {
            font-size: 1.4rem;
            color: var(--accent-blue);
            font-weight: 700;
            margin: 0.8rem 0;
        }
        .status-badge {
            display: inline-block;
            padding: 0.35rem 1rem;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-top: 0.3rem;
            letter-spacing: 0.3px;
        }

        .status.pending { background: rgba(255, 152, 0, 0.1); color: #ff9800; border: 1px solid rgba(255, 152, 0, 0.2); }
        .status.confirmat, .status.confirmata { background: rgba(76, 175, 80, 0.1); color: #4caf50; border: 1px solid rgba(76, 175, 80, 0.2); }
        .status.finalizat, .status.finalizata { background: rgba(33, 150, 243, 0.1); color: #2196f3; border: 1px solid rgba(33, 150, 243, 0.2); }
        .status.anulata { background: rgba(244, 67, 54, 0.1); color: #f44336; border: 1px solid rgba(244, 67, 54, 0.2); }
        .status.respinsa { background: rgba(158, 158, 158, 0.1); color: #9e9e9e; border: 1px solid rgba(158, 158, 158, 0.2); }

        .tab-nav-container {
            margin-bottom: 2.5rem; 
            display: flex; 
            gap: 0px; 
            justify-content: center; 
            max-width: 1400px; 
            margin-left: auto; 
            margin-right: auto;
            border-bottom: 1px solid #2a3443;
            padding-bottom: 1px;
        }

        .tab-btn {
            background: transparent; 
            color: #a0a8b4; 
            border: none;
            border-bottom: 2px solid transparent;
            padding: 1rem 2rem; 
            font-weight: 500; 
            cursor: pointer; 
            font-size: 1rem;
            transition: all 0.2s;
        }

        .tab-btn.active {
            color: white;
            border-bottom: 2px solid var(--accent-blue);
            font-weight: 600;
        }

        .tab-btn:hover:not(.active) {
            color: white;
            opacity: 0.8;
        }
        .btn.anuleaza {
            background: transparent;
            color: #f44336;
            border: 1px solid rgba(244, 67, 54, 0.4);
            padding: 0.7rem 1.5rem;
            border-radius: 6px;
            cursor: pointer;
            margin-top: 1rem;
            width: 100%;
            font-weight: 500;
            font-size: 0.9rem;
            transition: all 0.2s;
        }
        .btn.anuleaza:hover {
            background: #f44336;
            color: white;
            border-color: #f44336;
        }
        .btn-action {
            background: #1e293b; 
            color: white; 
            border: 1px solid #334155;
            padding: 10px; 
            border-radius: 6px; 
            text-decoration: none; 
            font-weight: 500; 
            font-size: 0.9rem; 
            display: block;
            transition: all 0.2s;
        }
        .btn-action:hover {
            background: var(--accent-blue);
            border-color: var(--accent-blue);
        }
        .no-rezervari {
            text-align: center;
            padding: 4rem 1rem;
            color: var(--gray);
            font-size: 1.1rem;
            background: #11151d;
            border-radius: 8px;
            max-width: 1400px;
            margin: 0 auto;
            border: 1px solid #1a2230;
        }
    </style>
</head>
<body>
    <main class="rezervari-main">
        <h2 style="text-align: center; color: white; font-size: 2rem; margin-bottom: 2rem; font-weight: 600;">Panou Activitate</h2>

        <?php if (isset($_GET['success'])): ?>
            <?php if ($_GET['success'] === 'anulata'): ?>
                <p style="text-align:center; color:#4caf50; margin-bottom:2rem; font-weight: 500; font-size: 0.95rem;">✓ Rezervarea auto a fost anulată cu succes!</p>
            <?php elseif ($_GET['success'] === 'cumparare_anulata'): ?>
                <p style="text-align:center; color:#4caf50; margin-bottom:2rem; font-weight: 500; font-size: 0.95rem;">✓ Solicitarea de cumpărare a fost retrasă cu succes!</p>
            <?php endif; ?>
        <?php endif; ?>

        <div class="tab-nav-container">
            <button type="button" id="tabRentalsBtn" class="tab-btn active" onclick="switchUserTab('rentals')">
                Închirieri Auto
            </button>
            <button type="button" id="tabSalesBtn" class="tab-btn" onclick="switchUserTab('sales')">
                Cereri Cumpărare
            </button>
        </div>

        <!-- inchirieri -->
        <div id="sectionRentals" style="display: block;">
            <div class="sectiune-titlu">Contracte active și istoric închirieri</div>
            
            <?php if ($rezervari->num_rows > 0): ?>
                <div class="rezervari-grid">
                    <?php while ($rez = $rezervari->fetch_assoc()): ?>
                        <div class="rezervare-card">
                            <div class="car-image-wrapper">
                                <img class="rezervare-img"
                                     src="<?= htmlspecialchars($rez['poza_principala'] ?? 'resurse/imagini/placeholder-car.png') ?>"
                                     alt="<?= htmlspecialchars($rez['marca'] . ' ' . $rez['model']) ?>"
                                     onerror="this.src='resurse/imagini/placeholder-car.png';">
                            </div>

                            <div class="rezervare-content">
                                <h3 class="rezervare-title"><?= htmlspecialchars($rez['marca'] . ' ' . $rez['model']) ?></h3>
                                <p class="rezervare-detail">
                                    <?= date('d.m.Y H:i', strtotime($rez['start_date'])) ?> –
                                    <?= date('d.m.Y H:i', strtotime($rez['end_date'])) ?>
                                </p>
                                <p class="rezervare-price"><?= number_format($rez['price'], 2) ?> €</p>
                                <span class="status-badge status <?= htmlspecialchars(strtolower($rez['status'])) ?>">
                                    <?= ucfirst($rez['status']) ?>
                                </span>

                                <div style="margin-top: 1.2rem; display: flex; gap: 10px; flex-direction: column;">
                                    <?php if (strtolower($rez['status']) === 'pending'): ?>
                                        <form method="post">
                                            <input type="hidden" name="anuleaza_id" value="<?= $rez['id'] ?>">
                                            <button type="submit" class="btn anuleaza" onclick="return confirm('Sigur dorești să anulezi această rezervare?')">
                                                Anulează rezervarea
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if (in_array(strtolower($rez['status']), ['confirmat', 'finalizat'])): ?>
                                        <a href="contract.php?rental_id=<?= $rez['id'] ?>" class="btn-action">
                                            Vezi Contract
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p class="no-rezervari">Nu aveți nicio rezervare de închiriere înregistrată.</p>
            <?php endif; ?>
        </div>


        <!-- achizitii -->
        <div id="sectionSales" style="display: none;">
            <div class="sectiune-titlu">Solicitări depuse pentru achiziție vehicule</div>
            
            <?php if ($cumparari->num_rows > 0): ?>
                <div class="rezervari-grid">
                    <?php while ($sale = $cumparari->fetch_assoc()): ?>
                        <div class="rezervare-card">
                            <div class="car-image-wrapper">
                                <img class="rezervare-img"
                                     src="<?= htmlspecialchars($sale['poza_principala'] ?? 'resurse/imagini/placeholder-car.png') ?>"
                                     alt="<?= htmlspecialchars($sale['marca'] . ' ' . $sale['model']) ?>"
                                     onerror="this.src='resurse/imagini/placeholder-car.png';">
                            </div>

                            <div class="rezervare-content">
                                <h3 class="rezervare-title"><?= htmlspecialchars($sale['marca'] . ' ' . $sale['model']) ?></h3>
                                <p class="rezervare-detail">
                                    Trimisă la: <?= date('d.m.Y H:i', strtotime($sale['sale_date'])) ?>
                                </p>
                                <p class="rezervare-price"><?= number_format($sale['price'], 2) ?> €</p>
                                <span class="status-badge status <?= htmlspecialchars(strtolower($sale['status'])) ?>">
                                    <?php 
                                        $st_comp = strtolower($sale['status']);
                                        if ($st_comp === 'pending') echo 'În așteptare';
                                        elseif ($st_comp === 'finalizat') echo 'Aprobată';
                                        elseif ($st_comp === 'respinsa' || $st_comp === 'respins') echo 'Respinsă';
                                        else echo ucfirst($sale['status']);
                                    ?>
                                </span>

                                <div style="margin-top: 1.2rem; display: flex; gap: 10px; flex-direction: column;">
                                    <?php if (strtolower($sale['status']) === 'pending'): ?>
                                        <form method="post">
                                            <input type="hidden" name="anuleaza_cumparare_id" value="<?= $sale['id'] ?>">
                                            <button type="submit" class="btn anuleaza" onclick="return confirm('Sigur dorești să retragi cererea de cumpărare?')">
                                                Retrage solicitarea
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if (strtolower($sale['status']) === 'finalizat'): ?>
                                        <a href="document-vanzare.php?sale_id=<?= $sale['id'] ?>" class="btn-action">
                                            Vezi Acte Vânzare
                                        </a>
                                    <?php endif; ?>								
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p class="no-rezervari">Nu aveți nicio solicitare de cumpărare trimisă.</p>
            <?php endif; ?>
        </div>

    </main>

<script>
function switchUserTab(tabName) {
    const rentalsSec = document.getElementById('sectionRentals');
    const salesSec = document.getElementById('sectionSales');
    const rentalsBtn = document.getElementById('tabRentalsBtn');
    const salesBtn = document.getElementById('tabSalesBtn');

    if (tabName === 'rentals') {
        rentalsSec.style.display = 'block';
        salesSec.style.display = 'none';
        rentalsBtn.classList.add('active');
        salesBtn.classList.remove('active');
    } else {
        rentalsSec.style.display = 'none';
        salesSec.style.display = 'block';
        salesBtn.classList.add('active');
        rentalsBtn.classList.remove('active');
    }
}
</script>

<?php include 'footer.php'; ?>
</body>
</html>