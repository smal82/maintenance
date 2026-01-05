<?php
// includes/footer.php
?>
</main>
        </div>
    </div>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <!-- JS Files -->
    <script src="<?php echo JS_URL; ?>/utils.js"></script>
    <script src="<?php echo JS_URL; ?>/sidebar.js"></script>
    <script src="<?php echo JS_URL; ?>/notifications.js"></script>
    <script src="<?php echo JS_URL; ?>/charts.js"></script>
    <script src="<?php echo JS_URL; ?>/forms.js"></script>
    <script src="<?php echo JS_URL; ?>/ajax.js"></script>
    <script src="<?php echo JS_URL; ?>/calendar.js"></script>
    <script src="<?php echo JS_URL; ?>/qrcode.js"></script>
    <script src="<?php echo JS_URL; ?>/app.js"></script>
    
    <script>
        // Configurazione globale
        const APP_CONFIG = {
            baseUrl: '<?php echo BASE_URL; ?>',
            userId: <?php echo $_SESSION['user_id'] ?? 'null'; ?>,
            userRole: '<?php echo $_SESSION['role'] ?? ''; ?>',
            csrfToken: '<?php echo $auth->generateCSRFToken(); ?>'
        };
    </script>
</body>
</html>