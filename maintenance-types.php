<?php
// maintenance-types.php
require_once 'config.php';

$auth = new Auth();
$auth->requireRole('admin');

$db = Database::getInstance();

$types = $db->fetchAll("SELECT * FROM maintenance_types ORDER BY name");

$success = $_SESSION['success_message'] ?? '';
$error = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

$pageTitle = 'Tipi Manutenzione';
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
            <h3>Tipi Manutenzione</h3>
            <a href="<?php echo BASE_URL; ?>/maintenance-type-form.php" class="btn btn-primary">
                <i class="fas fa-plus"></i>
                Nuovo Tipo
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Descrizione</th>
                            <th>Colore</th>
                            <th>Preventiva</th>
                            <th style="text-align: center;">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($types)): ?>
                        <tr>
                            <td colspan="5" class="table-empty">
                                <i class="fas fa-wrench"></i>
                                <p>Nessun tipo trovato</p>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($types as $type): ?>
                            <tr>
                                <td data-label="Nome">
                                    <strong><?php echo htmlspecialchars($type['name']); ?></strong>
                                </td>
                                <td data-label="Descrizione"><?php echo htmlspecialchars($type['description'] ?? ''); ?></td>
                                <td data-label="Colore">
                                    <span style="display:inline-block;width:30px;height:30px;background:<?php echo $type['color']; ?>;border-radius:4px;border:1px solid #ddd;"></span>
                                </td>
                                <td data-label="Preventiva">
                                    <?php if ($type['is_preventive']): ?>
                                        <span class="badge badge-success">Sì</span>
                                    <?php else: ?>
                                        <span class="badge" style="background-color: #ecf0f1; color: #7f8c8d;">No</span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Azioni" style="text-align: center;">
                                    <div class="table-actions">
                                        <a href="<?php echo BASE_URL; ?>/maintenance-type-form.php?id=<?php echo $type['id']; ?>" class="table-action-btn" title="Modifica">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="<?php echo BASE_URL; ?>/maintenance-type-delete.php?id=<?php echo $type['id']; ?>" 
                                           class="table-action-btn danger" 
                                           data-confirm-delete
                                           data-message="Sei sicuro di voler eliminare questo tipo?"
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