<?php
// asset-form.php
require_once 'config.php';

$auth = new Auth();
$auth->requireLogin();

if (!$auth->hasPermission('create_asset') && !$auth->hasRole('admin')) {
    die('Accesso negato');
}

$db = Database::getInstance();

$assetId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $assetId > 0;

$asset = null;
$error = '';
$success = '';

// Load asset data for edit
if ($isEdit) {
    $asset = $db->fetchOne("SELECT * FROM assets WHERE id = :id", ['id' => $assetId]);
    if (!$asset) {
        header('Location: ' . BASE_URL . '/assets.php');
        exit;
    }
}

// Get categories
$categories = $db->fetchAll("SELECT * FROM asset_categories ORDER BY name");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$auth->validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token CSRF non valido';
    } else {
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $name = trim($_POST['name'] ?? '');
        $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
        $description = trim($_POST['description'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $purchase_date = !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : null;
        $purchase_price = !empty($_POST['purchase_price']) ? (float)$_POST['purchase_price'] : null;
        $warranty_expiry = !empty($_POST['warranty_expiry']) ? $_POST['warranty_expiry'] : null;
        $status = $_POST['status'] ?? 'operational';
        $notes = trim($_POST['notes'] ?? '');
        
        // Validation
        if (empty($code) || empty($name)) {
            $error = 'Codice e nome sono obbligatori';
        } elseif (!in_array($status, ['operational', 'maintenance', 'broken', 'retired'])) {
            $error = 'Stato non valido';
        } else {
            // Check code uniqueness
            $checkCode = $db->fetchOne(
                "SELECT id FROM assets WHERE code = :code" . ($isEdit ? " AND id != :id" : ""),
                $isEdit ? ['code' => $code, 'id' => $assetId] : ['code' => $code]
            );
            
            if ($checkCode) {
                $error = 'Codice asset già utilizzato';
            } else {
                $data = [
                    'code' => $code,
                    'name' => $name,
                    'category_id' => $category_id,
                    'description' => $description,
                    'location' => $location,
                    'purchase_date' => $purchase_date,
                    'purchase_price' => $purchase_price,
                    'warranty_expiry' => $warranty_expiry,
                    'status' => $status,
                    'notes' => $notes
                ];
                
                if (!$isEdit) {
                    $data['created_by'] = $_SESSION['user_id'];
                }
                
                // Handle image upload
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                    $filename = $_FILES['image']['name'];
                    $ext = strtolower(pathlib($filename, PATHINFO_EXTENSION));
                    
                    if (!in_array($ext, $allowed)) {
                        $error = 'Formato immagine non consentito';
                    } elseif ($_FILES['image']['size'] > MAX_FILE_SIZE) {
                        $error = 'File troppo grande';
                    } else {
                        $imageDir = UPLOAD_PATH . '/assets';
                        if (!is_dir($imageDir)) {
                            mkdir($imageDir, 0755, true);
                        }
                        
                        $newFilename = 'asset_' . time() . '_' . uniqid() . '.' . $ext;
                        $destination = $imageDir . '/' . $newFilename;
                        
                        if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
                            // Delete old image
                            if ($isEdit && !empty($asset['image'])) {
                                $oldFile = $imageDir . '/' . $asset['image'];
                                if (file_exists($oldFile)) {
                                    unlink($oldFile);
                                }
                            }
                            $data['image'] = $newFilename;
                        }
                    }
                }
                
                if (empty($error)) {
                    try {
                        if ($isEdit) {
                            $db->update('assets', $data, 'id = :id', ['id' => $assetId]);
                            $_SESSION['success_message'] = 'Asset aggiornato con successo';
                        } else {
                            $newAssetId = $db->insert('assets', $data);
                            $_SESSION['success_message'] = 'Asset creato con successo';
                            header('Location: ' . BASE_URL . '/asset-form.php?id=' . $newAssetId);
                            exit;
                        }
                        
                        $asset = $db->fetchOne("SELECT * FROM assets WHERE id = :id", ['id' => $assetId ?: $newAssetId]);
                        $success = $_SESSION['success_message'];
                        unset($_SESSION['success_message']);
                    } catch (Exception $e) {
                        $error = 'Errore durante il salvataggio: ' . $e->getMessage();
                    }
                }
            }
        }
    }
}

