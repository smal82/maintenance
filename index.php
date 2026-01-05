<?php
// index.php
require_once 'config.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$user = $auth->getCurrentUser();

// Statistiche per dashboard
$stats = [
    'total_assets' => $db->count('assets'),
    'active_maintenances' => $db->count('maintenances', "status IN ('scheduled', 'in_progress')"),
    'pending_maintenances' => $db->count('maintenances', "status = 'scheduled' AND scheduled_date <= DATE_ADD(NOW(), INTERVAL 7 DAY)"),
    'critical_maintenances' => $db->count('maintenances', "priority = 'critical' AND status != 'completed'")
];

// Manutenzioni recenti
$recentMaintenances = $db->fetchAll("
    SELECT m.*, a.name as asset_name, a.code as asset_code, 
           u.full_name as technician_name, mt.name as type_name, mt.color as type_color
    FROM maintenances m
    LEFT JOIN assets a ON m.asset_id = a.id
    LEFT JOIN users u ON m.assigned_to = u.id
    LEFT JOIN maintenance_types mt ON m.type_id = mt.id
    ORDER BY m.created_at DESC
    LIMIT 10
");

// Asset per categoria
$assetsByCategory = $db->fetchAll("
    SELECT ac.name, ac.color, COUNT(a.id) as total
    FROM asset_categories ac
    LEFT JOIN assets a ON ac.id = a.category_id
    GROUP BY ac.id
    ORDER BY total DESC
");

// Manutenzioni per mese (ultimi 6 mesi)
$maintenancesByMonth = $db->fetchAll("
    SELECT 
        DATE_FORMAT(scheduled_date, '%Y-%m') as month,
        COUNT(*) as total,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
    FROM maintenances
    WHERE scheduled_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY month
    ORDER BY month
");

$pageTitle = 'Dashboard';
include 'includes/header.php';
?>

<div class="dashboard-grid">
    <!-- Stats Cards -->
    <div class="stats-cards">
        <div class="stat-card stat-primary">
            <div class="stat-icon">
                <i class="fas fa-box"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $stats['total_assets']; ?></div>
                <div class="stat-label">Asset Totali</div>
            </div>
        </div>
        
        <div class="stat-card stat-warning">
            <div class="stat-icon">
                <i class="fas fa-wrench"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $stats['active_maintenances']; ?></div>
                <div class="stat-label">Manutenzioni Attive</div>
            </div>
        </div>
        
        <div class="stat-card stat-info">
            <div class="stat-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $stats['pending_maintenances']; ?></div>
                <div class="stat-label">In Scadenza (7gg)</div>
            </div>
        </div>
        
        <div class="stat-card stat-danger">
            <div class="stat-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $stats['critical_maintenances']; ?></div>
                <div class="stat-label">Priorità Critica</div>
            </div>
        </div>
    </div>
    
    <!-- Charts Row -->
    <div class="charts-row">
        <div class="card chart-card">
            <div class="card-header">
                <h3>Manutenzioni per Mese</h3>
            </div>
            <div class="card-body">
                <canvas id="maintenancesChart"></canvas>
            </div>
        </div>
        
        <div class="card chart-card">
            <div class="card-header">
                <h3>Asset per Categoria</h3>
            </div>
            <div class="card-body">
                <canvas id="assetsCategoryChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Recent Maintenances -->
    <div class="card recent-maintenances">
        <div class="card-header">
            <h3>Manutenzioni Recenti</h3>
            <a href="maintenances.php" class="btn btn-sm btn-primary">Vedi Tutte</a>
        </div>
        <div class="card-body">
            <div class="timeline">
                <?php foreach ($recentMaintenances as $maintenance): ?>
                <div class="timeline-item">
                    <div class="timeline-marker" style="background-color: <?php echo $maintenance['type_color'] ?? '#3498db'; ?>"></div>
                    <div class="timeline-content">
                        <div class="timeline-header">
                            <h4><a href="maintenance-form.php?id=<?php echo $maintenance['id']; ?>"><?php echo htmlspecialchars($maintenance['title']); ?></a></h4>
                            <span class="badge badge-<?php echo $maintenance['priority']; ?>">
                                <?php echo ucfirst($maintenance['priority']); ?>
                            </span>
                            <span class="badge badge-<?php echo $maintenance['status']; ?>">
                                <?php echo ucfirst($maintenance['status']); ?>
                            </span>
                        </div>
                        <div class="timeline-meta">
                            <span><i class="fas fa-box"></i> <?php echo htmlspecialchars($maintenance['asset_name']); ?></span>
                            <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($maintenance['technician_name'] ?? 'Non assegnato'); ?></span>
                            <span><i class="fas fa-calendar"></i> <?php echo date('d/m/Y H:i', strtotime($maintenance['scheduled_date'])); ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Dati per i grafici
const maintenancesData = <?php echo json_encode($maintenancesByMonth); ?>;
const assetsCategoryData = <?php echo json_encode($assetsByCategory); ?>;
</script>

<?php include 'includes/footer.php'; ?>