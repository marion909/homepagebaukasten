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
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@300;400;700;900&family=Open+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Theme Stylesheet -->
    <link rel="stylesheet" href="/themes/magazine/style.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
</head>
<body>
    <!-- Breaking News Bar -->
    <div class="breaking-news">
        <div class="container">
            <div class="breaking-news-content">
                <span class="breaking-label">Aktuell</span>
                <div class="breaking-text">
                    <marquee>Willkommen bei {{site_name}} - Ihrem Online-Magazin für aktuelle Nachrichten und spannende Geschichten!</marquee>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Header -->
    <header class="header">
        <div class="container">
            <!-- Top Header -->
            <div class="header-top">
                <div class="header-info">
                    <span class="date" id="current-date"></span>
                    <span class="weather">
                        <i class="fas fa-sun"></i> 22°C
                    </span>
                </div>
                <div class="header-social">
                    <a href="{{facebook_url}}" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="{{twitter_url}}" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="{{instagram_url}}" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="/rss" aria-label="RSS"><i class="fas fa-rss"></i></a>
                </div>
            </div>
            
            <!-- Main Header -->
            <div class="header-main">
                <div class="logo">
                    <a href="/">
                        <h1>{{site_name}}</h1>
                        <p class="tagline">Ihr digitales Magazin</p>
                    </a>
                </div>
                
                <div class="header-ad">
                    <div class="ad-placeholder">
                        <span>Werbung</span>
                    </div>
                </div>
            </div>
            
            <!-- Navigation -->
            <nav class="main-navigation">
                <div class="nav-container">
                    <ul class="nav-menu">
                        {{navigation}}
                    </ul>
                    
                    <div class="nav-extras">
                        <button class="search-toggle">
                            <i class="fas fa-search"></i>
                        </button>
                        <button class="mobile-menu-toggle">
                            <span></span>
                            <span></span>
                            <span></span>
                        </button>
                    </div>
                </div>
                
                <!-- Search Bar -->
                <div class="search-bar">
                    <div class="search-container">
                        <input type="search" placeholder="Artikel durchsuchen...">
                        <button type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </nav>
        </div>
    </header>
