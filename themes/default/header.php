<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{title}}</title>
    <meta name="description" content="{{description}}">
    
    <!-- SEO Meta Tags -->
    <link rel="sitemap" type="application/xml" href="/sitemap.xml.php">
    <link rel="alternate" type="application/rss+xml" title="RSS Feed" href="/rss.xml.php">
    <meta name="robots" content="index, follow">
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="{{title}}">
    <meta property="og:description" content="{{description}}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{url}}">
    
    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="{{title}}">
    <meta name="twitter:description" content="{{description}}">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="/themes/default/style.css">
</head>
<body>
    <!-- Hero Section for Homepage -->
    <div class="hero-gradient"></div>
    
    <header class="modern-header">
        <div class="container">
            <div class="header-content">
                <div class="logo-section">
                    <h1 class="site-logo">
                        <i class="fas fa-cube"></i>
                        {{site_title}}
                    </h1>
                </div>
                
                <nav class="main-navigation">
                    <button class="mobile-menu-toggle" aria-label="Menü öffnen">
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>
                    <ul class="nav-menu">
                        {{navigation}}
                    </ul>
                </nav>
            </div>
        </div>
    </header>
