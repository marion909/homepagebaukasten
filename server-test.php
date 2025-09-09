<?php
// Test-Datei für Root-Verzeichnis
// Diese Datei ins Hauptverzeichnis: /www/wwwroot/baukasten.neuhauser.cloud/index.php

echo "<h1>Baukasten CMS - Server Test</h1>";
echo "<p>PHP Version: " . PHP_VERSION . "</p>";
echo "<p>Server Zeit: " . date('Y-m-d H:i:s') . "</p>";
echo "<p>Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p>Script Name: " . $_SERVER['SCRIPT_NAME'] . "</p>";

// Test Dateizugriff
echo "<h2>Datei-Tests:</h2>";

$files_to_check = [
    'public/index.php',
    'admin/index.php', 
    'core/init.php',
    'uploads/',
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        echo "<p style='color: green;'>✓ $file - EXISTS</p>";
        echo "<p>Permissions: " . substr(sprintf('%o', fileperms($file)), -4) . "</p>";
    } else {
        echo "<p style='color: red;'>✗ $file - NOT FOUND</p>";
    }
}

// Test Datenbankverbindung
echo "<h2>Datenbank-Test:</h2>";
try {
    // Teste mit deinen DB-Daten
    $pdo = new PDO("mysql:host=localhost;charset=utf8", "root", "");
    echo "<p style='color: green;'>✓ MySQL Verbindung erfolgreich</p>";
    
    // Liste verfügbare Datenbanken
    $stmt = $pdo->query("SHOW DATABASES");
    echo "<p>Verfügbare Datenbanken:</p><ul>";
    while ($row = $stmt->fetch()) {
        echo "<li>" . $row[0] . "</li>";
    }
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ Datenbankfehler: " . $e->getMessage() . "</p>";
}

// Navigation zu CMS-Bereichen
echo "<h2>CMS-Navigation:</h2>";
echo "<p><a href='public/'>Frontend (public/)</a></p>";
echo "<p><a href='admin/'>Admin-Bereich (admin/)</a></p>";
echo "<p><a href='public/index.php'>Frontend direkt</a></p>";
echo "<p><a href='admin/login.php'>Admin Login</a></p>";

// Server-Info
echo "<h2>Server-Informationen:</h2>";
echo "<pre>";
echo "SERVER_SOFTWARE: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unbekannt') . "\n";
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'Unbekannt') . "\n";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'Unbekannt') . "\n";
echo "QUERY_STRING: " . ($_SERVER['QUERY_STRING'] ?? 'Leer') . "\n";
echo "</pre>";
?>
