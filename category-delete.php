<?php
// category-delete.php
require_once 'config.php';

$auth = new Auth();
$auth->requireRole('admin');

$db = Database::getInstance();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id) {
    try {
        $db->delete('asset_categories', 'id = :id', ['id' => $id]);
        $_SESSION['success_message'] = 'Categoria eliminata con successo';
    } catch (Exception $e) {
        $_SESSION['error_message'] = 'Errore durante l\'eliminazione: ' . $e->getMessage();
    }
}

header('Location: ' . BASE_URL . '/categories.php');
exit;
?>