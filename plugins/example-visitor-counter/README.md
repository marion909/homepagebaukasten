# Visitor Counter Plugin

Ein einfaches aber leistungsfähiges Plugin zur Besucherzählung für das Baukasten CMS.

## Features

- **Besucher-Tracking**: Zählt Seitenaufrufe und eindeutige Besucher
- **Flexible Anzeige**: Verschiedene Anzeigestile (Standard, Minimal, Detailliert)
- **DSGVO-konform**: IP-Anonymisierung und konfigurierbare Datenaufbewahrung
- **Bot-Erkennung**: Filtert automatisch Suchmaschinen-Bots aus
- **Admin-Dashboard**: Detaillierte Statistiken und Konfiguration
- **Shortcode-Unterstützung**: Einfache Integration in Inhalte
- **Real-time Updates**: Automatische Aktualisierung der Counter (optional)

## Installation

1. Plugin-Ordner in das `/plugins/` Verzeichnis kopieren
2. Plugin über das Admin-Panel aktivieren
3. Einstellungen unter Admin → Tools → Visitor Counter konfigurieren

## Verwendung

### Automatische Anzeige
Das Plugin kann automatisch Counter auf allen Seiten anzeigen. Die Position ist konfigurierbar:
- Oben im Inhalt
- Unten im Inhalt
- Manuell per Shortcode

### Shortcodes

```php
// Standard Counter
[visitor_counter]

// Minimaler Stil
[visitor_counter style="minimal"]

// Nur Gesamtaufrufe anzeigen
[visitor_counter show="visits"]

// Nur eindeutige Besucher anzeigen
[visitor_counter show="unique"]
```

### Programmatische Verwendung

```php
// Counter-Daten für aktuelle Seite abrufen
$stats = VisitorCounter::getPageStats();

// Counter für spezifische Seite
$stats = VisitorCounter::getPageStats(123);

// Gesamtstatistiken
$totalStats = VisitorCounter::getTotalStats();
```

## Konfiguration

### Grundeinstellungen
- **Counter anzeigen**: Ein/Aus für automatische Anzeige
- **Position**: Wo der Counter angezeigt werden soll
- **Administratoren ausschließen**: Admins von der Zählung ausschließen
- **Stil**: Visueller Stil des Counters

### Erweiterte Einstellungen
- **IP-Anonymisierung**: DSGVO-konforme IP-Behandlung
- **Bot-Erkennung**: Suchmaschinen-Bots ausfiltern
- **Datenaufbewahrung**: Wie lange Daten gespeichert werden
- **Cache-Zeit**: Caching für bessere Performance

## Datenschutz

Das Plugin ist DSGVO-konform gestaltet:

- **IP-Anonymisierung**: IPs werden vor der Speicherung anonymisiert
- **Begrenzte Datenaufbewahrung**: Automatisches Löschen alter Daten
- **Keine Cookies**: Funktioniert ohne Cookies
- **Opt-out möglich**: Administratoren können ausgeschlossen werden

## API Referenz

### Hooks

```php
// Vor dem Speichern eines Besuchs
add_action('visitor_counter_before_log', function($pageId, $ip) {
    // Custom logic
});

// Nach dem Speichern eines Besuchs
add_action('visitor_counter_after_log', function($pageId, $ip, $visitId) {
    // Custom logic
});

// Filter für Bot-Erkennung
add_filter('visitor_counter_is_bot', function($isBBot, $userAgent) {
    // Custom bot detection
    return $isBot;
});

// Filter für Counter-Output
add_filter('visitor_counter_output', function($html, $stats, $style) {
    // Modify counter HTML
    return $html;
});
```

### Methoden

```php
// Hauptklasse: VisitorCounter

// Besucher loggen
VisitorCounter::logVisit($pageId = null);

// Statistiken abrufen
VisitorCounter::getPageStats($pageId = null);
VisitorCounter::getTotalStats();

// Einstellungen
VisitorCounter::getSetting($key, $default = null);
VisitorCounter::updateSetting($key, $value);

// Datenbereinigung
VisitorCounter::cleanOldData();
```

## Datenbankstruktur

### visitor_stats Tabelle
```sql
CREATE TABLE visitor_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    page_id INT DEFAULT 0,
    visit_date DATE NOT NULL,
    visits INT DEFAULT 1,
    unique_visitors INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_page_date (page_id, visit_date)
);
```

### visitor_logs Tabelle
```sql
CREATE TABLE visitor_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    page_id INT DEFAULT 0,
    ip_hash VARCHAR(64) NOT NULL,
    user_agent TEXT,
    referer TEXT,
    visit_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_page_id (page_id),
    INDEX idx_visit_time (visit_time),
    INDEX idx_ip_hash (ip_hash)
);
```

## Anpassung

### Custom Styles
Eigene CSS-Styles können hinzugefügt werden:

```css
.visitor-counter.custom-style {
    background: #your-color;
    border: 2px solid #your-border;
    /* Your custom styles */
}
```

### Template Überschreibung
Templates können im Theme überschrieben werden:

1. Ordner `/theme/plugins/visitor-counter/` erstellen
2. Template-Dateien kopieren und anpassen
3. Plugin verwendet automatisch Theme-Templates

## Troubleshooting

### Counter wird nicht angezeigt
- Plugin aktiviert?
- Einstellung "Counter anzeigen" aktiv?
- JavaScript-Fehler in Browser-Konsole?

### Falsche Zahlen
- Bot-Erkennung aktiviert?
- Administratoren ausgeschlossen?
- Cache-Zeit berücksichtigen

### Performance-Probleme
- Cache-Zeit erhöhen
- Alte Daten regelmäßig bereinigen
- Database-Indizes prüfen

## Support

- **Dokumentation**: `/docs/plugin-development.md`
- **Issues**: GitHub Repository
- **Community**: Baukasten CMS Forum

## Changelog

### Version 1.0.0
- Initiale Version
- Grundlegende Besucherzählung
- Admin-Interface
- DSGVO-Konformität
- Shortcode-Unterstützung

## Lizenz

Dieses Plugin steht unter der MIT-Lizenz. Siehe LICENSE Datei für Details.
