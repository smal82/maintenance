<?php
// maintenance-delete.php
require_once 'config.php';

$auth = new Auth();
$auth->requireRole('admin');

$db = Database::getInstance();
$maintenanceId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($maintenanceId === 0) {
    header('Location: ' . BASE_URL . '/maintenances.php');
    exit;
}

try {
    $db->delete('maintenances', 'id = :id', ['id' => $maintenanceId]);
    $_SESSION['success_message'] = 'Manutenzione eliminata con successo';
} catch (Exception $e) {
    $_SESSION['error_message'] = 'Errore durante l\'eliminazione: ' . $e->getMessage();
}

header('Location: ' . BASE_URL . '/maintenances.php');
exit;
?>