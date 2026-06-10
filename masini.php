<?php
include 'functions.php';
include 'db_connect.php';

// if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
//     header("Location: login.php");
//     exit;
// }

$where = [];
$params = [];
$types = "";
$where[] = "c.vizibil = 1";
$search       = trim($_GET['search'] ?? '');
$marca        = trim($_GET['marca'] ?? '');
$combustibil  = trim($_GET['combustibil'] ?? '');
$status       = trim($_GET['status'] ?? '');
$pret_min     = !empty($_GET['pret_min']) ? (float)$_GET['pret_min'] : null;
$pret_max     = !empty($_GET['pret_max']) ? (float)$_GET['pret_max'] : null;

if ($search !== '') {
    $like = "%$search%";
    $where[] = "(c.marca LIKE ? OR c.model LIKE ? OR c.numar_inmatriculare LIKE ? OR c.vin LIKE ? OR c.motorizare LIKE ?)";
    $params = array_merge($params, [$like, $like, $like, $like, $like]);
    $types .= "sssss";
}

if ($marca !== '') {
    $where[] = "c.marca = ?";
    $params[] = $marca;
    $types .= "s";
}

if ($combustibil !== '') {
    $where[] = "c.combustibil = ?";
    $params[] = $combustibil;
    $types .= "s";
}

if ($status !== '') {
    $where[] = "c.status = ?";
    $params[] = $status;
    $types .= "s";
}

if ($pret_min !== null) {
    $where[] = "(c.pret_inchiriere >= ? OR c.pret_vanzare >= ?)";
    $params[] = $pret_min;
    $params[] = $pret_min;
    $types .= "dd";
}

if ($pret_max !== null) {
    $where[] = "(c.pret_inchiriere <= ? OR c.pret_vanzare <= ?)";
    $params[] = $pret_max;
    $params[] = $pret_max;
    $types .= "dd";
}

$where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

$sql = "SELECT c.id AS id_masina, c.marca, c.model, c.an, c.combustibil, c.transmisie, 
               c.pret_inchiriere, c.pret_vanzare, c.pret_promo,
               c.status, c.numar_inmatriculare, c.vin, 
               c.kilometraj, c.motorizare, c.detalii,
               c.poza_principala,
               MAX(s.price) AS pret_incasat
        FROM cars c
        LEFT JOIN sales s ON c.id = s.car_id AND s.status = 'finalizat'
        $where_clause 
        GROUP BY c.id
        ORDER BY c.marca, c.model 
        LIMIT 60";

$stmt = $conn->prepare($sql);
if ($stmt) {
    if ($types) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $masini = $stmt->get_result();
} else {
    $eroare = "Eroare la interogare: " . $conn->error;
}

include 'header.php';
?>

