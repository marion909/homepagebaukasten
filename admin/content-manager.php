<?php
require_once '../core/config.php';
require_once '../core/Database.php';
require_once '../core/Auth.php';
require_once '../core/ContentManager.php';

// Authentifizierung prüfen
Auth::requireAuth();

$contentManager = new ContentManager();
$action = $_GET['action'] ?? 'overview';

// AJAX-Anfragen bearbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    switch ($action) {
        case 'search':
            $query = $_POST['query'] ?? '';
            $contentType = $_POST['content_type'] ?? null;
            $filters = [
                'status' => $_POST['status'] ?? null,
                'date_from' => $_POST['date_from'] ?? null,
                'date_to' => $_POST['date_to'] ?? null,
                'limit' => $_POST['limit'] ?? 50
            ];
            
            $results = $contentManager->searchContent($query, $contentType, $filters);
            echo json_encode(['success' => true, 'data' => $results]);
            exit;
            
        case 'bulk_action':
            $bulkAction = $_POST['bulk_action'] ?? '';
            $contentType = $_POST['content_type'] ?? '';
            $contentIds = $_POST['content_ids'] ?? [];
            
            $success = false;
            $message = '';
            
            switch ($bulkAction) {
                case 'publish':
                    $success = $contentManager->bulkUpdateStatus($contentType, $contentIds, 'published');
                    $message = $success ? 'Inhalte veröffentlicht' : 'Fehler beim Veröffentlichen';
                    break;
                case 'draft':
                    $success = $contentManager->bulkUpdateStatus($contentType, $contentIds, 'draft');
                    $message = $success ? 'Inhalte als Entwurf gespeichert' : 'Fehler beim Speichern';
                    break;
                case 'delete':
                    $success = $contentManager->bulkDelete($contentType, $contentIds);
                    $message = $success ? 'Inhalte gelöscht' : 'Fehler beim Löschen';
                    break;
            }
            
            echo json_encode(['success' => $success, 'message' => $message]);
            exit;
            
        case 'duplicate':
            $contentType = $_POST['content_type'] ?? '';
            $contentId = $_POST['content_id'] ?? '';
            $newTitle = $_POST['new_title'] ?? null;
            
            $newId = $contentManager->duplicateContent($contentType, $contentId, $newTitle);
            
            echo json_encode([
                'success' => $newId !== false,
                'message' => $newId ? 'Inhalt dupliziert' : 'Fehler beim Duplizieren',
                'new_id' => $newId
            ]);
            exit;
            
        case 'save_template':
            $contentType = $_POST['content_type'] ?? '';
            $contentId = $_POST['content_id'] ?? '';
            $templateName = $_POST['template_name'] ?? '';
            $description = $_POST['description'] ?? '';
            
            $success = $contentManager->saveAsTemplate($contentType, $contentId, $templateName, $description);
            
            echo json_encode([
                'success' => $success,
                'message' => $success ? 'Template gespeichert' : 'Fehler beim Speichern'
            ]);
            exit;
            
        case 'create_from_template':
            $templateId = $_POST['template_id'] ?? '';
            $newTitle = $_POST['new_title'] ?? '';
            
            $newId = $contentManager->createFromTemplate($templateId, $newTitle);
            
            echo json_encode([
                'success' => $newId !== false,
                'message' => $newId ? 'Inhalt aus Template erstellt' : 'Fehler beim Erstellen',
                'new_id' => $newId
            ]);
            exit;
    }
}

// Daten für die Übersicht laden
$db = Database::getInstance();

$totalPages = $db->fetchOne("SELECT COUNT(*) as count FROM pages")['count'];
$totalBlogPosts = $db->fetchOne("SELECT COUNT(*) as count FROM blog_posts")['count'];
$publishedPages = $db->fetchOne("SELECT COUNT(*) as count FROM pages WHERE status = 'published'")['count'];
$publishedPosts = $db->fetchOne("SELECT COUNT(*) as count FROM blog_posts WHERE status = 'published'")['count'];
$draftPages = $db->fetchOne("SELECT COUNT(*) as count FROM pages WHERE status = 'draft'")['count'];
$draftPosts = $db->fetchOne("SELECT COUNT(*) as count FROM blog_posts WHERE status = 'draft'")['count'];

