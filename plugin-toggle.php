<?php
// plugin-toggle.php
require_once 'config.php';

$auth = new Auth();
$auth->requireRole('admin');

$pluginManager = new PluginManager();
$id = (int)($_GET['id'] ?? 0);
$action = $_GET['action'] ?? '';

if ($id && in_array($action, ['activate', 'deactivate'])) {
    try {
        if ($action === 'activate') {
            $pluginManager->activatePlugin($id);
            $_SESSION['success_message'] = 'Plugin attivato con successo';
        } else {
            $pluginManager->deactivatePlugin($id);
            $_SESSION['success_message'] = 'Plugin disattivato con successo';
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = 'Errore: ' . $e->getMessage();
    }
}

header('Location: ' . BASE_URL . '/plugins.php');
exit;
?>