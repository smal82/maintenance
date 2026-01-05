<?php
// maintenance-form.php
require_once 'config.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();

$maintenanceId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $maintenanceId > 0;

$maintenance = null;
$error = '';
$success = '';

if ($isEdit) {
    $maintenance = $db->fetchOne("SELECT * FROM maintenances WHERE id = :id", ['id' => $maintenanceId]);
    if (!$maintenance) {
        header('Location: ' . BASE_URL . '/maintenances.php');
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
        $priority = $_POST['priority'] ?? 'medium';
        $status = $_POST['status'] ?? 'scheduled';
        $scheduled_date = $_POST['scheduled_date'] ?? '';
        $estimated_duration = !empty($_POST['estimated_duration']) ? (int)$_POST['estimated_duration'] : null;
        $assigned_to = !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null;
        $notes = trim($_POST['notes'] ?? '');
        
        if (empty($asset_id) || empty($title) || empty($scheduled_date)) {
            $error = 'Asset, titolo e data programmata sono obbligatori';
        } else {
            $data = [
                'asset_id' => $asset_id,
                'type_id' => $type_id,
                'title' => $title,
                'description' => $description,
                'priority' => $priority,
                'status' => $status,
                'scheduled_date' => $scheduled_date,
                'estimated_duration' => $estimated_duration,
                'assigned_to' => $assigned_to,
                'notes' => $notes
            ];
            
            if (!$isEdit) {
                $data['created_by'] = $_SESSION['user_id'];
            }
            
            try {
                if ($isEdit) {
                    $db->update('maintenances', $data, 'id = :id', ['id' => $maintenanceId]);
                    $_SESSION['success_message'] = 'Manutenzione aggiornata con successo';
                } else {
                    $newId = $db->insert('maintenances', $data);
                    $_SESSION['success_message'] = 'Manutenzione creata con successo';
                    header('Location: ' . BASE_URL . '/maintenance-form.php?id=' . $newId);
                    exit;
                }
                
                $maintenance = $db->fetchOne("SELECT * FROM maintenances WHERE id = :id", ['id' => $maintenanceId]);
                $success = $_SESSION['success_message'];
                unset($_SESSION['success_message']);
            } catch (Exception $e) {
                $error = 'Errore durante il salvataggio: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = $isEdit ? 'Modifica Manutenzione' : 'Nuova Manutenzione';
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
            <h3><?php echo $isEdit ? 'Modifica Manutenzione' : 'Nuova Manutenzione'; ?></h3>
        </div>
        <div class="card-body">
            <form method="POST" data-validate>
                <input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRFToken(); ?>">
                
                <div class="form-group">
                    <label class="form-label required">Asset</label>
                    <select name="asset_id" class="form-control" required>
                        <option value="">Seleziona asset</option>
                        <?php foreach ($assets as $asset): ?>
                        <option value="<?php echo $asset['id']; ?>" <?php echo ($maintenance['asset_id'] ?? '') == $asset['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($asset['code'] . ' - ' . $asset['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label required">Titolo</label>
                    <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($maintenance['title'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Descrizione</label>
                    <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($maintenance['description'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-grid form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Tipo Manutenzione</label>
                        <select name="type_id" class="form-control">
                            <option value="">Seleziona tipo</option>
                            <?php foreach ($types as $type): ?>
                            <option value="<?php echo $type['id']; ?>" <?php echo ($maintenance['type_id'] ?? '') == $type['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($type['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label required">Priorità</label>
                        <select name="priority" class="form-control" required>
                            <option value="low" <?php echo ($maintenance['priority'] ?? '') === 'low' ? 'selected' : ''; ?>>Bassa</option>
                            <option value="medium" <?php echo ($maintenance['priority'] ?? 'medium') === 'medium' ? 'selected' : ''; ?>>Media</option>
                            <option value="high" <?php echo ($maintenance['priority'] ?? '') === 'high' ? 'selected' : ''; ?>>Alta</option>
                            <option value="critical" <?php echo ($maintenance['priority'] ?? '') === 'critical' ? 'selected' : ''; ?>>Critica</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-grid form-grid-2">
                    <div class="form-group">
                        <label class="form-label required">Stato</label>
                        <select name="status" class="form-control" required>
                            <option value="scheduled" <?php echo ($maintenance['status'] ?? 'scheduled') === 'scheduled' ? 'selected' : ''; ?>>Programmata</option>
                            <option value="in_progress" <?php echo ($maintenance['status'] ?? '') === 'in_progress' ? 'selected' : ''; ?>>In Corso</option>
                            <option value="completed" <?php echo ($maintenance['status'] ?? '') === 'completed' ? 'selected' : ''; ?>>Completata</option>
                            <option value="cancelled" <?php echo ($maintenance['status'] ?? '') === 'cancelled' ? 'selected' : ''; ?>>Annullata</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label required">Data e Ora Programmata</label>
                        <input type="datetime-local" name="scheduled_date" class="form-control" 
                               value="<?php echo !empty($maintenance['scheduled_date']) ? date('Y-m-d\TH:i', strtotime($maintenance['scheduled_date'])) : ''; ?>" required>
                    </div>
                </div>
                
                <div class="form-grid form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Durata Stimata (minuti)</label>
                        <input type="number" name="estimated_duration" class="form-control" 
                               value="<?php echo $maintenance['estimated_duration'] ?? ''; ?>" min="0" placeholder="60">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Assegnato a</label>
                        <select name="assigned_to" class="form-control">
                            <option value="">Non assegnato</option>
                            <?php foreach ($technicians as $tech): ?>
                            <option value="<?php echo $tech['id']; ?>" <?php echo ($maintenance['assigned_to'] ?? '') == $tech['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($tech['full_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Note</label>
                    <textarea name="notes" class="form-control" rows="3"><?php echo htmlspecialchars($maintenance['notes'] ?? ''); ?></textarea>
                </div>
                
                <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 32px;">
                    <button type="button" class="btn btn-outline" onclick="window.location.href='<?php echo BASE_URL; ?>/maintenances.php'">
                        <i class="fas fa-times"></i>
                        Annulla
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        <?php echo $isEdit ? 'Salva Modifiche' : 'Crea Manutenzione'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>