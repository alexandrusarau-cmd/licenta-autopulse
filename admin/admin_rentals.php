<?php
session_start();
if(!isset($_SESSION['admin_loggedin'])) header("Location: admin_login.php");

include '../db_connect.php';
$conn->set_charset("utf8");

if(isset($_POST['action']) && $_POST['action'] === 'add_rental'){

    $user_id = (int)$_POST['user_id'];
    $car_id  = (int)$_POST['car_id'];
    $start   = $_POST['start_date'];
    $end     = $_POST['end_date'];
    $price   = (float)$_POST['price'];

    // verificam disponibilitate masina
    $check = $conn->prepare("
        SELECT id FROM rentals 
        WHERE car_id=? 
        AND status IN ('pending','confirmat')
        AND NOT (end_date < ? OR start_date > ?)
    ");
    $check->bind_param("iss", $car_id, $start, $end);
    $check->execute();
    $checkRes = $check->get_result();

    if($checkRes->num_rows > 0){
        echo "Masina nu este disponibila in aceasta perioada";
        exit;
    }

    // inseram rezervare
    $stmt = $conn->prepare("
        INSERT INTO rentals (user_id, car_id, start_date, end_date, price, status)
        VALUES (?, ?, ?, ?, ?, 'pending')
    ");
    $stmt->bind_param("iissd", $user_id, $car_id, $start, $end, $price);
    if($stmt->execute()){
        echo "ok";
    } else {
        echo $conn->error;
    }
    exit;
}

if(isset($_POST['action']) && $_POST['action'] === 'delete_rental'){
    $id = (int)$_POST['id'];
    // preluare car_id
    $carRes = $conn->query("SELECT car_id FROM rentals WHERE id=$id");
    if($carRow = $carRes->fetch_assoc()){
        $car_id = $carRow['car_id'];
        $conn->query("DELETE FROM rentals WHERE id=$id");
        $conn->query("UPDATE cars SET status='disponibil' WHERE id=$car_id");
        echo "ok";
    } else {
        echo "Rezervare inexistenta";
    }
    exit;
}

if(isset($_POST['action']) && $_POST['action'] === 'update_status'){
    $id = (int)$_POST['id'];
    $status = $_POST['status'];

    $info = $conn->prepare("SELECT user_id, car_id, start_date, end_date, price FROM rentals WHERE id = ?");
    $info->bind_param("i", $id);
    $info->execute();
    $row = $info->get_result()->fetch_assoc();

    if(!$row){
        echo "Rezervare inexistenta";
        exit;
    }

    $user_id   = $row['user_id'];
    $car_id    = $row['car_id'];
    $start     = $row['start_date'];
    $end       = $row['end_date'];
    $price     = $row['price'];

    $stmt = $conn->prepare("UPDATE rentals SET status=? WHERE id=?");
    $stmt->bind_param("si", $status, $id);
    $stmt->execute();

    if(strtolower($status) === 'confirmat' || strtolower($status) === 'confirmata'){
        
        $contract_number = 'CON-' . date('Ymd') . '-' . str_pad($id, 5, '0', STR_PAD_LEFT);

        $insert = $conn->prepare("
            INSERT INTO contracts 
            (rental_id, user_id, car_id, contract_number, start_date, end_date, pret_total, depozit, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 500.00, 'activ')
        ");

        $insert->bind_param("iiisssd", $id, $user_id, $car_id, $contract_number, $start, $end, $price);
        
        if($insert->execute()){
            echo "ok";
        } else {
            echo "ok";
        }
    } 
    elseif(in_array(strtolower($status), ['finalizat', 'anulata'])){
        $conn->query("UPDATE cars SET status='disponibil' WHERE id=$car_id");
        echo "ok";
    } 
    else {
        echo "ok";
    }

    exit;
}

$filter_sql = "";
$params = [];
if(isset($_GET['status']) && $_GET['status'] != ""){
    $filter_sql = "WHERE r.status=?";
    $params[] = $_GET['status'];
}

$sql = "
    SELECT
        r.id,
        u.username,
        r.user_id,
        c.marca,
        c.model,
        r.car_id,
        r.start_date,
        r.end_date,
        r.status,
        r.price,
        con.id as contract_id
    FROM rentals r
    JOIN users u ON r.user_id = u.id
    JOIN cars c ON r.car_id = c.id
    LEFT JOIN contracts con ON con.rental_id = r.id
    $filter_sql
    ORDER BY r.id ASC
";

$contracts_archive = $conn->query("
    SELECT 
        con.id AS contract_id,
        con.contract_number,
        u.username,
        ca.marca,
        ca.model,
        con.check_out_date,
        con.check_out_km,
        con.check_out_cost
    FROM contracts con
    JOIN users u ON con.user_id = u.id
    JOIN cars ca ON con.car_id = ca.id
    WHERE con.status = 'finalizat'
    ORDER BY con.check_out_date DESC
");

$stmt = $conn->prepare($sql);
if(count($params) > 0){
    $stmt->bind_param("s",$params[0]);
}
$stmt->execute();
$result = $stmt->get_result();
include 'admin_header.php';
?>
<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<title>Gestionare Rezervari</title>
<link rel="stylesheet" href="../resurse/css/admin.css">
</head>
<body>
<main>
<h2>Lista Rezervari</h2>

<div style="margin-bottom: 2rem; display: flex; gap: 10px;">
    <button type="button" id="tabActiveBtn" onclick="switchTab('active')" style="background: #ffcc00; color: #11151d; border: 1px solid #ffcc00; padding: 12px 24px; font-weight: bold; border-radius: 6px; cursor: pointer; text-transform: uppercase;">
        Rezervări Active
    </button>
    <button type="button" id="tabArchiveBtn" onclick="switchTab('archive')" style="background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; padding: 12px 24px; font-weight: normal; border-radius: 6px; cursor: pointer; text-transform: uppercase;">
        Arhivă Contracte
    </button>
</div>

<div id="sectionActive" style="display: block;">
    <form method="get">
    <label style="font-weight: 600;">Filtru status:</label>
    <select name="status" onchange="this.form.submit()" style="padding: 6px; border-radius: 4px;">
        <option value="">Toate</option>
        <option value="pending" <?= ($_GET['status']??'')=='pending'?'selected':'' ?>>Pending</option>
        <option value="confirmat" <?= ($_GET['status']??'')=='confirmat'?'selected':'' ?>>Confirmat</option>
        <option value="finalizat" <?= ($_GET['status']??'')=='finalizat'?'selected':'' ?>>Finalizat</option>
        <option value="anulata" <?= ($_GET['status']??'')=='anulata'?'selected':'' ?>>Anulata</option>
    </select>
    </form>
    <br>
    <button id="addRentalBtn">Adauga rezervare</button>
    <br><br>

    <table border="1" cellpadding="10" style="width: 100%; border-collapse: collapse;">
    <thead>
            <th>ID</th>
            <th>Client</th>
            <th>Masina</th>
            <th>Data start</th>
            <th>Data final</th>
            <th>Status</th>
            <th>Pret</th>
            <th style="text-align: left; padding-left: 10px;">Actiuni</th>
        </tr>
    </thead>
    <tbody>
    <?php while($row = $result->fetch_assoc()){ ?>
    <tr>
        <td><?= $row['id'] ?></td>
        <td><?= htmlspecialchars($row['username']) ?> <small style="display:block; color:#64748b;">(ID <?= $row['user_id'] ?>)</small></td>
        <td><?= htmlspecialchars($row['marca']." ".$row['model']) ?> <small style="display:block; color:#64748b;">(ID <?= $row['car_id'] ?>)</small></td>
        <td style="text-align: center;"><?= $row['start_date'] ?></td>
        <td style="text-align: center;"><?= $row['end_date'] ?></td>
        <td style="text-align: center;">
            <select class="rental-status" data-id="<?= $row['id'] ?>">
                <option value="pending" <?= $row['status']=='pending'?'selected':'' ?>>Pending</option>
                <option value="confirmat" <?= $row['status']=='confirmat'?'selected':'' ?>>Confirmat</option>
                <option value="finalizat" <?= $row['status']=='finalizat'?'selected':'' ?>>Finalizat</option>
                <option value="anulata" <?= $row['status']=='anulata'?'selected':'' ?>>Anulata</option>
            </select>
        </td>
        <td style="text-align: right; color: #ef4444; font-weight: bold;"><?= number_format($row['price'], 0) ?> €</td>
        <td style="text-align: left; padding-left: 10px;">
            <button class="delete-rental" data-id="<?= $row['id'] ?>">Sterge</button>
            <?php if (in_array(strtolower($row['status']), ['confirmat', 'confirmata'])): ?>
                <?php if (!empty($row['contract_id'])): ?>
                    <a href="check.php?id=<?= $row['contract_id'] ?? 0 ?>" 
                       class="btn"
                       style="background:#ff9800; color:#fff; padding:6px 12px; text-decoration:none; border-radius:6px; margin-left:8px; display:inline-block;">
                        Check-in/out
                    </a>
                <?php else: ?>
                    <span style="color:#ff9800; font-size:0.9rem; margin-left:8px;">(Contract în curs)</span>
                <?php endif; ?>
            <?php endif; ?>
        </td>
    </tr>
    <?php } ?>
    </tbody>
    </table>
</div>

<div id="sectionArchive" style="display: none;">
    <div style="margin-bottom: 1.5rem;">
        <label style="font-weight: 600;">Căutare rapidă în arhivă: </label>
        <input type="text" id="contractSearchInput" onkeyup="filterContractsArchive()" placeholder="Caută după nr. contract, client sau mașină..." style="padding: 8px 12px; width: 350px; border-radius: 6px; border: 1px solid #ccc; outline: none;">
    </div>
    
    <table border="1" cellpadding="10" style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background: #f1f5f9;">
                <th>Nr. Contract</th>
                <th>Client</th>
                <th>Masina</th>
                <th>Data Închidere</th>
                <th>Km la Retur</th>
                <th>Costuri Extra</th>
                <th>Acțiuni</th>
            </tr>
        </thead>
        <tbody id="archiveTableBody">
            <?php if($contracts_archive->num_rows === 0): ?>
                <tr class="no-data-row">
                    <td colspan="7" style="text-align:center; color:#64748b;">Nu există contracte finalizate în arhivă.</td>
                </tr>
            <?php else: ?>
                <?php while($c_row = $contracts_archive->fetch_assoc()): ?>
                <tr class="archive-row">
                    <td><strong class="searchable-data" style="color: #2ecc71;"><?= htmlspecialchars($c_row['contract_number']) ?></strong></td>
                    <td class="searchable-data"><?= htmlspecialchars($c_row['username']) ?></td>
                    <td class="searchable-data"><?= htmlspecialchars($c_row['marca'] . " " . $c_row['model']) ?></td>
					<td style="text-align: center;">
						<?php if (!empty($c_row['check_out_date']) && $c_row['check_out_date'] !== '0000-00-00 00:00:00'): ?>
							<?= date('d.m.Y H:i', strtotime($c_row['check_out_date'])) ?>
						<?php else: ?>
							<span style="color: #000;">În curs de returnare...</span>
						<?php endif; ?>
					</td>
                    <td style="text-align: center;"><?= number_format($c_row['check_out_km'], 0, '.', ',') ?> km</td>
                    <td style="text-align: right; color: #ef4444; font-weight: bold;"><?= number_format($c_row['check_out_cost'], 2) ?> €</td>
                    <td style="text-align: center;">
							<a href="check.php?id=<?= $c_row['contract_id'] ?>" style="background:#ef4444; color:#fff; padding:6px 12px; text-decoration:none; border-radius:4px; font-size:0.85rem; font-weight:600; display:inline-block;">
								Vezi Detalii
							</a>
							<a href="../contract.php?id=<?= $c_row['contract_id'] ?>" style="background:#0066b1; color:#fff; padding:6px 12px; text-decoration:none; border-radius:4px; font-size:0.85rem; font-weight:600; display:inline-block;" target="_blank">
								Vezi Contract
							</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div id="rentalModal" style="display:none; background:#fff; padding:20px; border:2px solid black; width:450px; position:fixed; top:10%; left:50%; transform:translateX(-50%); z-index:1000; color:#000;">
<h3>Rezervare noua</h3>
<label>Client:</label>
<select id="rentalClient">
<?php
$modal_users = $conn->query("SELECT id, username FROM users ORDER BY username ASC");
while($u = $modal_users->fetch_assoc()){
    echo "<option value='{$u['id']}'>{$u['username']} (ID {$u['id']})</option>";
}
?>
</select><br><br>
<label>Masina:</label>
<select id="rentalCar">
<?php
$cars_list = $conn->query("SELECT id, marca, model, pret_inchiriere FROM cars WHERE status='disponibil'");
while($c = $cars_list->fetch_assoc()){
    echo "<option value='{$c['id']}' data-pret='{$c['pret_inchiriere']}'>{$c['marca']} {$c['model']}</option>";
}
?>
</select><br><br>
<label>Data start:</label><input type="date" id="rentalStart"><br><br>
<label>Data final:</label><input type="date" id="rentalEnd"><br><br>
<label>Pret total:</label><input type="number" id="rentalPrice"><br><br>
<button id="saveRentalBtn">Salveaza</button>
<button onclick="document.getElementById('rentalModal').style.display='none'">Inchide</button>
</div>

<script>
function switchTab(tabName) {
    const activeSec = document.getElementById('sectionActive');
    const archiveSec = document.getElementById('sectionArchive');
    const activeBtn = document.getElementById('tabActiveBtn');
    const archiveBtn = document.getElementById('tabArchiveBtn');

    if (tabName === 'active') {
        activeSec.style.display = 'block';
        archiveSec.style.display = 'none';
        
        activeBtn.style.background = '#ffcc00';
        activeBtn.style.color = '#11151d';
        activeBtn.style.fontWeight = 'bold';
        activeBtn.style.border = '1px solid #ffcc00';
        
        archiveBtn.style.background = '#f1f5f9';
        archiveBtn.style.color = '#334155';
        archiveBtn.style.fontWeight = 'normal';
        archiveBtn.style.border = '1px solid #cbd5e1';
    } else {
        activeSec.style.display = 'none';
        archiveSec.style.display = 'block';
        
        archiveBtn.style.background = '#ffcc00';
        archiveBtn.style.color = '#11151d';
        archiveBtn.style.fontWeight = 'bold';
        archiveBtn.style.border = '1px solid #ffcc00';
        
        activeBtn.style.background = '#f1f5f9';
        activeBtn.style.color = '#334155';
        activeBtn.style.fontWeight = 'normal';
        activeBtn.style.border = '1px solid #cbd5e1';
    }
}

function filterContractsArchive() {
    const input = document.getElementById('contractSearchInput');
    const filter = input.value.toLowerCase().trim();
    const tbody = document.getElementById('archiveTableBody');
    const rows = tbody.getElementsByClassName('archive-row');

    for (let i = 0; i < rows.length; i++) {
        const textElements = rows[i].getElementsByClassName('searchable-data');
        let matchFound = false;

        for (let j = 0; j < textElements.length; j++) {
            const txtValue = textElements[j].textContent || textElements[j].innerText;
            if (txtValue.toLowerCase().indexOf(filter) > -1) {
                matchFound = true;
                break;
            }
        }
        rows[i].style.display = matchFound ? "" : "none";
    }
}
</script>

<script src="../resurse/js/admin_rentals.js"></script>
</main>
</body>
</html>