$recentContent = $contentManager->searchContent('', null, ['limit' => 10]);
$templates = $contentManager->getTemplates();
$categories = $contentManager->getCategories();
$tags = $contentManager->getTags();

include 'header.php';
?>

<div class="content-manager">
    <div class="page-header">
        <h1><i class="fas fa-file-alt"></i> Content Management</h1>
        <p>Erweiterte Verwaltung aller Inhalte</p>
    </div>
    
    <div class="content-tabs">
        <div class="tab-nav">
            <button class="tab-btn active" data-tab="overview">Übersicht</button>
            <button class="tab-btn" data-tab="search">Suche & Filter</button>
            <button class="tab-btn" data-tab="bulk">Bulk-Aktionen</button>
            <button class="tab-btn" data-tab="templates">Templates</button>
            <button class="tab-btn" data-tab="categories">Kategorien</button>
            <button class="tab-btn" data-tab="workflow">Workflow</button>
        </div>
        
        <!-- Übersicht Tab -->
        <div class="tab-content active" id="overview">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-file"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= $totalPages ?></h3>
                        <p>Seiten gesamt</p>
                        <small><?= $publishedPages ?> veröffentlicht, <?= $draftPages ?> Entwürfe</small>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-blog"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= $totalBlogPosts ?></h3>
                        <p>Blog-Posts gesamt</p>
                        <small><?= $publishedPosts ?> veröffentlicht, <?= $draftPosts ?> Entwürfe</small>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-layer-group"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= count($templates) ?></h3>
                        <p>Templates</p>
                        <small>Wiederverwendbare Vorlagen</small>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-tags"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= count($tags) ?></h3>
                        <p>Tags</p>
                        <small>Zur Kategorisierung</small>
                    </div>
                </div>
            </div>
            
            <div class="recent-content">
                <h3>Neueste Inhalte</h3>
                <div class="content-list">
                    <?php foreach ($recentContent as $content): ?>
                        <div class="content-item">
                            <div class="content-info">
                                <h4><?= htmlspecialchars($content['title']) ?></h4>
                                <p>
                                    <span class="content-type"><?= ucfirst($content['content_type'] ?? 'page') ?></span>
                                    <span class="content-status status-<?= $content['status'] ?>"><?= ucfirst($content['status']) ?></span>
                                    <span class="content-date"><?= date('d.m.Y H:i', strtotime($content['created_at'])) ?></span>
                                </p>
                            </div>
                            <div class="content-actions">
                                <button class="btn btn-sm btn-secondary" onclick="duplicateContent('<?= $content['content_type'] ?? 'page' ?>', '<?= $content['id'] ?>')">
                                    <i class="fas fa-copy"></i>
                                </button>
                                <button class="btn btn-sm btn-primary" onclick="editContent('<?= $content['content_type'] ?? 'page' ?>', '<?= $content['id'] ?>')">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Suche & Filter Tab -->
        <div class="tab-content" id="search">
            <div class="search-form">
                <div class="search-controls">
                    <div class="form-group">
                        <label for="search-query">Suchbegriff:</label>
                        <input type="text" id="search-query" class="form-control" placeholder="Titel oder Inhalt durchsuchen...">
                    </div>
                    
                    <div class="form-group">
                        <label for="search-type">Content-Typ:</label>
                        <select id="search-type" class="form-control">
                            <option value="">Alle Typen</option>
                            <option value="page">Seiten</option>
                            <option value="blog_post">Blog-Posts</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="search-status">Status:</label>
                        <select id="search-status" class="form-control">
                            <option value="">Alle Status</option>
                            <option value="published">Veröffentlicht</option>
                            <option value="draft">Entwurf</option>
                            <option value="review">In Überprüfung</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="search-date-from">Von Datum:</label>
                        <input type="date" id="search-date-from" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="search-date-to">Bis Datum:</label>
                        <input type="date" id="search-date-to" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <button id="search-btn" class="btn btn-primary">
                            <i class="fas fa-search"></i> Suchen
                        </button>
                        <button id="reset-search" class="btn btn-secondary">
                            <i class="fas fa-undo"></i> Zurücksetzen
                        </button>
                    </div>
                </div>
            </div>
            
            <div id="search-results" class="search-results" style="display: none;">
                <div class="results-header">
                    <h3>Suchergebnisse</h3>
                    <span id="results-count">0 Ergebnisse</span>
                </div>
                <div id="results-list" class="content-list"></div>
            </div>
        </div>
        
        <!-- Bulk-Aktionen Tab -->
        <div class="tab-content" id="bulk">
            <div class="bulk-actions">
                <h3>Bulk-Aktionen</h3>
                <p>Wählen Sie mehrere Inhalte aus und führen Sie Aktionen darauf aus.</p>
                
                <div class="bulk-controls">
                    <div class="form-group">
                        <label for="bulk-type">Content-Typ:</label>
                        <select id="bulk-type" class="form-control">
                            <option value="page">Seiten</option>
                            <option value="blog_post">Blog-Posts</option>
                        </select>
                    </div>
                    
                    <button id="load-bulk-content" class="btn btn-primary">
                        <i class="fas fa-list"></i> Inhalte laden
                    </button>
                </div>
                
                <div id="bulk-content-list" class="bulk-content" style="display: none;">
                    <div class="bulk-header">
                        <label class="checkbox-label">
                            <input type="checkbox" id="select-all-bulk"> Alle auswählen
                        </label>
                        
                        <div class="bulk-actions-bar">
                            <select id="bulk-action" class="form-control">
                                <option value="">Aktion wählen...</option>
                                <option value="publish">Veröffentlichen</option>
                                <option value="draft">Als Entwurf speichern</option>
                                <option value="delete">Löschen</option>
                            </select>
                            <button id="execute-bulk" class="btn btn-danger" disabled>
                                <i class="fas fa-play"></i> Ausführen
                            </button>
                        </div>
                    </div>
                    
                    <div id="bulk-items" class="bulk-items"></div>
                </div>
            </div>
        </div>
        
        <!-- Templates Tab -->
        <div class="tab-content" id="templates">
            <div class="templates-section">
                <h3>Content-Templates</h3>
                <p>Erstellen und verwalten Sie wiederverwendbare Vorlagen für Ihre Inhalte.</p>
                
                <div class="templates-grid">
                    <?php foreach ($templates as $template): ?>
                        <div class="template-card">
                            <div class="template-header">
                                <h4><?= htmlspecialchars($template['name']) ?></h4>
                                <span class="template-type"><?= ucfirst($template['content_type']) ?></span>
                            </div>
                            <div class="template-description">
                                <?= htmlspecialchars($template['description']) ?>
                            </div>
                            <div class="template-actions">
                                <button class="btn btn-primary" onclick="createFromTemplate('<?= $template['id'] ?>')">
                                    <i class="fas fa-plus"></i> Verwenden
                                </button>
                                <button class="btn btn-secondary" onclick="editTemplate('<?= $template['id'] ?>')">
                                    <i class="fas fa-edit"></i> Bearbeiten
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if (empty($templates)): ?>
                        <div class="empty-state">
                            <i class="fas fa-layer-group"></i>
                            <h4>Noch keine Templates</h4>
                            <p>Speichern Sie Inhalte als Template, um sie wiederzuverwenden.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Kategorien Tab -->
        <div class="tab-content" id="categories">
            <div class="categories-section">
                <h3>Kategorien & Tags</h3>
                
                <div class="categories-tags-grid">
                    <div class="categories-panel">
                        <h4>Kategorien</h4>
                        <div class="category-tree">
                            <?php foreach ($categories as $category): ?>
                                <div class="category-item">
                                    <i class="fas fa-folder"></i>
                                    <?= htmlspecialchars($category['name']) ?>
                                    <?php if (!empty($category['children'])): ?>
                                        <div class="category-children">
                                            <?php foreach ($category['children'] as $child): ?>
                                                <div class="category-child">
                                                    <i class="fas fa-folder-open"></i>
                                                    <?= htmlspecialchars($child['name']) ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <button class="btn btn-primary" onclick="createCategory()">
                            <i class="fas fa-plus"></i> Neue Kategorie
                        </button>
                    </div>
                    
                    <div class="tags-panel">
                        <h4>Tags</h4>
                        <div class="tags-list">
                            <?php foreach ($tags as $tag): ?>
                                <span class="tag-item" style="background-color: <?= htmlspecialchars($tag['color']) ?>">
                                    <?= htmlspecialchars($tag['name']) ?>
                                    <small>(<?= $tag['usage_count'] ?>)</small>
                                </span>
                            <?php endforeach; ?>
                        </div>
                        
                        <button class="btn btn-primary" onclick="createTag()">
                            <i class="fas fa-plus"></i> Neuer Tag
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Workflow Tab -->
        <div class="tab-content" id="workflow">
            <div class="workflow-section">
                <h3>Content-Workflow</h3>
                <p>Verwalten Sie den Veröffentlichungsworkflow für Ihre Inhalte.</p>
                
                <div class="workflow-stages">
                    <div class="stage-card">
                        <h4><i class="fas fa-edit"></i> Entwurf</h4>
                        <p>Inhalte werden erstellt und bearbeitet.</p>
                        <div class="stage-count" id="draft-count">0</div>
                    </div>
                    
                    <div class="stage-arrow">
                        <i class="fas fa-arrow-right"></i>
                    </div>
                    
                    <div class="stage-card">
                        <h4><i class="fas fa-eye"></i> Überprüfung</h4>
                        <p>Inhalte warten auf Freigabe.</p>
                        <div class="stage-count" id="review-count">0</div>
                    </div>
                    
                    <div class="stage-arrow">
                        <i class="fas fa-arrow-right"></i>
                    </div>
                    
                    <div class="stage-card">
                        <h4><i class="fas fa-globe"></i> Veröffentlicht</h4>
                        <p>Inhalte sind online verfügbar.</p>
                        <div class="stage-count" id="published-count">0</div>
                    </div>
                </div>
                
                <div class="pending-reviews" id="pending-reviews">
                    <h4>Pending Reviews</h4>
                    <div class="review-list" id="review-list">
                        <!-- Wird über JavaScript geladen -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modals -->
