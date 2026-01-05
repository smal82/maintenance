<?php
// category-form.php
require_once 'config.php';

$auth = new Auth();
$auth->requireRole('admin');

$db = Database::getInstance();

$catId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $catId > 0;

$category = null;
$error = '';
$success = '';

if ($isEdit) {
    $category = $db->fetchOne("SELECT * FROM asset_categories WHERE id = :id", ['id' => $catId]);
    if (!$category) {
        header('Location: ' . BASE_URL . '/categories.php');
        exit;
    }
}

$categories = $db->fetchAll("SELECT * FROM asset_categories WHERE id != :id ORDER BY name", ['id' => $catId ?: 0]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$auth->validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token CSRF non valido';
    } else {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $icon = trim($_POST['icon'] ?? 'fa-box');
        $color = trim($_POST['color'] ?? '#3498db');
        $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
        
        if (empty($name)) {
            $error = 'Il nome è obbligatorio';
        } else {
            $data = [
                'name' => $name,
                'description' => $description,
                'icon' => $icon,
                'color' => $color,
                'parent_id' => $parent_id
            ];
            
            try {
                if ($isEdit) {
                    $db->update('asset_categories', $data, 'id = :id', ['id' => $catId]);
                    $_SESSION['success_message'] = 'Categoria aggiornata con successo';
                } else {
                    $newId = $db->insert('asset_categories', $data);
                    $_SESSION['success_message'] = 'Categoria creata con successo';
                    header('Location: ' . BASE_URL . '/category-form.php?id=' . $newId);
                    exit;
                }
                
                $category = $db->fetchOne("SELECT * FROM asset_categories WHERE id = :id", ['id' => $catId]);
                $success = $_SESSION['success_message'];
                unset($_SESSION['success_message']);
            } catch (Exception $e) {
                $error = 'Errore durante il salvataggio: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = $isEdit ? 'Modifica Categoria' : 'Nuova Categoria';
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
            <h3><?php echo $isEdit ? 'Modifica Categoria' : 'Nuova Categoria'; ?></h3>
        </div>
        <div class="card-body">
            <form method="POST" data-validate>
                <input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRFToken(); ?>">
                
                <div class="form-group">
                    <label class="form-label required">Nome Categoria</label>
                    <input type="text" name="name" class="form-control" 
                           value="<?php echo htmlspecialchars($category['name'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Descrizione</label>
                    <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($category['description'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-grid form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Icona Font Awesome</label>
                        <input type="text" name="icon" class="form-control" 
                               value="<?php echo htmlspecialchars($category['icon'] ?? 'fa-box'); ?>" 
                               placeholder="fa-cogs">
                        <span class="form-text">Es: fa-cogs, fa-wrench, fa-laptop</span>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Colore</label>
                        <input type="color" name="color" class="form-control" 
                               value="<?php echo htmlspecialchars($category['color'] ?? '#3498db'); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Categoria Padre</label>
                    <select name="parent_id" class="form-control">
                        <option value="">Nessuna (categoria principale)</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo ($category['parent_id'] ?? '') == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 32px;">
                    <button type="button" class="btn btn-outline" onclick="window.location.href='<?php echo BASE_URL; ?>/categories.php'">
                        <i class="fas fa-times"></i>
                        Annulla
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        <?php echo $isEdit ? 'Salva Modifiche' : 'Crea Categoria'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>