<?php
require_once '../config.php';
require_once '../core/db.php';
require_once '../core/auth.php';
require_once '../core/Settings.php';

// Authentifizierung prüfen
$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$settings = Settings::getInstance();

$message = '';
$error = '';

// Handle form submissions
if ($_POST) {
    try {
        // Handle general settings
        if (isset($_POST['action']) && $_POST['action'] === 'general') {
            $settingsData = [
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
            
            foreach ($settingsData as $key => $value) {
                $settings->set($key, $value);
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
                $settings->set($key, $value);
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
                $settings->set($key, $value);
            }
            
            $message = 'Sicherheitseinstellungen erfolgreich gespeichert';
        }
        
        // Handle backup creation
        elseif (isset($_POST['action']) && $_POST['action'] === 'create_backup') {
            $backupFile = $settings->createBackup();
            if ($backupFile) {
                $message = 'Backup erfolgreich erstellt: ' . basename($backupFile);
            } else {
                $error = 'Fehler beim Erstellen des Backups';
            }
        }
        
        // Handle backup restore
        elseif (isset($_POST['action']) && $_POST['action'] === 'restore_backup') {
            $backupFile = $_POST['backup_file'] ?? '';
            if ($settings->restoreBackup($backupFile)) {
                $message = 'Backup erfolgreich wiederhergestellt';
            } else {
                $error = 'Fehler beim Wiederherstellen des Backups';
            }
        }
        
        // Handle backup deletion
        elseif (isset($_POST['action']) && $_POST['action'] === 'delete_backup') {
            $backupFile = $_POST['backup_file'] ?? '';
            if ($settings->deleteBackup($backupFile)) {
                $message = 'Backup erfolgreich gelöscht';
            } else {
                $error = 'Fehler beim Löschen des Backups';
            }
        }
        
    } catch (Exception $e) {
        $error = 'Fehler beim Speichern der Einstellungen: ' . $e->getMessage();
    }
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

// Get available backups (fallback wenn Methode nicht existiert)
$backupFiles = [];
if (method_exists($settings, 'getBackupFiles')) {
    $backupFiles = $settings->getBackupFiles();
}

include 'header.php';
?>

<div class="settings-page">
    <div class="page-header">
        <h1><i class="fas fa-cogs"></i> Einstellungen</h1>
        <p>Verwalten Sie alle Systemeinstellungen Ihrer Website</p>
    </div>
    
    <?php if ($message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    
    <div class="settings-container">
        <div class="settings-tabs">
            <div class="tab-nav">
                <button class="tab-btn active" data-tab="general">
                    <i class="fas fa-home"></i> Allgemein
                </button>
                <button class="tab-btn" data-tab="social">
                    <i class="fas fa-share-alt"></i> Social Media
                </button>
                <button class="tab-btn" data-tab="security">
                    <i class="fas fa-shield-alt"></i> Sicherheit
                </button>
                <button class="tab-btn" data-tab="backup">
                    <i class="fas fa-database"></i> Backup
                </button>
            </div>
            
            <!-- Allgemeine Einstellungen -->
            <div class="tab-content active" id="general">
                <form method="POST" class="settings-form">
                    <input type="hidden" name="action" value="general">
                    
                    <div class="form-section">
                        <h3><i class="fas fa-globe"></i> Website-Informationen</h3>
                        
                        <div class="form-group">
                            <label for="site_title">Website-Titel:</label>
                            <input type="text" id="site_title" name="site_title" 
                                   value="<?= htmlspecialchars($settings->get('site_title', 'Homepage Baukasten')) ?>" 
                                   class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="site_description">Website-Beschreibung:</label>
                            <textarea id="site_description" name="site_description" 
                                      class="form-control" rows="3"><?= htmlspecialchars($settings->get('site_description', '')) ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="site_keywords">Meta-Keywords:</label>
                            <input type="text" id="site_keywords" name="site_keywords" 
                                   value="<?= htmlspecialchars($settings->get('site_keywords', '')) ?>" 
                                   class="form-control" placeholder="Komma-getrennte Keywords">
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3><i class="fas fa-envelope"></i> Kontakt-Informationen</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="admin_email">Administrator E-Mail:</label>
                                <input type="email" id="admin_email" name="admin_email" 
                                       value="<?= htmlspecialchars($settings->get('admin_email', '')) ?>" 
                                       class="form-control">
                            </div>
                            
                            <div class="form-group">
                                <label for="contact_email">Kontakt E-Mail:</label>
                                <input type="email" id="contact_email" name="contact_email" 
                                       value="<?= htmlspecialchars($settings->get('contact_email', '')) ?>" 
                                       class="form-control">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="contact_phone">Telefon:</label>
                                <input type="tel" id="contact_phone" name="contact_phone" 
                                       value="<?= htmlspecialchars($settings->get('contact_phone', '')) ?>" 
                                       class="form-control">
                            </div>
                            
                            <div class="form-group">
                                <label for="contact_address">Adresse:</label>
                                <input type="text" id="contact_address" name="contact_address" 
                                       value="<?= htmlspecialchars($settings->get('contact_address', '')) ?>" 
                                       class="form-control">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3><i class="fas fa-paint-brush"></i> Design & Verhalten</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="theme">Theme:</label>
                                <select id="theme" name="theme" class="form-control">
                                    <?php foreach ($availableThemes as $theme): ?>
                                        <option value="<?= htmlspecialchars($theme) ?>" 
                                                <?= $settings->get('theme', 'default') === $theme ? 'selected' : '' ?>>
                                            <?= ucfirst(htmlspecialchars($theme)) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="language">Sprache:</label>
                                <select id="language" name="language" class="form-control">
                                    <option value="de" <?= $settings->get('language', 'de') === 'de' ? 'selected' : '' ?>>Deutsch</option>
                                    <option value="en" <?= $settings->get('language', 'de') === 'en' ? 'selected' : '' ?>>English</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="timezone">Zeitzone:</label>
                                <select id="timezone" name="timezone" class="form-control">
                                    <option value="Europe/Berlin" <?= $settings->get('timezone', 'Europe/Berlin') === 'Europe/Berlin' ? 'selected' : '' ?>>Europe/Berlin</option>
                                    <option value="Europe/Vienna" <?= $settings->get('timezone', 'Europe/Berlin') === 'Europe/Vienna' ? 'selected' : '' ?>>Europe/Vienna</option>
                                    <option value="Europe/Zurich" <?= $settings->get('timezone', 'Europe/Berlin') === 'Europe/Zurich' ? 'selected' : '' ?>>Europe/Zurich</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="items_per_page">Elemente pro Seite:</label>
                                <input type="number" id="items_per_page" name="items_per_page" 
                                       value="<?= htmlspecialchars($settings->get('items_per_page', '10')) ?>" 
                                       class="form-control" min="1" max="100">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3><i class="fas fa-users"></i> Benutzer-Einstellungen</h3>
                        
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="allow_registration" 
                                       <?= $settings->get('allow_registration', '0') === '1' ? 'checked' : '' ?>>
                                <span class="checkmark"></span>
                                Benutzerregistrierung erlauben
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="require_email_verification" 
                                       <?= $settings->get('require_email_verification', '0') === '1' ? 'checked' : '' ?>>
                                <span class="checkmark"></span>
                                E-Mail-Verifizierung erforderlich
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Speichern
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Social Media Einstellungen -->
            <div class="tab-content" id="social">
                <form method="POST" class="settings-form">
                    <input type="hidden" name="action" value="social">
                    
                    <div class="form-section">
                        <h3><i class="fas fa-share-alt"></i> Social Media Links</h3>
                        <p>Geben Sie die vollständigen URLs zu Ihren Social Media Profilen ein.</p>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="facebook_url">
                                    <i class="fab fa-facebook"></i> Facebook:
                                </label>
                                <input type="url" id="facebook_url" name="facebook_url" 
                                       value="<?= htmlspecialchars($settings->get('facebook_url', '')) ?>" 
                                       class="form-control" placeholder="https://facebook.com/username">
                            </div>
                            
                            <div class="form-group">
                                <label for="twitter_url">
                                    <i class="fab fa-twitter"></i> Twitter:
                                </label>
                                <input type="url" id="twitter_url" name="twitter_url" 
                                       value="<?= htmlspecialchars($settings->get('twitter_url', '')) ?>" 
                                       class="form-control" placeholder="https://twitter.com/username">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="instagram_url">
                                    <i class="fab fa-instagram"></i> Instagram:
                                </label>
                                <input type="url" id="instagram_url" name="instagram_url" 
                                       value="<?= htmlspecialchars($settings->get('instagram_url', '')) ?>" 
                                       class="form-control" placeholder="https://instagram.com/username">
                            </div>
                            
                            <div class="form-group">
                                <label for="linkedin_url">
                                    <i class="fab fa-linkedin"></i> LinkedIn:
                                </label>
                                <input type="url" id="linkedin_url" name="linkedin_url" 
                                       value="<?= htmlspecialchars($settings->get('linkedin_url', '')) ?>" 
                                       class="form-control" placeholder="https://linkedin.com/in/username">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="youtube_url">
                                    <i class="fab fa-youtube"></i> YouTube:
                                </label>
                                <input type="url" id="youtube_url" name="youtube_url" 
                                       value="<?= htmlspecialchars($settings->get('youtube_url', '')) ?>" 
                                       class="form-control" placeholder="https://youtube.com/channel/...">
                            </div>
                            
                            <div class="form-group">
                                <label for="github_url">
                                    <i class="fab fa-github"></i> GitHub:
                                </label>
                                <input type="url" id="github_url" name="github_url" 
                                       value="<?= htmlspecialchars($settings->get('github_url', '')) ?>" 
                                       class="form-control" placeholder="https://github.com/username">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Speichern
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Sicherheits-Einstellungen -->
            <div class="tab-content" id="security">
                <form method="POST" class="settings-form">
                    <input type="hidden" name="action" value="security">
                    
                    <div class="form-section">
                        <h3><i class="fas fa-shield-alt"></i> Sicherheitseinstellungen</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="session_timeout">Session-Timeout (Sekunden):</label>
                                <input type="number" id="session_timeout" name="session_timeout" 
                                       value="<?= htmlspecialchars($settings->get('session_timeout', '3600')) ?>" 
                                       class="form-control" min="300" max="86400">
                                <small class="form-text">Standard: 3600 (1 Stunde)</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="max_login_attempts">Max. Login-Versuche:</label>
                                <input type="number" id="max_login_attempts" name="max_login_attempts" 
                                       value="<?= htmlspecialchars($settings->get('max_login_attempts', '5')) ?>" 
                                       class="form-control" min="1" max="20">
                                <small class="form-text">Anzahl fehlgeschlagener Versuche vor Sperrung</small>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="lockout_duration">Sperrzeit (Sekunden):</label>
                                <input type="number" id="lockout_duration" name="lockout_duration" 
                                       value="<?= htmlspecialchars($settings->get('lockout_duration', '900')) ?>" 
                                       class="form-control" min="60" max="3600">
                                <small class="form-text">Standard: 900 (15 Minuten)</small>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="force_ssl" 
                                       <?= $settings->get('force_ssl', '0') === '1' ? 'checked' : '' ?>>
                                <span class="checkmark"></span>
                                SSL/HTTPS erzwingen
                            </label>
                            <small class="form-text">Alle HTTP-Anfragen zu HTTPS umleiten</small>
                        </div>
                        
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="enable_2fa" 
                                       <?= $settings->get('enable_2fa', '0') === '1' ? 'checked' : '' ?>>
                                <span class="checkmark"></span>
                                Zwei-Faktor-Authentifizierung aktivieren
                            </label>
                            <small class="form-text">Zusätzliche Sicherheit durch TOTP</small>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Speichern
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Backup-Einstellungen -->
            <div class="tab-content" id="backup">
                <div class="backup-section">
                    <h3><i class="fas fa-database"></i> Backup & Wiederherstellung</h3>
                    
                    <div class="backup-actions">
                        <form method="POST" style="display: inline-block;">
                            <input type="hidden" name="action" value="create_backup">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-plus"></i> Neues Backup erstellen
                            </button>
                        </form>
                    </div>
                    
                    <?php if (!empty($backupFiles)): ?>
                        <div class="backup-list">
                            <h4>Verfügbare Backups</h4>
                            <div class="backup-items">
                                <?php foreach ($backupFiles as $backup): ?>
                                    <div class="backup-item">
                                        <div class="backup-info">
                                            <strong><?= htmlspecialchars($backup['filename']) ?></strong>
                                            <small>
                                                Erstellt: <?= date('d.m.Y H:i', $backup['created']) ?> 
                                                (<?= $backup['size'] ?>)
                                            </small>
                                        </div>
                                        <div class="backup-actions">
                                            <form method="POST" style="display: inline-block;" 
                                                  onsubmit="return confirm('Möchten Sie dieses Backup wirklich wiederherstellen?')">
                                                <input type="hidden" name="action" value="restore_backup">
                                                <input type="hidden" name="backup_file" value="<?= htmlspecialchars($backup['filename']) ?>">
                                                <button type="submit" class="btn btn-sm btn-warning">
                                                    <i class="fas fa-undo"></i> Wiederherstellen
                                                </button>
                                            </form>
                                            
                                            <form method="POST" style="display: inline-block;" 
                                                  onsubmit="return confirm('Möchten Sie dieses Backup wirklich löschen?')">
                                                <input type="hidden" name="action" value="delete_backup">
                                                <input type="hidden" name="backup_file" value="<?= htmlspecialchars($backup['filename']) ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-trash"></i> Löschen
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-database"></i>
                            <h4>Keine Backups vorhanden</h4>
                            <p>Backup-Funktion noch nicht verfügbar.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.settings-page {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.page-header {
    text-align: center;
    margin-bottom: 40px;
}

.page-header h1 {
    color: #2c3e50;
    margin-bottom: 10px;
}

.settings-container {
    background: white;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    overflow: hidden;
}

.settings-tabs {
    display: flex;
    flex-direction: column;
}

.tab-nav {
    display: flex;
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

.tab-btn {
    flex: 1;
    padding: 20px;
    border: none;
    background: transparent;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 16px;
    font-weight: 500;
    color: #6c757d;
}

.tab-btn:hover {
    background: #e9ecef;
    color: #495057;
}

.tab-btn.active {
    background: white;
    color: #007bff;
    border-bottom: 3px solid #007bff;
}

.tab-btn i {
    margin-right: 8px;
}

.tab-content {
    display: none;
    padding: 40px;
}

.tab-content.active {
    display: block;
}

.settings-form {
    max-width: 800px;
}

.form-section {
    margin-bottom: 40px;
    padding-bottom: 30px;
    border-bottom: 1px solid #e9ecef;
}

.form-section:last-of-type {
    border-bottom: none;
    padding-bottom: 0;
}

.form-section h3 {
    color: #2c3e50;
    margin-bottom: 20px;
    font-size: 1.3em;
    display: flex;
    align-items: center;
}

.form-section h3 i {
    margin-right: 10px;
    color: #007bff;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #495057;
}

.form-control {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.3s ease;
}

.form-control:focus {
    outline: none;
    border-color: #007bff;
}

.form-text {
    display: block;
    margin-top: 5px;
    font-size: 12px;
    color: #6c757d;
}

.checkbox-label {
    display: flex;
    align-items: center;
    cursor: pointer;
    position: relative;
    padding-left: 35px;
    margin-bottom: 15px;
    font-size: 14px;
    user-select: none;
}

.checkbox-label input[type="checkbox"] {
    position: absolute;
    opacity: 0;
    cursor: pointer;
    height: 0;
    width: 0;
}

.checkmark {
    position: absolute;
    left: 0;
    height: 20px;
    width: 20px;
    background-color: #e9ecef;
    border-radius: 4px;
    transition: all 0.3s ease;
}

.checkbox-label:hover input ~ .checkmark {
    background-color: #dee2e6;
}

.checkbox-label input:checked ~ .checkmark {
    background-color: #007bff;
}

.checkmark:after {
    content: "";
    position: absolute;
    display: none;
    left: 7px;
    top: 3px;
    width: 5px;
    height: 10px;
    border: solid white;
    border-width: 0 3px 3px 0;
    transform: rotate(45deg);
}

.checkbox-label input:checked ~ .checkmark:after {
    display: block;
}

.form-actions {
    padding-top: 30px;
    border-top: 1px solid #e9ecef;
    text-align: right;
}

.btn {
    padding: 12px 30px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-primary {
    background: linear-gradient(135deg, #007bff, #0056b3);
    color: white;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #0056b3, #004085);
    transform: translateY(-2px);
}

.btn-success {
    background: linear-gradient(135deg, #28a745, #1e7e34);
    color: white;
}

.btn-warning {
    background: linear-gradient(135deg, #ffc107, #e0a800);
    color: #212529;
}

.btn-danger {
    background: linear-gradient(135deg, #dc3545, #c82333);
    color: white;
}

.btn-sm {
    padding: 8px 16px;
    font-size: 12px;
}

.alert {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-danger {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.backup-section {
    max-width: 800px;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #6c757d;
}

.empty-state i {
    font-size: 3em;
    margin-bottom: 15px;
    opacity: 0.5;
}

@media (max-width: 768px) {
    .tab-nav {
        flex-direction: column;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .tab-content {
        padding: 20px;
    }
}
</style>

<script>
// Tab-Navigation
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        // Alle Tabs deaktivieren
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        
        // Aktiven Tab aktivieren
        btn.classList.add('active');
        document.getElementById(btn.dataset.tab).classList.add('active');
    });
});

// Auto-hide alerts
setTimeout(() => {
    document.querySelectorAll('.alert').forEach(alert => {
        alert.style.opacity = '0';
        alert.style.transform = 'translateY(-10px)';
        setTimeout(() => alert.remove(), 300);
    });
}, 5000);
</script>

<?php include 'footer.php'; ?>
