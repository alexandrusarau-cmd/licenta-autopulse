<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AutoPulse Dealer Auto – Mașini Premium</title>
    <meta name="description" content="Închirieri și vânzări mașini de lux – flota selectă, prețuri competitive.">
    <link rel="icon" href="resurse/imagini/ico.png" type="image/x-icon">
    <link rel="stylesheet" href="resurse/css/general.css?v=<?= time() ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
			.whatsapp-btn {
				position: fixed;
				bottom: 20px;
				right: 20px;
				background-color: #25d366;
				color: white;
				width: 60px;
				height: 60px;
				border-radius: 50%;
				text-align: center;
				font-size: 30px;
				box-shadow: 2px 2px 10px rgba(0,0,0,0.3);
				z-index: 1000;
				display: flex;
				align-items: center;
				justify-content: center;
				text-decoration: none;
				transition: transform 0.3s ease;
			}
			.whatsapp-btn:hover {
				transform: scale(1.1);
				color: white;
			}
			.whatsapp-btn::before {
				content: 'WP';
				font-family: Arial, sans-serif;
				font-weight: bold;
				font-size: 18px;
			}
			.header {
				height: 65px;     
				display: flex;
				align-items: center;
				justify-content: space-between;
				padding: 0 3% 0 0; 
				background: #0a0e14;
				position: sticky;
				top: 0;
				z-index: 1000;
				overflow: visible;
			}
			.logo {
				margin-left: 80px;  
				display: flex;
				align-items: center;
				z-index: 1001;
			}
			.logo img {
				height: 80px;         
				width: auto;
				display: block;
				position: relative;
				top: 5px; 
				filter: drop-shadow(0px 4px 10px rgba(0,0,0,0.5));
			}
			.navbar {
				margin-right: 10px; 
			}
			.menu {
				display: flex;
				gap: 25px;         
				list-style: none;
			}			
    </style>
</head>
<body>

<a href="https://wa.me/40771421469" class="whatsapp-btn" target="_blank" title="Contactează-ne pe WhatsApp"></a>

<header class="header">
    <div class="logo">
        <a href="index.php">
            <img src="resurse/imagini/logo.png" alt="AutoPulse Logo">
        </a>
    </div>

    <nav class="navbar">
        <button class="hamburger" aria-label="Meniu" onclick="toggleMenu()">☰</button>
        <ul class="menu">
            <li><a href="index.php#hero">Acasă</a></li>
            <li><a href="masini.php">Mașini</a></li>
            <li><a href="index.php#oferte">Oferte</a></li>
            <li><a href="index.php#despre">Despre</a></li>
            <li><a href="contact.php">Contact</a></li>

            <li class="user-menu">
                <?php if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
                    <a href="#" class="user-link">
                        <?= htmlspecialchars($_SESSION['username']) ?>
                        <?php if(isset($_SESSION['role'])): ?>
                            <span class="badge <?= $_SESSION['role'] === 'administrator' ? 'admin' : 'client' ?>">
                                <?= strtoupper(substr($_SESSION['role'], 0, 5)) ?>
                            </span>
                        <?php endif; ?>
                    </a>
                    <ul class="dropdown">
                        <li><a href="profile.php">Profil</a></li>
                        <li><a href="rezervari-mele.php">Rezervările mele</a></li>
                        <?php if($_SESSION['role'] === 'administrator'): ?>
                            <li><a href="admin/admin_dashboard.php">Admin Panel</a></li>
                        <?php endif; ?>
                        <li><a href="logout.php">Logout</a></li>
                    </ul>
                <?php else: ?>
                    <a href="#" class="user-link">Autentificare</a>
                    <ul class="dropdown">
                        <li><a href="login.php">Login</a></li>
                        <li><a href="register.php">Înregistrare</a></li>
                    </ul>
                <?php endif; ?>
            </li>
        </ul>
    </nav>
</header>