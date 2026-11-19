# Hosting, Betrieb und Spielserver-Architektur

## Dokumentstatus

- Stand: 2026-11-19
- Status: **fachlich beschlossen**, technisch **nicht implementiert** (es
  gibt noch kein Deployment)
- Ersetzt als Zielbild: `docs/Stechen-Serverkonzept.md` (ALL-INKL Shared
  Hosting plus optionaler HomeServer) und das HomeServer-Konzept in
  `docs/HOMESERVER_FALLBACK.md`. Beide bleiben als Quelle für einzelne,
  weiterhin gültige Prinzipien erhalten (siehe Abschnitt 6).

Die Entscheidungen wurden vom Projektinhaber am 2026-11-16 (Diskussion) und
2026-11-19 (Bestätigung) getroffen.

---

## 1. Rahmenbedingungen (beschlossen)

### Ziel und Geld

- Ziel ist ein kleines Nebeneinkommen. Echtgeld ist auf Eis gelegt.
- Gespielt wird ausschließlich mit Spielgeld (St$), einem virtuellen
  Spielguthaben ohne Geldwert.
- Das Projekt gehört allein dem Projektinhaber.

### Hosting

- Spiel und Laravel-Anwendung laufen später auf einem **Hetzner-Cloud-Server**.
  Er ist auf die Firma eingetragen, der Projektinhaber ist Admin und
  Eigentümer. Der Server steht ihm für Entwicklung und Tests zur alleinigen
  Verfügung und darf frei konfiguriert werden.
- **Bis zum Release der Staging-Version wird ausschließlich lokal auf dem
  Entwicklungsrechner gearbeitet.** Der Server wird erst eingerichtet, wenn
  das Projekt für eine geschlossene Beta bereit ist.
- Dann werden **Staging und Produktion** eingerichtet; die Produktion bleibt
  zunächst leer.
- Der Hetzner-Server ist eine **Übergangslösung**, bis ein eigener Server
  bezahlbar ist.
- ALL-INKL Shared Hosting ist **kein Zielsystem** mehr.
- Ein HomeServer (Rechner beim Projektinhaber) ist **kein Baustein** mehr.

### Domains und E-Mail

Bewusste Trennung: **ALL-INKL** verwaltet Domain, Subdomain und
E-Mail-Adressen einschließlich des Mail-Speichers; **Hetzner** betreibt nur die
Anwendung.

- **Hauptdomain** für das Live-Spiel (Name noch offen).
- **Subdomain `beta.<hauptdomain>`** für die Staging-Umgebung und die
  geschlossene Beta.
- Die DNS-Einträge für Anwendung und Subdomain zeigen auf den Hetzner-Server,
  die Mail-Einträge bleiben bei ALL-INKL.
- **Mailversand über ALL-INKL**, und zwar schon während der lokalen
  Entwicklung (echter Versand, zum Beispiel für Registrierung und
  Passwort-Reset). Die Zugangsdaten stehen nur in der lokalen `.env`.

### Lokales Testen

Mehrspieler-Tests laufen lokal. Virtuelle Mitspieler übernehmen
KI-Assistenten (ChatGPT/Codex und Claude Code als Windows-Apps), die
eigene Testkonten im Browser bedienen.

### Echtzeit und Rechenleistung

- Ziel ist, dass **alle** Aktualisierungen in Echtzeit über den
  Node.js-Spielserver laufen, auch Lobby und Status. Die heutigen Abstände
  (Lobby-Polling alle 3 Sekunden, Startprüfung des Schedulers alle 2 Sekunden)
  entfallen dann.
- Unverändert bleibt der bewusste **Countdown vor dem Spielstart**
  (`config/game_rooms.php`, `start_countdown_seconds`).
- Der Server soll **mehrere CPU-Kerne nutzen** (erwartet werden 4 Kerne; zu
  prüfen bei der Server-Inventur). Node.js arbeitet pro Prozess mit einem
  Haupt-Thread; die Verteilung auf Kerne (mehrere Prozesse oder Worker
  Threads, zum Beispiel getrennt nach Spieltischen) wird beim Entwurf des
  Spielservers festgelegt. PHP-FPM verteilt Laravel-Anfragen ohnehin auf
  mehrere Prozesse.

### Umgebungen

Auf dem Server laufen später zwei vollständig getrennte Umgebungen:

| | Produktion | Staging |
|---|---|---|
| Adresse | `<hauptdomain>` | `beta.<hauptdomain>` |
| `APP_ENV` | `production` | `staging` |
| Datenbank | eigene | eigene |
| `.env` | eigene | eigene |
| Scheduler, Queue-Worker | eigene | eigene |
| Node.js-Spielserver | eigener Prozess | eigener Prozess |

Da `APP_ENV` dort nicht `local` ist, sind auf beiden Umgebungen alle
Dev-Elemente ausgeblendet (siehe `docs/FRONTEND_VUE_ISLANDS.md`, Abschnitt
Dev-Modus).

