<?php
require_once "../core/init.php";
require_once "../core/SettingsBackup.php";

$auth = new Auth();
$auth->requireLogin();

if (!$auth->canManageSystem()) {
    $_SESSION['flash_error'] = 'Keine Berechtigung für diese Aktion.';
    header('Location: index.php');
    exit;
}

$db = Database::getInstance();
$settingsBackup = new SettingsBackup();

$message = '';
$error = '';

// Handle form submissions
if ($_POST) {
    if (!$auth->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Ungültiger CSRF-Token';
    } else {
        try {
            // Handle general settings
            if (isset($_POST['action']) && $_POST['action'] === 'general') {
                $settings = [
                    'site_title' => trim($_POST['site_title'] ?? ''),
                    'site_description' => trim($_POST['site_description'] ?? ''),
                    'site_keywords' => trim($_POST['site_keywords'] ?? ''),
                    'admin_email' => trim($_POST['admin_email'] ?? ''),
                    'contact_email' => trim($_POST['contact_email'] ?? ''),
                    'contact_phone' => trim($_POST['contact_phone'] ?? ''),
                    'contact_address' => trim($_POST['contact_address'] ?? ''),
                    'theme' => $_POST['theme'] ?? 'default',
                    'timezone' => $_POST['timezone'] ?? 'Europe/Berlin',
                    'language' => $_POST['language'] ?? 'de',
                    'items_per_page' => intval($_POST['items_per_page'] ?? 10),
                    'allow_registration' => isset($_POST['allow_registration']) ? '1' : '0',
                    'require_email_verification' => isset($_POST['require_email_verification']) ? '1' : '0'
                ];
                
                foreach ($settings as $key => $value) {
                    $db->query(
                        "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
                         ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()",
                        [$key, $value, $value]
                    );
                }
                
                $message = 'Allgemeine Einstellungen erfolgreich gespeichert';
            }
            
            // Handle social media settings
            elseif (isset($_POST['action']) && $_POST['action'] === 'social') {
                $socialSettings = [
                    'facebook_url' => trim($_POST['facebook_url'] ?? ''),
                    'twitter_url' => trim($_POST['twitter_url'] ?? ''),
                    'instagram_url' => trim($_POST['instagram_url'] ?? ''),
                    'linkedin_url' => trim($_POST['linkedin_url'] ?? ''),
                    'youtube_url' => trim($_POST['youtube_url'] ?? ''),
                    'github_url' => trim($_POST['github_url'] ?? ''),
                    'discord_url' => trim($_POST['discord_url'] ?? ''),
                    'twitch_url' => trim($_POST['twitch_url'] ?? '')
                ];
                
                foreach ($socialSettings as $key => $value) {
                    $db->query(
                        "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
                         ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()",
                        [$key, $value, $value]
                    );
                }
                
                $message = 'Social Media Einstellungen erfolgreich gespeichert';
            }
            
            // Handle security settings
            elseif (isset($_POST['action']) && $_POST['action'] === 'security') {
                $securitySettings = [
                    'session_timeout' => intval($_POST['session_timeout'] ?? 3600),
                    'max_login_attempts' => intval($_POST['max_login_attempts'] ?? 5),
                    'lockout_duration' => intval($_POST['lockout_duration'] ?? 900),
                    'force_ssl' => isset($_POST['force_ssl']) ? '1' : '0',
                    'enable_2fa' => isset($_POST['enable_2fa']) ? '1' : '0'
                ];
                
                foreach ($securitySettings as $key => $value) {
                    $db->query(
                        "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
                         ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()",
                        [$key, $value, $value]
                    );
                }
                
                $message = 'Sicherheitseinstellungen erfolgreich gespeichert';
            }
            
            // Handle backup creation
            elseif (isset($_POST['action']) && $_POST['action'] === 'create_backup') {
                $backupFile = $settingsBackup->createBackup();
                if ($backupFile) {
                    $message = 'Backup erfolgreich erstellt: ' . basename($backupFile);
                } else {
                    $error = 'Fehler beim Erstellen des Backups';
                }
            }
            
            // Handle backup restore
            elseif (isset($_POST['action']) && $_POST['action'] === 'restore_backup') {
                $backupFile = $_POST['backup_file'] ?? '';
                if ($settingsBackup->restoreBackup($backupFile)) {
                    $message = 'Backup erfolgreich wiederhergestellt';
                    // Reload current settings
                    $currentSettings = [];
                    $results = $db->fetchAll("SELECT setting_key, setting_value FROM settings");
                    foreach ($results as $row) {
                        $currentSettings[$row['setting_key']] = $row['setting_value'];
                    }
                } else {
                    $error = 'Fehler beim Wiederherstellen des Backups';
                }
            }
            
            // Handle backup deletion
            elseif (isset($_POST['action']) && $_POST['action'] === 'delete_backup') {
                $backupFile = $_POST['backup_file'] ?? '';
                if ($settingsBackup->deleteBackup($backupFile)) {
                    $message = 'Backup erfolgreich gelöscht';
                } else {
                    $error = 'Fehler beim Löschen des Backups';
                }
            }
            
        } catch (Exception $e) {
            $error = 'Fehler beim Speichern der Einstellungen: ' . $e->getMessage();
        }
    }
}

