<?php
class User {
    private static $db;
    
    public static function init() {
        self::$db = Database::getInstance();
    }
    
    public static function getAll() {
        if (!self::$db) self::init();
        
        return self::$db->fetchAll("SELECT id, username, email, role, profile_image, created_at, active FROM users ORDER BY created_at DESC");
    }
    
    public static function getById($id) {
        if (!self::$db) self::init();
        
        return self::$db->fetchOne("SELECT * FROM users WHERE id = ?", [$id]);
    }
    
    public static function create($data) {
        if (!self::$db) self::init();
        
        // Validate required fields
        if (empty($data['username']) || empty($data['email']) || empty($data['password'])) {
            throw new Exception('Username, Email und Passwort sind erforderlich');
        }
        
        // Check if username or email already exists
        $existing = self::$db->fetchOne("SELECT id FROM users WHERE username = ? OR email = ?", 
                                       [$data['username'], $data['email']]);
        if ($existing) {
            throw new Exception('Benutzername oder E-Mail bereits vergeben');
        }
        
        $sql = "INSERT INTO users (username, email, password_hash, role, profile_image, bio, social_links, active) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $data['username'],
            $data['email'],
            password_hash($data['password'], PASSWORD_DEFAULT),
            $data['role'] ?? 'editor',
            $data['profile_image'] ?? null,
            $data['bio'] ?? null,
            !empty($data['social_links']) ? json_encode($data['social_links']) : null,
            $data['active'] ?? 1
        ];
        
        self::$db->query($sql, $params);
        return self::$db->lastInsertId();
    }
    
    public static function update($id, $data) {
        if (!self::$db) self::init();
        
        $fields = [];
        $params = [];
        
        if (isset($data['username'])) {
            // Check if username is taken by another user
            $existing = self::$db->fetchOne("SELECT id FROM users WHERE username = ? AND id != ?", 
                                           [$data['username'], $id]);
            if ($existing) {
                throw new Exception('Benutzername bereits vergeben');
            }
            $fields[] = "username = ?";
            $params[] = $data['username'];
        }
        
        if (isset($data['email'])) {
            // Check if email is taken by another user
            $existing = self::$db->fetchOne("SELECT id FROM users WHERE email = ? AND id != ?", 
                                           [$data['email'], $id]);
            if ($existing) {
                throw new Exception('E-Mail bereits vergeben');
            }
            $fields[] = "email = ?";
            $params[] = $data['email'];
        }
        
        if (isset($data['password']) && !empty($data['password'])) {
            $fields[] = "password_hash = ?";
            $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        if (isset($data['role'])) {
            $fields[] = "role = ?";
            $params[] = $data['role'];
        }
        
        if (isset($data['profile_image'])) {
            $fields[] = "profile_image = ?";
            $params[] = $data['profile_image'];
        }
        
        if (isset($data['bio'])) {
            $fields[] = "bio = ?";
            $params[] = $data['bio'];
        }
        
        if (isset($data['social_links'])) {
            $fields[] = "social_links = ?";
            $params[] = is_array($data['social_links']) ? json_encode($data['social_links']) : $data['social_links'];
        }
        
        if (isset($data['active'])) {
            $fields[] = "active = ?";
            $params[] = $data['active'];
        }
        
        if (empty($fields)) {
            return true; // Nothing to update
        }
        
        $sql = "UPDATE users SET " . implode(", ", $fields) . " WHERE id = ?";
        $params[] = $id;
        
        return self::$db->query($sql, $params);
    }
    
    public static function delete($id) {
        if (!self::$db) self::init();
        
        // Don't allow deleting the last admin
        $user = self::getById($id);
        if ($user['role'] === 'admin') {
            $adminCount = self::$db->fetchOne("SELECT COUNT(*) as count FROM users WHERE role = 'admin' AND active = 1")['count'];
            if ($adminCount <= 1) {
                throw new Exception('Der letzte Administrator kann nicht gelöscht werden');
            }
        }
        
        return self::$db->query("DELETE FROM users WHERE id = ?", [$id]);
    }
    
    public static function getRoleDisplayName($role) {
        $roles = [
            'admin' => 'Administrator',
            'moderator' => 'Moderator',
            'editor' => 'Redakteur'
        ];
        
        return $roles[$role] ?? $role;
    }
    
    public static function getAvailableRoles() {
        return [
            'admin' => 'Administrator - Vollzugriff auf alle Funktionen',
            'moderator' => 'Moderator - Kann Kommentare und Inhalte verwalten',
            'editor' => 'Redakteur - Kann Inhalte erstellen und bearbeiten'
        ];
    }
}
?>
