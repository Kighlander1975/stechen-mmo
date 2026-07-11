
# Spielräume

## Dokumentstatus

**Reset-Status:** analysiert und dokumentiert
**Implementierungsstatus:** teilweise implementiert / teilweise vorbereitet

---

# 1. Zweck

Dieses Dokument beschreibt die fachliche Domäne der Spielräume bis unmittelbar vor dem eigentlichen Spielbeginn.

Der Übergang in den Spielablauf beginnt erst mit dem Status `starting` und wird im Reset-Baustein `06_SPIELZYKLUS.md` behandelt.

---

# 2. Fachliche Abgrenzung

Dieses Dokument umfasst insbesondere:

- Raumarten
- Raumverwaltung
- Raumangebot
- Supply
- Status bis vor `starting`
- Teilnehmerverwaltung
- Cancellation
- Cleanup
- systemseitige Raumverwaltung

Nicht Bestandteil:

- StartCoordinator
- Countdown
- Konfliktauflösung
- GameEngine
- Running
- Finish
- Settlement

---

# 3. Beteiligte Komponenten

## Models

- GameRoom
- GameRoomPlayer

## Services

- GameRoomSupplyService
- GameRoomCancellationService
- GameRoomCleanupService

## Angrenzende Services

- GameRoomJoinService
- GameRoomLeaveService
- GameRoomEligibilityService

Diese werden in `04_LOBBY.md` beschrieben.

---

# 4. Raumarten

Aktuell sind zwei Raumarten vorgesehen:

- Sit'n'Go
- Scheduled

Spieler erzeugen keine eigenen Räume.

Alle Räume werden systemseitig bereitgestellt.

---

# 5. Raumversorgung

Der `GameRoomSupplyService` ist für das Raumangebot verantwortlich.

Er entscheidet unter anderem:

- welche Räume existieren,
- welche Buy-ins angeboten werden,
- welche Spielerzahlen bereitstehen,
- wann Ersatzräume erzeugt werden,
- wie viele Scheduled Rooms sichtbar sind.

Supply entscheidet **nicht**, ob ein bestimmter Spieler beitreten darf.

Diese Entscheidung verbleibt beim Eligibility-Service.

---

# 6. Raumstatus

Vor dem eigentlichen Spielzyklus befinden sich Räume typischerweise in Zuständen wie:

- draft
- open
- full
- cancelled

Der Status `starting` markiert den Beginn des Spielzyklus und gehört nicht mehr zu diesem Dokument.

---

# 7. Teilnehmermodell

Ein Raum besitzt Teilnehmer über `GameRoomPlayer`.

Vor Spielbeginn können Teilnehmer:

- beitreten,
- warten,
- den Raum freiwillig verlassen,
- durch Cancellation entfernt werden.

Die fachliche Join-/Leave-Logik ist bereits separat dokumentiert.

---

# 8. Cancellation

Der `GameRoomCancellationService` behandelt den Abbruch wartender Räume.

Dabei werden insbesondere:

- Reservierungen freigegeben,
- Ledger-Historie erhalten,
- wartende Teilnehmer entfernt,
- der Raum auf `cancelled` gesetzt.

Laufende Spiele werden ausdrücklich nicht verändert.

---

# 9. Cleanup

Cancelled Rooms sind operative Kurzzeitinformationen.

Der `GameRoomCleanupService` bereitet das spätere Entfernen solcher Räume vor.

Dabei gelten u. a.:

- keine laufenden Räume löschen,
- keine Ledger-Daten löschen,
- nur sichere Löschkandidaten berücksichtigen.

---

# 10. Fachliche Architektur

```text
System
    │
    ▼
GameRoomSupplyService
    │
    ▼
GameRoom
    │
 ┌──┴────────────┐
 ▼               ▼
Join/Leave   Cancellation
                  │
                  ▼
             Cleanup

ab Status "starting"
        ↓
06_SPIELZYKLUS.md
```

---

# 11. Implementierter Ist-Stand

Bestätigt sind:

- systemseitige Raumerzeugung
- Sit'n'Go und Scheduled
- getrennte Supply-Logik
- GameRoom-Modell
- GameRoomPlayer-Modell
- Cancellation-Service
- Cleanup-Service
- klare Trennung zwischen Raumverwaltung und Spielstart

---

# 12. Fachlich beschlossen

Bereits entschieden, aber teilweise noch nicht vollständig umgesetzt:

- dynamische Raumversorgung
- Gäste dürfen öffentliche Räume sehen
- Sichtbarkeit und Betretbarkeit sind getrennt
- mehrere wartende Raumreservierungen pro Spieler
- ein aktives Spiel pro Spieler
- spätere Favoriten- und Auto-Join-Funktionen

---

# 13. Offene Umsetzung

Für spätere Phasen vorgesehen:

- weitere Supply-Regeln
- Admin-Steuerung
- Optimierungen für Scheduler und Queue
- erweiterte Diagnosefunktionen

---

# 14. Definition of Done

Dieser Reset-Baustein beschreibt die Spielraum-Domäne bis unmittelbar vor dem Status `starting`.

Der eigentliche Spielbeginn sowie alle Abläufe ab `starting` werden vollständig in `06_SPIELZYKLUS.md` dokumentiert.
