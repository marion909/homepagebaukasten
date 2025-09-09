<?php
/**
 * Plugin Name: Besucher Counter (Beispiel)
 * Plugin URI: https://github.com/baukasten-cms/visitor-counter
 * Description: Ein Beispiel-Plugin, das Seitenaufrufe zählt und Statistiken anzeigt. Perfekt zum Erlernen der Plugin-Entwicklung.
 * Version: 1.0.0
 * Author: Baukasten CMS Team
 * Author URI: https://baukasten-cms.de
 * License: GPL v2 or later
 * Requires PHP: 8.0
 * Requires CMS: 2.1
 * Text Domain: visitor-counter
 */

// Direkten Zugriff verhindern
if (!defined('ABSPATH')) {
    exit('Direct access not allowed.');
}

// Plugin-Konstanten definieren
define('VISITOR_COUNTER_VERSION', '1.0.0');
define('VISITOR_COUNTER_PATH', __DIR__);
define('VISITOR_COUNTER_URL', '/plugins/example-visitor-counter');

/**
 * Hauptklasse des Visitor Counter Plugins
 */
class VisitorCounterPlugin {
    
    /**
     * Plugin-Instanz (Singleton)
     */
    private static $instance = null;
    
    /**
     * Database-Instanz
     */
    private $db;
    
    /**
     * Settings-Instanz
     */
    private $settings;
    
    /**
     * Singleton-Pattern
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Konstruktor - Plugin initialisieren
     */
    private function __construct() {
        $this->db = Database::getInstance();
        $this->settings = Settings::getInstance();
        
        // Hooks registrieren
        $this->registerHooks();
        
        // Plugin-Aktivierung/Deaktivierung
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
    }
    
    /**
     * Alle Hooks registrieren
     */
    private function registerHooks() {
        // Core Hooks
        PluginManager::addAction('init', [$this, 'init']);
        PluginManager::addAction('admin_init', [$this, 'adminInit']);
        PluginManager::addAction('admin_menu', [$this, 'addAdminMenu']);
        
        // Content Hooks
        PluginManager::addFilter('page_content', [$this, 'addCounterToContent']);
        PluginManager::addAction('page_view', [$this, 'countPageView']);
        
        // AJAX Hooks
        PluginManager::addAction('wp_ajax_get_visitor_stats', [$this, 'ajaxGetStats']);
        PluginManager::addAction('wp_ajax_reset_visitor_stats', [$this, 'ajaxResetStats']);
    }
    
    /**
     * Plugin-Initialisierung
     */
    public function init() {
        // Textdomain laden
        $this->loadTextdomain();
        
        // Frontend-Assets laden
        if (!is_admin()) {
            $this->enqueueFrontendAssets();
            $this->trackPageView();
        }
    }
    
    /**
     * Admin-Initialisierung
     */
    public function adminInit() {
        $this->enqueueAdminAssets();
        $this->registerSettings();
    }
    
    /**
     * Textdomain für Übersetzungen laden
     */
    private function loadTextdomain() {
        $languages_path = VISITOR_COUNTER_PATH . '/languages';
        load_plugin_textdomain('visitor-counter', false, $languages_path);
    }
    
