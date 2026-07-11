# Projektstatus nach dem Dokumentations-Reset

## Zweck und Quellenpriorität

Der Reset stellt eine belastbare Dokumentationsbasis her, die den tatsächlichen
Build von Zielarchitektur und historischen Planungen trennt. Bei Widersprüchen
gilt: Nutzeranweisung, Toolausgaben, tatsächlicher Code, Reset-Dokumentation,
aktive Projektdokumentation und zuletzt historische Dokumentation.

## Gesamtstatus

Der Dokumentations-Reset ist abgeschlossen. Die bestehende Plattform umfasst
Account-, Wallet-/Ledger-, Reward-, Lobby-, Raum- und technische
Spielorchestrierungsgrundlagen. Zukünftige Fachdomänen werden nicht als
implementiert dargestellt.

| Reset-Dokument | Kurzstatus |
|---|---|
| 01 Registrierung | überwiegend implementiert; Eligibility und Rechtstext-Historisierung offen |
| 02 Wallets und Ledger | Grundlage implementiert; Betriebs- und Reconciliation-Themen offen |
| 03 Reward-System | implementiert |
| 04 Lobby | implementierte Kernfunktionen; erneutes Fach- und UX-Review erforderlich |
| 05 Spielräume | Kern-Lifecycle vor Spielstart implementiert, Erweiterungen offen |
| 06 Spielzyklus | technische Orchestrierung und Finish-Harness vorhanden |
| 07 Settlement | fachlich geplant, technisch nur Grundlagen vorbereitet |
| 08 Admin und Testmodus | lokale Werkzeuge implementiert |
| 09 GameEngine-Vorbereitung | Schnittstellen vorbereitet; Engine nicht implementiert |
| 10 KI und Disconnect | fachlich dokumentiert; nicht implementiert |

Klarstellungen:

- Die GameEngine ist nicht implementiert.
- Echtes Settlement und Prize Distribution sind nicht implementiert.
- KI-Spieler und Disconnect-Autopilot sind nicht implementiert.
- Die Lobby ist implementiert, muss aber vor weiterer Entwicklung erneut
  fachlich und UX-seitig geprüft werden.

## Offene Punkte und nächster Schwerpunkt

Die verbindliche Sammlung offener Themen steht in `99_OFFENE_PUNKTE.md`.
Nächster Entwicklungsschwerpunkt ist ein gründliches Lobby-Review; anschließend
werden die offenen Punkte priorisiert umgesetzt. In diesem Reset wurden keine
offenen Produktfeatures implementiert.

## Maßgebliche Dokumente

Aktuell maßgeblich sind:

- `docs/CURRENT_PROJECT_STATUS.md` als kompakte Single Source of Truth
- dieses Dokument und `01_REGISTRIERUNG.md` bis
  `10_KI_UND_DISCONNECT.md` für die Domänenstände
- `99_OFFENE_PUNKTE.md` für verbleibende Aufgaben und Entscheidungen
- `docs/GAME_RULES.md` für die Spielregeln
- `docs/AI_AND_DISCONNECT_AUTOPILOT.md` für KI-Profile, Heuristiken und
  Disconnect-Autopilot-Grundsätze
- die beiden aktiven Settlement-Planungsdokumente für ihre konkreten Regeln
- weitere aktive technische Referenzen direkt unter `docs/`

Dokumente unter `docs/archiv/` sind historische Quellen und nicht automatisch
maßgeblich.
