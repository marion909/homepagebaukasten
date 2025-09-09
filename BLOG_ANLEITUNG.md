# Anleitung: Blog-Post erstellen im Baukasten CMS

## 🎯 Erste Schritte

### 1. Datenbank vorbereiten
**Wichtig:** Führe zuerst die SQL-Datei `blog-categories-tags.sql` in phpMyAdmin aus!

1. Öffne phpMyAdmin
2. Wähle deine CMS-Datenbank aus
3. Klicke auf "SQL"
4. Kopiere den kompletten Inhalt von `blog-categories-tags.sql` und führe ihn aus
5. Die Tabellen `blog_categories`, `blog_tags`, `blog_post_categories`, `blog_post_tags` werden erstellt

### 2. Admin-Bereich aufrufen
- Öffne: `http://localhost/baukasten/admin/`
- Melde dich mit deinem Admin-Account an
- Klicke in der Navigation auf **"Blog"**

## ✍️ Blog-Post erstellen

### 1. Neuen Post starten
- Klicke auf **"Neuer Beitrag"**
- Oder direkt: `http://localhost/baukasten/admin/blog.php?action=new`

### 2. Grunddaten eingeben

#### **Titel** (Pflichtfeld)
```
Beispiel: "Mein erstes CMS Tutorial"
```
- Der Titel wird automatisch in einen URL-Slug umgewandelt
- Umlaute werden automatisch konvertiert (ä→ae, ö→oe, ü→ue)

#### **URL-Slug** (Optional)
```
Automatisch: "mein-erstes-cms-tutorial"
Manual: "cms-tutorial-2024"
```
- Wird automatisch vom Titel generiert
- Kann manuell angepasst werden
- Muss eindeutig sein

#### **Status**
- **Entwurf**: Nicht öffentlich sichtbar
- **Veröffentlicht**: Öffentlich auf der Website

#### **Tags**
```
Beispiel: "PHP, Tutorial, CMS, Anfänger"
```
- Mit Komma getrennt eingeben
- Neue Tags werden automatisch erstellt
- Bestehende Tags werden wiederverwendet

### 3. Kategorien zuweisen
- Mehrere Kategorien können ausgewählt werden
- Standard-Kategorien: Technologie, Lifestyle, Business, Tutorial, News
- Neue Kategorien können unter **"Kategorien verwalten"** erstellt werden

### 4. Inhalte erstellen

#### **Kurzbeschreibung/Excerpt**
```
Beispiel: "In diesem Tutorial zeige ich dir, wie du dein erstes CMS einrichtest und verwendest."
```
- Wird in Blog-Übersichten angezeigt
- Sollte 1-2 Sätze umfassen

#### **Hauptinhalt**
- Vollständiger **TinyMCE WYSIWYG-Editor**
- Unterstützt: Text-Formatierung, Listen, Links, Bilder, Tabellen
- **Medien-Integration**: Bilder direkt hochladen oder aus Medienbibliothek wählen
- **Code-Ansicht**: Für erweiterte HTML-Bearbeitung

### 5. Speichern & Veröffentlichen
- **"Blog-Post erstellen"**: Speichert den Beitrag
- Status "Entwurf" = Nicht öffentlich
- Status "Veröffentlicht" = Sofort live

## 🔗 URLs & Zugriff

### Frontend-URLs
```
Einzelner Post: /blog/mein-post-slug
Kategorie: /blog/category/technologie
Tag: /blog/tag/php
Blog-Übersicht: /blog
```

### Admin-URLs
```
Blog-Verwaltung: /admin/blog.php
Kategorien: /admin/blog-categories.php
Kommentare: /admin/comments.php
```

## 🎨 Shortcodes für Seiten

Du kannst diese Shortcodes in normalen Seiten verwenden:

### Blog-Liste anzeigen
```
[blog_list]                    // Alle Posts
[blog_list limit="3"]          // Nur 3 neueste Posts
[blog_list category="tutorial"] // Nur Tutorial-Posts
```

### Kategorien-Liste
```
[blog_categories]              // Alle Kategorien mit Post-Anzahl
```

### Tag-Cloud
```
[blog_tags]                    // Tag-Wolke mit Popularität
```

## 🔧 Erweiterte Features

### Medien einbinden
1. **Über Editor**: Bild-Button → Upload oder Medienbibliothek
2. **Drag & Drop**: Bilder direkt in den Editor ziehen
3. **Medien-Button**: Öffnet Medienauswahl-Popup

### Kategorien verwalten
- **"Kategorien verwalten"** → Neue Kategorien erstellen
- Jede Kategorie hat: Name, Slug, Beschreibung
- Automatische Slug-Generierung mit Live-Vorschau

### SEO-Optimierung
- **Automatische Meta-Tags** für Kategorie/Tag-Seiten
- **Sprechende URLs** (/blog/category/technologie)
- **Excerpt** für Social Media Snippets

## ✅ Checkliste für ersten Post

- [ ] SQL-Datei in phpMyAdmin ausgeführt
- [ ] Admin-Bereich aufgerufen (/admin/blog.php)
- [ ] "Neuer Beitrag" geklickt
- [ ] Titel eingegeben
- [ ] Mindestens eine Kategorie ausgewählt
- [ ] Tags hinzugefügt (z.B. "Tutorial, Anfänger")
- [ ] Kurzbeschreibung geschrieben
- [ ] Hauptinhalt mit TinyMCE erstellt
- [ ] Status auf "Veröffentlicht" gesetzt
- [ ] "Blog-Post erstellen" geklickt
- [ ] Frontend-Ansicht getestet (/blog/post-slug)

## 🎯 Tipps

1. **Erst als Entwurf speichern** und Vorschau prüfen
2. **Aussagekräftige Slugs** verwenden für bessere SEO
3. **Tags sparsam** verwenden (3-5 pro Post optimal)
4. **Excerpt immer ausfüllen** für bessere Übersichten
5. **Bilder komprimieren** vor dem Upload für Performance

**Viel Erfolg beim Bloggen! 🚀**
