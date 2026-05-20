<?php
// config.php - Configurazione del sistema

// Configurazione Database
define('DB_HOST', 'localhost');
define('DB_NAME', '');
define('DB_USER', '');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Configurazione Applicazione
define('SITE_NAME', 'Maintenance Pro');
define('BASE_URL', '');
define('TIMEZONE', 'Europe/Rome');
date_default_timezone_set(TIMEZONE);

// Percorsi
define('ROOT_PATH', __DIR__);
define('UPLOAD_PATH', ROOT_PATH . '/uploads');
define('PLUGIN_PATH', ROOT_PATH . '/plugins');
define('ASSETS_PATH', ROOT_PATH . '/assets');

// URL Assets
define('CSS_URL', BASE_URL . '/assets/css');
define('JS_URL', BASE_URL . '/assets/js');
define('IMG_URL', BASE_URL . '/assets/img');

// Sicurezza
define('SESSION_NAME', 'MAINTENANCE_SESSION');
define('CSRF_TOKEN_NAME', '');
define('PASSWORD_SALT', '');

// Upload
define('MAX_FILE_SIZE', 10485760); // 10MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx']);

// Impostazioni QR Code
define('QR_CODE_SIZE', 300);
define('QR_CODE_PATH', UPLOAD_PATH . '/qrcodes');

// Plugin
define('PLUGIN_ENABLED', true);

// Errori (development: E_ALL, production: 0)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Autoload semplice per le classi
spl_autoload_register(function ($class) {
    $paths = [
        ROOT_PATH . '/classes/' . $class . '.php',
        ROOT_PATH . '/includes/' . $class . '.php',
    ];
    
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

// Inizializza sessione
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}
?>