$pageTitle = $isEdit ? 'Modifica Asset' : 'Nuovo Asset';
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
            <h3><?php echo $isEdit ? 'Modifica Asset' : 'Nuovo Asset'; ?></h3>
        </div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data" data-validate id="assetForm">
                <input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRFToken(); ?>">
                
                <div class="form-grid form-grid-2">
                    <div class="form-group">
                        <label class="form-label required">Codice Asset</label>
                        <input type="text" name="code" class="form-control" 
                               value="<?php echo htmlspecialchars($asset['code'] ?? ''); ?>" 
                               placeholder="ASSET-001" required style="text-transform: uppercase;">
                        <span class="form-text">Codice univoco (es: ASSET-001)</span>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label required">Nome Asset</label>
                        <input type="text" name="name" class="form-control" 
                               value="<?php echo htmlspecialchars($asset['name'] ?? ''); ?>" 
                               placeholder="Compressore Aria" required>
                    </div>
                </div>
                
                <div class="form-grid form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Categoria</label>
                        <select name="category_id" class="form-control">
                            <option value="">Seleziona categoria</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" 
                                    <?php echo ($asset['category_id'] ?? '') == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label required">Stato</label>
                        <select name="status" class="form-control" required>
                            <option value="operational" <?php echo ($asset['status'] ?? 'operational') === 'operational' ? 'selected' : ''; ?>>Operativo</option>
                            <option value="maintenance" <?php echo ($asset['status'] ?? '') === 'maintenance' ? 'selected' : ''; ?>>In Manutenzione</option>
                            <option value="broken" <?php echo ($asset['status'] ?? '') === 'broken' ? 'selected' : ''; ?>>Guasto</option>
                            <option value="retired" <?php echo ($asset['status'] ?? '') === 'retired' ? 'selected' : ''; ?>>Dismesso</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Descrizione</label>
                    <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($asset['description'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Posizione</label>
                    <input type="text" name="location" class="form-control" 
                           value="<?php echo htmlspecialchars($asset['location'] ?? ''); ?>" 
                           placeholder="Stabilimento A - Piano Terra">
                </div>
                
                <div class="form-grid form-grid-3">
                    <div class="form-group">
                        <label class="form-label">Data Acquisto</label>
                        <input type="date" name="purchase_date" class="form-control" 
                               value="<?php echo $asset['purchase_date'] ?? ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Prezzo Acquisto (€)</label>
                        <input type="number" name="purchase_price" class="form-control" 
                               value="<?php echo $asset['purchase_price'] ?? ''; ?>" 
                               min="0" step="0.01" placeholder="0.00">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Scadenza Garanzia</label>
                        <input type="date" name="warranty_expiry" class="form-control" 
                               value="<?php echo $asset['warranty_expiry'] ?? ''; ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Immagine</label>
                    <div class="file-upload">
                        <input type="file" name="image" id="image" accept="image/*">
                        <label for="image" class="file-upload-label">
                            <i class="fas fa-cloud-upload-alt file-upload-icon"></i>
                            <div class="file-upload-text">
                                <strong>Clicca per caricare un'immagine</strong>
                                <span>Formati: JPG, PNG, GIF (max 10MB)</span>
                            </div>
                        </label>
                    </div>
                    <?php if ($isEdit && !empty($asset['image'])): ?>
                    <div style="margin-top: 16px;">
                        <img src="<?php echo BASE_URL . '/uploads/assets/' . $asset['image']; ?>" 
                             alt="Asset" style="max-width: 200px; border-radius: 8px;">
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Note</label>
                    <textarea name="notes" class="form-control" rows="3"><?php echo htmlspecialchars($asset['notes'] ?? ''); ?></textarea>
                </div>
                
                <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 32px;">
                    <button type="button" class="btn btn-outline" onclick="window.location.href='<?php echo BASE_URL; ?>/assets.php'">
                        <i class="fas fa-times"></i>
                        Annulla
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        <?php echo $isEdit ? 'Salva Modifiche' : 'Crea Asset'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>