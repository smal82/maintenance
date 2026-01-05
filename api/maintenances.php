<?php
// api/maintenances.php
require_once '../config.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Non autenticato']);
    exit;
}

$db = Database::getInstance();

// GET - Retrieve maintenances
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $asset_id = $_GET['asset_id'] ?? '';
    $status = $_GET['status'] ?? '';
    $date_from = $_GET['date_from'] ?? '';
    $date_to = $_GET['date_to'] ?? '';
    
    $where = [];
    $params = [];
    
    if (!empty($asset_id)) {
        $where[] = "m.asset_id = :asset_id";
        $params['asset_id'] = $asset_id;
    }
    
    if (!empty($status)) {
        $where[] = "m.status = :status";
        $params['status'] = $status;
    }
    
    if (!empty($date_from)) {
        $where[] = "DATE(m.scheduled_date) >= :date_from";
        $params['date_from'] = $date_from;
    }
    
    if (!empty($date_to)) {
        $where[] = "DATE(m.scheduled_date) <= :date_to";
        $params['date_to'] = $date_to;
    }
    
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    $maintenances = $db->fetchAll("
        SELECT m.*, 
               a.name as asset_name, 
               a.code as asset_code,
               u.full_name as technician_name,
               mt.name as type_name
        FROM maintenances m
        LEFT JOIN assets a ON m.asset_id = a.id
        LEFT JOIN users u ON m.assigned_to = u.id
        LEFT JOIN maintenance_types mt ON m.type_id = mt.id
        {$whereClause}
        ORDER BY m.scheduled_date DESC
        LIMIT 100
    ", $params);
    
    echo json_encode([
        'success' => true,
        'maintenances' => $maintenances
    ]);
    exit;
}

// POST - Create maintenance
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$auth->validateCSRFToken($input['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'message' => 'Token CSRF non valido']);
        exit;
    }
    
    $data = [
        'asset_id' => $input['asset_id'] ?? 0,
        'title' => $input['title'] ?? '',
        'description' => $input['description'] ?? '',
        'priority' => $input['priority'] ?? 'medium',
        'status' => $input['status'] ?? 'scheduled',
        'scheduled_date' => $input['scheduled_date'] ?? '',
        'assigned_to' => $input['assigned_to'] ?? null,
        'created_by' => $_SESSION['user_id']
    ];
    
    try {
        $maintenanceId = $db->insert('maintenances', $data);
        echo json_encode([
            'success' => true,
            'message' => 'Manutenzione creata con successo',
            'maintenance_id' => $maintenanceId
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Errore: ' . $e->getMessage()
        ]);
    }
    exit;
}

// PUT - Update maintenance
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$auth->validateCSRFToken($input['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'message' => 'Token CSRF non valido']);
        exit;
    }
    
    $id = $input['id'] ?? 0;
    
    $data = [
        'title' => $input['title'] ?? '',
        'description' => $input['description'] ?? '',
        'priority' => $input['priority'] ?? 'medium',
        'status' => $input['status'] ?? 'scheduled',
        'scheduled_date' => $input['scheduled_date'] ?? '',
        'assigned_to' => $input['assigned_to'] ?? null
    ];
    
    try {
        $db->update('maintenances', $data, 'id = :id', ['id' => $id]);
        echo json_encode([
            'success' => true,
            'message' => 'Manutenzione aggiornata con successo'
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Errore: ' . $e->getMessage()
        ]);
    }
    exit;
}

// DELETE - Delete maintenance
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    parse_str(file_get_contents('php://input'), $input);
    
    if (!$auth->hasRole('admin')) {
        echo json_encode(['success' => false, 'message' => 'Permesso negato']);
        exit;
    }
    
    $id = $input['id'] ?? 0;
    
    try {
        $db->delete('maintenances', 'id = :id', ['id' => $id]);
        echo json_encode([
            'success' => true,
            'message' => 'Manutenzione eliminata con successo'
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Errore: ' . $e->getMessage()
        ]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Metodo non supportato']);
?>