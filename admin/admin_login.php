<?php
session_start();
include '../db_connect.php';

if(isset($_POST['login'])){
    $username = $_POST['username'];
    $password = $_POST['password'];

	$stmt = $conn->prepare("SELECT id, role, password FROM users WHERE username=?");
	if(!$stmt){
		die("Eroare prepare: ".$conn->error);
	}
	$stmt->bind_param("s", $username);
	$stmt->execute();
    $stmt->bind_result($id, $role, $db_password);

	if ($stmt->fetch()) {
		if (password_verify($password, $db_password) && $role === 'administrator') {
			$_SESSION['admin_loggedin'] = true;
			$_SESSION['admin_username'] = $username;
			$_SESSION['admin_id'] = $id;

			header("Location: admin_dashboard.php");
			exit;
		} else {
			$error = "Username/parolă greșită sau nu ai drept de administrator!";
		}
	} else {
		$error = "Utilizator invalid!";
	}
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<title>Login Administrator</title>
<link rel="stylesheet" href="../resurse/css/admin.css">
</head>
<body>
<div class="login-container">
    <h2>Login Administrator</h2>
    <?php if(isset($error)) echo "<p class='error'>$error</p>"; ?>
    <form method="post">
        <input type="text" name="username" placeholder="Username" required><br><br>
        <input type="password" name="password" placeholder="Parolă" required><br><br>
        <button type="submit" name="login">Login</button>
    </form>
</div>
</body>
</html>
