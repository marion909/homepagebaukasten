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
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medienverwaltung - Baukasten CMS</title>
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
        .form-group input, .form-group textarea { width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .btn { background: #007cba; color: white; padding: 0.75rem 1.5rem; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn:hover { background: #005a87; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        .btn-small { padding: 0.5rem 1rem; font-size: 0.9rem; }
        .message { background: #d4edda; color: #155724; padding: 1rem; border: 1px solid #c3e6cb; border-radius: 4px; margin-bottom: 1rem; }
        .error { background: #f8d7da; color: #721c24; padding: 1rem; border: 1px solid #f5c6cb; border-radius: 4px; margin-bottom: 1rem; }
        .media-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1rem; }
        .media-item { border: 1px solid #ddd; border-radius: 8px; overflow: hidden; background: white; }
        .media-image { width: 100%; height: 200px; object-fit: cover; }
        .media-info { padding: 1rem; }
        .media-filename { font-weight: bold; margin-bottom: 0.5rem; word-break: break-all; }
        .media-details { font-size: 0.85rem; color: #666; margin-bottom: 1rem; }
        .media-actions { display: flex; gap: 0.5rem; }
        .user-info { color: white; }
        .logout { background: #dc3545; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 4px; }
        .logout:hover { background: #c82333; }
        .upload-area { border: 2px dashed #ddd; border-radius: 8px; padding: 2rem; text-align: center; margin-bottom: 2rem; }
        .upload-area:hover { border-color: #007cba; }
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
            <li><a href="media.php" class="active">Medien</a></li>
            <li><a href="comments.php">Kommentare</a></li>
            <li><a href="seo.php">SEO & Feeds</a></li>
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
        
        <div class="card">
            <h2>Datei hochladen</h2>
            
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= $auth->generateCSRFToken() ?>">
                
                <div class="upload-area">
                    <div class="form-group">
                        <label for="media_file">Datei auswählen (JPEG, PNG, GIF, WebP - max. 5MB)</label>
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
                            <img src="<?= htmlspecialchars($media['file_path']) ?>" 
                                 alt="<?= htmlspecialchars($media['alt_text']) ?>" 
                                 class="media-image">
                            
                            <div class="media-info">
                                <div class="media-filename">
                                    <?= htmlspecialchars($media['original_name']) ?>
                                </div>
                                
                                <div class="media-details">
                                    Größe: <?= number_format($media['file_size'] / 1024, 1) ?> KB<br>
                                    Typ: <?= htmlspecialchars($media['mime_type']) ?><br>
                                    Hochgeladen: <?= date('d.m.Y', strtotime($media['created_at'])) ?>
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
    </div>
    
    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                alert('URL wurde in die Zwischenablage kopiert!');
            }, function(err) {
                console.error('Fehler beim Kopieren: ', err);
            });
        }
        
        // Drag & Drop functionality
        const uploadArea = document.querySelector('.upload-area');
        const fileInput = document.getElementById('media_file');
        
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.style.borderColor = '#007cba';
            this.style.backgroundColor = '#f0f8ff';
        });
        
        uploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.style.borderColor = '#ddd';
            this.style.backgroundColor = 'transparent';
        });
        
        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            this.style.borderColor = '#ddd';
            this.style.backgroundColor = 'transparent';
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
            }
        });
    </script>
</body>
</html>
