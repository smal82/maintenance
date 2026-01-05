<?php
// plugins.php
require_once 'config.php';

$auth = new Auth();
$auth->requireRole('admin');

$pluginManager = new PluginManager();
$plugins = $pluginManager->getAllPlugins();

$success = $_SESSION['success_message'] ?? '';
$error = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

$pageTitle = 'Gestione Plugin';
include 'includes/header.php';
?>

<div class="container">
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
            <h3>Plugin Installati</h3>
        </div>
        <div class="card-body">
            <?php if (empty($plugins)): ?>
            <div class="empty-state">
                <i class="fas fa-puzzle-piece"></i>
                <p>Nessun plugin installato</p>
                <p style="font-size: 0.875rem; color: var(--color-text-light); margin-top: 8px;">
                    Aggiungi plugin nella cartella <code>/plugins/</code>
                </p>
            </div>
            <?php else: ?>
                <?php foreach ($plugins as $plugin): ?>
                <div class="card" style="margin-bottom: 16px;">
                    <div class="card-body">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div style="flex: 1;">
                                <h4 style="margin-bottom: 8px;">
                                    <?php echo htmlspecialchars($plugin['display_name']); ?>
                                    <?php if ($plugin['is_active']): ?>
                                        <span class="badge badge-success" style="margin-left: 8px;">Attivo</span>
                                    <?php else: ?>
                                        <span class="badge" style="background-color: #ecf0f1; color: #7f8c8d; margin-left: 8px;">Disattivato</span>
                                    <?php endif; ?>
                                </h4>
                                <p style="color: var(--color-text-light); margin: 8px 0;">
                                    <?php echo htmlspecialchars($plugin['description'] ?? ''); ?>
                                </p>
                                <small style="color: var(--color-text-lighter);">
                                    Versione: <?php echo htmlspecialchars($plugin['version'] ?? 'N/D'); ?> | 
                                    Autore: <?php echo htmlspecialchars($plugin['author'] ?? 'N/D'); ?>
                                </small>
                            </div>
                            <div style="display: flex; gap: 12px;">
                                <?php if ($plugin['is_active']): ?>
                                <a href="<?php echo BASE_URL; ?>/plugin-toggle.php?id=<?php echo $plugin['id']; ?>&action=deactivate" class="btn btn-outline">
                                    <i class="fas fa-pause"></i> Disattiva
                                </a>
                                <?php else: ?>
                                <a href="<?php echo BASE_URL; ?>/plugin-toggle.php?id=<?php echo $plugin['id']; ?>&action=activate" class="btn btn-primary">
                                    <i class="fas fa-play"></i> Attiva
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>