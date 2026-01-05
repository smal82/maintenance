<?php
// user-form.php
require_once 'config.php';

$auth = new Auth();
$auth->requireRole('admin');

$db = Database::getInstance();

$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $userId > 0;

$user = null;
$error = '';
$success = '';

// Load user data for edit
if ($isEdit) {
    $user = $db->fetchOne("SELECT * FROM users WHERE id = :id", ['id' => $userId]);
    if (!$user) {
        header('Location: ' . BASE_URL . '/users.php');
        exit;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$auth->validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token CSRF non valido';
    } else {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $full_name = trim($_POST['full_name'] ?? '');
        $role = $_POST['role'] ?? 'viewer';
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        // Validation
        if (empty($username) || empty($email) || empty($full_name)) {
            $error = 'Username, email e nome completo sono obbligatori';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Email non valida';
        } elseif (!in_array($role, ['admin', 'technician', 'viewer'])) {
            $error = 'Ruolo non valido';
        } else {
            // Check username uniqueness
            $checkUsername = $db->fetchOne(
                "SELECT id FROM users WHERE username = :username" . ($isEdit ? " AND id != :id" : ""),
                $isEdit ? ['username' => $username, 'id' => $userId] : ['username' => $username]
            );
            
            if ($checkUsername) {
                $error = 'Username già utilizzato';
            } else {
                // Check email uniqueness
                $checkEmail = $db->fetchOne(
                    "SELECT id FROM users WHERE email = :email" . ($isEdit ? " AND id != :id" : ""),
                    $isEdit ? ['email' => $email, 'id' => $userId] : ['email' => $email]
                );
                
                if ($checkEmail) {
                    $error = 'Email già utilizzata';
                } else {
                    $data = [
                        'username' => $username,
                        'email' => $email,
                        'full_name' => $full_name,
                        'role' => $role,
                        'phone' => $phone,
                        'is_active' => $is_active
                    ];
                    
                    // Handle password
                    if (!$isEdit) {
                        // New user - password required
                        if (empty($password)) {
                            $error = 'La password è obbligatoria';
                        } elseif (strlen($password) < 8) {
                            $error = 'La password deve essere di almeno 8 caratteri';
                        } elseif ($password !== $confirm_password) {
                            $error = 'Le password non coincidono';
                        } else {
                            $data['password'] = password_hash($password, PASSWORD_DEFAULT);
                        }
                    } else {
                        // Edit user - password optional
                        if (!empty($password)) {
                            if (strlen($password) < 8) {
                                $error = 'La password deve essere di almeno 8 caratteri';
                            } elseif ($password !== $confirm_password) {
                                $error = 'Le password non coincidono';
                            } else {
                                $data['password'] = password_hash($password, PASSWORD_DEFAULT);
                            }
                        }
                    }
                    
                    if (empty($error)) {
                        try {
                            if ($isEdit) {
                                $db->update('users', $data, 'id = :id', ['id' => $userId]);
                                $success = 'Utente aggiornato con successo';
                                $user = $db->fetchOne("SELECT * FROM users WHERE id = :id", ['id' => $userId]);
                            } else {
                                $newUserId = $db->insert('users', $data);
                                $_SESSION['success_message'] = 'Utente creato con successo';
                                header('Location: ' . BASE_URL . '/user-form.php?id=' . $newUserId);
                                exit;
                            }
                        } catch (Exception $e) {
                            $error = 'Errore durante il salvataggio: ' . $e->getMessage();
                        }
                    }
                }
            }
        }
    }
}

// Check for success message from redirect
if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

$pageTitle = $isEdit ? 'Modifica Utente' : 'Nuovo Utente';
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
            <h3><?php echo $isEdit ? 'Modifica Utente' : 'Nuovo Utente'; ?></h3>
        </div>
        <div class="card-body">
            <form method="POST" data-validate id="userForm">
                <input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRFToken(); ?>">
                
                <div class="form-grid form-grid-2">
                    <div class="form-group">
                        <label class="form-label required">Username</label>
                        <input type="text" name="username" class="form-control" 
                               value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" 
                               <?php echo $isEdit ? 'readonly' : 'required'; ?>>
                        <?php if ($isEdit): ?>
                        <span class="form-text">Lo username non può essere modificato</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label required">Email</label>
                        <input type="email" name="email" class="form-control" 
                               value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label required">Nome Completo</label>
                    <input type="text" name="full_name" class="form-control" 
                           value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required>
                </div>
                
                <div class="form-grid form-grid-2">
                    <div class="form-group">
                        <label class="form-label required">Ruolo</label>
                        <select name="role" class="form-control" required>
                            <option value="admin" <?php echo ($user['role'] ?? '') === 'admin' ? 'selected' : ''; ?>>Amministratore</option>
                            <option value="technician" <?php echo ($user['role'] ?? '') === 'technician' ? 'selected' : ''; ?>>Tecnico</option>
                            <option value="viewer" <?php echo ($user['role'] ?? 'viewer') === 'viewer' ? 'selected' : ''; ?>>Visualizzatore</option>
                        </select>
                        <span class="form-text">
                            <strong>Admin:</strong> Accesso completo<br>
                            <strong>Tecnico:</strong> Gestisce manutenzioni e asset<br>
                            <strong>Visualizzatore:</strong> Solo visualizzazione
                        </span>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Telefono</label>
                        <input type="tel" name="phone" class="form-control" 
                               value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                    </div>
                </div>
                
                <hr style="margin: 32px 0; border: none; border-top: 1px solid var(--color-border);">
                
                <h4 style="margin-bottom: 24px; color: var(--color-dark);">
                    <?php echo $isEdit ? 'Cambio Password' : 'Password'; ?>
                </h4>
                <?php if ($isEdit): ?>
                <p style="color: var(--color-text-light); margin-bottom: 24px;">Lascia vuoto per mantenere la password attuale</p>
                <?php endif; ?>
                
                <div class="form-grid form-grid-2">
                    <div class="form-group">
                        <label class="form-label <?php echo !$isEdit ? 'required' : ''; ?>">Password</label>
                        <input type="password" name="password" class="form-control" 
                               minlength="8" <?php echo !$isEdit ? 'required' : ''; ?>>
                        <span class="form-text">Minimo 8 caratteri</span>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label <?php echo !$isEdit ? 'required' : ''; ?>">Conferma Password</label>
                        <input type="password" name="confirm_password" class="form-control" <?php echo !$isEdit ? 'required' : ''; ?>>
                    </div>
                </div>
                
                <hr style="margin: 32px 0; border: none; border-top: 1px solid var(--color-border);">
                
                <div class="form-group">
                    <div class="form-check">
                        <input type="checkbox" name="is_active" id="is_active" class="form-check-input" 
                               value="1" <?php echo ($user['is_active'] ?? 1) ? 'checked' : ''; ?>>
                        <label for="is_active" class="form-check-label">Utente attivo</label>
                    </div>
                    <span class="form-text">Gli utenti disattivati non possono accedere al sistema</span>
                </div>
                
                <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 32px;">
                    <button type="button" class="btn btn-outline" onclick="window.location.href='<?php echo BASE_URL; ?>/users.php'">
                        <i class="fas fa-times"></i>
                        Annulla
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        <?php echo $isEdit ? 'Salva Modifiche' : 'Crea Utente'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Password validation
    $('[name="password"], [name="confirm_password"]').on('input', function() {
        const password = $('[name="password"]').val();
        const confirm = $('[name="confirm_password"]').val();
        
        if (password && confirm && password !== confirm) {
            $('[name="confirm_password"]').addClass('error');
        } else {
            $('[name="confirm_password"]').removeClass('error');
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>