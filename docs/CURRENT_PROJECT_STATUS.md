# Aktueller Projektstatus

## Zweck

Diese Datei ist die kompakte Single Source of Truth für den tatsächlich
implementierten Projektstand. Zielarchitekturen und Planungen sind nur dann als
implementiert zu verstehen, wenn dies ausdrücklich so angegeben ist.

## Gesamtstand

Der vollständige Dokumentations-Reset und die anschließende
Dokumentationsbereinigung sind abgeschlossen. Historische Dokumente werden
geordnet archiviert, aber nicht pauschal gelöscht.

Implementiert sind insbesondere:

- Authentifizierung, Registrierung sowie Rollen- und Berechtigungsgrundlagen
- Wallet-, Ledger- und Reward-Grundlagen
- Lobby, Raumangebot, Join/Leave und Buy-in-Reservierung
- technische Spielstart-Orchestrierung mit Countdown, Buy-in-Commit und Rake
- pollingfähiger Spielzustand und technischer Spielabschluss-Platzhalter
- lokale Admin-, Diagnose- und Testwerkzeuge

## Noch nicht implementiert

- die fachliche GameEngine einschließlich Karten-, Stich-, Ansage- und
  Gewinnerlogik
- echtes automatisches Settlement und Prize Distribution
- `GameRoomSettlementService`
- `PrizePoolDistributionService`
- KI-Spieler und Disconnect-Autopilot
- Rankings, Tier-Berechnung und vollständige MMO-Auswertung

Der vorhandene technische Finish-Harness ersetzt weder die Gewinnerermittlung
der GameEngine noch ein echtes Settlement. Vorhanden sind lediglich die
technischen Abschluss-, Rake-, Wallet-/Ledger- und Orchestrierungsgrundlagen.

## Nächster Schwerpunkt

Als nächstes wird die Lobby gründlich fachlich und UX-seitig geprüft. Danach
werden die offenen Punkte aus
`docs/reset_dokumentation/99_OFFENE_PUNKTE.md` priorisiert umgesetzt. Keine
Zukunftsfunktion gilt allein aufgrund einer Planung als implementiert.

## Maßgebliche Dokumentation

- diese Statusdatei für den Gesamtstand
- `docs/reset_dokumentation/00_PROJECT_STATUS.md` und die Reset-Dokumente 01 bis
  10 für den domänenspezifischen Ist-Stand
- `docs/reset_dokumentation/99_OFFENE_PUNKTE.md` für offene Themen
- die weiterhin aktiven technischen und fachlichen Referenzen direkt unter
  `docs/`

Archivierte Dokumente dienen ausschließlich als historische Quelle und sind
nicht automatisch maßgeblich.
