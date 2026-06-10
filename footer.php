<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<footer class="footer">
    <div class="footer-content">
        <p>© <?= date("Y") ?> AutoPulse Dealer Auto. Toate drepturile rezervate.</p>
    </div>
    <a href="#hero" class="scroll-to-top">↑ Sus</a>
</footer>

<script>
function toggleMenu() {
    document.querySelector('.navbar').classList.toggle('active');
}
</script>