# Phase 2: Auth, Nutzerbasis und geschützte Bereiche

## 1. Ziel der Phase

Phase 2 baut auf der abgeschlossenen technischen Grundlage aus Phase 1 auf.

Ziel dieser Phase ist es, eine stabile Authentifizierungs- und Nutzerbasis für Stechen-MMO zu schaffen.

Am Ende dieser Phase sollen Nutzer sich registrieren, einloggen und ausloggen können. Außerdem soll es einen geschützten Bereich geben, der nur für eingeloggte Nutzer erreichbar ist.

Diese Phase legt die Grundlage für spätere Spielfunktionen wie Lobby, Wallet, Spielerprofil, Matchmaking und Spielhistorie.

---

## 2. Ergebnis der Phase

Nach Abschluss von Phase 2 soll gelten:

- Registrierung ist verfügbar
- Login ist verfügbar
- Logout funktioniert
- Passwort-Reset ist vorbereitet oder bewusst verschoben
- Ein geschütztes Dashboard existiert
- Nicht eingeloggte Nutzer werden korrekt weitergeleitet
- Eingeloggte Nutzer können ihre Basisdaten sehen
- Das User-Modell ist als Grundlage für Spieler vorbereitet
- Auth-Routen sind nachvollziehbar
- Session-Verhalten ist geprüft
- CSRF-Schutz funktioniert
- Relevante Tests laufen erfolgreich
- Dokumentation ist aktualisiert
- Änderungen sind committed und gepusht

---

## 3. Nicht-Ziele dieser Phase

Phase 2 soll bewusst fokussiert bleiben.

Nicht Bestandteil dieser Phase sind:

- Wallet-System
- Spielgeld oder Echtgeld-Logik
- Zahlungsanbieter
- Lobby-System
- Matchmaking
- Kartenspiel-Engine
- Live-Multiplayer
- WebSockets
- Ranglisten
- Freundeslisten
- Gildenintegration
- Adminbereich
- komplexe Rollen- und Rechteverwaltung
- Benutzeravatargenerierung
- öffentliche Spielerprofile
- E-Mail-Verifikation als Pflichtfeature
- Zwei-Faktor-Authentifizierung

Diese Punkte können in späteren Phasen umgesetzt werden.

---

## 4. Architekturentscheidung

Für die Auth-Grundlage wird Laravel Breeze mit Blade bevorzugt.

Grund:

- passt zur bestehenden Laravel-Struktur
- passt zur Entscheidung: Blade als Basis, Vue als Inselarchitektur
- liefert Login, Registrierung, Passwort-Reset und Dashboard-Grundlage
- bleibt leichtgewichtig
- ist gut testbar
- vermeidet unnötige Komplexität

Vue wird weiterhin nicht als komplette Single Page Application verwendet.

Vue-Komponenten können später gezielt für interaktive Bereiche eingesetzt werden, zum Beispiel:

- Lobby
- Spieltisch
- Kartenhand
- Timer
- Einsatzanzeige
- Live-Status
- Benachrichtigungen

---

## 5. Geplantes Auth-Paket

Vorgesehen:

```text
Laravel Breeze
```

Vorgesehener Stack:

```text
Blade
```

Voraussichtliche Installation:

```bash
composer require laravel/breeze --dev
php artisan breeze:install blade
npm install
npm run build
php artisan migrate
```

Hinweis:

Die exakten Kommandos werden erst ausgeführt, wenn Phase 2 aktiv begonnen wird.

---

## 6. Auswirkungen auf bestehende Dateien

Laravel Breeze wird voraussichtlich mehrere Dateien anlegen oder verändern.

Zu erwarten sind Änderungen in:

```text
routes/web.php
resources/views/
resources/css/app.css
resources/js/app.js
app/Models/User.php
app/Http/Controllers/
app/Http/Requests/
database/migrations/
tests/
composer.json
package.json
```

Vor der Installation sollte der Working Tree sauber sein.

Vorher prüfen:

```bash
git status
```

Nach der Installation prüfen:

```bash
git diff
git status
```

---

## 7. Checkliste: Vorbereitung

- [ ] Branch `main` ist aktuell
- [ ] `git status` ist sauber
- [ ] Phase-1-Abschluss ist committed und gepusht
- [ ] Lokaler Server funktioniert
- [ ] Datenbankverbindung funktioniert
- [ ] Migrationen laufen
- [ ] `composer test` funktioniert
- [ ] `npm run build` funktioniert
- [ ] Entscheidung für Laravel Breeze mit Blade bestätigt

