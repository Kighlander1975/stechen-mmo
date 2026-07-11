
# Offene Punkte

## Dokumentstatus

**Reset-Status:** Abschlussdokument des Projekt-Resets

---

# 1. Zweck

Dieses Dokument bildet den Abschluss der Reset-Dokumentation.

Es sammelt ausschließlich Themen, die nach Abschluss des Resets
bewusst offen bleiben und als Grundlage für die weitere Entwicklung
dienen.

---

# 2. Domänenstatus

| Domäne | Status |
|---|---|
| Registrierung | Dokumentiert |
| Wallet & Ledger | Dokumentiert |
| Reward-System | Dokumentiert |
| Lobby | Dokumentiert |
| Spielräume | Dokumentiert |
| Spielzyklus | Dokumentiert |
| Settlement & Prize Distribution | Dokumentiert |
| Admin & Testmodus | Dokumentiert |
| GameEngine-Vorbereitung | Dokumentiert |
| KI & Disconnect | Dokumentiert |

---

# 3. Hohe Priorität

## Lobby

Vor der eigentlichen GameEngine soll die Lobby fachlich und
benutzerseitig überprüft werden.

Insbesondere:

- Informationsdarstellung
- Benutzerführung
- Zustandsübergänge
- Übergang Lobby → Spiel
- Vorbereitung auf die GameEngine

---

## Settlement

Die grundlegende Architektur steht.

Noch umzusetzen sind u. a.:

- PrizePoolDistributionService
- GameRoomSettlementService
- automatische Settlement-Ausführung
- Rankings
- Tier-System
- Statistikaufbereitung

---

## GameEngine

Die Schnittstellen sind definiert.

Die eigentliche Engine bleibt als eigenständige Domäne vollständig
umzusetzen.

---

# 4. Mittlere Priorität

- HomeServer- und Realtime-Ausbau
- Presence
- Chat
- Matchmaking
- Favoriten
- Auto-Join
- weitere Adminfunktionen
- Diagnosewerkzeuge

---

# 5. Langfristige Themen

- KI-Vertretung bei Disconnects
- KI-gestützte Auswertungen
- Saison- und Ranglistensystem
- Achievements
- Replay-Metadaten
- Analytics
- Anti-Cheat- und Abuse-Erkennung

---

# 6. Architekturprinzipien

Für die weitere Entwicklung gelten weiterhin:

- Code ist die maßgebliche Quelle.
- Dokumentation gehört zur Definition of Done.
- Domänen bleiben klar getrennt.
- Fachlogik gehört in Services.
- Wallet- und Ledger-Buchungen bleiben transaktional und idempotent.
- Die GameEngine bleibt unabhängig von Wallet, Rewards und Settlement.

---

# 7. Abschluss des Resets

Mit Abschluss dieses Dokuments ist der Projekt-Reset beendet.

Die weitere Entwicklung beginnt auf Basis der dokumentierten
Ist-Architektur und der fachlich beschlossenen Zielarchitektur.

Änderungen erfolgen künftig wieder featureorientiert und werden
fortlaufend dokumentiert.
