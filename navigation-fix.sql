-- Navigation bereinigen und Blog-Seite prüfen
-- Führe diesen SQL-Code in phpMyAdmin aus

-- Prüfe ob eine Blog-Seite existiert
SELECT id, title, slug, show_in_nav, menu_order 
FROM pages 
WHERE slug = 'blog' OR title LIKE '%Blog%';

-- Falls eine Blog-Seite existiert, aber versteckt ist, zeige sie an
UPDATE pages 
SET show_in_nav = 1, menu_order = 99 
WHERE slug = 'blog' AND show_in_nav = 0;

-- Falls keine Blog-Seite existiert, erstelle eine (optional)
-- INSERT INTO pages (title, slug, content, status, show_in_nav, menu_order, created_by) 
-- VALUES ('Blog', 'blog', '[blog_list]', 'published', 1, 99, 1);

-- Aktuelle Navigation anzeigen
SELECT slug, title, show_in_nav, menu_order 
FROM pages 
WHERE status = 'published' 
ORDER BY menu_order, title;
