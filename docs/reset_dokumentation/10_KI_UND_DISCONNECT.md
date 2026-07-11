
# KI und Disconnect

## Dokumentstatus

**Reset-Status:** analysiert und dokumentiert
**Implementierungsstatus:** überwiegend fachlich vorbereitet

---

# 1. Zweck

Dieses Dokument beschreibt die geplante Einbindung von KI-Unterstützung
und den Umgang mit Verbindungsabbrüchen (Disconnects).

Beide Themen sind bewusst nicht Bestandteil der aktuellen
Spielimplementierung, wurden fachlich jedoch bereits vorbereitet.

---

# 2. Fachliche Grundidee

KI ist kein Ersatz für die Spielregeln.

Die GameEngine bleibt deterministisch und verarbeitet ausschließlich die
Spielregeln.

KI ergänzt das System später an definierten Stellen.

Das detaillierte Fachdokument
`docs/AI_AND_DISCONNECT_AUTOPILOT.md` bleibt für die Strategy Profiles
K1 bis K4, die Heuristiken und die Grundsätze des
Disconnect-Autopiloten maßgeblich. Dieses Reset-Dokument fasst nur den
Projektstatus und die fachliche Einordnung zusammen.

---

# 3. Geplante KI-Aufgaben

Vorgesehen sind insbesondere:

- Vertretung bei Disconnects
- Unterstützung lokaler Simulationen
- spätere Trainings- und Testspiele
- Analyse von Spielverläufen
- Erkennung auffälliger Muster
- Grundlage für spätere Tier- und Statistiksysteme

---

# 4. Disconnect-Konzept

Bereits fachlich beschlossen:

- Ein Disconnect beendet ein Spiel nicht automatisch.
- Ein echter Spieler bleibt settlementberechtigt.
- Eine spätere KI oder Automatik kann den Spieler technisch vertreten.
- Gewinne bleiben dem ursprünglichen Spieler zugeordnet.

---

# 5. Geplante Audit-Metadaten

Für spätere Auswertungen sind u. a. vorgesehen:

- was_ai_assisted
- ai_assisted_from
- ai_assisted_until
- disconnect_count
- abuse_review_required

Diese Informationen dienen Analyse- und Prüfzwecken und greifen nicht
in die Spielregeln ein.

---

# 6. Abgrenzung

Nicht Aufgabe der KI:

- Wallet-Buchungen
- Ledger
- Settlement
- Rewards
- Ranglisten
- Spielregelentscheidungen

Diese Verantwortlichkeiten verbleiben in ihren jeweiligen Domänen.

---

# 7. Aktueller Implementierungsstand

Vorhanden:

- fachliche Entscheidungen
- vorbereitete Architektur
- Berücksichtigung in Settlement-Planungen

Noch nicht vorhanden:

- KI-Spieler
- Disconnect-Vertretung
- KI-Entscheidungslogik
- Audit-Auswertung
- automatische Erkennung auffälliger Muster

---

# 8. Langfristige Zielarchitektur

```text
GameEngine
      │
      ▼
Spielergebnis
      │
      ▼
Settlement
      │
      ├── Rankings
      ├── Rewards
      ├── Tier-System
      ├── Statistik
      └── KI-/Audit-Auswertung
```

KI ergänzt die MMO-Plattform, ersetzt jedoch keine bestehende
Fachdomäne.

---

# 9. Definition of Done

Dieses Dokument beschreibt die geplanten KI- und Disconnect-Konzepte
sowie ihre fachliche Einordnung.

Die spätere Implementierung erfolgt auf Basis der bestehenden
Spielorchestrierung, GameEngine und Settlement-Domänen.
