<?php
// settings.php
require_once 'config.php';
$auth = new Auth();
$auth->requireRole('admin');
$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST as $key => $value) {
        if ($key !== 'csrf_token') {
            $db->query("
                UPDATE system_settings 
                SET setting_value = :value 
                WHERE setting_key = :key
            ", ['value' => $value, 'key' => $key]);
        }
    }
    $_SESSION['success_message'] = 'Impostazioni salvate';
    header('Location: ' . BASE_URL . '/settings.php');
    exit;
}

$settings = $db->fetchAll("SELECT * FROM system_settings ORDER BY setting_key");

$pageTitle = 'Impostazioni Sistema';
include 'includes/header.php';
?>

<div class="container" style="max-width: 800px;">
    <div class="card">
        <div class="card-header">
            <h3>Impostazioni Sistema</h3>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRFToken(); ?>">
                
                <?php foreach ($settings as $setting): ?>
                <div class="form-group">
                    <label class="form-label"><?php echo htmlspecialchars($setting['description']); ?></label>
                    
                    <?php if ($setting['setting_type'] === 'boolean'): ?>
                        <div class="form-check">
                            <input type="checkbox" name="<?php echo $setting['setting_key']; ?>" 
                                   value="1" <?php echo $setting['setting_value'] ? 'checked' : ''; ?> 
                                   class="form-check-input">
                        </div>
                    <?php elseif ($setting['setting_type'] === 'integer'): ?>
                        <input type="number" name="<?php echo $setting['setting_key']; ?>" 
                               class="form-control" value="<?php echo $setting['setting_value']; ?>">
                    <?php else: ?>
                        <input type="text" name="<?php echo $setting['setting_key']; ?>" 
                               class="form-control" value="<?php echo htmlspecialchars($setting['setting_value']); ?>">
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Salva Impostazioni
                </button>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>