// Get current settings
$currentSettings = [];
$results = $db->fetchAll("SELECT setting_key, setting_value FROM settings");
foreach ($results as $row) {
    $currentSettings[$row['setting_key']] = $row['setting_value'];
}

// Get available themes
$themesDir = dirname(__DIR__) . '/themes';
$availableThemes = [];
if (is_dir($themesDir)) {
    $themes = scandir($themesDir);
    foreach ($themes as $theme) {
        if ($theme !== '.' && $theme !== '..' && is_dir($themesDir . '/' . $theme)) {
            $availableThemes[] = $theme;
        }
    }
}

// Get system info
$systemInfo = [
    'php_version' => PHP_VERSION,
    'cms_version' => '1.0.0',
    'upload_limit' => ini_get('upload_max_filesize'),
    'max_execution_time' => ini_get('max_execution_time'),
    'memory_limit' => ini_get('memory_limit'),
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'
];

// Get database stats
try {
    $pageCount = $db->fetchOne("SELECT COUNT(*) as count FROM pages")['count'] ?? 0;
    $userCount = $db->fetchOne("SELECT COUNT(*) as count FROM users")['count'] ?? 0;
    $mediaCount = $db->fetchOne("SELECT COUNT(*) as count FROM media")['count'] ?? 0;
    $commentCount = $db->fetchOne("SELECT COUNT(*) as count FROM comments")['count'] ?? 0;
} catch (Exception $e) {
    $pageCount = $userCount = $mediaCount = $commentCount = 0;
}

// Get available backups
$availableBackups = $settingsBackup->listBackups();

$pageTitle = "Einstellungen";
include 'header.php';
?>
<?php if ($message): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="page-header">
    <h1>System-Einstellungen</h1>
    <p>Verwalten Sie hier die grundlegenden Einstellungen Ihrer Website.</p>
</div>

