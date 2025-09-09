<?php
// Basis-Konfiguration
define('DB_HOST', 'localhost');
define('DB_NAME', 'sql_baukasten_ne');
define('DB_USER', 'sql_baukasten_ne');
define('DB_PASS', '7a4cd47db86a98');

// Site-Einstellungen
define('SITE_NAME', 'Homepage Baukasten');
define('SITE_URL', 'https://baukasten.neuhauser.cloud');

// Upload-Einstellungen
define('UPLOAD_PATH', __DIR__ . '/uploads/');
define('UPLOAD_URL', SITE_URL . '/uploads/');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB

// TinyMCE API Key für erweiterte Features
define('TINYMCE_API_KEY', '3cfv67d066zs8v9dwjyfz4hsxpub9yg059k6v3duziykrben');
