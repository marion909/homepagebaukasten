-- Erweiterte Navigation Features für Pages
-- Führe diesen SQL-Code in phpMyAdmin aus

-- Prüfe aktuelle Struktur der pages Tabelle
DESCRIBE pages;

-- Füge fehlende Spalten zur pages Tabelle hinzu
ALTER TABLE pages 
ADD COLUMN show_in_nav TINYINT(1) DEFAULT 1 COMMENT 'Seite in Navigation anzeigen',
ADD COLUMN menu_order INT DEFAULT 0 COMMENT 'Reihenfolge in der Navigation';

-- Setze Standardwerte für bestehende Seiten
UPDATE pages SET show_in_nav = 1, menu_order = 0 WHERE show_in_nav IS NULL;

-- Spezielle Reihenfolge für wichtige Seiten
UPDATE pages SET menu_order = 1 WHERE slug = 'home';
UPDATE pages SET menu_order = 2 WHERE slug = 'about' OR slug = 'ueber-uns';
UPDATE pages SET menu_order = 3 WHERE slug = 'services' OR slug = 'leistungen';
UPDATE pages SET menu_order = 4 WHERE slug = 'contact' OR slug = 'kontakt';

-- Verstecke System-Seiten aus der Navigation (falls vorhanden)
UPDATE pages SET show_in_nav = 0 WHERE slug IN ('404', 'search', 'sitemap', 'imprint', 'privacy');

-- Zeige aktuelle Navigation an
SELECT slug, title, show_in_nav, menu_order 
FROM pages 
WHERE status = 'published' 
ORDER BY menu_order, title;
