<?php
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title><?= isset($page_title) ? $page_title : 'Dashboard Administrativ' ?></title>
    <link rel="stylesheet" href="../resurse/css/admin.css">
    <style>
        header nav a {
            text-decoration: none !important;
            transition: color 0.2s ease;
        }
        header nav a:hover {
            color: #a0a8b4 !important;
            text-decoration: none !important;
        }
        header nav a.back-to-site {
            color: #ef4444 !important;
            font-weight: bold;
        }
        header nav a.back-to-site:hover {
            color: #ff6b6b !important;
            text-decoration: none !important;
        }
    </style>
</head>
<body>

<header>
    <h1>Admin Dashboard</h1>
    <nav>
        <a href="../index.php" class="back-to-site">Inapoi la Site</a> |
        <a href="admin_dashboard.php">Dashboard</a> |
        <a href="admin_cars.php">Lista masini</a> |
        <a href="admin_users.php">Lista utilizatori</a> |
        <a href="admin_rentals.php">Rezervari</a> |
        <a href="admin_sales.php">Vânzări</a> |
        <a href="admin_logout.php" style="color: #ef4444 !important;">Logout</a>
    </nav>
</header>