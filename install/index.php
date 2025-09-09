<?php
/**
 * Installationsschutz
 * Verhindert versehentliche Neuinstallation
 */

// Prüfe ob bereits installiert
$configFile = __DIR__ . '/../config.php';
$installedFile = __DIR__ . '/../.installed';

if (file_exists($configFile) && file_exists($installedFile)) {
    // Bereits installiert - umleiten
    header('Location: ../admin/index.php');
    exit;
}

// Prüfe ob Datenbank bereits Tabellen hat
if (file_exists($configFile)) {
    try {
        include $configFile;
        if (defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER')) {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS
            );
            
            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            if (count($tables) > 0) {
                // Tabellen vorhanden - Installation überspringen
                touch($installedFile);
                header('Location: ../admin/index.php');
                exit;
            }
        }
    } catch (PDOException $e) {
        // Datenbankfehler - Installation fortsetzen
    }
}

// Installation ist notwendig - weiterleiten
header('Location: install.php');
exit;
