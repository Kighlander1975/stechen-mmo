
# Spielzyklus

## Dokumentstatus

**Reset-Status:** analysiert und dokumentiert
**Implementierungsstatus:** technischer Rahmen implementiert, GameEngine noch nicht implementiert

---

# 1. Zweck

Dieses Dokument beschreibt die Spielorchestrierung vom Übergang eines
Spielraums in den Status `starting` bis zur Übergabe an das Settlement.

Die eigentliche GameEngine ist bewusst noch kein Bestandteil des
aktuellen Builds.

---

# 2. Fachliche Abgrenzung

Bestandteil:

- StartCoordinator
- Countdown
- Startfinalisierung
- Buy-in-Commit
- Rake-Buchung
- Spielstatus
- Finish-Harness
- Übergang zum Settlement

Nicht Bestandteil:

- Lobby
- Raumversorgung
- eigentliche Spielregeln
- Kartenlogik
- Stiche
- Ansagen
- Gewinnerermittlung

---

# 3. Beteiligte Komponenten

## Services

- GameRoomStartCoordinatorService
- GameRoomPlayStateService
- GameRoomFinishService
- GameRoomRakeService

## Controller

- GamePlayController
- GamePlayStateController
- GameFinishController

## Scheduler

- Advance Starts Command

---

# 4. Aktueller Ablauf

```text
Room full
    ↓
starting
    ↓
Countdown
    ↓
Startfinalisierung
    ↓
Buy-ins committen
    ↓
Rake buchen
    ↓
running
    ↓
[ GameEngine (noch nicht implementiert) ]
    ↓
technischer Finish-Harness
    ↓
finished
    ↓
Settlement
```

---

# 5. StartCoordinator

Der StartCoordinator übernimmt die Orchestrierung des Spielstarts.

Bestätigt sind:

- Startanforderung
- Countdown
- Idempotenz
- Finalisierung
- Statuswechsel nach `running`
- Commit reservierter Buy-ins
- einmalige Rake-Buchung

Der Countdown wird ohne lang laufende Datenbanktransaktionen umgesetzt.

---

# 6. Spielzustand

Der PlayStateService stellt den aktuellen Spielzustand bereit.

Aktuell umfasst dieser u. a.:

- Raumstatus
- Countdown
- Teilnehmer
- Sitzplätze
- Finish-Fortschritt

Die spätere GameEngine wird diese Struktur um die eigentlichen
Spieldaten erweitern.

---

# 7. GameEngine

Die fachliche GameEngine ist derzeit bewusst noch nicht implementiert.

Noch nicht vorhanden sind insbesondere:

- Kartenausgabe
- Spielregeln
- Zugreihenfolge
- erlaubte Aktionen
- Stichauswertung
- Gewinnerermittlung

Der bestehende technische Rahmen ist bereits darauf vorbereitet.

---

# 8. Finish

Der aktuelle FinishService dient als technischer Test-Harness.

Ein Spiel endet derzeit erst, wenn alle aktiven Teilnehmer ihren
Testabschluss gemeldet haben.

Dies ersetzt später nicht die fachliche Gewinnerermittlung der
GameEngine.

---

# 9. Rake

Der GameRoomRakeService berechnet:

- Brutto-Preispool
- Rake
- Netto-Preispool

Die Tests bestätigen u. a.:

- Mindestgrenze
- Mindest-Rake
- prozentuale Berechnung
- idempotente Anwendung

---

# 10. Scheduler

Der Advance-Starts-Command verarbeitet getrennt:

1. neue Startanforderungen
2. fällige Startfinalisierungen

Vorhanden sind zusätzlich:

- Dry-Run
- Limitierung
- Validierung

Damit ist der automatische Spielstart vorbereitet.

---

# 11. Implementierter Ist-Stand

Bestätigt sind:

- Countdown
- StartCoordinator
- Buy-in-Commit
- Rake
- Spielstatus
- Pollingfähiger PlayState
- Finish-Harness
- Scheduler
- umfangreiche Testabdeckung

---

# 12. Offene Umsetzung

Die eigentliche GameEngine ergänzt später den bereits vorhandenen
technischen Rahmen.

Offen sind insbesondere:

- vollständige Spielregeln
- Kartenverwaltung
- Spieleraktionen
- Gewinnerermittlung
- Übergabe der Ergebnisse an das Settlement

---

# 13. Definition of Done

Dieser Reset-Baustein dokumentiert die aktuelle Spielorchestrierung.

Er trennt bewusst zwischen dem bereits implementierten technischen
Rahmen und der noch ausstehenden fachlichen GameEngine.
