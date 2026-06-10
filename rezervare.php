<?php
include 'functions.php';
include 'db_connect.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=rezervare&id=" . ($_GET['id'] ?? ''));
    exit;
}

$user_id = (int)$_SESSION['user_id'];

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: masini.php?error=id_invalid");
    exit;
}

$car_id = (int)$_GET['id'];

$stmt = $conn->prepare("
    SELECT id, marca, model, pret_inchiriere, pret_promo, status, poza_principala
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $start_date = $_POST['start_date'] ?? '';
    $start_time = $_POST['start_time'] ?? '09:00';
    $end_date   = $_POST['end_date'] ?? '';
    $end_time   = $_POST['end_time'] ?? '18:00';

    if (empty($start_date) || empty($end_date)) {
        $eroare = "Completează data de ridicare și returnare!";
    } else {
        // string-uri de tip DATETIME: YYYY-MM-DD HH:MM:SS
        $start_datetime = $start_date . ' ' . $start_time . ':00';
        $end_datetime   = $end_date . ' ' . $end_time . ':00';

        $timestamp_start = strtotime($start_datetime);
        $timestamp_end = strtotime($end_datetime);
        $timestamp_acum = time();

        if ($timestamp_start >= $timestamp_end) {
            $eroare = "Data/ora returnării trebuie să fie după ridicare!";
        } elseif ($timestamp_start < $timestamp_acum) {
            $eroare = "Data ridicării nu poate fi în trecut!";
        } else {
            // calculeaza zile
            $diff = $timestamp_end - $timestamp_start;
            // rotunjim in sus, daca inchiriaza 1 zi si o ora, plateste 2 zile
            $zile = ceil($diff / (60 * 60 * 24)); 
            if($zile < 1) $zile = 1;

            $pret_pe_zi = (!empty($masina['pret_promo']) && $masina['pret_promo'] > 0)
                ? $masina['pret_promo']
                : ($masina['pret_inchiriere'] ?? 0);

            $pret_total = $zile * $pret_pe_zi;

            // verificam suprapunere
            $check_stmt = $conn->prepare("
                SELECT COUNT(*) 
                FROM rentals 
                WHERE car_id = ? 
                AND status IN ('pending', 'confirmat')
                AND NOT (
                    end_date <= ? OR start_date >= ?
                )
            ");
            $check_stmt->bind_param("iss", $car_id, $start_datetime, $end_datetime);
            $check_stmt->execute();
            $check_stmt->bind_result($count);
            $check_stmt->fetch();
            $check_stmt->close();

            if ($count > 0) {
                $eroare = "Mașina este deja rezervată în acest interval orar!";
            } else {
                $status = 'pending';
                $insert_stmt = $conn->prepare("
                    INSERT INTO rentals (user_id, car_id, start_date, end_date, price, status)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $insert_stmt->bind_param("iissds", $user_id, $car_id, $start_datetime, $end_datetime, $pret_total, $status);

                if ($insert_stmt->execute()) {
                    $succes = "Rezervarea a fost înregistrată! Total: $pret_total € pentru $zile zile.";
                } else {
                    $eroare = "Eroare: " . $conn->error;
                }
                $insert_stmt->close();
            }
        }
    }
}
include 'header.php';
?>
<head>
    <style>
        .rezervare-main { padding: 4rem 5% 6rem; background: var(--bg-dark); min-height: calc(100vh - 140px); }
        .rezervare-card { background: var(--card-bg); border-radius: 16px; box-shadow: 0 8px 30px rgba(0,0,0,0.4); max-width: 900px; margin: 0 auto; overflow: hidden; }
        .masina-header { display: flex; flex-wrap: wrap; gap: 1.5rem; padding: 1.8rem; background: #1a2230; align-items: center; }
        .masina-img { width: 280px; height: 180px; object-fit: cover; border-radius: 10px; }
        .masina-info h2 { margin: 0 0 0.6rem; font-size: 1.8rem; color: white; }
        .masina-info p { margin: 0.3rem 0; color: var(--gray); }
        .rezervare-form { padding: 2rem 3rem; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; color: var(--gray); font-weight: 500; }
        .form-group input[type="date"], .form-group input[type="time"] {
            width: 100%; padding: 0.8rem; border-radius: 8px; border: 1px solid #2a3443; background: #1a2230; color: white; font-size: 1rem;
        }
        .rezumat { background: #11151d; padding: 1.5rem; border-radius: 10px; margin: 1.8rem 0; text-align: center; }
        .rezumat p { margin: 0.6rem 0; font-size: 1.05rem; }
        .rezumat .pret-total { font-size: 1.8rem; color: var(--accent-blue); font-weight: 700; }
        .btn.submit { width: 100%; padding: 1rem; font-size: 1.15rem; background: var(--accent-blue); color: white; border: none; border-radius: 50px; cursor: pointer; transition: all 0.3s; }
        .btn.submit:hover { background: var(--accent-blue-hover); transform: translateY(-2px); }
        @media (max-width: 768px) {
            .form-row { grid-template-columns: 1fr; }
            .masina-header { flex-direction: column; text-align: center; }
            .masina-img { width: 100%; height: 220px; }
        }
    </style>
</head>
<body>

    <main class="rezervare-main">
        <div class="rezervare-card">
		<div class="masina-header">
			<div style="position: relative; flex-shrink: 0;">
				
				<?php if (!empty($masina['pret_promo']) && $masina['pret_promo'] > 0): 
					$pret_original = ($masina['pret_inchiriere'] > 0) ? $masina['pret_inchiriere'] : ($masina['pret_vanzare'] ?? 0);
					$reducere_procent = $pret_original > 0 
						? round((($pret_original - $masina['pret_promo']) / $pret_original) * 100) 
						: 0;
				?>
					<div style="position: absolute; top: 12px; left: 12px; background: #ff9800; color: white; 
								padding: 7px 16px; border-radius: 25px; font-weight: 700; font-size: 0.9rem;
								box-shadow: 0 4px 12px rgba(255, 152, 0, 0.4); z-index: 10;">
						PROMOȚIE -<?= $reducere_procent ?>%
					</div>
				<?php endif; ?>

				<img 
					class="masina-img"
					src="<?= htmlspecialchars($masina['poza_principala'] ?? 'resurse/imagini/placeholder-car.png') ?>"
					alt="<?= htmlspecialchars($masina['marca'] . ' ' . $masina['model']) ?>"
					onerror="this.src='resurse/imagini/placeholder-car.png';"
				>
			</div>

			<div class="masina-info">
				<h2><?= htmlspecialchars($masina['marca'] . ' ' . $masina['model']) ?></h2>
				<p>Status: <strong><?= ucfirst($masina['status']) ?></strong></p>
				
				<?php if (!empty($masina['pret_promo']) && $masina['pret_promo'] > 0): ?>
					<?php if ($masina['pret_inchiriere'] > 0): ?>
						<p style="margin: 0.5rem 0;">
							<span style="text-decoration: line-through; color: #888;">
								de la <?= number_format($masina['pret_inchiriere'], 2) ?> €/zi
							</span>
						</p>
						<p style="margin: 0; font-size: 1.4rem; color: #4caf50; font-weight: 700;">
							de la <?= number_format($masina['pret_promo'], 2) ?> €/zi
						</p>
					<?php elseif ($masina['pret_vanzare'] > 0): ?>
						<p style="margin: 0.5rem 0;">
							<span style="text-decoration: line-through; color: #888;">
								<?= number_format($masina['pret_vanzare'], 2) ?> €
							</span>
						</p>
						<p style="margin: 0; font-size: 1.4rem; color: #4caf50; font-weight: 700;">
							<?= number_format($masina['pret_promo'], 2) ?> €
						</p>
					<?php endif; ?>
				<?php else: ?>
					<?php if ($masina['pret_inchiriere'] > 0): ?>
						<p>Preț închiriere: <strong><?= number_format($masina['pret_inchiriere'], 2) ?> €/zi</strong></p>
					<?php elseif ($masina['pret_vanzare'] > 0): ?>
						<p>Preț vânzare: <strong><?= number_format($masina['pret_vanzare'], 2) ?> €</strong></p>
					<?php endif; ?>
				<?php endif; ?>
			</div>
		</div>

            <?php if ($eroare): ?>
                <p class="error-msg" style="text-align:center; padding:1rem; color:#ff4d4d;"><?= htmlspecialchars($eroare) ?></p>
            <?php endif; ?>

            <?php if ($succes): ?>
                <p class="success-msg" style="text-align:center; padding:1.5rem; color:#4caf50; font-size:1.2rem;"><?= htmlspecialchars($succes) ?></p>
            <?php else: ?>
                <form method="post" class="rezervare-form">
                    <h3 style="text-align:center; color:white; margin-bottom:1.5rem;">Alege perioada de închiriere</h3>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="start_date">Data ridicare</label>
                            <input type="date" id="start_date" name="start_date" required min="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="form-group">
                            <label for="start_time">Ora ridicare</label>
                            <input type="time" id="start_time" name="start_time" value="09:00" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="end_date">Data returnare</label>
                            <input type="date" id="end_date" name="end_date" required min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                        </div>
                        <div class="form-group">
                            <label for="end_time">Ora returnare</label>
                            <input type="time" id="end_time" name="end_time" value="18:00" required>
                        </div>
                    </div>

                    <div class="rezumat">
					<?php
					$pret_pe_zi_afisat = (!empty($masina['pret_promo']) && $masina['pret_promo'] > 0)
						? $masina['pret_promo']
						: ($masina['pret_inchiriere'] ?? 0);
					?>

					<p>
					Preț estimat: 
					<span class="pret-total">

					<?php if (!empty($masina['pret_promo']) && $masina['pret_promo'] > 0): ?>

					<span style="text-decoration:line-through;color:#888;">
					<?= number_format($masina['pret_inchiriere'], 2) ?> €/zi
					</span>
					<br>

					<?= number_format($masina['pret_promo'], 2) ?> €/zi × zile

					<?php else: ?>

					<?= number_format($masina['pret_inchiriere'], 2) ?> €/zi × zile

					<?php endif; ?>

					</span>
					</p>
                        <p>Depozit garanție: 500 € (returnabil la finalizare fără daune)</p>
                    </div>

                    <button type="submit" class="btn submit">Confirmă Rezervarea</button>
                </form>
            <?php endif; ?>
        </div>
    </main>

<?php include 'footer.php'; ?>
<script>
const startInput = document.getElementById('start_date');
const startTimeInput = document.getElementById('start_time');
const endInput = document.getElementById('end_date');
const endTimeInput = document.getElementById('end_time');
const pretAfisat = document.querySelector('.pret-total');
const pretPeZi = <?= (float)$pret_pe_zi_afisat ?>;

function calculPret() {
    if (startInput.value && endInput.value) {
        // construim obiecte de tip Date combinand data cu ora (format ISO: YYYY-MM-DDTHH:MM)
        const start = new Date(startInput.value + 'T' + startTimeInput.value);
        const end = new Date(endInput.value + 'T' + endTimeInput.value);
        
        if (end > start) {
            const diffTime = end - start;
            // calculam zilele folosind Math.ceil pentru a taxa orice fractiune de zi
            let diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            
            // afisam rezultatul formatat
            const total = (pretPeZi * diffDays).toFixed(2);
            pretAfisat.innerHTML = total + " € (" + diffDays + " zile)";
        } else {
            pretAfisat.innerHTML = "<span style='color: #ff4d4d; font-size: 1rem;'>Dată invalidă</span>";
        }
    }
}

[startInput, startTimeInput, endInput, endTimeInput].forEach(input => {
    input.addEventListener('change', calculPret);
});

// calculam o data la incarcarea paginii in caz ca sunt valori predefinite
window.onload = calculPret;
</script>
</body>
</html>