<!-- Tab Navigation -->
<div class="tab-container">
    <div class="tab-nav">
        <button class="tab-button active" onclick="showTab('general')">
            <i class="icon">⚙️</i> Allgemein
        </button>
        <button class="tab-button" onclick="showTab('social')">
            <i class="icon">🌐</i> Social Media
        </button>
        <button class="tab-button" onclick="showTab('security')">
            <i class="icon">🔒</i> Sicherheit
        </button>
        <button class="tab-button" onclick="showTab('system')">
            <i class="icon">💻</i> System-Info
        </button>
        <button class="tab-button" onclick="showTab('backup')">
            <i class="icon">💾</i> Backup
        </button>
    </div>

    <!-- General Settings Tab -->
    <div id="general-tab" class="tab-content active">
        <div class="card">
            <h2>Allgemeine Website-Einstellungen</h2>
            
            <form method="POST" class="settings-form">
                <input type="hidden" name="csrf_token" value="<?= $auth->generateCSRFToken() ?>">
                <input type="hidden" name="action" value="general">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="site_title">Website-Titel</label>
                        <input type="text" id="site_title" name="site_title" 
                               value="<?= htmlspecialchars($currentSettings['site_title'] ?? '') ?>" 
                               required class="form-control">
                        <small>Der Haupttitel Ihrer Website</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="theme">Design-Theme</label>
                        <select id="theme" name="theme" class="form-control">
                            <?php foreach ($availableThemes as $theme): ?>
                                <option value="<?= htmlspecialchars($theme) ?>" 
                                        <?= ($currentSettings['theme'] ?? 'default') === $theme ? 'selected' : '' ?>>
                                    <?= ucfirst(htmlspecialchars($theme)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small>Wählen Sie das Design-Theme Ihrer Website</small>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="site_description">Website-Beschreibung</label>
                    <textarea id="site_description" name="site_description" 
                              rows="3" class="form-control"><?= htmlspecialchars($currentSettings['site_description'] ?? '') ?></textarea>
                    <small>Eine kurze Beschreibung Ihrer Website für Suchmaschinen</small>
                </div>
                
                <div class="form-group">
                    <label for="site_keywords">Suchbegriffe (Keywords)</label>
                    <input type="text" id="site_keywords" name="site_keywords" 
                           value="<?= htmlspecialchars($currentSettings['site_keywords'] ?? '') ?>" 
                           class="form-control">
                    <small>Komma-getrennte Suchbegriffe für Ihre Website</small>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="admin_email">Administrator E-Mail</label>
                        <input type="email" id="admin_email" name="admin_email" 
                               value="<?= htmlspecialchars($currentSettings['admin_email'] ?? '') ?>" 
                               required class="form-control">
                        <small>E-Mail für System-Benachrichtigungen</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="contact_email">Kontakt E-Mail</label>
                        <input type="email" id="contact_email" name="contact_email" 
                               value="<?= htmlspecialchars($currentSettings['contact_email'] ?? '') ?>" 
                               class="form-control">
                        <small>Öffentliche Kontakt-E-Mail</small>
                    </div>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="contact_phone">Telefonnummer</label>
                        <input type="tel" id="contact_phone" name="contact_phone" 
                               value="<?= htmlspecialchars($currentSettings['contact_phone'] ?? '') ?>" 
                               class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="timezone">Zeitzone</label>
                        <select id="timezone" name="timezone" class="form-control">
                            <option value="Europe/Berlin" <?= ($currentSettings['timezone'] ?? 'Europe/Berlin') === 'Europe/Berlin' ? 'selected' : '' ?>>Europe/Berlin</option>
                            <option value="Europe/Vienna" <?= ($currentSettings['timezone'] ?? '') === 'Europe/Vienna' ? 'selected' : '' ?>>Europe/Vienna</option>
                            <option value="Europe/Zurich" <?= ($currentSettings['timezone'] ?? '') === 'Europe/Zurich' ? 'selected' : '' ?>>Europe/Zurich</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="contact_address">Adresse</label>
                    <textarea id="contact_address" name="contact_address" 
                              rows="2" class="form-control"><?= htmlspecialchars($currentSettings['contact_address'] ?? '') ?></textarea>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="language">Sprache</label>
                        <select id="language" name="language" class="form-control">
                            <option value="de" <?= ($currentSettings['language'] ?? 'de') === 'de' ? 'selected' : '' ?>>Deutsch</option>
                            <option value="en" <?= ($currentSettings['language'] ?? '') === 'en' ? 'selected' : '' ?>>English</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="items_per_page">Einträge pro Seite</label>
                        <input type="number" id="items_per_page" name="items_per_page" 
                               value="<?= htmlspecialchars($currentSettings['items_per_page'] ?? '10') ?>" 
                               min="5" max="100" class="form-control">
                    </div>
                </div>
                
                <div class="form-group">
                    <h3>Benutzer-Einstellungen</h3>
                    <div class="checkbox-group">
                        <div class="form-check">
                            <input type="checkbox" id="allow_registration" name="allow_registration" 
                                   <?= ($currentSettings['allow_registration'] ?? '0') === '1' ? 'checked' : '' ?>>
                            <label for="allow_registration">Neue Benutzer-Registrierung erlauben</label>
                        </div>
                        
                        <div class="form-check">
                            <input type="checkbox" id="require_email_verification" name="require_email_verification" 
                                   <?= ($currentSettings['require_email_verification'] ?? '0') === '1' ? 'checked' : '' ?>>
                            <label for="require_email_verification">E-Mail-Verifizierung erforderlich</label>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="icon">💾</i> Einstellungen speichern
                </button>
            </form>
        </div>
    </div>

    <!-- Social Media Settings Tab -->
    <div id="social-tab" class="tab-content">
        <div class="card">
            <h2>Social Media Einstellungen</h2>
            <p>Verlinken Sie Ihre Social Media Profile mit Ihrer Website.</p>
            
            <form method="POST" class="settings-form">
                <input type="hidden" name="csrf_token" value="<?= $auth->generateCSRFToken() ?>">
                <input type="hidden" name="action" value="social">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="facebook_url">Facebook URL</label>
                        <input type="url" id="facebook_url" name="facebook_url" 
                               value="<?= htmlspecialchars($currentSettings['facebook_url'] ?? '') ?>" 
                               class="form-control" placeholder="https://facebook.com/yourpage">
                    </div>
                    
                    <div class="form-group">
                        <label for="twitter_url">Twitter URL</label>
                        <input type="url" id="twitter_url" name="twitter_url" 
                               value="<?= htmlspecialchars($currentSettings['twitter_url'] ?? '') ?>" 
                               class="form-control" placeholder="https://twitter.com/youraccount">
                    </div>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="instagram_url">Instagram URL</label>
                        <input type="url" id="instagram_url" name="instagram_url" 
                               value="<?= htmlspecialchars($currentSettings['instagram_url'] ?? '') ?>" 
                               class="form-control" placeholder="https://instagram.com/youraccount">
                    </div>
                    
                    <div class="form-group">
                        <label for="linkedin_url">LinkedIn URL</label>
                        <input type="url" id="linkedin_url" name="linkedin_url" 
                               value="<?= htmlspecialchars($currentSettings['linkedin_url'] ?? '') ?>" 
                               class="form-control" placeholder="https://linkedin.com/in/yourprofile">
                    </div>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="youtube_url">YouTube URL</label>
                        <input type="url" id="youtube_url" name="youtube_url" 
                               value="<?= htmlspecialchars($currentSettings['youtube_url'] ?? '') ?>" 
                               class="form-control" placeholder="https://youtube.com/yourchannel">
                    </div>
                    
                    <div class="form-group">
                        <label for="github_url">GitHub URL</label>
                        <input type="url" id="github_url" name="github_url" 
                               value="<?= htmlspecialchars($currentSettings['github_url'] ?? '') ?>" 
                               class="form-control" placeholder="https://github.com/yourusername">
                    </div>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="discord_url">Discord URL</label>
                        <input type="url" id="discord_url" name="discord_url" 
                               value="<?= htmlspecialchars($currentSettings['discord_url'] ?? '') ?>" 
                               class="form-control" placeholder="https://discord.gg/yourserver">
                    </div>
                    
                    <div class="form-group">
                        <label for="twitch_url">Twitch URL</label>
                        <input type="url" id="twitch_url" name="twitch_url" 
                               value="<?= htmlspecialchars($currentSettings['twitch_url'] ?? '') ?>" 
                               class="form-control" placeholder="https://twitch.tv/yourchannel">
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="icon">💾</i> Social Media Links speichern
                </button>
            </form>
        </div>
    </div>

    <!-- Security Settings Tab -->
    <div id="security-tab" class="tab-content">
        <div class="card">
            <h2>Sicherheitseinstellungen</h2>
            <p>Konfigurieren Sie die Sicherheitsaspekte Ihrer Website.</p>
            
            <form method="POST" class="settings-form">
                <input type="hidden" name="csrf_token" value="<?= $auth->generateCSRFToken() ?>">
                <input type="hidden" name="action" value="security">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="session_timeout">Session-Timeout (Sekunden)</label>
                        <input type="number" id="session_timeout" name="session_timeout" 
                               value="<?= htmlspecialchars($currentSettings['session_timeout'] ?? '3600') ?>" 
                               min="300" max="86400" class="form-control">
                        <small>Zeit bis automatischer Abmeldung (Standard: 3600 = 1 Stunde)</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="max_login_attempts">Max. Anmeldeversuche</label>
                        <input type="number" id="max_login_attempts" name="max_login_attempts" 
                               value="<?= htmlspecialchars($currentSettings['max_login_attempts'] ?? '5') ?>" 
                               min="3" max="20" class="form-control">
                        <small>Anzahl erlaubter fehlgeschlagener Anmeldungen</small>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="lockout_duration">Sperrzeit (Sekunden)</label>
                    <input type="number" id="lockout_duration" name="lockout_duration" 
                           value="<?= htmlspecialchars($currentSettings['lockout_duration'] ?? '900') ?>" 
                           min="60" max="7200" class="form-control">
                    <small>Sperrzeit nach zu vielen fehlgeschlagenen Anmeldungen (Standard: 900 = 15 Minuten)</small>
                </div>
                
                <div class="form-group">
                    <h3>Sicherheits-Features</h3>
                    <div class="checkbox-group">
                        <div class="form-check">
                            <input type="checkbox" id="force_ssl" name="force_ssl" 
                                   <?= ($currentSettings['force_ssl'] ?? '0') === '1' ? 'checked' : '' ?>>
                            <label for="force_ssl">HTTPS erzwingen</label>
                            <small>Automatische Weiterleitung zu HTTPS</small>
                        </div>
                        
                        <div class="form-check">
                            <input type="checkbox" id="enable_2fa" name="enable_2fa" 
                                   <?= ($currentSettings['enable_2fa'] ?? '0') === '1' ? 'checked' : '' ?>>
                            <label for="enable_2fa">Zwei-Faktor-Authentifizierung aktivieren</label>
                            <small>Zusätzliche Sicherheitsebene für Anmeldungen</small>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="icon">🔒</i> Sicherheitseinstellungen speichern
                </button>
            </form>
        </div>
    </div>

    <!-- System Information Tab -->
    <div id="system-tab" class="tab-content">
        <div class="dashboard-grid">
            <div class="card">
                <h3>System-Informationen</h3>
                <div class="info-list">
                    <div class="info-item">
                        <span class="info-label">PHP Version:</span>
                        <span class="info-value"><?= htmlspecialchars($systemInfo['php_version']) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">CMS Version:</span>
                        <span class="info-value"><?= htmlspecialchars($systemInfo['cms_version']) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Server Software:</span>
                        <span class="info-value"><?= htmlspecialchars($systemInfo['server_software']) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Upload-Limit:</span>
                        <span class="info-value"><?= htmlspecialchars($systemInfo['upload_limit']) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Memory Limit:</span>
                        <span class="info-value"><?= htmlspecialchars($systemInfo['memory_limit']) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Max. Ausführungszeit:</span>
                        <span class="info-value"><?= htmlspecialchars($systemInfo['max_execution_time']) ?> Sekunden</span>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <h3>Datenbank-Statistiken</h3>
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-number"><?= number_format($pageCount) ?></div>
                        <div class="stat-label">Seiten</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?= number_format($userCount) ?></div>
                        <div class="stat-label">Benutzer</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?= number_format($mediaCount) ?></div>
                        <div class="stat-label">Medien</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?= number_format($commentCount) ?></div>
                        <div class="stat-label">Kommentare</div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <h3>Verfügbare Themes</h3>
                <div class="theme-grid">
                    <?php foreach ($availableThemes as $theme): ?>
                        <div class="theme-item <?= ($currentSettings['theme'] ?? 'default') === $theme ? 'active' : '' ?>">
                            <div class="theme-name"><?= ucfirst(htmlspecialchars($theme)) ?></div>
                            <?php if (($currentSettings['theme'] ?? 'default') === $theme): ?>
                                <span class="theme-status">Aktiv</span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Backup & Restore Tab -->
    <div id="backup-tab" class="tab-content">
        <div class="dashboard-grid">
            <div class="card">
                <h3>Backup erstellen</h3>
                <p>Erstellen Sie ein vollständiges Backup aller Systemeinstellungen.</p>
                
                <form method="POST" style="margin-bottom: 2rem;">
                    <input type="hidden" name="csrf_token" value="<?= $auth->generateCSRFToken() ?>">
                    <input type="hidden" name="action" value="create_backup">
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="icon">💾</i> Backup jetzt erstellen
                    </button>
                </form>
                
                <div class="info-box">
                    <strong>Hinweis:</strong> Das Backup enthält alle Systemeinstellungen, 
                    aber keine Benutzer-, Seiten- oder Mediendaten.
                </div>
            </div>
            
            <div class="card">
                <h3>Verfügbare Backups</h3>
                
                <?php if (empty($availableBackups)): ?>
                    <p class="text-muted">Noch keine Backups erstellt.</p>
                <?php else: ?>
                    <div class="backup-list">
                        <?php foreach ($availableBackups as $backup): ?>
                            <div class="backup-item">
                                <div class="backup-info">
                                    <div class="backup-name"><?= htmlspecialchars($backup['filename']) ?></div>
                                    <div class="backup-details">
                                        <span>Erstellt: <?= date('d.m.Y H:i', strtotime($backup['created_at'])) ?></span>
                                        <span>Einstellungen: <?= $backup['settings_count'] ?></span>
                                        <span>Größe: <?= $backup['filesize_human'] ?></span>
                                    </div>
                                </div>
                                <div class="backup-actions">
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Sind Sie sicher, dass Sie dieses Backup wiederherstellen möchten? Alle aktuellen Einstellungen werden überschrieben!');">
                                        <input type="hidden" name="csrf_token" value="<?= $auth->generateCSRFToken() ?>">
                                        <input type="hidden" name="action" value="restore_backup">
                                        <input type="hidden" name="backup_file" value="<?= htmlspecialchars($backup['filepath']) ?>">
                                        <button type="submit" class="btn btn-sm btn-success">
                                            <i class="icon">↩️</i> Wiederherstellen
                                        </button>
                                    </form>
                                    
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Sind Sie sicher, dass Sie dieses Backup löschen möchten?');">
                                        <input type="hidden" name="csrf_token" value="<?= $auth->generateCSRFToken() ?>">
                                        <input type="hidden" name="action" value="delete_backup">
                                        <input type="hidden" name="backup_file" value="<?= htmlspecialchars($backup['filename']) ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="icon">🗑️</i> Löschen
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
    .page-header {
        margin-bottom: 2rem;
    }
    
    .page-header h1 {
        margin: 0 0 0.5rem 0;
        color: #333;
    }
    
    .page-header p {
        margin: 0;
        color: #666;
    }
    
    .tab-container {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        overflow: hidden;
    }
    
    .tab-nav {
        display: flex;
        background: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
    }
    
    .tab-button {
        flex: 1;
        padding: 1rem;
        background: none;
        border: none;
        cursor: pointer;
        font-size: 0.9rem;
        color: #6c757d;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }
    
    .tab-button:hover {
        background: #e9ecef;
        color: #495057;
    }
    
    .tab-button.active {
        background: #007cba;
        color: white;
    }
    
    .tab-content {
        display: none;
        padding: 2rem;
    }
    
    .tab-content.active {
        display: block;
    }
    
    .settings-form {
        max-width: none;
    }
    
    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
        margin-bottom: 1.5rem;
    }
    
    .checkbox-group {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        margin-top: 1rem;
    }
    
    .form-check {
        display: flex;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .form-check input[type="checkbox"] {
        margin-top: 0.2rem;
    }
    
    .form-check label {
        flex: 1;
        margin-bottom: 0;
    }
    
    .form-check small {
        display: block;
        color: #6c757d;
        font-size: 0.875rem;
        margin-top: 0.25rem;
    }
    
    .info-list {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .info-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.5rem 0;
        border-bottom: 1px solid #eee;
    }
    
    .info-item:last-child {
        border-bottom: none;
    }
    
    .info-label {
        font-weight: 600;
        color: #495057;
    }
    
    .info-value {
        color: #007cba;
        font-family: monospace;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
    }
    
    .stat-item {
        text-align: center;
        padding: 1rem;
        background: #f8f9fa;
        border-radius: 6px;
    }
    
    .stat-number {
        font-size: 2rem;
        font-weight: bold;
        color: #007cba;
        line-height: 1;
    }
    
    .stat-label {
        font-size: 0.875rem;
        color: #6c757d;
        margin-top: 0.5rem;
    }
    
    .theme-grid {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .theme-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem;
        background: #f8f9fa;
        border-radius: 6px;
        border: 2px solid transparent;
    }
    
    .theme-item.active {
        background: #e7f3ff;
        border-color: #007cba;
    }
    
    .theme-name {
        font-weight: 600;
        color: #495057;
    }
    
    .theme-status {
        background: #28a745;
        color: white;
        padding: 0.25rem 0.5rem;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: bold;
    }
    
    .info-box {
        background: #e7f3ff;
        border: 1px solid #b3d9ff;
        border-radius: 6px;
        padding: 1rem;
        margin-top: 1rem;
        font-size: 0.9rem;
        color: #0056b3;
    }
    
    .backup-list {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    
    .backup-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem;
        background: #f8f9fa;
        border-radius: 6px;
        border: 1px solid #dee2e6;
    }
    
    .backup-info {
        flex: 1;
    }
    
    .backup-name {
        font-weight: 600;
        color: #333;
        margin-bottom: 0.5rem;
    }
    
    .backup-details {
        display: flex;
        gap: 1rem;
        font-size: 0.875rem;
        color: #6c757d;
    }
    
    .backup-actions {
        display: flex;
        gap: 0.5rem;
    }
    
    .btn-sm {
        padding: 0.375rem 0.75rem;
        font-size: 0.8rem;
    }
    
    .btn-success {
        background: #28a745;
        color: white;
        border: 1px solid #28a745;
    }
    
    .btn-success:hover {
        background: #218838;
        border-color: #1e7e34;
    }
    
    .btn-danger {
        background: #dc3545;
        color: white;
        border: 1px solid #dc3545;
    }
    
    .btn-danger:hover {
        background: #c82333;
        border-color: #bd2130;
    }
    
    .text-muted {
        color: #6c757d;
        font-style: italic;
    }
    
    @media (max-width: 768px) {
        .tab-nav {
            flex-direction: column;
        }
        
        .form-grid {
            grid-template-columns: 1fr;
            gap: 1rem;
        }
        
        .stats-grid {
            grid-template-columns: 1fr;
        }
        
        .tab-content {
            padding: 1rem;
        }
    }
</style>

<script>
    function showTab(tabName) {
        // Hide all tab contents
        const tabContents = document.querySelectorAll('.tab-content');
        tabContents.forEach(content => {
            content.classList.remove('active');
        });
        
        // Remove active class from all tab buttons
        const tabButtons = document.querySelectorAll('.tab-button');
        tabButtons.forEach(button => {
            button.classList.remove('active');
        });
        
        // Show selected tab content
        document.getElementById(tabName + '-tab').classList.add('active');
        
        // Add active class to clicked button
        event.target.classList.add('active');
    }
    
    // Set initial tab based on URL hash or default to general
    document.addEventListener('DOMContentLoaded', function() {
        const hash = window.location.hash.substring(1);
        const validTabs = ['general', 'social', 'security', 'system', 'backup'];
        const activeTab = validTabs.includes(hash) ? hash : 'general';
        
        // Simulate click on the appropriate tab
        const tabButton = document.querySelector(`[onclick="showTab('${activeTab}')"]`);
        if (tabButton) {
            tabButton.click();
        }
    });
</script>

<?php include 'footer.php'; ?>
