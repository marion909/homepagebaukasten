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
    
    <link rel="stylesheet" href="/themes/default/style.css">
</head>
<body>
<header>
    <div class="container">
        <h1>{{site_title}}</h1>
        <nav>
            <ul>
                {{navigation}}
            </ul>
        </nav>
    </div>
</header>