<div id="duplicate-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Inhalt duplizieren</h3>
            <span class="modal-close">&times;</span>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label for="duplicate-title">Neuer Titel:</label>
                <input type="text" id="duplicate-title" class="form-control">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeDuplicateModal()">Abbrechen</button>
            <button class="btn btn-primary" onclick="confirmDuplicate()">Duplizieren</button>
        </div>
    </div>
</div>

<div id="template-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="template-modal-title">Template erstellen</h3>
            <span class="modal-close">&times;</span>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label for="template-name">Template-Name:</label>
                <input type="text" id="template-name" class="form-control">
            </div>
            <div class="form-group">
                <label for="template-description">Beschreibung:</label>
                <textarea id="template-description" class="form-control" rows="3"></textarea>
            </div>
            <div class="form-group" id="new-title-group" style="display: none;">
                <label for="template-new-title">Titel für neuen Inhalt:</label>
                <input type="text" id="template-new-title" class="form-control">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeTemplateModal()">Abbrechen</button>
            <button class="btn btn-primary" onclick="confirmTemplate()">Erstellen</button>
        </div>
    </div>
</div>

<style>
.content-manager {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

.page-header {
    text-align: center;
    margin-bottom: 40px;
}

.content-tabs {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
}

.tab-nav {
    display: flex;
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    flex-wrap: wrap;
}

.tab-btn {
    flex: 1;
    min-width: 120px;
    padding: 15px 20px;
    border: none;
    background: transparent;
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: 500;
}

.tab-btn:hover {
    background: #e9ecef;
}

.tab-btn.active {
    background: white;
    color: #007bff;
    border-bottom: 2px solid #007bff;
}

.tab-content {
    display: none;
    padding: 30px;
}

.tab-content.active {
    display: block;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}

.stat-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 25px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    gap: 20px;
}

