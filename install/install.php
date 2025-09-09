<?php
/**
 * Homepage Baukasten CMS - Installationsroutine
 * WordPress-ähnliche Installation für das CMS
 */

// Session starten
session_start();

// Zeitlimit erhöhen für Installation
set_time_limit(300);

// Installation Status
$step = $_GET['step'] ?? 1;
$errors = [];
$success = false;

// Check if already installed
$configFile = __DIR__ . '/../config.php';
$alreadyInstalled = false;

if (file_exists($configFile)) {
    include $configFile;
    // Prüfe ob Datenbank bereits konfiguriert ist
    if (defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER')) {
        try {
            $testConnection = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS
            );
            // Prüfe ob Tabellen existieren
            $result = $testConnection->query("SHOW TABLES LIKE 'users'")->fetchColumn();
            if ($result) {
                $alreadyInstalled = true;
            }
        } catch (PDOException $e) {
            // Datenbankfehler - Installation fortsetzen
        }
    }
}

// Wenn bereits installiert, umleiten
if ($alreadyInstalled && $step == 1) {
    header('Location: ../admin/index.php');
    exit;
}

// POST-Handler
if ($_POST) {
    switch ($step) {
        case 2:
            handleDatabaseConfig();
            break;
        case 3:
            handleSiteConfig();
            break;
        case 4:
            handleAdminUser();
            break;
    }
}

function handleDatabaseConfig() {
    global $errors, $step;
    
    $dbHost = trim($_POST['db_host'] ?? '');
    $dbName = trim($_POST['db_name'] ?? '');
    $dbUser = trim($_POST['db_user'] ?? '');
    $dbPass = $_POST['db_pass'] ?? '';
    
    // Validierung
    if (empty($dbHost)) $errors[] = "Datenbankhost ist erforderlich";
    if (empty($dbName)) $errors[] = "Datenbankname ist erforderlich";
    if (empty($dbUser)) $errors[] = "Datenbankbenutzer ist erforderlich";
    
    if (empty($errors)) {
        // Datenbankverbindung testen
        try {
            $pdo = new PDO(
                "mysql:host={$dbHost};charset=utf8mb4",
                $dbUser,
                $dbPass
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Datenbank erstellen falls sie nicht existiert
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `{$dbName}`");
            
            // Config-Datei erstellen
            createConfigFile($dbHost, $dbName, $dbUser, $dbPass);
            
            // Session-Daten speichern für nächsten Schritt
            $_SESSION['install_data'] = [
                'db_host' => $dbHost,
                'db_name' => $dbName,
                'db_user' => $dbUser,
                'db_pass' => $dbPass
            ];
            
            // Weiter zu Schritt 3
            header('Location: install.php?step=3');
            exit;
            
        } catch (PDOException $e) {
            $errors[] = "Datenbankverbindung fehlgeschlagen: " . $e->getMessage();
        }
    }
}

function handleSiteConfig() {
    global $errors, $step;
    
    $siteTitle = trim($_POST['site_title'] ?? '');
    $siteDescription = trim($_POST['site_description'] ?? '');
    $adminEmail = trim($_POST['admin_email'] ?? '');
    $timezone = $_POST['timezone'] ?? 'Europe/Berlin';
    
    // Validierung
    if (empty($siteTitle)) $errors[] = "Website-Titel ist erforderlich";
    if (empty($adminEmail)) $errors[] = "Administrator E-Mail ist erforderlich";
    if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) $errors[] = "Ungültige E-Mail-Adresse";
    
    if (empty($errors)) {
        $_SESSION['install_data']['site_title'] = $siteTitle;
        $_SESSION['install_data']['site_description'] = $siteDescription;
        $_SESSION['install_data']['admin_email'] = $adminEmail;
        $_SESSION['install_data']['timezone'] = $timezone;
        
        // Weiter zu Schritt 4
        header('Location: install.php?step=4');
        exit;
    }
}