    /**
     * Frontend-Assets einbinden
     */
    private function enqueueFrontendAssets() {
        wp_enqueue_style(
            'visitor-counter-frontend',
            VISITOR_COUNTER_URL . '/assets/css/frontend.css',
            [],
            VISITOR_COUNTER_VERSION
        );
        
        wp_enqueue_script(
            'visitor-counter-frontend',
            VISITOR_COUNTER_URL . '/assets/js/frontend.js',
            ['jquery'],
            VISITOR_COUNTER_VERSION,
            true
        );
        
        // JavaScript-Variablen
        wp_localize_script('visitor-counter-frontend', 'visitorCounter', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('visitor_counter_nonce'),
            'settings' => $this->getSettings()
        ]);
    }
    
    /**
     * Admin-Assets einbinden
     */
    private function enqueueAdminAssets() {
        wp_enqueue_style(
            'visitor-counter-admin',
            VISITOR_COUNTER_URL . '/assets/css/admin.css',
            [],
            VISITOR_COUNTER_VERSION
        );
        
        wp_enqueue_script(
            'visitor-counter-admin',
            VISITOR_COUNTER_URL . '/assets/js/admin.js',
            ['jquery', 'chart-js'],
            VISITOR_COUNTER_VERSION,
            true
        );
    }
    
    /**
     * Plugin-Einstellungen registrieren
     */
    private function registerSettings() {
        // Einstellungen in der Settings-API registrieren
        $this->settings->addSection('visitor_counter', [
            'title' => __('Visitor Counter', 'visitor-counter'),
            'description' => __('Konfiguration für den Besucher-Zähler', 'visitor-counter')
        ]);
        
        $this->settings->addField('visitor_counter', 'show_counter', [
            'title' => __('Counter anzeigen', 'visitor-counter'),
            'type' => 'checkbox',
            'default' => true,
            'description' => __('Besucher-Counter auf Seiten anzeigen', 'visitor-counter')
        ]);
        
        $this->settings->addField('visitor_counter', 'counter_position', [
            'title' => __('Counter-Position', 'visitor-counter'),
            'type' => 'select',
            'options' => [
                'top' => __('Oben', 'visitor-counter'),
                'bottom' => __('Unten', 'visitor-counter'),
                'manual' => __('Manuell (Shortcode)', 'visitor-counter')
            ],
            'default' => 'bottom'
        ]);
        
        $this->settings->addField('visitor_counter', 'exclude_admin', [
            'title' => __('Admins ausschließen', 'visitor-counter'),
            'type' => 'checkbox',
            'default' => true,
            'description' => __('Admin-Besuche nicht zählen', 'visitor-counter')
        ]);
    }
    
    /**
     * Admin-Menü hinzufügen
     */
    public function addAdminMenu() {
        add_menu_page(
            __('Besucher Statistiken', 'visitor-counter'),
            __('Besucher Stats', 'visitor-counter'),
            'manage_options',
            'visitor-counter-stats',
            [$this, 'adminPageStats'],
            'dashicons-chart-line',
            30
        );
        
        add_submenu_page(
            'visitor-counter-stats',
            __('Einstellungen', 'visitor-counter'),
            __('Einstellungen', 'visitor-counter'),
            'manage_options',
            'visitor-counter-settings',
            [$this, 'adminPageSettings']
        );
    }
    
    /**
     * Admin-Seite: Statistiken
     */
    public function adminPageStats() {
        $stats = $this->getDetailedStats();
        $topPages = $this->getTopPages(10);
        $recentVisits = $this->getRecentVisits(20);
        
        include VISITOR_COUNTER_PATH . '/templates/admin-stats.php';
    }
    
    /**
     * Admin-Seite: Einstellungen
     */
    public function adminPageSettings() {
        if (isset($_POST['save_settings'])) {
            $this->saveSettings($_POST);
            $message = __('Einstellungen gespeichert!', 'visitor-counter');
        }
        
        $settings = $this->getSettings();
        include VISITOR_COUNTER_PATH . '/templates/admin-settings.php';
    }
    
    /**
     * Seitenaufruf verfolgen
     */
    public function trackPageView() {
        global $currentPage;
        
        if (!$currentPage || !$this->shouldTrackVisit()) {
            return;
        }
        
        $pageId = $currentPage['id'];
        $isUnique = $this->isUniqueVisitor($pageId);
        $visitorInfo = $this->getVisitorInfo();
        
        // Besuch in Database speichern
        $this->recordVisit($pageId, $isUnique, $visitorInfo);
    }
    
    /**
     * Prüfen ob Besuch getrackt werden soll
     */
    private function shouldTrackVisit() {
        // Admin-Besuche ausschließen wenn aktiviert
        if ($this->getSetting('exclude_admin', true) && current_user_can('manage_options')) {
            return false;
        }
        
        // Bots ausschließen
        if ($this->isBot()) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Bot-Erkennung
     */
    private function isBot() {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $botPatterns = [
            'googlebot', 'bingbot', 'slurp', 'duckduckbot',
            'baiduspider', 'yandexbot', 'facebookexternalhit'
        ];
        
        foreach ($botPatterns as $pattern) {
            if (stripos($userAgent, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Prüfen ob es ein einzigartiger Besucher ist
     */
    private function isUniqueVisitor($pageId) {
        $sessionKey = 'visitor_counter_page_' . $pageId;
        
        if (!isset($_SESSION[$sessionKey])) {
            $_SESSION[$sessionKey] = time();
            return true;
        }
        
        // Als unique zählen wenn letzter Besuch > 24h her
        $lastVisit = $_SESSION[$sessionKey];
        if ((time() - $lastVisit) > 86400) { // 24 Stunden
            $_SESSION[$sessionKey] = time();
            return true;
        }
        
        return false;
    }
    
    /**
     * Besucher-Informationen sammeln
     */
    private function getVisitorInfo() {
        return [
            'ip_address' => $this->getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'referrer' => $_SERVER['HTTP_REFERER'] ?? '',
            'country' => $this->getCountryFromIP(),
            'browser' => $this->getBrowserInfo(),
            'device' => $this->getDeviceInfo()
        ];
    }
    
    /**
     * Client-IP ermitteln
     */
    private function getClientIP() {
        $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                // Bei mehreren IPs die erste nehmen
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    /**
     * Land aus IP ermitteln (vereinfacht)
     */
    private function getCountryFromIP() {
        // Hier könnte eine GeoIP-API verwendet werden
        return 'unknown';
    }
    
    /**
     * Browser-Info ermitteln
     */
    private function getBrowserInfo() {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        if (strpos($userAgent, 'Chrome') !== false) return 'Chrome';
        if (strpos($userAgent, 'Firefox') !== false) return 'Firefox';
        if (strpos($userAgent, 'Safari') !== false) return 'Safari';
        if (strpos($userAgent, 'Edge') !== false) return 'Edge';
        
        return 'Other';
    }
    
    /**
     * Device-Info ermitteln
     */
    private function getDeviceInfo() {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        if (strpos($userAgent, 'Mobile') !== false) return 'Mobile';
        if (strpos($userAgent, 'Tablet') !== false) return 'Tablet';
        
        return 'Desktop';
    }
    
    /**
     * Besuch in Database speichern
     */
    private function recordVisit($pageId, $isUnique, $visitorInfo) {
        // Haupt-Statistik aktualisieren
        $existing = $this->db->fetchOne(
            "SELECT * FROM visitor_stats WHERE page_id = ?",
            [$pageId]
        );
        
        if ($existing) {
            $uniqueIncrement = $isUnique ? 1 : 0;
            $this->db->query(
                "UPDATE visitor_stats 
                 SET visits = visits + 1, 
                     unique_visits = unique_visits + ?, 
                     last_visit = NOW() 
                 WHERE page_id = ?",
                [$uniqueIncrement, $pageId]
            );
        } else {
            $this->db->query(
                "INSERT INTO visitor_stats (page_id, visits, unique_visits, first_visit, last_visit) 
                 VALUES (?, 1, 1, NOW(), NOW())",
                [$pageId]
            );
        }
        
        // Detaillierte Besuchs-Info speichern
        $this->db->query(
            "INSERT INTO visitor_logs 
             (page_id, ip_address, user_agent, referrer, country, browser, device, visit_time) 
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW())",
            [
                $pageId,
                $visitorInfo['ip_address'],
                $visitorInfo['user_agent'],
                $visitorInfo['referrer'],
                $visitorInfo['country'],
                $visitorInfo['browser'],
                $visitorInfo['device']
            ]
        );
    }
    
    /**
     * Counter zum Content hinzufügen
     */
    public function addCounterToContent($content) {
        if (!$this->getSetting('show_counter', true)) {
            return $content;
        }
        
        global $currentPage;
        if (!$currentPage) {
            return $content;
        }
        
        $position = $this->getSetting('counter_position', 'bottom');
        $counter = $this->renderCounter($currentPage['id']);
        
        switch ($position) {
            case 'top':
                return $counter . $content;
            case 'bottom':
                return $content . $counter;
            default:
                return $content;
        }
    }
    
    /**
     * Counter HTML rendern
     */
    private function renderCounter($pageId) {
        $stats = $this->db->fetchOne(
            "SELECT visits, unique_visits FROM visitor_stats WHERE page_id = ?",
            [$pageId]
        );
        
        if (!$stats) {
            return '';
        }
        
        ob_start();
        include VISITOR_COUNTER_PATH . '/templates/counter.php';
        return ob_get_clean();
    }
    
    /**
     * Plugin-Einstellungen abrufen
     */
    private function getSettings() {
        return [
            'show_counter' => $this->getSetting('show_counter', true),
            'counter_position' => $this->getSetting('counter_position', 'bottom'),
            'exclude_admin' => $this->getSetting('exclude_admin', true)
        ];
    }
    
    /**
     * Einzelne Einstellung abrufen
     */
    private function getSetting($key, $default = null) {
        return $this->settings->get('visitor_counter_' . $key, $default);
    }
    
    /**
     * Einstellungen speichern
     */
    private function saveSettings($data) {
        $settings = [
            'show_counter' => isset($data['show_counter']),
            'counter_position' => sanitize_text_field($data['counter_position'] ?? 'bottom'),
            'exclude_admin' => isset($data['exclude_admin'])
        ];
        
        foreach ($settings as $key => $value) {
            $this->settings->set('visitor_counter_' . $key, $value);
        }
    }
    
    /**
     * Detaillierte Statistiken abrufen
     */
    private function getDetailedStats() {
        return [
            'total_visits' => $this->db->fetchOne("SELECT SUM(visits) as total FROM visitor_stats")['total'] ?? 0,
            'total_unique' => $this->db->fetchOne("SELECT SUM(unique_visits) as total FROM visitor_stats")['total'] ?? 0,
            'pages_tracked' => $this->db->fetchOne("SELECT COUNT(*) as total FROM visitor_stats")['total'] ?? 0,
            'today_visits' => $this->getTodayVisits(),
            'this_week' => $this->getWeekVisits(),
            'this_month' => $this->getMonthVisits()
        ];
    }
    
    /**
     * Heutige Besuche
     */
    private function getTodayVisits() {
        return $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM visitor_logs WHERE DATE(visit_time) = CURDATE()"
        )['total'] ?? 0;
    }
    
    /**
     * Wöchentliche Besuche
     */
    private function getWeekVisits() {
        return $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM visitor_logs WHERE visit_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
        )['total'] ?? 0;
    }
    
    /**
     * Monatliche Besuche
     */
    private function getMonthVisits() {
        return $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM visitor_logs WHERE visit_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
        )['total'] ?? 0;
    }
    
    /**
     * Top-Seiten abrufen
     */
    private function getTopPages($limit = 10) {
        return $this->db->fetchAll(
            "SELECT p.title, p.slug, vs.visits, vs.unique_visits 
             FROM visitor_stats vs 
             JOIN pages p ON vs.page_id = p.id 
             ORDER BY vs.visits DESC 
             LIMIT ?",
            [$limit]
        );
    }
    
    /**
     * Neueste Besuche abrufen
     */
    private function getRecentVisits($limit = 20) {
        return $this->db->fetchAll(
            "SELECT p.title, vl.ip_address, vl.browser, vl.device, vl.visit_time 
             FROM visitor_logs vl 
             JOIN pages p ON vl.page_id = p.id 
             ORDER BY vl.visit_time DESC 
             LIMIT ?",
            [$limit]
        );
    }
    
    /**
     * AJAX: Statistiken abrufen
     */
    public function ajaxGetStats() {
        check_ajax_referer('visitor_counter_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $stats = $this->getDetailedStats();
        wp_send_json_success($stats);
    }
    
    /**
     * AJAX: Statistiken zurücksetzen
     */
    public function ajaxResetStats() {
        check_ajax_referer('visitor_counter_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $this->db->query("TRUNCATE TABLE visitor_stats");
        $this->db->query("TRUNCATE TABLE visitor_logs");
        
        wp_send_json_success(['message' => __('Statistiken zurückgesetzt', 'visitor-counter')]);
    }
    
    /**
     * Plugin-Aktivierung
     */
    public function activate() {
        // Database-Tabellen erstellen
        $this->createTables();
        
        // Standard-Einstellungen setzen
        $this->setDefaultSettings();
        
        // Plugin-Version speichern
        update_option('visitor_counter_version', VISITOR_COUNTER_VERSION);
        
        // Flush Rewrite Rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin-Deaktivierung
     */
    public function deactivate() {
        // Cleanup (optional)
        wp_clear_scheduled_hook('visitor_counter_cleanup');
    }
    
    /**
     * Database-Tabellen erstellen
     */
    private function createTables() {
        // Haupt-Statistik-Tabelle
        $this->db->query("
            CREATE TABLE IF NOT EXISTS visitor_stats (
                id INT AUTO_INCREMENT PRIMARY KEY,
                page_id INT NOT NULL,
                visits INT DEFAULT 0,
                unique_visits INT DEFAULT 0,
                first_visit TIMESTAMP NULL,
                last_visit TIMESTAMP NULL,
                UNIQUE KEY unique_page (page_id),
                INDEX idx_visits (visits),
                INDEX idx_page_id (page_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Detaillierte Besuchs-Logs
        $this->db->query("
            CREATE TABLE IF NOT EXISTS visitor_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                page_id INT NOT NULL,
                ip_address VARCHAR(45),
                user_agent TEXT,
                referrer TEXT,
                country VARCHAR(10),
                browser VARCHAR(50),
                device VARCHAR(20),
                visit_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_page_id (page_id),
                INDEX idx_visit_time (visit_time),
                INDEX idx_ip (ip_address)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }
    
    /**
     * Standard-Einstellungen setzen
     */
    private function setDefaultSettings() {
        $defaults = [
            'visitor_counter_show_counter' => true,
            'visitor_counter_counter_position' => 'bottom',
            'visitor_counter_exclude_admin' => true
        ];
        
        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                update_option($key, $value);
            }
        }
    }
}

// Shortcode für manuellen Counter
function visitor_counter_shortcode($atts = []) {
    global $currentPage;
    
    if (!$currentPage) {
        return '';
    }
    
    $plugin = VisitorCounterPlugin::getInstance();
    return $plugin->renderCounter($currentPage['id']);
}
add_shortcode('visitor_counter', 'visitor_counter_shortcode');

// Plugin initialisieren
VisitorCounterPlugin::getInstance();

// Helper-Funktionen für Theme-Entwickler
function get_page_visit_count($pageId = null) {
    global $currentPage;
    
    if (!$pageId && $currentPage) {
        $pageId = $currentPage['id'];
    }
    
    if (!$pageId) {
        return 0;
    }
    
    $db = Database::getInstance();
    $result = $db->fetchOne("SELECT visits FROM visitor_stats WHERE page_id = ?", [$pageId]);
    
    return $result ? intval($result['visits']) : 0;
}

function get_page_unique_visits($pageId = null) {
    global $currentPage;
    
    if (!$pageId && $currentPage) {
        $pageId = $currentPage['id'];
    }
    
    if (!$pageId) {
        return 0;
    }
    
    $db = Database::getInstance();
    $result = $db->fetchOne("SELECT unique_visits FROM visitor_stats WHERE page_id = ?", [$pageId]);
    
    return $result ? intval($result['unique_visits']) : 0;
}