.stat-icon {
    font-size: 2.5em;
    opacity: 0.8;
}

.stat-info h3 {
    margin: 0;
    font-size: 2em;
    font-weight: bold;
}

.stat-info p {
    margin: 5px 0;
    font-size: 1.1em;
}

.stat-info small {
    opacity: 0.8;
    font-size: 0.9em;
}

.content-list {
    background: #f8f9fa;
    border-radius: 8px;
    overflow: hidden;
}

.content-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    border-bottom: 1px solid #e9ecef;
    transition: background-color 0.2s ease;
}

.content-item:hover {
    background: #e9ecef;
}

.content-item:last-child {
    border-bottom: none;
}

.content-info h4 {
    margin: 0 0 5px 0;
    color: #2c3e50;
}

.content-info p {
    margin: 0;
    font-size: 0.9em;
    color: #6c757d;
}

.content-type, .content-status, .content-date {
    margin-right: 15px;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.8em;
}

.content-type {
    background: #e9ecef;
    color: #495057;
}

.content-status {
    color: white;
}

.status-published { background: #28a745; }
.status-draft { background: #6c757d; }
.status-review { background: #ffc107; color: #212529; }

.content-actions {
    display: flex;
    gap: 8px;
}

.search-controls {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
}

.bulk-controls {
    display: flex;
    gap: 20px;
    align-items: end;
    margin-bottom: 20px;
}

.bulk-content {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
}

.bulk-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #dee2e6;
}

.bulk-actions-bar {
    display: flex;
    gap: 10px;
    align-items: center;
}

.bulk-items {
    max-height: 400px;
    overflow-y: auto;
}

.bulk-item {
    display: flex;
    align-items: center;
    padding: 10px;
    border-bottom: 1px solid #e9ecef;
}

.bulk-item:last-child {
    border-bottom: none;
}

.templates-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.template-card {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
    border-left: 4px solid #007bff;
}

.template-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.template-type {
    background: #007bff;
    color: white;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.8em;
}

.template-actions {
    display: flex;
    gap: 10px;
    margin-top: 15px;
}

.categories-tags-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
}

.categories-panel, .tags-panel {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
}

.category-tree {
    margin-bottom: 20px;
}

.category-item {
    padding: 8px 0;
    border-bottom: 1px solid #e9ecef;
}

.category-children {
    margin-left: 20px;
    margin-top: 8px;
}

.category-child {
    padding: 4px 0;
    color: #6c757d;
}

.tags-list {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 20px;
}

.tag-item {
    color: white;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.9em;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.workflow-stages {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 30px;
    margin-bottom: 40px;
}

.stage-card {
    background: #f8f9fa;
    padding: 25px;
    border-radius: 10px;
    text-align: center;
    min-width: 180px;
    border-left: 4px solid #007bff;
}

.stage-count {
    font-size: 2em;
    font-weight: bold;
    color: #007bff;
    margin-top: 10px;
}

.stage-arrow {
    font-size: 1.5em;
    color: #6c757d;
}

.pending-reviews {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
}

.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: white;
    border-radius: 8px;
    max-width: 500px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
}

.modal-header {
    padding: 20px;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-close {
    font-size: 1.5em;
    cursor: pointer;
    color: #6c757d;
}

.modal-body {
    padding: 20px;
}

.modal-footer {
    padding: 20px;
    border-top: 1px solid #e9ecef;
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.empty-state {
    text-align: center;
    color: #6c757d;
    padding: 40px;
}

.empty-state i {
    font-size: 3em;
    margin-bottom: 15px;
    opacity: 0.5;
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .search-controls {
        grid-template-columns: 1fr;
    }
    
    .categories-tags-grid {
        grid-template-columns: 1fr;
    }
    
    .workflow-stages {
        flex-direction: column;
        gap: 20px;
    }
    
    .stage-arrow {
        transform: rotate(90deg);
    }
}
</style>

<script>
// Tab-Navigation
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        
        btn.classList.add('active');
        document.getElementById(btn.dataset.tab).classList.add('active');
    });
});

