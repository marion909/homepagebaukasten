-- Sofort-Fix für Login-Problem
-- Führe diese SQL-Befehle in phpMyAdmin aus:

-- 1. Korrekte Passwort-Hash für admin123 setzen
UPDATE users SET password_hash = '$2y$12$4OC7QbVRMYnXlC0578pOnOqfsP5TMeHprGZZWTmmpim/K7GLO1Aum' WHERE username = 'admin';

-- 2. E-Mail aktualisieren (optional)
UPDATE users SET email = 'admin@baukasten.neuhauser.cloud' WHERE username = 'admin';

-- 3. Sicherstellen, dass User aktiv ist
UPDATE users SET active = 1 WHERE username = 'admin';

-- 4. Überprüfung - sollte einen Eintrag zeigen
SELECT id, username, email, role, active FROM users WHERE username = 'admin';
