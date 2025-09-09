<?php
require_once '../config.php';
require_once '../core/db.php';
require_once '../core/auth.php';
require_once '../core/Settings.php';
require_once '../core/UserManager.php';

// Authentifizierung prüfen
$auth = new Auth();
$auth->requireLogin();

if (!$auth->canManageUsers()) {
    header('Location: index.php');
    exit;
}

$db = Database::getInstance();
$userManager = new UserManager();

$message = '';
$error = '';

// Handle form submissions
if ($_POST) {
    try {
        switch ($_POST['action']) {
            case 'create_user':
                $result = $userManager->createUser([
                    'username' => trim($_POST['username']),
                    'email' => trim($_POST['email']),
                    'password' => $_POST['password'],
                    'role' => $_POST['role'],
                    'active' => isset($_POST['active']) ? 1 : 0
                ]);
                
                if ($result['success']) {
                    $message = 'Benutzer erfolgreich erstellt';
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'update_user':
                $userData = [
                    'username' => trim($_POST['username']),
                    'email' => trim($_POST['email']),
                    'role' => $_POST['role'],
                    'active' => isset($_POST['active']) ? 1 : 0
                ];
                
                if (!empty($_POST['password'])) {
                    $userData['password'] = $_POST['password'];
                }
                
                $result = $userManager->updateUser($_POST['user_id'], $userData);
                
                if ($result['success']) {
                    $message = 'Benutzer erfolgreich aktualisiert';
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'delete_user':
                $result = $userManager->deleteUser($_POST['user_id']);
                
                if ($result['success']) {
                    $message = 'Benutzer erfolgreich gelöscht';
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'toggle_status':
                $result = $userManager->toggleUserStatus($_POST['user_id']);
                
                if ($result['success']) {
                    $message = 'Benutzerstatus erfolgreich geändert';
                } else {
                    $error = $result['message'];
                }
                break;
        }
    } catch (Exception $e) {
        $error = 'Fehler: ' . $e->getMessage();
    }
}

// Get users with pagination
$page = intval($_GET['page'] ?? 1);
$limit = 20;
$offset = ($page - 1) * $limit;

$users = $userManager->getUsers($limit, $offset);
$totalUsers = $userManager->getUserCount();
$totalPages = ceil($totalUsers / $limit);

// Get user statistics
$stats = $userManager->getUserStats();

$pageTitle = "User Manager";
$currentPage = "user-manager";
include 'header.php';
?>

<div class="user-manager">
    <div class="page-header">
        <h2>🧑‍💼 User Manager</h2>
        <button type="button" class="btn btn-primary" onclick="showCreateUserModal()">
            Neuen Benutzer erstellen
        </button>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- User Statistics -->
    <div class="stats-grid">
        <div class="stat-card">
            <h4>Gesamt Benutzer</h4>
            <div class="stat-value"><?= $stats['total'] ?></div>
        </div>
        <div class="stat-card">
            <h4>Aktive Benutzer</h4>
            <div class="stat-value"><?= $stats['active'] ?></div>
        </div>
        <div class="stat-card">
            <h4>Administratoren</h4>
            <div class="stat-value"><?= $stats['admins'] ?></div>
        </div>
        <div class="stat-card">
            <h4>Editoren</h4>
            <div class="stat-value"><?= $stats['editors'] ?></div>
        </div>
    </div>

    <!-- User Filters -->
    <div class="filters">
        <div class="filter-group">
            <label>Filter nach Rolle:</label>
            <select id="roleFilter" onchange="filterUsers()">
                <option value="">Alle Rollen</option>
                <option value="admin">Administrator</option>
                <option value="editor">Editor</option>
                <option value="author">Autor</option>
                <option value="subscriber">Abonnent</option>
            </select>
        </div>
        <div class="filter-group">
            <label>Filter nach Status:</label>
            <select id="statusFilter" onchange="filterUsers()">
                <option value="">Alle Status</option>
                <option value="active">Aktiv</option>
                <option value="inactive">Inaktiv</option>
            </select>
        </div>
        <div class="filter-group">
            <label>Suche:</label>
            <input type="text" id="searchUsers" placeholder="Benutzername oder E-Mail" onkeyup="searchUsers()">
        </div>
    </div>

    <!-- Users Table -->
    <div class="table-container">
        <table class="table" id="usersTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Benutzername</th>
                    <th>E-Mail</th>
                    <th>Rolle</th>
                    <th>Status</th>
                    <th>Erstellt</th>
                    <th>Letzter Login</th>
                    <th>Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr data-role="<?= $user['role'] ?>" data-status="<?= $user['active'] ? 'active' : 'inactive' ?>">
                    <td><?= $user['id'] ?></td>
                    <td>
                        <div class="user-info">
                            <strong><?= htmlspecialchars($user['username']) ?></strong>
                            <?php if ($user['id'] == $auth->getCurrentUser()['id']): ?>
                                <span class="badge badge-primary">Du</span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td>
                        <span class="role-badge role-<?= $user['role'] ?>">
                            <?= ucfirst($user['role']) ?>
                        </span>
                    </td>
                    <td>
                        <span class="status-badge status-<?= $user['active'] ? 'active' : 'inactive' ?>">
                            <?= $user['active'] ? 'Aktiv' : 'Inaktiv' ?>
                        </span>
                    </td>
                    <td><?= date('d.m.Y H:i', strtotime($user['created_at'])) ?></td>
                    <td>
                        <?= $user['last_login'] ? date('d.m.Y H:i', strtotime($user['last_login'])) : 'Nie' ?>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <button type="button" class="btn btn-sm btn-secondary" 
                                    onclick="editUser(<?= $user['id'] ?>)">
                                Bearbeiten
                            </button>
                            <?php if ($user['id'] != $auth->getCurrentUser()['id']): ?>
                            <button type="button" class="btn btn-sm btn-warning" 
                                    onclick="toggleUserStatus(<?= $user['id'] ?>, <?= $user['active'] ? 'false' : 'true' ?>)">
                                <?= $user['active'] ? 'Deaktivieren' : 'Aktivieren' ?>
                            </button>
                            <button type="button" class="btn btn-sm btn-danger" 
                                    onclick="deleteUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['username']) ?>')">
                                Löschen
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?page=<?= $i ?>" class="<?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Create User Modal -->
<div id="createUserModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeCreateUserModal()">&times;</span>
        <h3>Neuen Benutzer erstellen</h3>
        <form method="post">
            <input type="hidden" name="action" value="create_user">
            
            <div class="form-group">
                <label for="username">Benutzername:</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="email">E-Mail:</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="password">Passwort:</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="form-group">
                <label for="role">Rolle:</label>
                <select id="role" name="role" required>
                    <option value="subscriber">Abonnent</option>
                    <option value="author">Autor</option>
                    <option value="editor">Editor</option>
                    <option value="admin">Administrator</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="active" checked>
                    Aktiv
                </label>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeCreateUserModal()">Abbrechen</button>
                <button type="submit" class="btn btn-primary">Erstellen</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit User Modal -->
<div id="editUserModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeEditUserModal()">&times;</span>
        <h3>Benutzer bearbeiten</h3>
        <form method="post" id="editUserForm">
            <input type="hidden" name="action" value="update_user">
            <input type="hidden" name="user_id" id="edit_user_id">
            
            <div class="form-group">
                <label for="edit_username">Benutzername:</label>
                <input type="text" id="edit_username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="edit_email">E-Mail:</label>
                <input type="email" id="edit_email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="edit_password">Neues Passwort (leer lassen für unverändert):</label>
                <input type="password" id="edit_password" name="password">
            </div>
            
            <div class="form-group">
                <label for="edit_role">Rolle:</label>
                <select id="edit_role" name="role" required>
                    <option value="subscriber">Abonnent</option>
                    <option value="author">Autor</option>
                    <option value="editor">Editor</option>
                    <option value="admin">Administrator</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="active" id="edit_active">
                    Aktiv
                </label>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeEditUserModal()">Abbrechen</button>
                <button type="submit" class="btn btn-primary">Speichern</button>
            </div>
        </form>
    </div>
</div>

<style>
.user-manager {
    padding: 2rem 0;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    text-align: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.stat-card h4 {
    margin: 0 0 0.5rem 0;
    color: #666;
    font-size: 0.9rem;
}

.stat-value {
    font-size: 2rem;
    font-weight: bold;
    color: #007cba;
}

.filters {
    background: white;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1rem;
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.filter-group label {
    font-weight: bold;
    font-size: 0.9rem;
}

.filter-group select,
.filter-group input {
    padding: 0.5rem;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.table-container {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.user-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.badge {
    padding: 0.2rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: bold;
}

.badge-primary {
    background: #007cba;
    color: white;
}

.role-badge {
    padding: 0.3rem 0.6rem;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: bold;
}

.role-admin { background: #dc3545; color: white; }
.role-editor { background: #28a745; color: white; }
.role-author { background: #ffc107; color: black; }
.role-subscriber { background: #6c757d; color: white; }

.status-badge {
    padding: 0.3rem 0.6rem;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: bold;
}

.status-active { background: #d4edda; color: #155724; }
.status-inactive { background: #f8d7da; color: #721c24; }

.action-buttons {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.pagination {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
    margin-top: 2rem;
}

.pagination a {
    padding: 0.5rem 0.75rem;
    background: white;
    border: 1px solid #ddd;
    text-decoration: none;
    color: #007cba;
    border-radius: 4px;
}

.pagination a.active {
    background: #007cba;
    color: white;
}

.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: white;
    margin: 5% auto;
    padding: 2rem;
    border-radius: 8px;
    width: 90%;
    max-width: 500px;
    position: relative;
}

.close {
    position: absolute;
    right: 1rem;
    top: 1rem;
    font-size: 1.5rem;
    cursor: pointer;
}

.modal-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
    margin-top: 2rem;
}
</style>

<script>
function showCreateUserModal() {
    document.getElementById('createUserModal').style.display = 'block';
}

function closeCreateUserModal() {
    document.getElementById('createUserModal').style.display = 'none';
}

function editUser(userId) {
    // Get user data and populate form
    fetch(`user-manager-api.php?action=get_user&id=${userId}`)
        .then(response => response.json())
        .then(user => {
            document.getElementById('edit_user_id').value = user.id;
            document.getElementById('edit_username').value = user.username;
            document.getElementById('edit_email').value = user.email;
            document.getElementById('edit_role').value = user.role;
            document.getElementById('edit_active').checked = user.active == 1;
            document.getElementById('editUserModal').style.display = 'block';
        });
}

function closeEditUserModal() {
    document.getElementById('editUserModal').style.display = 'none';
}

function toggleUserStatus(userId, activate) {
    if (confirm(`Möchten Sie diesen Benutzer wirklich ${activate ? 'aktivieren' : 'deaktivieren'}?`)) {
        const form = document.createElement('form');
        form.method = 'post';
        form.innerHTML = `
            <input type="hidden" name="action" value="toggle_status">
            <input type="hidden" name="user_id" value="${userId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function deleteUser(userId, username) {
    if (confirm(`Möchten Sie den Benutzer "${username}" wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden.`)) {
        const form = document.createElement('form');
        form.method = 'post';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_user">
            <input type="hidden" name="user_id" value="${userId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function filterUsers() {
    const roleFilter = document.getElementById('roleFilter').value;
    const statusFilter = document.getElementById('statusFilter').value;
    const rows = document.querySelectorAll('#usersTable tbody tr');
    
    rows.forEach(row => {
        let show = true;
        
        if (roleFilter && row.dataset.role !== roleFilter) {
            show = false;
        }
        
        if (statusFilter && row.dataset.status !== statusFilter) {
            show = false;
        }
        
        row.style.display = show ? '' : 'none';
    });
}

function searchUsers() {
    const search = document.getElementById('searchUsers').value.toLowerCase();
    const rows = document.querySelectorAll('#usersTable tbody tr');
    
    rows.forEach(row => {
        const username = row.cells[1].textContent.toLowerCase();
        const email = row.cells[2].textContent.toLowerCase();
        
        if (username.includes(search) || email.includes(search)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Close modals when clicking outside
window.onclick = function(event) {
    const createModal = document.getElementById('createUserModal');
    const editModal = document.getElementById('editUserModal');
    
    if (event.target == createModal) {
        createModal.style.display = 'none';
    }
    if (event.target == editModal) {
        editModal.style.display = 'none';
    }
}
</script>

<?php include 'footer.php'; ?>