// Globale Variablen
let currentDuplicateData = null;
let currentTemplateData = null;

// Suche
document.getElementById('search-btn').addEventListener('click', performSearch);
document.getElementById('reset-search').addEventListener('click', resetSearch);

async function performSearch() {
    const formData = new FormData();
    formData.append('query', document.getElementById('search-query').value);
    formData.append('content_type', document.getElementById('search-type').value);
    formData.append('status', document.getElementById('search-status').value);
    formData.append('date_from', document.getElementById('search-date-from').value);
    formData.append('date_to', document.getElementById('search-date-to').value);
    
    try {
        const response = await fetch('content-manager.php?action=search', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            displaySearchResults(result.data);
        } else {
            alert('Fehler bei der Suche');
        }
    } catch (error) {
        alert('Fehler: ' + error.message);
    }
}

function displaySearchResults(results) {
    const resultsContainer = document.getElementById('search-results');
    const resultsList = document.getElementById('results-list');
    const resultsCount = document.getElementById('results-count');
    
    resultsCount.textContent = `${results.length} Ergebnisse`;
    
    resultsList.innerHTML = results.map(item => `
        <div class="content-item">
            <div class="content-info">
                <h4>${escapeHtml(item.title)}</h4>
                <p>
                    <span class="content-type">${item.content_type ? item.content_type.charAt(0).toUpperCase() + item.content_type.slice(1) : 'Page'}</span>
                    <span class="content-status status-${item.status}">${item.status.charAt(0).toUpperCase() + item.status.slice(1)}</span>
                    <span class="content-date">${new Date(item.created_at).toLocaleDateString('de-DE')}</span>
                </p>
            </div>
            <div class="content-actions">
                <button class="btn btn-sm btn-secondary" onclick="duplicateContent('${item.content_type || 'page'}', '${item.id}')">
                    <i class="fas fa-copy"></i>
                </button>
                <button class="btn btn-sm btn-primary" onclick="editContent('${item.content_type || 'page'}', '${item.id}')">
                    <i class="fas fa-edit"></i>
                </button>
            </div>
        </div>
    `).join('');
    
    resultsContainer.style.display = 'block';
}

