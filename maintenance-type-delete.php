<?php
// maintenance-type-delete.php
require_once 'config.php';
$auth = new Auth();
$auth->requireRole('admin');
$db = Database::getInstance();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id) {
    $db->delete('maintenance_types', 'id = :id', ['id' => $id]);
    $_SESSION['success_message'] = 'Tipo eliminato con successo';
}
header('Location: ' . BASE_URL . '/maintenance-types.php');
exit;
?>