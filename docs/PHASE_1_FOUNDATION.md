# Datei: docs/PHASE_1_FOUNDATION.md

# stechen-mmo — Phase 1: Foundation

Stand: Juni 2026  
Status: Umsetzungs-Checkliste

---

## 1. Ziel der Phase

Phase 1 schafft die technische Basis für das Projekt.

Am Ende dieser Phase soll das Projekt lokal stabil laufen, ein Basislayout besitzen und Vue 3 über Vite innerhalb von Laravel nutzbar sein.

Diese Phase enthält noch keine Spiellogik.

---

## 2. Ergebnis der Phase

Am Ende von Phase 1 gilt:

```text
Laravel läuft lokal.
Datenbankverbindung funktioniert.
Frontend-Build funktioniert.
Vue 3 ist eingerichtet.
Blade-Basislayout existiert.
Erste Seiten sind erreichbar.
Auth-Grundlage ist vorbereitet oder vorhanden.
Projekt ist sauber committet.
```

---

## 3. Technische Zielarchitektur für Phase 1

```text
Laravel
Blade
Vue 3
Vite
MySQL/MariaDB
Polling-fähige HTTP-Struktur
```

Kein vollständiges SPA.

Vue wird als Inselarchitektur eingebunden.

---

## 4. Checkliste: Projektzustand prüfen

- [x] Repository ist lokal vorhanden
- [x] Branch `main` ist aktuell
- [x] `git status` zeigt einen sauberen Working Tree
- [x] `.env` existiert lokal
- [ ] `APP_KEY` ist gesetzt
- [x] PHP-Version passt zu Laravel-Version
- [x] Composer-Abhängigkeiten sind installiert
- [x] Node-Abhängigkeiten sind installiert
- [ ] Datenbank ist lokal angelegt
- [ ] Datenbankzugang in `.env` ist korrekt
- [x] `php artisan` funktioniert
- [ ] Laravel-Startseite ist erreichbar

---

## 5. Checkliste: Backend-Grundlage

- [ ] Laravel-Version prüfen
- [ ] `.env.example` prüfen und bei Bedarf aktualisieren
- [ ] Datenbankverbindung testen
- [ ] Migrationen ausführen
- [ ] Cache-Kommandos testen
- [ ] Lokalen Development-Server starten
- [ ] Fehlerseite/Debug-Modus lokal prüfen

Nützliche Kommandos:

```bash
composer install
php artisan key:generate
php artisan migrate
php artisan serve
```

---

## 6. Checkliste: Frontend-Grundlage

- [ ] `package.json` prüfen
- [ ] Vite-Konfiguration prüfen
- [ ] Vue 3 installieren, falls noch nicht vorhanden
- [ ] Frontend-Abhängigkeiten installieren
- [ ] Development-Build starten
- [ ] Production-Build testen
- [ ] Vue-Testkomponente erstellen
- [ ] Vue-Testkomponente in Blade einbinden
- [ ] Prüfen, ob Hot Reload lokal funktioniert

Nützliche Kommandos:

```bash
npm install
npm run dev
npm run build
```

---

## 7. Checkliste: Vue 3 Integration

- [ ] `resources/js/app.js` oder `resources/js/app.ts` prüfen
- [ ] Vue-App initialisieren
- [ ] Erste Vue-Komponente erstellen
- [ ] Mount-Punkt im Blade-Layout ergänzen
- [ ] Datenübergabe von Blade zu Vue testen
- [ ] JSON-Endpoint testweise abrufen
- [ ] Fehlerausgabe im Browser prüfen

Beispiel-Komponente:

```text
resources/js/components/AppStatus.vue
```

Aufgabe der Komponente:

- anzeigen, dass Vue geladen wurde
- optional aktuellen App-Namen anzeigen
- optional Test-Request an Laravel-Endpunkt senden

---

## 8. Checkliste: Basislayout

- [ ] Hauptlayout erstellen oder prüfen
- [ ] Navigation anlegen
- [ ] Startseite anlegen
- [ ] Regeln-Link anlegen
- [ ] Login/Register Links vorsehen
- [ ] Dashboard-Link für eingeloggte Nutzer vorsehen
- [ ] Bereich für Flash-Messages vorsehen
- [ ] Bereich für Vue-Inseln vorsehen

Mögliche Datei:

```text
resources/views/layouts/app.blade.php
```

Erste Seiten:

```text
/
/rules
/dashboard
```

---

## 9. Checkliste: Auth-Grundlage

Noch keine komplexe Rollenlogik.

Ziel ist nur, eine stabile Grundlage für registrierte Nutzer zu haben.

- [ ] Prüfen, ob Auth bereits vorhanden ist
- [ ] Login-Seite verfügbar machen
- [ ] Registrierungsseite verfügbar machen
- [ ] Passwort-vergessen-Funktion vorbereiten
- [ ] Dashboard nur für eingeloggte Nutzer erreichbar machen
- [ ] Logout testen