### Zugang und Deployment

- Ein SSH-Zugang zum Hetzner-Server besteht.
- **Zugangsdaten, Hostnamen, Benutzer und Schlüssel stehen nie in der
  Dokumentation oder im Repository.**
- Deploys sollen automatisiert laufen, auch durch den KI-Assistenten:
  - **Staging:** eine Freigabe des Projektinhabers pro Deploy-Runde.
  - **Produktion:** ausdrückliche Bestätigung vor **jedem** Deploy.
- Deployment ist ein kritisches Thema im Sinne der Entwicklungsanweisung:
  Analyse, Auswirkungen, Dokumentation und Bestätigung vor der Umsetzung.

### Spielserver-Architektur (Variante B)

```text
                 Browser
                 |      \
   HTTP/Polling  |       \  WebSocket (laufendes Spiel, später Chat/Presence)
                 v        v
  +------------------+   +-------------------------+
  | Laravel          |   | Node.js-Spielserver     |
  | Konten, Lobby,   |-->| führt das laufende      |
  | Buy-in, Wallet,  |   | Spiel (GameEngine),     |
  | Settlement       |<--| Ereignisprotokoll       |
  +------------------+   +-------------------------+
        |   Start-Übergabe / Ergebnis-Rückmeldung:
        |   signiert und idempotent
        v
     MariaDB
```

- **Laravel** übernimmt alles **vor** dem Spielstart (Konten, Lobby,
  Buy-in-Reservierung und -Abbuchung, Rake) und **nach** dem Spielende
  (Settlement, Auswertung). **Geld bucht ausschließlich Laravel.**
- **Node.js** führt das **laufende Spiel**: GameEngine nach dem Vertrag in
  `docs/reset_dokumentation/09_GAMEENGINE_VORBEREITUNG.md`, Echtzeit über
  WebSocket, jede Aktion wird als Ereignis gespeichert, damit ein Absturz
  wiederhergestellt werden kann.
- Übergabe Laravel → Node (Spielstart) und Node → Laravel (Ergebnis) laufen
  über einen signierten internen Aufruf, der bei Wiederholung nichts doppelt
  ausführt.
- Lobby-Chat und Presence kommen später über dieselbe Echtzeit-Schiene.
- **Reihenfolge:** Die Umsetzung beginnt erst, wenn das „Drumherum“
  (Lobby, Spielräume, Start, Wallet, Settlement-Grundlagen) einwandfrei läuft.

---

## 2. Vorgeschlagenes Server-Zielbild (offen, nicht beschlossen)

Vorschlag als Diskussionsgrundlage für die spätere Server-Einrichtung:

- Webserver mit PHP-FPM für beide Laravel-Umgebungen, TLS-Zertifikate per
  Let's Encrypt.
- Laravel-Scheduler per Cron oder systemd-Timer, Queue-Worker als
  Systemdienst.
- Node.js-Spielserver je Umgebung als Systemdienst hinter dem Webserver
  (WebSocket über TLS).
- MariaDB auf demselben Server, nur lokal erreichbar.
- Regelmäßige Backups, mindestens eine Kopie außerhalb von Hetzner beim
  Projektinhaber.
- Servereinrichtung reproduzierbar dokumentiert, damit der spätere Umzug auf
  einen eigenen Server ohne Wissensverlust möglich ist.

---

## 3. Lokale Abbildung (wie wir es auf dem Entwicklungsrechner lösen)

Die Entwicklung läuft unter Windows. Vorhanden sind PHP, Composer, MariaDB
(Windows-Dienst), Node.js 24.11, außerdem WSL mit Ubuntu 24.04 und Docker
Desktop.

| Baustein auf Hetzner | Lokal | Status |
|---|---|---|
| Webserver mit PHP-FPM | `php artisan serve` auf `127.0.0.1:8000` | implementiert (`tools/dev/start.ps1`) |
| Scheduler (Cron/systemd) | `php artisan schedule:work` | implementiert (`start.ps1`) |
| Frontend-Build | Vite-Dev-Server mit Hot Reload | implementiert (`start.ps1`) |
| MariaDB | Windows-Dienst `MariaDB` | implementiert |
| Queue-Worker | `QUEUE_CONNECTION=database`, derzeit ohne Jobs, daher ohne Worker | nicht benötigt |
| Node.js-Spielserver | Node.js 24.11 lokal; kommt als vierter Prozess in `start.ps1` | vorbereitet (Runtime vorhanden) |
| Redis | nicht im Einsatz, siehe unten | offen |
| Domain und TLS | `127.0.0.1` ohne TLS | bewusst nicht nachgebildet |
| Mailversand | heute `MAIL_MAILER=log`; geplant: echter Versand über ALL-INKL-SMTP | geplant (Einrichtung durch den Projektinhaber) |
| Mitspieler | KI-Assistenten als virtuelle Spieler mit Testkonten | geplant |
| Linux-Server-Umgebung | WSL Ubuntu 24.04 für Tests von Setup- und Deploy-Skripten | vorbereitet |

