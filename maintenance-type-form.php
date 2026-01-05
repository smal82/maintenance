<?php
// maintenance-type-form.php
require_once 'config.php';

$auth = new Auth();
$auth->requireRole('admin');

$db = Database::getInstance();

$typeId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $typeId > 0;

$type = null;
$error = '';
$success = '';

if ($isEdit) {
    $type = $db->fetchOne("SELECT * FROM maintenance_types WHERE id = :id", ['id' => $typeId]);
    if (!$type) {
        header('Location: ' . BASE_URL . '/maintenance-types.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$auth->validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token CSRF non valido';
    } else {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $color = trim($_POST['color'] ?? '#3498db');
        $is_preventive = isset($_POST['is_preventive']) ? 1 : 0;
        
        if (empty($name)) {
            $error = 'Il nome è obbligatorio';
        } else {
            $data = [
                'name' => $name,
                'description' => $description,
                'color' => $color,
                'is_preventive' => $is_preventive
            ];
            
            try {
                if ($isEdit) {
                    $db->update('maintenance_types', $data, 'id = :id', ['id' => $typeId]);
                    $_SESSION['success_message'] = 'Tipo aggiornato con successo';
                } else {
                    $newId = $db->insert('maintenance_types', $data);
                    $_SESSION['success_message'] = 'Tipo creato con successo';
                    header('Location: ' . BASE_URL . '/maintenance-type-form.php?id=' . $newId);
                    exit;
                }
                
                $type = $db->fetchOne("SELECT * FROM maintenance_types WHERE id = :id", ['id' => $typeId]);
                $success = $_SESSION['success_message'];
                unset($_SESSION['success_message']);
            } catch (Exception $e) {
                $error = 'Errore durante il salvataggio: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = $isEdit ? 'Modifica Tipo' : 'Nuovo Tipo';
include 'includes/header.php';
?>

<div class="container" style="max-width: 800px;">
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
            <h3><?php echo $isEdit ? 'Modifica Tipo' : 'Nuovo Tipo'; ?></h3>
        </div>
        <div class="card-body">
            <form method="POST" data-validate>
                <input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRFToken(); ?>">
                
                <div class="form-group">
                    <label class="form-label required">Nome Tipo</label>
                    <input type="text" name="name" class="form-control" 
                           value="<?php echo htmlspecialchars($type['name'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Descrizione</label>
                    <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($type['description'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Colore</label>
                    <input type="color" name="color" class="form-control" 
                           value="<?php echo htmlspecialchars($type['color'] ?? '#3498db'); ?>">
                </div>
                
                <div class="form-group">
                    <div class="form-check">
                        <input type="checkbox" name="is_preventive" id="is_preventive" class="form-check-input" 
                               value="1" <?php echo ($type['is_preventive'] ?? 0) ? 'checked' : ''; ?>>
                        <label for="is_preventive" class="form-check-label">Manutenzione Preventiva</label>
                    </div>
                    <span class="form-text">Le manutenzioni preventive sono programmate regolarmente</span>
                </div>
                
                <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 32px;">
                    <button type="button" class="btn btn-outline" onclick="window.location.href='<?php echo BASE_URL; ?>/maintenance-types.php'">
                        <i class="fas fa-times"></i>
                        Annulla
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        <?php echo $isEdit ? 'Salva Modifiche' : 'Crea Tipo'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>