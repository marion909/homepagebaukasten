<?php
require_once "../core/init.php";

// Simple preview for content blocks
$key = $_GET['key'] ?? '';

if (empty($key)) {
    echo 'Kein Block-Key angegeben';
    exit;
}

try {
    $content = ContentBlock::render($key, 'Content-Block nicht gefunden oder inaktiv');
    echo $content;
} catch (Exception $e) {
    echo 'Fehler: ' . htmlspecialchars($e->getMessage());
}
?>
