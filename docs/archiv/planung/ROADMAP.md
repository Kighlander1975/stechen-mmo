ROADMAP_and_PHASE_1_FOUNDATION.md

# Datei: docs/ROADMAP.md

# stechen-mmo — Globale Roadmap

Stand: Juni 2026  
Status: Erste technische Roadmap

---

## 1. Ziel dieses Dokuments

Dieses Dokument beschreibt die globale technische Roadmap für `stechen-mmo`.

Grundlage ist das MVP-Konzept aus:

```text
docs/MVP_CONCEPT.md
```

Die Roadmap dient als Orientierung für die schrittweise Umsetzung des Projekts in Laravel mit Vue 3.

---

## 2. Technische Grundentscheidung

Das Projekt startet als webbasierte Laravel-Anwendung.

### Backend

```text
Laravel 11/12
MySQL/MariaDB
Shared Hosting kompatibel
```

Laravel ist die autoritative Instanz für:

- Authentifizierung
- Spielregeln
- Game-State
- Wallet
- Ledger
- Buy-ins
- Rake
- Settlement
- Statistiken
- Persistenz
- Auditierbarkeit

### Frontend

```text
Blade + Vue 3 Inseln + Vite
```

Das MVP wird nicht als vollständige SPA umgesetzt.

Stattdessen rendert Laravel klassische Seiten mit Blade. Interaktive Bereiche werden als Vue-Komponenten eingebunden.

Vue-Inseln werden insbesondere genutzt für:

- Lobby
- Chat
- Spieltisch
- Timer
- Vorauswahl
- Zuschaueransicht
- Wallet-Anzeige
- Reconnect-Status

---

## 3. Warum Blade + Vue-Inseln?

Diese Architektur passt zum MVP, weil:

- Shared Hosting unterstützt wird
- klassische Laravel-Auth einfach bleibt
- Regelseiten, Login und Statistiken unkompliziert sind
- Vue nur dort eingesetzt wird, wo Interaktivität nötig ist
- Polling zunächst einfach umsetzbar ist
- spätere Erweiterung möglich bleibt

Eine vollständige SPA wird für das MVP bewusst vermieden.

---

## 4. Langfristige Vision

In ferner Zukunft kann das Projekt technisch weiterentwickelt werden.

Denkbar wäre:

- echte Server-App
- eigenständiger Realtime-Server
- Desktop-Clients
- Mobile-Clients
- native Apps
- PWA
- API-first Architektur

Diese Umstellung ist nur relevant, falls das Projekt stark wächst.

Für das MVP gilt:

```text
Web zuerst.
Laravel bleibt autoritativ.
Vue ergänzt interaktive Bereiche.
```

---

## 5. HomeServer-Strategie

Das Projekt soll so entwickelt werden, dass ein HomeServer später leicht eingebunden werden kann.

Dabei gilt:

```text
Laravel = Quelle der Wahrheit
HomeServer = optionaler Realtime-/Worker-Adapter
```

Der HomeServer darf im MVP nicht zwingend erforderlich sein.

Wenn der HomeServer offline ist:

- Laravel funktioniert weiter
- Polling übernimmt
- Spielaktionen laufen über Laravel
- Game-State liegt in Laravel/MySQL
- Ledger liegt in Laravel/MySQL
- Settlement erfolgt über Laravel
- Nutzer können weiter spielen

Wenn der HomeServer online ist:

- Realtime-Updates können schneller verteilt werden
- Timer-Events können komfortabler gepusht werden
- Chat kann live aktualisiert werden
- Spielstatus kann per Push aktualisiert werden
- Polling kann reduziert werden

Der HomeServer darf keine alleinige Autorität über Spielstände oder Wallets erhalten.

---

## 6. Architekturprinzip: Adapter statt Refactor

Damit der HomeServer später eingebunden werden kann, sollen relevante Systemteile über Services und Adapter abstrahiert werden.

Beispiele:

```text
RealtimeBroadcasterInterface
PollingStateProvider
GameActionService
TimerService
ChatDeliveryService
```

MVP-Implementierung:

```text
Polling
HTTP Requests
Laravel Controller
Laravel Services
```

Spätere Implementierung:

```text
WebSockets
Laravel Reverb
HomeServer Bridge
Push Events
Worker
```

Ziel:

```text
Später erweitern, nicht komplett neu bauen.
```

---

## 7. Globale Umsetzungsphasen

## Phase 1: Foundation

Ziel:

Laravel-Projekt technisch stabilisieren, Vue 3 einrichten, Basislayout und Auth-Grundlagen herstellen.

Ergebnis:

```text
Projekt ist lokal lauffähig, Frontend-Build funktioniert, Basislayout steht.
```

Details:

```text
docs/PHASE_1_FOUNDATION.md
```

---

## Phase 2: Rollen, Gäste und Zugriff

Ziel:

Besucher, Gäste mit Nickname und eingeloggte Nutzer sauber trennen.

Inhalte:

- Gastnickname-System
- Namensprüfung
- reservierte Namen
- Gast-Aktivität
- Zugriffsrechte
- Middleware/Policies
- Grundlagen für Zuschauerrechte

Ergebnis:

```text
Gäste können eingeschränkt teilnehmen, registrierte Nutzer erhalten erweiterte Rechte.
```

---

## Phase 3: Wallet und Ledger

Ziel:

Spielgeld sauber und nachvollziehbar verwalten.

Inhalte:

- Wallets
- Ledger-Einträge
- Startguthaben
- aktive tägliche Gutschrift
- Admin-/Rake-Wallet
- Buchungstypen
- Transaktionsservices

Ergebnis:

```text
Nutzer besitzen Spielgeld, alle Bewegungen sind auditierbar.
```

