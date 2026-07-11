
# Settlement und Prize Distribution

## Dokumentstatus

**Reset-Status:** analysiert und dokumentiert
**Implementierungsstatus:** fachlich weitgehend geplant, technisch in Grundzügen vorbereitet

---

# 1. Zweck

Dieses Dokument beschreibt alle dauerhaften Auswirkungen eines
abgeschlossenen Spiels.

Die GameEngine liefert ausschließlich das fachliche Spielergebnis.
Settlement verarbeitet daraus die wirtschaftlichen und langfristigen
MMO-Auswirkungen.

---

# 2. Fachliche Abgrenzung

Dieses Dokument umfasst:

- Prize Distribution
- Prize-Pool
- Rake
- Wallet- und Ledger-Buchungen
- Auszahlungen
- spätere Rankings
- spätere Tier-Einstufung
- spätere Statistiken
- spätere KI-/Audit-Metadaten

Nicht Bestandteil:

- Spielregeln
- Kartenspiel-Engine
- Zuglogik
- Gewinnerermittlung

---

# 3. Zielarchitektur

```text
GameEngine
      │
      ▼
Ranking
      │
      ▼
Prize Distribution
      │
      ▼
Settlement
      │
 ├── Wallet
 ├── Ledger
 ├── Rewards
 ├── Rankings
 ├── Tier-System
 ├── Statistiken
 └── Rückkehr zur Lobby
```

---

# 4. Implementierter Ist-Stand

Aktuell vorhanden sind:

- Buy-in-Commit beim Spielstart
- Rake-Berechnung
- Wallet-/Ledger-Grundlagen
- technischer Finish-Harness

Ein automatisches Settlement nach Spielende existiert derzeit noch nicht.

---

# 5. Fachlich beschlossene Architektur

Bereits festgelegt sind unter anderem:

- zentrales Prize-Pool-Wallet
- getrenntes Rake-Wallet
- GameRoomSettlementService
- PrizePoolDistributionService
- transaktionale Buchungen
- Idempotency für alle Economy-Buchungen
- vollständige Ledger-Nachvollziehbarkeit
- getrennte Simulation auf eigener SQLite-Datenbank

---

# 6. Prize Distribution

Die Prize Distribution ist eine eigenständige fachliche Domäne.

Aufgaben:

- Brutto-Pool bestimmen
- Rake berücksichtigen
- Netto-Pool bestimmen
- Auszahlung je Platz berechnen
- Rundungsreste behandeln
- Summenkonsistenz sicherstellen

Sie enthält ausdrücklich keine Wallet-Buchungen.

---

# 7. Settlement

Settlement übernimmt anschließend:

- Prize-Pool-Debit
- User-Wallet-Credit
- Ledger-Einträge
- Referenzen auf GameRoom und Teilnehmer
- Validierung der Rangliste
- Schutz vor Doppelbuchungen

---

# 8. MMO-Auswirkungen

Nach erfolgreichem Settlement entstehen dauerhaft nutzbare Daten:

- Spielhistorie
- Ranglisten
- Tier-Einstufung
- Statistiken
- Rewards
- spätere KI-/Audit-Kennzeichnungen

Diese Schicht ist noch nicht implementiert.

---

# 9. Simulation

Für die Economy ist eine getrennte Simulation vorgesehen.

Merkmale:

- eigene SQLite-Datenbank
- fortlaufende Wallet-Entwicklung
- keine künstliche Wiederauffüllung
- Markdown-Reports
- reproduzierbare Läufe

---

# 10. Offene Umsetzung

Noch nicht implementiert sind insbesondere:

- GameRoomSettlementService
- PrizePoolDistributionService
- automatische Settlement-Ausführung
- Rankings
- Tier-System
- vollständige MMO-Auswertung

---

# 11. Definition of Done

Dieses Dokument trennt bewusst zwischen:

- implementierten Wallet-Grundlagen,
- fachlich beschlossener Settlement-Architektur,
- zukünftiger MMO-Auswertung.

Die eigentliche GameEngine liefert später ausschließlich das
Spielergebnis; sämtliche dauerhaften Auswirkungen werden durch die
Settlement-Domäne verarbeitet.
