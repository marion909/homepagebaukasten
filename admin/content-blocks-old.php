<?php
require_once "../core/init.php";

$auth = new Auth();
$auth->requireLogin();
$auth->requireAnyRole(['admin', 'editor']); // Admins und Editoren können Content-Blöcke verwalten

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
                    $blockData = [
                        'name' => $_POST['name'],
                        'block_key' => $_POST['block_key'],
                        'content' => $_POST['content'],
                        'description' => $_POST['description'],
                        'type' => $_POST['type'],
                        'active' => isset($_POST['active']) ? 1 : 0,
                        'created_by' => $auth->getCurrentUser()['id']
                    ];
                    
                    ContentBlock::create($blockData);
                    $message = 'Content-Block erfolgreich erstellt.';
                    $action = 'list';
                    break;
                    
                case 'edit':
                    if (!$id) {
                        $error = 'Block-ID fehlt';
                        break;
                    }
                    
                    $blockData = [
                        'name' => $_POST['name'],
                        'block_key' => $_POST['block_key'],
                        'content' => $_POST['content'],
                        'description' => $_POST['description'],
                        'type' => $_POST['type'],
                        'active' => isset($_POST['active']) ? 1 : 0
                    ];
                    
                    ContentBlock::update($id, $blockData);
                    $message = 'Content-Block erfolgreich aktualisiert.';
                    $action = 'list';
                    break;
                    
                case 'delete':
                    if (!$id) {
                        $error = 'Block-ID fehlt';
                        break;
                    }
                    
                    ContentBlock::delete($id);
                    $message = 'Content-Block erfolgreich gelöscht.';
                    $action = 'list';
                    break;
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Get data for current action
$blocks = [];
$blockData = null;
$availableTypes = ContentBlock::getTypes();

switch ($action) {
    case 'list':
        $blocks = ContentBlock::getAll();
        break;
        
    case 'create':
        // Nothing special needed
        break;
        
    case 'edit':
        if ($id) {
            $blockData = ContentBlock::getById($id);
            if (!$blockData) {
                $error = 'Content-Block nicht gefunden';
                $action = 'list';
                $blocks = ContentBlock::getAll();
            }
        } else {
            $error = 'Block-ID fehlt';
            $action = 'list';
            $blocks = ContentBlock::getAll();
        }
        break;
}

$currentUser = $auth->getCurrentUser();

$pageTitle = "Content-Blöcke";
$currentPage = "content-blocks";
include 'header.php';
?>

