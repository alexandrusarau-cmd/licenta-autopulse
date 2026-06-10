<?php
session_start();
if(!isset($_SESSION['admin_loggedin'])) {
    header("Location: admin_login.php");
    exit;
}
include '../db_connect.php';


$available = $conn->query("SELECT COUNT(*) AS cnt FROM cars WHERE status='disponibil'")->fetch_assoc()['cnt'];

$rented = $conn->query("SELECT COUNT(*) AS cnt FROM cars WHERE status IN ('inchiriat', 'închiriat')")->fetch_assoc()['cnt'];

$active_rentals = $conn->query("SELECT COUNT(*) AS cnt FROM rentals WHERE status IN ('pending', 'confirmat')")->fetch_assoc()['cnt'];

$users = $conn->query("SELECT COUNT(*) AS cnt FROM users")->fetch_assoc()['cnt'];


$current_month = date('Y-m');

$venit_inchirieri = $conn->query("
    SELECT COALESCE(SUM(price),0) as total
    FROM rentals
    WHERE status IN ('confirmat','finalizat')
    AND DATE_FORMAT(start_date,'%Y-%m')='$current_month'
")->fetch_assoc()['total'];

$venit_vanzari = $conn->query("
    SELECT COALESCE(SUM(pret_vanzare),0) as total
    FROM cars
    WHERE status='vandut'
")->fetch_assoc()['total'];

$venit_total = $venit_inchirieri + $venit_vanzari;


$total_cars = $conn->query("SELECT COUNT(*) AS cnt FROM cars WHERE vizibil = 1")->fetch_assoc()['cnt'];

$occupancy_rate = $total_cars > 0
    ? round(($rented / $total_cars) * 100)
    : 0;


$luni_grafic = [];
$date_inchirieri_grafic = [];
$date_vanzari_grafic = [];
$date_total_grafic = []; 

for ($i = 5; $i >= 0; $i--) {

    $data = new DateTime('first day of this month');
    $data->modify("-$i months");

    $luna = $data->format('Y-m');
    $nume = $data->format('M Y');

    $row_rent = $conn->query("
        SELECT COALESCE(SUM(price),0) as total
        FROM rentals
        WHERE status IN ('confirmat','finalizat')
        AND start_date >= '$luna-01'
        AND start_date < DATE_ADD('$luna-01', INTERVAL 1 MONTH)
    ")->fetch_assoc();

    $luni_grafic[] = $nume;
    $v_inchiriere_luna = $row_rent['total'];
    
    $v_vanzare_luna = ($luna === $current_month) ? $venit_vanzari : 0;

    $date_inchirieri_grafic[] = $v_inchiriere_luna;
    $date_vanzari_grafic[] = $v_vanzare_luna;
    $date_total_grafic[] = $v_inchiriere_luna + $v_vanzare_luna;
}

$current = $date_total_grafic[count($date_total_grafic)-1] ?? 0;
$previous = $date_total_grafic[count($date_total_grafic)-2] ?? 0;

$growth_rate = $previous > 0
    ? round((($current - $previous) / $previous) * 100, 1)
    : 0;

$avg_monthly = count($date_total_grafic)
    ? array_sum($date_total_grafic) / count($date_total_grafic)
    : 0;

$top_cars = $conn->query("
    SELECT 
        c.id,
        c.marca,
        c.model,
        c.status,
        c.pret_inchiriere,
        c.pret_vanzare,
        COUNT(r.id) AS total_inchirieri,
        IF(c.pret_inchiriere > 0, ROUND(COALESCE(SUM(r.price), 0) / c.pret_inchiriere), 0) AS total_zile,
        (COALESCE(SUM(r.price), 0) + IF(c.status = 'vandut', c.pret_vanzare, 0)) AS venit_total
    FROM cars c
    LEFT JOIN rentals r ON c.id = r.car_id AND r.status IN ('confirmat','finalizat')
    GROUP BY c.id
    HAVING venit_total > 0
    ORDER BY venit_total DESC
");


// economic stuff

$valoare_parc = $conn->query("
    SELECT COALESCE(SUM(pret_vanzare), 0) AS total 
    FROM cars 
    WHERE status != 'vandut' AND vizibil = 1
")->fetch_assoc()['total'];

$rev_per_car = $total_cars > 0 ? ($venit_total / $total_cars) : 0;

$total_rentals = $conn->query("SELECT COUNT(*) AS cnt FROM rentals")->fetch_assoc()['cnt'];
$successful_rentals = $conn->query("SELECT COUNT(*) AS cnt FROM rentals WHERE status IN ('confirmat', 'finalizat')")->fetch_assoc()['cnt'];

$conversion_rate = $total_rentals > 0 
    ? round(($successful_rentals / $total_rentals) * 100, 1) 
    : 0;

// valoarea medie a tranzacției pe inchirieri luna curenta
$aov_inchirieri = $successful_rentals > 0 ? ($venit_inchirieri / $successful_rentals) : 0;

// durata medie inchiriere
$total_zile_inchiriate = $conn->query("
    SELECT COALESCE(SUM(DATEDIFF(end_date, start_date)), 0) AS total 
    FROM rentals 
    WHERE status IN ('confirmat', 'finalizat')
")->fetch_assoc()['total'];

$durata_medie = $successful_rentals > 0 ? round($total_zile_inchiriate / $successful_rentals, 1) : 0;
    
include 'admin_header.php';
    
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin - Autopulse</title>

    <link rel="stylesheet" href="../resurse/css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }

        .kpi-card {
            background: #11151d;
            padding: 1.8rem;
            border-radius: 12px;
            text-align: center;
            position: relative; 
        }

        .kpi-number {
            font-size: 2.4rem;
            font-weight: 700;
            color: #fff;
        }

        .kpi-label {
            color: #fff;
            font-weight: 600;
            padding-right: 15px; 
        }

        .kpi-card small {
            color: #fff;
        }

        /* ? icon */
        .kpi-help {
            position: absolute;
            top: 12px;
            right: 14px;
            background: #1b2230;
            color: #8892b0;
            width: 20px;
            height: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border: 1px solid #2a3443;
            z-index: 100;
        }

        /* Bula de text */
        .kpi-help::after {
            content: attr(data-tooltip); 
            position: absolute;
            top: 28px; 
            right: 0;
            background: #2563eb; 
            color: #ffffff;
            font-family: sans-serif;
            font-weight: normal;
            font-size: 0.85rem;
            line-height: 1.4;
            padding: 8px 14px;
            border-radius: 8px; 
            width: 240px;
            text-align: left;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5);
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transition: opacity 0.2s ease, visibility 0.2s ease;
        }

        .kpi-help:hover::after {
            opacity: 1;
            visibility: visible;
        }
    </style>
</head>

<body>

<main style="padding: 2rem;">

<h2 style="color:#E74C3C;">Panou de Control - <?= date('F Y') ?></h2>

<div class="dashboard-grid">

    <div class="kpi-card">
        <span class="kpi-help" data-tooltip="Suma totală încasată din contractele de închiriere active/finalizate în această lună adunată cu prețul mașinilor vândute definitiv.">?</span>
        <div class="kpi-label">Venituri Lună Curentă</div>
        <div class="kpi-number"><?= number_format($venit_total,0) ?> €</div>
        <small>Închirieri: <?= number_format($venit_inchirieri,0) ?>€ | Vânzări: <?= number_format($venit_vanzari,0) ?>€</small>
    </div>

    <div class="kpi-card">
        <span class="kpi-help" data-tooltip="Procentul de mașini ocupate (închiriate) în prezent din totalul flotei vizibile pe site-ul public.">?</span>
        <div class="kpi-label">Grad Ocupare Flotă</div>
        <div class="kpi-number"><?= $occupancy_rate ?>%</div>
        <small><?= $rented ?> din <?= $total_cars ?></small>
    </div>

    <div class="kpi-card">
        <span class="kpi-help" data-tooltip="Numărul total de rezervări auto care se află în stadiul de procesare (În așteptare) sau aprobate deja (Confirmate).">?</span>
        <div class="kpi-label">Rezervări Active</div>
        <div class="kpi-number"><?= $active_rentals ?></div>
        <small>Pending + Confirmate</small>
    </div>

    <div class="kpi-card">
        <span class="kpi-help" data-tooltip="Numărul total de conturi de utilizatori înregistrate în baza de date a platformei.">?</span>
        <div class="kpi-label">Utilizatori</div>
        <div class="kpi-number"><?= $users ?></div>
        <small>Total conturi</small>
    </div>

    <div class="kpi-card">
        <span class="kpi-help" data-tooltip="Rata procentuală de creștere sau descreștere a veniturilor totale din luna curentă comparativ cu luna anterioară.">?</span>
        <div class="kpi-label">Creștere Lunară</div>
        <div class="kpi-number"><?= $growth_rate ?>%</div>
        <small>vs luna trecută (Total)</small>
    </div>

    <div class="kpi-card">
        <span class="kpi-help" data-tooltip="Media aritmetică a încasărilor totale generate de companie pe parcursul ultimelor 6 luni analizate.">?</span>
        <div class="kpi-label">Media Lunară</div>
        <div class="kpi-number"><?= number_format($avg_monthly,0) ?> €</div>
        <small>Total business (6 luni)</small>
    </div>
    
    <div class="kpi-card">
        <span class="kpi-help" data-tooltip="Valoarea totală a mașinilor de vânzare din stoc și numărul de mașini active destinate exclusiv închirierii.">?</span>
        <div class="kpi-label">Valoare Patrimoniu Activ</div>
        <div class="kpi-number" style="color: #2ecc71; font-size: 1.8rem; margin-top: 0.5rem;">
            <?= number_format($valoare_parc, 0) ?> € <span style="font-size: 1rem; color: #fff;">(Vânzări)</span>
        </div>
        <small style="color: #3498db; font-weight: bold;">
            + <?= $total_cars - $conn->query("SELECT COUNT(*) AS cnt FROM cars WHERE status='de vanzare' AND vizibil=1")->fetch_assoc()['cnt'] ?> Mașini la Închiriere
        </small>
    </div>

    <div class="kpi-card">
        <span class="kpi-help" data-tooltip="RevPAG (Revenue Per Available Garage): Venitul mediu financiar generat per unitate auto din totalul flotei în luna curentă.">?</span>
        <div class="kpi-label">Venit Mediu / Mașină</div>
        <div class="kpi-number" style="color: #ffcc00;"><?= number_format($rev_per_car, 0) ?> €</div>
        <small>RevPAG (Luna Curentă)</small>
    </div>

    <div class="kpi-card">
        <span class="kpi-help" data-tooltip="Procentul solicitărilor transformate cu succes în contracte încasate (status Confirmat/Finalizat) din numărul total de cereri plasate.">?</span>
        <div class="kpi-label">Rată Conversie Rezervări</div>
        <div class="kpi-number" style="color: #3498db;"><?= $conversion_rate ?>%</div>
        <small>Procent contracte încasate</small>
    </div>

    <div class="kpi-card">
        <span class="kpi-help" data-tooltip="Average Order Value (AOV): Suma medie facturată și încasată pentru un singur contract de închiriere aprobat în luna curentă.">?</span>
        <div class="kpi-label">Valoare Medie Contract</div>
        <div class="kpi-number" style="color: #a855f7;"><?= number_format($aov_inchirieri, 1) ?> €</div>
        <small>AOV mediu pe închirieri</small>
    </div>

    <div class="kpi-card">
        <span class="kpi-help" data-tooltip="Average Length of Stay/Rent (ALOS): Numărul mediu de zile pentru care clienții rețin mașinile rezervate prin platformă.">?</span>
        <div class="kpi-label">Durată Medie Închiriere</div>
        <div class="kpi-number" style="color: #f43f5e;"><?= $durata_medie ?> zile</div>
        <small>ALOS istoric pe flotă</small>
    </div>

</div>

<div style="background:#11151d; padding:2rem; border-radius:12px; margin-top:2rem; width: 100%; box-sizing: border-box;">
    <h3 style="color:#fff; margin-bottom: 15px;">Evoluție Venituri</h3>
    <div style="position: relative; height: 350px; width: 100%;">
        <canvas id="revenueChart"></canvas>
    </div>
</div>

<div style="background:#11151d;padding:2rem;border-radius:12px;margin-top:2rem;">
    <div>
        <h3 style="color:#fff; margin-bottom: 8px;">Primele 5 Mașini (după venitul generat)</h3>
        <input type="text" id="carSearchInput" onkeyup="filterTopCars()" placeholder="Caută marcă sau model..." 
               style="background: #1b222c; color: #fff; border: 1px solid #ffcc00; padding: 0.6rem 1rem; border-radius: 8px; width: 280px; outline: none; font-size: 0.95rem; margin-bottom: 20px; display: block;">
    </div>
    
    <div style="max-height: 450px; overflow-y: auto; padding-right: 5px;">
        <table style="width:100%;color:#fff;border-collapse:collapse;">
            <thead>
                <tr style="border-bottom: 2px solid #1b222c; position: sticky; top: 0; background: #11151d; z-index: 1;">
                    <th style="text-align:left; padding: 10px;">Mașină</th>
                    <th style="padding: 10px;">Închirieri efectuate</th>
                    <th style="text-align:right; padding: 10px;">Venit Total</th>
                </tr>
            </thead>
                <tbody id="topCarsTableBody">
                <?php 
                $index = 0;
                while($car = $top_cars->fetch_assoc()): 
                    $index++;
                    $style_ascuns = ($index > 5) ? 'display: none;' : '';
                    $clasa_rand = ($index > 5) ? 'car-row extra-car' : 'car-row';
                ?>
                    <tr class="<?= $clasa_rand ?>" style="border-bottom: 1px solid #1b222c; <?= $style_ascuns ?>">
                        <td style="padding: 12px 10px;">
                            <span class="car-name" style="font-weight: 600; font-size: 1.05rem;"><?= htmlspecialchars($car['marca'] . ' ' . $car['model']) ?></span>
                            <small style="color: #a0a8b4; font-size: 0.8rem; display: block; margin-top: 2px;">
                                Status: <?= strtoupper($car['status']) ?>
                            </small>
                        </td>

                        <td style="text-align:center; padding: 12px 10px;">
                            <div style="font-size: 1.1rem; font-weight: 600;"><?= $car['total_inchirieri'] ?>
                                <span style="font-size: 0.9rem; color: #a0a8b4; font-weight: 400;">
                                    (<?= $car['total_zile'] ?> <?= $car['total_zile'] == 1 ? 'zi' : 'zile' ?>)
                                </span>
                            </div>
                            
                            <?php if(strtolower($car['status']) !== 'vandut' && isset($car['pret_inchiriere']) && $car['pret_inchiriere'] > 0): ?>
                                <small style="color: #ffcc00; font-size: 0.8rem; display: block; margin-top: 2px;">
                                    (<?= number_format($car['pret_inchiriere'], 0) ?> €/zi)
                                </small>
                            <?php endif; ?>
                        </td>

                        <td style="text-align:right; padding: 12px 10px;">
                            <div style="font-size: 1.1rem; font-weight: 600; color: #fff;">
                                <?= number_format($car['venit_total'], 0) ?> €
                            </div>

                            <?php if(strtolower($car['status']) === 'vandut' && isset($car['pret_vanzare']) && $car['pret_vanzare'] > 0): ?>
                                <small style="color: #2ecc71; font-size: 0.8rem; display: block; margin-top: 2px;">
                                    (Vândută cu <?= number_format($car['pret_vanzare'], 0) ?> €)
                                </small>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
        </table>
    </div>
</div>
</main>

<script>
function filterTopCars() {
    const input = document.getElementById('carSearchInput');
    const filter = input.value.toLowerCase().trim();
    
    const tbody = document.getElementById('topCarsTableBody');
    const rows = tbody.getElementsByClassName('car-row');

    for (let i = 0; i < rows.length; i++) {
        const carNameSpan = rows[i].querySelector('.car-name');
        
        if (carNameSpan) {
            const txtValue = carNameSpan.textContent || carNameSpan.innerText;
            
            if (filter !== "") {
                if (txtValue.toLowerCase().indexOf(filter) > -1) {
                    rows[i].style.display = ""; 
                } else {
                    rows[i].style.display = "none"; 
                }
            } 
            else {
                if (i < 5) {
                    rows[i].style.display = ""; 
                } else {
                    rows[i].style.display = "none"; 
                }
            }
        }
    }
}

const ctx = document.getElementById('revenueChart');

new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($luni_grafic) ?>,
        datasets: [
            {
                label: 'Închirieri (€)',
                data: <?= json_encode($date_inchirieri_grafic) ?>,
                backgroundColor: '#ffcc00',
                yAxisID: 'y'
            },
            {
                label: 'Vânzări (€)',
                data: <?= json_encode($date_vanzari_grafic) ?>,
                backgroundColor: '#2ecc71',
                yAxisID: 'y1'
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: { 
                type: 'linear',
                display: true,
                position: 'left',
                beginAtZero: true,
                ticks: { color: '#ffcc00' },
                grid: { color: 'rgba(255, 255, 255, 0.1)' }
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                beginAtZero: true,
                ticks: { color: '#2ecc71' },
                grid: { drawOnChartArea: false }
            },
            x: { 
                ticks: { color: '#fff' } 
            }
        },
        plugins: {
            legend: {
                labels: { color: '#fff' }
            }
        }
    }
});
</script>

</body>
</html>