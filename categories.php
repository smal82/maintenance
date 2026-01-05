<?php
// categories.php
require_once 'config.php';

$auth = new Auth();
$auth->requireRole('admin');

$db = Database::getInstance();

$categories = $db->fetchAll("SELECT * FROM asset_categories ORDER BY name");

$success = $_SESSION['success_message'] ?? '';
$error = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

$pageTitle = 'Categorie Asset';
include 'includes/header.php';
?>

<div class="container">
    <?php if ($success): ?>
    <div class="alert alert-success" data-auto-hide="5000">
        <i class="fas fa-check-circle"></i>
        <span><?php echo htmlspecialchars($success); ?></span>
    </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            <h3>Categorie Asset</h3>
            <a href="<?php echo BASE_URL; ?>/category-form.php" class="btn btn-primary">
                <i class="fas fa-plus"></i>
                Nuova Categoria
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Descrizione</th>
                            <th>Icona</th>
                            <th>Colore</th>
                            <th style="text-align: center;">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($categories)): ?>
                        <tr>
                            <td colspan="5" class="table-empty">
                                <i class="fas fa-folder"></i>
                                <p>Nessuna categoria trovata</p>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td data-label="Nome">
                                    <strong><?php echo htmlspecialchars($cat['name']); ?></strong>
                                </td>
                                <td data-label="Descrizione"><?php echo htmlspecialchars($cat['description'] ?? ''); ?></td>
                                <td data-label="Icona">
                                    <i class="fas <?php echo $cat['icon']; ?>" style="font-size: 1.25rem;"></i>
                                </td>
                                <td data-label="Colore">
                                    <span style="display:inline-block;width:30px;height:30px;background:<?php echo $cat['color']; ?>;border-radius:4px;border:1px solid #ddd;"></span>
                                </td>
                                <td data-label="Azioni" style="text-align: center;">
                                    <div class="table-actions">
                                        <a href="<?php echo BASE_URL; ?>/category-form.php?id=<?php echo $cat['id']; ?>" class="table-action-btn" title="Modifica">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="<?php echo BASE_URL; ?>/category-delete.php?id=<?php echo $cat['id']; ?>" 
                                           class="table-action-btn danger" 
                                           data-confirm-delete
                                           data-message="Sei sicuro di voler eliminare questa categoria?"
                                           title="Elimina">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>