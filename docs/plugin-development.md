# 🔌 Plugin-Entwicklung für Homepage Baukasten CMS

Entwickeln Sie eigene Plugins für das Homepage Baukasten CMS und erweitern Sie die Funktionalität nach Ihren Wünschen.

## 📋 Inhaltsverzeichnis

1. [Grundlagen](#grundlagen)
2. [Plugin-Struktur](#plugin-struktur)
3. [Hook-System](#hook-system)
4. [API-Referenz](#api-referenz)
5. [Beispiel-Plugin](#beispiel-plugin)
6. [Best Practices](#best-practices)
7. [Deployment](#deployment)

## 🏗️ Grundlagen

### Was ist ein Plugin?

Ein Plugin ist eine Erweiterung, die zusätzliche Funktionalität zum CMS hinzufügt, ohne den Core-Code zu verändern. Plugins können:

- Neue Features hinzufügen
- Bestehende Funktionen erweitern
- Custom Post Types erstellen
- Shortcodes registrieren
- Admin-Seiten hinzufügen
- Database-Tabellen erstellen

### Systemanforderungen

- PHP 8.0 oder höher
- Zugriff auf das `/plugins` Verzeichnis
- Grundkenntnisse in PHP und HTML/CSS
- Optional: JavaScript für Frontend-Features

## 📁 Plugin-Struktur

### Minimale Plugin-Struktur

```
/plugins/
├── mein-plugin/
│   ├── plugin.php          # Haupt-Plugin-Datei
│   ├── README.md          # Plugin-Dokumentation
│   └── assets/            # CSS, JS, Bilder
│       ├── css/
│       ├── js/
│       └── images/
```

### Erweiterte Plugin-Struktur

```
/plugins/
├── advanced-plugin/
│   ├── plugin.php          # Haupt-Plugin-Datei
│   ├── README.md          # Plugin-Dokumentation
│   ├── config.json        # Plugin-Konfiguration
│   ├── includes/          # PHP-Includes
│   │   ├── class-admin.php
│   │   ├── class-frontend.php
│   │   └── class-database.php
│   ├── templates/         # Template-Dateien
│   │   ├── admin-page.php
│   │   └── frontend-view.php
│   ├── assets/           # Statische Dateien
│   │   ├── css/
│   │   ├── js/
│   │   └── images/
│   └── languages/        # Übersetzungen
│       ├── de.json
│       └── en.json
```

## 🔧 Plugin Header

Jede `plugin.php` Datei muss mit einem Plugin-Header beginnen:

```php
<?php
/**
 * Plugin Name: Mein Beispiel Plugin
 * Plugin URI: https://example.com/mein-plugin
 * Description: Eine kurze Beschreibung des Plugins
 * Version: 1.0.0
 * Author: Ihr Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Requires PHP: 8.0
 * Requires CMS: 2.1
 */

// Direkten Zugriff verhindern
if (!defined('ABSPATH')) {
    exit;
}
```

## 🪝 Hook-System

Das CMS verwendet ein Hook-System für Erweiterungen:

### Action Hooks

Action Hooks werden an bestimmten Punkten ausgeführt:

```php
// Hook registrieren
PluginManager::addAction('init', 'mein_plugin_init');

// Hook-Funktion
function mein_plugin_init() {
    // Wird beim CMS-Start ausgeführt
}
```

### Filter Hooks

Filter Hooks modifizieren Daten:

```php
// Filter registrieren
PluginManager::addFilter('page_content', 'mein_plugin_filter_content');

// Filter-Funktion
function mein_plugin_filter_content($content) {
    return $content . '<p>Von Plugin hinzugefügt</p>';
}
```

### Verfügbare Hooks

#### Action Hooks:
- `init` - CMS-Initialisierung
- `admin_init` - Admin-Bereich-Initialisierung
- `admin_menu` - Admin-Menü wird erstellt
- `wp_head` - HTML-Head-Bereich
- `wp_footer` - HTML-Footer-Bereich
- `save_post` - Nach dem Speichern einer Seite
- `delete_post` - Nach dem Löschen einer Seite

#### Filter Hooks:
- `page_content` - Seiten-Inhalt
- `blog_content` - Blog-Artikel-Inhalt
- `admin_menu_items` - Admin-Menü-Einträge
- `page_title` - Seiten-Titel
- `meta_description` - Meta-Beschreibung

## 🛠️ API-Referenz

### PluginManager Klasse

```php
// Plugin aktivieren/deaktivieren
PluginManager::activatePlugin($pluginSlug);
PluginManager::deactivatePlugin($pluginSlug);

// Hooks
PluginManager::addAction($hook, $callback, $priority = 10);
PluginManager::addFilter($hook, $callback, $priority = 10);
PluginManager::removeAction($hook, $callback);
PluginManager::removeFilter($hook, $callback);

// Plugin-Informationen
PluginManager::getPluginInfo($pluginSlug);
PluginManager::isPluginActive($pluginSlug);
```

### Database Zugriff

```php
// Database-Instanz erhalten
$db = Database::getInstance();

// Daten abfragen
$results = $db->fetchAll("SELECT * FROM my_table WHERE status = ?", ['active']);
$single = $db->fetchOne("SELECT * FROM my_table WHERE id = ?", [1]);

// Daten einfügen/aktualisieren
$db->query("INSERT INTO my_table (name, status) VALUES (?, ?)", ['Test', 'active']);
```

### Settings API

```php
// Einstellungen speichern/laden
$settings = Settings::getInstance();
$settings->set('my_plugin_option', 'value');
$value = $settings->get('my_plugin_option', 'default_value');
```

### Admin-Seiten

```php
// Admin-Seite hinzufügen
function add_my_admin_page() {
    PluginManager::addAdminPage([
        'page_title' => 'Mein Plugin',
        'menu_title' => 'Mein Plugin',
        'capability' => 'manage_system',
        'menu_slug' => 'my-plugin',
        'callback' => 'my_plugin_admin_page'
    ]);
}
PluginManager::addAction('admin_menu', 'add_my_admin_page');
```

## 📝 Beispiel-Plugin

Hier ist ein vollständiges Beispiel-Plugin:

```php
<?php
/**
 * Plugin Name: Besucher Counter
 * Description: Zählt Seitenaufrufe und zeigt Statistiken
 * Version: 1.0.0
 * Author: CMS Team
 */

if (!defined('ABSPATH')) {
    exit;
}

class VisitorCounterPlugin {
    
    public function __construct() {
        // Plugin initialisieren
        PluginManager::addAction('init', [$this, 'init']);
        PluginManager::addAction('admin_menu', [$this, 'addAdminMenu']);
        PluginManager::addFilter('page_content', [$this, 'addCounterToContent']);
        
        // Plugin-Aktivierung
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
    }
    
    public function init() {
        // CSS/JS einbinden
        if (is_admin()) {
            wp_enqueue_style('visitor-counter-admin', plugin_url('assets/admin.css'));
        } else {
            wp_enqueue_script('visitor-counter', plugin_url('assets/counter.js'));
            $this->countVisit();
        }
    }
    
    public function activate() {
        // Database-Tabelle erstellen
        $db = Database::getInstance();
        $db->query("
            CREATE TABLE IF NOT EXISTS visitor_stats (
                id INT AUTO_INCREMENT PRIMARY KEY,
                page_id INT NOT NULL,
                visits INT DEFAULT 0,
                unique_visits INT DEFAULT 0,
                last_visit TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX(page_id)
            )
        ");
    }
    
    public function deactivate() {
        // Cleanup wenn nötig
    }
    
    public function addAdminMenu() {
        PluginManager::addAdminPage([
            'page_title' => 'Besucher Statistiken',
            'menu_title' => 'Besucher Stats',
            'capability' => 'view_stats',
            'menu_slug' => 'visitor-stats',
            'callback' => [$this, 'adminPage']
        ]);
    }
    
    public function adminPage() {
        $db = Database::getInstance();
        $stats = $db->fetchAll("
            SELECT p.title, vs.visits, vs.unique_visits, vs.last_visit 
            FROM visitor_stats vs 
            JOIN pages p ON vs.page_id = p.id 
            ORDER BY vs.visits DESC
        ");
        
        include plugin_path('templates/admin-stats.php');
    }
    
    public function countVisit() {
        global $currentPage;
        
        if (!$currentPage) return;
        
        $db = Database::getInstance();
        $pageId = $currentPage['id'];
        $isUnique = $this->isUniqueVisitor($pageId);
        
        // Besuch zählen
        $existing = $db->fetchOne("SELECT * FROM visitor_stats WHERE page_id = ?", [$pageId]);
        
        if ($existing) {
            $uniqueIncrement = $isUnique ? 1 : 0;
            $db->query("
                UPDATE visitor_stats 
                SET visits = visits + 1, 
                    unique_visits = unique_visits + ?, 
                    last_visit = NOW() 
                WHERE page_id = ?
            ", [$uniqueIncrement, $pageId]);
        } else {
            $db->query("
                INSERT INTO visitor_stats (page_id, visits, unique_visits) 
                VALUES (?, 1, 1)
            ", [$pageId]);
        }
    }
    
    public function addCounterToContent($content) {
        global $currentPage;
        
        if (!$currentPage) return $content;
        
        $db = Database::getInstance();
        $stats = $db->fetchOne("SELECT visits FROM visitor_stats WHERE page_id = ?", [$currentPage['id']]);
        
        if ($stats) {
            $counter = '<div class="visitor-counter">👀 ' . $stats['visits'] . ' Aufrufe</div>';
            $content .= $counter;
        }
        
        return $content;
    }
    
    private function isUniqueVisitor($pageId) {
        // Vereinfachte Unique-Visitor-Logik
        $key = 'visited_page_' . $pageId;
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = true;
            return true;
        }
        
        return false;
    }
}

// Plugin initialisieren
new VisitorCounterPlugin();

// Helper-Funktionen
function plugin_url($path = '') {
    return '/plugins/visitor-counter/' . $path;
}

function plugin_path($path = '') {
    return __DIR__ . '/' . $path;
}
```

## 🎨 Best Practices

### 1. Namenskonventionen

- Plugin-Ordner: `mein-plugin-name`
- Funktionen: `mein_plugin_function_name()`
- Klassen: `MeinPluginClassName`
- Database-Tabellen: `mp_table_name` (mp = mein plugin)

### 2. Sicherheit

```php
// Nonces für Formulare
wp_nonce_field('my_plugin_action', 'my_plugin_nonce');

// Nonce verifizieren
if (!wp_verify_nonce($_POST['my_plugin_nonce'], 'my_plugin_action')) {
    die('Sicherheitsfehler');
}

// Input sanitisieren
$input = sanitize_text_field($_POST['user_input']);
$email = sanitize_email($_POST['email']);
```

### 3. Internationalisierung

```php
// Text übersetzen
echo __('Hello World', 'my-plugin-textdomain');

// Plural-Formen
echo _n('1 item', '%d items', $count, 'my-plugin-textdomain');
```

### 4. Performance

```php
// Nur laden wenn nötig
if (is_admin()) {
    require_once 'includes/admin.php';
}

// Caching verwenden
$cache_key = 'my_plugin_data_' . $page_id;
$data = wp_cache_get($cache_key);

if (!$data) {
    $data = expensive_operation();
    wp_cache_set($cache_key, $data, '', 3600);
}
```

## 🚀 Deployment

### 1. Plugin-Verpackung

```bash
# Plugin-Ordner komprimieren
zip -r mein-plugin.zip mein-plugin/

# Oder als tar.gz
tar -czf mein-plugin.tar.gz mein-plugin/
```

### 2. Installation

1. Plugin-Datei ins `/plugins/` Verzeichnis hochladen
2. Admin-Bereich → Plugins → Plugin aktivieren
3. Konfiguration bei Bedarf anpassen

### 3. Update-Mechanismus

```php
// Version prüfen und Update durchführen
function my_plugin_check_version() {
    $current_version = get_option('my_plugin_version', '0.0.0');
    $new_version = '1.1.0';
    
    if (version_compare($current_version, $new_version, '<')) {
        my_plugin_update_database();
        update_option('my_plugin_version', $new_version);
    }
}
```

## 🔧 Debug und Testing

### Debug-Modus aktivieren

```php
// In config.php
define('DEBUG_MODE', true);

// Im Plugin
if (defined('DEBUG_MODE') && DEBUG_MODE) {
    error_log('Plugin Debug: ' . $message);
}
```

### Testing

```php
// Unit Tests (vereinfacht)
function test_my_plugin_function() {
    $result = my_plugin_function('test');
    assert($result === 'expected_output', 'Function test failed');
}
```

## 📚 Weitere Ressourcen

- [CMS Hook-Referenz](hooks.md)
- [Database API](database-api.md)
- [Admin UI Guidelines](admin-ui.md)
- [Plugin-Beispiele](examples/)

---

**Viel Erfolg bei der Plugin-Entwicklung! 🚀**

Bei Fragen zur Plugin-Entwicklung können Sie sich an das Entwickler-Team wenden.