Nützliche Kommandos:

```bash
git status
composer test
npm run build
php artisan migrate:status
```

---

## 8. Checkliste: Breeze installieren

- [ ] Laravel Breeze als Dev-Abhängigkeit installieren
- [ ] Breeze mit Blade installieren
- [ ] Node-Abhängigkeiten aktualisieren
- [ ] Frontend bauen
- [ ] Migrationen ausführen
- [ ] Auth-Routen prüfen
- [ ] Neue und geänderte Dateien prüfen
- [ ] Tests ausführen
- [ ] Änderungen committen

Voraussichtliche Kommandos:

```bash
cd laravel-app

composer require laravel/breeze --dev

php artisan breeze:install blade

npm install

npm run build

php artisan migrate

composer test
```

Danach aus Repository-Wurzel:

```bash
cd ..

git status
git diff
```

---

## 9. Checkliste: Auth-Routen

Nach der Breeze-Installation sollen die Auth-Routen geprüft werden.

Zu prüfen:

- [ ] Login-Route existiert
- [ ] Register-Route existiert
- [ ] Logout-Route existiert
- [ ] Dashboard-Route existiert
- [ ] Passwort-vergessen-Route existiert oder ist bewusst verschoben
- [ ] Nicht eingeloggte Nutzer werden zu Login weitergeleitet
- [ ] Eingeloggte Nutzer erreichen Dashboard

Nützliches Kommando:

```bash
php artisan route:list
```

Erwartete Routen ungefähr:

```text
GET|HEAD login
POST login
GET|HEAD register
POST register
POST logout
GET|HEAD dashboard
```

Die tatsächliche Ausgabe ist nach Installation zu prüfen.

---

## 10. Checkliste: Registrierung

Zu prüfen:

- [ ] Registrierungsseite ist erreichbar
- [ ] Name kann eingegeben werden
- [ ] E-Mail kann eingegeben werden
- [ ] Passwort kann eingegeben werden
- [ ] Passwortbestätigung funktioniert
- [ ] Validierungsfehler werden angezeigt
- [ ] Neuer Nutzer wird in der Datenbank angelegt
- [ ] Nutzer ist nach Registrierung eingeloggt oder wird korrekt weitergeleitet
- [ ] Registrierung funktioniert nach Browser-Refresh weiterhin korrekt

Testfälle:

```text
gültige Registrierung
fehlende E-Mail
ungültige E-Mail
zu kurzes Passwort
Passwortbestätigung stimmt nicht überein
bereits vergebene E-Mail
```

---

## 11. Checkliste: Login

Zu prüfen:

- [ ] Login-Seite ist erreichbar
- [ ] Login mit gültigen Daten funktioniert
- [ ] Login mit falschem Passwort schlägt sauber fehl
- [ ] Login mit unbekannter E-Mail schlägt sauber fehl
- [ ] Validierungsfehler werden angezeigt
- [ ] Nach Login erfolgt Weiterleitung zum geschützten Bereich
- [ ] Bereits eingeloggte Nutzer werden sinnvoll behandelt
- [ ] Session-Cookie wird gesetzt

Testfälle:

```text
gültiger Login
falsches Passwort
unbekannte E-Mail
leeres Formular
Refresh nach Login
geschützte Seite direkt aufrufen
```

---

## 12. Checkliste: Logout

Zu prüfen:

- [ ] Logout-Button oder Logout-Formular ist vorhanden
- [ ] Logout funktioniert
- [ ] Session wird beendet
- [ ] Nach Logout ist Dashboard nicht mehr erreichbar
- [ ] Nutzer wird sinnvoll weitergeleitet
- [ ] Browser-Back-Button zeigt keine geschützten Inhalte nutzbar an

Testfälle:

```text
Logout aus Dashboard
Dashboard nach Logout neu laden
Dashboard nach Logout direkt aufrufen
```

---

## 13. Checkliste: Passwort-Reset

Passwort-Reset ist grundsätzlich Teil von Breeze, kann aber abhängig von Mail-Konfiguration zunächst nur vorbereitet werden.

Aktuelle lokale Mail-Konfiguration aus Phase 1:

```env
MAIL_MAILER=log
```

Zu prüfen:

- [ ] Passwort-vergessen-Seite ist erreichbar
- [ ] Formular validiert E-Mail-Adressen
- [ ] Mail wird lokal geloggt oder Funktion wird bewusst verschoben
- [ ] Passwort-Reset-Flow ist dokumentiert
- [ ] Entscheidung getroffen: aktiv nutzen oder später finalisieren

Mögliche Entscheidung:

```text
Passwort-Reset wird technisch vorbereitet, aber produktiv erst in einer späteren Deployment-/Mail-Phase finalisiert.
```

---

## 14. Geschütztes Dashboard

Ein Dashboard soll als erster geschützter Bereich dienen.

Ziel:

```text
/dashboard ist nur für eingeloggte Nutzer erreichbar.
```

Zu prüfen:

- [ ] Dashboard-Route existiert
- [ ] Dashboard verwendet Projektlayout
- [ ] Dashboard ist mit Auth-Middleware geschützt
- [ ] Nicht eingeloggte Nutzer werden zu Login weitergeleitet
- [ ] Eingeloggte Nutzer sehen Dashboard
- [ ] Dashboard zeigt mindestens den eingeloggten Nutzer oder Platzhalterinformationen
- [ ] Dashboard enthält keine Spiellogik
- [ ] Dashboard ist optisch konsistent mit Stechen-MMO

Mögliche Inhalte zunächst:

- Begrüßung
- Nutzername
- E-Mail
- Hinweis auf kommende Lobby
- Hinweis auf kommende Wallet
- Logout-Möglichkeit

---

## 15. User-Modell als Spielerbasis

Das vorhandene Laravel-User-Modell wird zur Grundlage für spätere Spieler.

In Phase 2 noch nicht umsetzen:

- Wallet
- Coins
- Echtgeld
- Rang
- Level
- Statistik
- Inventar
- Gilde
- Freundesliste

Aber vorbereitend dokumentieren:

Zukünftige mögliche Felder oder Relationen:

```text
users
player_profiles
wallets
matches
match_players
game_statistics
```

In Phase 2 soll vermieden werden, das User-Modell vorschnell mit Spiellogik zu überladen.

Empfehlung:

```text
User bleibt Auth-Identität.
Spielbezogene Daten kommen später in eigene Tabellen.
```

---

## 16. Datenbank

Nach Breeze-Installation werden vorhandene Auth-nahe Tabellen geprüft.

Typische Tabellen:

```text
users
password_reset_tokens
sessions
cache
jobs
```

Zu prüfen:

- [ ] `users`-Tabelle existiert
- [ ] Migrationen laufen sauber durch
- [ ] Sessions funktionieren mit Datenbanktreiber
- [ ] Testnutzer kann angelegt werden
- [ ] Testnutzer kann gelöscht oder für spätere Tests behalten werden

Nützliche Kommandos:

```bash
php artisan migrate:status
php artisan migrate
```

Optional für lokale Entwicklung:

```bash
php artisan tinker
```

---

## 17. Session-Verhalten

Da in Phase 1 `SESSION_DRIVER=database` verwendet wird, soll Session-Verhalten bewusst geprüft werden.

Zu prüfen:

- [ ] Session-Tabelle existiert
- [ ] Login erzeugt Session-Eintrag
- [ ] Logout beendet Session nachvollziehbar
- [ ] CSRF-Schutz funktioniert
- [ ] 419-Fehlerseite ist vorhanden
- [ ] Abgelaufene Session wird sauber behandelt

Relevante Fehlerseite:

```text
resources/views/errors/419.blade.php
```

---

## 18. CSRF-Schutz

Formulare müssen mit CSRF-Schutz arbeiten.

Zu prüfen:

- [ ] Login-Formular enthält CSRF-Token
- [ ] Register-Formular enthält CSRF-Token
- [ ] Logout nutzt POST und CSRF
- [ ] Manipulierte oder abgelaufene Formulare werden sauber behandelt
- [ ] 419-Fehlerseite nutzt Projektlayout

---

## 19. Layout und Navigation

Nach Breeze-Installation kann Breeze eigene Layouts mitbringen.

Zu prüfen:

- [ ] Bestehendes Projektlayout bleibt funktionsfähig
- [ ] Fehlerseiten funktionieren weiterhin
- [ ] Startseite funktioniert weiterhin
- [ ] Regelseite funktioniert weiterhin
- [ ] Auth-Seiten sind optisch akzeptabel
- [ ] Navigation enthält sinnvolle Links
- [ ] Login/Register werden sichtbar, wenn Nutzer ausgeloggt ist
- [ ] Dashboard/Logout werden sichtbar, wenn Nutzer eingeloggt ist

