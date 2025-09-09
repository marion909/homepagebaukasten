<?php
require_once "../core/init.php";

$auth = new Auth();
$auth->requireLogin();

if (!$auth->canManageSystem()) {
    $_SESSION['flash_error'] = 'Keine Berechtigung für diese Aktion.';
    header('Location: index.php');
    exit;
}

$pluginManager = PluginManager::getInstance();
$message = '';
$error = '';

// POST-Handler
if ($_POST) {
    if (!$auth->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Ungültiger CSRF-Token';
    } else {
        $action = $_POST['action'] ?? '';
        $plugin = $_POST['plugin'] ?? '';
        
        switch ($action) {
            case 'activate':
                if ($pluginManager->activatePlugin($plugin)) {
                    $message = "Plugin '{$plugin}' erfolgreich aktiviert";
                } else {
                    $error = "Fehler beim Aktivieren des Plugins '{$plugin}'";
                }
                break;
                
            case 'deactivate':
                if ($pluginManager->deactivatePlugin($plugin)) {
                    $message = "Plugin '{$plugin}' erfolgreich deaktiviert";
                } else {
                    $error = "Fehler beim Deaktivieren des Plugins '{$plugin}'";
                }
                break;
                
            case 'delete':
                // Plugin löschen (Implementierung folgt)
                $error = "Plugin-Löschung noch nicht implementiert";
                break;
        }
    }
}

// Verfügbare Plugins scannen
$availablePlugins = $pluginManager->scanPlugins();
$loadedPlugins = $pluginManager->getLoadedPlugins();

$pageTitle = "Plugins";
include 'header.php';
?>

<?php if ($message): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="page-header">
    <h1>Plugin-Verwaltung</h1>
    <p>Erweitern Sie Ihr CMS mit zusätzlichen Funktionen durch Plugins.</p>
</div>

<div class="plugin-container">
    <!-- Plugin Upload -->
    <div class="card">
        <h2>Plugin hochladen</h2>
        <div class="upload-area" id="plugin-upload">
            <div class="upload-content">
                <i class="icon">📦</i>
                <p>Plugin-ZIP-Datei hier ablegen oder klicken zum Auswählen</p>
                <input type="file" id="plugin-file" accept=".zip" hidden>
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('plugin-file').click()">
                    Datei auswählen
                </button>
            </div>
        </div>
    </div>
    
    <!-- Verfügbare Plugins -->
    <div class="card">
        <h2>Verfügbare Plugins (<?= count($availablePlugins) ?>)</h2>
        
        <?php if (empty($availablePlugins)): ?>
            <div class="empty-state">
                <i class="icon">🔌</i>
                <h3>Keine Plugins gefunden</h3>
                <p>Laden Sie ein Plugin hoch oder installieren Sie eines aus dem Plugin-Verzeichnis.</p>
                <a href="#" class="btn btn-primary">Plugin-Verzeichnis durchsuchen</a>
            </div>
        <?php else: ?>
            <div class="plugins-grid">
                <?php foreach ($availablePlugins as $directory => $plugin): ?>
                    <?php 
                    $isActive = $pluginManager->getPluginStatus($directory);
                    $isLoaded = isset($loadedPlugins[$directory]);
                    ?>
                    <div class="plugin-card <?= $isActive ? 'active' : '' ?>">
                        <div class="plugin-header">
                            <div class="plugin-icon">
                                <?php if (file_exists($plugin['path'] . '/icon.png')): ?>
                                    <img src="../plugins/<?= htmlspecialchars($directory) ?>/icon.png" alt="Plugin Icon">
                                <?php else: ?>
                                    <div class="default-icon">🔌</div>
                                <?php endif; ?>
                            </div>
                            <div class="plugin-info">
                                <h3><?= htmlspecialchars($plugin['name']) ?></h3>
                                <p class="plugin-author">von <?= htmlspecialchars($plugin['author'] ?? 'Unbekannt') ?></p>
                                <span class="plugin-version">Version <?= htmlspecialchars($plugin['version'] ?? '1.0') ?></span>
                            </div>
                            <div class="plugin-status">
                                <?php if ($isActive): ?>
                                    <span class="status-badge active">Aktiv</span>
                                <?php else: ?>
                                    <span class="status-badge inactive">Inaktiv</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="plugin-description">
                            <p><?= htmlspecialchars($plugin['description'] ?? 'Keine Beschreibung verfügbar.') ?></p>
                        </div>
                        
                        <div class="plugin-actions">
                            <?php if ($isActive): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="csrf_token" value="<?= $auth->generateCSRFToken() ?>">
                                    <input type="hidden" name="action" value="deactivate">
                                    <input type="hidden" name="plugin" value="<?= htmlspecialchars($directory) ?>">
                                    <button type="submit" class="btn btn-warning" onclick="return confirm('Plugin deaktivieren?')">
                                        Deaktivieren
                                    </button>
                                </form>
                            <?php else: ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="csrf_token" value="<?= $auth->generateCSRFToken() ?>">
                                    <input type="hidden" name="action" value="activate">
                                    <input type="hidden" name="plugin" value="<?= htmlspecialchars($directory) ?>">
                                    <button type="submit" class="btn btn-primary">
                                        Aktivieren
                                    </button>
                                </form>
                            <?php endif; ?>
                            
                            <button type="button" class="btn btn-secondary" onclick="showPluginDetails('<?= htmlspecialchars($directory) ?>')">
                                Details
                            </button>
                            
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="csrf_token" value="<?= $auth->generateCSRFToken() ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="plugin" value="<?= htmlspecialchars($directory) ?>">
                                <button type="submit" class="btn btn-danger" onclick="return confirm('Plugin wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden!')">
                                    Löschen
                                </button>
                            </form>
                        </div>
                        
                        <?php if (isset($plugin['plugin_uri']) && $plugin['plugin_uri']): ?>
                            <div class="plugin-links">
                                <a href="<?= htmlspecialchars($plugin['plugin_uri']) ?>" target="_blank" class="plugin-link">
                                    Plugin-Homepage
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Plugin-Entwicklung -->
    <div class="card">
        <h2>Plugin-Entwicklung</h2>
        <p>Entwickeln Sie eigene Plugins für das Homepage Baukasten CMS.</p>
        
        <div class="dev-links">
            <a href="https://docs.baukasten-cms.de/plugins/development" target="_blank" class="btn btn-secondary">
                <i class="icon">📚</i> Entwickler-Dokumentation
            </a>
            <a href="https://github.com/marion909/homepagebaukasten/tree/main/plugins/example" target="_blank" class="btn btn-secondary">
                <i class="icon">💡</i> Beispiel-Plugin
            </a>
            <button type="button" class="btn btn-primary" onclick="createPluginBoilerplate()">
                <i class="icon">🚀</i> Plugin-Boilerplate erstellen
            </button>
        </div>
    </div>
