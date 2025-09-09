<?php
require_once '../config.php';
require_once '../core/db.php';
require_once '../core/auth.php';

// Authentifizierung prüfen
$auth = new Auth();
$auth->requireLogin();

if (!$auth->canManageSystem()) {
    header('Location: index.php');
    exit;
}

$message = '';
$error = '';

// Migration durchführen
if (isset($_POST['migrate'])) {
    try {
        $db = Database::getInstance();
        
        // SQL-Datei lesen
        $sqlFile = '../database/version_2_1_tables.sql';
        
        if (!file_exists($sqlFile)) {
            throw new Exception("SQL-Datei nicht gefunden: $sqlFile");
        }
        
        $sql = file_get_contents($sqlFile);
        
        // SQL in einzelne Statements aufteilen
        $statements = array_filter(
            array_map('trim', explode(';', $sql)), 
            function($statement) {
                return !empty($statement) && !preg_match('/^--/', $statement);
            }
        );
        
        $success = 0;
        $errors = 0;
        $details = [];
        
        foreach ($statements as $statement) {
            if (empty(trim($statement))) continue;
            
            try {
                $db->query($statement);
                
                // Tabellennamen extrahieren
                if (preg_match('/CREATE TABLE.*?`([^`]+)`/', $statement, $matches)) {
                    $details[] = "✓ Tabelle '{$matches[1]}' erstellt";
                } elseif (preg_match('/INSERT.*?INTO.*?`([^`]+)`/', $statement, $matches)) {
                    $details[] = "✓ Daten in '{$matches[1]}' eingefügt";
                }
                
                $success++;
            } catch (Exception $e) {
                $details[] = "✗ Fehler: " . $e->getMessage();
                $errors++;
            }
        }
        
        if ($errors === 0) {
            $message = "Migration erfolgreich abgeschlossen! $success Statements ausgeführt.";
            $_SESSION['migration_details'] = $details;
        } else {
            $error = "Migration teilweise fehlgeschlagen. $success erfolgreich, $errors Fehler.";
            $_SESSION['migration_details'] = $details;
        }
        
    } catch (Exception $e) {
        $error = 'Fehler bei der Migration: ' . $e->getMessage();
    }
}

// Prüfen, welche Tabellen bereits existieren
$db = Database::getInstance();
$requiredTables = [
    'content_templates' => 'Content Templates',
    'content_revisions' => 'Content Revisionen',
    'content_schedule' => 'Content Schedulierung',
    'plugins' => 'Plugin-System',
    'plugin_hooks' => 'Plugin Hooks',
    'seo_analysis' => 'SEO-Analyse',
    'seo_keywords' => 'SEO Keywords',
    'seo_reports' => 'SEO Reports',
    'user_activities' => 'Benutzer-Aktivitäten',
    'user_permissions' => 'Benutzer-Berechtigungen',
    'content_workflow' => 'Content Workflow',
    'content_metadata' => 'Content Metadaten',
    'content_tags' => 'Content Tags',
    'content_tag_relations' => 'Tag-Zuordnungen'
];

$existingTables = [];
$missingTables = [];

foreach ($requiredTables as $table => $description) {
    try {
        $result = $db->query("SHOW TABLES LIKE '$table'");
        if ($result->rowCount() > 0) {
            $existingTables[$table] = $description;
        } else {
            $missingTables[$table] = $description;
        }
    } catch (Exception $e) {
        $missingTables[$table] = $description;
    }
}

$pageTitle = "Version 2.1 Migration";
$currentPage = "migration";
include 'header.php';
?>

