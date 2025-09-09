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
                $name = trim($_POST['name'] ?? '');
                $slug = trim($_POST['slug'] ?? '');
                $description = trim($_POST['description'] ?? '');
                
                // Validation
                if (empty($name)) {
                    $error = 'Kategorie-Name ist erforderlich.';
                } else {
                    // Auto-generate slug if empty
                    if (empty($slug)) {
                        $slug = BlogCategory::generateSlug($name);
                    } else {
                        $slug = BlogCategory::generateSlug($slug);
                    }
                    
                    // Check for duplicate slug
                    $existing = BlogCategory::getBySlug($slug);
                    if ($existing && ($action === 'new' || $existing['id'] != $id)) {
                        $error = 'Eine Kategorie mit diesem Slug existiert bereits.';
                    } else {
                        $data = [
                            'name' => $name,
                            'slug' => $slug,
                            'description' => $description
                        ];
                        
                        try {
                            if ($action === 'new') {
                                BlogCategory::create($data);
                                $message = 'Kategorie wurde erfolgreich erstellt.';
                            } else {
                                BlogCategory::update($id, $data);
                                $message = 'Kategorie wurde erfolgreich aktualisiert.';
                            }
                        } catch (Exception $e) {
                            $error = 'Fehler beim Speichern: ' . $e->getMessage();
                        }
                    }
                }
                break;
                
            case 'delete':
                if ($id) {
                    try {
                        BlogCategory::delete($id);
                        $message = 'Kategorie wurde gelöscht.';
                        $action = 'list'; // Back to list
                    } catch (Exception $e) {
                        $error = 'Fehler beim Löschen: ' . $e->getMessage();
                    }
                }
                break;
        }
    }
}

// Get data for forms
$categoryData = [];
if ($action === 'edit' && $id) {
    $categoryData = BlogCategory::getById($id);
    if (!$categoryData) {
        $error = 'Kategorie nicht gefunden.';
        $action = 'list';
    }
}