</div>

<!-- Plugin-Details Modal -->
<div id="plugin-details-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Plugin-Details</h2>
            <button type="button" class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body" id="plugin-details-content">
            <!-- Content wird via JavaScript geladen -->
        </div>
    </div>
</div>

<style>
    .plugin-container {
        display: flex;
        flex-direction: column;
        gap: 2rem;
    }
    
    .upload-area {
        border: 2px dashed #ccc;
        border-radius: 8px;
        padding: 2rem;
        text-align: center;
        transition: border-color 0.3s;
        cursor: pointer;
    }
    
    .upload-area:hover,
    .upload-area.dragover {
        border-color: #007cba;
        background: #f0f8ff;
    }
    
    .upload-content .icon {
        font-size: 3rem;
        margin-bottom: 1rem;
        display: block;
    }
    
    .plugins-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 1.5rem;
        margin-top: 1rem;
    }
    
    .plugin-card {
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 1.5rem;
        background: white;
        transition: all 0.3s;
    }
    
    .plugin-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        transform: translateY(-2px);
    }
    
    .plugin-card.active {
        border-color: #28a745;
        background: #f8fff9;
    }
    
    .plugin-header {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        margin-bottom: 1rem;
    }
    
    .plugin-icon img,
    .default-icon {
        width: 48px;
        height: 48px;
        border-radius: 6px;
        background: #f0f0f0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }
    
    .plugin-info {
        flex: 1;
    }
    
    .plugin-info h3 {
        margin: 0 0 0.25rem 0;
        font-size: 1.1rem;
        color: #333;
    }
    
    .plugin-author {
        margin: 0;
        color: #666;
        font-size: 0.9rem;
    }
    
    .plugin-version {
        background: #e9ecef;
        color: #495057;
        padding: 0.2rem 0.5rem;
        border-radius: 12px;
        font-size: 0.8rem;
        font-weight: 500;
    }
    
    .plugin-status {
        margin-left: auto;
    }
    
    .status-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 12px;
        font-size: 0.8rem;
        font-weight: 600;
    }
    
    .status-badge.active {
        background: #d4edda;
        color: #155724;
    }
    
    .status-badge.inactive {
        background: #f8d7da;
        color: #721c24;
    }
    
    .plugin-description {
        margin-bottom: 1rem;
        color: #555;
        line-height: 1.5;
    }
    
    .plugin-actions {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
        margin-bottom: 0.5rem;
    }
    
    .plugin-actions .btn {
        font-size: 0.85rem;
        padding: 0.4rem 0.8rem;
    }
    
    .plugin-links {
        border-top: 1px solid #e0e0e0;
        padding-top: 0.5rem;
    }
    
    .plugin-link {
        color: #007cba;
        text-decoration: none;
        font-size: 0.9rem;
    }
    
    .plugin-link:hover {
        text-decoration: underline;
    }
    
    .empty-state {
        text-align: center;
        padding: 3rem;
        color: #666;
    }
    
    .empty-state .icon {
        font-size: 4rem;
        margin-bottom: 1rem;
        display: block;
    }
    
    .empty-state h3 {
        margin-bottom: 0.5rem;
        color: #333;
    }
    
    .dev-links {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
    }
    
    .dev-links .btn {
        display: flex;
        align-items: center;
        gap: 0.5rem;
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
        max-width: 600px;
        width: 90%;
        max-height: 80vh;
        overflow-y: auto;
    }
    
    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1.5rem;
        border-bottom: 1px solid #e0e0e0;
    }
    
    .modal-header h2 {
        margin: 0;
    }
    
    .modal-close {
        background: none;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
        color: #666;
    }
    
    .modal-body {
        padding: 1.5rem;
    }
    
    @media (max-width: 768px) {
        .plugins-grid {
            grid-template-columns: 1fr;
        }
        
        .plugin-actions {
            flex-direction: column;
        }
        
        .dev-links {
            flex-direction: column;
        }
    }
