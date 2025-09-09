<?php
require_once '../config.php';
require_once '../core/db.php';
require_once '../core/auth.php';

// Authentifizierung prüfen
$auth = new Auth();
$auth->requireLogin();

if (!$auth->canManageUsers()) {
    http_response_code(403);
    echo json_encode(['error' => 'Nicht autorisiert']);
    exit;
}

$db = Database::getInstance();

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_user':
        $userId = intval($_GET['id'] ?? 0);
        if ($userId > 0) {
            $user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);
            if ($user) {
                // Remove sensitive data
                unset($user['password_hash']);
                echo json_encode($user);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Benutzer nicht gefunden']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Ungültige Benutzer-ID']);
        }
        break;
        
    case 'search_users':
        $search = $_GET['search'] ?? '';
        $role = $_GET['role'] ?? '';
        $status = $_GET['status'] ?? '';
        
        $sql = "SELECT id, username, email, role, active, created_at, last_login FROM users WHERE 1=1";
        $params = [];
        
        if ($search) {
            $sql .= " AND (username LIKE ? OR email LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        if ($role) {
            $sql .= " AND role = ?";
            $params[] = $role;
        }
        
        if ($status === 'active') {
            $sql .= " AND active = 1";
        } elseif ($status === 'inactive') {
            $sql .= " AND active = 0";
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        $users = $db->fetchAll($sql, $params);
        echo json_encode($users);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Ungültige Aktion']);
        break;
}
?>