function resetSearch() {
    document.getElementById('search-query').value = '';
    document.getElementById('search-type').value = '';
    document.getElementById('search-status').value = '';
    document.getElementById('search-date-from').value = '';
    document.getElementById('search-date-to').value = '';
    document.getElementById('search-results').style.display = 'none';
}

// Bulk-Aktionen
document.getElementById('load-bulk-content').addEventListener('click', loadBulkContent);
document.getElementById('select-all-bulk').addEventListener('change', toggleSelectAll);
document.getElementById('bulk-action').addEventListener('change', toggleExecuteButton);
document.getElementById('execute-bulk').addEventListener('click', executeBulkAction);

async function loadBulkContent() {
    const contentType = document.getElementById('bulk-type').value;
    // Vereinfachte Implementierung - lädt über Suche
    const formData = new FormData();
    formData.append('content_type', contentType);
    formData.append('limit', '100');
    
    try {
        const response = await fetch('content-manager.php?action=search', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            displayBulkContent(result.data);
        }
    } catch (error) {
        alert('Fehler: ' + error.message);
    }
}

function displayBulkContent(items) {
    const container = document.getElementById('bulk-items');
    
    container.innerHTML = items.map(item => `
        <div class="bulk-item">
            <label class="checkbox-label">
                <input type="checkbox" class="bulk-checkbox" value="${item.id}">
                <strong>${escapeHtml(item.title)}</strong>
                <span class="content-status status-${item.status}">${item.status}</span>
            </label>
        </div>
    `).join('');
    
    document.getElementById('bulk-content-list').style.display = 'block';
    
    // Event-Listener für Checkboxen
    document.querySelectorAll('.bulk-checkbox').forEach(cb => {
        cb.addEventListener('change', toggleExecuteButton);
    });
}

function toggleSelectAll() {
    const selectAll = document.getElementById('select-all-bulk');
    const checkboxes = document.querySelectorAll('.bulk-checkbox');
    
    checkboxes.forEach(cb => {
        cb.checked = selectAll.checked;
    });
    
    toggleExecuteButton();
}

function toggleExecuteButton() {
    const selectedItems = document.querySelectorAll('.bulk-checkbox:checked');
    const action = document.getElementById('bulk-action').value;
    const executeBtn = document.getElementById('execute-bulk');
    
    executeBtn.disabled = selectedItems.length === 0 || !action;
}

async function executeBulkAction() {
    const selectedItems = Array.from(document.querySelectorAll('.bulk-checkbox:checked')).map(cb => cb.value);
    const action = document.getElementById('bulk-action').value;
    const contentType = document.getElementById('bulk-type').value;
    
    if (!confirm(`Möchten Sie diese Aktion (${action}) auf ${selectedItems.length} Elemente anwenden?`)) {
        return;
    }
    
    const formData = new FormData();
    formData.append('bulk_action', action);
    formData.append('content_type', contentType);
    selectedItems.forEach(id => formData.append('content_ids[]', id));
    
    try {
        const response = await fetch('content-manager.php?action=bulk_action', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert(result.message);
            loadBulkContent(); // Reload
        } else {
            alert('Fehler: ' + result.message);
        }
    } catch (error) {
        alert('Fehler: ' + error.message);
    }
}

// Content-Aktionen
function duplicateContent(contentType, contentId) {
    currentDuplicateData = { contentType, contentId };
    document.getElementById('duplicate-title').value = '';
    document.getElementById('duplicate-modal').style.display = 'flex';
}

