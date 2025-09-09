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
                $excerpt = trim($_POST['excerpt'] ?? '');
                $content = $_POST['content'] ?? '';
                $status = $_POST['status'] ?? 'draft';
                $categories = $_POST['categories'] ?? [];
                $tags = trim($_POST['tags'] ?? '');
                
                // Validation
                if (empty($title)) {
                    $error = 'Titel ist erforderlich.';
                } else {
                    // Auto-generate slug if empty
                    if (empty($slug)) {
                        $slug = BlogCategory::generateSlug($title);
                    } else {
                        $slug = BlogCategory::generateSlug($slug);
                    }
                    
                    // Check for duplicate slug
                    $existing = Blog::getBySlug($slug);
                    if ($existing && ($action === 'new' || $existing['id'] != $id)) {
                        $error = 'Ein Beitrag mit diesem Slug existiert bereits.';
                    } else {
                        $data = [
                            'title' => $title,
                            'slug' => $slug,
                            'excerpt' => $excerpt,
                            'content' => $content,
                            'status' => $status,
                            'created_by' => $user['id']
                        ];
                        
                        try {
                            if ($action === 'new') {
                                $post_id = Blog::create($data);
                                $message = 'Blog-Beitrag wurde erfolgreich erstellt.';
                            } else {
                                Blog::update($id, $data);
                                $post_id = $id;
                                $message = 'Blog-Beitrag wurde erfolgreich aktualisiert.';
                            }
                            
                            // Handle categories
                            if (!empty($categories)) {
                                BlogCategory::assignToPost($post_id, $categories);
                            }
                            
                            // Handle tags
                            if (!empty($tags)) {
                                BlogTag::assignToPost($post_id, $tags);
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
                        Blog::delete($id);
                        $message = 'Blog-Beitrag wurde gelöscht.';
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
$postData = [];
$postCategories = [];
$postTags = '';

if ($action === 'edit' && $id) {
    $postData = Blog::getById($id);
    if (!$postData) {
        $error = 'Blog-Beitrag nicht gefunden.';
        $action = 'list';
    } else {
        $postCategories = array_column(BlogCategory::getByPostId($id), 'id');
        $postTags = BlogTag::getTagsAsString($id);
    }
}

// Get all posts for list view
$posts = [];
if ($action === 'list') {
    $posts = $db->fetchAll("
        SELECT bp.*, u.username as author_name,
               GROUP_CONCAT(DISTINCT bc.name SEPARATOR ', ') as categories
        FROM blog_posts bp
        LEFT JOIN users u ON bp.created_by = u.id
        LEFT JOIN blog_post_categories bpc ON bp.id = bpc.post_id
        LEFT JOIN blog_categories bc ON bpc.category_id = bc.id
        GROUP BY bp.id
        ORDER BY bp.created_at DESC
    ");
}

// Get all categories for form
$allCategories = BlogCategory::getAll();

$pageTitle = "Blog-Posts";
$currentPage = "blog";
include 'header.php';
?>

<!-- TinyMCE Editor -->
<script src="https://cdn.tiny.cloud/1/<?php echo TINYMCE_API_KEY; ?>/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($action === 'list'): ?>
            <div class="page-header">
                <h2>Blog-Posts</h2>
                <div>
                    <a href="blog-categories.php" class="btn btn-secondary">Kategorien verwalten</a>
                    <a href="blog.php?action=new" class="btn">Neuer Beitrag</a>
                </div>
            </div>
            
            <div class="card">
                <?php if (empty($posts)): ?>
                    <p>Noch keine Blog-Posts vorhanden. <a href="blog.php?action=new">Erstellen Sie den ersten Beitrag</a>.</p>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Titel</th>
                                <th>Status</th>
                                <th>Kategorien</th>
                                <th>Autor</th>
                                <th>Datum</th>
                                <th>Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($posts as $post): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($post['title']) ?></strong>
                                        <br><small>/<a href="/blog/<?= htmlspecialchars($post['slug']) ?>" target="_blank"><?= htmlspecialchars($post['slug']) ?></a></small>
                                    </td>
                                    <td>
                                        <span class="status status-<?= $post['status'] ?>">
                                            <?= ucfirst($post['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($post['categories'] ?? 'Keine') ?></td>
                                    <td><?= htmlspecialchars($post['author_name'] ?? 'Unbekannt') ?></td>
                                    <td><?= date('d.m.Y H:i', strtotime($post['created_at'])) ?></td>
                                    <td>
                                        <div class="actions">
                                            <a href="blog.php?action=edit&id=<?= $post['id'] ?>" class="btn btn-small">Bearbeiten</a>
                                            <a href="/blog/<?= htmlspecialchars($post['slug']) ?>" class="btn btn-small btn-secondary" target="_blank">Ansehen</a>
                                            <a href="blog.php?action=delete&id=<?= $post['id'] ?>" 
                                               class="btn btn-small btn-danger" 
                                               onclick="return confirm('Blog-Post wirklich löschen?')">Löschen</a>
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
                <h2><?= $action === 'new' ? 'Neuen Blog-Post erstellen' : 'Blog-Post bearbeiten' ?></h2>
                <a href="blog.php" class="btn btn-secondary">Zurück zur Liste</a>
            </div>
            
            <div class="card">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $auth->generateCSRFToken() ?>">
                    
                    <div class="form-group">
                        <label for="title">Titel *</label>
                        <input type="text" id="title" name="title" required 
                               value="<?= htmlspecialchars($postData['title'] ?? '') ?>"
                               placeholder="Geben Sie den Titel Ihres Blog-Posts ein">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="slug">URL-Slug</label>
                            <input type="text" id="slug" name="slug" 
                                   value="<?= htmlspecialchars($postData['slug'] ?? '') ?>"
                                   placeholder="Wird automatisch generiert">
                            <div class="slug-preview" id="slug-preview">
                                URL: /blog/<span id="slug-text"><?= htmlspecialchars($postData['slug'] ?? '') ?></span>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                <option value="draft" <?= ($postData['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Entwurf</option>
                                <option value="published" <?= ($postData['status'] ?? '') === 'published' ? 'selected' : '' ?>>Veröffentlicht</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="tags">Tags</label>
                            <input type="text" id="tags" name="tags" 
                                   value="<?= htmlspecialchars($postTags) ?>"
                                   placeholder="PHP, JavaScript, Tutorial">
                            <div class="tags-help">Mit Komma getrennt. Neue Tags werden automatisch erstellt.</div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="excerpt">Kurzbeschreibung/Excerpt</label>
                        <textarea id="excerpt" name="excerpt" 
                                  placeholder="Kurze Zusammenfassung des Beitrags für die Übersicht"><?= htmlspecialchars($postData['excerpt'] ?? '') ?></textarea>
                    </div>
                    
                    <?php if (!empty($allCategories)): ?>
                        <div class="form-group">
                            <label>Kategorien</label>
                            <div class="checkbox-group">
                                <?php foreach ($allCategories as $category): ?>
                                    <div class="checkbox-item">
                                        <input type="checkbox" 
                                               id="cat_<?= $category['id'] ?>" 
                                               name="categories[]" 
                                               value="<?= $category['id'] ?>"
                                               <?= in_array($category['id'], $postCategories) ? 'checked' : '' ?>>
                                        <label for="cat_<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="content">Inhalt</label>
                        <textarea id="content" name="content"><?= htmlspecialchars($postData['content'] ?? '') ?></textarea>
                    </div>
                    
                    <div>
                        <button type="submit" class="btn">
                            <?= $action === 'new' ? 'Blog-Post erstellen' : 'Blog-Post aktualisieren' ?>
                        </button>
                        <a href="blog.php" class="btn btn-secondary">Abbrechen</a>
                    </div>
                </form>
            </div>
            
        <?php elseif ($action === 'delete' && $id): ?>
            <div class="page-header">
                <h2>Blog-Post löschen</h2>
                <a href="blog.php" class="btn btn-secondary">Abbrechen</a>
            </div>
            
            <div class="card">
                <p><strong>Möchten Sie diesen Blog-Post wirklich löschen?</strong></p>
                <p>Titel: <strong><?= htmlspecialchars($postData['title'] ?? '') ?></strong></p>
                <p style="color: #dc3545;"><small>Hinweis: Alle Kommentare und Zuordnungen gehen verloren.</small></p>
                
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $auth->generateCSRFToken() ?>">
                    <div style="display: flex; gap: 1rem;">
                        <button type="submit" class="btn btn-danger">Ja, löschen</button>
                        <a href="blog.php" class="btn btn-secondary">Abbrechen</a>
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
            const slugText = document.getElementById('slug-text');
            
            if (!slugField.value || slugField.dataset.auto !== 'false') {
                let slug = this.value.toLowerCase()
                    .replace(/ä/g, 'ae').replace(/ö/g, 'oe').replace(/ü/g, 'ue').replace(/ß/g, 'ss')
                    .replace(/[^a-z0-9\s-]/g, '')
                    .replace(/[\s-]+/g, '-')
                    .replace(/^-+|-+$/g, '');
                slugField.value = slug;
                slugText.textContent = slug || 'blog-post-slug';
            }
        });
        
        // Mark slug as manually edited
        document.getElementById('slug')?.addEventListener('input', function() {
            this.dataset.auto = 'false';
            document.getElementById('slug-text').textContent = this.value || 'blog-post-slug';
        });
        
        // Initial slug preview
        document.addEventListener('DOMContentLoaded', function() {
            const slugField = document.getElementById('slug');
            const slugText = document.getElementById('slug-text');
            if (slugField && slugText && !slugText.textContent) {
                slugText.textContent = 'blog-post-slug';
            }
        });
    </script>

<?php include 'footer.php'; ?>
