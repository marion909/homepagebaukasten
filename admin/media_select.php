<?php
require_once "../core/init.php";

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();

// Get all media files for popup selection
$mediaFiles = $db->fetchAll("SELECT * FROM media ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medien auswählen - Baukasten CMS</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 1rem; background: #f5f5f5; }
        .media-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 1rem; }
        .media-item { border: 1px solid #ddd; border-radius: 8px; overflow: hidden; background: white; cursor: pointer; transition: transform 0.2s; }
        .media-item:hover { transform: scale(1.02); border-color: #007cba; }
        .media-image { width: 100%; height: 120px; object-fit: cover; }
        .media-info { padding: 0.5rem; text-align: center; }
        .media-filename { font-size: 0.8rem; color: #666; word-break: break-all; }
        .selected { border-color: #007cba; background: #e7f3ff; }
        .actions { margin-bottom: 1rem; }
        .btn { background: #007cba; color: white; padding: 0.5rem 1rem; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn:hover { background: #005a87; }
        .btn:disabled { background: #ccc; cursor: not-allowed; }
    </style>
</head>
<body>
    <div class="actions">
        <button id="selectBtn" class="btn" disabled onclick="selectImage()">Bild auswählen</button>
        <button class="btn" onclick="window.close()">Abbrechen</button>
    </div>
    
    <?php if (empty($mediaFiles)): ?>
        <p>Noch keine Bilder hochgeladen. <a href="media.php" target="_blank">Hier Bilder hochladen</a></p>
    <?php else: ?>
        <div class="media-grid">
            <?php foreach ($mediaFiles as $media): ?>
                <div class="media-item" onclick="selectMediaItem(this)" 
                     data-url="<?= htmlspecialchars($media['file_path']) ?>"
                     data-alt="<?= htmlspecialchars($media['alt_text']) ?>">
                    <img src="<?= htmlspecialchars($media['file_path']) ?>" 
                         alt="<?= htmlspecialchars($media['alt_text']) ?>" 
                         class="media-image">
                    <div class="media-info">
                        <div class="media-filename">
                            <?= htmlspecialchars($media['original_name']) ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <script>
        let selectedItem = null;
        
        function selectMediaItem(item) {
            // Remove previous selection
            if (selectedItem) {
                selectedItem.classList.remove('selected');
            }
            
            // Select new item
            selectedItem = item;
            item.classList.add('selected');
            
            // Enable select button
            document.getElementById('selectBtn').disabled = false;
        }
        
        function selectImage() {
            if (selectedItem && window.opener) {
                const url = selectedItem.dataset.url;
                const alt = selectedItem.dataset.alt;
                
                // Check if we're in TinyMCE context
                if (window.opener.tinymce) {
                    const editor = window.opener.tinymce.activeEditor;
                    if (editor) {
                        editor.insertContent(`<img src="${url}" alt="${alt}" style="max-width: 100%; height: auto;">`);
                    }
                }
                
                window.close();
            }
        }
    </script>
</body>
</html>
