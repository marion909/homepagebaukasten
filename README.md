# 🏗️ Homepage Baukasten CMS

Ein professionelles, WordPress-ähnliches Content Management System mit modernem Design und umfangreichen Features für deutsche Websites.

![PHP](https://img.shields.io/badge/PHP-8.0%2B-blue)
![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-orange)
![License](https://img.shields.io/badge/License-MIT-green)
![Version](https://img.shields.io/badge/Version-2.0-brightgreen)

## ✨ Features

### 🎨 **Design & Themes**
- **5 professionelle Themes** (Corporate, Creative, Minimal, Magazine, Dark)
- **Responsive Design** für alle Geräte
- **Theme-Wechsel** per Admin-Panel
- **Moderne CSS-Features** (Grid, Flexbox, Animationen)

### 📝 **Content Management**
- **Seiten-Editor** mit WYSIWYG
- **Blog-System** mit Kategorien, Tags und Kommentaren
- **Medien-Verwaltung** für Bilder und Dateien
- **Content-Blocks** für wiederverwendbare Inhalte

### 🔧 **Administration**
- **WordPress-ähnliches Admin-Panel**
- **Benutzer-Verwaltung** mit Rollen (Admin, Editor)
- **Umfassendes Settings-System** mit Backup/Restore
- **Navigation-Builder** für flexible Menüs

### 📊 **Formulare & Interaktion**
- **Form-Builder** für benutzerdefinierte Formulare
- **Kontakt-Formulare** mit Spam-Schutz
- **Kommentar-System** mit Moderation
- **Newsletter-Integration**

### 🔒 **Sicherheit**
- **CSRF-Schutz** für alle Formulare
- **Session-Management** mit Timeout
- **Sichere Passwort-Hashes** (PHP password_hash)
- **Eingabe-Validierung** und XSS-Schutz

### 🚀 **Performance & SEO**
- **Optimierte Datenbankstruktur** mit Indizes
- **SEO-freundliche URLs**
- **Meta-Tags** und Open Graph
- **Sitemap-Generation**
- **Cache-System**

## 🚀 Installation

### Schnellstart (WordPress-ähnlich)

1. **Dateien hochladen**
   ```bash
   git clone https://github.com/marion909/homepagebaukasten.git
   cd homepagebaukasten
   ```

2. **Installation starten**
   ```
   http://ihre-domain.de/install/
   ```

3. **4 einfache Schritte**
   - ✅ System-Anforderungen prüfen
   - ✅ Datenbank konfigurieren
   - ✅ Website-Informationen eingeben
   - ✅ Administrator-Account erstellen

4. **Fertig!** 🎉
   - Automatische Umleitung zum Admin-Panel
   - Standard-Seiten bereits erstellt
   - Sicherheitsmaßnahmen aktiviert

### Manuelle Installation

```bash
# Datenbank erstellen
mysql -u root -p -e "CREATE DATABASE baukasten_cms"

# SQL-Installation ausführen
mysql -u root -p baukasten_cms < install/complete_installation.sql

# Config-Datei anpassen
cp config-sample.php config.php
# DB-Daten in config.php eintragen
```

## 📋 System-Anforderungen

### Minimum
- **PHP 7.4+** (Empfohlen: PHP 8.0+)
- **MySQL 5.7+** oder MariaDB 10.2+
- **64 MB RAM**
- **50 MB Festplattenspeicher**

### Empfohlen
- **PHP 8.1+** mit OPcache
- **MySQL 8.0+**
- **128 MB RAM**
- **SSL/HTTPS**
- **mod_rewrite** aktiviert

### PHP Extensions
- PDO + PDO_MySQL
- JSON
- MBString
- OpenSSL
- GD oder ImageMagick

## 🎨 Verfügbare Themes

| Theme | Beschreibung | Ideal für |
|-------|-------------|-----------|
| **Corporate** | Professionelles Business-Design | Unternehmen, Agenturen |
| **Creative** | Modernes Portfolio-Design | Kreative, Designer, Künstler |
| **Minimal** | Sauberes, minimalistisches Design | Blogs, persönliche Websites |
| **Magazine** | Editorial-Design mit Sidebar | News, Magazine, Content-Sites |
| **Dark** | Gaming/Tech-Design mit Neon-Effekten | Gaming, Tech, Communities |

## 📱 Screenshots

### Admin-Panel
![Admin Dashboard](docs/screenshots/admin-dashboard.png)

### Theme-Auswahl
![Theme Selection](docs/screenshots/themes.png)

### Blog-System
![Blog System](docs/screenshots/blog.png)

## 🛠️ Entwicklung

### Lokale Entwicklung

```bash
# Repository klonen
git clone https://github.com/marion909/homepagebaukasten.git
cd homepagebaukasten

# Lokalen Server starten (PHP 8.0+)
php -S localhost:8000

# Oder mit Docker
docker-compose up -d
```

### Ordnerstruktur

```
homepagebaukasten/
├── admin/              # Admin-Panel
├── core/               # Kern-Klassen
├── themes/             # Design-Themes
│   ├── corporate/
│   ├── creative/
│   ├── minimal/
│   ├── magazine/
│   └── dark/
├── install/            # Installationsroutine
├── uploads/            # Medien-Dateien
├── backups/            # Backup-Dateien
└── index.php          # Frontend-Entry-Point
```

### API-Struktur

```php
// Datenbankzugriff
$db = Database::getInstance();
$users = $db->fetchAll("SELECT * FROM users");

// Einstellungen
$settings = Settings::getInstance();
$siteTitle = $settings->get('site_title');

// Authentifizierung
$auth = new Auth();
$auth->requireLogin();
```

## 🤝 Contributing

Beiträge sind willkommen! Bitte beachten Sie:

1. **Fork** das Repository
2. **Feature Branch** erstellen (`git checkout -b feature/AmazingFeature`)
3. **Commit** Ihre Änderungen (`git commit -m 'Add AmazingFeature'`)
4. **Push** zum Branch (`git push origin feature/AmazingFeature`)
5. **Pull Request** öffnen

### Coding Standards
- PSR-12 Coding Standard
- Deutsche Kommentare und Variablennamen
- PHPDoc für alle öffentlichen Methoden
- Unit Tests für neue Features

## 🐛 Bug Reports

Bugs bitte über [GitHub Issues](https://github.com/marion909/homepagebaukasten/issues) melden:

- **Beschreibung** des Problems
- **Schritte** zur Reproduktion
- **Erwartetes** vs. **tatsächliches** Verhalten
- **System-Informationen** (PHP, MySQL Version)
- **Screenshots** falls möglich

## 📚 Dokumentation

### Benutzerhandbuch
- [Installation](docs/installation.md)
- [Admin-Panel](docs/admin-guide.md)
- [Theme-Entwicklung](docs/theme-development.md)
- [Plugin-System](docs/plugins.md)

### Entwickler-Dokumentation
- [API-Referenz](docs/api-reference.md)
- [Datenbank-Schema](docs/database-schema.md)
- [Sicherheits-Guide](docs/security.md)
- [Performance-Optimierung](docs/performance.md)

## 🔧 Konfiguration

### Grundeinstellungen (config.php)
```php
// Datenbank
define('DB_HOST', 'localhost');
define('DB_NAME', 'baukasten_cms');
define('DB_USER', 'username');
define('DB_PASS', 'password');

// Sicherheit
define('SECURITY_SALT', 'your-unique-salt');

// Debug (nur in Entwicklung)
define('DEBUG_MODE', false);
```

### Erweiterte Konfiguration
- **Cache-Einstellungen** über Admin-Panel
- **SEO-Optimierungen** in Settings
- **E-Mail-Konfiguration** für Benachrichtigungen
- **Backup-Automatisierung**

## 🚦 Status

- ✅ **Stabil:** Kern-CMS funktional
- ✅ **Features:** Blog, Seiten, Admin-Panel
- ✅ **Themes:** 5 professionelle Designs
- ✅ **Sicherheit:** Grundlegende Sicherheitsmaßnahmen
- 🔄 **In Arbeit:** Plugin-System, API-Erweiterungen
- 📋 **Geplant:** Multi-Language Support, E-Commerce

## 📈 Roadmap

### Version 2.1 (Q4 2025)
- [ ] Plugin-System
- [ ] Erweiterte SEO-Tools
- [ ] Performance-Monitoring
- [ ] Auto-Updates

### Version 2.2 (Q1 2026)
- [ ] Multi-Language Support
- [ ] REST API
- [ ] Mobile App
- [ ] E-Commerce Integration

## 📄 Lizenz

Dieses Projekt steht unter der [MIT Lizenz](LICENSE).

```
MIT License

Copyright (c) 2025 Homepage Baukasten CMS

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.
```

## 👥 Team

- **Marion909** - *Hauptentwickler* - [GitHub](https://github.com/marion909)

## 🙏 Danksagungen

- **Bootstrap** für das CSS-Framework
- **Font Awesome** für die Icons
- **PHP Community** für die großartige Dokumentation
- **WordPress** für die Inspiration des Admin-Panels

## 📞 Support

- **GitHub Issues:** [Bug Reports & Feature Requests](https://github.com/marion909/homepagebaukasten/issues)
- **Diskussionen:** [GitHub Discussions](https://github.com/marion909/homepagebaukasten/discussions)
- **E-Mail:** support@baukasten-cms.de

---

<div align="center">

**[🌐 Live Demo](https://demo.baukasten-cms.de)** | 
**[📚 Dokumentation](https://docs.baukasten-cms.de)** | 
**[🎨 Theme Gallery](https://themes.baukasten-cms.de)**

Made with ❤️ in Germany

</div>
