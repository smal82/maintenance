<?php
// spare-parts.php
require_once 'config.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

$search = $_GET['search'] ?? '';
$lowStock = isset($_GET['low_stock']) ? 1 : 0;

$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(code LIKE :search OR name LIKE :search OR category LIKE :search)";
    $params['search'] = "%{$search}%";
}

if ($lowStock) {
    $where[] = "quantity <= min_quantity";
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$spareParts = $db->fetchAll("
    SELECT * FROM spare_parts
    {$whereClause}
    ORDER BY code
    LIMIT {$perPage} OFFSET {$offset}
", $params);

$total = $db->count('spare_parts', $whereClause ? substr($whereClause, 6) : '', $params);
$totalPages = ceil($total / $perPage);

$success = $_SESSION['success_message'] ?? '';
$error = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

$pageTitle = 'Gestione Ricambi';
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
            <h3>Ricambi</h3>
            <?php if ($auth->hasRole('admin') || $auth->hasRole('technician')): ?>
            <a href="<?php echo BASE_URL; ?>/spare-part-form.php" class="btn btn-primary">
                <i class="fas fa-plus"></i>
                Nuovo Ricambio
            </a>
            <?php endif; ?>
        </div>
        
        <div class="table-filters">
            <div class="table-filters-left">
                <div class="table-search">
                    <i class="fas fa-search"></i>
                    <input type="text" class="form-control" placeholder="Cerca ricambi..." value="<?php echo htmlspecialchars($search); ?>" id="searchInput">
                </div>
                
                <div class="form-check" style="margin-left: 16px;">
                    <input type="checkbox" id="lowStockFilter" class="form-check-input" <?php echo $lowStock ? 'checked' : ''; ?>>
                    <label for="lowStockFilter" class="form-check-label">Solo scorte basse</label>
                </div>
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
                        <th>Codice</th>
                        <th>Nome</th>
                        <th>Categoria</th>
                        <th>Quantità</th>
                        <th>Min. Scorta</th>
                        <th>Prezzo Unitario</th>
                        <th>Fornitore</th>
                        <th style="text-align: center;">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($spareParts)): ?>
                    <tr>
                        <td colspan="8" class="table-empty">
                            <i class="fas fa-cog"></i>
                            <p>Nessun ricambio trovato</p>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($spareParts as $part): ?>
                        <tr <?php echo $part['quantity'] <= $part['min_quantity'] ? 'style="background-color: #fff3cd;"' : ''; ?>>
                            <td data-label="Codice">
                                <strong><?php echo htmlspecialchars($part['code']); ?></strong>
                            </td>
                            <td data-label="Nome"><?php echo htmlspecialchars($part['name']); ?></td>
                            <td data-label="Categoria"><?php echo htmlspecialchars($part['category'] ?? '-'); ?></td>
                            <td data-label="Quantità">
                                <strong><?php echo $part['quantity']; ?></strong>
                                <?php if ($part['quantity'] <= $part['min_quantity']): ?>
                                <i class="fas fa-exclamation-triangle" style="color: #f39c12;" title="Scorta bassa"></i>
                                <?php endif; ?>
                            </td>
                            <td data-label="Min. Scorta"><?php echo $part['min_quantity']; ?></td>
                            <td data-label="Prezzo">€ <?php echo number_format($part['unit_price'] ?? 0, 2, ',', '.'); ?></td>
                            <td data-label="Fornitore"><?php echo htmlspecialchars($part['supplier'] ?? '-'); ?></td>
                            <td data-label="Azioni" style="text-align: center;">
                                <div class="table-actions">
                                    <?php if ($auth->hasRole('admin') || $auth->hasRole('technician')): ?>
                                    <a href="<?php echo BASE_URL; ?>/spare-part-form.php?id=<?php echo $part['id']; ?>" class="table-action-btn" title="Modifica">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php endif; ?>
                                    <?php if ($auth->hasRole('admin')): ?>
                                    <a href="<?php echo BASE_URL; ?>/spare-part-delete.php?id=<?php echo $part['id']; ?>" 
                                       class="table-action-btn danger" 
                                       data-confirm-delete
                                       data-message="Sei sicuro di voler eliminare questo ricambio?"
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
            <span>Mostrando <?php echo count($spareParts); ?> di <?php echo $total; ?> ricambi</span>
            
            <div class="pagination">
                <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $lowStock ? '&low_stock=1' : ''; ?>" class="pagination-item">
                    <i class="fas fa-chevron-left"></i>
                </a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <a href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $lowStock ? '&low_stock=1' : ''; ?>" 
                   class="pagination-item <?php echo $i === $page ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                <a href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $lowStock ? '&low_stock=1' : ''; ?>" class="pagination-item">
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

$('#lowStockFilter').on('change', function() {
    applyFilters();
});

function applyFilters() {
    const search = $('#searchInput').val();
    const lowStock = $('#lowStockFilter').is(':checked');
    
    let url = '?page=1';
    if (search) url += '&search=' + encodeURIComponent(search);
    if (lowStock) url += '&low_stock=1';
    
    window.location.href = url;
}

function resetFilters() {
    window.location.href = '<?php echo BASE_URL; ?>/spare-parts.php';
}
</script>

<?php include 'includes/footer.php'; ?>