<?php
require_once "../core/init.php";

$auth = new Auth();
$auth->requireLogin();
$auth->requireRole('admin'); // Nur Administratoren können Benutzer verwalten

$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$message = '';
$error = '';

// Handle form submissions
if ($_POST) {
    if (!$auth->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Ungültiger CSRF-Token';
    } else {
        try {
            switch ($action) {
                case 'create':
                    $userData = [
                        'username' => $_POST['username'],
                        'email' => $_POST['email'],
                        'password' => $_POST['password'],
                        'role' => $_POST['role'],
                        'bio' => $_POST['bio'] ?? null,
                        'active' => isset($_POST['active']) ? 1 : 0
                    ];
                    
                    // Handle social links
                    $socialLinks = [];
                    if (!empty($_POST['social_facebook'])) $socialLinks['facebook'] = $_POST['social_facebook'];
                    if (!empty($_POST['social_twitter'])) $socialLinks['twitter'] = $_POST['social_twitter'];
                    if (!empty($_POST['social_linkedin'])) $socialLinks['linkedin'] = $_POST['social_linkedin'];
                    if (!empty($_POST['social_instagram'])) $socialLinks['instagram'] = $_POST['social_instagram'];
                    
                    if (!empty($socialLinks)) {
                        $userData['social_links'] = $socialLinks;
                    }
                    
                    User::create($userData);
                    $message = 'Benutzer erfolgreich erstellt.';
                    $action = 'list';
                    break;
                    
                case 'edit':
                    if (!$id) {
                        $error = 'Benutzer-ID fehlt';
                        break;
                    }
                    
                    $userData = [
                        'username' => $_POST['username'],
                        'email' => $_POST['email'],
                        'role' => $_POST['role'],
                        'bio' => $_POST['bio'] ?? null,
                        'active' => isset($_POST['active']) ? 1 : 0
                    ];
                    
                    // Only update password if provided
                    if (!empty($_POST['password'])) {
                        $userData['password'] = $_POST['password'];
                    }
                    
                    // Handle social links
                    $socialLinks = [];
                    if (!empty($_POST['social_facebook'])) $socialLinks['facebook'] = $_POST['social_facebook'];
                    if (!empty($_POST['social_twitter'])) $socialLinks['twitter'] = $_POST['social_twitter'];
                    if (!empty($_POST['social_linkedin'])) $socialLinks['linkedin'] = $_POST['social_linkedin'];
                    if (!empty($_POST['social_instagram'])) $socialLinks['instagram'] = $_POST['social_instagram'];
                    
                    $userData['social_links'] = $socialLinks;
                    
                    User::update($id, $userData);
                    $message = 'Benutzer erfolgreich aktualisiert.';
                    $action = 'list';
                    break;
                    
                case 'delete':
                    if (!$id) {
                        $error = 'Benutzer-ID fehlt';
                        break;
                    }
                    
                    User::delete($id);
                    $message = 'Benutzer erfolgreich gelöscht.';
                    $action = 'list';
                    break;
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Get data for current action
$users = [];
$userData = null;
$availableRoles = User::getAvailableRoles();

switch ($action) {
    case 'list':
        $users = User::getAll();
        break;
        
    case 'create':
        // Nothing special needed
        break;
        
    case 'edit':
        if ($id) {
            $userData = User::getById($id);
            if (!$userData) {
                $error = 'Benutzer nicht gefunden';
                $action = 'list';
                $users = User::getAll();
            } else {
                // Parse social links JSON
                if ($userData['social_links']) {
                    $userData['social_links'] = json_decode($userData['social_links'], true);
                }
            }
        } else {
            $error = 'Benutzer-ID fehlt';
            $action = 'list';
            $users = User::getAll();
        }
        break;
}

$currentUser = $auth->getCurrentUser();

$pageTitle = "Benutzerverwaltung";
$currentPage = "users";
include 'header.php';
?>

<!-- External CSS für erweiterte Funktionen -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<style>
    .social-input { margin-bottom: 10px; }
    .user-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }
    .role-badge { font-size: 0.8em; }
</style>

<div class="row mt-4">
    <div class="col-md-9">
                <?php if ($message): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <?php if ($action === 'list'): ?>
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>Benutzerverwaltung</h2>
                        <a href="?action=create" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Neuer Benutzer
                        </a>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Avatar</th>
                                            <th>Benutzername</th>
                                            <th>E-Mail</th>
                                            <th>Rolle</th>
                                            <th>Status</th>
                                            <th>Erstellt</th>
                                            <th>Aktionen</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $user): ?>
                                            <tr>
                                                <td><?= $user['id'] ?></td>
                                                <td>
                                                    <?php if ($user['profile_image']): ?>
                                                        <img src="../<?= htmlspecialchars($user['profile_image']) ?>" 
                                                             alt="Avatar" class="user-avatar">
                                                    <?php else: ?>
                                                        <div class="user-avatar bg-secondary d-flex align-items-center justify-content-center">
                                                            <i class="fas fa-user text-white"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= htmlspecialchars($user['username']) ?></td>
                                                <td><?= htmlspecialchars($user['email']) ?></td>
                                                <td>
                                                    <span class="badge role-badge 
                                                        <?= $user['role'] === 'admin' ? 'bg-danger' : 
                                                           ($user['role'] === 'moderator' ? 'bg-warning' : 'bg-info') ?>">
                                                        <?= User::getRoleDisplayName($user['role']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge <?= $user['active'] ? 'bg-success' : 'bg-secondary' ?>">
                                                        <?= $user['active'] ? 'Aktiv' : 'Inaktiv' ?>
                                                    </span>
                                                </td>
                                                <td><?= date('d.m.Y', strtotime($user['created_at'])) ?></td>
                                                <td>
                                                    <a href="?action=edit&id=<?= $user['id'] ?>" 
                                                       class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <?php if ($user['id'] != $currentUser['id']): ?>
                                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                onclick="deleteUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['username']) ?>')">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                <?php elseif ($action === 'create' || $action === 'edit'): ?>
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2><?= $action === 'create' ? 'Neuen Benutzer erstellen' : 'Benutzer bearbeiten' ?></h2>
                        <a href="?action=list" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Zurück
                        </a>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= $auth->generateCSRFToken() ?>">
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="username">Benutzername</label>
                                            <input type="text" class="form-control" id="username" name="username" 
                                                   value="<?= htmlspecialchars($userData['username'] ?? '') ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="email">E-Mail</label>
                                            <input type="email" class="form-control" id="email" name="email" 
                                                   value="<?= htmlspecialchars($userData['email'] ?? '') ?>" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="password">Passwort<?= $action === 'edit' ? ' (leer lassen für keine Änderung)' : '' ?></label>
                                            <input type="password" class="form-control" id="password" name="password" 
                                                   <?= $action === 'create' ? 'required' : '' ?>>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="role">Rolle</label>
                                            <select class="form-control" id="role" name="role" required>
                                                <?php foreach ($availableRoles as $roleKey => $roleDesc): ?>
                                                    <option value="<?= $roleKey ?>" 
                                                            <?= ($userData['role'] ?? 'editor') === $roleKey ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($roleDesc) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group mb-3">
                                    <label for="bio">Biografie</label>
                                    <textarea class="form-control" id="bio" name="bio" rows="3"><?= htmlspecialchars($userData['bio'] ?? '') ?></textarea>
                                </div>

                                <div class="form-group mb-3">
                                    <label>Social Media Links</label>
                                    <div class="social-input">
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fab fa-facebook"></i></span>
                                            <input type="url" class="form-control" name="social_facebook" 
                                                   placeholder="Facebook URL" 
                                                   value="<?= htmlspecialchars($userData['social_links']['facebook'] ?? '') ?>">
                                        </div>
                                    </div>
                                    <div class="social-input">
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fab fa-twitter"></i></span>
                                            <input type="url" class="form-control" name="social_twitter" 
                                                   placeholder="Twitter URL" 
                                                   value="<?= htmlspecialchars($userData['social_links']['twitter'] ?? '') ?>">
                                        </div>
                                    </div>
                                    <div class="social-input">
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fab fa-linkedin"></i></span>
                                            <input type="url" class="form-control" name="social_linkedin" 
                                                   placeholder="LinkedIn URL" 
                                                   value="<?= htmlspecialchars($userData['social_links']['linkedin'] ?? '') ?>">
                                        </div>
                                    </div>
                                    <div class="social-input">
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fab fa-instagram"></i></span>
                                            <input type="url" class="form-control" name="social_instagram" 
                                                   placeholder="Instagram URL" 
                                                   value="<?= htmlspecialchars($userData['social_links']['instagram'] ?? '') ?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="form-check mb-3">
                                    <input type="checkbox" class="form-check-input" id="active" name="active" 
                                           value="1" <?= ($userData['active'] ?? 1) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="active">
                                        Benutzer aktiv
                                    </label>
                                </div>

                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> 
                                    <?= $action === 'create' ? 'Erstellen' : 'Aktualisieren' ?>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Delete confirmation modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Benutzer löschen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Möchten Sie den Benutzer <strong id="deleteUsername"></strong> wirklich löschen?</p>
                    <p class="text-danger">Diese Aktion kann nicht rückgängig gemacht werden.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <form method="POST" style="display: inline;" id="deleteForm">
                        <input type="hidden" name="csrf_token" value="<?= $auth->generateCSRFToken() ?>">
                        <button type="submit" class="btn btn-danger">Löschen</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function deleteUser(id, username) {
            document.getElementById('deleteUsername').textContent = username;
            document.getElementById('deleteForm').action = '?action=delete&id=' + id;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }
    </script>

<?php include 'footer.php'; ?>
