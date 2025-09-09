<?php
session_start();

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function login($username, $password) {
        $user = $this->db->fetchOne(
            "SELECT * FROM users WHERE username = ? AND active = 1", 
            [$username]
        );
        
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            return true;
        }
        return false;
    }
    
    public function logout() {
        session_destroy();
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: login.php');
            exit;
        }
    }
    
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return $this->db->fetchOne(
            "SELECT id, username, email, role FROM users WHERE id = ?", 
            [$_SESSION['user_id']]
        );
    }
    
    public function hasRole($role) {
        return isset($_SESSION['role']) && $_SESSION['role'] === $role;
    }
    
    public function hasAnyRole($roles) {
        if (!isset($_SESSION['role'])) return false;
        return in_array($_SESSION['role'], $roles);
    }
    
    public function canManagePages() {
        return $this->hasAnyRole(['admin', 'editor', 'moderator']);
    }
    
    public function canManageUsers() {
        return $this->hasRole('admin');
    }
    
    public function canManageComments() {
        return $this->hasAnyRole(['admin', 'moderator']);
    }
    
    public function canPublishPosts() {
        return $this->hasAnyRole(['admin', 'editor']);
    }
    
    public function canManageBlog() {
        return $this->hasAnyRole(['admin', 'editor']);
    }
    
    public function canManageSystem() {
        return $this->hasRole('admin');
    }
    
    public function requireRole($role) {
        if (!$this->hasRole($role)) {
            header('HTTP/1.1 403 Forbidden');
            die('Zugriff verweigert. Erforderliche Rolle: ' . $role);
        }
    }
    
    public function requireAnyRole($roles) {
        if (!$this->hasAnyRole($roles)) {
            header('HTTP/1.1 403 Forbidden');
            die('Zugriff verweigert. Erforderliche Rollen: ' . implode(', ', $roles));
        }
    }
    
    public function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    public function verifyCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}