### Redis

- Die Anwendung braucht Redis derzeit nicht: Cache, Queue und Session laufen
  über die Datenbank (`CACHE_STORE`, `QUEUE_CONNECTION`, `SESSION_DRIVER` =
  `database`).
- Laravel kapselt diese Treiber. Ein späterer Wechsel auf Redis ist eine
  Konfigurationsänderung in der `.env`, keine Codeänderung.
- Wird Redis nötig (zum Beispiel für den Node.js-Spielserver oder für Last),
  lässt es sich lokal genauso betreiben wie auf dem Server:
  - als Docker-Container (Docker Desktop ist vorhanden) oder
  - als `redis-server` in WSL Ubuntu 24.04.
- PHP-Anbindung unter Windows: Die PHP-Erweiterung `phpredis` ist lokal nicht
  installiert. Alternative wäre die reine PHP-Bibliothek `predis`. Auf dem
  Server wäre `phpredis` die übliche Wahl. Die Entscheidung fällt erst, wenn
  Redis tatsächlich gebraucht wird.

### Staging-Verhalten lokal prüfen

Wie die Seite auf Staging oder Live aussieht (ohne Dev-Elemente, ohne
`/vue-test`), lässt sich lokal prüfen, indem `APP_ENV` in der lokalen `.env`
vorübergehend auf `staging` gesetzt wird. Danach wieder auf `local`
zurückstellen.

---

## 4. Offene Entscheidungen

| Thema | Frage |
|---|---|
| Datenschutz-Verantwortung | Geklärt ist: Der Server ist auf die Firma eingetragen, der Projektinhaber ist Admin. Vor dem **öffentlichen** Betrieb (nicht für lokale Tests) zu klären: Wer ist Verantwortlicher im Sinne der DSGVO, und deckt der Vertrag mit Hetzner (Auftragsverarbeitung) den Betrieb des privaten Projekts ab? |
| Domainname | Welche Hauptdomain? |
| Server-Stack | Webserver, Prozessverwaltung (systemd oder PM2) und Node.js-Version auf dem Server; Grundlage ist die Server-Inventur |
| Mehrkern-Nutzung | Aufteilung des Node.js-Spielservers auf mehrere Prozesse oder Worker Threads |
| Polling als Rückfallebene | Bleibt Polling als Notlösung erhalten, wenn die Echtzeit-Verbindung abbricht, oder entfällt es ganz? |
| Ordner des Spielservers | Bisher ist `homeserver/` im Repository reserviert. Umbenennen (zum Beispiel `game-server/`)? |
| Redis | Ob und ab wann; siehe Abschnitt 3 |
| Backups | Rhythmus, Aufbewahrung, externer Speicherort |
| Monetarisierung | Werbung und Spielgeld-Käufe sind denkbar, aber nicht entschieden |
| Disconnect-Zuständigkeit | Bisher gilt „Laravel entscheidet über Disconnect-Konsequenzen“. Bei Variante B läuft das Spiel im Node.js-Spielserver. **Wird beim Entwurf der Spielengine geklärt** (Entscheidung Projektinhaber 2026-11-19). |
| Vor öffentlichem Betrieb | Impressum, ausgearbeitete AGB und Datenschutzerklärung (Rechtstexte sind derzeit Platzhalter) |

---

## 5. Auswirkungen auf bestehende Grundsätze

- „Laravel bleibt die autoritative Instanz“ gilt weiterhin für Konten, Geld,
  Lobby und Spielräume. Für den **laufenden Spielzustand** ist künftig der
  Node.js-Spielserver maßgeblich.
- „Der HomeServer darf keine verbindlichen Entscheidungen treffen“ und „Die
  Anwendung muss bei Ausfall des HomeServers weiter funktionieren“ entfallen
  in dieser Form. Node.js ist kein optionaler Beschleuniger mehr, sondern ein
  Kernbestandteil auf demselben Server.
- Ziel ist Echtzeit für alle Aktualisierungen. Ob Polling als Notlösung
  erhalten bleibt, ist offen (Abschnitt 4).

---

## 6. Was aus den bisherigen Konzepten weiter gilt

Aus `docs/Stechen-Serverkonzept.md` und `docs/HOMESERVER_FALLBACK.md` bleiben
als Prinzipien gültig:

- Spielaktionen sind idempotent.
- Broadcasts dürfen Laravel nicht blockieren.
- Polling über Laravel bleibt als Rückfallebene erhalten.
- Kapazitätsgrenzen für gleichzeitig laufende Spiele werden bewusst gesetzt.

Nicht mehr gültig sind dort insbesondere: ALL-INKL als Zielplattform, der
HomeServer als optionaler Realtime-Beschleuniger und die Betriebsmodi
`degraded`/`all_inkl_only`/`recovering`.
