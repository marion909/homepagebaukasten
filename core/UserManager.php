<?php
/**
 * Erweiterte Benutzer- und Rechteverwaltung
 */

class UserManager {
    private $db;
    private $settings;
    
    // Verfügbare Benutzerrollen
    const ROLES = [
        'admin' => [
            'name' => 'Administrator',
            'permissions' => ['all']
        ],
        'editor' => [
            'name' => 'Redakteur',
            'permissions' => [
                'manage_content', 'publish_content', 'delete_content',
                'manage_media', 'manage_categories', 'manage_tags'
            ]
        ],
        'author' => [
            'name' => 'Autor',
            'permissions' => [
                'create_content', 'edit_own_content', 'publish_own_content',
                'upload_media', 'use_categories', 'use_tags'
            ]
        ],
        'contributor' => [
            'name' => 'Mitarbeiter',
            'permissions' => [
                'create_content', 'edit_own_content',
                'upload_media'
            ]
        ],
        'subscriber' => [
            'name' => 'Abonnent',
            'permissions' => [
                'read_content', 'comment'
            ]
        ]
    ];
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->settings = Settings::getInstance();
    }
    
    /**
     * Neuen Benutzer erstellen
     */
    public function createUser($userData) {
        // Eingaben validieren
        $validation = $this->validateUserData($userData);
        if (!$validation['valid']) {
            return ['success' => false, 'errors' => $validation['errors']];
        }
        
        // Prüfen ob Username/Email bereits existiert
        if ($this->userExists($userData['username'], $userData['email'])) {
            return ['success' => false, 'errors' => ['Benutzername oder E-Mail bereits vergeben']];
        }
        
        // Passwort hashen
        $passwordHash = password_hash($userData['password'], PASSWORD_DEFAULT);
        
        // User erstellen
        $userId = $this->db->execute("
            INSERT INTO users (username, email, password_hash, role, status, created_at)
            VALUES (?, ?, ?, ?, 'active', NOW())
        ", [
            $userData['username'],
            $userData['email'],
            $passwordHash,
            $userData['role'] ?? 'subscriber'
        ]);
        
        if ($userId) {
            // Benutzer-Profil erstellen
            $this->createUserProfile($userId, $userData);
            
            // Willkommens-E-Mail senden (optional)
            if ($userData['send_welcome'] ?? false) {
                $this->sendWelcomeEmail($userId);
            }
            
            return ['success' => true, 'user_id' => $userId];
        }
        
        return ['success' => false, 'errors' => ['Fehler beim Erstellen des Benutzers']];
    }
    
    /**
     * Benutzer aktualisieren
     */
    public function updateUser($userId, $userData) {
        $user = $this->getUser($userId);
        if (!$user) {
            return ['success' => false, 'errors' => ['Benutzer nicht gefunden']];
        }
        
        // Eingaben validieren
        $validation = $this->validateUserData($userData, $userId);
        if (!$validation['valid']) {
            return ['success' => false, 'errors' => $validation['errors']];
        }
        
        $updateFields = [];
        $updateValues = [];
        
        // Username aktualisieren
        if (isset($userData['username']) && $userData['username'] !== $user['username']) {
            $updateFields[] = 'username = ?';
            $updateValues[] = $userData['username'];
        }
        
        // E-Mail aktualisieren
        if (isset($userData['email']) && $userData['email'] !== $user['email']) {
            $updateFields[] = 'email = ?';
            $updateValues[] = $userData['email'];
        }
        
        // Rolle aktualisieren
        if (isset($userData['role']) && $userData['role'] !== $user['role']) {
            $updateFields[] = 'role = ?';
            $updateValues[] = $userData['role'];
        }
        
        // Status aktualisieren
        if (isset($userData['status']) && $userData['status'] !== $user['status']) {
            $updateFields[] = 'status = ?';
            $updateValues[] = $userData['status'];
        }
        
        // Passwort aktualisieren (wenn angegeben)
        if (!empty($userData['password'])) {
            $updateFields[] = 'password_hash = ?';
            $updateValues[] = password_hash($userData['password'], PASSWORD_DEFAULT);
        }
        
        if (!empty($updateFields)) {
            $updateValues[] = $userId;
            
            $success = $this->db->execute("
                UPDATE users SET " . implode(', ', $updateFields) . ", updated_at = NOW()
                WHERE id = ?
            ", $updateValues);
            
            if ($success) {
                // Profil aktualisieren
                $this->updateUserProfile($userId, $userData);
                return ['success' => true];
            }
        }
        
        return ['success' => false, 'errors' => ['Keine Änderungen vorgenommen']];
    }
    
    /**
     * Benutzer löschen
     */
    public function deleteUser($userId) {
        $user = $this->getUser($userId);
        if (!$user) {
            return false;
        }
        
        // Inhalte des Benutzers einem anderen Benutzer zuweisen oder löschen
        $action = $this->settings->get('user_deletion_action', 'reassign');
        
        if ($action === 'reassign') {
            $defaultUserId = $this->settings->get('default_user_id', 1);
            
            // Seiten zuweisen
            $this->db->execute("UPDATE pages SET author_id = ? WHERE author_id = ?", [$defaultUserId, $userId]);
            
            // Blog-Posts zuweisen
            $this->db->execute("UPDATE blog_posts SET author_id = ? WHERE author_id = ?", [$defaultUserId, $userId]);
        } elseif ($action === 'delete') {
            // Inhalte löschen
            $this->db->execute("DELETE FROM pages WHERE author_id = ?", [$userId]);
            $this->db->execute("DELETE FROM blog_posts WHERE author_id = ?", [$userId]);
        }
        
        // Benutzer-Sessions löschen
        $this->db->execute("DELETE FROM user_sessions WHERE user_id = ?", [$userId]);
        
        // Benutzer-Profil löschen
        $this->db->execute("DELETE FROM user_profiles WHERE user_id = ?", [$userId]);
        
        // Benutzer löschen
        return $this->db->execute("DELETE FROM users WHERE id = ?", [$userId]);
    }
    
    /**
     * Benutzer abrufen
     */
    public function getUser($userId) {
        return $this->db->fetchOne("
            SELECT u.*, p.first_name, p.last_name, p.bio, p.avatar, p.website,
                   p.social_links, p.preferences
            FROM users u
            LEFT JOIN user_profiles p ON u.id = p.user_id
            WHERE u.id = ?
        ", [$userId]);
    }
    
    /**
     * Alle Benutzer abrufen
     */
    public function getAllUsers($filters = []) {
        $whereConditions = [];
        $params = [];
        
        // Filter anwenden
        if (isset($filters['role'])) {
            $whereConditions[] = 'u.role = ?';
            $params[] = $filters['role'];
        }
        
        if (isset($filters['status'])) {
            $whereConditions[] = 'u.status = ?';
            $params[] = $filters['status'];
        }
        
        if (isset($filters['search'])) {
            $whereConditions[] = '(u.username LIKE ? OR u.email LIKE ? OR p.first_name LIKE ? OR p.last_name LIKE ?)';
            $searchTerm = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }
        
        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        $orderBy = $filters['order_by'] ?? 'u.created_at';
        $orderDir = $filters['order_dir'] ?? 'DESC';
        
        return $this->db->fetchAll("
            SELECT u.*, p.first_name, p.last_name, p.avatar,
                   (SELECT COUNT(*) FROM pages WHERE author_id = u.id) as page_count,
                   (SELECT COUNT(*) FROM blog_posts WHERE author_id = u.id) as post_count
            FROM users u
            LEFT JOIN user_profiles p ON u.id = p.user_id
            {$whereClause}
            ORDER BY {$orderBy} {$orderDir}
        ", $params);
    }
    
    /**
     * Benutzer-Statistiken
     */
    public function getUserStats() {
        $stats = [
            'total_users' => 0,
            'active_users' => 0,
            'inactive_users' => 0,
            'roles' => []
        ];
        
        // Gesamtzahl
        $result = $this->db->fetchOne("SELECT COUNT(*) as count FROM users");
        $stats['total_users'] = $result['count'];
        
        // Nach Status
        $statusCounts = $this->db->fetchAll("
            SELECT status, COUNT(*) as count 
            FROM users 
            GROUP BY status
        ");
        
        foreach ($statusCounts as $row) {
            if ($row['status'] === 'active') {
                $stats['active_users'] = $row['count'];
            } else {
                $stats['inactive_users'] += $row['count'];
            }
        }
        
        // Nach Rollen
        $roleCounts = $this->db->fetchAll("
            SELECT role, COUNT(*) as count 
            FROM users 
            GROUP BY role
        ");
        
        foreach ($roleCounts as $row) {
            $stats['roles'][$row['role']] = [
                'count' => $row['count'],
                'name' => self::ROLES[$row['role']]['name'] ?? $row['role']
            ];
        }
        
        return $stats;
    }
    
    /**
     * Benutzer-Sessions verwalten
     */
    public function getUserSessions($userId) {
        return $this->db->fetchAll("
            SELECT * FROM user_sessions 
            WHERE user_id = ? 
            ORDER BY last_activity DESC
        ", [$userId]);
    }
    
    public function revokeSession($sessionId) {
        return $this->db->execute("DELETE FROM user_sessions WHERE id = ?", [$sessionId]);
    }
    
    public function revokeAllSessions($userId, $exceptCurrentSession = null) {
        $query = "DELETE FROM user_sessions WHERE user_id = ?";
        $params = [$userId];
        
        if ($exceptCurrentSession) {
            $query .= " AND id != ?";
            $params[] = $exceptCurrentSession;
        }
        
        return $this->db->execute($query, $params);
    }
    
    /**
     * Berechtigungen prüfen
     */
    public function hasPermission($userId, $permission) {
        $user = $this->getUser($userId);
        if (!$user) {
            return false;
        }
        
        $userRole = $user['role'];
        $rolePermissions = self::ROLES[$userRole]['permissions'] ?? [];
        
        // Admin hat alle Rechte
        if (in_array('all', $rolePermissions)) {
            return true;
        }
        
        return in_array($permission, $rolePermissions);
    }
    
    public function canEditContent($userId, $contentAuthorId) {
        $user = $this->getUser($userId);
        if (!$user) {
            return false;
        }
        
        // Admin und Editor können alles bearbeiten
        if (in_array($user['role'], ['admin', 'editor'])) {
            return true;
        }
        
        // Benutzer kann eigene Inhalte bearbeiten
        if ($this->hasPermission($userId, 'edit_own_content') && $userId == $contentAuthorId) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Aktivitätslog
     */
    public function logActivity($userId, $action, $details = []) {
        return $this->db->execute("
            INSERT INTO user_activity_log (user_id, action, details, ip_address, user_agent, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ", [
            $userId,
            $action,
            json_encode($details),
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    }
    
    public function getActivityLog($userId, $limit = 50) {
        return $this->db->fetchAll("
            SELECT * FROM user_activity_log 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ", [$userId, $limit]);
    }
    
    /**
     * Passwort-Reset
     */
    public function requestPasswordReset($email) {
        $user = $this->db->fetchOne("SELECT * FROM users WHERE email = ? AND status = 'active'", [$email]);
        if (!$user) {
            return false;
        }
        
        // Reset-Token generieren
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Token speichern
        $this->db->execute("
            INSERT INTO password_reset_tokens (user_id, token, expires_at, created_at)
            VALUES (?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
            token = VALUES(token),
            expires_at = VALUES(expires_at),
            created_at = NOW()
        ", [$user['id'], $token, $expires]);
        
        // E-Mail senden
        return $this->sendPasswordResetEmail($user, $token);
    }
    
    public function resetPassword($token, $newPassword) {
        // Token validieren
        $resetData = $this->db->fetchOne("
            SELECT * FROM password_reset_tokens 
            WHERE token = ? AND expires_at > NOW()
        ", [$token]);
        
        if (!$resetData) {
            return false;
        }
        
        // Passwort aktualisieren
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $success = $this->db->execute("
            UPDATE users SET password_hash = ?, updated_at = NOW()
            WHERE id = ?
        ", [$passwordHash, $resetData['user_id']]);
        
        if ($success) {
            // Token löschen
            $this->db->execute("DELETE FROM password_reset_tokens WHERE token = ?", [$token]);
            
            // Aktivität loggen
            $this->logActivity($resetData['user_id'], 'password_reset');
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Zwei-Faktor-Authentifizierung
     */
    public function enable2FA($userId) {
        $secret = $this->generate2FASecret();
        
        $success = $this->db->execute("
            UPDATE users SET two_factor_secret = ?, two_factor_enabled = 1
            WHERE id = ?
        ", [$secret, $userId]);
        
        if ($success) {
            return $secret;
        }
        
        return false;
    }
    
    public function disable2FA($userId) {
        return $this->db->execute("
            UPDATE users SET two_factor_secret = NULL, two_factor_enabled = 0
            WHERE id = ?
        ", [$userId]);
    }
    
    public function verify2FA($userId, $code) {
        $user = $this->getUser($userId);
        if (!$user || !$user['two_factor_enabled']) {
            return false;
        }
        
        // TOTP-Code verifizieren (vereinfachte Implementierung)
        return $this->verifyTOTP($user['two_factor_secret'], $code);
    }
    
    /**
     * Bulk-Operationen
     */
    public function bulkUpdateUsers($userIds, $updates) {
        if (empty($userIds) || empty($updates)) {
            return false;
        }
        
        $updateFields = [];
        $updateValues = [];
        
        if (isset($updates['role'])) {
            $updateFields[] = 'role = ?';
            $updateValues[] = $updates['role'];
        }
        
        if (isset($updates['status'])) {
            $updateFields[] = 'status = ?';
            $updateValues[] = $updates['status'];
        }
        
        if (empty($updateFields)) {
            return false;
        }
        
        $placeholders = str_repeat('?,', count($userIds) - 1) . '?';
        $params = array_merge($updateValues, $userIds);
        
        return $this->db->execute("
            UPDATE users 
            SET " . implode(', ', $updateFields) . ", updated_at = NOW()
            WHERE id IN ({$placeholders})
        ", $params);
    }
    
    /**
     * Helper-Methoden
     */
    private function validateUserData($data, $excludeUserId = null) {
        $errors = [];
        
        // Username validieren
        if (empty($data['username'])) {
            $errors[] = 'Benutzername ist erforderlich';
        } elseif (strlen($data['username']) < 3) {
            $errors[] = 'Benutzername muss mindestens 3 Zeichen lang sein';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $data['username'])) {
            $errors[] = 'Benutzername darf nur Buchstaben, Zahlen und Unterstriche enthalten';
        }
        
        // E-Mail validieren
        if (empty($data['email'])) {
            $errors[] = 'E-Mail ist erforderlich';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Ungültige E-Mail-Adresse';
        }
        
        // Passwort validieren (nur bei Erstellung oder wenn angegeben)
        if (isset($data['password']) && !empty($data['password'])) {
            if (strlen($data['password']) < 8) {
                $errors[] = 'Passwort muss mindestens 8 Zeichen lang sein';
            }
        } elseif ($excludeUserId === null) { // Nur bei Erstellung erforderlich
            $errors[] = 'Passwort ist erforderlich';
        }
        
        // Rolle validieren
        if (isset($data['role']) && !array_key_exists($data['role'], self::ROLES)) {
            $errors[] = 'Ungültige Benutzerrolle';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    private function userExists($username, $email, $excludeUserId = null) {
        $query = "SELECT COUNT(*) as count FROM users WHERE (username = ? OR email = ?)";
        $params = [$username, $email];
        
        if ($excludeUserId) {
            $query .= " AND id != ?";
            $params[] = $excludeUserId;
        }
        
        $result = $this->db->fetchOne($query, $params);
        return $result['count'] > 0;
    }
    
    private function createUserProfile($userId, $userData) {
        return $this->db->execute("
            INSERT INTO user_profiles (user_id, first_name, last_name, bio, website, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ", [
            $userId,
            $userData['first_name'] ?? '',
            $userData['last_name'] ?? '',
            $userData['bio'] ?? '',
            $userData['website'] ?? ''
        ]);
    }
    
    private function updateUserProfile($userId, $userData) {
        $updateFields = [];
        $updateValues = [];
        
        $profileFields = ['first_name', 'last_name', 'bio', 'website', 'avatar'];
        
        foreach ($profileFields as $field) {
            if (isset($userData[$field])) {
                $updateFields[] = "{$field} = ?";
                $updateValues[] = $userData[$field];
            }
        }
        
        if (!empty($updateFields)) {
            $updateValues[] = $userId;
            
            return $this->db->execute("
                UPDATE user_profiles 
                SET " . implode(', ', $updateFields) . ", updated_at = NOW()
                WHERE user_id = ?
            ", $updateValues);
        }
        
        return true;
    }
    
    private function sendWelcomeEmail($userId) {
        // E-Mail-Versand implementieren
        // Vereinfacht - in Produktionsumgebung würde hier ein E-Mail-Service verwendet
        return true;
    }
    
    private function sendPasswordResetEmail($user, $token) {
        // E-Mail-Versand implementieren
        // Vereinfacht - in Produktionsumgebung würde hier ein E-Mail-Service verwendet
        $resetLink = $this->settings->get('site_url') . '/admin/reset-password.php?token=' . $token;
        // E-Mail mit $resetLink senden
        return true;
    }
    
    private function generate2FASecret() {
        // TOTP-Secret generieren (vereinfacht)
        return base32_encode(random_bytes(20));
    }
    
    private function verifyTOTP($secret, $code) {
        // TOTP-Verifizierung implementieren (vereinfacht)
        // In Produktionsumgebung würde hier eine TOTP-Bibliothek verwendet
        return true;
    }
}

// Helper-Funktion für Base32-Encoding
function base32_encode($data) {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $output = '';
    $v = 0;
    $vbits = 0;
    
    for ($i = 0; $i < strlen($data); $i++) {
        $v = ($v << 8) | ord($data[$i]);
        $vbits += 8;
        
        while ($vbits >= 5) {
            $output .= $alphabet[($v >> ($vbits - 5)) & 31];
            $vbits -= 5;
        }
    }
    
    if ($vbits > 0) {
        $output .= $alphabet[($v << (5 - $vbits)) & 31];
    }
    
    return $output;
}
