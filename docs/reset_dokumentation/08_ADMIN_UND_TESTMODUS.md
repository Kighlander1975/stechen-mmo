
# Admin und Testmodus

## Dokumentstatus

**Reset-Status:** analysiert und dokumentiert
**Implementierungsstatus:** implementiert und für die lokale Entwicklung ausgelegt

---

# 1. Zweck

Dieses Dokument beschreibt die vorhandenen Administrations-,
Diagnose- und Entwicklungswerkzeuge des Projekts.

Der Schwerpunkt liegt auf reproduzierbaren lokalen Entwicklungs- und
Testabläufen, nicht auf einer späteren Produktivadministration.

---

# 2. Fachliche Abgrenzung

Dieses Dokument umfasst:

- Admin-Dashboard
- Test-Harness
- lokale Testdaten
- Room-Supply-Testmodus
- Registration-Bonus-Backfill
- Zugriffs- und Sicherheitsregeln

Nicht Bestandteil:

- Lobby
- Spielräume
- Spielzyklus
- Settlement
- HomeServer-Infrastruktur

---

# 3. Beteiligte Komponenten

## Controller

- AdminDashboardController
- Phase3LocalTestHarnessController
- RoomSupplyTestModeController
- RegistrationBonusBackfillController

## Services

- Phase3LocalTestHarnessService
- Phase3LocalTestDataService

## Views

- Admin-Dashboard
- Overview Cards
- Rewards Card

---

# 4. Admin-Dashboard

Das Dashboard dient als zentraler Einstiegspunkt für
Entwicklungs- und Wartungsfunktionen.

Die eigentliche Fachlogik befindet sich nicht im Controller,
sondern in eigenständigen Services.

---

# 5. Phase-3-Test-Harness

Der Test-Harness stellt reproduzierbare lokale
Entwicklungsbedingungen bereit.

Bestätigt sind u. a.:

- Aktivierung und Deaktivierung
- Umgebungsprüfung
- Feature-Schalter
- Statusabfrage
- lokale Schutzmechanismen

---

# 6. Testdaten

Der Phase3LocalTestDataService erzeugt deterministische Testdaten.

Hierzu gehören insbesondere:

- Testbenutzer
- Wallets
- Ledger
- Spielräume
- definierte Ausgangszustände

Dadurch können Tests und Entwicklung mit reproduzierbaren Daten
durchgeführt werden.

---

# 7. Room-Supply-Testmodus

Der Room-Supply-Testmodus erlaubt gezielte lokale Tests der
Raumerzeugung.

Dabei können fachlich definierte Einschränkungen – insbesondere
Wallet-Eligibility für den Supply – kontrolliert übersteuert werden.

Diese Funktion ist ausschließlich für Entwicklungsumgebungen
vorgesehen.

---

# 8. Registration-Bonus-Backfill

Der Backfill dient der nachträglichen Vergabe bereits eingeführter
Registrierungsboni.

Die eigentliche Fachlogik ist gekapselt und wird nicht direkt im
Controller umgesetzt.

---

# 9. Sicherheit

Alle Entwicklungswerkzeuge sind gegen produktiven Einsatz abgesichert.

Zum Einsatz kommen unter anderem:

- Admin-Berechtigungen
- lokale Umgebungsprüfung
- Testing-Umgebung
- SystemSettings
- Feature-Schalter

---

# 10. Testabdeckung

Die vorhandenen Tests bestätigen insbesondere:

- Zugriffsschutz
- Administratorrechte
- reproduzierbare Testdaten
- deterministische Testumgebungen
- Backfill-Verhalten
- lokale Entwicklungsabläufe

---

# 11. Implementierter Ist-Stand

Vorhanden sind:

- modulares Admin-Dashboard
- lokaler Test-Harness
- Testdaten-Generator
- Room-Supply-Testmodus
- Registration-Bonus-Backfill
- umfangreiche Zugriffstests

---

# 12. Definition of Done

Dieses Dokument beschreibt die vorhandenen
Administrations- und Entwicklungswerkzeuge.

Die Produktionsadministration ist bewusst noch klein gehalten,
während lokale Entwicklung, Tests und Diagnose bereits gezielt
unterstützt werden.