function handleAdminUser() {
    global $errors, $step, $success;
    
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';
    
    // Validierung
    if (empty($username)) $errors[] = "Benutzername ist erforderlich";
    if (strlen($username) < 3) $errors[] = "Benutzername muss mindestens 3 Zeichen lang sein";
    if (empty($email)) $errors[] = "E-Mail ist erforderlich";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Ungültige E-Mail-Adresse";
    if (empty($password)) $errors[] = "Passwort ist erforderlich";
    if (strlen($password) < 6) $errors[] = "Passwort muss mindestens 6 Zeichen lang sein";
    if ($password !== $passwordConfirm) $errors[] = "Passwörter stimmen nicht überein";
    
    if (empty($errors)) {
        try {
            // Vollständige Installation durchführen
            performFullInstallation($username, $email, $password);
            $success = true;
            $step = 5; // Erfolgs-Seite
        } catch (Exception $e) {
            $errors[] = "Installation fehlgeschlagen: " . $e->getMessage();
        }
    }
}

function createConfigFile($dbHost, $dbName, $dbUser, $dbPass) {
    $configContent = "<?php
/**
 * Homepage Baukasten CMS - Konfiguration
 * Automatisch erstellt am " . date('Y-m-d H:i:s') . "
 */

// Datenbankeinstellungen
define('DB_HOST', '{$dbHost}');
define('DB_NAME', '{$dbName}');
define('DB_USER', '{$dbUser}');
define('DB_PASS', '{$dbPass}');
define('DB_CHARSET', 'utf8mb4');

// Sicherheitsschlüssel (automatisch generiert)
define('SECURITY_SALT', '" . generateSecuritySalt() . "');

// Basis-Pfade
define('BASE_PATH', dirname(__FILE__));
define('BASE_URL', 'http://' . \$_SERVER['HTTP_HOST'] . dirname(\$_SERVER['SCRIPT_NAME']));

// Debug-Modus (in Produktion auf false setzen)
define('DEBUG_MODE', false);

// Timezone
date_default_timezone_set('Europe/Berlin');

// Session-Konfiguration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isset(\$_SERVER['HTTPS']));
";
    
    file_put_contents(__DIR__ . '/../config.php', $configContent);
}

function generateSecuritySalt($length = 64) {
    return bin2hex(random_bytes($length / 2));
}

