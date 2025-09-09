<?php
require_once "../core/init.php";

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$user = $auth->getCurrentUser();

$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$message = '';
$error = '';

// Handle form submissions
if ($_POST) {
    if (!$auth->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Ungültiger CSRF-Token';
    } else {
        switch ($action) {
            case 'new':
            case 'edit':
                $title = trim($_POST['title'] ?? '');
                $slug = trim($_POST['slug'] ?? '');
                $content = $_POST['content'] ?? '';
                $meta_description = trim($_POST['meta_description'] ?? '');
                $meta_keywords = trim($_POST['meta_keywords'] ?? '');
                $status = $_POST['status'] ?? 'draft';
                $sort_order = (int)($_POST['sort_order'] ?? 0);
                
                // Validation
                if (empty($title)) {
                    $error = 'Titel ist erforderlich';
                } else {
                    // Generate slug if empty
                    if (empty($slug)) {
                        $slug = Page::generateSlug($title);
                    }
                    
                    // Check if slug exists
                    if (Page::slugExists($slug, $id)) {
                        $error = 'URL-Slug existiert bereits';
                    } else {
                        $data = [
                            'title' => $title,
                            'slug' => $slug,
                            'content' => $content,
                            'meta_description' => $meta_description,
                            'meta_keywords' => $meta_keywords,
                            'status' => $status,
                            'sort_order' => $sort_order
                        ];
                        
                        if ($action === 'new') {
                            $data['created_by'] = $user['id'];
                            $newId = Page::create($data);
                            $message = 'Seite erfolgreich erstellt';
                            header('Location: pages.php?message=' . urlencode($message));
                            exit;
                        } else {
                            Page::update($id, $data);
                            $message = 'Seite erfolgreich aktualisiert';
                            header('Location: pages.php?message=' . urlencode($message));
                            exit;
                        }
                    }
                }
                break;
                
            case 'delete':
                if ($id) {
                    Page::delete($id);
                    $message = 'Seite erfolgreich gelöscht';
                    header('Location: pages.php?message=' . urlencode($message));
                    exit;
                }
                break;
        }
    }
}

// Get messages from URL
if (isset($_GET['message'])) {
    $message = $_GET['message'];
}

// Get page data for editing
$pageData = null;
if ($action === 'edit' && $id) {
    $pageData = Page::getById($id);
    if (!$pageData) {
        $error = 'Seite nicht gefunden';
        $action = 'list';
    }
}

