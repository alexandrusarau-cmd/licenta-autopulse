<?php
include 'functions.php';
include 'db_connect.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php?redirect=detalii-masina&id=" . ($_GET['id'] ?? ''));
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: masini.php?error=id_invalid");
    exit;
}

$id = (int)$_GET['id'];

// added LEFT JOIN pentru a prelua pretul incasat la vanzare
$stmt = $conn->prepare("
    SELECT c.id, c.marca, c.model, c.an, c.combustibil, c.transmisie, c.pret_inchiriere, c.pret_vanzare, c.pret_promo,
           c.status, c.numar_inmatriculare, c.vin, c.kilometraj, c.motorizare, c.putere, c.detalii, c.poza_principala,
           s.price AS pret_incasat
    FROM cars c 
    LEFT JOIN sales s ON c.id = s.car_id AND s.status = 'finalizat'
    WHERE c.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    header("Location: masini.php?error=masina_inexistenta");
    exit;
}

$masina = $result->fetch_assoc();
$perioade_stmt = $conn->prepare("
    SELECT start_date, end_date, status 
    FROM rentals
    WHERE car_id = ?
    AND status IN ('pending', 'confirmat')
    AND end_date > NOW()
    ORDER BY start_date ASC
");
$perioade_stmt->bind_param("i", $id);
$perioade_stmt->execute();
$perioade_result = $perioade_stmt->get_result();
$perioade_inchiriate = [];
while ($p = $perioade_result->fetch_assoc()) {
    $perioade_inchiriate[] = $p;
}
$perioade_stmt->close();
$stmt->close();
include 'header.php';

$status_lower = strtolower($masina['status']);
$este_vanzare = ($status_lower === 'de vanzare');
$este_vandut = ($status_lower === 'vandut');
$are_promo = (!empty($masina['pret_promo']) && $masina['pret_promo'] > 0);
?>

<head>
    <style>
			.car-image-wrapper {
				width: 100%;
				height: 280px;
				background: #0f141c;
				display: flex;
				align-items: center;
				justify-content: center;
			}

			.car-image-wrapper img {
				max-width: 100%;
				max-height: 100%;
				object-fit: contain;
			}
			.btn.reserve.disabled {
				background: #555 !important;
				color: #aaa !important;
				cursor: not-allowed !important;
				opacity: 0.6;
				transform: none !important;
				box-shadow: none !important;
			}

			.btn.reserve.disabled:hover {
				background: #555 !important;
				transform: none !important;
			}
			.car-detail-main {
				padding: 5rem 5% 4rem;
				background: var(--bg-dark);
				min-height: calc(100vh - 120px);
			}

			#thumbnails img {
				transition: border-color 0.1s ease, transform 0.1s ease;
			}
			.car-detail-card {
				background: var(--card-bg);
				border-radius: 14px;
				overflow: hidden;
				box-shadow: 0 6px 22px rgba(0,0,0,0.45);
				max-width: 760px;
				margin: 0 auto;
			}

			.car-detail-content {
				padding: 1.4rem 1.8rem;
			}

			.car-detail-title {
				font-size: 1.9rem;
				color: white;
				margin-bottom: 0.3rem;
				position: relative;
			}

			.car-detail-title::after {
				content: "";
				display: block;
				width: 70px;
				height: 3px;
				background: var(--accent-blue);
				margin-top: 6px;
				border-radius: 2px;
			}

			.price-big {
				text-align: center;
				font-size: 1.6rem;
				color: var(--accent-blue);
				font-weight: 700;
				margin: 0.8rem 0;
			}

			.status-badge {
				display: inline-block;
				padding: 0.35rem 1rem;
				border-radius: 18px;
				font-size: 0.85rem;
				margin-bottom: 1rem;
			}

			.status.disponibil { background:#4caf50;color:#fff;}
			.status.inchiriat  { background:#ff9800;color:#fff;}
			.status.vandut     { background:#f44336;color:#fff;}
			.status.de_vanzare     { background:#2196F3;color:#fff;}

			.car-detail-info {
				display: grid;
				grid-template-columns: repeat(auto-fit, minmax(180px,1fr));
				gap: 1rem;
				margin: 1.2rem 0;
			}

			.info-item {
				background: #1a2230;
				padding: 0.8rem 1rem;
				border-radius: 8px;
			}

			.info-label {
				font-size: 0.8rem;
				color: var(--gray);
			}

			.info-value {
				font-size: 1rem;
				font-weight: 600;
				color: white;
			}

			.action-buttons {
				display: flex;
				gap: 0.8rem;
				justify-content: center;
				margin-top: 1.2rem;
			}

			.btn.reserve {
				background: var(--accent-blue);
				color: white;
				padding: 0.75rem 1.6rem;
				border-radius: 40px;
				font-size: 1rem;
				text-decoration: none;
			}

			.btn.back {
				border: 2px solid var(--accent-blue);
				color: var(--accent-blue);
				padding: 0.75rem 1.6rem;
				border-radius: 40px;
				text-decoration: none;
			}

			@media (max-width: 768px) {
				.car-image-wrapper { height: 220px; }
				.action-buttons { flex-direction: column; }
			}

	</style>
</head>

<body>

<main class="car-detail-main">
	<div class="car-detail-card" style="max-width: 1200px; margin: 0 auto;">

		<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 3rem; padding: 2rem;">

		<div>
			<div class="car-image-wrapper" style="position: relative; height: 460px; border-radius: 16px; overflow: hidden;" 
			 onmouseover="showArrows()" onmouseout="hideArrows()">

				<?php 
				if (!$este_vandut && $are_promo): 
					$pret_original = $este_vanzare ? ($masina['pret_vanzare'] ?? 0) : ($masina['pret_inchiriere'] ?? 0);
					$reducere_procent = $pret_original > 0 ? round((($pret_original - $masina['pret_promo']) / $pret_original) * 100) : 0;
				?>
					<div style="position: absolute; top: 15px; left: 15px; background: #ff9800; color: white; 
								padding: 7px 16px; border-radius: 25px; font-weight: 700; font-size: 0.95rem;
								box-shadow: 0 4px 12px rgba(255, 152, 0, 0.4); z-index: 10;">
						PROMOȚIE -<?= $reducere_procent ?>%
					</div>
				<?php endif; ?>

			<img id="mainPhoto" src="<?= htmlspecialchars($masina['poza_principala']) ?>"
				 style="width:100%; height:100%; object-fit: contain;"
				 onerror="this.src='resurse/imagini/placeholder-car.png';">

			<button id="prevBtn" onclick="prevPhoto()" 
					style="position: absolute; top: 50%; left: 15px; transform: translateY(-50%);
						   background: rgba(0,0,0,0.4); color: white; border: none; width: 48px; height: 48px;
						   border-radius: 50%; font-size: 1.8rem; cursor: pointer; z-index: 15; opacity: 0; transition: all 0.3s;">
				←
			</button>
			<button id="nextBtn" onclick="nextPhoto()" 
					style="position: absolute; top: 50%; right: 15px; transform: translateY(-50%);
						   background: rgba(0,0,0,0.4); color: white; border: none; width: 48px; height: 48px;
						   border-radius: 50%; font-size: 1.8rem; cursor: pointer; z-index: 15; opacity: 0; transition: all 0.3s;">
				→
			</button>
		</div>
            <div id="thumbnails" style="margin-top: 18px; display: flex; gap: 12px; flex-wrap: wrap; justify-content: center;">
                <?php
                $car_folder = 'resurse/imagini/masini/car_' . $masina['id'] . '/';
                $full_path = $_SERVER['DOCUMENT_ROOT'] . '/web/' . $car_folder;
                $all_photos = [];
                if (!empty($masina['poza_principala'])) $all_photos[] = $masina['poza_principala'];
                if (is_dir($full_path)) {
                    $photos = glob($full_path . '*.{jpg,jpeg,png,webp,gif}', GLOB_BRACE);
                    foreach ($photos as $photo) {
                        $relative = str_replace($_SERVER['DOCUMENT_ROOT'] . '/web/', '', $photo);
						if (!empty($masina['poza_principala']) && $relative === $masina['poza_principala']) {
									continue;
								}
								$all_photos[] = $relative;
							}
                }
                foreach ($all_photos as $index => $photo_path) {
                    echo '
                    <img src="' . htmlspecialchars($photo_path) . '" 
                         style="width: 95px; height: 65px; object-fit: cover; border-radius: 8px; cursor: pointer; border: 2px solid #555;"
                         onclick="changePhoto(' . $index . ')">';
                }
                ?>
            </div>
        </div>

        <div>
            <h1 class="car-detail-title"><?= htmlspecialchars($masina['marca'].' '.$masina['model']) ?></h1>
            <div class="price-big" style="margin: 1.2rem 0;">
				<?php 
				if ($status_lower === 'vandut'): 
					$pret_istoric = (!empty($masina['pret_incasat'])) ? $masina['pret_incasat'] : $masina['pret_vanzare'];
				?>
					<span style="font-size: 2.6rem; font-weight: 800; color: var(--accent-blue);">
						<?= number_format($pret_istoric, 2) ?> €
					</span>

				<?php elseif ($status_lower === 'de vanzare'): ?>
					<?php if ($are_promo): ?>
						<span style="text-decoration: line-through; color: #888; font-size: 1.2rem;">
							<?= number_format($masina['pret_vanzare'], 2) ?> €
						</span><br>
						<span style="color: #4caf50; font-weight: 800; font-size: 2.6rem;">
							<?= number_format($masina['pret_promo'], 2) ?> €
						</span>
					<?php else: ?>
						<span style="font-size: 2.6rem; font-weight: 800; color: var(--accent-blue);">
							<?= number_format($masina['pret_vanzare'], 2) ?> €
						</span>
					<?php endif; ?>

				<?php else: //inchirieri/ocupate ?>
					<?php if ($are_promo): ?>
						<?php if ($masina['pret_inchiriere'] > 0): ?>
							<span style="text-decoration: line-through; color: #888; font-size: 1.1rem;">
								de la <?= number_format($masina['pret_inchiriere'], 2) ?> €/zi
							</span><br>
						<?php endif; ?>
						<span style="color: #4caf50; font-weight: 700; font-size: 2.4rem;">
							de la <?= number_format($masina['pret_promo'], 2) ?> €/zi
						</span>
					<?php else: ?>
						<span style="font-size: 2.4rem; font-weight: 700; color: var(--accent-blue);">
							de la <?= number_format($masina['pret_inchiriere'], 2) ?> €/zi
						</span>
					<?php endif; ?>
				<?php endif; ?>
			</div>

			<span class="status-badge status <?= str_replace(' ', '_', $status_lower) ?>">
				<?php 
                    if ($status_lower === 'disponibil') {
                        echo 'Disponibil închiriere';
                    } elseif ($status_lower === 'inchiriat') {
                        echo 'Ocupat în prezent';
                    } elseif ($status_lower === 'de vanzare') {
                        echo 'De vânzare';
                    } else {
                        echo ucfirst(htmlspecialchars($masina['status']));
                    }
                ?>
			</span>

        <?php if (!empty($perioade_inchiriate)): ?>
        <div style="margin-top: 2rem; background: #1a2230; padding: 1.5rem; border-radius: 12px; border: 1px solid #2a3443;">
            <strong style="color: #ff9800; display: block; margin-bottom: 1rem;">Perioade de închiriere active:</strong>
            <?php foreach ($perioade_inchiriate as $p): ?>
            <div style="margin-bottom: 0.8rem; padding-left: 1rem; border-left: 4px solid <?= $p['status'] === 'pending' ? '#ff9800' : '#4caf50' ?>;">
                <strong><?= $p['status'] === 'pending' ? 'În așteptare (pending)' : 'Confirmată' ?></strong><br>
                <span style="color: #aaa;">
                    <?= date('d.m.Y H:i', strtotime($p['start_date'])) ?> –
                    <?= date('d.m.Y H:i', strtotime($p['end_date'])) ?>
                </span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="action-buttons" style="margin-top: 2.5rem; display: flex; gap: 15px; flex-wrap: wrap; justify-content: center;">

		<?php if ($status_lower === 'disponibil'): ?>
			<a href="rezervare.php?id=<?= $masina['id'] ?>" class="btn reserve">Rezervă acum</a>

		<?php elseif ($status_lower === 'de vanzare'): ?>
			<a href="cumpara.php?id=<?= $masina['id'] ?>" class="btn"
			   style="background: #4caf50; color: white; padding: 0.9rem 2.2rem; border-radius: 40px; font-weight: 600; text-decoration: none;">
				Cumpără acum
			</a>

		<?php elseif ($status_lower === 'inchiriat'): ?>
            <a href="rezervare.php?id=<?= $masina['id'] ?>" class="btn reserve" style="background: #ff9800;">
                Rezervă pentru altă perioadă
            </a>

		<?php elseif ($status_lower === 'in_service'): ?>
			<button class="btn reserve disabled" disabled style="opacity: 0.65; cursor: not-allowed;">
				În service
			</button>

		<?php elseif ($status_lower === 'vandut'): ?>
		<?php endif; ?>

		<a href="masini.php" class="btn back">Înapoi la flotă</a>
	</div>
        </div>
    </div>

    <div style="padding: 0 2rem 2rem;">
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 1.4rem; margin: 2.5rem 0;">
            <div class="info-item"><div class="info-label">An</div><div class="info-value"><?= $masina['an'] ?></div></div>
            <div class="info-item"><div class="info-label">Combustibil</div><div class="info-value"><?= $masina['combustibil'] ?></div></div>
            <div class="info-item"><div class="info-label">Motorizare</div><div class="info-value"><?= $masina['motorizare'] ?></div></div>
            <div class="info-item"><div class="info-label">Transmisie</div><div class="info-value"><?= $masina['transmisie'] ?></div></div>
            <div class="info-item"><div class="info-label">KM</div><div class="info-value"><?= number_format($masina['kilometraj']) ?></div></div>
            <div class="info-item">
                <div class="info-label">Putere</div>
                <div class="info-value">
                    <?php
                    if (!empty($masina['putere'])) {
                        $cp = (int)$masina['putere'];
                        $kw = round($cp * 0.7355);
                        echo $cp . ' CP / ' . $kw . ' kW';
                    } else {
                        echo '-';
                    }
                    ?>
                </div>
            </div>
        </div>

        <?php if (!empty($masina['detalii'])): ?>
        <div style="margin: 2.5rem 0; padding: 2rem; background: #1a2230; border-radius: 12px;">
            <h3 style="color: white; margin-bottom: 1rem;">Descriere detaliată</h3>
            <p style="line-height: 1.8; color: #ddd;"><?= nl2br(htmlspecialchars($masina['detalii'])) ?></p>
        </div>
        <?php endif; ?>
    </div>

</div>
</main>

<?php include 'footer.php'; ?>
<script>
let currentIndex = 0;
const photos = <?= json_encode($all_photos) ?>;

function changePhoto(index) {
    currentIndex = index;
    
    const mainPhoto = document.getElementById('mainPhoto');
    const thumbs = document.querySelectorAll('#thumbnails img');
    
    mainPhoto.src = photos[index];
    
    thumbs.forEach(img => {
        img.style.borderColor = '#333'; 
        img.style.transform = 'scale(1)';
        img.style.boxShadow = 'none';
    });

    if (thumbs[index]) {
        thumbs[index].style.borderColor = 'var(--accent-blue)'; 
        thumbs[index].style.transform = 'scale(1.05)';
        thumbs[index].style.boxShadow = '0 0 10px rgba(0, 102, 204, 0.3)';
    }
}

function prevPhoto() {
    currentIndex = (currentIndex - 1 + photos.length) % photos.length;
    changePhoto(currentIndex);
}

function nextPhoto() {
    currentIndex = (currentIndex + 1) % photos.length;
    changePhoto(currentIndex);
}

document.addEventListener('DOMContentLoaded', () => {
    if (photos.length > 0) {
        changePhoto(0);
    }
});

function showArrows() {
    document.getElementById('prevBtn').style.opacity = '1';
    document.getElementById('nextBtn').style.opacity = '1';
}

function hideArrows() {
    document.getElementById('prevBtn').style.opacity = '0';
    document.getElementById('nextBtn').style.opacity = '0';
}
</script>
</body>
</html>