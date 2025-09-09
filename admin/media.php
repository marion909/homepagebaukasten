<?php
require_once "../core/init.php";

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$user = $auth->getCurrentUser();

$message = '';
$error = '';

// Handle form submissions
if ($_POST) {
    if (!$auth->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Ungültiger CSRF-Token';
    } else {
        // Handle file upload
        if (isset($_FILES['media_file']) && $_FILES['media_file']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $file = $_FILES['media_file'];
            $originalName = $file['name'];
            $tmpName = $file['tmp_name'];
            $fileSize = $file['size'];
            $mimeType = $file['type'];
            
            // Validate file type
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($mimeType, $allowedTypes)) {
                $error = 'Nur Bilder (JPEG, PNG, GIF, WebP) sind erlaubt';
            } elseif ($fileSize > 5 * 1024 * 1024) { // 5MB limit
                $error = 'Datei zu groß (Maximum: 5MB)';
            } else {
                $extension = pathinfo($originalName, PATHINFO_EXTENSION);
                $filename = uniqid() . '.' . $extension;
                $filepath = $uploadDir . $filename;
                
                if (move_uploaded_file($tmpName, $filepath)) {
                    // Save to database
                    $altText = $_POST['alt_text'] ?? '';
                    $sql = "INSERT INTO media (filename, original_name, mime_type, file_size, file_path, alt_text, uploaded_by) 
                            VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $db->query($sql, [$filename, $originalName, $mimeType, $fileSize, $filepath, $altText, $user['id']]);
                    $message = 'Datei erfolgreich hochgeladen';
                } else {
                    $error = 'Fehler beim Hochladen der Datei';
                }
            }
        }
        
        // Handle delete
        if (isset($_POST['delete_id'])) {
            $deleteId = (int)$_POST['delete_id'];
            $media = $db->fetchOne("SELECT * FROM media WHERE id = ?", [$deleteId]);
            if ($media) {
                // Delete file
                if (file_exists($media['file_path'])) {
                    unlink($media['file_path']);
                }
                // Delete from database
                $db->query("DELETE FROM media WHERE id = ?", [$deleteId]);
                $message = 'Datei erfolgreich gelöscht';
            }
        }
    }
}

// Get messages from URL
if (isset($_GET['message'])) {
    $message = $_GET['message'];
}

// Get all media files
$mediaFiles = $db->fetchAll("SELECT * FROM media ORDER BY created_at DESC");

$pageTitle = "Medienverwaltung";
$currentPage = "media";
include 'header.php';
?>

<style>
    .upload-area {
        border: 2px dashed #ddd;
        border-radius: 8px;
        padding: 2rem;
        text-align: center;
        margin-bottom: 2rem;
        transition: border-color 0.3s;
        cursor: pointer;
    }
    .upload-area:hover {
        border-color: #007cba;
    }
    .upload-area.dragover {
        border-color: #007cba;
        background: #f0f8ff;
    }
    .media-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 1rem;
        margin-top: 2rem;
    }
    .media-item {
        background: white;
        border-radius: 8px;
        padding: 1rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        transition: transform 0.2s;
    }
    .media-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }
    .media-preview {
        width: 100%;
        height: 120px;
        object-fit: cover;
        border-radius: 4px;
        margin-bottom: 0.5rem;
    }
    .media-info {
        font-size: 0.9rem;
    }
    .media-name {
        font-weight: bold;
        margin-bottom: 0.25rem;
        word-break: break-word;
    }
    .media-meta {
        color: #666;
        font-size: 0.8rem;
    }
    .media-actions {
        margin-top: 0.5rem;
        display: flex;
        gap: 0.5rem;
    }
    .file-icon {
        width: 100%;
        height: 120px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f8f9fa;
        border-radius: 4px;
        margin-bottom: 0.5rem;
        font-size: 2rem;
        color: #666;
    }
    .upload-progress {
        display: none;
        margin-top: 1rem;
    }
    .progress-bar {
        width: 100%;
        height: 20px;
        background: #e9ecef;
        border-radius: 10px;
        overflow: hidden;
    }
    .progress-fill {
        height: 100%;
        background: #007cba;
        width: 0%;
        transition: width 0.3s;
    }
</style>



<?php if ($message): ?>
    <div class="message"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card">
    <h2>Datei hochladen</h2>
    
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= $auth->generateCSRFToken() ?>">
        
        <div class="upload-area">
            <div class="form-group">
                <label for="media_file">📁 Datei auswählen oder hierher ziehen</label>
                <p>JPEG, PNG, GIF, WebP - max. 5MB</p>
                <input type="file" id="media_file" name="media_file" accept="image/*" required>
            </div>
        </div>
        
        <div class="form-group">
            <label for="alt_text">Alt-Text (für Barrierefreiheit)</label>
            <input type="text" id="alt_text" name="alt_text" placeholder="Beschreibung des Bildes">
        </div>
        
        <button type="submit" class="btn">Datei hochladen</button>
    </form>
</div>
<div class="card">
    <h2>Medien-Bibliothek</h2>
    
    <?php if (empty($mediaFiles)): ?>
        <p>Noch keine Dateien hochgeladen.</p>
    <?php else: ?>
        <div class="media-grid">
            <?php foreach ($mediaFiles as $media): ?>
                <div class="media-item">
                    <?php if (strpos($media['mime_type'], 'image/') === 0): ?>
                        <img src="<?= htmlspecialchars($media['file_path']) ?>" 
                             alt="<?= htmlspecialchars($media['alt_text']) ?>" 
                             class="media-preview">
                    <?php else: ?>
                        <div class="file-icon">📄</div>
                    <?php endif; ?>
                    
                    <div class="media-info">
                        <div class="media-name">
                            <?= htmlspecialchars($media['original_name']) ?>
                        </div>
                        
                        <div class="media-meta">
                            <?= number_format($media['file_size'] / 1024, 1) ?> KB<br>
                            <?= date('d.m.Y', strtotime($media['created_at'])) ?>
                        </div>
                        
                        <div class="media-actions">
                            <button onclick="copyToClipboard('<?= htmlspecialchars($media['file_path']) ?>')" 
                                    class="btn btn-small">URL kopieren</button>
                            
                            <form method="POST" style="display: inline;" 
                                  onsubmit="return confirm('Datei wirklich löschen?')">
                                <input type="hidden" name="csrf_token" value="<?= $auth->generateCSRFToken() ?>">
                                <input type="hidden" name="delete_id" value="<?= $media['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-small">Löschen</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
    
    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                showNotification('URL wurde in die Zwischenablage kopiert!', 'success');
            }, function(err) {
                console.error('Fehler beim Kopieren: ', err);
                showNotification('Fehler beim Kopieren', 'error');
            });
        }
        
        // Drag & Drop functionality
        const uploadArea = document.querySelector('.upload-area');
        const fileInput = document.getElementById('media_file');
        
        if (uploadArea && fileInput) {
            uploadArea.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.classList.add('dragover');
            });
            
            uploadArea.addEventListener('dragleave', function(e) {
                e.preventDefault();
                this.classList.remove('dragover');
            });
            
            uploadArea.addEventListener('drop', function(e) {
                e.preventDefault();
                this.classList.remove('dragover');
                
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    fileInput.files = files;
                }
            });
            
            uploadArea.addEventListener('click', function() {
                fileInput.click();
            });
        }
    </script>

<?php include 'footer.php'; ?>
