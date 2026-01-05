<?php
// includes/header.php
require_once 'config.php';

$auth = new Auth();
$auth->requireLogin();

if (!$auth->hasPermission('create_asset') && !$auth->hasRole('admin')) {
    die('Accesso negato');
}

$db = Database::getInstance();
$user = $auth->getCurrentUser();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Dashboard'; ?> - <?php echo SITE_NAME; ?></title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/reset.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/variables.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/layout.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/components.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/forms.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/tables.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/responsive.css">
</head>
<body>
    <div class="app-wrapper">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <i class="fas fa-tools"></i>
                    <span><?php echo SITE_NAME; ?></span>
                </div>
                <button class="sidebar-toggle" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            
            <nav class="sidebar-nav">
                <ul>
                    <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                        <a href="<?php echo BASE_URL; ?>/index.php">
                            <i class="fas fa-home"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    
                    <li class="nav-section">Asset</li>
                    <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'assets.php' ? 'active' : ''; ?>">
                        <a href="<?php echo BASE_URL; ?>/assets.php">
                            <i class="fas fa-box"></i>
                            <span>Gestione Asset</span>
                        </a>
                    </li>
                    <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'asset-map.php' ? 'active' : ''; ?>">
                        <a href="<?php echo BASE_URL; ?>/asset-map.php">
                            <i class="fas fa-map-marked-alt"></i>
                            <span>Mappa Asset</span>
                        </a>
                    </li>
                    <?php if ($auth->hasRole('admin')): ?>
                    <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>">
                        <a href="<?php echo BASE_URL; ?>/categories.php">
                            <i class="fas fa-folder"></i>
                            <span>Categorie</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <li class="nav-section">Manutenzioni</li>
                    <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'maintenances.php' ? 'active' : ''; ?>">
                        <a href="<?php echo BASE_URL; ?>/maintenances.php">
                            <i class="fas fa-wrench"></i>
                            <span>Manutenzioni</span>
                        </a>
                    </li>
                    <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'calendar.php' ? 'active' : ''; ?>">
                        <a href="<?php echo BASE_URL; ?>/calendar.php">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Calendario</span>
                        </a>
                    </li>
                    <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'scheduled-maintenances.php' ? 'active' : ''; ?>">
                        <a href="<?php echo BASE_URL; ?>/scheduled-maintenances.php">
                            <i class="fas fa-clock"></i>
                            <span>Programmate</span>
                        </a>
                    </li>
                    <?php if ($auth->hasRole('admin')): ?>
                    <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'maintenance-types.php' ? 'active' : ''; ?>">
                        <a href="<?php echo BASE_URL; ?>/maintenance-types.php">
                            <i class="fas fa-tags"></i>
                            <span>Tipi Manutenzione</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <li class="nav-section">Magazzino</li>
                    <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'spare-parts.php' ? 'active' : ''; ?>">
                        <a href="<?php echo BASE_URL; ?>/spare-parts.php">
                            <i class="fas fa-cog"></i>
                            <span>Ricambi</span>
                        </a>
                    </li>
                    
                    <?php if ($auth->hasRole('admin')): ?>
                    <li class="nav-section">Amministrazione</li>
                    <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
                        <a href="<?php echo BASE_URL; ?>/users.php">
                            <i class="fas fa-users"></i>
                            <span>Utenti</span>
                        </a>
                    </li>
                    <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'plugins.php' ? 'active' : ''; ?>">
                        <a href="<?php echo BASE_URL; ?>/plugins.php">
                            <i class="fas fa-puzzle-piece"></i>
                            <span>Plugin</span>
                        </a>
                    </li>
                    <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
                        <a href="<?php echo BASE_URL; ?>/settings.php">
                            <i class="fas fa-cog"></i>
                            <span>Impostazioni</span>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Bar -->
            <header class="topbar">
                <div class="topbar-left">
                    <button class="mobile-menu-toggle" id="mobileMenuToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1 class="page-title"><?php echo $pageTitle ?? 'Dashboard'; ?></h1>
                </div>
                
                <div class="topbar-right">
                    <div class="topbar-item notifications-dropdown">
                        <button class="btn-icon" id="notificationsBtn">
                            <i class="fas fa-bell"></i>
                            <span class="badge-count" id="notificationCount">0</span>
                        </button>
                        <div class="dropdown-menu" id="notificationsMenu">
                            <div class="dropdown-header">
                                <h4>Notifiche</h4>
                            </div>
                            <div class="notifications-list" id="notificationsList">
                                <div class="empty-state">
                                    <i class="fas fa-bell-slash"></i>
                                    <p>Nessuna notifica</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="topbar-item user-dropdown">
                        <button class="user-menu-btn" id="userMenuBtn">
                            <div class="user-avatar">
                                <?php if (!empty($user['avatar'])): ?>
                                    <img src="<?php echo BASE_URL . '/uploads/avatars/' . $user['avatar']; ?>" alt="Avatar">
                                <?php else: ?>
                                    <i class="fas fa-user"></i>
                                <?php endif; ?>
                            </div>
                            <span class="user-name"><?php echo htmlspecialchars($user['full_name']); ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="dropdown-menu" id="userMenu">
                            <a href="<?php echo BASE_URL; ?>/profile.php" class="dropdown-item">
                                <i class="fas fa-user"></i> Profilo
                            </a>
                            <a href="<?php echo BASE_URL; ?>/logout.php" class="dropdown-item">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Page Content -->
            <main class="page-content"><?php // Content will be inserted here ?>