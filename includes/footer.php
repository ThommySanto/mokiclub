<?php
require_once dirname(__DIR__) . '/security_headers.php';
?>
<footer class="site-footer">
    <p>&copy; <?php echo date("Y"); ?> Moki Club Numana - Tutti i diritti riservati</p>
</footer>

<script>
    // JS per i Toast Notification (Snackbar) auto-distruttive
    document.addEventListener("DOMContentLoaded", () => {
        const alerts = document.querySelectorAll('.success-message, .error-message');
        alerts.forEach(alert => {
            // Dopo 4 secondi nascondi il toast fluidamente
            setTimeout(() => {
                alert.classList.add('toast-hiding');
                // Dopo mezzo secondo in cui sfuma, distruggi il div
                setTimeout(() => alert.remove(), 500);
        });
    });

    // JS per Flatpickr (Calendario Moderno)
    document.addEventListener("DOMContentLoaded", () => {
        flatpickr("input[type='date']", {
            locale: "it",
            dateFormat: "Y-m-d",
            allowInput: true
        });
    });
</script>
</body>

</html>