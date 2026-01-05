<?php
// scheduled-maintenance-form.php
require_once 'config.php';

$auth = new Auth();
$auth->requireLogin();

if (!$auth->hasRole('admin') && !$auth->hasRole('technician')) {
    die('Accesso negato');
}

$db = Database::getInstance();

$smId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $smId > 0;

$sm = null;
$error = '';
$success = '';

if ($isEdit) {
    $sm = $db->fetchOne("SELECT * FROM scheduled_maintenances WHERE id = :id", ['id' => $smId]);
    if (!$sm) {
        header('Location: ' . BASE_URL . '/scheduled-maintenances.php');
        exit;
    }
}

$assets = $db->fetchAll("SELECT id, code, name FROM assets WHERE status != 'retired' ORDER BY code");
$types = $db->fetchAll("SELECT * FROM maintenance_types ORDER BY name");
$technicians = $db->fetchAll("SELECT id, full_name FROM users WHERE role IN ('admin', 'technician') AND is_active = 1 ORDER BY full_name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$auth->validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token CSRF non valido';
    } else {
        $asset_id = (int)$_POST['asset_id'];
        $type_id = !empty($_POST['type_id']) ? (int)$_POST['type_id'] : null;
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $frequency_type = $_POST['frequency_type'] ?? 'monthly';
        $frequency_value = (int)($_POST['frequency_value'] ?? 1);
        $start_date = $_POST['start_date'] ?? '';
        $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
        $assigned_to = !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null;
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if (empty($asset_id) || empty($title) || empty($start_date)) {
            $error = 'Asset, titolo e data inizio sono obbligatori';
        } else {
            $data = [
                'asset_id' => $asset_id,
                'type_id' => $type_id,
                'title' => $title,
                'description' => $description,
                'frequency_type' => $frequency_type,
                'frequency_value' => $frequency_value,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'assigned_to' => $assigned_to,
                'is_active' => $is_active
            ];
            
            // Calculate next execution date
            if (!$isEdit || $sm['start_date'] != $start_date || $sm['frequency_type'] != $frequency_type) {
                $data['next_execution'] = $start_date;
            }
            
            try {
                if ($isEdit) {
                    $db->update('scheduled_maintenances', $data, 'id = :id', ['id' => $smId]);
                    $_SESSION['success_message'] = 'Manutenzione programmata aggiornata';
                } else {
                    $newId = $db->insert('scheduled_maintenances', $data);
                    $_SESSION['success_message'] = 'Manutenzione programmata creata';
                    header('Location: ' . BASE_URL . '/scheduled-maintenance-form.php?id=' . $newId);
                    exit;
                }
                
                $sm = $db->fetchOne("SELECT * FROM scheduled_maintenances WHERE id = :id", ['id' => $smId]);
                $success = $_SESSION['success_message'];
                unset($_SESSION['success_message']);
            } catch (Exception $e) {
                $error = 'Errore durante il salvataggio: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = $isEdit ? 'Modifica Programmazione' : 'Nuova Programmazione';
include 'includes/header.php';
?>

<div class="container" style="max-width: 900px;">
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
            <h3><?php echo $isEdit ? 'Modifica Programmazione' : 'Nuova Programmazione'; ?></h3>
        </div>
        <div class="card-body">
            <form method="POST" data-validate>
                <input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRFToken(); ?>">
                
                <div class="form-group">
                    <label class="form-label required">Asset</label>
                    <select name="asset_id" class="form-control" required>
                        <option value="">Seleziona asset</option>
                        <?php foreach ($assets as $asset): ?>
                        <option value="<?php echo $asset['id']; ?>" <?php echo ($sm['asset_id'] ?? '') == $asset['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($asset['code'] . ' - ' . $asset['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label required">Titolo</label>
                    <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($sm['title'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Descrizione</label>
                    <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($sm['description'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Tipo Manutenzione</label>
                    <select name="type_id" class="form-control">
                        <option value="">Seleziona tipo</option>
                        <?php foreach ($types as $type): ?>
                        <option value="<?php echo $type['id']; ?>" <?php echo ($sm['type_id'] ?? '') == $type['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($type['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-grid form-grid-2">
                    <div class="form-group">
                        <label class="form-label required">Frequenza</label>
                        <select name="frequency_type" class="form-control" required>
                            <option value="daily" <?php echo ($sm['frequency_type'] ?? '') === 'daily' ? 'selected' : ''; ?>>Giornaliera</option>
                            <option value="weekly" <?php echo ($sm['frequency_type'] ?? '') === 'weekly' ? 'selected' : ''; ?>>Settimanale</option>
                            <option value="monthly" <?php echo ($sm['frequency_type'] ?? 'monthly') === 'monthly' ? 'selected' : ''; ?>>Mensile</option>
                            <option value="quarterly" <?php echo ($sm['frequency_type'] ?? '') === 'quarterly' ? 'selected' : ''; ?>>Trimestrale</option>
                            <option value="yearly" <?php echo ($sm['frequency_type'] ?? '') === 'yearly' ? 'selected' : ''; ?>>Annuale</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label required">Ogni (n volte)</label>
                        <input type="number" name="frequency_value" class="form-control" 
                               value="<?php echo $sm['frequency_value'] ?? 1; ?>" min="1" required>
                        <span class="form-text">Es: Ogni 2 = ogni 2 mesi/settimane</span>
                    </div>
                </div>
                
                <div class="form-grid form-grid-2">
                    <div class="form-group">
                        <label class="form-label required">Data Inizio</label>
                        <input type="date" name="start_date" class="form-control" 
                               value="<?php echo $sm['start_date'] ?? ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Data Fine (opzionale)</label>
                        <input type="date" name="end_date" class="form-control" 
                               value="<?php echo $sm['end_date'] ?? ''; ?>">
                        <span class="form-text">Lascia vuoto per programmazione illimitata</span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Assegnato a</label>
                    <select name="assigned_to" class="form-control">
                        <option value="">Non assegnato</option>
                        <?php foreach ($technicians as $tech): ?>
                        <option value="<?php echo $tech['id']; ?>" <?php echo ($sm['assigned_to'] ?? '') == $tech['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($tech['full_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <div class="form-check">
                        <input type="checkbox" name="is_active" id="is_active" class="form-check-input" 
                               value="1" <?php echo ($sm['is_active'] ?? 1) ? 'checked' : ''; ?>>
                        <label for="is_active" class="form-check-label">Programmazione attiva</label>
                    </div>
                    <span class="form-text">Le programmazioni disattivate non generano manutenzioni automatiche</span>
                </div>
                
                <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 32px;">
                    <button type="button" class="btn btn-outline" onclick="window.location.href='<?php echo BASE_URL; ?>/scheduled-maintenances.php'">
                        <i class="fas fa-times"></i>
                        Annulla
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        <?php echo $isEdit ? 'Salva Modifiche' : 'Crea Programmazione'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>