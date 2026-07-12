# Benutzerpräferenzen

## Zweck

`user_preferences` speichert benutzerspezifische UI- und UX-Präferenzen in
einem expliziten, erweiterbaren Tabellenmodell. Jedes Feld besitzt eine klar
definierte Verantwortung. Eine generische Key-Value-Struktur oder ein
unkontrolliertes JSON-Feld wird bewusst nicht verwendet.

Im aktuellen Stand werden ausschließlich die vorhandenen Lobbyfilter
persistiert.

## Fachliche Abgrenzung

Benutzerpräferenzen beschreiben persönliche Darstellungs- und
Bedienentscheidungen.

Sie sind getrennt von:

- `user_profiles` für fachliche Profildaten und Profilvollständigkeit,
- `user_verifications` für Identitäts- oder Nachweisprüfungen,
- Authentifizierung, Rollen und Berechtigungen,
- temporären Sitzungs- und UI-Zuständen.

`user_profiles` und `user_verifications` sind nicht Bestandteil dieser
Implementierung.

## Datenmodell

`users` besitzt eine optionale 1:1-Beziehung zu `user_preferences`:

```text
User hasOne UserPreference
UserPreference belongsTo User
```

`user_preferences.user_id` ist eindeutig und wird beim Löschen des Benutzers
mitgelöscht. Pro Benutzer kann dadurch höchstens ein Präferenzdatensatz
existieren.

Lobbyfelder verwenden konsequent das Präfix `lobby_`:

| Feld | Typ | Default | Bedeutung |
|---|---|---|---|
| `lobby_status` | nullable string | `null` | Raumstatus oder alle Statuswerte |
| `lobby_start_mode` | nullable string | `null` | Startmodus oder alle Startmodi |
| `lobby_buy_in` | nullable string | `null` | Buy-in-Kategorie oder alle Kategorien |
| `lobby_players` | nullable string | `null` | Tischgrößen-Kategorie oder alle Größen |
| `lobby_only_test` | boolean | `false` | ausschließlich berechtigte Testräume |

## Lazy-Erzeugung und Defaults

Bei Registrierung und bloßem Lobbybesuch wird kein Präferenzdatensatz erzeugt.
Ohne Datensatz gelten die bestehenden Lobbydefaults:

```text
status: null
start_mode: null
buy_in: null
players: null
only_test: false
```

Die serverseitige Präferenzlogik ist die fachliche Quelle für Defaults,
Normalisierung und Testmodusberechtigung. Das Frontend übernimmt den vom Server
gelieferten Zustand.

## Vollständiger Lobbyfilter-Snapshot

Beim ersten bewussten Anwenden oder Zurücksetzen der Filter sendet das Frontend
den vollständigen aktuellen Filterzustand. Der Server validiert und normalisiert
alle Felder gemeinsam und legt den Datensatz bei Bedarf an.

Weitere Änderungen aktualisieren denselben Datensatz ebenfalls als vollständigen
Snapshot. Dadurch entstehen keine teilweise gefüllten oder kontextlosen
Lobbypräferenzen. Wiederholtes Speichern desselben Zustands ist idempotent.

Nicht mehr gültige gespeicherte Werte werden beim Laden auf den jeweiligen
Default normalisiert. Ein bloßer Lobbybesuch ohne vorhandenen Datensatz bleibt
schreibfrei.

## Testmodus

`lobby_only_test` ist nur wirksam, wenn:

- der lokale Phase-3-Testmodus aktiv ist und
- der Benutzer als Phase-3-Testbenutzer für Testräume berechtigt ist.

Beim Deaktivieren des Testmodus werden ausschließlich aktive
`lobby_only_test`-Werte auf `false` gesetzt. Alle normalen Lobbypräferenzen
bleiben unverändert. Eine spätere Reaktivierung des Testmodus aktiviert dadurch
keinen alten Testfilter erneut.

## Bewusst nicht persistierte Zustände

Nicht in `user_preferences` gespeichert werden:

- aktuell ausgewählter Raum,
- Scrollposition,
- Lade- und Pollingzustände,
- Fehler- und Toastmeldungen,
- geöffnete Detailbereiche,
- sonstige rein sitzungsbezogene UI-Zustände.

Neue Präferenzfelder werden nur bei einer konkreten fachlichen Anforderung als
eigene, klar benannte Spalten ergänzt.
