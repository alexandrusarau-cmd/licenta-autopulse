<?php
session_start();
if(!isset($_SESSION['admin_loggedin'])) header("Location: admin_login.php");

include '../db_connect.php';

if(isset($_POST['action'])){

	$allowedFields = ['username','email','role'];
    if($_POST['action'] === 'update'){
        if(!in_array($_POST['field'],$allowedFields)){
            echo "camp invalid";
            exit;
        }
        $stmt = $conn->prepare("UPDATE users SET ".$_POST['field']."=? WHERE id=?");
        $stmt->bind_param("si", $_POST['value'], $_POST['id']);
        echo $stmt->execute() ? "ok" : "eroare";
        exit;
    }
    if($_POST['action'] === 'delete'){
        $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
        $stmt->bind_param("i", $_POST['id']);
        echo $stmt->execute() ? "ok" : "eroare";
        exit;
    }
	if($_POST['action'] === 'add'){
		$password = password_hash('test123', PASSWORD_DEFAULT);

		$stmt = $conn->prepare("INSERT INTO users (username,email,password,role) VALUES (?,?,?,?)");
		$stmt->bind_param("ssss", $_POST['username'], $_POST['email'], $password, $_POST['role']);

		echo $stmt->execute() ? "ok" : "eroare";
		exit;
	}
}

$filterSQL = "";
$params = [];
$types = "";

if(isset($_GET['search']) && $_GET['search'] !== ""){
    $filterSQL = "WHERE username LIKE ? OR email LIKE ?";
    $searchTerm = "%" . $_GET['search'] . "%";
    $params = [$searchTerm, $searchTerm];
    $types = "ss";
}

$stmt = $conn->prepare("SELECT id, username, email, role FROM users $filterSQL ORDER BY id ASC");
if($filterSQL) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
include 'admin_header.php';
?>
<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<title>Gestionare Utilizatori</title>
<link rel="stylesheet" href="../resurse/css/admin.css">
</head>
<script src="../resurse/js/admin_users.js"></script>
<body>

<main>
<h2>Lista utilizatori</h2>

<form method="get">
    <label>Cauta username / email:</label>
    <input type="text" name="search" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" placeholder="Username / Email">
    <button type="submit">Cauta</button>
</form>
<br>

<button id="addUserBtn">Adauga utilizator</button>
<div id="userModal" style="display:none; background:#fff; padding:20px; border:2px solid black; width:400px; position:fixed; top:10%; left:50%; transform:translateX(-50%); z-index:1000;">
    <h3>Adauga utilizator</h3>

    <input id="username" placeholder="Username"><br>
    <input id="email" placeholder="Email"><br>
	<p>Parola implicita pentru conturile noi este: <strong>test123</strong></p>
    <select id="role">
        <option value="client">Client</option>
        <option value="administrator">Administrator</option>
    </select><br><br>

    <button id="saveUser">Salveaza</button>
    <button onclick="document.getElementById('userModal').style.display='none'">Anuleaza</button>
</div>

<table border="1" cellpadding="10">
<tr>
    <th>ID</th>
    <th>Username</th>
    <th>Email</th>
    <th>Rol</th>
    <th>Actiuni</th>
</tr>

<?php while($row = $result->fetch_assoc()){ ?>
<tr data-id="<?php echo $row['id']; ?>">
    <td><?php echo $row['id']; ?></td>

    <?php foreach(['username','email','role'] as $field){ ?>
    <td>
        <span class="editable" data-field="<?php echo $field; ?>" data-id="<?php echo $row['id']; ?>">
            <?php echo $row[$field]; ?>
        </span>
        <span class="edit-icon">✎</span>
    </td>
    <?php } ?>

    <td>
        <button class="delete-btn" data-id="<?php echo $row['id']; ?>">🗑</button>
    </td>
</tr>
<?php } ?>

</table>
</main>
</body>
</html>
