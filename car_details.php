<?php
include '../db_connect.php';
$id = $_GET['id'] ?? 0;

$stmt = $conn->prepare("SELECT * FROM cars WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if(!$row) { echo "Masina nu exista"; exit; }

echo "<h3>{$row['marca']} {$row['model']} ({$row['an']})</h3>";
echo "<p>VIN: {$row['vin']}</p>";
echo "<p>Nr. inmatriculare: {$row['numar_inmatriculare']}</p>";
echo "<p>Motorizare: {$row['motorizare']}</p>";
echo "<p>Kilometraj: {$row['kilometraj']}</p>";
echo "<p>Status: {$row['status']}</p>";
echo "<h4>Detalii complete:</h4>";
echo "<p>{$row['detalii']}</p>";
?>