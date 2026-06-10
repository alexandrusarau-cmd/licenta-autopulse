<?php
include 'functions.php';
include 'db_connect.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

$rental_id = isset($_GET['rental_id']) ? (int)$_GET['rental_id'] : 0;
$contract_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($rental_id === 0 && $contract_id === 0) {
    die("ID invalid. Accesează contractul din Rezervările mele.");
}

// daca am rental_id = generare contract automat
if ($rental_id > 0) {
    $check = $conn->prepare("SELECT id FROM contracts WHERE rental_id = ?");
    $check->bind_param("i", $rental_id);
    $check->execute();
    $exists = $check->get_result()->fetch_assoc();

    if (!$exists) {
        $stmt = $conn->prepare("
            SELECT r.id as rental_id, r.user_id, r.car_id, r.start_date, r.end_date, r.price,
                   u.username, u.email, c.marca, c.model, c.an, c.numar_inmatriculare
            FROM rentals r
            JOIN users u ON r.user_id = u.id
            JOIN cars c ON r.car_id = c.id
            WHERE r.id = ?
        ");
        $stmt->bind_param("i", $rental_id);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_assoc();

        if ($data) {
            $contract_number = 'CON-' . date('Ymd') . '-' . str_pad($rental_id, 5, '0', STR_PAD_LEFT);

            $insert = $conn->prepare("
                INSERT INTO contracts 
                (rental_id, user_id, car_id, contract_number, start_date, end_date, pret_total, depozit, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, 500.00, 'activ')
            ");
            $insert->bind_param("iiisssd", 
                $data['rental_id'], 
                $data['user_id'], 
                $data['car_id'], 
                $contract_number, 
                $data['start_date'], 
                $data['end_date'], 
                $data['price']
            );
            $insert->execute();

            $contract_id = $conn->insert_id;
        }
    } else {
        $contract_id = $exists['id'];
    }
}

$stmt = $conn->prepare("
    SELECT c.*, 
           u.username, u.email,
           ca.marca, ca.model, ca.an, ca.numar_inmatriculare, ca.poza_principala
    FROM contracts c
    JOIN users u ON c.user_id = u.id
    JOIN cars ca ON c.car_id = ca.id
    WHERE c.id = ?
");
$stmt->bind_param("i", $contract_id);
$stmt->execute();
$contract = $stmt->get_result()->fetch_assoc();

if (!$contract) {
    die("Contractul nu a fost găsit.");
}

$d1 = new DateTime($contract['start_date']);
$d2 = new DateTime($contract['end_date']);
$total_zile = $d1->diff($d2)->days;
if($total_zile == 0) $total_zile = 1;

$pret_zi = $contract['pret_total'] / $total_zile;
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contract Închiriere #<?= htmlspecialchars($contract['contract_number']) ?></title>
    <link rel="stylesheet" href="resurse/css/general.css?v=<?= time() ?>">
    <style>
        body {
            background: #f4f6f9;
            color: #111;
        }
        .page-container {
            max-width: 1000px;
            margin: 40px auto;
        }
        .contract-page {
            background: white;
            padding: 50px;
            border: 1px solid #ccc;
            box-shadow: 0 0 25px rgba(0,0,0,0.1);
            line-height: 1.6;
            text-align: justify;
        }
        .contract-header {
            text-align: center;
            border-bottom: 4px solid #0066b1;
            padding-bottom: 25px;
            margin-bottom: 35px;
        }
        .contract-header h1 {
            font-size: 24px;
            color: #111;
            margin: 0 0 5px 0;
            text-transform: uppercase;
        }
        .section-title {
            color: #0066b1;
            border-bottom: 2px solid #eee;
            padding-bottom: 5px;
            margin-top: 30px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 1.1rem;
        }
        .clauza-text {
            margin: 8px 0;
            font-size: 0.95rem;
        }
        .btn-print {
            background: #0066b1;
            color: white;
            padding: 14px 30px;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            cursor: pointer;
            font-weight: 600;
            display: inline-block;
            transition: background 0.2s;
        }
        .btn-print:hover {
            background: #004b82;
        }
        .btn-back {
            color: #64748b;
            text-decoration: none;
            font-family: sans-serif;
            font-size: 1rem;
            display: inline-block;
            margin-bottom: 15px;
        }
        .btn-check {
            background: #2e7d32;
            color: white;
            padding: 14px 30px;
            font-size: 1.1rem;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            display: inline-block;
            transition: background 0.2s;
        }
        .btn-check:hover {
            background: #1b5e20;
        }
        
        @media print {
            .no-print-zone { display: none; }
            body { background: white; }
            .contract-page { border: none; box-shadow: none; padding: 0; margin: 0; }
        }
    </style>
</head>
<body>

<div class="page-container">
    
    <div class="no-print-zone">
        <a href="rezervari-mele.php" class="btn-back">← Înapoi la Activitatea Mea</a>
    </div>

    <div class="contract-page" style="font-family: 'Times New Roman', Times, serif; font-size: 15px;">
        
        <div class="contract-header">
            <h1>CONTRACT DE ÎNCHIRIERE AUTOVEHICUL</h1>
            <p style="margin: 5px 0 0 0; font-size: 1.1rem;">Nr. <strong><?= htmlspecialchars($contract['contract_number']) ?></strong></p>
            <p style="margin: 2px 0 0 0; color: #555; font-size: 0.9rem;">Data emiterii: <?= isset($contract['generated_at']) ? date('d.m.Y H:i', strtotime($contract['generated_at'])) : date('d.m.Y H:i') ?></p>
        </div>

        <h2 class="section-title">1. Părțile Contractante</h2>
        <div class="clauza-text">
            <strong>LOCATOR (Proprietar):</strong> <strong>AUTOPULSE S.R.L.</strong>, cu sediul social în Bd. Unirii, Nr. 10, București, CIF RO987654321, Reg. Com. J40/12345/2024, denumită în continuare societate.
        </div>
        <div class="clauza-text">
            <strong>LOCATAR (Client):</strong> Utilizatorul înregistrat cu numele <strong><?= htmlspecialchars($contract['username']) ?></strong>, având adresa de corespondență electronică <?= htmlspecialchars($contract['email']) ?>, denumit în continuare Client.
        </div>

        <h2 class="section-title">2. Obiectul și Durata Contractului</h2>
        <div class="clauza-text">
            2.1. Obiectul contractului îl constituie darea în folosință temporară a autovehiculului marca și modelul <strong><?= htmlspecialchars($contract['marca'] . ' ' . $contract['model']) ?></strong>, având anul de fabricație <?= htmlspecialchars($contract['an']) ?> și numărul de înmatriculare <strong><?= htmlspecialchars($contract['numar_inmatriculare']) ?></strong>.
        </div>
        <div class="clauza-text">
            2.2. Durata închirierii este de <strong><?= $total_zile ?> <?= $total_zile == 1 ? 'zi' : 'zile' ?></strong>, începând cu data de <strong><?= date('d.m.Y H:i', strtotime($contract['start_date'])) ?></strong> până la data de <strong><?= date('d.m.Y H:i', strtotime($contract['end_date'])) ?></strong>.
        </div>

        <h2 class="section-title">3. Condiții Financiare și Garanții</h2>
        <div class="clauza-text">
            3.1. Prețul total stabilit pentru întreaga perioadă de exploatare este de <strong><?= number_format($contract['pret_total'], 2) ?> €</strong> (calculat la un tarif de mediu de <?= number_format($pret_zi, 2) ?> €/zi).
        </div>
        <div class="clauza-text">
            3.2. Clientul constituie un depozit de garanție returnabil în valoare de <strong><?= number_format($contract['depozit'], 2) ?> €</strong>. Garanția va fi deblocată integral la returnarea vehiculului în aceeași stare tehnică și estetică în care a fost preluat.
        </div>

        <h2 class="section-title">4. Obligațiile Locatarului (Clientului)</h2>
        <div class="clauza-text">4.1. Să posede permis de conducere național sau internațional valabil pentru categoria autovehiculului închiriat, cu o vechime de minimum un an.</div>
        <div class="clauza-text">4.2. Să nu încredințeze conducerea autovehiculului altor persoane care nu au fost autorizate în mod expres de către societate.</div>
        <div class="clauza-text">4.3. Să suporte costurile amenzilor de circulație, de parcare sau ale oricăror alte sancțiuni contravenționale survenite în perioada de utilizare a autovehiculului.</div>
        <div class="clauza-text">4.4. Să restituie vehiculul la data și ora menționate în contract, având aceeași cantitate de combustibil existentă la predare.</div>

        <h2 class="section-title">5. Responsabilități și Penalități</h2>
        <div class="clauza-text">5.1. Întârzierea neanunțată în predarea autovehiculului cu mai mult de 2 ore se penalizează cu valoarea dublă a tarifului zilnic de închiriere pentru fiecare zi de întârziere.</div>
        <div class="clauza-text">5.2. În caz de accident sau defecțiune tehnică provocată din culpa sa, Clientul este obligat să anunțe imediat reprezentanții companiei și să obțină procesul verbal de la organele de poliție competente.</div>

        <h2 class="section-title">6. Validare și Semnături</h2>
        <div class="clauza-text">
            Prezentul contract este încheiat în format electronic în conformitate cu legislația privind comerțul la distanță. El își produce efectele juridice depline în momentul aprobării rezervării și validării electronice din contul de client.
        </div>

        <table style="width: 100%; margin-top: 50px; border: none;">
            <tr>
                <td style="border:none; padding: 0;">
                    <strong>LOCATOR (Companie):</strong><br>
                    AutoPulse S.R.L.<br>
                    <small style="color:#666;">[Validat electronic - Sistem centralizat]</small>
                </td>
                <td style="text-align: right; border:none; padding: 0;">
                    <strong>LOCATAR (Client):</strong><br>
                    <?= htmlspecialchars($contract['username']) ?><br>
                    <small style="color:#666;">[Semnat prin cont securizat IP]</small>
                </td>
            </tr>
        </table>

        <div class="no-print-zone" style="text-align: center; margin-top: 50px; padding-top: 25px; border-top: 2px dashed #0066b1; display: flex; justify-content: center; gap: 15px;">
            
            <button onclick="window.print()" class="btn-print">
                Printează Contractul
            </button>

            <?php if (isset($_SESSION['admin_loggedin'])): ?>
                <a href="admin/check.php?id=<?= $contract_id ?>" class="btn-check">
                    Execută Check-in/out (Admin)
                </a>
            <?php endif; ?>
            
        </div>

        <p style="text-align:center; margin-top: 40px; color:#666; font-size:0.85rem; font-family: sans-serif;">
            Acest document conține date cu caracter confidențial generate automat. Protejat de termenii de confidențialitate ai platformei AutoPulse.
        </p>
    </div>
</div>

</body>
</html>
<?php $conn->close(); ?>