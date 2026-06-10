<?php
include 'functions.php';
include 'db_connect.php';

// 4 masini random
$masini_stmt = $conn->prepare("
    SELECT id, marca, model, an, combustibil, transmisie, kilometraj,
           pret_inchiriere, pret_vanzare, poza_principala, status
    FROM cars 
    WHERE status = 'disponibil'
    ORDER BY RAND() LIMIT 4
");
$masini_stmt->execute();
$masini = $masini_stmt->get_result();
$total_cars = $conn->query("SELECT COUNT(*) as total FROM cars")->fetch_assoc()['total'];

include 'header.php';
?>
<head>
    <style>
        .hero {
            background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('resurse/imagini/hero-bg.jpg') center/cover no-repeat;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
        }
        .hero-content h1 {
            font-size: 3.5rem;
            margin-bottom: 1rem;
        }
        .hero-cta .btn {
            padding: 1rem 2.5rem;
            font-size: 1.2rem;
            margin: 0 1rem;
        }

         .car-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        .car-card {
            background: var(--card-bg);
            border-radius: 12px;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
            text-decoration: none;
            color: inherit;
        }

        .car-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(0,102,177,0.2);
        }

        .car-image-wrapper {
            width: 100%;
            height: 220px;
            background: #0f141c;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .car-image-wrapper img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            transition: transform 0.5s ease;
        }
        .car-card:hover .car-image-wrapper img {
            transform: scale(1.1);
        }
        .car-info {
            padding: 1.5rem;
            text-align: center;
        }

        .car-info h3 {
            margin: 0 0 0.8rem;
            font-size: 1.4rem;
        }

        .car-info .details {
            color: var(--gray);
            font-size: 0.95rem;
            margin: 0.4rem 0;
        }

        .car-info .price {
            font-size: 1.3rem;
            color: var(--accent-blue);
            font-weight: 700;
            margin: 0.8rem 0;
        }

        .car-info .status {
            display: inline-block;
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }
        .status.disponibil { background: #4caf50; color: white; }

        .calculator {
            padding: 4rem 5%;
            text-align: center;
        }
        .simulator-box {
            max-width: 600px;
            margin: 0 auto;
            padding: 30px;
            background: #1a2230;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.4);
            color: white;
            text-align: left;
        }

        .section-divider {
            height: 2px;
            background: linear-gradient(to right, transparent, #0066b1, transparent);
            margin: 4rem 0;
        }

        .stats {
            background: #0a0e14;
            padding: 5rem 5%;
            text-align: center;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 3rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        .stat-item h3 {
            font-size: 3rem;
            color: var(--accent-blue);
            margin: 0 0 8px 0;
        }
        .testimonials h2 {
            text-align: center;
            width: 100%;
            margin-bottom: 3rem;
        }
        .testimonial-slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            text-align: center;
            padding: 2rem 1rem;
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1);
            pointer-events: none;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .testimonial-slide.active {
            opacity: 1;
            transform: translateX(0);
            pointer-events: auto;
        }
        .testimonial-slide.exit {
            opacity: 0;
            transform: translateX(-100%);
        }
        .testimonial-slider {
            max-width: 900px;
            margin: 0 auto;
            position: relative;
            min-height: 200px;
            overflow: hidden;
        }
        .stars {
            color: #ffd700;
            font-size: 1.6rem;
            margin-bottom: 1rem;
        }
        .newsletter {
            background: #0a0e14;
            padding: 5rem 5%;
            text-align: center;
        }
        .newsletter form {
            max-width: 500px;
            margin: 0 auto;
            display: flex;
            gap: 12px;
        }
        .newsletter input {
            flex: 1;
            padding: 16px 20px;
            border-radius: 50px;
            border: 1px solid #334155;
            background: #1e293b;
            color: white;
        }
    </style>
</head>
<body>
    <section id="hero" class="hero">
        <div class="hero-content">
            <h1>Descoperă Plăcerea Conducerii Premium</h1>
            <p>Flotă selectă de mașini de lux - disponibile pentru vânzare sau închiriere.</p>
            <div class="hero-cta">
                <a href="masini.php" class="btn primary">Vezi Flota</a>
                <?php if(!isset($_SESSION['loggedin'])): ?>
                    <a href="login.php" class="btn secondary">Autentifică-te</a>
                <?php endif; ?>
            </div>
        </div>
    </section>
	<div class="section-divider"></div>
    <section id="masini" class="section cars">
        <h2>Flota Noastră</h2>
        <div class="car-grid">
            <?php while ($car = $masini->fetch_assoc()): ?>
                <a href="detalii-masina.php?id=<?= $car['id'] ?>" class="car-card">
                    <div class="car-image-wrapper">
                        <img 
                            src="<?= htmlspecialchars($car['poza_principala'] ?? 'resurse/imagini/placeholder-car.png') ?>" 
                            alt="<?= htmlspecialchars($car['marca'] . ' ' . $car['model']) ?>" 
                            loading="lazy"
                            onerror="this.src='resurse/imagini/placeholder-car.png';"
                        >
                    </div>
                    <div class="car-info">
                        <h3><?= htmlspecialchars($car['marca'] . ' ' . $car['model']) ?></h3>
                        <p class="details">
                            <?= $car['an'] ?? 'N/A' ?> • 
                            <?= htmlspecialchars($car['combustibil'] ?? '-') ?> • 
                            <?= htmlspecialchars($car['transmisie'] ?? '-') ?> • 
                            <?= number_format($car['kilometraj'] ?? 0) ?> km
                        </p>
                        <p class="price">
                            <?php if ($car['pret_inchiriere'] > 0): ?>
                                de la <?= number_format($car['pret_inchiriere'], 2) ?> €/zi
                            <?php elseif ($car['pret_vanzare'] > 0): ?>
                                <?= number_format($car['pret_vanzare'], 2) ?> €
                            <?php else: ?>
                                Preț la cerere
                            <?php endif; ?>
                        </p>
                        <span class="status <?= htmlspecialchars(strtolower($car['status'])) ?>">
                            <?= ucfirst(htmlspecialchars($car['status'])) ?>
                        </span>
                    </div>
                </a>
            <?php endwhile; ?>
        </div>
        <div style="text-align:center; margin-top:2rem;">
            <a href="masini.php" class="btn primary">Vezi toată flota</a>
        </div>
    </section>

    <div class="section-divider"></div>

    <section id="stats-section" class="stats">
        <div class="stats-grid">
            <div class="stat-item">
                <h3 data-target="<?= $total_cars ?>" class="counter">0</h3>
                <p>Mașini în flotă</p>
            </div>
            <div class="stat-item">
                <h3 data-target="1240" class="counter">0</h3>
                <p>Clienți mulțumiți</p>
            </div>
            <div class="stat-item">
                <h3 data-target="98" class="counter">0</h3>
                <p>Rată de satisfacție</p>
            </div>
            <div class="stat-item">
                <h3 data-target="24" class="counter">0</h3>
                <p>Suport clienți</p>
            </div>
        </div>
    </section>

    <div class="section-divider"></div>

    <section class="testimonials">
        <h2>Ce spun clienții noștri</h2>
        <div class="testimonial-slider" id="testimonialSlider">
            <div class="testimonial-slide active">
                <div class="stars">★★★★★</div>
                <p>"Cel mai bun dealer din care am cumpărat. Totul a fost extrem de profesionist..."</p>
                <strong>— Mihai Popescu, București</strong>
            </div>
            <div class="testimonial-slide">
                <div class="stars">★★★★★</div>
                <p>"Am închiriat o mașină pentru o săptămână și experiența a fost excelentă..."</p>
                <strong>— Elena Ionescu, Cluj</strong>
            </div>
            <div class="testimonial-slide">
                <div class="stars">★★★★★</div>
                <p>"Serviciu rapid, mașini curate și prețuri corecte. Revin cu siguranță!"</p>
                <strong>— Andrei Dumitrescu, Timișoara</strong>
            </div>
        </div>
    </section>

    <div class="section-divider"></div>

    <section class="newsletter">
        <h2>Abonează-te pentru oferte exclusive</h2>
        <p style="margin-bottom: 1.8rem; color: #94a3b8;">Primește primele oferte și promoții speciale direct pe email.</p>
        <form style="max-width: 520px; margin: 0 auto; display: flex; gap: 12px;">
            <input type="email" placeholder="Adresa ta de email" required>
            <button type="submit" class="btn primary" style="white-space: nowrap;">Abonează-mă</button>
        </form>
    </section>
    
    <div class="section-divider"></div>

   <!-- <section class="section calculator">
        <h2>Calculează-ți Rata Lunară</h2>
        
        <div class="simulator-box">
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #94a3b8;">Alege Autovehiculul:</label>
                <select id="leasingPretMasina" onchange="calculeazaLeasing()" style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #2a3443; background: #0f172a; color: white; font-size: 1rem; box-sizing: border-box; outline: none;">
                    <?php
                    $calc_stmt = $conn->query("SELECT id, marca, model, pret_vanzare FROM cars WHERE pret_vanzare > 0 LIMIT 12");
                    $first = true;
                    while ($calc_car = $calc_stmt->fetch_assoc()):
                    ?>
                        <option value="<?= $calc_car['pret_vanzare'] ?>"><?= htmlspecialchars($calc_car['marca'] . ' ' . $calc_car['model']) ?> (<?= number_format($calc_car['pret_vanzare'], 0) ?> €)</option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div style="margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between; font-weight: 600; margin-bottom: 8px; color: #94a3b8;">
                    <label>Avans Finanțat (%):</label>
                    <span id="afisajAvansProcent" style="color: #0066b1;">20%</span>
                </div>
                <input type="range" id="leasingAvans" min="10" max="50" value="20" step="5" oninput="calculeazaLeasing()" style="width: 100%; cursor: pointer;">
                <small style="color: #64748b; display: block; margin-top: 4px;">Valoare avans reținută: <span id="afisajAvansValoare" style="color: #fff;">0</span> €</small>
            </div>

            <div style="margin-bottom: 25px;">
                <div style="display: flex; justify-content: space-between; font-weight: 600; margin-bottom: 8px; color: #94a3b8;">
                    <label>Durată Contract (Luni):</label>
                    <span id="afisajLuni" style="color: #0066b1;">36 luni</span>
                </div>
                <input type="range" id="leasingLuni" min="12" max="60" value="36" step="12" oninput="calculeazaLeasing()" style="width: 100%; cursor: pointer;">
            </div>

            <div style="background: #0f172a; padding: 20px; border-radius: 10px; border-left: 5px solid #2ecc71; text-align: center;">
                <p style="margin: 0; color: #94a3b8; font-size: 0.9rem; text-transform: uppercase; font-weight: bold; letter-spacing: 0.5px;">Rată Lunară Fixă</p>
                <p style="margin: 10px 0 0 0; color: #2ecc71; font-size: 2.2rem; font-weight: 800;"><span id="rezultatRata">0.00</span> € <span style="font-size: 1rem; font-weight: 400; color: #64748b;">/ lună</span></p>
                <small style="color: #64748b; display: block; margin-top: 10px;">Dobândă fixă inclusă: <b>3% pe an</b> • Fără comisioane ascunse</small>
            </div>
        </div>
    </section>*/

    <div class="section-divider"></div>-->

	<section id="despre" class="section about" style="text-align: center;">
        <h2>Despre Noi</h2>
        <div class="about-content" style="max-width: 800px; margin: 0 auto;">
            <p>Suntem un dealer auto dedicat să oferim <strong>cele mai bune soluții</strong> pentru achiziția de mașini noi și second-hand.
            <br><br>
            Cu o gamă variată de vehicule, îți oferim posibilitatea de a achiziționa mașina visurilor tale în deplină siguranță, toate mașinile noastre fiind livrate cu o <strong>garanție extinsă de 12 luni</strong> și revizie completă inclusă.</p>
            
            <p style="margin-top: 1.5rem;"><strong>Calitatea, profesionalismul</strong> și satisfacția clienților sunt prioritățile noastre.</p>
        </div>
    </section>
	<div class="section-divider"></div>
    <section id="oferte" class="section offers">
        <h2>Oferte Speciale</h2>
        <div class="offer-grid">
            <div class="offer-card">
                <h3>Garanție Premium</h3>
                <p>Toate autovehiculele noastre destinate vânzării beneficiază de 12 luni garanție inclusă.</p>
            </div>
            <div class="offer-card">
                <h3>Trade-In Avantajos</h3>
                <p>Schimbă-ți mașina veche cu una nouă la preț special.</p>
            </div>
        </div>
    </section>

</main>

<?php include 'footer.php'; ?>

<script>
let countersAnimated = false;

function animateCounters() {
    if (countersAnimated) return;
    
    const counters = document.querySelectorAll('.counter');
    counters.forEach(counter => {
        const target = parseInt(counter.getAttribute('data-target'));
        let count = 0;
        const duration = 2000; // 2 secunde
        const increment = target / (duration / 30);
        
        const timer = setInterval(() => {
            count += increment;
            if (count >= target) {
                counter.textContent = target + (target === 98 ? '%' : '');
                clearInterval(timer);
            } else {
                counter.textContent = Math.floor(count) + (target === 98 ? '%' : '');
            }
        }, 30);
    });
    countersAnimated = true;
}

window.addEventListener('scroll', () => {
    const statsSection = document.getElementById('stats-section');
    if (!statsSection) return;

    const sectionTop = statsSection.getBoundingClientRect().top;
    const windowHeight = window.innerHeight;

    if (sectionTop < windowHeight * 0.85) {
        animateCounters();
    }
});

// ====================== TESTIMONIAL SLIDER (LEFT-EXIT) ======================
let currentSlide = 0;
const slides = document.querySelectorAll('.testimonial-slide');

function nextSlide() {
    slides[currentSlide].classList.remove('active');
    slides[currentSlide].classList.add('exit');
    
    let oldSlide = currentSlide;
    currentSlide = (currentSlide + 1) % slides.length;

    slides[currentSlide].classList.remove('exit');
    slides[currentSlide].classList.add('active');

    setTimeout(() => {
        slides[oldSlide].classList.remove('exit');
    }, 800);
}

setInterval(nextSlide, 5000);

// ====================== COD CORECTAT: LOGICĂ SIMULATOR FINANCIAR 3% ======================
function calculeazaLeasing() {
    const selector = document.getElementById('leasingPretMasina');
    if(!selector) return;

    const pret = parseFloat(selector.value) || 0;
    const avansProcent = parseInt(document.getElementById('leasingAvans').value) || 20;
    const luni = parseInt(document.getElementById('leasingLuni').value) || 36;
    
    const dobandaAnuala = 0.03; // Dobânda ta de 3%
    const dobandaLunara = dobandaAnuala / 12;

    const valoareAvans = pret * (avansProcent / 100);
    const sumaFinantata = pret - valoareAvans;

    document.getElementById('afisajAvansProcent').innerText = avansProcent + "%";
    document.getElementById('afisajAvansValoare').innerText = Math.round(valoareAvans).toLocaleString('ro-RO');
    document.getElementById('afisajLuni').innerText = luni + " luni (" + (luni/12) + " ani)";

    let rataLunara = 0;
    if (pret > 0 && luni > 0) {
        // Formula oficială de anuitate financiară cu dobândă compusă distribuită lunar (PMT)
        rataLunara = (sumaFinantata * dobandaLunara) / (1 - Math.pow(1 + dobandaLunara, -luni));
    }

    document.getElementById('rezultatRata').innerText = rataLunara.toFixed(2);
}

// Initializam simulatorul imediat ce elementele DOM s-au incarcat in pagina
document.addEventListener("DOMContentLoaded", calculeazaLeasing);
</script>
</body>
</html>