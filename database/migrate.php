<?php
/**
 * Database Migration für Version 2.1
 * Erstellt alle notwendigen Tabellen für die neuen Features
 */

require_once '../config.php';
require_once '../core/db.php';

$db = Database::getInstance();

// SQL-Datei lesen
$sqlFile = __DIR__ . '/version_2_1_tables.sql';

if (!file_exists($sqlFile)) {
    die("SQL-Datei nicht gefunden: $sqlFile");
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

echo "<h2>Database Migration für Version 2.1</h2>\n";
echo "<pre>\n";

foreach ($statements as $statement) {
    if (empty(trim($statement))) continue;
    
    try {
        $db->query($statement);
        
        // Tabellennamen extrahieren für bessere Ausgabe
        if (preg_match('/CREATE TABLE.*?`([^`]+)`/', $statement, $matches)) {
            echo "✓ Tabelle '{$matches[1]}' erstellt\n";
        } elseif (preg_match('/INSERT.*?INTO.*?`([^`]+)`/', $statement, $matches)) {
            echo "✓ Daten in '{$matches[1]}' eingefügt\n";
        } else {
            echo "✓ SQL-Statement ausgeführt\n";
        }
        
        $success++;
    } catch (Exception $e) {
        echo "✗ Fehler: " . $e->getMessage() . "\n";
        $errors++;
    }
}

echo "\n=== Migration abgeschlossen ===\n";
echo "Erfolgreich: $success\n";
echo "Fehler: $errors\n";

if ($errors === 0) {
    echo "\n🎉 Alle Version 2.1 Features sind jetzt verfügbar!\n";
    echo "\nNeue Features:\n";
    echo "- Plugin-System\n";
    echo "- SEO-Tools\n";
    echo "- Content-Manager\n";
    echo "- Erweiterte Benutzerverwaltung\n";
} else {
    echo "\n⚠️ Es gab Fehler bei der Migration. Bitte prüfen Sie die Ausgabe.\n";
}

echo "</pre>\n";

// Prüfen, ob alle wichtigen Tabellen existieren
$requiredTables = [
    'content_templates',
    'content_revisions', 
    'plugins',
    'seo_analysis',
    'user_activities'
];

echo "<h3>Tabellen-Prüfung:</h3>\n";
echo "<ul>\n";

foreach ($requiredTables as $table) {
    try {
        $result = $db->query("SHOW TABLES LIKE '$table'");
        if ($result->rowCount() > 0) {
            echo "<li style='color: green;'>✓ $table</li>\n";
        } else {
            echo "<li style='color: red;'>✗ $table (nicht gefunden)</li>\n";
        }
    } catch (Exception $e) {
        echo "<li style='color: red;'>✗ $table (Fehler: {$e->getMessage()})</li>\n";
    }
}

echo "</ul>\n";
?>
