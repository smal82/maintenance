<?php
// maintenances.php
require_once 'config.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Filters
$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$priorityFilter = $_GET['priority'] ?? '';
$assetFilter = $_GET['asset'] ?? '';

// Build query
$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(m.title LIKE :search OR a.name LIKE :search OR a.code LIKE :search)";
    $params['search'] = "%{$search}%";
}

if (!empty($statusFilter)) {
    $where[] = "m.status = :status";
    $params['status'] = $statusFilter;
}

if (!empty($priorityFilter)) {
    $where[] = "m.priority = :priority";
    $params['priority'] = $priorityFilter;
}

if (!empty($assetFilter)) {
    $where[] = "m.asset_id = :asset";
    $params['asset'] = $assetFilter;
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get maintenances
$maintenances = $db->fetchAll("
    SELECT m.*, a.name as asset_name, a.code as asset_code,
           u.full_name as technician_name,
           mt.name as type_name, mt.color as type_color,
           creator.full_name as created_by_name
    FROM maintenances m
    LEFT JOIN assets a ON m.asset_id = a.id
    LEFT JOIN users u ON m.assigned_to = u.id
    LEFT JOIN maintenance_types mt ON m.type_id = mt.id
    LEFT JOIN users creator ON m.created_by = creator.id
    {$whereClause}
    ORDER BY m.scheduled_date DESC
    LIMIT {$perPage} OFFSET {$offset}
", $params);

// Get assets for filter
$assets = $db->fetchAll("SELECT id, code, name FROM assets ORDER BY code");

// Get total count
$totalMaintenances = $db->count('maintenances m LEFT JOIN assets a ON m.asset_id = a.id', 
    $whereClause ? substr($whereClause, 6) : '', $params);
$totalPages = ceil($totalMaintenances / $perPage);

$success = $_SESSION['success_message'] ?? '';
$error = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

$pageTitle = 'Gestione Manutenzioni';
include 'includes/header.php';
?>

<div class="container-fluid">
    <?php if ($success): ?>
    <div class="alert alert-success" data-auto-hide="5000">
        <i class="fas fa-check-circle"></i>
        <span><?php echo htmlspecialchars($success); ?></span>
    </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i>
        <span><?php echo htmlspecialchars($error); ?></span>
    </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            <h3>Manutenzioni</h3>
            <?php if ($auth->hasPermission('create_maintenance') || $auth->hasRole('admin')): ?>
            <a href="<?php echo BASE_URL; ?>/maintenance-form.php" class="btn btn-primary">
                <i class="fas fa-plus"></i>
                Nuova Manutenzione
            </a>
            <?php endif; ?>
        </div>
        
        <div class="table-filters">
            <div class="table-filters-left">
                <div class="table-search">
                    <i class="fas fa-search"></i>
                    <input type="text" class="form-control" placeholder="Cerca..." value="<?php echo htmlspecialchars($search); ?>" id="searchInput">
                </div>
                
                <select class="form-control" id="statusFilter" style="width: 180px;">
                    <option value="">Tutti gli stati</option>
                    <option value="scheduled" <?php echo $statusFilter === 'scheduled' ? 'selected' : ''; ?>>Programmata</option>
                    <option value="in_progress" <?php echo $statusFilter === 'in_progress' ? 'selected' : ''; ?>>In Corso</option>
                    <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completata</option>
                    <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Annullata</option>
                </select>
                
                <select class="form-control" id="priorityFilter" style="width: 150px;">
                    <option value="">Tutte le priorità</option>
                    <option value="low" <?php echo $priorityFilter === 'low' ? 'selected' : ''; ?>>Bassa</option>
                    <option value="medium" <?php echo $priorityFilter === 'medium' ? 'selected' : ''; ?>>Media</option>
                    <option value="high" <?php echo $priorityFilter === 'high' ? 'selected' : ''; ?>>Alta</option>
                    <option value="critical" <?php echo $priorityFilter === 'critical' ? 'selected' : ''; ?>>Critica</option>
                </select>
                
                <select class="form-control" id="assetFilter" style="width: 200px;">
                    <option value="">Tutti gli asset</option>
                    <?php foreach ($assets as $asset): ?>
                    <option value="<?php echo $asset['id']; ?>" <?php echo $assetFilter == $asset['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($asset['code'] . ' - ' . $asset['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="table-filters-right">
                <button class="btn btn-outline" onclick="resetFilters()">
                    <i class="fas fa-redo"></i>
                    Reset
                </button>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Titolo</th>
                        <th>Asset</th>
                        <th>Tipo</th>
                        <th>Priorità</th>
                        <th>Stato</th>
                        <th>Data Programmata</th>
                        <th>Assegnato a</th>
                        <th style="text-align: center;">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($maintenances)): ?>
                    <tr>
                        <td colspan="8" class="table-empty">
                            <i class="fas fa-wrench"></i>
                            <p>Nessuna manutenzione trovata</p>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($maintenances as $maintenance): ?>
                        <tr>
                            <td data-label="Titolo">
                                <strong><?php echo htmlspecialchars($maintenance['title']); ?></strong>
                            </td>
                            <td data-label="Asset">
                                <?php echo htmlspecialchars($maintenance['asset_code'] . ' - ' . $maintenance['asset_name']); ?>
                            </td>
                            <td data-label="Tipo">
                                <?php if ($maintenance['type_name']): ?>
                                <span class="badge" style="background-color: <?php echo $maintenance['type_color'] ?? '#3498db'; ?>20; color: <?php echo $maintenance['type_color'] ?? '#3498db'; ?>; border: 1px solid <?php echo $maintenance['type_color'] ?? '#3498db'; ?>;">
                                    <?php echo htmlspecialchars($maintenance['type_name']); ?>
                                </span>
                                <?php else: ?>
                                -
                                <?php endif; ?>
                            </td>
                            <td data-label="Priorità">
                                <span class="badge badge-<?php echo $maintenance['priority']; ?>">
                                    <?php echo ucfirst($maintenance['priority']); ?>
                                </span>
                            </td>
                            <td data-label="Stato">
                                <span class="badge badge-<?php echo $maintenance['status']; ?>">
                                    <?php
                                    $statusLabels = [
                                        'scheduled' => 'Programmata',
                                        'in_progress' => 'In Corso',
                                        'completed' => 'Completata',
                                        'cancelled' => 'Annullata'
                                    ];
                                    echo $statusLabels[$maintenance['status']] ?? $maintenance['status'];
                                    ?>
                                </span>
                            </td>
                            <td data-label="Data">
                                <?php echo date('d/m/Y H:i', strtotime($maintenance['scheduled_date'])); ?>
                            </td>
                            <td data-label="Assegnato">
                                <?php echo htmlspecialchars($maintenance['technician_name'] ?? 'Non assegnato'); ?>
                            </td>
                            <td data-label="Azioni" style="text-align: center;">
                                <div class="table-actions">
                                    <?php if ($auth->hasPermission('edit_maintenance') || $auth->hasRole('admin')): ?>
                                    <a href="<?php echo BASE_URL; ?>/maintenance-form.php?id=<?php echo $maintenance['id']; ?>" class="table-action-btn" title="Modifica">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php endif; ?>
                                    <?php if ($auth->hasRole('admin')): ?>
                                    <a href="<?php echo BASE_URL; ?>/maintenance-delete.php?id=<?php echo $maintenance['id']; ?>" 
                                       class="table-action-btn danger" 
                                       data-confirm-delete
                                       data-message="Sei sicuro di voler eliminare questa manutenzione?"
                                       title="Elimina">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($totalPages > 1): ?>
        <div class="table-info">
            <span>Mostrando <?php echo count($maintenances); ?> di <?php echo $totalMaintenances; ?> manutenzioni</span>
            
            <div class="pagination">
                <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $statusFilter ? '&status=' . $statusFilter : ''; ?><?php echo $priorityFilter ? '&priority=' . $priorityFilter : ''; ?><?php echo $assetFilter ? '&asset=' . $assetFilter : ''; ?>" class="pagination-item">
                    <i class="fas fa-chevron-left"></i>
                </a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <a href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $statusFilter ? '&status=' . $statusFilter : ''; ?><?php echo $priorityFilter ? '&priority=' . $priorityFilter : ''; ?><?php echo $assetFilter ? '&asset=' . $assetFilter : ''; ?>" 
                   class="pagination-item <?php echo $i === $page ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                <a href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $statusFilter ? '&status=' . $statusFilter : ''; ?><?php echo $priorityFilter ? '&priority=' . $priorityFilter : ''; ?><?php echo $assetFilter ? '&asset=' . $assetFilter : ''; ?>" class="pagination-item">
                    <i class="fas fa-chevron-right"></i>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
let searchTimeout;

$('#searchInput').on('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 500);
});

$('#statusFilter, #priorityFilter, #assetFilter').on('change', function() {
    applyFilters();
});

function applyFilters() {
    const search = $('#searchInput').val();
    const status = $('#statusFilter').val();
    const priority = $('#priorityFilter').val();
    const asset = $('#assetFilter').val();
    
    let url = '?page=1';
    if (search) url += '&search=' + encodeURIComponent(search);
    if (status) url += '&status=' + status;
    if (priority) url += '&priority=' + priority;
    if (asset) url += '&asset=' + asset;
    
    window.location.href = url;
}

function resetFilters() {
    window.location.href = '<?php echo BASE_URL; ?>/maintenances.php';
}
</script>

<?php include 'includes/footer.php'; ?>