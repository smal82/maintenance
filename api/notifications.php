<?php
// api/notifications.php
require_once '../config.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Non autenticato']);
    exit;
}

$db = Database::getInstance();
$userId = $_SESSION['user_id'];

// GET - Retrieve notifications
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $notifications = $db->fetchAll("
        SELECT * FROM notifications
        WHERE user_id = :user_id
        ORDER BY created_at DESC
        LIMIT 20
    ", ['user_id' => $userId]);
    
    $unreadCount = $db->count('notifications', 'user_id = :user_id AND is_read = 0', ['user_id' => $userId]);
    
    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'unread_count' => $unreadCount
    ]);
    exit;
}

// POST - Mark as read / Mark all as read
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if (!$auth->validateCSRFToken($_POST['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'message' => 'Token CSRF non valido']);
        exit;
    }
    
    if ($action === 'mark_read') {
        $notificationId = $_POST['notification_id'] ?? 0;
        
        $db->update(
            'notifications',
            ['is_read' => 1],
            'id = :id AND user_id = :user_id',
            ['id' => $notificationId, 'user_id' => $userId]
        );
        
        echo json_encode(['success' => true, 'message' => 'Notifica letta']);
        exit;
    }
    
    if ($action === 'mark_all_read') {
        $db->update(
            'notifications',
            ['is_read' => 1],
            'user_id = :user_id',
            ['user_id' => $userId]
        );
        
        echo json_encode(['success' => true, 'message' => 'Tutte le notifiche sono state lette']);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Azione non valida']);
?>