Mögliche spätere Navigationsstruktur:

Ausgeloggt:

```text
Start
Regeln
Login
Registrieren
```

Eingeloggt:

```text
Start
Regeln
Dashboard
Logout
```

---

## 20. Fehlerseiten nach Auth prüfen

Die in Phase 1 angelegten Fehlerseiten müssen nach Auth weiterhin funktionieren.

Zu prüfen:

- [ ] 403 nutzt Projektlayout
- [ ] 404 nutzt Projektlayout
- [ ] 419 nutzt Projektlayout
- [ ] 500 nutzt Projektlayout
- [ ] 503 nutzt Projektlayout
- [ ] Fehlerseiten verursachen keine Folgefehler
- [ ] Links auf Fehlerseiten funktionieren

Test-URLs:

```text
/statistiken
/nicht-vorhanden
/dashboard ausgeloggt
```

---

## 21. Tests

Nach Auth-Installation sind Tests besonders wichtig.

Zu prüfen:

- [ ] Bestehende Tests laufen weiterhin
- [ ] Breeze-Tests laufen
- [ ] Registrierungstest läuft
- [ ] Logintest läuft
- [ ] Logouttest läuft
- [ ] Passwort-Reset-Test wird geprüft oder bewusst angepasst
- [ ] `composer test` läuft

Nützliche Kommandos:

```bash
composer test
php artisan test
```

Bei PHP-8.5-Deprecation-Warnungen gilt:

```text
Deprecations aus externen Paketen werden dokumentiert, aber nicht als Projektfehler gewertet.
```

---

## 22. Frontend-Build

Nach Breeze-Installation und Layout-Anpassungen muss der Frontend-Build funktionieren.

Zu prüfen:

- [ ] `npm install` ist ausgeführt
- [ ] `npm run build` funktioniert
- [ ] Vite funktioniert lokal
- [ ] CSS wird geladen
- [ ] JavaScript verursacht keine Browserfehler
- [ ] Vue-Test aus Phase 1 funktioniert weiterhin oder wird bewusst entfernt/verschoben

Nützliche Kommandos:

```bash
npm run build
npm run dev
```

---

## 23. Sicherheit in dieser Phase

Zu beachten:

- Keine echten Passwörter committen
- `.env` nicht committen
- `.env.example` aktuell halten
- Keine Testzugänge mit echten Daten dokumentieren
- Keine produktiven Mail-Zugangsdaten verwenden
- Kein Echtgeldsystem einbauen
- Keine Adminrechte ohne Konzept einbauen

---

## 24. Lokale Testnutzer

Für lokale Tests darf ein Testnutzer angelegt werden.

Beispiel:

```text
Name: Testspieler
E-Mail: testspieler@example.com
Passwort: lokal frei wählbar
```

Wichtig:

```text
Keine echten Passwörter in die Dokumentation schreiben.
```

Optional später:

- Seeder für lokalen Testnutzer
- Factory für User
- Feature-Tests mit User Factory

---

## 25. Dokumentationspflicht

Während Phase 2 sollen relevante Entscheidungen dokumentiert werden.

Zu dokumentieren:

- Auth-Paket
- Installationsweg
- geänderte Dateien
- relevante Routen
- Teststatus
- bekannte Warnungen
- bewusst verschobene Punkte
- Ergebnis der Phase

---

## 26. Mögliche Commits in Phase 2

Sinnvolle kleine Commit-Schritte:

```text
docs: add phase 2 auth plan
chore: install laravel breeze
feat: add authentication views
feat: add protected dashboard
test: verify authentication flow
docs: document phase 2 auth setup
docs: finalize phase 2 auth foundation
```

Nicht alles muss exakt so passieren. Wichtig ist, dass Commits klein und nachvollziehbar bleiben.

---

## 27. Risiken

Mögliche Risiken:

- Breeze überschreibt oder ergänzt Layouts unerwartet
- Navigation muss mit Auth-Zustand abgestimmt werden
- Tests können durch geänderte Weiterleitungen fehlschlagen
- Passwort-Reset benötigt Mail-Konfiguration
- Session-Treiber `database` muss sauber funktionieren
- Dashboard darf nicht versehentlich öffentlich erreichbar sein
- Fehlerseiten dürfen nicht durch Layoutänderungen kaputtgehen