function performFullInstallation($adminUsername, $adminEmail, $adminPassword) {
    // Config laden
    require_once __DIR__ . '/../config.php';
    
    // Datenbankverbindung
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // SQL-Installation ausführen
    $sqlFile = __DIR__ . '/complete_installation.sql';
    $sql = file_get_contents($sqlFile);
    
    // SQL in einzelne Statements aufteilen
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement) && !preg_match('/^--/', $statement)) {
            try {
                $pdo->exec($statement);
            } catch (PDOException $e) {
                // Ignoriere "already exists" Fehler
                if (strpos($e->getMessage(), 'already exists') === false) {
                    throw $e;
                }
            }
        }
    }
    
    // Admin-Benutzer erstellen
    $passwordHash = password_hash($adminPassword, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("
        INSERT INTO users (username, email, password_hash, role, active) 
        VALUES (?, ?, ?, 'admin', 1)
    ");
    $stmt->execute([$adminUsername, $adminEmail, $passwordHash]);
    
    // Website-Einstellungen speichern
    $installData = $_SESSION['install_data'];
    
    $settings = [
        'site_title' => $installData['site_title'],
        'site_description' => $installData['site_description'],
        'admin_email' => $installData['admin_email'],
        'contact_email' => $adminEmail,
        'timezone' => $installData['timezone']
    ];
    
    foreach ($settings as $key => $value) {
        $stmt = $pdo->prepare("
            INSERT INTO settings (setting_key, setting_value) 
            VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE setting_value = ?
        ");
        $stmt->execute([$key, $value, $value]);
    }
    
    // Standard-Seiten erstellen
    createDefaultPages($pdo, $adminUsername, $adminEmail);
    
    // Installation als abgeschlossen markieren
    touch(__DIR__ . '/../.installed');
}

function createDefaultPages($pdo, $adminUsername, $adminEmail) {
    // Admin-Benutzer ID holen
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$adminUsername]);
    $adminId = $stmt->fetchColumn();
    
    $defaultPages = [
        [
            'title' => 'Startseite',
            'slug' => 'home',
            'content' => '<h1>Willkommen bei ' . $_SESSION['install_data']['site_title'] . '</h1>
<p>Herzlich willkommen auf Ihrer neuen Website! Diese Seite wurde automatisch während der Installation erstellt.</p>
<p>Sie können diese Seite über das Admin-Panel bearbeiten und weitere Inhalte hinzufügen.</p>',
            'status' => 'published'
        ],
        [
            'title' => 'Über uns',
            'slug' => 'about',
            'content' => '<h1>Über uns</h1>
<p>Hier können Sie Informationen über Ihr Unternehmen oder sich selbst einfügen.</p>
<p>Diese Seite wurde automatisch erstellt und kann über das Admin-Panel bearbeitet werden.</p>',
            'status' => 'published'
        ],
        [
            'title' => 'Kontakt',
            'slug' => 'contact',
            'content' => '<h1>Kontakt</h1>
<p>Nehmen Sie Kontakt mit uns auf:</p>
<p><strong>E-Mail:</strong> ' . $adminEmail . '</p>
[contact_form]',
            'status' => 'published'
        ],
        [
            'title' => 'Blog',
            'slug' => 'blog',
            'content' => '<h1>Blog</h1>
<p>Hier finden Sie unsere neuesten Artikel:</p>
[blog_list]',
            'status' => 'published'
        ]
    ];
    
    foreach ($defaultPages as $index => $page) {
        $stmt = $pdo->prepare("
            INSERT INTO pages (title, slug, content, status, sort_order, created_by) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $page['title'], 
            $page['slug'], 
            $page['content'], 
            $page['status'], 
            $index + 1, 
            $adminId
        ]);
    }
}

function checkRequirements() {
    $requirements = [
        'PHP Version >= 7.4' => version_compare(PHP_VERSION, '7.4.0', '>='),
        'PDO Extension' => extension_loaded('pdo'),
        'PDO MySQL' => extension_loaded('pdo_mysql'),
        'JSON Extension' => extension_loaded('json'),
        'MBString Extension' => extension_loaded('mbstring'),
        'OpenSSL Extension' => extension_loaded('openssl'),
        'Writable config.php' => is_writable(__DIR__ . '/../') || !file_exists(__DIR__ . '/../config.php')
    ];
    
    return $requirements;
}

