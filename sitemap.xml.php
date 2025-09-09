<?php
require_once "core/init.php";

// Generate and output sitemap
try {
    header('Content-Type: application/xml; charset=UTF-8');
    echo Sitemap::generateXML();
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: text/plain');
    echo 'Error generating sitemap: ' . $e->getMessage();
}
?>
