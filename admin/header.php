<?php
// Admin Header - Einheitlicher Header für alle Admin-Seiten
if (!isset($auth) || !$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$currentUser = $auth->getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Admin') ?> - Baukasten CMS</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 0; 
            padding: 0; 
            background: #f5f5f5; 
        }
        
        .header { 
            background: #007cba; 
            color: white; 
            padding: 1rem 2rem; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            margin: 0;
            font-size: 1.5rem;
        }
        
        .header .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .header .user-info a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        
        .header .user-info a:hover {
            background-color: rgba(255,255,255,0.2);
        }
        
        .nav { 
            background: #005a87; 
            padding: 0; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .nav ul { 
            list-style: none; 
            margin: 0; 
            padding: 0; 
            display: flex; 
            flex-wrap: wrap;
        }
        
        .nav li { 
            margin: 0; 
            position: relative;
        }
        
        .nav a { 
            display: block; 
            padding: 1rem 1.5rem; 
            color: white; 
            text-decoration: none; 
            transition: background-color 0.3s;
            border-bottom: 3px solid transparent;
        }
        
        .nav a:hover { 
            background: #004666; 
        }
        
        .nav a.active {
            background: #004666;
            border-bottom-color: #007cba;
        }
        
        /* Grouped Navigation Styles */
        .nav-group {
            position: relative;
        }
        
        .nav-group-label {
            display: block;
            padding: 1rem 1.5rem;
            color: white;
            font-weight: bold;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: #004666;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .nav-group:hover .nav-group-label {
            background: #003d5c;
        }
        
        .nav-submenu {
            position: absolute;
            top: 100%;
            left: 0;
            background: #004666;
            min-width: 200px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 1000;
            flex-direction: column;
        }
        
        .nav-group:hover .nav-submenu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        
        .nav-submenu li {
            width: 100%;
        }
        
        .nav-submenu a {
            padding: 0.75rem 1.5rem;
            border-bottom: 1px solid #005a87;
            font-size: 0.9rem;
            border-bottom-width: 0;
        }
        
        .nav-submenu a:hover {
            background: #003d5c;
            padding-left: 2rem;
        }
        
        .nav-submenu a.active {
            background: #007cba;
            border-bottom-color: transparent;
        }
        
        .container { 
            max-width: 1200px; 
            margin: 2rem auto; 
            padding: 0 2rem; 
        }
        
        .dashboard-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); 
            gap: 1.5rem; 
            margin-bottom: 2rem; 
        }
        
        .card { 
            background: white; 
            padding: 1.5rem; 
            border-radius: 8px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); 
            transition: box-shadow 0.3s;
        }
        
        .card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .card h3 { 
            margin: 0 0 1rem 0; 
            color: #333; 
            font-size: 1.1rem;
        }
        
        .stat { 
            font-size: 2.5rem; 
            font-weight: bold; 
            color: #007cba; 
            margin: 0.5rem 0; 
        }
        
        .recent-list { 
            list-style: none; 
            padding: 0; 
            margin: 0; 
        }
        
        .recent-list li { 
            padding: 0.5rem 0; 
            border-bottom: 1px solid #eee; 
        }
        
        .recent-list li:last-child { 
            border-bottom: none; 
        }
        
        .btn {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: #007cba;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background: #005a87;
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .btn-secondary:hover {
            background: #545b62;
        }
        
        .btn-danger {
            background: #dc3545;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .btn-success {
            background: #28a745;
        }
        
        .btn-success:hover {
            background: #1e7e34;
        }
        
        .alert {
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 4px;
            border: 1px solid transparent;
        }
        
        .alert-success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
        
        .alert-info {
            background-color: #d1ecf1;
            border-color: #bee5eb;
            color: #0c5460;
        }
        
        .alert-warning {
            background-color: #fff3cd;
            border-color: #ffeaa7;
            color: #856404;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
            background: white;
        }
        
        .table th,
        .table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        
        .table th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #495057;
        }
        
        .table tbody tr:hover {
            background-color: #f5f5f5;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
            color: #495057;
        }
        
        .form-control {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 1rem;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #007cba;
            box-shadow: 0 0 0 2px rgba(0, 124, 186, 0.25);
        }
        
        .form-check {
            margin: 0.5rem 0;
        }
        
        .form-check input {
            margin-right: 0.5rem;
        }
        
        .badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
            font-weight: bold;
            border-radius: 3px;
            color: white;
        }
        
        .badge-success { background-color: #28a745; }
        .badge-danger { background-color: #dc3545; }
        .badge-warning { background-color: #ffc107; color: #212529; }
        .badge-info { background-color: #17a2b8; }
        .badge-secondary { background-color: #6c757d; }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .nav ul {
                flex-direction: column;
            }
            
            .nav-group {
                width: 100%;
            }
            
            .nav-submenu {
                position: static;
                opacity: 1;
                visibility: visible;
                transform: none;
                box-shadow: none;
                background: #003d5c;
                display: none;
            }
            
            .nav-group:hover .nav-submenu {
                display: block;
            }
            
            .nav-group-label {
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            
            .nav-group-label::after {
                content: '▼';
                font-size: 0.8rem;
                transition: transform 0.3s;
            }
            
            .nav-group:hover .nav-group-label::after {
                transform: rotate(180deg);
            }
            
            .container {
                padding: 0 1rem;
            }
            
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .table {
                font-size: 0.9rem;
            }
            
            .table th,
            .table td {
                padding: 0.5rem;
            }
        }
        
        /* Custom Admin Styles */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #007cba;
        }
        
        .page-header h2 {
            margin: 0;
            color: #333;
            font-size: 1.8rem;
        }
        
        .actions {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }
        
        .status-active { color: #28a745; font-weight: bold; }
        .status-inactive { color: #dc3545; font-weight: bold; }
        .status-pending { color: #ffc107; font-weight: bold; }
        
        .text-muted { color: #6c757d; }
        .text-center { text-align: center; }
        
        /* Loading and animations */
        .loading {
            text-align: center;
            padding: 2rem;
            color: #6c757d;
        }
        
        .fade-in {
            animation: fadeIn 0.3s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
    <?= $additionalCSS ?? '' ?>
</head>
<body>
    <div class="header">
        <h1>
            <i class="icon">⚙️</i> Baukasten CMS
        </h1>
        <div class="user-info">
            <span>Willkommen, <?= htmlspecialchars($currentUser['username']) ?></span>
            <a href="../public/index.php" target="_blank">Website ansehen</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
    
    <nav class="nav">
        <ul>
            <li><a href="index.php" class="<?= $currentPage === 'index' ? 'active' : '' ?>">📊 Dashboard</a></li>
            
            <!-- Content Management -->
            <li class="nav-group">
                <span class="nav-group-label">Content</span>
                <ul class="nav-submenu">
                    <li><a href="pages.php" class="<?= $currentPage === 'pages' ? 'active' : '' ?>">Seiten</a></li>
                    <li><a href="blog.php" class="<?= $currentPage === 'blog' ? 'active' : '' ?>">Blog</a></li>
                    <li><a href="content-blocks.php" class="<?= $currentPage === 'content-blocks' ? 'active' : '' ?>">Content-Blöcke</a></li>
                    <li><a href="content-manager.php" class="<?= $currentPage === 'content-manager' ? 'active' : '' ?>">Content Manager</a></li>
                </ul>
            </li>
            
            <!-- Tools & Features -->
            <li class="nav-group">
                <span class="nav-group-label">Tools</span>
                <ul class="nav-submenu">
                    <li><a href="forms.php" class="<?= $currentPage === 'forms' ? 'active' : '' ?>">Formulare</a></li>
                    <li><a href="media.php" class="<?= $currentPage === 'media' ? 'active' : '' ?>">Medien</a></li>
                    <?php if ($auth->canManageComments()): ?>
                    <li><a href="comments.php" class="<?= $currentPage === 'comments' ? 'active' : '' ?>">Kommentare</a></li>
                    <?php endif; ?>
                </ul>
            </li>
            
            <!-- Advanced Features -->
            <?php if ($auth->canManageSystem()): ?>
            <li class="nav-group">
                <span class="nav-group-label">Advanced</span>
                <ul class="nav-submenu">
                    <li><a href="plugins.php" class="<?= $currentPage === 'plugins' ? 'active' : '' ?>">🔌 Plugins</a></li>
                    <li><a href="seo-tools.php" class="<?= $currentPage === 'seo-tools' ? 'active' : '' ?>">🔍 SEO Tools</a></li>
                    <li><a href="seo.php" class="<?= $currentPage === 'seo' ? 'active' : '' ?>">SEO</a></li>
                    <li><a href="migration.php" class="<?= $currentPage === 'migration' ? 'active' : '' ?>" style="color: #ffc107;">🚀 Migration</a></li>
                </ul>
            </li>
            <?php endif; ?>
            
            <!-- Administration -->
            <?php if ($auth->canManageUsers() || $auth->canManageSystem()): ?>
            <li class="nav-group">
                <span class="nav-group-label">Admin</span>
                <ul class="nav-submenu">
                    <?php if ($auth->canManageUsers()): ?>
                    <li><a href="users.php" class="<?= $currentPage === 'users' ? 'active' : '' ?>">Benutzer</a></li>
                    <?php endif; ?>
                    <?php if ($auth->canManageSystem()): ?>
                    <li><a href="settings.php" class="<?= $currentPage === 'settings' ? 'active' : '' ?>">Einstellungen</a></li>
                    <?php endif; ?>
                </ul>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
    
    <div class="container fade-in">
