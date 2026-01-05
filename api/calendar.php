<?php
// api/calendar.php
require_once '../config.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Non autenticato']);
    exit;
}

$db = Database::getInstance();

// GET - Retrieve events for calendar
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $year = $_GET['year'] ?? date('Y');
    $month = $_GET['month'] ?? date('m');
    
    $startDate = "$year-$month-01";
    $endDate = date('Y-m-t', strtotime($startDate));
    
    $events = $db->fetchAll("
        SELECT m.id, m.title, m.scheduled_date, m.priority, m.status,
               a.name as asset_name, a.code as asset_code,
               mt.name as type_name, mt.color
        FROM maintenances m
        LEFT JOIN assets a ON m.asset_id = a.id
        LEFT JOIN maintenance_types mt ON m.type_id = mt.id
        WHERE DATE(m.scheduled_date) BETWEEN :start AND :end
        AND m.status != 'cancelled'
        ORDER BY m.scheduled_date
    ", ['start' => $startDate, 'end' => $endDate]);
    
    // Format events for calendar
    $formatted = array_map(function($event) {
        return [
            'id' => $event['id'],
            'title' => $event['title'],
            'date' => date('Y-m-d', strtotime($event['scheduled_date'])),
            'time' => date('H:i', strtotime($event['scheduled_date'])),
            'color' => $event['color'] ?? '#3498db',
            'priority' => $event['priority'],
            'status' => $event['status'],
            'asset_name' => $event['asset_name'],
            'asset_code' => $event['asset_code']
        ];
    }, $events);
    
    echo json_encode([
        'success' => true,
        'events' => $formatted,
        'month' => $month,
        'year' => $year
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Metodo non supportato']);
?>