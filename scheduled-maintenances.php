<?php
// scheduled-maintenances.php
require_once 'config.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

$search = $_GET['search'] ?? '';
$activeFilter = $_GET['active'] ?? '';

$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(sm.title LIKE :search OR a.name LIKE :search OR a.code LIKE :search)";
    $params['search'] = "%{$search}%";
}

if ($activeFilter !== '') {
    $where[] = "sm.is_active = :active";
    $params['active'] = $activeFilter;
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$scheduledMaintenances = $db->fetchAll("
    SELECT sm.*, a.name as asset_name, a.code as asset_code,
           u.full_name as technician_name,
           mt.name as type_name, mt.color as type_color
    FROM scheduled_maintenances sm
    LEFT JOIN assets a ON sm.asset_id = a.id
    LEFT JOIN users u ON sm.assigned_to = u.id
    LEFT JOIN maintenance_types mt ON sm.type_id = mt.id
    {$whereClause}
    ORDER BY sm.next_execution ASC
    LIMIT {$perPage} OFFSET {$offset}
", $params);

$total = $db->count('scheduled_maintenances sm LEFT JOIN assets a ON sm.asset_id = a.id', 
    $whereClause ? substr($whereClause, 6) : '', $params);
$totalPages = ceil($total / $perPage);

$success = $_SESSION['success_message'] ?? '';
$error = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

$pageTitle = 'Manutenzioni Programmate';
include 'includes/header.php';
?>

<div class="container-fluid">
    <?php if ($success): ?>
    <div class="alert alert-success" data-auto-hide="5000">
        <i class="fas fa-check-circle"></i>
        <span><?php echo htmlspecialchars($success); ?></span>
    </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            <h3>Manutenzioni Programmate</h3>
            <?php if ($auth->hasRole('admin') || $auth->hasRole('technician')): ?>
            <a href="<?php echo BASE_URL; ?>/scheduled-maintenance-form.php" class="btn btn-primary">
                <i class="fas fa-plus"></i>
                Nuova Programmazione
            </a>
            <?php endif; ?>
        </div>
        
        <div class="table-filters">
            <div class="table-filters-left">
                <div class="table-search">
                    <i class="fas fa-search"></i>
                    <input type="text" class="form-control" placeholder="Cerca..." value="<?php echo htmlspecialchars($search); ?>" id="searchInput">
                </div>
                
                <select class="form-control" id="activeFilter" style="width: 180px;">
                    <option value="">Tutti gli stati</option>
                    <option value="1" <?php echo $activeFilter === '1' ? 'selected' : ''; ?>>Attive</option>
                    <option value="0" <?php echo $activeFilter === '0' ? 'selected' : ''; ?>>Disattivate</option>
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
                        <th>Frequenza</th>
                        <th>Prossima Esecuzione</th>
                        <th>Assegnato a</th>
                        <th>Stato</th>
                        <th style="text-align: center;">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($scheduledMaintenances)): ?>
                    <tr>
                        <td colspan="8" class="table-empty">
                            <i class="fas fa-clock"></i>
                            <p>Nessuna manutenzione programmata</p>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($scheduledMaintenances as $sm): ?>
                        <tr>
                            <td data-label="Titolo">
                                <strong><?php echo htmlspecialchars($sm['title']); ?></strong>
                            </td>
                            <td data-label="Asset">
                                <?php echo htmlspecialchars($sm['asset_code'] . ' - ' . $sm['asset_name']); ?>
                            </td>
                            <td data-label="Tipo">
                                <?php if ($sm['type_name']): ?>
                                <span class="badge" style="background-color: <?php echo $sm['type_color'] ?? '#3498db'; ?>20; color: <?php echo $sm['type_color'] ?? '#3498db'; ?>; border: 1px solid <?php echo $sm['type_color'] ?? '#3498db'; ?>;">
                                    <?php echo htmlspecialchars($sm['type_name']); ?>
                                </span>
                                <?php else: ?>
                                -
                                <?php endif; ?>
                            </td>
                            <td data-label="Frequenza">
                                <?php
                                $freqLabels = [
                                    'daily' => 'Giornaliera',
                                    'weekly' => 'Settimanale',
                                    'monthly' => 'Mensile',
                                    'quarterly' => 'Trimestrale',
                                    'yearly' => 'Annuale'
                                ];
                                echo $freqLabels[$sm['frequency_type']] ?? $sm['frequency_type'];
                                if ($sm['frequency_value'] > 1) {
                                    echo ' (ogni ' . $sm['frequency_value'] . ')';
                                }
                                ?>
                            </td>
                            <td data-label="Prossima">
                                <?php echo $sm['next_execution'] ? date('d/m/Y', strtotime($sm['next_execution'])) : '-'; ?>
                            </td>
                            <td data-label="Assegnato">
                                <?php echo htmlspecialchars($sm['technician_name'] ?? 'Non assegnato'); ?>
                            </td>
                            <td data-label="Stato">
                                <?php if ($sm['is_active']): ?>
                                    <span class="badge badge-success">Attiva</span>
                                <?php else: ?>
                                    <span class="badge" style="background-color: #ecf0f1; color: #7f8c8d;">Disattivata</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Azioni" style="text-align: center;">
                                <div class="table-actions">
                                    <?php if ($auth->hasRole('admin') || $auth->hasRole('technician')): ?>
                                    <a href="<?php echo BASE_URL; ?>/scheduled-maintenance-form.php?id=<?php echo $sm['id']; ?>" class="table-action-btn" title="Modifica">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php endif; ?>
                                    <?php if ($auth->hasRole('admin')): ?>
                                    <a href="<?php echo BASE_URL; ?>/scheduled-maintenance-delete.php?id=<?php echo $sm['id']; ?>" 
                                       class="table-action-btn danger" 
                                       data-confirm-delete
                                       data-message="Sei sicuro di voler eliminare questa programmazione?"
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
            <span>Mostrando <?php echo count($scheduledMaintenances); ?> di <?php echo $total; ?> programmazioni</span>
            
            <div class="pagination">
                <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $activeFilter !== '' ? '&active=' . $activeFilter : ''; ?>" class="pagination-item">
                    <i class="fas fa-chevron-left"></i>
                </a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <a href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $activeFilter !== '' ? '&active=' . $activeFilter : ''; ?>" 
                   class="pagination-item <?php echo $i === $page ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                <a href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $activeFilter !== '' ? '&active=' . $activeFilter : ''; ?>" class="pagination-item">
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

$('#activeFilter').on('change', function() {
    applyFilters();
});

function applyFilters() {
    const search = $('#searchInput').val();
    const active = $('#activeFilter').val();
    
    let url = '?page=1';
    if (search) url += '&search=' + encodeURIComponent(search);
    if (active !== '') url += '&active=' + active;
    
    window.location.href = url;
}

function resetFilters() {
    window.location.href = '<?php echo BASE_URL; ?>/scheduled-maintenances.php';
}
</script>

<?php include 'includes/footer.php'; ?>