<head>
    <style>
        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            max-width: 1400px;
            margin: 0 auto 2.5rem;
            padding: 1.5rem;
            background: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }
        .filter-group {
            flex: 1;
            min-width: 160px;
        }
        .filter-group input, .filter-group select {
            width: 100%;
            padding: 0.9rem;
            border-radius: 8px;
            border: 1px solid #2a3443;
            background: #1a2230;
            color: white;
            font-size: 0.95rem;
        }
        .filter-group input::placeholder { color: #5a677a; }
        .no-results { text-align: center; padding: 4rem 1rem; color: var(--gray); font-size: 1.3rem; }
        .car-card .price { font-weight: 700; font-size: 1.3rem; color: var(--accent-blue); margin: 0.4rem 0; }
        .car-card .status {
            display: inline-block;
            padding: 0.35rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            margin-top: 0.6rem;
        }
        .status.disponibil { background: #4caf50; color: white; }
        .status.inchiriat  { background: #ff9800; color: white; }
        .status.vandut     { background: #f44336; color: white; }
        .status.in_service { background: #757575; color: white; }
        .status.de_vanzare { background: #2196F3; color: white; }
    </style>
</head>
<body>
<main class="section cars">
    <h2>Flota Noastră</h2>

    <form method="get" class="filter-form">
        <div class="filter-group">
            <input type="text" name="search" placeholder="Căutare marcă / model / nr. înmatriculare / VIN..." value="<?= htmlspecialchars($search ?? '') ?>">
        </div>
		<div class="filter-group">
			<select name="marca">
				<option value="">Toate mărcile</option>
				<?php
				$marci_stmt = $conn->query("SELECT DISTINCT marca FROM cars WHERE marca IS NOT NULL AND marca != '' ORDER BY marca ASC");
				while ($m = $marci_stmt->fetch_assoc()) {
					$selected = ($marca === $m['marca']) ? 'selected' : '';
					echo '<option value="' . htmlspecialchars($m['marca']) . '" ' . $selected . '>' 
						 . htmlspecialchars($m['marca']) . '</option>';
				}
				$marci_stmt->free();
				?>
			</select>
		</div>
		<div class="filter-group">
			<select name="combustibil">
				<option value="">Toate combustibilele</option>
				<?php
				$combustibili_stmt = $conn->query("
					SELECT DISTINCT combustibil 
					FROM cars 
					WHERE combustibil IS NOT NULL AND combustibil != '' 
					ORDER BY combustibil ASC
				");
				while ($c = $combustibili_stmt->fetch_assoc()) {
					$selected = ($combustibil === $c['combustibil']) ? 'selected' : '';
					echo '<option value="' . htmlspecialchars($c['combustibil']) . '" ' . $selected . '>' 
						 . htmlspecialchars($c['combustibil']) . '</option>';
				}
				$combustibili_stmt->free();
				?>
			</select>
		</div>
        <div class="filter-group">
            <select name="status">
                <option value="">Toate statusurile</option>
                <option value="disponibil" <?= ($status ?? '') === 'disponibil' ? 'selected' : '' ?>>Disponibil - inchiriere</option>
                <option value="inchiriat" <?= ($status ?? '') === 'inchiriat' ? 'selected' : '' ?>>Închiriat</option>
                <option value="vandut" <?= ($status ?? '') === 'vandut' ? 'selected' : '' ?>>Vândut</option>
				<option value="de vanzare" <?= ($status === 'de vanzare') ? 'selected' : '' ?>>De vanzare</option>
            </select>
        </div>
        <div class="filter-group" style="display:flex; gap:0.5rem;">
            <input type="number" name="pret_min" placeholder="Preț min" value="<?= htmlspecialchars($pret_min ?? '') ?>">
            <input type="number" name="pret_max" placeholder="Preț max" value="<?= htmlspecialchars($pret_max ?? '') ?>">
        </div>
        <button type="submit" class="btn primary">Filtrează</button>
        <a href="masini.php" class="btn secondary">Resetează</a>
    </form>

<div class="car-grid">
    <?php if (isset($eroare)): ?>
        <p class="error-msg"><?= htmlspecialchars($eroare) ?></p>
    
    <?php elseif ($masini && $masini->num_rows > 0): ?>
       
        <?php while ($car = $masini->fetch_assoc()): ?>
        
        <a href="detalii-masina.php?id=<?= $car['id_masina'] ?>" class="car-card" 
           style="<?= (!empty($car['pret_promo']) && $car['pret_promo'] > 0) ? 'padding-top: 35px;' : '' ?>">

		<div class="car-image-wrapper" style="position: relative;">
			<?php 
			$st = strtolower($car['status']);
			$este_vanzare = ($st === 'de vanzare');
			$este_vandut = ($st === 'vandut');

			if (!$este_vandut && !empty($car['pret_promo']) && $car['pret_promo'] > 0): 
				$pret_original = $este_vanzare ? ($car['pret_vanzare'] ?? 0) : ($car['pret_inchiriere'] ?? 0);
				$reducere_procent = $pret_original > 0 ? round((($pret_original - $car['pret_promo']) / $pret_original) * 100) : 0;
			?>
				<div style="position: absolute; top: -18px; left: 15px; background: #ff9800; color: white; 
							padding: 7px 16px; border-radius: 25px; font-weight: 700; font-size: 0.85rem;
							box-shadow: 0 4px 12px rgba(255, 152, 0, 0.4); z-index: 10;">
					PROMOȚIE -<?= $reducere_procent ?>%
				</div>
			<?php endif; ?>

        <img 
            src="<?= htmlspecialchars($car['poza_principala'] ?? 'resurse/imagini/placeholder-car.png') ?>" 
            alt="<?= htmlspecialchars($car['marca'] . ' ' . $car['model']) ?>"
            loading="lazy"
            onerror="this.onerror=null; this.src='resurse/imagini/placeholder-car.png';"
        >
    </div>

    <div class="car-info">
        <h3><?= htmlspecialchars($car['marca'] . ' ' . $car['model']) ?></h3>
        
        <p class="details">
            <?= $car['an'] ?? 'N/A' ?> • 
            <?= htmlspecialchars($car['combustibil'] ?? '-') ?> • 
            <?= htmlspecialchars($car['transmisie'] ?? '-') ?> • 
            <?= number_format($car['kilometraj'] ?? 0) ?> km
        </p>

<p class="price">
    <?php 
    $st = strtolower($car['status']);
    $are_promo = (!empty($car['pret_promo']) && $car['pret_promo'] > 0);
    
    // 1. masina e vanduta = verificam daca exista pret incasat in sales
    if ($st === 'vandut'): 
        $pret_final_vanzare = (!empty($car['pret_incasat'])) ? $car['pret_incasat'] : $car['pret_vanzare'];
    ?>
        <?= number_format($pret_final_vanzare, 2) ?> €

    <?php 
    // 2. masina pt sell?
    elseif ($st === 'de vanzare'): ?>
        <?php if ($are_promo): ?>
            <span style="text-decoration: line-through; color: #888; font-size: 0.95rem;">
                <?= number_format($car['pret_vanzare'], 2) ?> €
            </span><br>
            <span style="color: #4caf50; font-weight: 700; font-size: 1.4rem;">
                <?= number_format($car['pret_promo'], 2) ?> €
            </span>
        <?php else: ?>
            <?= number_format($car['pret_vanzare'], 2) ?> €
        <?php endif; ?>

    <?php 
    // 3. masina pentru rent?
    else: ?>
        <?php if ($are_promo): ?>
            <?php if ($car['pret_inchiriere'] > 0): ?>
                <span style="text-decoration: line-through; color: #888;">
                    de la <?= number_format($car['pret_inchiriere'], 2) ?> €/zi
                </span><br>
            <?php endif; ?>
            <span style="color: #4caf50; font-weight: 700; font-size: 1.4rem;">
                de la <?= number_format($car['pret_promo'], 2) ?> €
            </span>

        <?php else: ?>
            <?php if ($car['pret_inchiriere'] > 0): ?>
                de la <?= number_format($car['pret_inchiriere'], 2) ?> €/zi
            <?php elseif ($car['pret_vanzare'] > 0): ?>
                <?= number_format($car['pret_vanzare'], 2) ?> €
            <?php else: ?>
                Preț la cerere
            <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>
	</p>

        <span class="status <?= str_replace(' ', '_', htmlspecialchars(strtolower($car['status']))) ?>">
			<?php 
                $current_status = strtolower($car['status']);
                if ($current_status === 'disponibil') {
                    echo 'Disponibil închiriere';
                } elseif ($current_status === 'inchiriat') {
                    echo 'Ocupat în prezent';
                } elseif ($current_status === 'de vanzare') {
                    echo 'De vânzare';
                } else {
                    echo ucfirst(htmlspecialchars($car['status']));
                }
            ?>
		</span>
    </div>
</a>
        <?php endwhile; ?>

    <?php else: ?>
        <p class="no-results">Nicio mașină nu corespunde criteriilor tale.</p>
    <?php endif; ?>
</div>
</main>

<?php include 'footer.php'; ?>

</body>
</html>

<?php
if (isset($stmt)) $stmt->close();
$conn->close();
?>