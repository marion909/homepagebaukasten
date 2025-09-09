<?php
require_once "../core/init.php";

$auth = new Auth();
$error = '';

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    header('Location: index.php');
    exit;
}

// Handle login form submission
if ($_POST) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($auth->login($username, $password)) {
        header('Location: index.php');
        exit;
    } else {
        $error = 'Ungültige Anmeldedaten';
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Baukasten CMS</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 0; }
        .login-container { max-width: 400px; margin: 100px auto; background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 1rem; }
        label { display: block; margin-bottom: 0.5rem; font-weight: bold; }
        input[type="text"], input[type="password"] { width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .btn { background: #007cba; color: white; padding: 0.75rem 1.5rem; border: none; border-radius: 4px; cursor: pointer; width: 100%; }
        .btn:hover { background: #005a87; }
        .error { color: #d63384; margin-bottom: 1rem; padding: 0.75rem; background: #f8d7da; border: 1px solid #f5c2c7; border-radius: 4px; }
        h1 { text-align: center; color: #333; margin-bottom: 2rem; }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>Baukasten CMS</h1>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="username">Benutzername:</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="password">Passwort:</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn">Anmelden</button>
        </form>
        
        <p style="text-align: center; margin-top: 2rem; color: #666; font-size: 0.9rem;">
            Standard Login: admin / admin123
        </p>
    </div>
</body>
</html>
