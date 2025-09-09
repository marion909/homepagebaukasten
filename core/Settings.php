<?php
/**
 * Settings-Klasse für das Homepage Baukasten System
 * Verwaltet alle Systemeinstellungen und stellt sie zentral zur Verfügung
 */
class Settings {
    private static $instance = null;
    private $settings = [];
    private $db;
    
    private function __construct() {
        $this->db = Database::getInstance();
        $this->loadSettings();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Lädt alle Einstellungen aus der Datenbank
     */
    private function loadSettings() {
        try {
            $results = $this->db->fetchAll("SELECT setting_key, setting_value FROM settings");
            foreach ($results as $row) {
                $this->settings[$row['setting_key']] = $row['setting_value'];
            }
        } catch (Exception $e) {
            // Fallback-Einstellungen wenn Datenbank nicht erreichbar
            $this->settings = [
                'site_title' => 'Homepage Baukasten',
                'site_description' => 'Ein professionelles Content Management System',
                'theme' => 'default',
                'timezone' => 'Europe/Berlin',
                'language' => 'de',
                'items_per_page' => '10'
            ];
        }
    }
    
    /**
     * Holt eine Einstellung
     * @param string $key Der Schlüssel der Einstellung
     * @param mixed $default Der Standardwert falls die Einstellung nicht existiert
     * @return mixed Der Wert der Einstellung
     */
    public function get($key, $default = null) {
        return $this->settings[$key] ?? $default;
    }
    
    /**
     * Setzt eine Einstellung
     * @param string $key Der Schlüssel der Einstellung
     * @param mixed $value Der Wert der Einstellung
     * @return bool True bei Erfolg, False bei Fehler
     */
    public function set($key, $value) {
        try {
            $this->db->query(
                "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
                 ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()",
                [$key, $value, $value]
            );
            $this->settings[$key] = $value;
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Holt alle Einstellungen
     * @return array Alle Einstellungen als Array
     */
    public function getAll() {
        return $this->settings;
    }
    
    /**
     * Aktualisiert die Einstellungen aus der Datenbank
     */
    public function refresh() {
        $this->loadSettings();
    }
    
    /**
     * Hilfsfunktionen für häufig verwendete Einstellungen
     */
    public function getSiteTitle() {
        return $this->get('site_title', 'Homepage Baukasten');
    }
    
    public function getSiteDescription() {
        return $this->get('site_description', 'Ein professionelles Content Management System');
    }
    
    public function getTheme() {
        return $this->get('theme', 'default');
    }
    
    public function getTimezone() {
        return $this->get('timezone', 'Europe/Berlin');
    }
    
    public function getLanguage() {
        return $this->get('language', 'de');
    }
    
    public function getItemsPerPage() {
        return intval($this->get('items_per_page', 10));
    }
    
    public function isRegistrationAllowed() {
        return $this->get('allow_registration', '0') === '1';
    }
    
    public function isEmailVerificationRequired() {
        return $this->get('require_email_verification', '0') === '1';
    }
    
    public function isSSLForced() {
        return $this->get('force_ssl', '0') === '1';
    }
    
    public function is2FAEnabled() {
        return $this->get('enable_2fa', '0') === '1';
    }
    
    /**
     * Social Media URLs
     */
    public function getSocialMediaUrls() {
        return [
            'facebook' => $this->get('facebook_url', ''),
            'twitter' => $this->get('twitter_url', ''),
            'instagram' => $this->get('instagram_url', ''),
            'linkedin' => $this->get('linkedin_url', ''),
            'youtube' => $this->get('youtube_url', ''),
            'github' => $this->get('github_url', ''),
            'discord' => $this->get('discord_url', ''),
            'twitch' => $this->get('twitch_url', '')
        ];
    }
    
    /**
     * Kontakt-Informationen
     */
    public function getContactInfo() {
        return [
            'email' => $this->get('contact_email', ''),
            'phone' => $this->get('contact_phone', ''),
            'address' => $this->get('contact_address', '')
        ];
    }
    
    /**
     * Sicherheitseinstellungen
     */
    public function getSecuritySettings() {
        return [
            'session_timeout' => intval($this->get('session_timeout', 3600)),
            'max_login_attempts' => intval($this->get('max_login_attempts', 5)),
            'lockout_duration' => intval($this->get('lockout_duration', 900)),
            'force_ssl' => $this->isSSLForced(),
            'enable_2fa' => $this->is2FAEnabled()
        ];
    }
}

// Globale Hilfsfunktion für einfachen Zugriff
function getSetting($key, $default = null) {
    return Settings::getInstance()->get($key, $default);
}

function setSetting($key, $value) {
    return Settings::getInstance()->set($key, $value);
}
