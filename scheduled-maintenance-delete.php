<?php
// scheduled-maintenance-delete.php
require_once 'config.php';
$auth = new Auth();
$auth->requireRole('admin');
$db = Database::getInstance();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id) {
    $db->delete('scheduled_maintenances', 'id = :id', ['id' => $id]);
    $_SESSION['success_message'] = 'Manutenzione programmata eliminata';
}
header('Location: ' . BASE_URL . '/scheduled-maintenances.php');
exit;
?>