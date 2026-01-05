<?php
// api/assets.php
require_once '../config.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Non autenticato']);
    exit;
}

$db = Database::getInstance();

// GET - Retrieve assets
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $search = $_GET['search'] ?? '';
    $category = $_GET['category'] ?? '';
    $status = $_GET['status'] ?? '';
    
    $where = [];
    $params = [];
    
    if (!empty($search)) {
        $where[] = "(code LIKE :search OR name LIKE :search)";
        $params['search'] = "%{$search}%";
    }
    
    if (!empty($category)) {
        $where[] = "category_id = :category";
        $params['category'] = $category;
    }
    
    if (!empty($status)) {
        $where[] = "status = :status";
        $params['status'] = $status;
    }
    
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    $assets = $db->fetchAll("
        SELECT a.*, ac.name as category_name 
        FROM assets a
        LEFT JOIN asset_categories ac ON a.category_id = ac.id
        {$whereClause}
        ORDER BY a.code
        LIMIT 100
    ", $params);
    
    echo json_encode([
        'success' => true,
        'assets' => $assets
    ]);
    exit;
}

// POST - Create asset
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$auth->validateCSRFToken($input['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'message' => 'Token CSRF non valido']);
        exit;
    }
    
    $data = [
        'code' => $input['code'] ?? '',
        'name' => $input['name'] ?? '',
        'category_id' => $input['category_id'] ?? null,
        'description' => $input['description'] ?? '',
        'location' => $input['location'] ?? '',
        'status' => $input['status'] ?? 'operational',
        'created_by' => $_SESSION['user_id']
    ];
    
    try {
        $assetId = $db->insert('assets', $data);
        echo json_encode([
            'success' => true,
            'message' => 'Asset creato con successo',
            'asset_id' => $assetId
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