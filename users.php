<?php
// users.php
require_once 'config.php';

$auth = new Auth();
$auth->requireRole('admin');

$db = Database::getInstance();

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Search and filters
$search = $_GET['search'] ?? '';
$roleFilter = $_GET['role'] ?? '';

// Build query
$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(username LIKE :search OR email LIKE :search OR full_name LIKE :search)";
    $params['search'] = "%{$search}%";
}

if (!empty($roleFilter)) {
    $where[] = "role = :role";
    $params['role'] = $roleFilter;
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get users
$users = $db->fetchAll("
    SELECT * FROM users
    {$whereClause}
    ORDER BY created_at DESC
    LIMIT {$perPage} OFFSET {$offset}
", $params);

// Get total count
$totalUsers = $db->count('users', $whereClause ? substr($whereClause, 6) : '', $params);
$totalPages = ceil($totalUsers / $perPage);

$pageTitle = 'Gestione Utenti';
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h3>Utenti</h3>
            <a href="<?php echo BASE_URL; ?>/user-form.php" class="btn btn-primary">
                <i class="fas fa-plus"></i>
                Nuovo Utente
            </a>
        </div>
        
        <div class="table-filters">
            <div class="table-filters-left">
                <div class="table-search">
                    <i class="fas fa-search"></i>
                    <input type="text" class="form-control" placeholder="Cerca utenti..." value="<?php echo htmlspecialchars($search); ?>" id="searchInput">
                </div>
                
                <select class="form-control" id="roleFilter" style="width: 200px;">
                    <option value="">Tutti i ruoli</option>
                    <option value="admin" <?php echo $roleFilter === 'admin' ? 'selected' : ''; ?>>Amministratore</option>
                    <option value="technician" <?php echo $roleFilter === 'technician' ? 'selected' : ''; ?>>Tecnico</option>
                    <option value="viewer" <?php echo $roleFilter === 'viewer' ? 'selected' : ''; ?>>Visualizzatore</option>
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
                        <th>Utente</th>
                        <th>Email</th>
                        <th>Ruolo</th>
                        <th>Telefono</th>
                        <th>Stato</th>
                        <th>Data Creazione</th>
                        <th style="text-align: center;">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="7" class="table-empty">
                            <i class="fas fa-users"></i>
                            <p>Nessun utente trovato</p>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td data-label="Utente">
                                <div class="table-cell-avatar">
                                    <?php if (!empty($user['avatar'])): ?>
                                        <img src="<?php echo BASE_URL . '/uploads/avatars/' . $user['avatar']; ?>" alt="Avatar">
                                    <?php else: ?>
                                        <div class="user-avatar" style="width: 36px; height: 36px;">
                                            <i class="fas fa-user"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>
                                        <br>
                                        <span style="font-size: 0.875rem; color: var(--color-text-light);">
                                            @<?php echo htmlspecialchars($user['username']); ?>
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Email"><?php echo htmlspecialchars($user['email']); ?></td>
                            <td data-label="Ruolo">
                                <span class="badge badge-<?php echo $user['role']; ?>">
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                            </td>
                            <td data-label="Telefono"><?php echo htmlspecialchars($user['phone'] ?? '-'); ?></td>
                            <td data-label="Stato">
                                <?php if ($user['is_active']): ?>
                                    <span class="badge badge-success">Attivo</span>
                                <?php else: ?>
                                    <span class="badge" style="background-color: #ecf0f1; color: #7f8c8d;">Disattivato</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Data Creazione"><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                            <td data-label="Azioni" style="text-align: center;">
                                <div class="table-actions">
                                    <a href="<?php echo BASE_URL; ?>/user-form.php?id=<?php echo $user['id']; ?>" class="table-action-btn" title="Modifica">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                    <a href="<?php echo BASE_URL; ?>/user-delete.php?id=<?php echo $user['id']; ?>" 
                                       class="table-action-btn danger" 
                                       data-confirm-delete
                                       data-message="Sei sicuro di voler eliminare l'utente <?php echo htmlspecialchars($user['full_name']); ?>?"
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
            <span>Mostrando <?php echo count($users); ?> di <?php echo $totalUsers; ?> utenti</span>
            
            <div class="pagination">
                <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $roleFilter ? '&role=' . $roleFilter : ''; ?>" class="pagination-item">
                    <i class="fas fa-chevron-left"></i>
                </a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <a href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $roleFilter ? '&role=' . $roleFilter : ''; ?>" 
                   class="pagination-item <?php echo $i === $page ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                <a href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $roleFilter ? '&role=' . $roleFilter : ''; ?>" class="pagination-item">
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

$('#roleFilter').on('change', function() {
    applyFilters();
});

function applyFilters() {
    const search = $('#searchInput').val();
    const role = $('#roleFilter').val();
    
    let url = '?page=1';
    if (search) url += '&search=' + encodeURIComponent(search);
    if (role) url += '&role=' + role;
    
    window.location.href = url;
}

function resetFilters() {
    window.location.href = '<?php echo BASE_URL; ?>/users.php';
}
</script>

<?php include 'includes/footer.php'; ?>