$requirements = checkRequirements();
$canInstall = !in_array(false, $requirements);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Homepage Baukasten CMS - Installation</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .install-container {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 600px;
            margin: 2rem;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .logo h1 {
            color: #667eea;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .logo p {
            color: #666;
            font-size: 1.1rem;
        }
        
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
        }
        
        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e0e0e0;
            color: #666;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 10px;
            font-weight: bold;
            position: relative;
        }
        
        .step.active {
            background: #667eea;
            color: white;
        }
        
        .step.completed {
            background: #28a745;
            color: white;
        }
        
        .step:not(:last-child)::after {
            content: '';
            position: absolute;
            right: -20px;
            top: 50%;
            transform: translateY(-50%);
            width: 20px;
            height: 2px;
            background: #e0e0e0;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .form-group small {
            color: #666;
            font-size: 0.9rem;
            margin-top: 0.25rem;
            display: block;
        }
        
        .btn {
            background: #667eea;
            color: white;
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: background 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn:hover {
            background: #5a6fd8;
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .btn-secondary:hover {
            background: #545b62;
        }
        
        .btn-success {
            background: #28a745;
        }
        
        .btn-success:hover {
            background: #218838;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
        }
        
        .alert-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .alert-warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
        }
        
        .requirements-list {
            list-style: none;
        }
        
        .requirements-list li {
            padding: 0.5rem 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .status-icon {
            font-weight: bold;
            font-size: 1.2rem;
        }
        
        .status-ok {
            color: #28a745;
        }
        
        .status-error {
            color: #dc3545;
        }
        
        .text-center {
            text-align: center;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .install-container {
                margin: 1rem;
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="logo">
            <h1>Homepage Baukasten CMS</h1>
            <p>WordPress-ähnliche Installation</p>
        </div>
        
        <?php if ($step < 5): ?>
        <div class="step-indicator">
            <div class="step <?= $step >= 1 ? ($step > 1 ? 'completed' : 'active') : '' ?>">1</div>
            <div class="step <?= $step >= 2 ? ($step > 2 ? 'completed' : 'active') : '' ?>">2</div>
            <div class="step <?= $step >= 3 ? ($step > 3 ? 'completed' : 'active') : '' ?>">3</div>
            <div class="step <?= $step >= 4 ? ($step > 4 ? 'completed' : 'active') : '' ?>">4</div>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <strong>Fehler:</strong><br>
                <?php foreach ($errors as $error): ?>
                    • <?= htmlspecialchars($error) ?><br>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php switch($step): 
            case 1: ?>
                <h2>Schritt 1: System-Anforderungen</h2>
                <p>Überprüfung der System-Anforderungen für die Installation:</p>
                
                <ul class="requirements-list">
                    <?php foreach ($requirements as $requirement => $status): ?>
                        <li>
                            <span><?= htmlspecialchars($requirement) ?></span>
                            <span class="status-icon <?= $status ? 'status-ok' : 'status-error' ?>">
                                <?= $status ? '✓' : '✗' ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                
                <?php if (!$canInstall): ?>
                    <div class="alert alert-error">
                        <strong>Installation nicht möglich!</strong><br>
                        Bitte beheben Sie die oben genannten Probleme und laden Sie die Seite neu.
                    </div>
                <?php else: ?>
                    <div class="alert alert-success">
                        <strong>Alle Anforderungen erfüllt!</strong><br>
                        Die Installation kann fortgesetzt werden.
                    </div>
                    
                    <div class="text-center">
                        <a href="install.php?step=2" class="btn">Installation starten</a>
                    </div>
                <?php endif; ?>
                
            <?php break; case 2: ?>
                <h2>Schritt 2: Datenbank-Konfiguration</h2>
                <p>Geben Sie die Verbindungsdaten für Ihre MySQL-Datenbank ein:</p>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="db_host">Datenbankhost</label>
                        <input type="text" id="db_host" name="db_host" value="<?= htmlspecialchars($_POST['db_host'] ?? 'localhost') ?>" required>
                        <small>Normalerweise "localhost"</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="db_name">Datenbankname</label>
                        <input type="text" id="db_name" name="db_name" value="<?= htmlspecialchars($_POST['db_name'] ?? '') ?>" required>
                        <small>Name der MySQL-Datenbank</small>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="db_user">Benutzername</label>
                            <input type="text" id="db_user" name="db_user" value="<?= htmlspecialchars($_POST['db_user'] ?? '') ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="db_pass">Passwort</label>
                            <input type="password" id="db_pass" name="db_pass" value="<?= htmlspecialchars($_POST['db_pass'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div class="text-center">
                        <button type="submit" class="btn">Datenbank testen</button>
                    </div>
                </form>
                
            <?php break; case 3: ?>
                <h2>Schritt 3: Website-Informationen</h2>
                <p>Konfigurieren Sie die grundlegenden Informationen Ihrer Website:</p>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="site_title">Website-Titel</label>
                        <input type="text" id="site_title" name="site_title" value="<?= htmlspecialchars($_POST['site_title'] ?? 'Meine Website') ?>" required>
                        <small>Der Name Ihrer Website</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="site_description">Website-Beschreibung</label>
                        <textarea id="site_description" name="site_description" rows="3"><?= htmlspecialchars($_POST['site_description'] ?? 'Eine professionelle Website mit Homepage Baukasten CMS') ?></textarea>
                        <small>Kurze Beschreibung für Suchmaschinen</small>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="admin_email">Administrator E-Mail</label>
                            <input type="email" id="admin_email" name="admin_email" value="<?= htmlspecialchars($_POST['admin_email'] ?? '') ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="timezone">Zeitzone</label>
                            <select id="timezone" name="timezone">
                                <option value="Europe/Berlin" <?= ($_POST['timezone'] ?? 'Europe/Berlin') == 'Europe/Berlin' ? 'selected' : '' ?>>Europa/Berlin</option>
                                <option value="Europe/Vienna" <?= ($_POST['timezone'] ?? '') == 'Europe/Vienna' ? 'selected' : '' ?>>Europa/Wien</option>
                                <option value="Europe/Zurich" <?= ($_POST['timezone'] ?? '') == 'Europe/Zurich' ? 'selected' : '' ?>>Europa/Zürich</option>
                                <option value="UTC" <?= ($_POST['timezone'] ?? '') == 'UTC' ? 'selected' : '' ?>>UTC</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="text-center">
                        <button type="submit" class="btn">Weiter</button>
                    </div>
                </form>
                
            <?php break; case 4: ?>
                <h2>Schritt 4: Administrator-Account</h2>
                <p>Erstellen Sie den Administrator-Account für das CMS:</p>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="username">Benutzername</label>
                        <input type="text" id="username" name="username" value="<?= htmlspecialchars($_POST['username'] ?? 'admin') ?>" required>
                        <small>Mindestens 3 Zeichen, keine Leerzeichen</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">E-Mail-Adresse</label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? $_SESSION['install_data']['admin_email'] ?? '') ?>" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="password">Passwort</label>
                            <input type="password" id="password" name="password" required>
                            <small>Mindestens 6 Zeichen</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="password_confirm">Passwort bestätigen</label>
                            <input type="password" id="password_confirm" name="password_confirm" required>
                        </div>
                    </div>
                    
                    <div class="alert alert-warning">
                        <strong>Wichtig:</strong> Merken Sie sich diese Anmeldedaten gut! Sie benötigen sie für den Zugang zum Admin-Bereich.
                    </div>
                    
                    <div class="text-center">
                        <button type="submit" class="btn">Installation abschließen</button>
                    </div>
                </form>
                
            <?php break; case 5: ?>
                <h2>🎉 Installation erfolgreich!</h2>
                
                <div class="alert alert-success">
                    <strong>Herzlichen Glückwunsch!</strong><br>
                    Homepage Baukasten CMS wurde erfolgreich installiert.
                </div>
                
                <h3>Was wurde installiert:</h3>
                <ul style="margin-left: 2rem; margin-bottom: 2rem;">
                    <li>✅ Vollständige Datenbankstruktur</li>
                    <li>✅ Administrator-Account erstellt</li>
                    <li>✅ Grundeinstellungen konfiguriert</li>
                    <li>✅ Standard-Seiten erstellt</li>
                    <li>✅ Sicherheitskonfiguration</li>
                </ul>
                
                <h3>Nächste Schritte:</h3>
                <ol style="margin-left: 2rem; margin-bottom: 2rem;">
                    <li>Melden Sie sich im Admin-Bereich an</li>
                    <li>Passen Sie die Einstellungen an</li>
                    <li>Erstellen Sie Ihre ersten Inhalte</li>
                    <li>Wählen Sie ein Design-Theme</li>
                </ol>
                
                <div class="text-center">
                    <a href="../admin/index.php" class="btn btn-success">Zum Admin-Bereich</a>
                    <a href="../index.php" class="btn btn-secondary">Website ansehen</a>
                </div>
                
                <div class="alert alert-warning" style="margin-top: 2rem;">
                    <strong>Sicherheitshinweis:</strong> Löschen Sie den install-Ordner nach der Installation für erhöhte Sicherheit.
                </div>
                
        <?php endswitch; ?>
    </div>
</body>
</html>
