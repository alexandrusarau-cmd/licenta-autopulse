<?php
include '../functions.php';
include '../db_connect.php';

if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    header("Location: admin_login.php");
    exit;
}

if (isset($_GET['action']) && isset($_GET['id'])) {
    $sale_id = (int)$_GET['id'];
    $action = $_GET['action'];

    $stmt_check = $conn->prepare("SELECT car_id, status FROM sales WHERE id = ?");
    $stmt_check->bind_param("i", $sale_id);
    $stmt_check->execute();
    $sale_data = $stmt_check->get_result()->fetch_assoc();
    $stmt_check->close();

    if ($sale_data) {
        $car_id = $sale_data['car_id'];

        if ($action === 'approba' && $sale_data['status'] === 'pending') {
            $conn->begin_transaction();
            try {
                $update_sale = $conn->prepare("UPDATE sales SET status = 'finalizat' WHERE id = ?");
                $update_sale->bind_param("i", $sale_id);
                $update_sale->execute();

                $update_car = $conn->prepare("UPDATE cars SET status = 'vandut' WHERE id = ?");
                $update_car->bind_param("i", $car_id);
                $update_car->execute();

                $stmt_sales_info = $conn->prepare("SELECT user_id, price FROM sales WHERE id = ?");
                $stmt_sales_info->bind_param("i", $sale_id);
                $stmt_sales_info->execute();
                $s_info = $stmt_sales_info->get_result()->fetch_assoc();
                $stmt_sales_info->close();

                $user_id = $s_info['user_id'];
                $pret_total = (float)$s_info['price'];
                
                $baza_impozabila = $pret_total / 1.21;
                $tva = $pret_total - $baza_impozabila;

                $data_cod = date('Ymd');
                $id_formatat = str_pad($sale_id, 5, '0', STR_PAD_LEFT);
                $numar_contract = 'VNZ-' . $data_cod . '-' . $id_formatat;
                $numar_factura = 'AP-FT-' . $data_cod . '-' . $id_formatat;

                $insert_invoice = $conn->prepare("
                    INSERT INTO sales_invoices 
                    (sale_id, user_id, car_id, invoice_number, contract_number, pret_total, baza_impozabila, tva) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $insert_invoice->bind_param("iiisssdd", $sale_id, $user_id, $car_id, $numar_factura, $numar_contract, $pret_total, $baza_impozabila, $tva);
                $insert_invoice->execute();
                $insert_invoice->close();

                $conn->commit();
                echo "<script>alert('Vânzare aprobată, iar contractul și factura au fost salvate în baza de date!'); window.location.href='admin_sales.php';</script>";
                exit;
            } catch (Exception $e) {
                $conn->rollback();
                echo "<script>alert('Eroare la procesare: " . $e->getMessage() . "'); window.location.href='admin_sales.php';</script>";
                exit;
            }
        }
        elseif ($action === 'respinge' && $sale_data['status'] === 'pending') {
            $update_sale = $conn->prepare("UPDATE sales SET status = 'respins' WHERE id = ?");
            $update_sale->bind_param("i", $sale_id);
            
            if ($update_sale->execute()) {
                echo "<script>alert('Cererea a fost respinsă. Mașina rămâne de vânzare pe site.'); window.location.href='admin_sales.php';</script>";
            } else {
                echo "<script>alert('Eroare la respingere.'); window.location.href='admin_sales.php';</script>";
            }
            $update_sale->close();
            exit;
        }
        elseif ($action === 'sterge') {
            $conn->begin_transaction();
            try {
                $delete_sale = $conn->prepare("DELETE FROM sales WHERE id = ?");
                $delete_sale->bind_param("i", $sale_id);
                $delete_sale->execute();

                if ($sale_data['status'] === 'finalizat') {
                    $update_car = $conn->prepare("UPDATE cars SET status = 'disponibil' WHERE id = ?");
                    $update_car->bind_param("i", $car_id);
                    $update_car->execute();
                }

                $conn->commit();
                echo "<script>alert('Înregistrarea a fost ștearsă cu succes!'); window.location.href='admin_sales.php';</script>";
                exit;
            } catch (Exception $e) {
                $conn->rollback();
                echo "<script>alert('Eroare la ștergere: " . $e->getMessage() . "'); window.location.href='admin_sales.php';</script>";
                exit;
            }
        }
    }
}

$sql_sales = "
    SELECT s.id, s.nume, s.telefon, s.email, s.mesaj, s.price, s.sale_date, s.status,
           ca.marca, ca.model, ca.numar_inmatriculare,
           si.contract_number, si.invoice_number
    FROM sales s
    JOIN cars ca ON s.car_id = ca.id
    LEFT JOIN sales_invoices si ON s.id = si.sale_id
    ORDER BY s.sale_date DESC
";
$result_sales = $conn->query($sql_sales);

$page_title = "Gestiune Vânzări Auto - Autopulse";
include 'admin_header.php';
?>

<main style="padding: 2rem;">
    <h2 style="color:#E74C3C; margin-bottom: 1.5rem;">Gestiune Cereri Cumpărare</h2>

	<div style="margin-bottom: 1.5rem; display: flex; align-items: center; gap: 15px;">
        <label style="font-weight: 600; white-space: nowrap; margin: 0;">Căutare rapidă acte (Contract, Client, Mașină):</label>
        <input type="text" id="salesSearchInput" onkeyup="filterSalesTable()" placeholder="Caută după nr. contract (ex: VNZ-...), factură (ex: AP-FT-...), nume, email sau mașină..." style="padding: 10px 14px; width: 100%; max-width: 450px; border-radius: 6px; border: 1px solid #cbd5e1; outline: none; font-size: 0.95rem; color: #000; margin: 0;">
    </div>

    <?php if ($result_sales && $result_sales->num_rows > 0): ?>
        <table class="rentals-table" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr>
                    <th>Număr Acte / ID</th>
                    <th>Client / Contact</th>
                    <th>Autovehicul solicitat</th>
                    <th>Preț Ofertat</th>
                    <th>Data Solicitării</th>
                    <th>Mesaj Client</th>
                    <th>Status</th>
                    <th>Acțiuni</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($sale = $result_sales->fetch_assoc()): ?>
                    <tr class="sale-row">
                        <td class="searchable-sale">
                            <?php if (!empty($sale['contract_number'])): ?>
                                <span style="color: #0066b1; font-weight: bold; display: block; font-size: 0.85rem;">Contract: <?= htmlspecialchars($sale['contract_number']) ?></span>
                                <span style="color: #2ecc71; font-weight: bold; display: block; font-size: 0.85rem; margin-top: 2px;">Factura: <?= htmlspecialchars($sale['invoice_number']) ?></span>
                            <?php endif; ?>
                            <small style="color: #64748b; display: block; margin-top: 4px;"><strong>ID Vânzare: #<?= $sale['id'] ?></strong></small>
                        </td>
                        <td class="searchable-sale">
                            <span style="font-weight: 700; font-size: 1.05rem;"><?= htmlspecialchars($sale['nume']) ?></span><br>
                            <span style="color: #64748b; font-size: 0.9rem;">📞 <?= htmlspecialchars($sale['telefon']) ?></span><br>
                            <span style="color: #38bdf8; font-size: 0.85rem;">✉ <?= htmlspecialchars($sale['email']) ?></span>
                        </td>
                        <td class="searchable-sale">
                            <strong style="font-size: 1.05rem;"><?= htmlspecialchars($sale['marca'] . ' ' . $sale['model']) ?></strong><br>
                            <span style="color: #f59e0b; font-weight: 600; font-size: 0.85rem;">[ <?= htmlspecialchars($sale['numar_inmatriculare'] ?? 'Fără Nr.') ?> ]</span>
                        </td>
                        <td><span style="color: #2ecc71; font-size: 1.1rem; font-weight: 800;"><?= number_format($sale['price'], 2) ?> €</span></td>
                        <td><?= date('d.m.Y H:i', strtotime($sale['sale_date'])) ?></td>
                        <td style="max-width: 220px; font-size: 0.9rem; line-height: 1.4;">
                            <?= !empty($sale['mesaj']) ? nl2br(htmlspecialchars($sale['mesaj'])) : '<span style="color: #94a3b8; font-style: italic;">Fără mesaj atașat</span>' ?>
                        </td>
                        <td>
                            <span class="status-badge status <?= str_replace(' ', '_', strtolower($sale['status'])) ?>">
                                <?php 
                                    if ($sale['status'] === 'pending') echo 'În așteptare';
                                    elseif ($sale['status'] === 'finalizat') echo 'Vândut';
                                    elseif ($sale['status'] === 'respins') echo 'Respins';
                                    else echo htmlspecialchars($sale['status']);
                                ?>
                            </span>
                        </td>
                        <td>
                            <?php if (strtolower($sale['status']) === 'pending'): ?>
                                <a href="admin_sales.php?action=approba&id=<?= $sale['id'] ?>" 
                                   style="background:#2ecc71; color:#fff; padding:6px 12px; text-decoration:none; border-radius:4px; font-size:0.85rem; font-weight:600; display:inline-block; margin-right: 5px;"
                                   onclick="return confirm('Confirmi încasarea plății pentru <?= htmlspecialchars($sale['marca'] . ' ' . $sale['model']) ?>?')">
                                   Aprobă
                                </a>

                                <a href="admin_sales.php?action=respinge&id=<?= $sale['id'] ?>" 
                                   style="background:#ef4444; color:#fff; padding:6px 12px; text-decoration:none; border-radius:4px; font-size:0.85rem; font-weight:600; display:inline-block; margin-right: 5px;"
                                   onclick="return confirm('Sigur dorești să respingi această cerere?')">
                                   Respinge
                                </a>
                            <?php elseif (strtolower($sale['status']) === 'finalizat'): ?>
                                <a href="../document-vanzare.php?sale_id=<?= $sale['id'] ?>" 
                                   style="background:#ef4444; color:#fff; padding:6px 12px; text-decoration:none; border-radius:4px; font-size:0.85rem; font-weight:600; display:inline-block; margin-right: 5px;" 
                                   target="_blank">
                                   Vezi Acte Vânzare
                                </a>
                            <?php else: ?>
                                <span style="opacity: 0.5; font-style: italic; margin-right: 5px;">Cerere Respinsă</span>
                            <?php endif; ?>

							<a class="delete-btn" href="admin_sales.php?action=sterge&id=<?= $sale['id'] ?>" onclick="return confirm('Sigur dorești să ștergi definitiv această înregistrare din baza de date?')" style="background: #ef4444; color: #fff; padding: 6px 12px; text-decoration: none; border-radius: 4px; font-size: 0.85rem; font-weight: normal; display: inline-block;">🗑</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p style="color: #94a3b8;">Nu s-a găsit nicio cerere de cumpărare înregistrată în sistem.</p>
    <?php endif; ?>
</main>

<script>
function filterSalesTable() {
    const input = document.getElementById('salesSearchInput');
    const filter = input.value.toLowerCase().trim();
    const rows = document.getElementsByClassName('sale-row');

    for (let i = 0; i < rows.length; i++) {
        const textElements = rows[i].getElementsByClassName('searchable-sale');
        let matchFound = false;

        for (let j = 0; j < textElements.length; j++) {
            const txtValue = textElements[j].textContent || textElements[j].innerText;
            if (txtValue.toLowerCase().indexOf(filter) > -1) {
                matchFound = true;
                break;
            }
        }
        rows[i].style.display = matchFound ? "" : "none";
    }
}
</script>

</body>
</html>