<?php
// spare-part-delete.php
require_once 'config.php';
$auth = new Auth();
$auth->requireRole('admin');
$db = Database::getInstance();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id) {
    $db->delete('spare_parts', 'id = :id', ['id' => $id]);
    $_SESSION['success_message'] = 'Ricambio eliminato con successo';
}
header('Location: ' . BASE_URL . '/spare-parts.php');
exit;
?>