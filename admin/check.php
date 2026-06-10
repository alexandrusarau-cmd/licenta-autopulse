<?php
include '../functions.php';
include '../db_connect.php';

if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    header("Location: admin_login.php");
    exit;
}

$contract_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$rental_id   = isset($_GET['rental_id']) ? (int)$_GET['rental_id'] : 0;

if ($contract_id === 0 && $rental_id === 0) {
    die("ID invalid.");
}

if ($contract_id === 0 && $rental_id > 0) {
    $stmt = $conn->prepare("SELECT id FROM contracts WHERE rental_id = ? LIMIT 1");
    $stmt->bind_param("i", $rental_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $contract_id = $res->num_rows > 0 ? $res->fetch_assoc()['id'] : 0;
}

if ($contract_id === 0) {
    die("Contractul nu a fost găsit.");
}

$stmt = $conn->prepare("
    SELECT c.*, ca.marca, ca.model, ca.numar_inmatriculare, u.username 
    FROM contracts c
    JOIN cars ca ON c.car_id = ca.id
    JOIN users u ON c.user_id = u.id
    WHERE c.id = ?
");
$stmt->bind_param("i", $contract_id);
$stmt->execute();
$contract = $stmt->get_result()->fetch_assoc();

if (!$contract) {
    die("Contractul nu există.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? '';

    if ($type === 'check_in') {
        $km       = (int)($_POST['check_in_km'] ?? 0);
        $fuel     = $_POST['check_in_fuel'] ?? '';
        $notes    = $_POST['check_in_notes'] ?? '';
        $docs     = isset($_POST['check_in_docs']) ? 'da' : 'nu';
        $keys     = isset($_POST['check_in_keys']) ? 'da' : 'nu';
        $kit      = isset($_POST['check_in_kit']) ? 'da' : 'nu';
        $cleaning = $_POST['check_in_cleaning'] ?? 'perfect';

        $current_check_in_date = !empty($contract['check_in_date']) ? $contract['check_in_date'] : date('Y-m-d H:i:s');

        $update = $conn->prepare("
            UPDATE contracts 
            SET check_in_km=?, check_in_fuel=?, check_in_notes=?, check_in_docs=?, check_in_keys=?, check_in_kit=?, check_in_cleaning=?, check_in_date=? 
            WHERE id=?
        ");
		$update->bind_param("isssssssi", $km, $fuel, $notes, $docs, $keys, $kit, $cleaning, $current_check_in_date, $contract_id);
        $update->execute();

        $update_car = $conn->prepare("UPDATE cars SET status = 'inchiriat' WHERE id = ?");
        $update_car->bind_param("i", $contract['car_id']);
        $update_car->execute();

        echo "<script>alert('Check-in salvat cu succes! Mașina este acum marcată ca Închiriată.'); window.location.href='check.php?id=$contract_id';</script>";
        exit;
    } 
    elseif ($type === 'check_out') {
        $km       = (int)($_POST['check_out_km'] ?? 0);
        $fuel     = $_POST['check_out_fuel'] ?? '';
        $damage   = $_POST['check_out_damage'] ?? '';
        $cost     = (float)($_POST['check_out_cost'] ?? 0);
        $docs     = isset($_POST['check_out_docs']) ? 'da' : 'nu';
        $keys     = isset($_POST['check_out_keys']) ? 'da' : 'nu';
        $kit      = isset($_POST['check_out_kit']) ? 'da' : 'nu';
        $cleaning = $_POST['check_out_cleaning'] ?? 'perfect';

        $now_check_out_date = date('Y-m-d H:i:s');

        $update = $conn->prepare("
            UPDATE contracts 
            SET check_out_km=?, check_out_fuel=?, check_out_damage=?, check_out_cost=?, check_out_docs=?, check_out_keys=?, check_out_kit=?, check_out_cleaning=?, check_out_date=?, status='finalizat' 
            WHERE id=?
        ");
		$update->bind_param("issdsssssi", $km, $fuel, $damage, $cost, $docs, $keys, $kit, $cleaning, $now_check_out_date, $contract_id);
        $update->execute();

        $update_car_back = $conn->prepare("UPDATE cars SET status = 'disponibil', kilometraj = ? WHERE id = ?");
        $update_car_back->bind_param("ii", $km, $contract['car_id']);
        $update_car_back->execute();

        $conn->query("UPDATE rentals SET status = 'finalizat' WHERE id = " . (int)$contract['rental_id']);
        
        echo "<script>alert('Check-out finalizat! Contract închis și mașina este disponibilă.'); window.location.href='admin_rentals.php';</script>";
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Check-in / Check-out - Autopulse</title>
    <link rel="stylesheet" href="../resurse/css/admin.css">
    
    <style>
        .forms-layout-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        @media (max-width: 600px) {
            .forms-layout-grid { grid-template-columns: 1fr; }
        }
        .section-card {
            background: #11151d;
            padding: 2rem;
            border-radius: 12px;
        }
        .info-panel {
            background: #11151d;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            border-left: 4px solid #ffcc00;
        }
        .form-group {
            margin-bottom: 1.2rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #fff;
            font-weight: 600;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 10px;
            background: #1b222c;
            border: 1px solid #334155;
            border-radius: 6px;
            color: #fff;
            box-sizing: border-box;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            border-color: #ffcc00;
            outline: none;
        }
        .checklist-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.8rem;
            background: #1b222c;
            padding: 12px;
            border-radius: 6px;
            border: 1px solid #334155;
        }
        .checklist-grid label {
            margin: 0;
            font-size: 0.88rem;
            font-weight: normal;
            color: #a0a8b4;
            display: flex;
            align-items: center;
            cursor: pointer;
        }
        .checklist-grid input[type="checkbox"] {
            width: auto;
            margin-right: 8px;
            cursor: pointer;
        }
        .btn-submit {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            text-transform: uppercase;
            margin-top: 1rem;
        }
        .btn-green { background: #2ecc71; color: #fff; }
        .btn-red { background: #ef4444; color: #fff; }
    </style>
</head>
<body>

<header>
    <h1>Autopulse - Dashboard Administrativ</h1>
    <nav>
        <a href="admin_dashboard.php">Dashboard</a> |
        <a href="admin_cars.php">Lista masini</a> |
        <a href="admin_users.php">Lista utilizatori</a> |
        <a href="admin_rentals.php">Rezervari</a> |
        <a href="admin_logout.php">Logout</a>
    </nav>
</header>

<main style="padding: 2rem;">

    <h2 style="color:#E74C3C; margin-bottom: 1.5rem;">Procesare Flux Vehicul (Check-in / Check-out)</h2>

    <div class="info-panel">
        <h3 style="color: #fff; margin: 0 0 8px 0;">
            Autovehicul: <?= htmlspecialchars($contract['marca'] . ' ' . $contract['model']) ?> 
            <span style="color: #ffcc00; font-size: 1rem; font-weight: normal; margin-left: 10px;">
                [ <?= htmlspecialchars($contract['numar_inmatriculare'] ?? '') ?> ]
            </span>
        </h3>
        <p style="margin: 0; color: #a0a8b4;">
            <strong>Client asociat:</strong> <?= htmlspecialchars($contract['username']) ?> | 
            <strong>Număr contract:</strong> <?= htmlspecialchars($contract['contract_number'] ?? 'N/A') ?>
            <?php if(!empty($contract['check_out_date'])): ?>
                <br><strong style="color: #ef4444;">Contract Finalizat la:</strong> <?= date('d.m.Y H:i', strtotime($contract['check_out_date'])) ?>
            <?php endif; ?>
        </p>
    </div>

    <div class="forms-layout-grid">
        
        <div class="section-card">
            <h3 style="color: #ffcc00; margin-top: 0; margin-bottom: 1.5rem; border-bottom: 1px solid #1b222c; padding-bottom: 8px;">Preluare Autoturism (Check-in)</h3>
            
            <?php 
            $has_check_in = (!empty($contract['check_in_date']) && $contract['check_in_date'] !== NULL);
            $should_lock = false;

            if ($has_check_in) {
                $check_in_time = strtotime($contract['check_in_date']);
                $current_time = time();
                $diff_seconds = $current_time - $check_in_time;
                $diff_days = $diff_seconds / (60 * 60 * 24);

                if ($diff_days >= 1) {
                    $should_lock = true;
                }
            }
            ?>

            <form method="POST">
                <input type="hidden" name="type" value="check_in">
                
                <div class="form-group">
                    <label>Kilometraj la predare (km):</label>
                    <input type="number" name="check_in_km" value="<?= htmlspecialchars($contract['check_in_km'] ?? '') ?>" placeholder="Ex: 120000" required <?= $should_lock ? 'readonly' : '' ?> style="<?= $should_lock ? 'opacity: 0.7;' : '' ?>">
                </div>

                <div class="form-group">
                    <label>Nivel combustibil inițial:</label>
                    <select name="check_in_fuel" required <?= $should_lock ? 'disabled' : '' ?> style="<?= $should_lock ? 'opacity: 0.7;' : '' ?>">
                        <option value="plin" <?= ($contract['check_in_fuel'] ?? '') == 'plin' ? 'selected' : '' ?>>Plin</option>
                        <option value="3/4" <?= ($contract['check_in_fuel'] ?? '') == '3/4' ? 'selected' : '' ?>>3/4</option>
                        <option value="1/2" <?= ($contract['check_in_fuel'] ?? '') == '1/2' ? 'selected' : '' ?>>1/2</option>
                        <option value="1/4" <?= ($contract['check_in_fuel'] ?? '') == '1/4' ? 'selected' : '' ?>>1/4</option>
                        <option value="gol" <?= ($contract['check_in_fuel'] ?? '') == 'gol' ? 'selected' : '' ?>>Gol</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Inventar Accesorii și Documente:</label>
                    <div class="checklist-grid" style="<?= $should_lock ? 'opacity: 0.6; pointer-events: none;' : '' ?>">
                        <label><input type="checkbox" name="check_in_docs" <?= ($contract['check_in_docs'] ?? '') == 'da' ? 'checked' : '' ?> <?= $should_lock ? 'disabled' : '' ?>> Talon + RCA prezente</label>
                        <label><input type="checkbox" name="check_in_keys" <?= ($contract['check_in_keys'] ?? '') == 'da' ? 'checked' : '' ?> <?= $should_lock ? 'disabled' : '' ?>> Ambele chei primite</label>
                        <label><input type="checkbox" name="check_in_kit" <?= ($contract['check_in_kit'] ?? '') == 'da' ? 'checked' : '' ?> <?= $should_lock ? 'disabled' : '' ?>> Kit siguranță complet</label>
                    </div>
                </div>

                <div class="form-group">
                    <label>Stare igienizare / Curățenie:</label>
                    <select name="check_in_cleaning" required <?= $should_lock ? 'disabled' : '' ?> style="<?= $should_lock ? 'opacity: 0.7;' : '' ?>">
                        <option value="perfect" <?= ($contract['check_in_cleaning'] ?? '') == 'perfect' ? 'selected' : '' ?>>Perfectă (Curățată interior/exterior)</option>
                        <option value="dirty_ext" <?= ($contract['check_in_cleaning'] ?? '') == 'dirty_ext' ? 'selected' : '' ?>>Necesită spălare exterioară</option>
                        <option value="dirty_int" <?= ($contract['check_in_cleaning'] ?? '') == 'dirty_int' ? 'selected' : '' ?>>Necesită igienizare interior</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Observații starea mașinii:</label>
                    <textarea name="check_in_notes" rows="3" placeholder="Zgârieturi existente..." <?= $should_lock ? 'readonly' : '' ?> style="<?= $should_lock ? 'opacity: 0.7;' : '' ?>"><?= htmlspecialchars($contract['check_in_notes'] ?? '') ?></textarea>
                </div>

                <?php if (!$should_lock): ?>
                    <button type="submit" class="btn-submit btn-green">
                        <?= $has_check_in ? 'Actualizează Date Preluare' : 'Salvează Date Preluare' ?>
                    </button>
                    <?php if ($has_check_in): ?>
                        <div style="text-align: center; color: #ffcc00; font-size: 0.85rem; margin-top: 8px; font-weight: 600;">
                            ⚠️ Datele pot fi modificate timp de 24 de ore de la salvarea inițială!
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div style="text-align: center; color: #a0a8b4; font-weight: bold; padding: 12px; background: rgba(255, 255, 255, 0.05); border-radius: 6px; border: 1px solid #334155; font-size: 0.85rem;">
                        🔒 ISTORIC SECURIZAT (Au trecut mai mult de 24 de ore de la Check-in)
                    </div>
                <?php endif; ?>
            </form>
        </div>

        <div class="section-card">
            <h3 style="color: #2ecc71; margin-top: 0; margin-bottom: 1.5rem; border-bottom: 1px solid #1b222c; padding-bottom: 8px;">Returnare Autoturism (Check-out)</h3>
            
            <?php 
            $has_check_out = (!empty($contract['check_out_date']) && $contract['check_out_date'] !== NULL);
            ?>

            <form method="POST">
                <input type="hidden" name="type" value="check_out">
                
                <div class="form-group">
                    <label>Kilometraj la returnare (km):</label>
                    <input type="number" name="check_out_km" value="<?= htmlspecialchars($contract['check_out_km'] ?? '') ?>" placeholder="Ex: 120500" required <?= $has_check_out ? 'readonly' : '' ?>>
                </div>

                <div class="form-group">
                    <label>Nivel combustibil returnat:</label>
                    <select name="check_out_fuel" required <?= $has_check_out ? 'disabled' : '' ?>>
                        <option value="plin" <?= ($contract['check_out_fuel'] ?? '') == 'plin' ? 'selected' : '' ?>>Plin</option>
                        <option value="3/4" <?= ($contract['check_out_fuel'] ?? '') == '3/4' ? 'selected' : '' ?>>3/4</option>
                        <option value="1/2" <?= ($contract['check_out_fuel'] ?? '') == '1/2' ? 'selected' : '' ?>>1/2</option>
                        <option value="1/4" <?= ($contract['check_out_fuel'] ?? '') == '1/4' ? 'selected' : '' ?>>1/4</option>
                        <option value="gol" <?= ($contract['check_out_fuel'] ?? '') == 'gol' ? 'selected' : '' ?>>Gol</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Verificare Retur Accesorii și Documente:</label>
                    <div class="checklist-grid" style="<?= $has_check_out ? 'opacity: 0.6; pointer-events: none;' : '' ?>">
                        <label><input type="checkbox" name="check_out_docs" <?= ($contract['check_out_docs'] ?? '') == 'da' ? 'checked' : '' ?> <?= $has_check_out ? 'disabled' : '' ?>> Talon + RCA returnate</label>
                        <label><input type="checkbox" name="check_out_keys" <?= ($contract['check_out_keys'] ?? '') == 'da' ? 'checked' : '' ?> <?= $has_check_out ? 'disabled' : '' ?>> Ambele chei înapoiate</label>
                        <label><input type="checkbox" name="check_out_kit" <?= ($contract['check_out_kit'] ?? '') == 'da' ? 'checked' : '' ?> <?= $has_check_out ? 'disabled' : '' ?>> Kit siguranță înapoiat</label>
                    </div>
                </div>

                <div class="form-group">
                    <label>Stare curățenie la returnare:</label>
                    <select name="check_out_cleaning" required <?= $has_check_out ? 'disabled' : '' ?>>
                        <option value="perfect" <?= ($contract['check_out_cleaning'] ?? '') == 'perfect' ? 'selected' : '' ?>>Perfectă / Acceptabilă</option>
                        <option value="dirty_ext" <?= ($contract['check_out_cleaning'] ?? '') == 'dirty_ext' ? 'selected' : '' ?>>Murdară la exterior (Necesită spălare)</option>
                        <option value="dirty_int" <?= ($contract['check_out_cleaning'] ?? '') == 'dirty_int' ? 'selected' : '' ?>>Murdară la interior (Necesită detailing)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Deficiențe / Pagube constatate:</label>
                    <textarea name="check_out_damage" rows="3" placeholder="Zgârieturi noi, lovituri, accesorii lipsă..." <?= $has_check_out ? 'readonly' : '' ?>><?= htmlspecialchars($contract['check_out_damage'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label>Penalități / Costuri adiționale (EUR):</label>
                    <input type="number" step="0.01" name="check_out_cost" value="<?= htmlspecialchars($contract['check_out_cost'] ?? '0.00') ?>" <?= $has_check_out ? 'readonly' : '' ?>>
                </div>

                <?php if (!$has_check_out): ?>
                    <button type="submit" class="btn-submit btn-red" onclick="return confirm('Sunteți sigur că doriți închiderea definitivă a contractului și eliberarea mașinii?')">Finalizează Contract & Eliberează Mașina</button>
                <?php else: ?>
                    <div style="text-align: center; color: #ef4444; font-weight: bold; padding: 12px; background: rgba(239, 68, 68, 0.1); border-radius: 6px; border: 1px dashed #ef4444; font-size: 0.85rem; margin-top: 1rem;">
                        ✓ CONTRACT ÎNCHIS DEFINITIV (DATELE SUNT SALVATE)
                    </div>
                <?php endif; ?>
            </form>
        </div>

    </div>
</main>

</body>
</html>