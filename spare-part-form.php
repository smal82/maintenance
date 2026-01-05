<?php
// spare-part-form.php
require_once 'config.php';

$auth = new Auth();
$auth->requireLogin();

if (!$auth->hasRole('admin') && !$auth->hasRole('technician')) {
    die('Accesso negato');
}

$db = Database::getInstance();

$partId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $partId > 0;

$part = null;
$error = '';
$success = '';

if ($isEdit) {
    $part = $db->fetchOne("SELECT * FROM spare_parts WHERE id = :id", ['id' => $partId]);
    if (!$part) {
        header('Location: ' . BASE_URL . '/spare-parts.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$auth->validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token CSRF non valido';
    } else {
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $quantity = (int)($_POST['quantity'] ?? 0);
        $min_quantity = (int)($_POST['min_quantity'] ?? 0);
        $unit_price = !empty($_POST['unit_price']) ? (float)$_POST['unit_price'] : null;
        $supplier = trim($_POST['supplier'] ?? '');
        $location = trim($_POST['location'] ?? '');
        
        if (empty($code) || empty($name)) {
            $error = 'Codice e nome sono obbligatori';
        } else {
            $checkCode = $db->fetchOne(
                "SELECT id FROM spare_parts WHERE code = :code" . ($isEdit ? " AND id != :id" : ""),
                $isEdit ? ['code' => $code, 'id' => $partId] : ['code' => $code]
            );
            
            if ($checkCode) {
                $error = 'Codice già utilizzato';
            } else {
                $data = [
                    'code' => $code,
                    'name' => $name,
                    'description' => $description,
                    'category' => $category,
                    'quantity' => $quantity,
                    'min_quantity' => $min_quantity,
                    'unit_price' => $unit_price,
                    'supplier' => $supplier,
                    'location' => $location
                ];
                
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                    $filename = $_FILES['image']['name'];
                    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                    
                    if (in_array($ext, $allowed) && $_FILES['image']['size'] <= MAX_FILE_SIZE) {
                        $imageDir = UPLOAD_PATH . '/spare-parts';
                        if (!is_dir($imageDir)) {
                            mkdir($imageDir, 0755, true);
                        }
                        
                        $newFilename = 'part_' . time() . '_' . uniqid() . '.' . $ext;
                        $destination = $imageDir . '/' . $newFilename;
                        
                        if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
                            if ($isEdit && !empty($part['image'])) {
                                $oldFile = $imageDir . '/' . $part['image'];
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
                            $db->update('spare_parts', $data, 'id = :id', ['id' => $partId]);
                            $_SESSION['success_message'] = 'Ricambio aggiornato con successo';
                        } else {
                            $newId = $db->insert('spare_parts', $data);
                            $_SESSION['success_message'] = 'Ricambio creato con successo';
                            header('Location: ' . BASE_URL . '/spare-part-form.php?id=' . $newId);
                            exit;
                        }
                        
                        $part = $db->fetchOne("SELECT * FROM spare_parts WHERE id = :id", ['id' => $partId]);
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

$pageTitle = $isEdit ? 'Modifica Ricambio' : 'Nuovo Ricambio';
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
            <h3><?php echo $isEdit ? 'Modifica Ricambio' : 'Nuovo Ricambio'; ?></h3>
        </div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data" data-validate>
                <input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRFToken(); ?>">
                
                <div class="form-grid form-grid-2">
                    <div class="form-group">
                        <label class="form-label required">Codice Ricambio</label>
                        <input type="text" name="code" class="form-control" 
                               value="<?php echo htmlspecialchars($part['code'] ?? ''); ?>" 
                               placeholder="PART-001" required style="text-transform: uppercase;">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label required">Nome Ricambio</label>
                        <input type="text" name="name" class="form-control" 
                               value="<?php echo htmlspecialchars($part['name'] ?? ''); ?>" 
                               placeholder="Filtro aria" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Descrizione</label>
                    <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($part['description'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-grid form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Categoria</label>
                        <input type="text" name="category" class="form-control" 
                               value="<?php echo htmlspecialchars($part['category'] ?? ''); ?>" 
                               placeholder="Filtri">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Fornitore</label>
                        <input type="text" name="supplier" class="form-control" 
                               value="<?php echo htmlspecialchars($part['supplier'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-grid form-grid-3">
                    <div class="form-group">
                        <label class="form-label required">Quantità</label>
                        <input type="number" name="quantity" class="form-control" 
                               value="<?php echo $part['quantity'] ?? 0; ?>" 
                               min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label required">Scorta Minima</label>
                        <input type="number" name="min_quantity" class="form-control" 
                               value="<?php echo $part['min_quantity'] ?? 0; ?>" 
                               min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Prezzo Unitario (€)</label>
                        <input type="number" name="unit_price" class="form-control" 
                               value="<?php echo $part['unit_price'] ?? ''; ?>" 
                               min="0" step="0.01" placeholder="0.00">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Posizione</label>
                    <input type="text" name="location" class="form-control" 
                           value="<?php echo htmlspecialchars($part['location'] ?? ''); ?>" 
                           placeholder="Scaffale A - Ripiano 3">
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
                    <?php if ($isEdit && !empty($part['image'])): ?>
                    <div style="margin-top: 16px;">
                        <img src="<?php echo BASE_URL . '/uploads/spare-parts/' . $part['image']; ?>" 
                             alt="Ricambio" style="max-width: 200px; border-radius: 8px;">
                    </div>
                    <?php endif; ?>
                </div>
                
                <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 32px;">
                    <button type="button" class="btn btn-outline" onclick="window.location.href='<?php echo BASE_URL; ?>/spare-parts.php'">
                        <i class="fas fa-times"></i>
                        Annulla
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        <?php echo $isEdit ? 'Salva Modifiche' : 'Crea Ricambio'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>