<div class="migration-manager">
    <div class="page-header">
        <h2>🚀 Version 2.1 Migration</h2>
        <p>Installation der neuen Features für Plugin-System, SEO-Tools, Content-Manager und erweiterte Benutzerverwaltung</p>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Migration Status -->
    <div class="migration-status">
        <h3>📊 Status der Datenbank-Tabellen</h3>
        
        <?php if (!empty($existingTables)): ?>
        <div class="table-status existing">
            <h4>✅ Bereits vorhanden (<?= count($existingTables) ?>)</h4>
            <ul>
                <?php foreach ($existingTables as $table => $description): ?>
                <li><code><?= $table ?></code> - <?= $description ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <?php if (!empty($missingTables)): ?>
        <div class="table-status missing">
            <h4>❌ Fehlen noch (<?= count($missingTables) ?>)</h4>
            <ul>
                <?php foreach ($missingTables as $table => $description): ?>
                <li><code><?= $table ?></code> - <?= $description ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>

    <!-- Migration Actions -->
    <div class="migration-actions">
        <?php if (!empty($missingTables)): ?>
        <div class="card">
            <h3>🔧 Migration durchführen</h3>
            <p>Die folgenden neuen Features werden installiert:</p>
            <ul class="feature-list">
                <li>🔌 <strong>Plugin-System</strong> - Erweiterte Funktionalität durch Plugins</li>
                <li>🔍 <strong>SEO-Tools</strong> - Suchmaschinenoptimierung und Analyse</li>
                <li>📝 <strong>Content-Manager</strong> - Erweiterte Inhaltsverwaltung mit Templates</li>
                <li>👥 <strong>User-Manager</strong> - Erweiterte Benutzerverwaltung und Berechtigungen</li>
            </ul>
            
            <form method="post" onsubmit="return confirm('Möchten Sie die Migration wirklich durchführen?')">
                <button type="submit" name="migrate" class="btn btn-primary btn-large">
                    🚀 Migration starten
                </button>
            </form>
        </div>
        <?php else: ?>
        <div class="card success">
            <h3>🎉 Migration abgeschlossen!</h3>
            <p>Alle Version 2.1 Features sind bereits installiert und verfügbar.</p>
            <div class="feature-links">
                <a href="plugins.php" class="btn btn-secondary">🔌 Plugin-Manager</a>
                <a href="seo-tools.php" class="btn btn-secondary">🔍 SEO-Tools</a>
                <a href="content-manager.php" class="btn btn-secondary">📝 Content-Manager</a>
                <a href="users.php" class="btn btn-secondary">👥 Benutzer-Manager</a>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Migration Details -->
    <?php if (isset($_SESSION['migration_details'])): ?>
    <div class="migration-details">
        <h3>📋 Migration Details</h3>
        <div class="details-log">
            <?php foreach ($_SESSION['migration_details'] as $detail): ?>
            <div class="log-entry <?= strpos($detail, '✓') !== false ? 'success' : 'error' ?>">
                <?= htmlspecialchars($detail) ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php unset($_SESSION['migration_details']); ?>
    <?php endif; ?>

    <!-- Feature Overview -->
    <div class="feature-overview">
        <h3>🌟 Neue Features in Version 2.1</h3>
        
        <div class="features-grid">
            <div class="feature-card">
                <h4>🔌 Plugin-System</h4>
                <p>Erweitern Sie Ihr CMS mit benutzerdefinierten Plugins. Aktivieren, deaktivieren und konfigurieren Sie Plugins nach Bedarf.</p>
                <ul>
                    <li>Plugin-Verwaltung</li>
                    <li>Hook-System</li>
                    <li>Automatische Updates</li>
                </ul>
            </div>
            
            <div class="feature-card">
                <h4>🔍 SEO-Tools</h4>
                <p>Optimieren Sie Ihre Website für Suchmaschinen mit fortgeschrittenen SEO-Analyse-Tools.</p>
                <ul>
                    <li>SEO-Analyse</li>
                    <li>Keyword-Tracking</li>
                    <li>Performance-Reports</li>
                </ul>
            </div>
            
            <div class="feature-card">
                <h4>📝 Content-Manager</h4>
                <p>Verwalten Sie Inhalte effizienter mit Templates, Revisionen und erweiterten Tools.</p>
                <ul>
                    <li>Content-Templates</li>
                    <li>Versionsverwaltung</li>
                    <li>Workflow-Management</li>
                </ul>
            </div>
            
            <div class="feature-card">
                <h4>👥 User-Manager</h4>
                <p>Erweiterte Benutzerverwaltung mit detaillierten Berechtigungen und Aktivitäts-Tracking.</p>
                <ul>
                    <li>Erweiterte Berechtigungen</li>
                    <li>Aktivitäts-Log</li>
                    <li>Session-Management</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
.migration-manager {
    padding: 2rem 0;
}

.page-header {
    text-align: center;
    margin-bottom: 3rem;
}

.page-header h2 {
    margin-bottom: 0.5rem;
    font-size: 2.5rem;
}

.migration-status {
    margin-bottom: 2rem;
}

.table-status {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    margin-bottom: 1rem;
    border-left: 4px solid;
}

.table-status.existing {
    border-left-color: #28a745;
}

.table-status.missing {
    border-left-color: #dc3545;
}

.table-status ul {
    margin: 0;
    padding-left: 1.5rem;
}

.table-status li {
    margin-bottom: 0.5rem;
}

.table-status code {
    background: #f8f9fa;
    padding: 0.2rem 0.4rem;
    border-radius: 3px;
    font-family: monospace;
}

.migration-actions .card {
    background: white;
    padding: 2rem;
    border-radius: 8px;
    margin-bottom: 2rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.migration-actions .card.success {
    border-left: 4px solid #28a745;
}

.feature-list {
    margin: 1rem 0;
    padding-left: 1.5rem;
}

.feature-list li {
    margin-bottom: 0.5rem;
}

.btn-large {
    padding: 1rem 2rem;
    font-size: 1.1rem;
    font-weight: bold;
}

.feature-links {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    margin-top: 1rem;
}

.migration-details {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    margin-bottom: 2rem;
}

.details-log {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 4px;
    font-family: monospace;
    font-size: 0.9rem;
    max-height: 300px;
    overflow-y: auto;
}

.log-entry {
    margin-bottom: 0.25rem;
}

.log-entry.success {
    color: #28a745;
}

.log-entry.error {
    color: #dc3545;
}

.feature-overview {
    margin-top: 3rem;
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-top: 1.5rem;
}

.feature-card {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.feature-card h4 {
    margin-top: 0;
    margin-bottom: 1rem;
    color: #007cba;
}

.feature-card ul {
    margin: 1rem 0 0 0;
    padding-left: 1.5rem;
}

.feature-card li {
    margin-bottom: 0.25rem;
    color: #666;
}
</style>

<?php include 'footer.php'; ?>