---

## Phase 4: Spielräume

Ziel:

Räume erstellen, anzeigen, betreten und verlassen können.

Inhalte:

- GameRoom
- GameRoomSeat
- Buy-in reservieren
- Leave vor Spielstart
- Raumstatus
- automatische Raumvorlagen
- kein Multitabling

Ergebnis:

```text
Nutzer können Spielräume betreten und vor Spielstart wieder verlassen.
```

---

## Phase 5: Game-State und Persistenz

Ziel:

Laufende Spiele persistent modellieren.

Inhalte:

- Game
- GamePlayer
- GameRound
- GameTurn
- GameCard
- GameEvent
- öffentliche/private State-Ausgabe
- serverseitiges Mischen
- Kartenverteilung

Ergebnis:

```text
Aus einem Raum kann ein persistentes Spiel entstehen.
```

---

## Phase 6: Rules Engine

Ziel:

Eine vollständige Partie serverseitig regelkonform abbilden.

Inhalte:

- gültige Aktionen
- Kartenlogik
- Tipps
- Stiche
- Rundenwertung
- Punkteberechnung
- Spielende
- Platzierung
- Tiebreaker

Ergebnis:

```text
Eine Partie kann vollständig serverseitig gespielt und beendet werden.
```

---

## Phase 7: Spieltisch mit Vue

Ziel:

Spieloberfläche interaktiv nutzbar machen.

Inhalte:

- GameTable.vue
- PlayerHand.vue
- PlayedStack.vue
- TurnTimer.vue
- PreselectCard.vue
- GameLog.vue
- Polling
- Aktionen per JSON-Endpunkt

Ergebnis:

```text
Nutzer können über den Browser aktiv spielen.
```

---

## Phase 8: Chat-System

Ziel:

Globale Lobby und Tisch-Chat sauber trennen.

Inhalte:

- Lobby-Chat
- Tisch-Chat
- Gastchat
- Rate Limits
- Linkfilter
- HTML escaping
- keine Tisch-Chat-Einsicht für Zuschauer

Ergebnis:

```text
Chat funktioniert mit klaren Rechten und Basisschutz.
```

---

## Phase 9: Timebank, Inaktivität und Reconnect

Ziel:

Spiele dürfen durch inaktive Spieler nicht blockieren.

Inhalte:

- Zugzeit
- individuelle Timebank
- Inaktivitätsstatus
- Button "Bin zurück"
- Reconnect
- Vorauswahl löschen bei Reconnect
- automatische gültige Züge

Ergebnis:

```text
Inaktive Spieler blockieren keine laufenden Spiele.
```

---

## Phase 10: KI-Spieler für Entwicklung und Training

Ziel:

Spiele ohne genügend echte Spieler testen können.

Inhalte:

- einfache regelkonforme KI
- KI-Stufen vorbereiten
- KI nur für Entwicklung/Training
- keine KI von Anfang an in Wertspielen
- klare KI-Kennzeichnung

Ergebnis:

```text
Lokale Tests und kostenlose Übungsspiele mit KI sind möglich.
```

---

## Phase 11: Settlement, Rake und Preisverteilung

Ziel:

Spielende erzeugt korrekte Auszahlungen und Statistiken.

Inhalte:

- Preispool
- Rake
- Auszahlung
- Ledger-Buchungen
- Platzierungen
- Tiebreaker
- KI-/Disconnect-Regeln
- Spielstatistik

Ergebnis:

```text
Nach Spielende werden Ergebnisse und Spielgeld korrekt verarbeitet.
```

---

## Phase 12: Zuschauer und öffentliche Statistiken

Ziel:

Spiele beobachtbar und Ergebnisse vergleichbar machen.

Inhalte:

- Zuschaueransicht
- öffentliche State-Ausgabe
- keine verdeckten Karten
- keine Tisch-Chat-Einsicht
- Spielersuche
- öffentliche Statistikseiten

Ergebnis:

```text
Gäste und Nutzer können Spiele passiv beobachten und Statistiken einsehen.
```

---

## Phase 13: Moderation und Hardening

Ziel:

Mindestschutz gegen Missbrauch schaffen.

Inhalte:

- Mute
- Ban
- Chat-Sperren
- Rate Limits
- reservierte Namen
- Abuse-Logs
- Adminansicht
- Audit-Review

Ergebnis:

```text
MVP ist robuster gegen Spam, Missbrauch und offensichtliche Manipulation.
```

---

## 8. Commit-Strategie

Änderungen sollen in kleinen, nachvollziehbaren Schritten committet werden.

Beispielhafte Commit-Reihenfolge:

```text
docs: add implementation roadmap
chore: configure vue frontend
feat: add base layout
feat: add auth scaffolding
feat: add guest identity model
feat: add wallet ledger models
feat: add game room models
feat: add game state models
feat: add initial rules engine
```

---

## 9. Grundregel für alle Phasen

Jede Phase soll lauffähig, testbar und committbar abgeschlossen werden.

Keine Phase soll darauf angewiesen sein, dass eine spätere Phase bereits existiert.

---

## 10. Kurzfassung

`stechen-mmo` startet als Laravel-Webanwendung mit Blade und Vue 3 Inseln.

Laravel bleibt immer autoritativ.

Der HomeServer wird architektonisch vorbereitet, aber nicht vorausgesetzt.

Das MVP wird schrittweise aufgebaut:

```text
Foundation
→ Rollen/Gäste
→ Wallet/Ledger
→ Räume
→ Game-State
→ Rules Engine
→ Vue-Spieltisch
→ Chat
→ Timebank/Reconnect
→ KI
→ Settlement
→ Zuschauer/Statistiken
→ Moderation/Hardening
```

---

---
