<?php
// user-delete.php
require_once 'config.php';

$auth = new Auth();
$auth->requireRole('admin');

$db = Database::getInstance();

$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($userId === 0) {
    header('Location: ' . BASE_URL . '/users.php');
    exit;
}

// Prevent deleting yourself
if ($userId == $_SESSION['user_id']) {
    $_SESSION['error_message'] = 'Non puoi eliminare il tuo account';
    header('Location: ' . BASE_URL . '/users.php');
    exit;
}

// Get user
$user = $db->fetchOne("SELECT * FROM users WHERE id = :id", ['id' => $userId]);

if (!$user) {
    header('Location: ' . BASE_URL . '/users.php');
    exit;
}

try {
    // Delete user avatar if exists
    if (!empty($user['avatar'])) {
        $avatarPath = UPLOAD_PATH . '/avatars/' . $user['avatar'];
        if (file_exists($avatarPath)) {
            unlink($avatarPath);
        }
    }
    
    // Delete user
    $db->delete('users', 'id = :id', ['id' => $userId]);
    
    $_SESSION['success_message'] = 'Utente eliminato con successo';
} catch (Exception $e) {
    $_SESSION['error_message'] = 'Errore durante l\'eliminazione: ' . $e->getMessage();
}

header('Location: ' . BASE_URL . '/users.php');
exit;
?>