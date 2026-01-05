<?php
// profile.php
require_once 'config.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$user = $auth->getCurrentUser();

$success = '';
$error = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$auth->validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token CSRF non valido';
    } else {
        $full_name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validate required fields
        if (empty($full_name) || empty($email)) {
            $error = 'Nome e email sono obbligatori';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Email non valida';
        } else {
            // Check if email is already used by another user
            $emailCheck = $db->fetchOne(
                "SELECT id FROM users WHERE email = :email AND id != :id",
                ['email' => $email, 'id' => $user['id']]
            );
            
            if ($emailCheck) {
                $error = 'Email già utilizzata da un altro utente';
            } else {
                $updateData = [
                    'full_name' => $full_name,
                    'email' => $email,
                    'phone' => $phone
                ];
                
                // Handle password change
                if (!empty($new_password)) {
                    if (empty($current_password)) {
                        $error = 'Inserisci la password attuale per cambiarla';
                    } elseif (!password_verify($current_password, $user['password'])) {
                        $error = 'Password attuale non corretta';
                    } elseif (strlen($new_password) < 8) {
                        $error = 'La nuova password deve essere di almeno 8 caratteri';
                    } elseif ($new_password !== $confirm_password) {
                        $error = 'Le password non coincidono';
                    } else {
                        $updateData['password'] = password_hash($new_password, PASSWORD_DEFAULT);
                    }
                }
                
                // Handle avatar upload
                if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                    $filename = $_FILES['avatar']['name'];
                    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                    
                    if (!in_array($ext, $allowed)) {
                        $error = 'Formato file non consentito';
                    } elseif ($_FILES['avatar']['size'] > MAX_FILE_SIZE) {
                        $error = 'File troppo grande';
                    } else {
                        $avatarDir = UPLOAD_PATH . '/avatars';
                        if (!is_dir($avatarDir)) {
                            mkdir($avatarDir, 0755, true);
                        }
                        
                        $newFilename = 'user_' . $user['id'] . '_' . time() . '.' . $ext;
                        $destination = $avatarDir . '/' . $newFilename;
                        
                        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $destination)) {
                            // Delete old avatar
                            if (!empty($user['avatar'])) {
                                $oldFile = $avatarDir . '/' . $user['avatar'];
                                if (file_exists($oldFile)) {
                                    unlink($oldFile);
                                }
                            }
                            $updateData['avatar'] = $newFilename;
                        }
                    }
                }
                
                if (empty($error)) {
                    $db->update('users', $updateData, 'id = :id', ['id' => $user['id']]);
                    
                    // Update session data
                    $_SESSION['full_name'] = $full_name;
                    
                    $success = 'Profilo aggiornato con successo';
                    $user = $auth->getCurrentUser(); // Reload user data
                }
            }
        }
    }
}

$pageTitle = 'Il Mio Profilo';
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
            <h3>Informazioni Profilo</h3>
        </div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data" data-validate id="profileForm">
                <input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRFToken(); ?>">
                
                <!-- Avatar -->
                <div class="form-group" style="text-align: center; margin-bottom: 32px;">
                    <div style="display: inline-block; position: relative;">
                        <div class="user-avatar" style="width: 120px; height: 120px; font-size: 3rem; margin: 0 auto;" id="avatarContainer">
                            <?php if (!empty($user['avatar'])): ?>
                                <img src="<?php echo BASE_URL . '/uploads/avatars/' . $user['avatar']; ?>" alt="Avatar" id="avatarPreview">
                            <?php else: ?>
                                <i class="fas fa-user" id="avatarIcon"></i>
                            <?php endif; ?>
                        </div>
                        <label for="avatar" style="position: absolute; bottom: 0; right: 0; width: 40px; height: 40px; background: var(--color-primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 2px 8px rgba(0,0,0,0.2);">
                            <i class="fas fa-camera"></i>
                        </label>
                        <input type="file" name="avatar" id="avatar" accept="image/*" style="display: none;">
                    </div>
                    <p style="margin-top: 12px; color: var(--color-text-light); font-size: 0.875rem;">
                        Clicca sull'icona per cambiare foto
                    </p>
                </div>
                
                <div class="form-grid form-grid-2">
                    <div class="form-group">
                        <label class="form-label required">Nome Completo</label>
                        <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label required">Email</label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                </div>
                
                <div class="form-grid form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                        <span class="form-text">Lo username non può essere modificato</span>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Telefono</label>
                        <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Ruolo</label>
                    <input type="text" class="form-control" value="<?php echo ucfirst($user['role']); ?>" disabled>
                </div>
                
                <hr style="margin: 32px 0; border: none; border-top: 1px solid var(--color-border);">
                
                <h4 style="margin-bottom: 24px; color: var(--color-dark);">Cambio Password</h4>
                <p style="color: var(--color-text-light); margin-bottom: 24px;">Lascia vuoto se non vuoi cambiare la password</p>
                
                <div class="form-group">
                    <label class="form-label">Password Attuale</label>
                    <input type="password" name="current_password" class="form-control">
                </div>
                
                <div class="form-grid form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Nuova Password</label>
                        <input type="password" name="new_password" class="form-control" minlength="8">
                        <span class="form-text">Minimo 8 caratteri</span>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Conferma Password</label>
                        <input type="password" name="confirm_password" class="form-control">
                    </div>
                </div>
                
                <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 32px;">
                    <button type="button" class="btn btn-outline" onclick="window.location.href='<?php echo BASE_URL; ?>/index.php'">
                        Annulla
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Salva Modifiche
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Avatar preview - CORRETTO
    $('#avatar').on('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#avatarIcon').remove();
                const preview = $('#avatarPreview');
                if (preview.length) {
                    preview.attr('src', e.target.result);
                } else {
                    $('#avatarContainer').html(`<img src="${e.target.result}" alt="Avatar" id="avatarPreview" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">`);
                }
            };
            reader.readAsDataURL(file);
        }
    });
    
    // Password validation
    $('[name="new_password"], [name="confirm_password"]').on('input', function() {
        const newPass = $('[name="new_password"]').val();
        const confirmPass = $('[name="confirm_password"]').val();
        
        if (newPass && confirmPass && newPass !== confirmPass) {
            $('[name="confirm_password"]').addClass('error');
        } else {
            $('[name="confirm_password"]').removeClass('error');
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>