<!-- External CSS für erweiterte Funktionen -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<style>
    .code-editor { font-family: 'Courier New', monospace; background-color: #f8f9fa; border: 1px solid #ced4da; border-radius: 0.375rem; }
    .shortcode-example { font-family: 'Courier New', monospace; background-color: #e9ecef; padding: 5px; border-radius: 3px; margin-top: 10px; }
    .type-badge { font-size: 0.8em; }
</style>

<div class="row mt-4">
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Content-Blöcke - Baukasten CMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .code-editor {
            font-family: 'Courier New', monospace;
            font-size: 14px;
        }
        .block-preview {
            border: 1px solid #ddd;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 5px;
            margin-top: 10px;
        }
        .shortcode-example {
            background: #e7f3ff;
            padding: 10px;
            border-radius: 5px;
            font-family: monospace;
            margin-top: 10px;
        }
        .type-badge {
            font-size: 0.8em;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-cogs"></i> Baukasten CMS
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php">Dashboard</a>
                <a class="nav-link" href="../public/index.php" target="_blank">Website ansehen</a>
                <a class="nav-link" href="logout.php">Logout (<?= htmlspecialchars($currentUser['username']) ?>)</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-3">
                <div class="list-group">
                    <a href="index.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <a href="pages.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-file-alt"></i> Seiten
                    </a>
                    <a href="blog.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-blog"></i> Blog
                    </a>
                    <a href="content-blocks.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-cubes"></i> Content-Blöcke
                    </a>
                    <?php if ($auth->canManageComments()): ?>
                    <a href="comments.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-comments"></i> Kommentare
                    </a>
                    <?php endif; ?>
                    <a href="media.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-images"></i> Medien
                    </a>
                    <?php if ($auth->canManageUsers()): ?>
                    <a href="users.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-users"></i> Benutzer
                    </a>
                    <?php endif; ?>
                    <?php if ($auth->canManageSystem()): ?>
                    <a href="seo.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-search"></i> SEO
                    </a>
    <div class="col-md-9">
                <?php if ($message): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <?php if ($action === 'list'): ?>
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>Content-Blöcke</h2>
                        <a href="?action=create" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Neuer Content-Block
                        </a>
                    </div>

                    <div class="alert alert-info">
                        <strong>Info:</strong> Content-Blöcke sind wiederverwendbare Inhaltselemente. 
                        Verwenden Sie den Shortcode <code>[content_block key="ihr_key"]</code> in Seiten oder Blog-Posts.
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Block-Key</th>
                                            <th>Typ</th>
                                            <th>Status</th>
                                            <th>Erstellt</th>
                                            <th>Aktionen</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($blocks as $block): ?>
                                            <tr>
                                                <td><?= $block['id'] ?></td>
                                                <td>
                                                    <strong><?= htmlspecialchars($block['name']) ?></strong>
                                                    <?php if ($block['description']): ?>
                                                        <br><small class="text-muted"><?= htmlspecialchars($block['description']) ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <code><?= htmlspecialchars($block['block_key']) ?></code>
                                                    <br><small class="text-muted">
                                                        [content_block key="<?= htmlspecialchars($block['block_key']) ?>"]
                                                    </small>
                                                </td>
                                                <td>
                                                    <span class="badge type-badge 
                                                        <?= $block['type'] === 'html' ? 'bg-primary' : 
                                                           ($block['type'] === 'css' ? 'bg-warning' : 
                                                           ($block['type'] === 'javascript' ? 'bg-info' : 'bg-secondary')) ?>">
                                                        <?= strtoupper($block['type']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge <?= $block['active'] ? 'bg-success' : 'bg-secondary' ?>">
                                                        <?= $block['active'] ? 'Aktiv' : 'Inaktiv' ?>
                                                    </span>
                                                </td>
                                                <td><?= date('d.m.Y H:i', strtotime($block['created_at'])) ?></td>
                                                <td>
                                                    <a href="?action=edit&id=<?= $block['id'] ?>" 
                                                       class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-outline-info" 
                                                            onclick="previewBlock('<?= htmlspecialchars($block['block_key']) ?>')">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                                            onclick="deleteBlock(<?= $block['id'] ?>, '<?= htmlspecialchars($block['name']) ?>')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
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
                        <h2><?= $action === 'create' ? 'Neuen Content-Block erstellen' : 'Content-Block bearbeiten' ?></h2>
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
                                            <label for="name">Name</label>
                                            <input type="text" class="form-control" id="name" name="name" 
                                                   value="<?= htmlspecialchars($blockData['name'] ?? '') ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="block_key">Block-Key (eindeutig)</label>
                                            <input type="text" class="form-control" id="block_key" name="block_key" 
                                                   value="<?= htmlspecialchars($blockData['block_key'] ?? '') ?>" 
                                                   pattern="[a-z0-9_-]+" title="Nur Kleinbuchstaben, Zahlen, _ und - erlaubt" required>
                                            <small class="text-muted">Nur Kleinbuchstaben, Zahlen, Unterstriche und Bindestriche</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group mb-3">
                                    <label for="description">Beschreibung</label>
                                    <input type="text" class="form-control" id="description" name="description" 
                                           value="<?= htmlspecialchars($blockData['description'] ?? '') ?>">
                                </div>

                                <div class="form-group mb-3">
                                    <label for="type">Typ</label>
                                    <select class="form-control" id="type" name="type" required>
                                        <?php foreach ($availableTypes as $typeKey => $typeDesc): ?>
                                            <option value="<?= $typeKey ?>" 
                                                    <?= ($blockData['type'] ?? 'html') === $typeKey ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($typeDesc) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group mb-3">
                                    <label for="content">Inhalt</label>
                                    <textarea class="form-control code-editor" id="content" name="content" rows="15" required><?= htmlspecialchars($blockData['content'] ?? '') ?></textarea>
                                    <small class="text-muted">
                                        Bei HTML-Typ können Sie Shortcodes verwenden. Verfügbare Shortcodes: 
                                        [contact_form], [blog_list], [blog_categories], [blog_tags]
                                    </small>
                                </div>

                                <div class="form-check mb-3">
                                    <input type="checkbox" class="form-check-input" id="active" name="active" 
                                           value="1" <?= ($blockData['active'] ?? 1) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="active">
                                        Content-Block aktiv
                                    </label>
                                </div>

                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> 
                                    <?= $action === 'create' ? 'Erstellen' : 'Aktualisieren' ?>
                                </button>
                                
                                <?php if ($action === 'edit'): ?>
                                    <button type="button" class="btn btn-info" onclick="previewCurrentBlock()">
                                        <i class="fas fa-eye"></i> Vorschau
                                    </button>
                                <?php endif; ?>
                            </form>
                            
                            <?php if ($action === 'edit' && $blockData): ?>
                                <div class="shortcode-example mt-4">
                                    <strong>Shortcode:</strong> [content_block key="<?= htmlspecialchars($blockData['block_key']) ?>"]
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
                
                <?php endif; ?>
            </div>
        </div>

    <!-- Preview Modal -->
    <div class="modal fade" id="previewModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Content-Block Vorschau</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="previewContent" class="block-preview">
                        Laden...
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Schließen</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete confirmation modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Content-Block löschen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Möchten Sie den Content-Block <strong id="deleteBlockName"></strong> wirklich löschen?</p>
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
        function deleteBlock(id, name) {
            document.getElementById('deleteBlockName').textContent = name;
            document.getElementById('deleteForm').action = '?action=delete&id=' + id;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }
        
        function previewBlock(key) {
            document.getElementById('previewContent').innerHTML = 'Laden...';
            new bootstrap.Modal(document.getElementById('previewModal')).show();
            
            // Simple AJAX request to get block content
            fetch('preview-block.php?key=' + encodeURIComponent(key))
                .then(response => response.text())
                .then(data => {
                    document.getElementById('previewContent').innerHTML = data;
                })
                .catch(error => {
                    document.getElementById('previewContent').innerHTML = 'Fehler beim Laden der Vorschau';
                });
        }
        
        function previewCurrentBlock() {
            const content = document.getElementById('content').value;
            const type = document.getElementById('type').value;
            
            document.getElementById('previewContent').innerHTML = 'Laden...';
            new bootstrap.Modal(document.getElementById('previewModal')).show();
            
            if (type === 'html') {
                document.getElementById('previewContent').innerHTML = content;
            } else {
                document.getElementById('previewContent').innerHTML = '<pre>' + content.replace(/</g, '&lt;') + '</pre>';
            }
        }
        
        // Auto-generate block key from name
        document.getElementById('name').addEventListener('input', function() {
            if (document.getElementById('block_key').value === '') {
                const key = this.value.toLowerCase()
                    .replace(/[^a-z0-9\s-]/g, '')
                    .replace(/\s+/g, '_')
                    .replace(/-+/g, '_');
                document.getElementById('block_key').value = key;
            }
        });
    </script>

<?php include 'footer.php'; ?>
