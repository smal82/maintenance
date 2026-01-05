<?php
// classes/Auth.php

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function login($username, $password) {
        $user = $this->db->fetchOne(
            "SELECT * FROM users WHERE username = :username AND is_active = 1",
            ['username' => $username]
        );
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            
            $this->logActivity($user['id'], 'login', null, null, 'User logged in');
            
            return true;
        }
        
        return false;
    }
    
    public function logout() {
        $userId = $_SESSION['user_id'] ?? null;
        
        if ($userId) {
            $this->logActivity($userId, 'logout', null, null, 'User logged out');
        }
        
        session_destroy();
        return true;
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return $this->db->fetchOne(
            "SELECT * FROM users WHERE id = :id",
            ['id' => $_SESSION['user_id']]
        );
    }
    
    public function hasRole($role) {
        return isset($_SESSION['role']) && $_SESSION['role'] === $role;
    }
    
    public function hasPermission($permission) {
        $role = $_SESSION['role'] ?? null;
        
        $permissions = [
            'admin' => ['all'],
            'technician' => ['view_maintenances', 'create_maintenance', 'edit_maintenance', 'view_assets'],
            'viewer' => ['view_maintenances', 'view_assets']
        ];
        
        if ($role === 'admin') {
            return true;
        }
        
        return in_array($permission, $permissions[$role] ?? []);
    }
    
    public function generateCSRFToken() {
        if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
            $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
        }
        return $_SESSION[CSRF_TOKEN_NAME];
    }
    
    public function validateCSRFToken($token) {
        return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
    }
    
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: ' . BASE_URL . '/login.php');
            exit;
        }
    }
    
    public function requireRole($role) {
        $this->requireLogin();
        if (!$this->hasRole($role)) {
            header('HTTP/1.1 403 Forbidden');
            die('Accesso negato');
        }
    }
    
    private function logActivity($userId, $action, $entityType, $entityId, $description) {
        $this->db->insert('activity_log', [
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'description' => $description,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    }
}
?>