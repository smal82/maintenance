<?php
// assets.php
require_once 'config.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Search and filters
$search = $_GET['search'] ?? '';
$categoryFilter = $_GET['category'] ?? '';
$statusFilter = $_GET['status'] ?? '';

// Build query
$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(a.code LIKE :search OR a.name LIKE :search OR a.location LIKE :search)";
    $params['search'] = "%{$search}%";
}

if (!empty($categoryFilter)) {
    $where[] = "a.category_id = :category";
    $params['category'] = $categoryFilter;
}

if (!empty($statusFilter)) {
    $where[] = "a.status = :status";
    $params['status'] = $statusFilter;
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get assets
$assets = $db->fetchAll("
    SELECT a.*, ac.name as category_name, ac.color as category_color,
           u.full_name as created_by_name
    FROM assets a
    LEFT JOIN asset_categories ac ON a.category_id = ac.id
    LEFT JOIN users u ON a.created_by = u.id
    {$whereClause}
    ORDER BY a.created_at DESC
    LIMIT {$perPage} OFFSET {$offset}
", $params);

// Get categories for filter
$categories = $db->fetchAll("SELECT * FROM asset_categories ORDER BY name");

// Get total count
$totalAssets = $db->count('assets a', $whereClause ? substr($whereClause, 6) : '', $params);
$totalPages = ceil($totalAssets / $perPage);

// Handle success/error messages from session
$success = $_SESSION['success_message'] ?? '';
$error = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

$pageTitle = 'Gestione Asset';
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
            <h3>Asset</h3>
            <?php if ($auth->hasPermission('create_asset') || $auth->hasRole('admin')): ?>
            <a href="<?php echo BASE_URL; ?>/asset-form.php" class="btn btn-primary">
                <i class="fas fa-plus"></i>
                Nuovo Asset
            </a>
            <?php endif; ?>
        </div>
        
        <div class="table-filters">
            <div class="table-filters-left">
                <div class="table-search">
                    <i class="fas fa-search"></i>
                    <input type="text" class="form-control" placeholder="Cerca asset..." value="<?php echo htmlspecialchars($search); ?>" id="searchInput">
                </div>
                
                <select class="form-control" id="categoryFilter" style="width: 200px;">
                    <option value="">Tutte le categorie</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>" <?php echo $categoryFilter == $cat['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                
                <select class="form-control" id="statusFilter" style="width: 180px;">
                    <option value="">Tutti gli stati</option>
                    <option value="operational" <?php echo $statusFilter === 'operational' ? 'selected' : ''; ?>>Operativo</option>
                    <option value="maintenance" <?php echo $statusFilter === 'maintenance' ? 'selected' : ''; ?>>Manutenzione</option>
                    <option value="broken" <?php echo $statusFilter === 'broken' ? 'selected' : ''; ?>>Guasto</option>
                    <option value="retired" <?php echo $statusFilter === 'retired' ? 'selected' : ''; ?>>Dismesso</option>
                </select>
            </div>
            
            <div class="table-filters-right">
                <button class="btn btn-outline" onclick="resetFilters()">
                    <i class="fas fa-redo"></i>
                    Reset
                </button>
                <button class="btn btn-outline" data-export-csv="#assetsTable">
                    <i class="fas fa-download"></i>
                    Esporta CSV
                </button>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table" id="assetsTable">
                <thead>
                    <tr>
                        <th>Codice</th>
                        <th>Nome</th>
                        <th>Categoria</th>
                        <th>Posizione</th>
                        <th>Stato</th>
                        <th>Data Acquisto</th>
                        <th style="text-align: center;">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($assets)): ?>
                    <tr>
                        <td colspan="7" class="table-empty">
                            <i class="fas fa-box"></i>
                            <p>Nessun asset trovato</p>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($assets as $asset): ?>
                        <tr>
                            <td data-label="Codice">
                                <strong><?php echo htmlspecialchars($asset['code']); ?></strong>
                            </td>
                            <td data-label="Nome"><?php echo htmlspecialchars($asset['name']); ?></td>
                            <td data-label="Categoria">
                                <?php if ($asset['category_name']): ?>
                                <span class="badge" style="background-color: <?php echo $asset['category_color'] ?? '#3498db'; ?>20; color: <?php echo $asset['category_color'] ?? '#3498db'; ?>; border: 1px solid <?php echo $asset['category_color'] ?? '#3498db'; ?>;">
                                    <?php echo htmlspecialchars($asset['category_name']); ?>
                                </span>
                                <?php else: ?>
                                -
                                <?php endif; ?>
                            </td>
                            <td data-label="Posizione"><?php echo htmlspecialchars($asset['location'] ?? '-'); ?></td>
                            <td data-label="Stato">
                                <div class="table-cell-status">
                                    <span class="status-dot <?php echo $asset['status']; ?>"></span>
                                    <?php
                                    $statusLabels = [
                                        'operational' => 'Operativo',
                                        'maintenance' => 'Manutenzione',
                                        'broken' => 'Guasto',
                                        'retired' => 'Dismesso'
                                    ];
                                    echo $statusLabels[$asset['status']] ?? $asset['status'];
                                    ?>
                                </div>
                            </td>
                            <td data-label="Data Acquisto">
                                <?php echo $asset['purchase_date'] ? date('d/m/Y', strtotime($asset['purchase_date'])) : '-'; ?>
                            </td>
                            <td data-label="Azioni" style="text-align: center;">
                                <div class="table-actions">
                                    <button class="table-action-btn" 
                                            data-show-qr
                                            data-asset-id="<?php echo $asset['id']; ?>"
                                            data-asset-code="<?php echo htmlspecialchars($asset['code']); ?>"
                                            data-asset-name="<?php echo htmlspecialchars($asset['name']); ?>"
                                            title="QR Code">
                                        <i class="fas fa-qrcode"></i>
                                    </button>
                                    <?php if ($auth->hasPermission('edit_asset') || $auth->hasRole('admin')): ?>
                                    <a href="<?php echo BASE_URL; ?>/asset-form.php?id=<?php echo $asset['id']; ?>" class="table-action-btn" title="Modifica">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php endif; ?>
                                    <?php if ($auth->hasRole('admin')): ?>
                                    <a href="<?php echo BASE_URL; ?>/asset-delete.php?id=<?php echo $asset['id']; ?>" 
                                       class="table-action-btn danger" 
                                       data-confirm-delete
                                       data-message="Sei sicuro di voler eliminare l'asset <?php echo htmlspecialchars($asset['name']); ?>?"
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
            <span>Mostrando <?php echo count($assets); ?> di <?php echo $totalAssets; ?> asset</span>
            
            <div class="pagination">
                <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $categoryFilter ? '&category=' . $categoryFilter : ''; ?><?php echo $statusFilter ? '&status=' . $statusFilter : ''; ?>" class="pagination-item">
                    <i class="fas fa-chevron-left"></i>
                </a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <a href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $categoryFilter ? '&category=' . $categoryFilter : ''; ?><?php echo $statusFilter ? '&status=' . $statusFilter : ''; ?>" 
                   class="pagination-item <?php echo $i === $page ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                <a href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $categoryFilter ? '&category=' . $categoryFilter : ''; ?><?php echo $statusFilter ? '&status=' . $statusFilter : ''; ?>" class="pagination-item">
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

$('#categoryFilter, #statusFilter').on('change', function() {
    applyFilters();
});

function applyFilters() {
    const search = $('#searchInput').val();
    const category = $('#categoryFilter').val();
    const status = $('#statusFilter').val();
    
    let url = '?page=1';
    if (search) url += '&search=' + encodeURIComponent(search);
    if (category) url += '&category=' + category;
    if (status) url += '&status=' + status;
    
    window.location.href = url;
}

function resetFilters() {
    window.location.href = '<?php echo BASE_URL; ?>/assets.php';
}
</script>

<?php include 'includes/footer.php'; ?>