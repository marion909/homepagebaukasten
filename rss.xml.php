<?php
require_once "core/init.php";

// Generate and output RSS feed
try {
    RSS::outputFeed(20);
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: text/plain');
    echo 'Error generating RSS feed: ' . $e->getMessage();
}
?>