function closeDuplicateModal() {
    document.getElementById('duplicate-modal').style.display = 'none';
    currentDuplicateData = null;
}

async function confirmDuplicate() {
    if (!currentDuplicateData) return;
    
    const newTitle = document.getElementById('duplicate-title').value;
    const formData = new FormData();
    formData.append('content_type', currentDuplicateData.contentType);
    formData.append('content_id', currentDuplicateData.contentId);
    if (newTitle) formData.append('new_title', newTitle);
    
    try {
        const response = await fetch('content-manager.php?action=duplicate', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert(result.message);
            closeDuplicateModal();
        } else {
            alert('Fehler: ' + result.message);
        }
    } catch (error) {
        alert('Fehler: ' + error.message);
    }
}

function editContent(contentType, contentId) {
    const editUrls = {
        'page': 'pages.php?action=edit&id=',
        'blog_post': 'blog.php?action=edit&id='
    };
    
    const url = editUrls[contentType] || editUrls['page'];
    window.location.href = url + contentId;
}

// Template-Funktionen
function createFromTemplate(templateId) {
    currentTemplateData = { action: 'create', templateId };
    document.getElementById('template-modal-title').textContent = 'Aus Template erstellen';
    document.getElementById('template-name').value = '';
    document.getElementById('template-description').value = '';
    document.getElementById('new-title-group').style.display = 'block';
    document.getElementById('template-new-title').value = '';
    document.getElementById('template-modal').style.display = 'flex';
}

function saveAsTemplate(contentType, contentId) {
    currentTemplateData = { action: 'save', contentType, contentId };
    document.getElementById('template-modal-title').textContent = 'Als Template speichern';
    document.getElementById('template-name').value = '';
    document.getElementById('template-description').value = '';
    document.getElementById('new-title-group').style.display = 'none';
    document.getElementById('template-modal').style.display = 'flex';
}

function closeTemplateModal() {
    document.getElementById('template-modal').style.display = 'none';
    currentTemplateData = null;
}

async function confirmTemplate() {
    if (!currentTemplateData) return;
    
    const formData = new FormData();
    
    if (currentTemplateData.action === 'create') {
        formData.append('template_id', currentTemplateData.templateId);
        formData.append('new_title', document.getElementById('template-new-title').value);
        
        const response = await fetch('content-manager.php?action=create_from_template', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert(result.message);
            closeTemplateModal();
        } else {
            alert('Fehler: ' + result.message);
        }
    } else if (currentTemplateData.action === 'save') {
        formData.append('content_type', currentTemplateData.contentType);
        formData.append('content_id', currentTemplateData.contentId);
        formData.append('template_name', document.getElementById('template-name').value);
        formData.append('description', document.getElementById('template-description').value);
        
        const response = await fetch('content-manager.php?action=save_template', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert(result.message);
            closeTemplateModal();
        } else {
            alert('Fehler: ' + result.message);
        }
    }
}

// Hilfsfunktionen
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Modal-Close-Events
document.querySelectorAll('.modal-close').forEach(btn => {
    btn.addEventListener('click', () => {
        btn.closest('.modal').style.display = 'none';
    });
});

// Click außerhalb Modal schließt es
document.querySelectorAll('.modal').forEach(modal => {
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.style.display = 'none';
        }
    });
});

// Kategorien und Tags (Platzhalter-Funktionen)
function createCategory() {
    const name = prompt('Kategorie-Name:');
    if (name) {
        // Implementation für neue Kategorie
        alert('Kategorie-Erstellung: ' + name);
    }
}

function createTag() {
    const name = prompt('Tag-Name:');
    if (name) {
        // Implementation für neuen Tag
        alert('Tag-Erstellung: ' + name);
    }
}

function editTemplate(templateId) {
    // Implementation für Template-Bearbeitung
    alert('Template bearbeiten: ' + templateId);
}

// Workflow-Zähler aktualisieren (beim Laden)
document.addEventListener('DOMContentLoaded', function() {
    // Diese Werte kommen aus PHP, aber können auch über AJAX aktualisiert werden
    document.getElementById('draft-count').textContent = '<?= $draftPages + $draftPosts ?>';
    document.getElementById('published-count').textContent = '<?= $publishedPages + $publishedPosts ?>';
    // Review-Count müsste über separate Abfrage ermittelt werden
});
</script>

<?php include 'footer.php'; ?>
