
# Lobby

## Dokumentstatus

**Reset-Status:** analysiert und dokumentiert
**Implementierungsstatus:** teilweise implementiert / teilweise vorbereitet

---

# 1. Zweck

Die Lobby ist der zentrale MMO-Hub von stechen-mmo.

Sie verbindet Benutzer, Wallet, Rewards und Spielräume und bildet den
Übergang zwischen Accountverwaltung und eigentlichem Spiel.

Die Lobby ist ausdrücklich **nicht** nur eine Raumliste.

---

# 2. Fachliche Rolle

Die Lobby übernimmt aktuell insbesondere:

- Darstellung verfügbarer Räume
- Filterung und Auswahl
- Join- und Leave-Aktionen
- Anzeige eigener Raumteilnahmen
- Übergabe an die Spielraum-Domäne

Sie bildet die Orchestrierungsschicht zwischen Frontend und den
GameRoom-Services.

---

# 3. Beteiligte Komponenten

## Controller

- LobbyController
- LobbyRoomsController
- LobbyRoomJoinController
- LobbyRoomLeaveController

## Lobby-Services

- LobbyRoomQueryService
- LobbyRoomBrowserPayloadService

## GameRoom-Services

- GameRoomEligibilityService
- GameRoomJoinService
- GameRoomLeaveService

## Modelle

- GameRoom
- GameRoomPlayer

## Frontend

- resources/views/lobby/index.blade.php
- LobbyRoomBrowser.vue

---

# 4. Implementierte Architektur

Der aktuelle Build verwendet eine mehrstufige Architektur.

```text
Blade
    ↓
Vue Room Browser
    ↓
Lobby Controller
    ↓
Payload Service
    ↓
Query Service
    ↓
GameRoom Services
    ↓
Wallet / Ledger
```

Die Raumliste wird nicht direkt im Blade-Template erzeugt.
Vue erhält ein vorbereitetes Payload.

---

# 5. Implementierter Ist-Stand

Bestätigt sind u.a.:

- Vue-Island für die Lobby
- Anzeige des authentifizierten Spielernamens im gemeinsamen Lobby-Header
- persistente benutzerspezifische Lobbyfilter über `user_preferences`
- Payload-API
- Query-Service
- Filter und Kategorien
- ausgewählter Raum
- Join
- Leave
- Eligibility-Prüfung
- Wallet-Integration
- Datenbanktransaktionen
- Sperren bei kritischen Änderungen
- Polling-Unterstützung
- Phase-3-Testmodus

---

# 6. Fachliche Grundregeln

Bereits fachlich beschlossen sind:

- Laravel bleibt autoritativ.
- Echtzeit für alle Lobby-Aktualisierungen, Chat und Presence kommt später
  über den Node.js-Spielserver (Ziel 2026-11-19); ob Polling als Notlösung
  bleibt, ist offen.
- Gäste dürfen sehen, was in der Lobby passiert (Tische und ihr Zustand), aber
  nicht den Chat; der Chat steht nur eingeloggten Spielern zur Verfügung
  (bestätigt 2026-11-19). **Ist-Stand im Code:** `/lobby` verlangt Login und
  bestätigte E-Mail-Adresse; Gäste werden zum Login umgeleitet.
- Sichtbarkeit ist von Betretbarkeit getrennt.
- Spieler dürfen mehrere wartende Raumreservierungen besitzen.
- Pro Spieler darf nur ein gestartetes Spiel aktiv sein.
- Buy-ins werden beim Join reserviert.
- Beim Start des führenden Raums werden andere Reservierungen freigegeben.

Diese Regeln sind teilweise bereits umgesetzt und teilweise Grundlage
für spätere Entwicklungsphasen.

Abgleich mit dem Code (2026-11-16):

- Implementiert: mehrere wartende Reservierungen (maximal 3),
  Buy-in-Reservierung beim Join, kein Join bei laufender Teilnahme
  (`playing`) in einem anderen Raum.
- Nicht implementiert: Freigabe anderer Reservierungen beim Start des
  führenden Raums und die Absicherung „nur ein gestartetes Spiel“ beim
  Start. `GameRoomStartCoordinatorService` finalisiert einen Raum, ohne
  andere Teilnahmen desselben Spielers zu prüfen. Siehe
  `99_OFFENE_PUNKTE.md`.

---

# 7. Eligibility

Eligibility ist bewusst von Join getrennt.

Der Eligibility-Service entscheidet fachlich, ob ein Spieler einen Raum
betreten darf.

Der Join-Service führt die eigentliche Operation transaktional aus.

Diese Trennung erlaubt dieselbe Eligibility-Logik für:

- UI
- API
- Auto-Join
- Diagnose
- zukünftige Matchmaking-Funktionen

---

# 8. Join und Leave

Join und Leave besitzen eigene Controller und Services.

Die Geschäftslogik befindet sich ausschließlich in den Services.

Controller übernehmen lediglich Request, Berechtigungen und Antwort.

---

# 9. Polling und Realtime

Die Lobby ist bereits auf regelmäßige Aktualisierung vorbereitet.

Polling ist implementiert.

Echtzeit über den geplanten Node.js-Spielserver (WebSocket) folgt später,
siehe `docs/HOSTING_UND_BETRIEB.md`.

Laravel bleibt weiterhin die Quelle der Wahrheit.

---

# 10. Vorbereitete Funktionen

Im aktuellen Stand vorbereitet oder geplant:

- Chat
- Presence
- Realtime
- Benachrichtigungen
- Matchmaking-nahe Funktionen
- Auto-Join
- Favoriten
- erweiterte Supply-Regeln

---

# 11. Abgrenzung

Nicht Bestandteil dieses Dokuments:

- vollständiger Raum-Lifecycle
- Supply
- StartCoordinator
- Cleanup
- Finish
- Rake
- Spielzustände
- Settlement

Diese Bereiche werden in späteren Reset-Dokumenten behandelt.

---

# 12. Statusübersicht

| Bereich | Status |
|---|---|
| Lobby-UI | Implementiert |
| Vue-Island | Implementiert |
| Query-Service | Implementiert |
| Payload-Service | Implementiert |
| Eligibility | Implementiert |
| Join | Implementiert |
| Leave | Implementiert |
| Polling | Implementiert |
| Persistente Lobbyfilter | Implementiert |
| Lobby-Layout nach Zielbild v3 (2026-11-19) | Implementiert, ohne Leiste „Deine Tische“ |
| Dev-Only-Elemente (`APP_ENV=local`) | Implementiert |
| Echtzeit über Node.js-Spielserver | Fachlich beschlossen |
| Chat | Fachlich beschlossen |
| Presence | Vorbereitet |
| Matchmaking | Fachlich beschlossen |

---

# 13. Definition of Done

Details zur Oberfläche: `docs/lobby-room-browser-vue-state.md`.

Dieses Dokument beschreibt den aktuellen Lobby-Stand anhand der
tatsächlichen Implementierung und trennt klar zwischen:

- implementiert,
- vorbereitet,
- fachlich beschlossen,
- späteren Entwicklungsphasen.

Die Lobby wird damit als eigenständige MMO-Domäne dokumentiert und nicht
nur als Raumliste verstanden.