Gegenmaßnahmen:

- vor Installation sauberer Git-Status
- nach jedem Schritt diff prüfen
- Tests früh ausführen
- Browser manuell prüfen
- kleine Commits
- Dokumentation aktuell halten

---

## 28. Abhängigkeiten zu späteren Phasen

Phase 2 schafft Grundlagen für:

- Phase 3: Spielerprofil
- Phase 4: Wallet oder Spielkonto
- Phase 5: Lobby
- Phase 6: Matchmaking
- Phase 7: Spieltisch
- Phase 8: Spielhistorie und Statistiken
- Phase 9: Closed Beta
- Phase 10: Deployment/Live-Test

Die genaue Phasenbenennung kann später angepasst werden.

---

## 29. Definition of Done

Phase 2 gilt als abgeschlossen, wenn:

- [x] Laravel Breeze mit Blade ist installiert oder eine gleichwertige Auth-Lösung ist dokumentiert
- [x] Registrierung funktioniert
- [x] Login funktioniert
- [x] Logout funktioniert
- [x] Dashboard ist geschützt erreichbar
- [x] Nicht eingeloggte Nutzer werden korrekt weitergeleitet
- [x] Eingeloggte Nutzer sehen den geschützten Bereich
- [x] Auth-Seiten sind optisch akzeptabel integriert
- [x] User-Modell bleibt sauber von Spiellogik getrennt
- [x] Session-Verhalten ist geprüft
- [x] CSRF-Verhalten ist geprüft
- [x] Fehlerseiten funktionieren weiterhin
- [x] `composer test` läuft
- [x] `npm run build` läuft
- [x] Dokumentation ist aktualisiert
- [x] Änderungen sind committed
- [x] Änderungen sind gepusht
- [x] `git status` ist sauber

---

## 30. Aktueller Abschlussstand

Phase 2 ist technisch umgesetzt und abgeschlossen.

Umgesetzt und geprüft sind:

- Laravel Breeze/Auth-Struktur mit Blade-Views
- Registrierung mit Annahme der rechtlichen Hinweise
- Login mit Validierung und Rate-Limiting
- Logout per POST mit CSRF-Schutz
- Passwort-Reset-Flow auf technischer Ebene
- geschütztes Dashboard unter `/dashboard`
- Admin-Grundlage unter `/admin` mit Permission `admin.access`
- erweitertes User-Modell als Auth- und Account-Grundlage
- dunkle Projektlayouts für Auth-, App- und Fehlerseiten
- Flash-Toast-Komponente für Statusmeldungen
- Tests für Auth-, Profil-, Passwort- und Zugriffskontrolle

Zusätzlich wurde ein expliziter Zugriffskontrolltest ergänzt:

```text
laravel-app/tests/Feature/AccessControlTest.php
```

Dieser Test deckt ab:

- Gäste werden von `/dashboard` zu `/login` weitergeleitet
- eingeloggte Nutzer können `/dashboard` sehen
- Gäste werden von `/admin` zu `/login` weitergeleitet
- eingeloggte Nutzer ohne `admin.access` erhalten für `/admin` einen 403-Status
- eingeloggte Nutzer mit `admin.access` können das Admin-Dashboard sehen

Aktueller Teststand nach Ergänzung des Zugriffskontrolltests:

```text
composer test
Tests: 30 deprecated, 1 passed (74 assertions)
```

Die Deprecation-Hinweise beziehen sich auf die bekannte externe Warnung:

```text
Constant PDO::MYSQL_ATTR_SSL_CA is deprecated since 8.5, use Pdo\Mysql::ATTR_SSL_CA instead
```

Diese Warnung wird aktuell dokumentiert, aber nicht als Projektfehler gewertet.

Aktueller Git-Stand zum Abschluss:

- Test-Commit für Dashboard- und Admin-Zugriff wurde erstellt
- lokale Commits wurden nach `origin/main` gepusht
- Arbeitsbaum war vor dem Push sauber

---

## 31. Abschlussnotiz

Phase 2 ist der Übergang von der technischen Projektgrundlage zu einer nutzbaren Spielerbasis.

Nach dieser Phase kann Stechen-MMO erstmals zwischen Gästen und eingeloggten Spielern unterscheiden.

Damit wird die Grundlage geschaffen, um später echte Spielkonten, Lobbys, Spielrunden und Beta-Tests mit ausgewählten Testern aufzubauen.