</style>

<script>
    // Plugin-Upload Handler
    document.addEventListener('DOMContentLoaded', function() {
        const uploadArea = document.getElementById('plugin-upload');
        const fileInput = document.getElementById('plugin-file');
        
        uploadArea.addEventListener('click', () => fileInput.click());
        
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });
        
        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });
        
        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                handlePluginUpload(files[0]);
            }
        });
        
        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                handlePluginUpload(e.target.files[0]);
            }
        });
    });
    
    function handlePluginUpload(file) {
        if (!file.name.endsWith('.zip')) {
            alert('Bitte wählen Sie eine ZIP-Datei aus.');
            return;
        }
        
        const formData = new FormData();
        formData.append('plugin_file', file);
        formData.append('csrf_token', '<?= $auth->generateCSRFToken() ?>');
        formData.append('action', 'upload');
        
        // Upload-Indikator zeigen
        const uploadContent = document.querySelector('.upload-content');
        uploadContent.innerHTML = '<div class="loading">Uploading... <div class="spinner"></div></div>';
        
        fetch('plugin-upload.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Upload fehlgeschlagen: ' + data.message);
                location.reload();
            }
        })
        .catch(error => {
            alert('Upload-Fehler: ' + error.message);
            location.reload();
        });
    }
    
    function showPluginDetails(directory) {
        fetch(`plugin-details.php?plugin=${encodeURIComponent(directory)}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('plugin-details-content').innerHTML = html;
            document.getElementById('plugin-details-modal').style.display = 'flex';
        })
        .catch(error => {
            alert('Fehler beim Laden der Plugin-Details: ' + error.message);
        });
    }
    
    function closeModal() {
        document.getElementById('plugin-details-modal').style.display = 'none';
    }
    
    function createPluginBoilerplate() {
        const pluginName = prompt('Plugin-Name eingeben:');
        if (pluginName) {
            fetch('plugin-boilerplate.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    name: pluginName,
                    csrf_token: '<?= $auth->generateCSRFToken() ?>'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Plugin-Boilerplate erfolgreich erstellt!');
                    location.reload();
                } else {
                    alert('Fehler: ' + data.message);
                }
            });
        }
    }
    
    // Modal schließen bei Klick außerhalb
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            closeModal();
        }
    });
</script>

<?php include 'footer.php'; ?>
