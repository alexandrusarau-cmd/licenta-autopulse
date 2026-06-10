<?php
session_start();
if(!isset($_SESSION['admin_loggedin'])) header("Location: admin_login.php");

include '../db_connect.php';

function deleteFolderRecursive($dir) {
    if (!is_dir($dir)) return;
    $files = array_diff(scandir($dir), array('.', '..'));
    foreach ($files as $file) {
        (is_dir("$dir/$file")) ? deleteFolderRecursive("$dir/$file") : unlink("$dir/$file");
    }
    return rmdir($dir);
}

$folder_poza = '../resurse/imagini/masini/';
if (!is_dir($folder_poza)) {
    mkdir($folder_poza, 0755, true);
}
if(isset($_POST['action'])){

    // campuri permise (anti sql injection)
    $allowedFields = [
        'marca','model','vin','status','vizibil','pret_vanzare','pret_inchiriere','pret_promo','kilometraj','an','combustibil','transmisie','numar_inmatriculare','motorizare','putere'
    ];
	if($_POST['action'] === 'update'){
		if(!in_array($_POST['field'], $allowedFields)){
			echo "camp invalid"; exit;
		}

		$field = $_POST['field'];
		$value = $_POST['value'];
		$id    = $_POST['id'];

		if($field === 'pret_promo'){
			if($value === '' || $value === '0' || $value === '0.00'){
				$sql = "UPDATE cars SET pret_promo = NULL WHERE id = ?";
				$stmt = $conn->prepare($sql);
				$stmt->bind_param("i", $id);
			} else {
				$sql = "UPDATE cars SET pret_promo = ? WHERE id = ?";
				$stmt = $conn->prepare($sql);
				$stmt->bind_param("di", $value, $id); 
			}
		} else {
			$sql = "UPDATE cars SET ".$field."=? WHERE id=?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("si", $value, $id);
		}

		echo $stmt->execute() ? "ok" : "eroare";
		exit;
	}

	if($_POST['action'] === 'delete'){
		$id = $_POST['id'];
		
		$folder_masina = "../resurse/imagini/masini/car_" . $id . "/";

		if (is_dir($folder_masina)) {
			deleteFolderRecursive($folder_masina);
		}

		$stmt = $conn->prepare("DELETE FROM cars WHERE id=?");
		$stmt->bind_param("i", $id);
		
		echo $stmt->execute() ? "ok" : "eroare";
		exit;
	}
	if($_POST['action'] === 'add'){

    $stmt = $conn->prepare("
        INSERT INTO cars 
        (marca, model, vin, status, vizibil, pret_vanzare, pret_inchiriere, kilometraj, an, combustibil, transmisie, numar_inmatriculare, motorizare, putere)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "ssssiddiissssi",
        $_POST['marca'], $_POST['model'], $_POST['vin'], $_POST['status'], $_POST['vizibil'],
        $_POST['pret_vanzare'], $_POST['pret_inchiriere'], $_POST['kilometraj'],
        $_POST['an'], $_POST['combustibil'], $_POST['transmisie'],
        $_POST['numar_inmatriculare'], $_POST['motorizare'], $_POST['putere']
    );

    if(!$stmt->execute()){
        echo "eroare insert";
        exit;
    }

    $car_id = $conn->insert_id;

    // create new folder
    $folder = "../resurse/imagini/masini/car_" . $car_id . "/";

    if(!is_dir($folder)){
        mkdir($folder, 0755, true);
    }

    $poza_principala_path = null;

    // upload poza principala
    if(isset($_FILES['poza']) && $_FILES['poza']['error'] === UPLOAD_ERR_OK){

        $file = $_FILES['poza'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        $nume = "main." . $ext;
        $cale = $folder . $nume;

        if(move_uploaded_file($file['tmp_name'], $cale)){
            $poza_principala_path = "resurse/imagini/masini/car_" . $car_id . "/" . $nume;
        }
    }

    // poze suplimentare
	if(isset($_FILES['poze_extra']) && is_array($_FILES['poze_extra']['name'])){
        foreach($_FILES['poze_extra']['tmp_name'] as $key => $tmp_name){

            if($_FILES['poze_extra']['error'][$key] === 0){

                $ext = strtolower(pathinfo($_FILES['poze_extra']['name'][$key], PATHINFO_EXTENSION));
				$nume = "extra_" . time() . "_" . uniqid() . "_" . $key . "." . $ext;
                $cale = $folder . $nume;

                move_uploaded_file($tmp_name, $cale);
            }
        }
    }

    // update poza principala
    if($poza_principala_path){
        $stmt2 = $conn->prepare("UPDATE cars SET poza_principala=? WHERE id=?");
        $stmt2->bind_param("si", $poza_principala_path, $car_id);
        $stmt2->execute();
    }

    echo "ok";
    exit;
	}
		if($_POST['action'] === 'list_extra_photos'){
		$id = $_POST['id'] ?? 0;
		$folder = "../resurse/imagini/masini/car_" . $id . "/";
		$photos = [];
		if (is_dir($folder)) {
			// only extra
			$files = glob($folder . "extra_*.*");
			foreach($files as $f) {
				$photos[] = basename($f); // extra_123.jpg
			}
		}
		echo json_encode($photos);
		exit;
	}

	if($_POST['action'] === 'delete_single_photo'){
		$id = $_POST['id'] ?? 0;
		$filename = $_POST['filename'] ?? '';
		
		$filename = basename($filename); 
		$cale = "../resurse/imagini/masini/car_" . $id . "/" . $filename;

		if (is_file($cale)) {
			echo unlink($cale) ? "ok" : "eroare";
		} else {
			echo "fisier_inexistent";
		}
		exit;
	}
	if($_POST['action'] === 'details'){
		$id = $_POST['id'] ?? 0;
		$stmt = $conn->prepare("SELECT marca, model, an, detalii, putere FROM cars WHERE id=?");
		$stmt->bind_param("i", $id);
		$stmt->execute();
		$row = $stmt->get_result()->fetch_assoc();
		if(!$row){ echo json_encode(['error'=>'Masina nu exista']); exit; }

		echo json_encode($row);
		exit;
	}
	if($_POST['action'] === 'update_details'){
		$id = $_POST['id'] ?? 0;
		$detalii = $_POST['detalii'] ?? '';
		$stmt = $conn->prepare("UPDATE cars SET detalii=? WHERE id=?");
		$stmt->bind_param("si", $detalii, $id);
		echo $stmt->execute() ? "ok" : "eroare";
		exit;
	}
	if($_POST['action'] === 'edit_poza'){
		$id = $_POST['id'] ?? 0;
		if(!$id) { echo "ID lipsa"; exit; }

		$folder = "../resurse/imagini/masini/car_" . $id . "/";
		if(!is_dir($folder)) mkdir($folder, 0755, true);

		if(isset($_FILES['edit_poza_main']) && $_FILES['edit_poza_main']['error'] === UPLOAD_ERR_OK){
			$file = $_FILES['edit_poza_main'];
			$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
			$nume_nou = "main." . $ext;
			$cale_completa = $folder . $nume_nou;

			$vechi = glob($folder . "main.*");
			foreach($vechi as $f) { if(is_file($f)) unlink($f); }

			if(move_uploaded_file($file['tmp_name'], $cale_completa)){
				$db_path = "resurse/imagini/masini/car_" . $id . "/" . $nume_nou;
				$stmt = $conn->prepare("UPDATE cars SET poza_principala=? WHERE id=?");
				$stmt->bind_param("si", $db_path, $id);
				$stmt->execute();
			}
		}

		if(isset($_FILES['edit_poze_extra']) && is_array($_FILES['edit_poze_extra']['name'])){
			foreach($_FILES['edit_poze_extra']['tmp_name'] as $key => $tmp_name){
				if($_FILES['edit_poze_extra']['error'][$key] === 0){
					$ext = strtolower(pathinfo($_FILES['edit_poze_extra']['name'][$key], PATHINFO_EXTENSION));
					// uniqid() ca sa nu se suprascrie pozele extra existente
					$nume_extra = "extra_" . time() . "_" . uniqid() . "_" . $key . "." . $ext;
					move_uploaded_file($tmp_name, $folder . $nume_extra);
				}
			}
		}

		echo "ok";
		exit;
	}
}
$where = [];
$params = [];
$types = "";

// filtru status
if(!empty($_GET['status'])){
    $where[] = "status = ?";
    $params[] = $_GET['status'];
    $types .= "s";
}

// search vin
if(!empty($_GET['search'])){
    $where[] = "(vin LIKE ? OR numar_inmatriculare LIKE ?)";
    $search = "%".$_GET['search']."%";
    $params[] = $search;
    $params[] = $search;
    $types .= "ss";
}

$sql = "SELECT * FROM cars";
if($where){
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY id ASC";

$stmt = $conn->prepare($sql);

// bind dinamic daca exista parametri
if(!empty($params)){
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
include 'admin_header.php';
?>
<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<title>Gestionare Flota Auto</title>
<link rel="stylesheet" href="../resurse/css/admin.css">
</head>
<body>

<main>
<h2>Gestionare masini</h2>

<form method="get">
    <label>Filtreaza dupa status:</label>
	<select name="status" onchange="this.form.submit()">
		<option value="">Toate</option>
		<option value="disponibil" <?= ($_GET['status'] ?? '') == 'disponibil' ? 'selected' : '' ?>>Disponibil</option>
		<option value="inchiriat" <?= ($_GET['status'] ?? '') == 'inchiriat' ? 'selected' : '' ?>>Inchiriat</option>
		<option value="vandut" <?= ($_GET['status'] ?? '') == 'vandut' ? 'selected' : '' ?>>Vandut</option>
		<option value="in service" <?= ($_GET['status'] ?? '') == 'in service' ? 'selected' : '' ?>>In service</option>
		<option value="de vanzare" <?= ($_GET['status'] ?? '') == 'de vanzare' ? 'selected' : '' ?>>De vanzare</option>
	</select>

    <label>Cauta VIN / Nr. inmatriculare:</label>
    <input type="text" name="search" value="<?php echo $_GET['search'] ?? ''; ?>" placeholder="VIN / Nr. inmatriculare">
    <button type="submit">Cauta</button>
</form>

<br>
<button id="addCarBtn">Adauga masina</button>
<div id="carModal" style="display:none; background:#fff; padding:20px; border:2px solid black; width:400px; position:fixed; top:10%; left:50%; transform:translateX(-50%); z-index:1000;">
    <h3>Adauga masina</h3>

    <input id="marca" placeholder="Marca"><br>
    <input id="model" placeholder="Model"><br>
    <input id="vin" placeholder="VIN"><br>

    <select id="status">
        <option value="disponibil">Disponibil</option>
        <option value="inchiriat">Inchiriat</option>
        <option value="vandut">Vandut</option>
        <option value="in service">In service</option>
		<option value="de vanzare">De vanzare</option>
    </select><br>
	<label>Vizibil pe site?</label>
	<select id="vizibil">
		<option value="1" selected>Da - Afisat pe site</option>
		<option value="0">Nu - Ascuns temporar</option>
	</select><br>
    <input id="pret_vanzare" placeholder="Pret vanzare"><br>
    <input id="pret_inchiriere" placeholder="Pret inchiriere"><br>
    <input id="kilometraj" placeholder="Kilometraj"><br>
    <input id="an" placeholder="An"><br>
    <input id="combustibil" placeholder="Combustibil"><br>
    <input id="transmisie" placeholder="Transmisie"><br>
    <input id="numar_inmatriculare" placeholder="Nr. inmatriculare"><br>
    <input id="motorizare" placeholder="Motorizare"><br><br>
	<input id="putere" placeholder="Putere (ex: 190)"><br><br>
	<label for="poza">Poza principala (jpg/png/gif/webp, max 5MB):</label><br>
	<input type="file" id="poza" name="poza" accept="image/jpeg,image/png,image/gif,image/webp"><br>
	<label>Poze suplimentare:</label><br>
	<input type="file" id="poze_extra" name="poze_extra[]" multiple accept="image/*"><br><br>
	<button id="saveCar" type="button">Salveaza</button>
    <button onclick="document.getElementById('carModal').style.display='none'">Anuleaza</button>
</div>

<table border="1" cellpadding="10">
<tr>
    <th>ID</th>
    <th>Marca</th>
    <th>Model</th>
    <th>VIN</th>
    <th>Status</th>
	<th>Vizibil pe site</th>
    <th>Pret vanzare</th>
    <th>Pret inchiriere</th>
	<th>Pret Promo</th>
    <th>Kilometraj</th>
    <th>An</th>
    <th>Combustibil</th>
    <th>Transmisie</th>
    <th>Nr. inmatriculare</th>
    <th>Motorizare</th>
	<th>Putere</th>
    <th>Actiuni</th>
</tr>

<div id="detailsModal" style="display:none; background:#fff; padding:20px; border:2px solid black; width:500px; position:fixed; top:10%; left:50%; transform:translateX(-50%); z-index:1000;">
    <h3 id="detaliiTitlu"></h3>
    <h4 style="color:red;">Detalii suplimentare:</h4>
    <textarea id="detaliiField" style="width:100%; height:200px;"></textarea><br><br>
    <button id="saveDetaliiBtn">Salveaza detalii</button>
    <button id="closeDetails">Inchide</button>
</div>

<div id="editPozaModal" style="display:none; background:#fff; padding:20px; border:2px solid black; width:450px; position:fixed; top:10%; left:50%; transform:translateX(-50%); z-index:1000; max-height: 80vh; overflow-y: auto;">
    <h3>Editeaza Poze - ID <span id="editPozaId"></span></h3>
    
    <label><b>Poza Principala Noua:</b></label><br>
    <input type="file" id="edit_poza_main" accept="image/*"><br><br>
    
    <label><b>Adauga Poze Suplimentare:</b></label><br>
    <input type="file" id="edit_poze_extra" multiple accept="image/*"><br><br>

    <hr>
    <label><b>Poze Suplimentare Actuale (Click pe X pentru Stergere):</b></label>
    <div id="current_extra_photos" style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 10px; background: #f0f0f0; padding: 10px; border-radius: 5px;">
        </div>
    <hr>
    
    <button id="saveEditPoza">Salveaza Modificari</button>
    <button onclick="document.getElementById('editPozaModal').style.display='none'">Inchide</button>
</div>

<?php while($row = $result->fetch_assoc()) { ?>
<tr data-id="<?php echo $row['id']; ?>">
    <td><?php echo $row['id']; ?></td>
	
	<?php foreach(['marca','model','vin','status','vizibil','pret_vanzare','pret_inchiriere','pret_promo','kilometraj','an','combustibil','transmisie','numar_inmatriculare','motorizare','putere'] as $field){ ?>
		<td>
			<span class="editable" data-field="<?php echo $field; ?>" data-id="<?php echo $row['id']; ?>">
				<?php 
					if($field === 'vizibil'){
							echo $row['vizibil'] == 1 ? 'Da' : '<span style="color:red;">Nu</span>';
						} elseif($field === 'pret_promo'){
							echo !empty($row['pret_promo']) ? number_format($row['pret_promo'], 2) : '-';
						} else {
							echo htmlspecialchars($row[$field] ?? '-');
						}
					?>
			</span>
            <span class="edit-icon" style="cursor:pointer;">✎</span>
		</td>
	<?php } ?>

    <td>
		<button class="details-btn" data-id="<?= $row['id'] ?>">Detalii</button>
		<button class="edit-poza-btn" data-id="<?= $row['id'] ?>">Edit Poza</button>
        <button class="delete-btn" data-id="<?php echo $row['id']; ?>">🗑</button>
    </td>
</tr>
<?php } ?>

</table>

<script src="../resurse/js/admin.js"></script>
</main>
</body>
</html>