// Get all categories for list view
$categories = [];
if ($action === 'list') {
    $categories = BlogCategory::getWithPostCount();
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog-Kategorien - Baukasten CMS</title>
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
        .form-group textarea { height: 100px; resize: vertical; }
        .btn { background: #007cba; color: white; padding: 0.75rem 1.5rem; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; margin-right: 0.5rem; }
        .btn:hover { background: #005a87; }
        .btn-secondary { background: #6c757d; }
        .btn-secondary:hover { background: #5a6268; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        .btn-small { padding: 0.5rem 1rem; font-size: 0.9rem; }
        .table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        .table th, .table td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #dee2e6; }
        .table th { background: #f8f9fa; font-weight: bold; }
        .table tr:hover { background: #f8f9fa; }
        .actions { display: flex; gap: 0.5rem; }
        .message { background: #d4edda; color: #155724; padding: 1rem; border: 1px solid #c3e6cb; border-radius: 4px; margin-bottom: 1rem; }
        .error { background: #f8d7da; color: #721c24; padding: 1rem; border: 1px solid #f5c6cb; border-radius: 4px; margin-bottom: 1rem; }
        .user-info { color: white; }
        .logout { background: #dc3545; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 4px; }
        .logout:hover { background: #c82333; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .page-header h2 { margin: 0; }
        .badge { background: #6c757d; color: white; padding: 0.25rem 0.5rem; border-radius: 12px; font-size: 0.75rem; }
        .slug-preview { font-family: monospace; background: #f8f9fa; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.9rem; color: #666; margin-top: 0.25rem; }
    </style>
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
            <li><a href="pages.php">Seiten</a></li>
            <li><a href="blog.php" class="active">Blog</a></li>
            <li><a href="media.php">Medien</a></li>
            <li><a href="comments.php">Kommentare</a></li>
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
            <div class="page-header">
                <h2>Blog-Kategorien</h2>
                <a href="blog-categories.php?action=new" class="btn">Neue Kategorie</a>
            </div>
            
            <div class="card">
                <?php if (empty($categories)): ?>
                    <p>Noch keine Kategorien vorhanden. <a href="blog-categories.php?action=new">Erstellen Sie die erste Kategorie</a>.</p>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Slug</th>
                                <th>Beschreibung</th>
                                <th>Beiträge</th>
                                <th>Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $category): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($category['name']) ?></strong></td>
                                    <td><code><?= htmlspecialchars($category['slug']) ?></code></td>
                                    <td><?= htmlspecialchars(substr($category['description'] ?? '', 0, 100)) ?><?= strlen($category['description'] ?? '') > 100 ? '...' : '' ?></td>
                                    <td>
                                        <span class="badge"><?= $category['post_count'] ?></span>
                                    </td>
                                    <td>
                                        <div class="actions">
                                            <a href="blog-categories.php?action=edit&id=<?= $category['id'] ?>" class="btn btn-small">Bearbeiten</a>
                                            <a href="blog-categories.php?action=delete&id=<?= $category['id'] ?>" 
                                               class="btn btn-small btn-danger" 
                                               onclick="return confirm('Kategorie wirklich löschen? Zuordnungen zu Beiträgen gehen verloren.')">Löschen</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
        <?php elseif ($action === 'new' || $action === 'edit'): ?>
            <div class="page-header">
                <h2><?= $action === 'new' ? 'Neue Kategorie erstellen' : 'Kategorie bearbeiten' ?></h2>
                <a href="blog-categories.php" class="btn btn-secondary">Zurück zur Liste</a>
            </div>
            
            <div class="card">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $auth->generateCSRFToken() ?>">
                    
                    <div class="form-group">
                        <label for="name">Name *</label>
                        <input type="text" id="name" name="name" required 
                               value="<?= htmlspecialchars($categoryData['name'] ?? '') ?>"
                               placeholder="z.B. Technologie, Lifestyle">
                    </div>
                    
                    <div class="form-group">
                        <label for="slug">URL-Slug</label>
                        <input type="text" id="slug" name="slug" 
                               value="<?= htmlspecialchars($categoryData['slug'] ?? '') ?>"
                               placeholder="Wird automatisch generiert">
                        <div class="slug-preview" id="slug-preview">
                            URL: /blog/category/<span id="slug-text"><?= htmlspecialchars($categoryData['slug'] ?? '') ?></span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Beschreibung</label>
                        <textarea id="description" name="description" 
                                  placeholder="Kurze Beschreibung der Kategorie"><?= htmlspecialchars($categoryData['description'] ?? '') ?></textarea>
                    </div>
                    
                    <div>
                        <button type="submit" class="btn">
                            <?= $action === 'new' ? 'Kategorie erstellen' : 'Kategorie aktualisieren' ?>
                        </button>
                        <a href="blog-categories.php" class="btn btn-secondary">Abbrechen</a>
                    </div>
                </form>
            </div>
            
        <?php elseif ($action === 'delete' && $id): ?>
            <div class="page-header">
                <h2>Kategorie löschen</h2>
                <a href="blog-categories.php" class="btn btn-secondary">Abbrechen</a>
            </div>
            
            <div class="card">
                <p><strong>Möchten Sie diese Kategorie wirklich löschen?</strong></p>
                <p>Kategorie: <strong><?= htmlspecialchars($categoryData['name'] ?? '') ?></strong></p>
                <p style="color: #dc3545;"><small>Hinweis: Alle Zuordnungen zu Blog-Beiträgen gehen verloren.</small></p>
                
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $auth->generateCSRFToken() ?>">
                    <div style="display: flex; gap: 1rem;">
                        <button type="submit" class="btn btn-danger">Ja, löschen</button>
                        <a href="blog-categories.php" class="btn btn-secondary">Abbrechen</a>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Auto-generate slug from name
        document.getElementById('name')?.addEventListener('input', function() {
            const slugField = document.getElementById('slug');
            const slugText = document.getElementById('slug-text');
            
            if (!slugField.value || slugField.dataset.auto !== 'false') {
                let slug = this.value.toLowerCase()
                    .replace(/ä/g, 'ae').replace(/ö/g, 'oe').replace(/ü/g, 'ue').replace(/ß/g, 'ss')
                    .replace(/[^a-z0-9\s-]/g, '')
                    .replace(/[\s-]+/g, '-')
                    .replace(/^-+|-+$/g, '');
                slugField.value = slug;
                slugText.textContent = slug || 'category-slug';
            }
        });
        
        // Mark slug as manually edited
        document.getElementById('slug')?.addEventListener('input', function() {
            this.dataset.auto = 'false';
            document.getElementById('slug-text').textContent = this.value || 'category-slug';
        });
        
        // Initial slug preview
        document.addEventListener('DOMContentLoaded', function() {
            const slugField = document.getElementById('slug');
            const slugText = document.getElementById('slug-text');
            if (slugField && slugText && !slugText.textContent) {
                slugText.textContent = 'category-slug';
            }
        });
    </script>
</body>
</html>
