# Stechen-MMO Phasenübersicht

Stand: Juni 2026  
Status: Zentrale Inhalts- und Orientierungsdatei für Projektphasen

## Zweck dieser Datei

Diese Datei dient als zentrale Übersicht über die Projektphasen von Stechen-MMO.

Sie ersetzt nicht die Detaildokumente, sondern verweist auf sie. Die Detaildokumente bleiben im Repository erhalten und können bei Bedarf direkt geladen werden.

Ziel ist es, die Knowledge Base schlank zu halten und trotzdem eine klare Orientierung über den aktuellen Projektstand zu behalten.

## Aktueller Fokus

Aktueller Umsetzungsfokus ist Phase 3.

Phase 3 behandelt:

- abstrakte Wallets;
- St$ / StechenDollar als Spielgeldwährung;
- technische Trennung von `PLAY_MONEY` und späterem `REAL_MONEY`;
- Buy-in-Reservierung;
- Buy-in-Commit beim Spielstart;
- Preispool-Bildung;
- Rake-Berechnung;
- Lobby-Grundlage;
- Raumstatus und Startmodi;
- KI-Spieler in kostenlosen Räumen;
- Ranglisten-/Rating-Vorbereitung.

Maßgebliche Detaildatei:

- `docs/PHASE_3_WALLET_BUYIN_AND_LOBBY.md`

## Phasen

### Phase 1: Foundation

Status: abgeschlossen / historisch

Detaildatei:

- `docs/PHASE_1_FOUNDATION.md`

Kurzbeschreibung:

Phase 1 legte die grundlegende Projektbasis. Dazu gehören Laravel-Projektstruktur, erste Architekturentscheidungen, grundlegende Seiten, Entwicklungsumgebung und frühe Projektorganisation.

Die Datei ist vor allem für historischen Kontext relevant.

Bei Bedarf laden mit:

- `Get-Content "docs/PHASE_1_FOUNDATION.md" -Raw`

### Phase 2: Auth, Users, Layout und Frontend-Grundlagen

Status: abgeschlossen / historisch

Detaildateien:

- `docs/PHASE_2_AUTH_AND_USERS.md`
- `docs/FRONTEND_VUE_ISLANDS.md`

Kurzbeschreibung:

Phase 2 behandelte Authentifizierung, Benutzerbereiche, Layout-Grundlagen, dunkles Tailwind-Design, Vue/Vite-Anbindung und die Vue-Insel-Architektur.

Wichtige bekannte Punkte:

- Authentifizierung basiert auf Laravel Breeze/Auth-Struktur.
- Logout erfolgt per POST.
- Nach Logout wird zur Startseite `/` weitergeleitet.
- Flash-Meldungen können über `x-flash-toast` angezeigt werden.
- Es existieren mehrere Layout-Systeme.
- Vue wird gezielt als Insel-Architektur eingesetzt.

Bei Bedarf laden mit:

- `Get-Content "docs/PHASE_2_AUTH_AND_USERS.md" -Raw`
- `Get-Content "docs/FRONTEND_VUE_ISLANDS.md" -Raw`

### Phase 3: Wallet, Buy-in, Preispool und Lobby

Status: geplant / aktueller Umsetzungsfokus

Detaildatei:

- `docs/PHASE_3_WALLET_BUYIN_AND_LOBBY.md`

Kurzbeschreibung:

Phase 3 legt die Economy- und Lobby-Grundlage für Stechen-MMO.

Zentrale Entscheidungen:

- Spielgeldwährung im UI: `St$` / StechenDollar.
- Technischer Asset-Code für Spielgeld: `PLAY_MONEY`.
- `REAL_MONEY` wird architektonisch vorbereitet, aber noch nicht umgesetzt.
- Wallets werden abstrakt und owner-basiert modelliert.
- Kontostände werden nicht direkt in Controllern manipuliert.
- Alle relevanten Bewegungen laufen über Services und Ledger.
- Buy-ins werden vor Spielstart reserviert.
- Beim Spielstart werden Buy-ins committed.
- Preispool und Rake entstehen erst beim Spielstart.
- Ohne Spielstart gibt es keinen Rake.
- Räume unterstützen 2 bis 11 Spieler.
- Startmodi: `when_full` und `scheduled`.
- Nach Spielstart gibt es keinen Einstieg mehr.
- Kostenlose Räume mit KI-Spielern sind möglich.
- Ranglistenwertung ist auch bei Spielgeldspielen vorgesehen.
- Wertung ist ab mindestens 3 menschlichen Spielern möglich.
- KI-Spieler zählen nicht für die menschliche Wertung.
- Langfristig ist ein Rating-/Elo-System für Rangliste und Raumzuordnung geplant.

Bei Bedarf laden mit:

- `Get-Content "docs/PHASE_3_WALLET_BUYIN_AND_LOBBY.md" -Raw`

### Phase 4: Spielengine und Gameplay

Status: noch nicht detailliert geplant

Mögliche künftige Themen:

- Kartenlogik;
- Stichlogik;
- Ansagen;
- Trumpf-/Farbregeln;
- Punkteberechnung;
- Spielende;
- Ergebnisermittlung;
- Preisverteilung nach Spielende;
- Event-/Replay-Historie.

Voraussichtliche Detaildatei:

- `docs/PHASE_4_GAME_ENGINE.md`

### Phase 5: Realtime, Homeserver und Multiplayer-Betrieb

Status: teilweise vorbereitet / noch nicht detailliert geplant

Relevante bestehende Dateien:

- `docs/HOMESERVER_FALLBACK.md`
- `docs/homeserver-inventory.md`
- `docs/Stechen-Serverkonzept.md`
- `docs/INFRASTRUCTURE_STATUS.md`

