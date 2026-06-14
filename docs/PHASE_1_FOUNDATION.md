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
- [x] `APP_KEY` ist gesetzt
- [x] PHP-Version passt zu Laravel-Version
- [x] Composer-Abhängigkeiten sind installiert
- [x] Node-Abhängigkeiten sind installiert
- [x] Datenbank ist lokal angelegt
- [x] Datenbankzugang in `.env` ist korrekt
- [x] `php artisan` funktioniert
- [x] Laravel-Startseite ist erreichbar

---

## 5. Checkliste: Backend-Grundlage

- [x] Laravel-Version prüfen
- [x] `.env.example` prüfen und bei Bedarf aktualisieren
- [x] Datenbankverbindung testen
- [x] Migrationen ausführen
- [x] Cache-Kommandos testen
- [x] Lokalen Development-Server starten
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

- [x] `package.json` prüfen
- [x] Vite-Konfiguration prüfen
- [x] Vue 3 installieren, falls noch nicht vorhanden
- [x] Frontend-Abhängigkeiten installieren
- [x] Development-Build starten
- [x] Production-Build testen
- [x] Vue-Testkomponente erstellen
- [x] Vue-Testkomponente in Blade einbinden
- [x] Prüfen, ob Hot Reload lokal funktioniert

Nützliche Kommandos:

```bash
npm install
npm run dev
npm run build
```

---

## 7. Checkliste: Vue 3 Integration

- [x] `resources/js/app.js` oder `resources/js/app.ts` prüfen
- [x] Vue-App initialisieren
- [x] Erste Vue-Komponente erstellen
- [x] Mount-Punkt im Blade-Layout ergänzen
- [x] Datenübergabe von Blade zu Vue testen
- [x] JSON-Endpoint testweise abrufen
- [x] Fehlerausgabe im Browser prüfen

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

- [x] Hauptlayout erstellen oder prüfen
- [x] Navigation anlegen
- [x] Startseite anlegen
- [x] Regeln-Link anlegen
- [ ] Login/Register Links vorsehen
- [ ] Dashboard-Link für eingeloggte Nutzer vorsehen
- [ ] Bereich für Flash-Messages vorsehen
- [x] Bereich für Vue-Inseln vorsehen

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

- [x] `/`
- [x] `/rules`
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

- [x] `/api/app-status` oder vergleichbarer Test-Endpunkt

---

## 11. Checkliste: HomeServer-Fallback vorbereiten

In Phase 1 wird der HomeServer noch nicht implementiert.

Trotzdem soll die Struktur vorbereitet werden.

- [x] Dokumentieren: Laravel ist autoritativ
- [x] Polling als MVP-Fallback festlegen
- [x] keine Spiellogik von externem Server abhängig machen
- [x] spätere Realtime-Anbindung über Adapter vorsehen
- [x] keine direkten Annahmen einbauen, dass WebSockets vorhanden sind

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

- [x] `app/Services`
- [x] `app/Services/Game`
- [x] `app/Services/Wallet`
- [x] `app/Services/Chat`
- [x] `app/Services/Lobby`
- [x] `app/Services/Realtime`
- [x] `app/Services/AI`
- [x] `resources/js/components`
- [x] `resources/js/components/common`
- [x] `resources/js/components/lobby`
- [x] `resources/js/components/game`

Wichtig:

Leere Ordner werden von Git nicht versioniert. Falls nötig später mit `.gitkeep` arbeiten.

---

## 13. Checkliste: Qualitätsbasis

- [x] `.editorconfig` prüfen
- [x] `.gitattributes` prüfen
- [x] Line Endings prüfen
- [x] `composer test` oder Testkommando prüfen
- [x] Basis-Test ausführen
- [x] README auf aktuellen Stand prüfen
- [x] Dokumentation verlinken

Mögliche Kommandos:

```bash
php artisan test
composer test
```

---

## 14. Definition of Done für Phase 1

Phase 1 gilt als abgeschlossen, wenn:

- [x] Laravel lokal startet
- [x] Datenbankverbindung funktioniert
- [x] Migrationen laufen fehlerfrei
- [x] Vue 3 ist installiert und wird über Vite gebaut
- [x] Eine Vue-Testkomponente wird in Blade angezeigt
- [x] `npm run build` funktioniert
- [x] Basislayout existiert
- [x] Startseite existiert
- [x] Regelseite existiert oder Platzhalter ist vorhanden
- [ ] Auth-Grundlage ist vorhanden oder bewusst als nächster Schritt markiert
- [ ] Dashboard ist geschützt erreichbar
- [x] HomeServer-Fallback-Prinzip ist dokumentiert
- [x] `git status` ist sauber
- [x] Änderungen sind committed
- [x] Änderungen sind gepusht

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




