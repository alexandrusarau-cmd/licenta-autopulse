<?php
include 'functions.php';
include 'db_connect.php';

$is_client = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
$is_admin = isset($_SESSION['admin_loggedin']) && $_SESSION['admin_loggedin'] === true;

if (!$is_client && !$is_admin) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['sale_id'])) {
    die("ID tranzacție lipsă. Accesează documentul din panou.");
}

$sale_id = (int)$_GET['sale_id'];

if ($is_admin) {
    $stmt = $conn->prepare("
        SELECT si.*, s.nume AS client_nume, s.email AS client_email, s.telefon AS client_telefon,
               c.marca, c.model, c.an, c.numar_inmatriculare, c.vin
        FROM sales_invoices si
        JOIN sales s ON si.sale_id = s.id
        JOIN cars c ON si.car_id = c.id
        WHERE si.sale_id = ?
    ");
    $stmt->bind_param("i", $sale_id);
} else {
    $user_id = (int)$_SESSION['user_id'];
    $stmt = $conn->prepare("
        SELECT si.*, s.nume AS client_nume, s.email AS client_email, s.telefon AS client_telefon,
               c.marca, c.model, c.an, c.numar_inmatriculare, c.vin
        FROM sales_invoices si
        JOIN sales s ON si.sale_id = s.id
        JOIN cars c ON si.car_id = c.id
        WHERE si.sale_id = ? AND si.user_id = ?
    ");
    $stmt->bind_param("ii", $sale_id, $user_id);
}

$stmt->execute();
$date = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$date) {
    die("Documentele nu au fost generate sau nu aveți permisiunea să le vizualizați.");
}

$numar_contract_oficial = $date['contract_number'];
$numar_factura_oficial = $date['invoice_number'];
$data_afisare = date('d.m.Y', strtotime($date['generated_at']));

$pret_total = $date['pret_total'];
$baza_impozabila = $date['baza_impozabila'];
$tva = $date['tva'];
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acte Vânzare #<?= htmlspecialchars($numar_contract_oficial) ?></title>
    <link rel="stylesheet" href="resurse/css/general.css?v=<?= time() ?>">
    <style>
        body { background: #f4f6f9; margin: 0; padding: 0; }
        .page-container { max-width: 210mm; margin: 40px auto; }
        .document-page {
            background: white; color: #111; padding: 20mm;
            width: 210mm; height: 297mm; box-sizing: border-box;
            border: 1px solid #ccc; box-shadow: 0 0 25px rgba(0,0,0,0.1);
            line-height: 1.6; margin-bottom: 40px; position: relative;
            page-break-after: always;
        }
        .contract-header { text-align: center; border-bottom: 4px solid #0066b1; padding-bottom: 20px; margin-bottom: 35px; }
        .section-title { color: #0066b1; border-bottom: 2px solid #eee; padding-bottom: 8px; margin-top: 25px; font-weight: bold; text-transform: uppercase; font-size: 1.1rem; }
        .btn-print { background: #0066b1; color: white; padding: 14px 32px; border: none; border-radius: 8px; font-size: 1.1rem; cursor: pointer; font-weight: 600; }
        .btn-back { color: #64748b; text-decoration: none; font-family: sans-serif; font-size: 1rem; display: inline-block; margin-bottom: 15px; }
        .grid-parti { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .grid-parti td { width: 50%; border: 1px solid #ddd; padding: 15px; vertical-align: top; background: #fafafa; }
        .tabel-produse { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .tabel-produse th { background: #0066b1; color: white; padding: 12px; text-align: left; }
        .tabel-produse td { border: 1px solid #ddd; padding: 12px; }
        @media print {
            @page { size: A4 portrait; margin: 0; }
            body { background: white; }
            .no-print-zone { display: none !important; }
            .page-container { margin: 0; max-width: none; width: auto; }
            .document-page { margin-bottom: 0; border: none; box-shadow: none; width: 210mm; height: 297mm; page-break-after: always; }
        }
    </style>
</head>
<body>

<div class="page-container">
    
    <div class="no-print-zone">
        <a href="rezervari-mele.php" class="btn-back">← Înapoi la Activitatea Mea</a>
    </div>

    <div class="document-page" style="font-family: 'Times New Roman', Times, serif; font-size: 15px; text-align: justify;">
        <div class="contract-header">
            <h1>CONTRACT DE VÂNZARE - CUMPĂRARE AUTO</h1>
            <p style="margin: 5px 0 0 0; font-size: 1.1rem;">Nr. <strong><?= htmlspecialchars($numar_contract_oficial) ?></strong></p>
            <p style="margin: 2px 0 0 0; color: #555; font-size: 0.9rem;">Data încheierii: <?= $data_afisare ?></p>
        </div>

        <h2 class="section-title">1. Părțile Contractante</h2>
        <p><strong>VÂNZĂTOR:</strong> AUTOPULSE S.R.L., cu sediul în Bd. Unirii, Nr. 10, București, J40/12345/2024, C.I.F. RO987654321, în calitate de proprietar legal al vehiculului.</p>
        <p><strong>CUMPĂRĂTOR:</strong> Dl/Dna <strong><?= htmlspecialchars($date['client_nume']) ?></strong>, având adresa de email <?= htmlspecialchars($date['client_email']) ?> și numărul de telefon <?= htmlspecialchars($date['client_telefon'] ?? '-') ?>.</p>

        <h2 class="section-title">2. Obiectul Contractului</h2>
        <p>Obiectul contractului este transferul dreptului de proprietate asupra următorului autovehicul:</p>
        <p><strong>Marcă și Model:</strong> <?= htmlspecialchars($date['marca'] . ' ' . $date['model']) ?></p>
        <p><strong>An Fabricație:</strong> <?= htmlspecialchars($date['an']) ?></p>
        <p><strong>Număr Înmatriculare:</strong> <?= htmlspecialchars($date['numar_inmatriculare'] ?? 'Nespecificat') ?></p>
        <p><strong>Serie șasiu (VIN):</strong> <?= htmlspecialchars($date['vin'] ?? 'Nespecificat') ?></p>

        <h2 class="section-title">3. Condiții Financiare și Predare</h2>
        <p>3.1. Prețul de vânzare convenit este de <strong><?= number_format($pret_total, 2) ?> €</strong> (TVA inclus), achitat prin platforma electronică AutoPulse.</p>
        <p>3.2. Predarea documentelor mașinii și a cheilor se va face la sediul Vânzătorului în termen de 24 de ore de la procesarea tranzacției.</p>

        <h2 class="section-title">4. Semnături</h2>
        <table style="width: 100%; margin-top: 60px; border: none;">
            <tr>
                <td style="border:none; padding: 0;"><strong>Vânzător:</strong> AutoPulse S.R.L.<br><small style="color:#666;">[Semnat electronic prin platformă]</small></td>
                <td style="text-align: right; border:none; padding: 0;"><strong>Cumpărător:</strong> <?= htmlspecialchars($date['client_nume']) ?><br><small style="color:#666;">[Semnat din contul securizat]</small></td>
            </tr>
        </table>
    </div>

    <div class="document-page" style="font-family: Arial, sans-serif;">
        <div style="display: flex; justify-content: space-between; border-bottom: 4px solid #0066b1; padding-bottom: 15px; margin-bottom: 30px;">
            <div>
                <h1 style="margin: 0; color: #0066b1; font-size: 2.2rem;">AUTOPULSE S.R.L.</h1>
                <p style="margin: 5px 0 0 0; color:#555;">Factură Fiscală în regim automat</p>
            </div>
            <div style="text-align: right;">
                <h2 style="margin: 0; color: #333;">FACTURĂ</h2>
                <p style="margin: 3px 0;"><strong>Serie/Număr:</strong> <?= htmlspecialchars($numar_factura_oficial) ?></p>
                <p style="margin: 3px 0;"><strong>Data emiterii:</strong> <?= $data_afisare ?></p>
            </div>
        </div>

        <table class="grid-parti">
            <tr>
                <td>
                    <h3 style="margin-top:0; color:#0066b1;">FURNIZOR</h3>
                    <strong>AUTOPULSE S.R.L.</strong><br>
                    C.I.F.: RO987654321<br>
                    Reg. Com: J40/12345/2024<br>
                    Sediu: Bd. Unirii, Nr. 10, București
                </td>
                <td>
                    <h3 style="margin-top:0; color:#0066b1;">CLIENT</h3>
                    <strong>Nume:</strong> <?= htmlspecialchars($date['client_nume']) ?><br>
                    <strong>Email:</strong> <?= htmlspecialchars($date['client_email']) ?><br>
                    <strong>Telefon:</strong> <?= htmlspecialchars($date['client_telefon'] ?? '-') ?>
                </td>
            </tr>
        </table>

        <table class="tabel-produse">
            <thead>
                <tr>
                    <th>Denumire produs / serviciu</th>
                    <th style="text-align: center;">Cantitate</th>
                    <th style="text-align: right;">Preț fără TVA</th>
                    <th style="text-align: right;">Valoare TVA (21%)</th>
                    <th style="text-align: right;">Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Autovehicul second-hand: <strong><?= htmlspecialchars($date['marca'] . ' ' . $date['model']) ?></strong> (VIN: <?= htmlspecialchars($date['vin'] ?? 'Nespecificat') ?>)</td>
                    <td style="text-align: center;">1 buc</td>
                    <td style="text-align: right;"><?= number_format($baza_impozabila, 2) ?> €</td>
                    <td style="text-align: right;"><?= number_format($tva, 2) ?> €</td>
                    <td style="text-align: right;"><strong><?= number_format($pret_total, 2) ?> €</strong></td>
                </tr>
            </tbody>
        </table>

        <div style="float: right; width: 300px; margin-top: 30px;">
            <table style="width: 100%; border-collapse: collapse; line-height: 2;">
                <tr>
                    <td>Total valoare netă:</td>
                    <td style="text-align: right;"><?= number_format($baza_impozabila, 2) ?> €</td>
                </tr>
                <tr>
                    <td>Total TVA (21%):</td>
                    <td style="text-align: right;"><?= number_format($tva, 2) ?> €</td>
                </tr>
                <tr style="font-weight: bold; font-size: 1.2rem; border-top: 2px solid #0066b1; background: #f8fafc;">
                    <td style="padding: 5px 10px;">TOTAL PLATĂ:</td>
                    <td style="text-align: right; padding: 5px 10px; color: #0066b1;"><?= number_format($pret_total, 2) ?> €</td>
                </tr>
            </table>
        </div>

        <div style="clear: both; margin-top: 80px; text-align: center; color: #666; font-size: 0.9rem; border-top: 1px dashed #ddd; padding-top: 15px;">
            Prezenta factură este generată automat în baza contractului comercial și circulă fără ștampilă conform Codului Fiscal.
        </div>
    </div>

    <div class="no-print-zone" style="text-align: center; margin-top: 20px; padding-bottom: 40px;">
        <button onclick="window.print()" class="btn-print">Printează Documentele / Salvează PDF</button>
    </div>

</div>

</body>
</html>