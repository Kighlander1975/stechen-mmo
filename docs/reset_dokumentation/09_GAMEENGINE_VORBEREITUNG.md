
# GameEngine Vorbereitung

## Dokumentstatus

**Reset-Status:** analysiert und dokumentiert
**Implementierungsstatus:** Architektur vorbereitet, GameEngine noch nicht implementiert

---

# 1. Zweck

Dieses Dokument beschreibt die fachliche und technische Schnittstelle
zwischen der bestehenden MMO-Plattform und der späteren GameEngine.

Die GameEngine ist bewusst als eigenständige Domäne vorgesehen.

---

# 2. Fachliche Rolle

Die GameEngine verarbeitet ausschließlich das eigentliche Kartenspiel.

Sie erhält einen gültigen Spielzustand, verarbeitet Spieleraktionen nach
dem Regelwerk und liefert das fachliche Spielergebnis zurück.

Alle wirtschaftlichen und langfristigen Auswirkungen werden außerhalb
der Engine verarbeitet.

---

# 3. Voraussetzungen

Vor dem Start der GameEngine garantiert die Spielorchestrierung:

- Raumstatus `running`
- Countdown abgeschlossen
- Teilnehmer stehen fest
- Buy-ins wurden committed
- Rake wurde gebucht
- Spielerstatus ist `playing`

Die GameEngine muss diese Schritte nicht erneut prüfen oder durchführen.

---

# 4. Erwartete Eingaben

Die spätere Engine verarbeitet insbesondere:

- Spielraum
- Teilnehmer
- Sitzreihenfolge
- Regelwerk
- Spielzustand
- Spieleraktionen
- optionale Zufallsquelle bzw. Seed

---

# 5. Erwartete Ausgaben

Die Engine liefert ausschließlich fachliche Ergebnisse zurück:

- neuer Spielzustand
- erlaubte Folgeaktionen
- Ereignisse des Spiels
- Spielende
- Rangfolge
- optionale Spielstatistiken

Diese Ergebnisse dienen als Eingabe für das Settlement.

---

# 6. Verantwortlichkeiten

Die GameEngine ist verantwortlich für:

- Kartenverwaltung
- Zugreihenfolge
- Regelprüfung
- Spielaktionen
- Stichauswertung
- Gewinnerermittlung

---

# 7. Nicht Bestandteil

Die GameEngine verändert ausdrücklich nicht:

- Wallets
- Ledger
- Rewards
- Ranglisten
- Tier-System
- Lobby
- Benutzerkonten
- Chat
- HomeServer
- Infrastruktur

Diese Verantwortlichkeiten verbleiben in den jeweiligen Domänen.

---

# 8. Übergabe an Settlement

Nach Abschluss des Spiels übergibt die Engine:

- finale Rangfolge
- Ergebnisdaten
- optionale Statistikdaten
- Metadaten für Audit und Historie

Ab diesem Zeitpunkt übernimmt die Settlement-Domäne.

---

# 9. Architekturziel

```text
Lobby
    ↓
Spielräume
    ↓
Spielorchestrierung
    ↓
GameEngine
    ↓
Settlement
    ↓
MMO-Auswertung
```

Die GameEngine bleibt dadurch unabhängig von Wallet-, Reward- und
Infrastrukturkomponenten.

---

# 10. Definition of Done

Dieses Dokument definiert den Vertrag zwischen Spielorchestrierung,
GameEngine und Settlement.

Die spätere Implementierung der GameEngine soll sich innerhalb dieser
Schnittstelle bewegen, ohne fachliche Verantwortung anderer Domänen zu
übernehmen.
