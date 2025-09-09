# Homepage Baukasten CMS - Installation

## WordPress-ähnliche Installationsroutine

### 🚀 Schnellstart

1. **Dateien hochladen**
   - Alle CMS-Dateien auf Ihren Webserver hochladen
   - Sicherstellen, dass PHP 7.4+ und MySQL verfügbar sind

2. **Installation starten**
   - Browser öffnen und zu `http://ihre-domain.de/install/` navigieren
   - Der Installationsassistent führt Sie durch 4 einfache Schritte

3. **Fertig!**
   - Nach der Installation automatische Umleitung zum Admin-Bereich
   - Install-Ordner wird automatisch gesichert

### 📋 Installationsschritte

#### Schritt 1: System-Anforderungen
- Automatische Überprüfung aller Systemanforderungen
- PHP Version, Extensions, Schreibrechte werden geprüft
- Weiter nur bei erfüllten Anforderungen

#### Schritt 2: Datenbank-Konfiguration
- MySQL-Verbindungsdaten eingeben
- Automatischer Verbindungstest
- Datenbank wird automatisch erstellt falls nicht vorhanden

#### Schritt 3: Website-Informationen
- Website-Titel und Beschreibung
- Administrator E-Mail
- Zeitzone auswählen

#### Schritt 4: Administrator-Account
- Admin-Benutzername und Passwort festlegen
- Vollständige Installation wird durchgeführt
- Standard-Seiten werden automatisch erstellt

### 🔧 Automatische Installation

**Was wird automatisch erledigt:**
- ✅ Vollständige Datenbankstruktur (alle Tabellen mit Indizes)
- ✅ Konfigurationsdatei (config.php) wird erstellt
- ✅ Sicherheitsschlüssel werden generiert
- ✅ Administrator-Account wird angelegt
- ✅ Grundeinstellungen werden konfiguriert
- ✅ Standard-Seiten (Home, Über uns, Kontakt, Blog) werden erstellt
- ✅ Sicherheitsmaßnahmen werden aktiviert

### 📁 Installationsdateien

- **`install.php`** - Hauptinstallationsroutine (WordPress-ähnlich)
- **`index.php`** - Installationsschutz und Umleitung
- **`secure.php`** - Post-Installation Sicherheitsmaßnahmen
- **`complete_installation.sql`** - Vollständige Datenbankstruktur
- **`minimal_installation.sql`** - Nur Basis-Tabellen
- **`README.md`** - Diese Installationsanleitung

### 🔒 Sicherheitsfeatures

**Automatische Sicherheitsmaßnahmen:**
- Install-Ordner wird nach Installation gesperrt
- Sensitive Dateien werden über .htaccess geschützt
- Sicherheits-Headers werden gesetzt
- CSRF-Schutz für alle Formulare
- Session-Sicherheit wird konfiguriert

**Manueller Schutz (empfohlen):**
- Install-Ordner komplett löschen nach erfolgreicher Installation
- SSL/HTTPS aktivieren
- Regelmäßige Backups erstellen
- Starke Passwörter verwenden

### 🔄 Alternative Installationsmethoden

#### Option 1: Automatische Installation (EMPFOHLEN)
```
http://ihre-domain.de/install/
```

#### Option 2: Manuelle SQL-Installation
```bash
# Über Kommandozeile
mysql -u benutzername -p datenbankname < complete_installation.sql

# Oder über phpMyAdmin
# -> SQL-Tab -> complete_installation.sql Inhalt einfügen -> Ausführen
```

#### Option 3: Programmatische Installation
```php
// In eigenem Script
include 'install/install.php';
// Installation-Logic aufrufen
```

### ⚡ System-Anforderungen

**Minimum:**
- PHP 7.4 oder höher
- MySQL 5.7 oder höher / MariaDB 10.2+
- 64 MB RAM
- 50 MB Festplattenspeicher

**Empfohlen:**
- PHP 8.0 oder höher
- MySQL 8.0 oder höher
- 128 MB RAM
- SSL/HTTPS
- mod_rewrite aktiviert

**Erforderliche PHP-Extensions:**
- PDO + PDO_MySQL
- JSON
- MBString
- OpenSSL
- GD oder ImageMagick (für Bildbearbeitung)

### 🚨 Troubleshooting

**Problem: "Fehler 500" beim Aufruf von install/**
- Lösung: PHP-Fehlerlog prüfen, meist fehlende Extensions

**Problem: "Datenbankverbindung fehlgeschlagen"**
- Lösung: MySQL-Zugangsdaten überprüfen, Datenbank existiert

**Problem: "Schreibrechte fehlen"**
- Lösung: Ordner-Permissions auf 755, Dateien auf 644 setzen

**Problem: "Installation bereits durchgeführt"**
- Lösung: `.installed` Datei löschen oder install-Ordner neu hochladen

### 🆘 Support

**Bei Problemen:**
1. PHP-Fehlerlog prüfen
2. Systemanforderungen nochmals überprüfen
3. Dateiberechtigungen kontrollieren
4. Saubere Neuinstallation versuchen

**Logs finden:**
- Server-Fehlerlog: meist `/var/log/apache2/error.log`
- PHP-Fehlerlog: `error_log()` Ausgaben
- CMS-Logs: werden nach Installation in `/logs/` erstellt

### 📚 Nach der Installation

**Erste Schritte:**
1. **Admin-Bereich besuchen:** `http://ihre-domain.de/admin/`
2. **Einstellungen anpassen:** Settings → Allgemein
3. **Theme auswählen:** Settings → Design
4. **Erste Seite bearbeiten:** Seiten → Startseite
5. **Menü konfigurieren:** Navigation → Hauptmenü

**Sicherheit:**
1. Install-Ordner löschen: `rm -rf install/`
2. Starke Passwörter verwenden
3. SSL/HTTPS einrichten
4. Regelmäßige Updates durchführen
5. Backups automatisieren

### 🔄 Updates

Das CMS verfügt über ein integriertes Update-System:
- Updates über Admin-Panel → System → Updates
- Automatische Benachrichtigung bei neuen Versionen
- Ein-Klick Updates mit Backup-Funktionalität
