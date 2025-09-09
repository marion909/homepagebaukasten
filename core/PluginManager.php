<?php
/**
 * Plugin-System für Homepage Baukasten CMS
 * Ermöglicht das Laden und Verwalten von Plugins
 */

class PluginManager {
    private static $instance = null;
    private $plugins = [];
    private $hooks = [];
    private $pluginDir;
    private $db;
    
    private function __construct() {
        $this->db = Database::getInstance();
        $this->pluginDir = dirname(__DIR__) . '/plugins';
        $this->loadActivePlugins();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Lädt alle aktiven Plugins
     */
    private function loadActivePlugins() {
        try {
            $activePlugins = $this->db->fetchAll(
                "SELECT * FROM plugins WHERE active = 1 ORDER BY load_order ASC"
            );
            
            foreach ($activePlugins as $plugin) {
                $this->loadPlugin($plugin['directory'], $plugin);
            }
        } catch (Exception $e) {
            // Plugins-Tabelle existiert noch nicht - ignorieren
        }
    }
    
    /**
     * Lädt ein einzelnes Plugin
     */
    public function loadPlugin($directory, $pluginData = null) {
        $pluginPath = $this->pluginDir . '/' . $directory;
        $mainFile = $pluginPath . '/' . $directory . '.php';
        
        if (!file_exists($mainFile)) {
            return false;
        }
        
        // Plugin-Informationen aus Header lesen
        $pluginInfo = $this->getPluginInfo($mainFile);
        
        if (!$pluginInfo) {
            return false;
        }
        
        // Plugin laden
        try {
            require_once $mainFile;
            
            // Plugin-Klasse instanziieren
            $className = $pluginInfo['class'] ?? ucfirst($directory) . 'Plugin';
            
            if (class_exists($className)) {
                $pluginInstance = new $className();
                
                // Plugin-Daten speichern
                $this->plugins[$directory] = [
                    'instance' => $pluginInstance,
                    'info' => $pluginInfo,
                    'data' => $pluginData,
                    'path' => $pluginPath
                ];
                
                // Plugin aktivieren
                if (method_exists($pluginInstance, 'activate')) {
                    $pluginInstance->activate();
                }
                
                return true;
            }
        } catch (Exception $e) {
            error_log("Plugin-Ladefehler [{$directory}]: " . $e->getMessage());
            return false;
        }
        
        return false;
    }
    
    /**
     * Plugin-Informationen aus Datei-Header extrahieren
     */
    private function getPluginInfo($file) {
        $content = file_get_contents($file, false, null, 0, 8192);
        
        if (preg_match('/\/\*\*(.*?)\*\//s', $content, $matches)) {
            $header = $matches[1];
            
            $info = [
                'name' => $this->extractHeaderValue($header, 'Plugin Name'),
                'description' => $this->extractHeaderValue($header, 'Description'),
                'version' => $this->extractHeaderValue($header, 'Version'),
                'author' => $this->extractHeaderValue($header, 'Author'),
                'author_uri' => $this->extractHeaderValue($header, 'Author URI'),
                'plugin_uri' => $this->extractHeaderValue($header, 'Plugin URI'),
                'requires' => $this->extractHeaderValue($header, 'Requires'),
                'tested_up_to' => $this->extractHeaderValue($header, 'Tested up to'),
                'class' => $this->extractHeaderValue($header, 'Main Class')
            ];
            
            return array_filter($info);
        }
        
        return false;
    }
    
    /**
     * Extrahiert Wert aus Plugin-Header
     */
    private function extractHeaderValue($header, $key) {
        if (preg_match('/\* ' . preg_quote($key) . ':\s*(.*)$/m', $header, $matches)) {
            return trim($matches[1]);
        }
        return '';
    }
    
    /**
     * Hook-System - Action hinzufügen
     */
    public function addAction($hook, $callback, $priority = 10) {
        if (!isset($this->hooks[$hook])) {
            $this->hooks[$hook] = [];
        }
        
        $this->hooks[$hook][] = [
            'callback' => $callback,
            'priority' => $priority
        ];
        
        // Nach Priorität sortieren
        usort($this->hooks[$hook], function($a, $b) {
            return $a['priority'] - $b['priority'];
        });
    }
    
    /**
     * Hook-System - Action ausführen
     */
    public function doAction($hook, ...$args) {
        if (isset($this->hooks[$hook])) {
            foreach ($this->hooks[$hook] as $action) {
                call_user_func_array($action['callback'], $args);
            }
        }
    }
    
    /**
     * Filter-System
     */
    public function applyFilter($hook, $value, ...$args) {
        if (isset($this->hooks[$hook])) {
            foreach ($this->hooks[$hook] as $filter) {
                $value = call_user_func_array($filter['callback'], array_merge([$value], $args));
            }
        }
        return $value;
    }
    
    /**
     * Plugin aktivieren
     */
    public function activatePlugin($directory) {
        try {
            // Plugin-Informationen laden
            $pluginPath = $this->pluginDir . '/' . $directory;
            $mainFile = $pluginPath . '/' . $directory . '.php';
            
            if (!file_exists($mainFile)) {
                throw new Exception("Plugin-Hauptdatei nicht gefunden");
            }
            
            $pluginInfo = $this->getPluginInfo($mainFile);
            
            if (!$pluginInfo) {
                throw new Exception("Ungültige Plugin-Informationen");
            }
            
            // In Datenbank speichern
            $this->db->query(
                "INSERT INTO plugins (name, directory, version, description, author, active, load_order) 
                 VALUES (?, ?, ?, ?, ?, 1, 100) 
                 ON DUPLICATE KEY UPDATE active = 1",
                [
                    $pluginInfo['name'],
                    $directory,
                    $pluginInfo['version'] ?? '1.0',
                    $pluginInfo['description'] ?? '',
                    $pluginInfo['author'] ?? ''
                ]
            );
            
            // Plugin laden
            $this->loadPlugin($directory);
            
            return true;
        } catch (Exception $e) {
            error_log("Plugin-Aktivierung fehlgeschlagen [{$directory}]: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Plugin deaktivieren
     */
    public function deactivatePlugin($directory) {
        try {
            // Plugin deaktivieren
            if (isset($this->plugins[$directory])) {
                $plugin = $this->plugins[$directory]['instance'];
                
                if (method_exists($plugin, 'deactivate')) {
                    $plugin->deactivate();
                }
                
                unset($this->plugins[$directory]);
            }
            
            // In Datenbank deaktivieren
            $this->db->query(
                "UPDATE plugins SET active = 0 WHERE directory = ?",
                [$directory]
            );
            
            return true;
        } catch (Exception $e) {
            error_log("Plugin-Deaktivierung fehlgeschlagen [{$directory}]: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Alle verfügbaren Plugins scannen
     */
    public function scanPlugins() {
        $availablePlugins = [];
        
        if (!is_dir($this->pluginDir)) {
            mkdir($this->pluginDir, 0755, true);
            return $availablePlugins;
        }
        
        $dirs = scandir($this->pluginDir);
        
        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..') continue;
            
            $pluginPath = $this->pluginDir . '/' . $dir;
            
            if (is_dir($pluginPath)) {
                $mainFile = $pluginPath . '/' . $dir . '.php';
                
                if (file_exists($mainFile)) {
                    $info = $this->getPluginInfo($mainFile);
                    
                    if ($info) {
                        $availablePlugins[$dir] = $info;
                        $availablePlugins[$dir]['directory'] = $dir;
                        $availablePlugins[$dir]['path'] = $pluginPath;
                    }
                }
            }
        }
        
        return $availablePlugins;
    }
    
    /**
     * Plugin-Status abrufen
     */
    public function getPluginStatus($directory) {
        try {
            $result = $this->db->fetchOne(
                "SELECT active FROM plugins WHERE directory = ?",
                [$directory]
            );
            
            return $result ? (bool)$result['active'] : false;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Alle geladenen Plugins abrufen
     */
    public function getLoadedPlugins() {
        return $this->plugins;
    }
}

// Globale Plugin-Funktionen
function addAction($hook, $callback, $priority = 10) {
    PluginManager::getInstance()->addAction($hook, $callback, $priority);
}

function doAction($hook, ...$args) {
    PluginManager::getInstance()->doAction($hook, ...$args);
}

function applyFilter($hook, $value, ...$args) {
    return PluginManager::getInstance()->applyFilter($hook, $value, ...$args);
}
