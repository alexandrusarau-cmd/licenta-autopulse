<?php
include '../functions.php';
include '../db_connect.php';

if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    header("Location: admin_login.php");
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $message_id = (int)$_GET['id'];

    $stmt = $conn->prepare("DELETE FROM contacte WHERE id = ?");
    $stmt->bind_param("i", $message_id);

    if ($stmt->execute()) {
        echo "<script>alert('Mesajul a fost șters cu succes!'); window.location.href='admin_messages.php';</script>";
    } else {
        echo "<script>alert('Eroare la ștergerea mesajului.'); window.location.href='admin_messages.php';</script>";
    }
    $stmt->close();
    exit;
}

$sql_messages = "SELECT id, nume, email, mesaj, data_trimitere FROM contacte ORDER BY data_trimitere DESC";
$result_messages = $conn->query($sql_messages);

$page_title = "Mesaje Clienti";
include 'admin_header.php';
?>

<main style="padding: 2rem;">
    <h2 style="color:#E74C3C; margin-bottom: 1.5rem;">Inbox Mesaje Contact</h2>

    <?php if ($result_messages && $result_messages->num_rows > 0): ?>
        <table class="rentals-table" style="width: 100%; border-collapse: collapse; background: #fff; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border-radius: 8px; overflow: hidden;">
            <thead>
                <tr style="background: #ef4444; color: white; text-align: left;">
                    <th style="padding: 15px;">Expeditor</th>
                    <th style="padding: 15px;">Mesaj primit</th>
                    <th style="padding: 15px;">Data trimiterii</th>
                    <th style="padding: 15px; text-align: center;">Acțiuni</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($msg = $result_messages->fetch_assoc()): ?>
                    <tr style="border-bottom: 1px solid #cbd5e1; transition: background 0.2s;">
                        <td style="padding: 15px; vertical-align: top; width: 250px;">
                            <span style="font-weight: 700; font-size: 1.05rem; color: #1e293b;"><?= htmlspecialchars($msg['nume']) ?></span><br>
                            <span style="color: #64748b; font-size: 0.9rem;"> <a href="mailto:<?= htmlspecialchars($msg['email']) ?>" style="color: #ef4444; text-decoration: none;"><?= htmlspecialchars($msg['email']) ?></a></span>
                        </td>
                        
                        <td style="padding: 15px; vertical-align: top; line-height: 1.5; color: #334155; font-size: 0.95rem; max-width: 500px; white-space: pre-line;">
                            <?= htmlspecialchars($msg['mesaj']) ?>
                        </td>
                        
                        <td style="padding: 15px; vertical-align: top; width: 150px; color: #64748b; font-size: 0.9rem;">
                            <?= date('d.m.Y H:i', strtotime($msg['data_trimitere'])) ?>
                        </td>
                        
                        <td style="padding: 15px; vertical-align: top; text-align: center; width: 100px;">
                            <a class="delete-btn" href="admin_messages.php?action=delete&id=<?= $msg['id'] ?>" 
                               onclick="return confirm('Sigur dorești să ștergi definitiv acest mesaj?')" 
                               style="background: #ef4444; color: #fff; padding: 6px 12px; text-decoration: none; border-radius: 4px; font-size: 0.85rem; display: inline-block;">
                                Șterge
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div style="background: #fff; padding: 30px; text-align: center; border-radius: 8px; border: 1px solid #cbd5e1; color: #64748b; font-style: italic;">
            Nu s-a primit niciun mesaj prin intermediul formularului de contact.
        </div>
    <?php endif; ?>
</main>

</body>
</html>
<?php
$conn->close();
?>