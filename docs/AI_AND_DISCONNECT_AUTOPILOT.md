# AI_AND_DISCONNECT_AUTOPILOT.md

# Ziel

Dieses Dokument beschreibt die langfristige Architektur der Spiel-KI
sowie den Disconnect-Autopiloten. Es dient als fachliche Grundlage für
Phase 4 (GameEngine) und ersetzt keine technische Implementierung.

------------------------------------------------------------------------

# Grundprinzip

Die GameEngine kennt ausschließlich die Spielregeln.

Die KI kennt keine Sonderregeln und verändert niemals die Regeln des
Spiels. Sie bewertet lediglich erlaubte Spielzüge.

Trennung:

-   GameEngine → Regeln, Zustandsübergänge, Validierung
-   Decision Engine → bewertet erlaubte Züge
-   Strategy Profile → bestimmt die Spielstärke
-   Heuristiken → Erfahrungswissen

------------------------------------------------------------------------

# Strategy Profiles

## K1 -- Autopilot / Anfänger

Ziel: - Regelkonformes Spiel - Eigene Ansage erfüllen - Keine
langfristige Strategie

Einsatz: - Disconnect - Anfänger-Bot - Trainingsbot

------------------------------------------------------------------------

## K2 -- Taktischer Spieler

Zusätzlich: - Gegner beobachten - Offensichtliche Ziele erkennen -
Einfache taktische Entscheidungen

------------------------------------------------------------------------

## K3 -- Erfahrener Spieler

Grundlage: Persönliche Spielerfahrung des Projektinhabers.

Nutzt u. a.:

-   Ansageposition
-   Kartenverteilung
-   freie Farben
-   Schutz hoher Karten
-   Nichtbedienen als Information
-   langfristige Stichplanung

Leitsatz:

> K3 spielt nicht nur Karten. K3 spielt Informationen.

------------------------------------------------------------------------

## K4 (optional)

Noch zu definieren.

Denkbare Aufgaben:

-   Analyse kompletter Spiele
-   Auswertung öffentlicher Informationen
-   Meta-Strategien

------------------------------------------------------------------------

# Heuristiken

Die eigentliche Spielintelligenz besteht aus dokumentierten Heuristiken.

Beispiele:

-   Freie Farben sind wertvoll.
-   Eine hohe Einzelkarte macht eine 0-Ansage nicht automatisch
    schlecht.
-   Ansageposition verändert die Bewertung einer Hand.
-   Nichtbedienen liefert Information.
-   Weitere Heuristiken werden während der Entwicklung ergänzt.

------------------------------------------------------------------------

# Disconnect-Autopilot

Grundsatz:

Der Disconnect-Autopilot schützt den Spielfluss, nicht den
disconnecteten Spieler.

Anforderungen:

-   ausschließlich K1
-   regelkonformes Spiel
-   keine unfairen Vorteile
-   keine versteckten Informationen
-   jede Auto-Aktion nachvollziehbar
-   Reconnect übernimmt sofort wieder die Kontrolle
-   bereits ausgeführte Auto-Aktionen bleiben bestehen

Laravel bleibt jederzeit die autoritative Instanz.