// Get all pages for listing
$pages = Page::getAll();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seitenverwaltung - Baukasten CMS</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background: #f5f5f5; }
        .header { background: #007cba; color: white; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        .nav { background: #005a87; padding: 0; }
        .nav ul { list-style: none; margin: 0; padding: 0; display: flex; }
        .nav li { margin: 0; }
        .nav a { display: block; padding: 1rem 1.5rem; color: white; text-decoration: none; }
        .nav a:hover, .nav a.active { background: #004666; }
        .container { max-width: 1200px; margin: 2rem auto; padding: 0 2rem; }
        .card { background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 2rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: bold; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .form-group textarea { height: 200px; resize: vertical; }
        .form-group textarea#content { height: 400px; }
        .btn { background: #007cba; color: white; padding: 0.75rem 1.5rem; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn:hover { background: #005a87; }
        .btn-secondary { background: #6c757d; }
        .btn-secondary:hover { background: #545b62; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        .btn-small { padding: 0.5rem 1rem; font-size: 0.9rem; }
        .message { background: #d4edda; color: #155724; padding: 1rem; border: 1px solid #c3e6cb; border-radius: 4px; margin-bottom: 1rem; }
        .error { background: #f8d7da; color: #721c24; padding: 1rem; border: 1px solid #f5c6cb; border-radius: 4px; margin-bottom: 1rem; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { padding: 0.75rem; border-bottom: 1px solid #ddd; text-align: left; }
        .table th { background: #f8f9fa; font-weight: bold; }
        .table tr:hover { background: #f8f9fa; }
        .status { padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.85rem; }
        .status-published { background: #d4edda; color: #155724; }
        .status-draft { background: #fff3cd; color: #856404; }
        .actions { display: flex; gap: 0.5rem; }
        .user-info { color: white; }
        .logout { background: #dc3545; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 4px; }
        .logout:hover { background: #c82333; }
        .form-row { display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 1rem; }
        @media (max-width: 768px) { .form-row { grid-template-columns: 1fr; } }
    </style>
    
    <!-- TinyMCE Editor -->
    <script src="https://cdn.tiny.cloud/1/<?php echo TINYMCE_API_KEY; ?>/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
</head>
<body>
    <div class="header">
        <h1>Baukasten CMS</h1>
        <div class="user-info">
            Willkommen, <?= htmlspecialchars($user['username']) ?> 
            <a href="logout.php" class="logout">Abmelden</a>
        </div>
    </div>
    
    <nav class="nav">
        <ul>
            <li><a href="index.php">Dashboard</a></li>
            <li><a href="pages.php" class="active">Seiten</a></li>
            <li><a href="media.php">Medien</a></li>
            <li><a href="settings.php">Einstellungen</a></li>
        </ul>
    </nav>
    
    <div class="container">
        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($action === 'list'): ?>
            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <h2>Seitenverwaltung</h2>
                    <a href="pages.php?action=new" class="btn">Neue Seite</a>
                </div>
                
                <table class="table">
                    <thead>
                        <tr>
                            <th>Titel</th>
                            <th>URL-Slug</th>
                            <th>Status</th>
                            <th>Sortierung</th>
                            <th>Erstellt</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pages as $page): ?>
                            <tr>
                                <td><?= htmlspecialchars($page['title']) ?></td>
                                <td><code><?= htmlspecialchars($page['slug']) ?></code></td>
                                <td>
                                    <span class="status status-<?= $page['status'] ?>">
                                        <?= $page['status'] === 'published' ? 'Veröffentlicht' : 'Entwurf' ?>
                                    </span>
                                </td>
                                <td><?= $page['sort_order'] ?></td>
                                <td><?= date('d.m.Y H:i', strtotime($page['created_at'])) ?></td>
                                <td>
                                    <div class="actions">
                                        <a href="pages.php?action=edit&id=<?= $page['id'] ?>" class="btn btn-small">Bearbeiten</a>
                                        <a href="/<?= htmlspecialchars($page['slug']) ?>" target="_blank" class="btn btn-secondary btn-small">Anzeigen</a>
                                        <a href="pages.php?action=delete&id=<?= $page['id'] ?>" 
                                           class="btn btn-danger btn-small"
                                           onclick="return confirm('Seite wirklich löschen?')">Löschen</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
        <?php elseif ($action === 'new' || $action === 'edit'): ?>
            <div class="card">
                <h2><?= $action === 'new' ? 'Neue Seite erstellen' : 'Seite bearbeiten' ?></h2>
                
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $auth->generateCSRFToken() ?>">
                    
                    <div class="form-group">
                        <label for="title">Titel *</label>
                        <input type="text" id="title" name="title" required 
                               value="<?= htmlspecialchars($pageData['title'] ?? '') ?>">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="slug">URL-Slug</label>
                            <input type="text" id="slug" name="slug" 
                                   value="<?= htmlspecialchars($pageData['slug'] ?? '') ?>"
                                   placeholder="Wird automatisch generiert">
                        </div>
                        
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                <option value="draft" <?= ($pageData['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Entwurf</option>
                                <option value="published" <?= ($pageData['status'] ?? '') === 'published' ? 'selected' : '' ?>>Veröffentlicht</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="sort_order">Sortierung</label>
                            <input type="number" id="sort_order" name="sort_order" 
                                   value="<?= htmlspecialchars($pageData['sort_order'] ?? '0') ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="content">Inhalt</label>
                        <textarea id="content" name="content"><?= htmlspecialchars($pageData['content'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="meta_description">Meta-Beschreibung</label>
                        <textarea id="meta_description" name="meta_description" style="height: 80px;"><?= htmlspecialchars($pageData['meta_description'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="meta_keywords">Meta-Keywords</label>
                        <input type="text" id="meta_keywords" name="meta_keywords" 
                               value="<?= htmlspecialchars($pageData['meta_keywords'] ?? '') ?>"
                               placeholder="Keyword1, Keyword2, Keyword3">
                    </div>
                    
                    <div style="display: flex; gap: 1rem;">
                        <button type="submit" class="btn">
                            <?= $action === 'new' ? 'Seite erstellen' : 'Seite aktualisieren' ?>
                        </button>
                        <a href="pages.php" class="btn btn-secondary">Abbrechen</a>
                    </div>
                </form>
            </div>
            
        <?php elseif ($action === 'delete' && $id): ?>
            <div class="card">
                <h2>Seite löschen</h2>
                <p>Möchten Sie diese Seite wirklich löschen?</p>
                
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $auth->generateCSRFToken() ?>">
                    <div style="display: flex; gap: 1rem;">
                        <button type="submit" class="btn btn-danger">Ja, löschen</button>
                        <a href="pages.php" class="btn btn-secondary">Abbrechen</a>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Initialize TinyMCE Editor
        tinymce.init({
            selector: '#content',
            height: 400,
            menubar: false,
            plugins: [
                'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                'insertdatetime', 'media', 'table', 'help', 'wordcount'
            ],
            toolbar: 'undo redo | formatselect | bold italic backcolor | ' +
                'alignleft aligncenter alignright alignjustify | ' +
                'bullist numlist outdent indent | removeformat | image link medialib | code | help',
            content_style: 'body { font-family:Arial,sans-serif; font-size:14px }',
            images_upload_handler: function (blobInfo, success, failure) {
                // Handle image uploads through media manager
                const formData = new FormData();
                formData.append('media_file', blobInfo.blob(), blobInfo.filename());
                formData.append('csrf_token', '<?= $auth->generateCSRFToken() ?>');
                
                fetch('media_upload.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        success(result.url);
                    } else {
                        failure('Fehler beim Hochladen: ' + result.error);
                    }
                })
                .catch(error => {
                    failure('Netzwerkfehler beim Hochladen');
                });
            },
            setup: function(editor) {
                // Custom button for media library
                editor.ui.registry.addButton('medialib', {
                    text: 'Medien',
                    onAction: function() {
                        window.open('media_select.php', 'medialib', 'width=800,height=600,scrollbars=yes');
                    }
                });
            }
        });
        
        // Auto-generate slug from title
        document.getElementById('title')?.addEventListener('input', function() {
            const slugField = document.getElementById('slug');
            if (!slugField.value || slugField.dataset.auto !== 'false') {
                let slug = this.value.toLowerCase()
                    .replace(/ä/g, 'ae').replace(/ö/g, 'oe').replace(/ü/g, 'ue').replace(/ß/g, 'ss')
                    .replace(/[^a-z0-9\s-]/g, '')
                    .replace(/[\s-]+/g, '-')
                    .replace(/^-+|-+$/g, '');
                slugField.value = slug;
            }
        });
        
        // Mark slug as manually edited
        document.getElementById('slug')?.addEventListener('input', function() {
            this.dataset.auto = 'false';
        });
    </script>
</body>
</html>
