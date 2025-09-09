<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{title}}</title>
    <meta name="description" content="{{description}}">
    <meta name="keywords" content="{{keywords}}">
    <meta name="author" content="{{author}}">
    <meta name="generator" content="Baukasten CMS">
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
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Theme Stylesheet -->
    <link rel="stylesheet" href="/themes/dark/style.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
</head>
<body>
    <!-- Animated Background -->
    <div class="animated-bg">
        <div class="particle" style="--i: 1;"></div>
        <div class="particle" style="--i: 2;"></div>
        <div class="particle" style="--i: 3;"></div>
        <div class="particle" style="--i: 4;"></div>
        <div class="particle" style="--i: 5;"></div>
        <div class="particle" style="--i: 6;"></div>
        <div class="particle" style="--i: 7;"></div>
        <div class="particle" style="--i: 8;"></div>
        <div class="particle" style="--i: 9;"></div>
        <div class="particle" style="--i: 10;"></div>
    </div>
    
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <a href="/">
                        <div class="logo-glow">
                            <i class="fas fa-code"></i>
                        </div>
                        <div class="logo-text">
                            <span class="logo-main">{{site_name}}</span>
                            <span class="logo-sub">Gaming & Tech</span>
                        </div>
                    </a>
                </div>
                
                <nav class="main-navigation">
                    <ul class="nav-menu">
                        {{navigation}}
                    </ul>
                    
                    <div class="nav-extras">
                        <button class="theme-toggle" aria-label="Theme toggle">
                            <i class="fas fa-moon"></i>
                        </button>
                        <button class="search-toggle" aria-label="Search">
                            <i class="fas fa-search"></i>
                        </button>
                        <div class="mobile-menu-toggle">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                    </div>
                </nav>
            </div>
            
            <!-- Search Overlay -->
            <div class="search-overlay">
                <div class="search-container">
                    <input type="search" placeholder="Suche nach Artikeln, Games, Tech...">
                    <button class="search-close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="search-suggestions">
                    <div class="suggestion-category">
                        <h4>Beliebte Suchanfragen</h4>
                        <div class="suggestions">
                            <span class="suggestion-tag">Cyberpunk 2077</span>
                            <span class="suggestion-tag">RTX 4090</span>
                            <span class="suggestion-tag">Gaming Setup</span>
                            <span class="suggestion-tag">JavaScript</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>