Mögliche künftige Themen:

- Homeserver-Kommunikation;
- WebSocket-/Realtime-Betrieb;
- Fallback-Verhalten;
- Spielraum-Synchronisierung;
- Verbindungsabbrüche;
- Reconnect;
- Serverzustand;
- Betriebsmodell.

Bei Bedarf laden mit:

- `Get-Content "docs/HOMESERVER_FALLBACK.md" -Raw`
- `Get-Content "docs/homeserver-inventory.md" -Raw`
- `Get-Content "docs/Stechen-Serverkonzept.md" -Raw`
- `Get-Content "docs/INFRASTRUCTURE_STATUS.md" -Raw`

### Phase 6: Rating, Rangliste und Matchmaking

Status: als Ziel vorgemerkt / noch nicht detailliert geplant

Mögliche künftige Themen:

- Rating-/Elo-System;
- Multiplayer-taugliche Ratingformel;
- Ranglisten;
- Raumfilter nach Spielstärke;
- Raumerstellung nach Ratingbereichen;
- Einsteiger-/Fortgeschrittenenräume;
- Schutz neuer Spieler;
- saisonale Wertungen.

Voraussichtliche Detaildatei:

- `docs/PHASE_6_RATING_AND_MATCHMAKING.md`

### Phase 7: Turniere, Satellites und Spezialformate

Status: als Ziel vorgemerkt / noch nicht detailliert geplant

Mögliche künftige Themen:

- Turniere;
- Satellites;
- Tickets;
- Qualifikationsspiele;
- Spezialräume;
- Events;
- Admin-gesteuerte Aktionen;
- besondere Preispool-Modelle.

Voraussichtliche Detaildatei:

- `docs/PHASE_7_TOURNAMENTS_AND_SATELLITES.md`

## Weitere zentrale Dokumente

### Projektüberblick

Datei:

- `docs/PROJECT_OVERVIEW.md`

Zweck:

- allgemeine Projektbeschreibung;
- Kontext;
- Zielbild;
- Orientierung für neue Projektteile.

Bei Bedarf laden mit:

- `Get-Content "docs/PROJECT_OVERVIEW.md" -Raw`

### MVP-Konzept

Datei:

- `docs/MVP_CONCEPT.md`

Zweck:

- MVP-Abgrenzung;
- frühe Produktidee;
- Zielumfang.

Bei Bedarf laden mit:

- `Get-Content "docs/MVP_CONCEPT.md" -Raw`

### Roadmap

Datei:

- `docs/ROADMAP.md`

Zweck:

- grobe Projektplanung;
- Etappen;
- Prioritäten.

Bei Bedarf laden mit:

- `Get-Content "docs/ROADMAP.md" -Raw`

### Spielregeln

Datei:

- `docs/GAME_RULES.md`

Zweck:

- fachliche Spielregeln;
- Grundlage für spätere Game-Engine.

Bei Bedarf laden mit:

- `Get-Content "docs/GAME_RULES.md" -Raw`

### Rollen und Berechtigungen

Datei:

- `docs/rollen-und-berechtigungen.md`

Zweck:

- Rollenmodell;
- Berechtigungen;
- Admin-/User-Abgrenzung.

Bei Bedarf laden mit:

- `Get-Content "docs/rollen-und-berechtigungen.md" -Raw`

### Tech Stack

Datei:

- `docs/tech-stack.md`

Zweck:

- technische Grundentscheidungen;
- verwendete Technologien;
- Infrastrukturhinweise.

Bei Bedarf laden mit:

- `Get-Content "docs/tech-stack.md" -Raw`

### KB-Quellenindex

Datei:

- `docs/KB_SOURCE_INDEX.md`

Zweck:

- Übersicht über Dateien, die für die Knowledge Base relevant sind;
- Quelle für KB-Pflege.

Bei Bedarf laden mit:

- `Get-Content "docs/KB_SOURCE_INDEX.md" -Raw`

## Empfehlung für die Knowledge Base

Um die Knowledge Base schlank zu halten, sollte langfristig bevorzugt diese Datei aufgenommen werden:

- `docs/PHASES_INDEX.md`

Zusätzlich sinnvoll für den aktuellen Arbeitsstand:

- `docs/KB_SOURCE_INDEX.md`
- `docs/PROJECT_OVERVIEW.md`
- `docs/PHASE_3_WALLET_BUYIN_AND_LOBBY.md`

Während Phase 3 aktiv umgesetzt wird, ist es sinnvoll, die Phase-3-Detaildatei in der KB zu behalten.

Nach Abschluss oder Stabilisierung von Phase 3 kann die Detaildatei aus der KB entfernt werden, solange sie im Repository verfügbar bleibt.

Detaildateien können bei Bedarf direkt angefordert werden, zum Beispiel:

- `Get-Content "docs/PHASE_3_WALLET_BUYIN_AND_LOBBY.md" -Raw`

## Arbeitsregel für zukünftige Phasendokumente

Für neue Phasen sollte jeweils eine eigene Detaildatei erstellt werden.

Empfohlenes Namensschema:

- `docs/PHASE_4_GAME_ENGINE.md`
- `docs/PHASE_5_REALTIME_AND_HOMESERVER.md`
- `docs/PHASE_6_RATING_AND_MATCHMAKING.md`
- `docs/PHASE_7_TOURNAMENTS_AND_SATELLITES.md`

Diese Inhaltsdatei sollte anschließend aktualisiert werden.

Die KB muss nicht jede Detaildatei dauerhaft enthalten. Maßgeblich ist, dass die Detaildateien im Repository liegen und bei Bedarf gezielt geladen werden können.
