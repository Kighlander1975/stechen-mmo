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
- die Führungsregel beim Spielstart: Andere wartende Teilnahmen eines Spielers
  werden beim Start nicht verlassen und ihre Reservierungen nicht freigegeben;
  ein Spieler ist dadurch nicht gegen zwei gleichzeitig startende Spiele
  abgesichert (Details in `docs/reset_dokumentation/99_OFFENE_PUNKTE.md`)

Der vorhandene technische Finish-Harness ersetzt weder die Gewinnerermittlung
der GameEngine noch ein echtes Settlement. Vorhanden sind lediglich die
technischen Abschluss-, Rake-, Wallet-/Ledger- und Orchestrierungsgrundlagen.

Seit 2026-11-19 zusätzlich implementiert:

- Lobby nach Zielbild v3 für Desktop und Handy (Tischliste, eigene Tische als
  fester Block, Tischdetails mit Mini-Pokertisch, Bottom-Sheets mobil)
- mobile Ansicht aller Nicht-Admin-Seiten, selbst gehostete Schrift
- lokaler Start und Stopp ohne VS Code (`tools/dev/`)

## Fachlich beschlossen, nicht implementiert

Hosting auf Hetzner mit Staging und Produktion, Domains über ALL-INKL und
ein Node.js-Spielserver für das laufende Spiel (Variante B). Details und
offene Einzelentscheidungen: `docs/HOSTING_UND_BETRIEB.md`.

## Nächster Schwerpunkt

Vor Beginn der GameEngine werden die Lücken im „Drumherum“ geschlossen
(Raumversorgung, Führungsregel beim Start, Preispool-Wallet, Aufräumen
hängender Räume, Teilnahmelimits), siehe
`docs/reset_dokumentation/99_OFFENE_PUNKTE.md`. Keine Zukunftsfunktion gilt
allein aufgrund einer Planung als implementiert.

## Maßgebliche Dokumentation

- diese Statusdatei für den Gesamtstand
- `docs/reset_dokumentation/00_PROJECT_STATUS.md` und die Reset-Dokumente 01 bis
  10 für den domänenspezifischen Ist-Stand
- `docs/reset_dokumentation/99_OFFENE_PUNKTE.md` für offene Themen
- `docs/HOSTING_UND_BETRIEB.md` für Hosting, Umgebungen, Deployment und die
  Spielserver-Architektur
- die weiterhin aktiven technischen und fachlichen Referenzen direkt unter
  `docs/`

Archivierte Dokumente dienen ausschließlich als historische Quelle und sind
nicht automatisch maßgeblich.