Mögliche Laravel-Pakete:

```text
Laravel Breeze
```

Hinweis:

Breeze mit Blade ist passend zur gewählten Architektur.

---

## 10. Checkliste: Routenstruktur

Erste Routen sollen sauber getrennt werden.

Öffentlich:

- [ ] `/`
- [ ] `/rules`
- [ ] `/games/public` oder Platzhalter für öffentliche Spiele
- [ ] `/login`
- [ ] `/register`
- [ ] `/forgot-password`

Authentifiziert:

- [ ] `/dashboard`
- [ ] später `/wallet`
- [ ] später `/lobby`
- [ ] später `/games/{game}`

API/JSON testweise:

- [ ] `/api/app-status` oder vergleichbarer Test-Endpunkt

---

## 11. Checkliste: HomeServer-Fallback vorbereiten

In Phase 1 wird der HomeServer noch nicht implementiert.

Trotzdem soll die Struktur vorbereitet werden.

- [ ] Dokumentieren: Laravel ist autoritativ
- [ ] Polling als MVP-Fallback festlegen
- [ ] keine Spiellogik von externem Server abhängig machen
- [ ] spätere Realtime-Anbindung über Adapter vorsehen
- [ ] keine direkten Annahmen einbauen, dass WebSockets vorhanden sind

Optional erste Interface-Idee dokumentieren:

```text
RealtimeBroadcasterInterface
```

MVP-Verhalten:

```text
Wenn kein Realtime-Server vorhanden ist, liefern HTTP-Endpunkte den aktuellen Zustand.
```

---

## 12. Checkliste: Projektstruktur vorbereiten

Folgende Ordner können vorbereitet werden, auch wenn noch nicht alle Services gefüllt sind:

- [ ] `app/Services`
- [ ] `app/Services/Game`
- [ ] `app/Services/Wallet`
- [ ] `app/Services/Chat`
- [ ] `app/Services/Lobby`
- [ ] `app/Services/Realtime`
- [ ] `app/Services/AI`
- [ ] `resources/js/components`
- [ ] `resources/js/components/common`
- [ ] `resources/js/components/lobby`
- [ ] `resources/js/components/game`

Wichtig:

Leere Ordner werden von Git nicht versioniert. Falls nötig später mit `.gitkeep` arbeiten.

---

## 13. Checkliste: Qualitätsbasis

- [ ] `.editorconfig` prüfen
- [ ] `.gitattributes` prüfen
- [ ] Line Endings prüfen
- [ ] `composer test` oder Testkommando prüfen
- [ ] Basis-Test ausführen
- [ ] README auf aktuellen Stand prüfen
- [ ] Dokumentation verlinken

Mögliche Kommandos:

```bash
php artisan test
composer test
```

---

## 14. Definition of Done für Phase 1

Phase 1 gilt als abgeschlossen, wenn:

- [ ] Laravel lokal startet
- [ ] Datenbankverbindung funktioniert
- [ ] Migrationen laufen fehlerfrei
- [ ] Vue 3 ist installiert und wird über Vite gebaut
- [ ] Eine Vue-Testkomponente wird in Blade angezeigt
- [ ] `npm run build` funktioniert
- [ ] Basislayout existiert
- [ ] Startseite existiert
- [ ] Regelseite existiert oder Platzhalter ist vorhanden
- [ ] Auth-Grundlage ist vorhanden oder bewusst als nächster Schritt markiert
- [ ] Dashboard ist geschützt erreichbar
- [ ] HomeServer-Fallback-Prinzip ist dokumentiert
- [ ] `git status` ist sauber
- [ ] Änderungen sind committed
- [ ] Änderungen sind gepusht

---

## 15. Erwartete Commits für Phase 1

Mögliche Commit-Reihenfolge:

```text
docs: add roadmap and phase 1 checklist
chore: verify laravel foundation
chore: configure vue 3 with vite
feat: add base blade layout
feat: add initial public pages
feat: add auth foundation
chore: prepare service directories
```

Die genaue Reihenfolge kann während der Umsetzung angepasst werden.

---

## 16. Nicht Bestandteil von Phase 1

Folgende Themen gehören bewusst nicht in Phase 1:

- Wallet
- Ledger
- Spielräume
- Buy-in
- Rake
- Rules Engine
- Spieltisch
- Chat
- KI
- Timebank
- Reconnect
- Zuschaueransicht
- Statistiken
- Moderation

Diese Themen folgen in späteren Phasen.

---

## 17. Kurzfassung

Phase 1 macht das Projekt technisch startklar.

Ziel ist nicht Spiellogik, sondern eine saubere Basis:

```text
Laravel läuft.
Vue läuft.
Blade-Layout steht.
Auth-Basis ist vorbereitet.
HomeServer-Fallback-Prinzip ist berücksichtigt.
Projekt ist committbar.
```
