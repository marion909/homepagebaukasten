<?php
/**
 * Post-Installation Sicherheitsmaßnahmen
 * Wird nach erfolgreicher Installation ausgeführt
 */

function secureInstallation() {
    $messages = [];
    
    // 1. .installed Datei erstellen
    $installedFile = __DIR__ . '/../.installed';
    if (!file_exists($installedFile)) {
        file_put_contents($installedFile, date('Y-m-d H:i:s'));
        $messages[] = "✅ Installation als abgeschlossen markiert";
    }
    
    // 2. .htaccess für Install-Ordner erstellen
    $htaccessContent = "# Homepage Baukasten CMS - Install-Schutz
# Nach der Installation erstellt

<Files \"*.php\">
    Order Deny,Allow
    Deny from all
</Files>

<Files \"install.php\">
    Order Deny,Allow
    Deny from all
</Files>

# Nur index.php erlauben (für Umleitung)
<Files \"index.php\">
    Order Allow,Deny
    Allow from all
</Files>

# SQL-Dateien schützen
<Files \"*.sql\">
    Order Deny,Allow
    Deny from all
</Files>

# README schützen
<Files \"README.md\">
    Order Deny,Allow
    Deny from all
</Files>
";
    
    $htaccessFile = __DIR__ . '/.htaccess';
    file_put_contents($htaccessFile, $htaccessContent);
    $messages[] = "✅ Install-Ordner gesichert (.htaccess erstellt)";
    
    // 3. Hauptverzeichnis .htaccess erweitern
    $mainHtaccess = __DIR__ . '/../.htaccess';
    $mainHtaccessContent = "";
    
    if (file_exists($mainHtaccess)) {
        $mainHtaccessContent = file_get_contents($mainHtaccess);
    }
    
    // Security Headers hinzufügen wenn noch nicht vorhanden
    if (strpos($mainHtaccessContent, 'X-Content-Type-Options') === false) {
        $securityHeaders = "
# Homepage Baukasten CMS - Sicherheits-Headers
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection \"1; mode=block\"
    Header always set Referrer-Policy \"strict-origin-when-cross-origin\"
    Header always set Permissions-Policy \"geolocation=(), microphone=(), camera=()\"
</IfModule>

# Sensitive Dateien schützen
<FilesMatch \"\\.(sql|log|ini|conf)$\">
    Order Deny,Allow
    Deny from all
</FilesMatch>

# Config-Dateien schützen
<Files \"config.php\">
    Order Deny,Allow
    Deny from all
</Files>

<Files \".installed\">
    Order Deny,Allow
    Deny from all
</Files>

# Install-Ordner nach Installation sperren
<Directory \"install\">
    Order Deny,Allow
    Deny from all
    <Files \"index.php\">
        Order Allow,Deny
        Allow from all
    </Files>
</Directory>

";
        
        $mainHtaccessContent = $securityHeaders . $mainHtaccessContent;
        file_put_contents($mainHtaccess, $mainHtaccessContent);
        $messages[] = "✅ Sicherheits-Headers hinzugefügt";
    }
    
    // 4. Temporäre Install-Dateien bereinigen
    $tempFiles = [
        __DIR__ . '/temp_install.log',
        __DIR__ . '/install_progress.tmp'
    ];
    
    foreach ($tempFiles as $tempFile) {
        if (file_exists($tempFile)) {
            unlink($tempFile);
        }
    }
    $messages[] = "✅ Temporäre Dateien bereinigt";
    
    // 5. Session-Daten bereinigen
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    unset($_SESSION['install_data']);
    $messages[] = "✅ Install-Session bereinigt";
    
    return $messages;
}

// Automatische Ausführung wenn direkt aufgerufen
if (basename($_SERVER['PHP_SELF']) === 'secure.php') {
    $messages = secureInstallation();
    
    echo "<!DOCTYPE html>
<html lang='de'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Installation gesichert</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .btn { background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 5px 0 0; }
        .btn:hover { background: #005a87; }
    </style>
</head>
<body>
    <h1>Installation erfolgreich gesichert</h1>";
    
    foreach ($messages as $message) {
        echo "<div class='success'>{$message}</div>";
    }
    
    echo "
    <h3>Empfohlene nächste Schritte:</h3>
    <ol>
        <li>Löschen Sie den install-Ordner komplett für maximale Sicherheit</li>
        <li>Ändern Sie die Datenbankpasswörter</li>
        <li>Aktivieren Sie SSL/HTTPS</li>
        <li>Überprüfen Sie die Dateiberechtigungen</li>
    </ol>
    
    <a href='../admin/index.php' class='btn'>Zum Admin-Bereich</a>
    <a href='../index.php' class='btn'>Website ansehen</a>
</body>
</html>";
}
?>
