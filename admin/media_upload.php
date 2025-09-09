<?php
require_once "../core/init.php";

// API endpoint for TinyMCE image uploads
header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Nicht angemeldet']);
    exit;
}

if (!$auth->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'error' => 'Ungültiger CSRF-Token']);
    exit;
}

$db = Database::getInstance();
$user = $auth->getCurrentUser();

if (isset($_FILES['media_file']) && $_FILES['media_file']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = '../uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $file = $_FILES['media_file'];
    $originalName = $file['name'];
    $tmpName = $file['tmp_name'];
    $fileSize = $file['size'];
    $mimeType = $file['type'];
    
    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($mimeType, $allowedTypes)) {
        echo json_encode(['success' => false, 'error' => 'Nur Bilder sind erlaubt']);
        exit;
    }
    
    if ($fileSize > 5 * 1024 * 1024) { // 5MB limit
        echo json_encode(['success' => false, 'error' => 'Datei zu groß (max. 5MB)']);
        exit;
    }
    
    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    if (move_uploaded_file($tmpName, $filepath)) {
        // Save to database
        $sql = "INSERT INTO media (filename, original_name, mime_type, file_size, file_path, uploaded_by) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $db->query($sql, [$filename, $originalName, $mimeType, $fileSize, $filepath, $user['id']]);
        
        // Return the URL for TinyMCE
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $imageUrl = $protocol . '://' . $host . '/uploads/' . $filename;
        
        echo json_encode(['success' => true, 'url' => $imageUrl]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Fehler beim Hochladen']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Keine Datei empfangen']);
}
