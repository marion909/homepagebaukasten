<?php
require_once "../core/init.php";

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$user = $auth->getCurrentUser();

// Require admin role for settings
if (!$auth->hasRole('admin')) {
    header('Location: index.php');
    exit;
}

$message = '';
$error = '';

// Handle form submissions
if ($_POST) {
    if (!$auth->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Ungültiger CSRF-Token';
    } else {
        // Update settings
        $settings = [
            'site_title' => trim($_POST['site_title'] ?? ''),
            'site_description' => trim($_POST['site_description'] ?? ''),
            'admin_email' => trim($_POST['admin_email'] ?? ''),
            'theme' => $_POST['theme'] ?? 'default'
        ];
        
        foreach ($settings as $key => $value) {
            $db->query(
                "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
                 ON DUPLICATE KEY UPDATE setting_value = ?",
                [$key, $value, $value]
            );
        }
        
        $message = 'Einstellungen erfolgreich gespeichert';
    }
}

// Get current settings
$currentSettings = [];
$settingRows = $db->fetchAll("SELECT setting_key, setting_value FROM settings");
foreach ($settingRows as $row) {
    $currentSettings[$row['setting_key']] = $row['setting_value'];
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Einstellungen - Baukasten CMS</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background: #f5f5f5; }
        .header { background: #007cba; color: white; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        .nav { background: #005a87; padding: 0; }
        .nav ul { list-style: none; margin: 0; padding: 0; display: flex; }
        .nav li { margin: 0; }
        .nav a { display: block; padding: 1rem 1.5rem; color: white; text-decoration: none; }
        .nav a:hover, .nav a.active { background: #004666; }
        .container { max-width: 800px; margin: 2rem auto; padding: 0 2rem; }
        .card { background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 2rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: bold; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .form-group textarea { height: 100px; resize: vertical; }
        .btn { background: #007cba; color: white; padding: 0.75rem 1.5rem; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn:hover { background: #005a87; }
        .message { background: #d4edda; color: #155724; padding: 1rem; border: 1px solid #c3e6cb; border-radius: 4px; margin-bottom: 1rem; }
        .error { background: #f8d7da; color: #721c24; padding: 1rem; border: 1px solid #f5c6cb; border-radius: 4px; margin-bottom: 1rem; }
        .user-info { color: white; }
        .logout { background: #dc3545; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 4px; }
        .logout:hover { background: #c82333; }
        .info-box { background: #e7f3ff; border: 1px solid #bee5eb; padding: 1rem; border-radius: 4px; margin-bottom: 1rem; }
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
            <li><a href="media.php">Medien</a></li>
            <li><a href="settings.php" class="active">Einstellungen</a></li>
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
            <h2>Website-Einstellungen</h2>
            
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= $auth->generateCSRFToken() ?>">
                
                <div class="form-group">
                    <label for="site_title">Website-Titel</label>
                    <input type="text" id="site_title" name="site_title" 
                           value="<?= htmlspecialchars($currentSettings['site_title'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="site_description">Website-Beschreibung</label>
                    <textarea id="site_description" name="site_description"><?= htmlspecialchars($currentSettings['site_description'] ?? '') ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="admin_email">Administrator E-Mail</label>
                    <input type="email" id="admin_email" name="admin_email" 
                           value="<?= htmlspecialchars($currentSettings['admin_email'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="theme">Theme</label>
                    <select id="theme" name="theme">
                        <option value="default" <?= ($currentSettings['theme'] ?? '') === 'default' ? 'selected' : '' ?>>Standard</option>
                    </select>
                </div>
                
                <button type="submit" class="btn">Einstellungen speichern</button>
            </form>
        </div>
        
        <div class="card">
            <h2>System-Informationen</h2>
            
            <div class="info-box">
                <p><strong>PHP Version:</strong> <?= PHP_VERSION ?></p>
                <p><strong>CMS Version:</strong> 1.0.0</p>
                <p><strong>Upload-Limit:</strong> <?= ini_get('upload_max_filesize') ?></p>
                <p><strong>Max. Ausführungszeit:</strong> <?= ini_get('max_execution_time') ?> Sekunden</p>
                <p><strong>Memory Limit:</strong> <?= ini_get('memory_limit') ?></p>
            </div>
        </div>
        
        <div class="card">
            <h2>Datenbank-Informationen</h2>
            
            <div class="info-box">
                <?php
                $pageCount = $db->fetchOne("SELECT COUNT(*) as count FROM pages")['count'];
                $userCount = $db->fetchOne("SELECT COUNT(*) as count FROM users")['count'];
                $mediaCount = $db->fetchOne("SELECT COUNT(*) as count FROM media")['count'];
                ?>
                <p><strong>Seiten:</strong> <?= $pageCount ?></p>
                <p><strong>Benutzer:</strong> <?= $userCount ?></p>
                <p><strong>Medien-Dateien:</strong> <?= $mediaCount ?></p>
            </div>
        </div>
    </div>
</body>
</html>
