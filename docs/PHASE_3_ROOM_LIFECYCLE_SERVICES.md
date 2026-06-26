# Phase 3: Room Lifecycle Services

Stand: 2026-06-26

Diese Datei ergänzt die bestehenden Phase-3-Dokumente und hält den aktuellen Stand der Raum-Lifecycle-Services fest.

Der Fokus liegt auf:

- Raumbeitritt
- Buy-in-Reservierung
- freiwilligem Austritt vor Spielstart
- systemseitigem Raumabbruch
- späterem Cleanup abgebrochener Räume
- Vorbereitung der Startphase

Die Datei ist bewusst ergänzbar und dient als Arbeitsdokument für die nächsten Phase-3-Schritte.

---

## Aktueller technischer Stand

Folgende Services sind implementiert, getestet und auf `main` integriert:

- `GameRoomEligibilityService`
- `GameRoomJoinService`
- `GameRoomLeaveService`
- `GameRoomCancellationService`

Der letzte bestätigte Teststand nach Implementierung der Cancellation-Logik:

- 204 Tests bestanden
- 945 Assertions bestanden

---

## Grundsätze

Laravel bleibt die autoritative Backend- und Spielstandsquelle.

Die Phase-3-Raumlogik arbeitet transaktional und servicebasiert. Wallet-, Ledger-, Join-, Leave- und Cancel-Aktionen sollen nicht direkt in Controllern manipuliert werden.

Wichtige Regeln:

- Wallet-Buchungen laufen über `WalletService`.
- Buy-ins werden vor Spielstart reserviert.
- Reservierungen reduzieren `available_units`, nicht `balance_units`.
- Ledger-Einträge bleiben die dauerhafte finanzielle Historie.
- Vorstart-Teilnahmen dürfen operativ gelöscht werden, wenn die finanzielle Spur im Ledger erhalten bleibt.
- Laufende Spiele dürfen durch Leave-, Cancel- oder Cleanup-Logik nicht beschädigt werden.

---

## Wallet- und Ledger-Semantik

### Reservierung beim Join

Beim erfolgreichen Raumbeitritt wird das Buy-in reserviert.

Die Reservierung umfasst:

- Buy-in
- ggf. Rake-Anteil

Die Reservierung erzeugt einen Ledger-Eintrag vom Typ Reserve.

Der Idempotency-Key für Join-Reservierungen folgt aktuell dem Muster:

- `game-room-player:{id}:reserve`

### Freigabe beim Leave

Beim freiwilligen Verlassen eines wartenden Raums wird die Reservierung freigegeben.

Der Idempotency-Key für Leave-Freigaben folgt aktuell dem Muster:

- `game-room-player:{id}:release`

Die Ledger-Metadata enthält u. a.:

- Quelle
- Grund
- Raum-ID
- Public Code
- GameRoomPlayer-ID
- User-ID
- Seat Number
- Buy-in
- Rake
- freigegebene Units

### Freigabe beim Cancel

Beim systemseitigen oder administrativen Raumabbruch werden Vorstart-Reservierungen freigegeben.

Der Idempotency-Key für Cancel-Freigaben folgt aktuell dem Muster:

- `game-room-player:{id}:cancel-release`

Die Ledger-Metadata enthält den Cancel-Grund.

Beispiele für Cancel-Gründe:

- `scheduled_too_few_players`
- `admin_cancelled`
- `system_cancelled`

---

## GameRoomEligibilityService

Der Eligibility-Service prüft, ob ein User einen Raum grundsätzlich betreten darf.

Typische Prüfungen:

- User darf spielen oder hat die passende Berechtigung.
- Raum ist offen.
- Raumlimits sind valide.
- Buy-in ist valide.
- Asset und Währung werden unterstützt.
- Test-Harness-Regeln werden eingehalten.
- Test-User dürfen nur Test-Räume betreten.
- Normale User dürfen keine Test-Räume betreten.

Der Service dient als fachliche Vorprüfung und wird im Join-Service nach dem Datenbank-Lock erneut berücksichtigt.

---

## GameRoomJoinService

Der Join-Service führt den Raumbeitritt transaktional aus.

Aktuelle Eigenschaften:

- Raum wird per `lockForUpdate()` gesichert.
- Eligibility wird nach dem Lock erneut geprüft.
- Aktive Doppelteilnahme im selben Raum wird idempotent behandelt.
- Der erste freie Sitzplatz wird vergeben.
- Buy-in und Rake werden reserviert.
- Ein Ledger-Reserve-Eintrag wird geschrieben.
- Wenn `max_players` erreicht ist, wird der Raum auf `full` gesetzt.

Aktuelle Idempotenzregel:

- Ein zweiter Join-Aufruf für denselben User im selben Raum gibt die bestehende aktive Teilnahme zurück.
- Es wird keine zweite Reservierung erzeugt.
- Es wird kein zweiter Ledger-Reserve-Eintrag erzeugt.

---

## GameRoomLeaveService

Der Leave-Service behandelt user-zentrierte Austritte aus wartenden Räumen.

Aktuelle Methoden:

- `leave(User $user, GameRoom $room, string $reason): bool`
- `leaveAllNonRunningForUser(User $user, string $reason): int`

### Normales Leave vor Spielstart

Fachliche Entscheidung:

- User Leave vor Spielstart nutzt Delete-on-Leave.
- Die `GameRoomPlayer`-Zeile wird gelöscht.
- Die Ledger-Historie bleibt erhalten.

Erlaubte Raumstatus:

- `open`
- `full`

Erlaubte Playerstatus:

- `reserved`
- `joined`
- `ready`

Nicht betroffen:

- `running` Räume
- `playing` Teilnehmer

Effekte:

- Raum wird gelockt.
- Teilnahme wird gelockt.
- Reservierung wird freigegeben.
- Release-Ledger wird geschrieben.
- `GameRoomPlayer` wird gelöscht.
- Ein `full` Raum wird wieder auf `open` gesetzt.
- Wenn keine passende Teilnahme existiert, gibt der Service idempotent `false` zurück.

### Leave all non-running

`leaveAllNonRunningForUser()` entfernt einen User aus allen wartenden, nicht laufenden Räumen.

Die Methode ist vorgesehen für spätere Funktionen wie:

- bewusste User-Aktion: aus allen wartenden Räumen abmelden
- expliziter Logout
- Disconnect-Timeout nach Ablauf einer Schonfrist
- Startkonflikt-Auflösung

Wichtig:

- Laufende Spiele bleiben unangetastet.
- Für laufende Spiele wird später Bot-/Presence-/Abuse-Logik benötigt.

---

## GameRoomCancellationService

Der Cancellation-Service behandelt raumzentrierte Abbrüche vor Spielstart.

Aktuelle Methode:

- `cancelRoom(GameRoom $room, string $reason): int`

Fachliche Entscheidung:

- Bei Room-Cancel werden Vorstart-Reservierungen freigegeben.
- Vorstart-`GameRoomPlayer`-Zeilen werden gelöscht.
- Der Raum wird auf `cancelled` gesetzt.
- Die finanzielle Historie bleibt über Ledger-Einträge erhalten.
- Cancel-Gründe werden in Ledger-Metadata gespeichert.

Erlaubte Raumstatus:

- `draft`
- `open`
- `full`

Nicht erlaubt:

- `running`
- `finished`

Bereits `cancelled`:

- wird idempotent mit Rückgabe `0` behandelt.

Erlaubte Playerstatus für Freigabe und Löschung:

- `reserved`
- `joined`
- `ready`

`playing` wird nicht angefasst.

### Hinweis zur Domain-Invariante

Im normalen Spielfluss sollte gelten:

- `GameRoomPlayer.status = playing` kommt nur in `GameRoom.status = running` vor.

Ein `playing` Player in einem nicht laufenden Raum wäre ein inkonsistenter Zustand und sollte später durch Startlogik, Tests oder Admin-/Repair-Prüfungen verhindert werden.

Der aktuelle Cancellation-Service behandelt diesen Zustand defensiv und löscht solche Player nicht.

---

## Cancelled Rooms und Cleanup

Fachliche Entscheidung:

Abgebrochene Räume sind primär operative Kurzzeitinformationen.

Sie müssen nicht dauerhaft in `game_rooms` aufbewahrt werden, wenn:

- kein Spiel stattgefunden hat,
- keine dauerhafte Spielhistorie entstanden ist,
- alle Reservierungen freigegeben wurden,
- alle relevanten finanziellen Informationen im Ledger erhalten bleiben.

Der Raum kann kurzfristig mit Status `cancelled` bestehen bleiben, damit Lobby oder Admin-UI anzeigen können, warum ein Event nicht stattgefunden hat.

Geplante Kurzzeitregel:

- `cancelled` Räume bleiben etwa 30 Minuten sichtbar oder prüfbar.
- Danach dürfen sie durch Cleanup entfernt werden.
- Ledger-Einträge werden nicht gelöscht.

Aktuell existiert noch kein eigenes Feld:

- `cancelled_at`
- `cancel_reason`

Für den MVP kann zunächst `updated_at` als Cancel-Zeitpunkt verwendet werden, sobald der Status auf `cancelled` gesetzt wird.

Später kann bei Bedarf eine Migration für `cancelled_at` und `cancel_reason` ergänzt werden.

---

## Geplante Cleanup-Wege

Die Cleanup-Logik soll später drei Wege unterstützen.

### 1. CronJob / Scheduler

Primärer Betriebsweg auf dem Hoster.

Mögliche spätere Form:

- Artisan Command für cancelled room cleanup
- Ausführung über Hoster-CronJob
- optional Laravel Scheduler

Beispielhafte Regel:

- lösche `cancelled` Räume
- nur wenn älter als 30 Minuten
- nur wenn keine `GameRoomPlayer` mehr vorhanden sind
- lösche keine Ledger-Einträge

### 2. Fallback ohne CronJob

Zusätzlich soll ein opportunistischer Cleanup möglich sein.

Mögliche Trigger:

- Lobby-Aufruf
- Room-Browser-API
- Admin-Dashboard

Wichtig:

- gedrosselt ausführen
- nicht bei jedem Request harte Cleanup-Scans
- optional mit Cache-/Lock-Mechanismus
- normale Seitenaufrufe dürfen nicht spürbar langsam werden

### 3. Admin-Aufräumbutton

Zusätzlich soll ein manueller Admin-Button möglich sein.

Regeln:

- nur Admins dürfen ihn verwenden
- nur `cancelled` Räume werden berücksichtigt
- 30-Minuten-Regel muss eingehalten werden
- keine `running` oder `finished` Räume löschen
- keine Räume mit verbliebenen `GameRoomPlayer`-Zeilen löschen
- keine Ledger-Einträge löschen

---

## Logout, Disconnect und laufende Spiele

Laufende Spiele dürfen nicht durch Leave- oder Cleanup-Logik zerstört werden.

Fachliche Linie:

- Expliziter Logout ist ein bewusstes Signal.
- Disconnect oder Browser-Schließen ist kein bewusstes Signal.
- Vor Spielstart können User aus wartenden Räumen entfernt werden.
- In laufenden Spielen bleibt die Teilnahme bestehen.
- Eine KI kann später übernehmen.
- Aktionen müssen später unterscheiden können, ob ein Mensch oder eine KI gehandelt hat.

Ein einzelner Logout oder Disconnect während eines Spiels ist kein Betrugsbeweis.

Regelmäßige Muster können aber verdächtig sein.

Spätere Marker können sein:

- Menschliche Züge
- KI-Züge
- Logout während laufendem Spiel
- Disconnect während laufendem Spiel
- Bot-Übernahme
- Preisrang trotz längerer Bot-Übernahme
- wiederkehrende Abwesenheitsmuster

Diese Marker gehören nicht in den aktuellen Leave- oder Cancellation-Service, sondern später in Presence-, Bot-, Spielaktions- oder Abuse-Signal-Logik.

---

## Nächste geplante Reihenfolge

Die nächsten fachlichen Blöcke sollen in dieser Reihenfolge bearbeitet werden:

### B) Cleanup-Konzept für cancelled rooms

Zuerst als Text/Plan verfeinern.

Inhalte:

- 30-Minuten-Regel
- CronJob
- Fallback ohne CronJob
- Admin-Aufräumbutton
- Kriterien für sichere Löschung
- Umgang mit Ledger-Referenzen
- mögliche Nutzung von `updated_at`
- spätere Option `cancelled_at`

### A) Startphase / StartCoordinator

Danach Startlogik modellieren.

Themen:

- `when_full`
- `scheduled`
- mögliche Startverzögerung von 5 bis 10 Sekunden
- Anwesenheitsprüfung
- Konflikte bei Usern in mehreren wartenden Räumen
- Gewinnerraum und Verliererräume
- automatische Freigabe anderer Reservierungen
- Übergang zu `running`
- spätere Invariante `playing` nur in `running`

Offene Frage:

- Brauchen wir einen neuen Status `starting`?

Tendenz:

- Ja, aber erst nach sauberer Spezifikation.

### C) UI- und Controller-Integration

Danach sichtbare Bedienung.

Mögliche Funktionen:

- Join-Button in der Lobby
- Leave-Button für einzelne Räume
- Button: aus allen wartenden Räumen abmelden
- Admin-Cancel
- Admin-Cleanup
- Flash-/Toast-Rückmeldungen
- Room-Browser-Refresh

UI-Integration soll erst erfolgen, wenn die Services und fachlichen Regeln stabil genug sind.

---

## Bewusst noch nicht umgesetzt

Noch offen:

- Startphase
- `starting` Status
- StartCoordinator
- Konfliktauflösung zwischen mehreren startbereiten Räumen
- Logout-Hook
- Disconnect-Timeout
- Bot-/KI-Übernahme
- Abuse-Marker
- Cleanup-Service
- Cleanup-Command
- Scheduler/Cron-Integration
- opportunistischer Cleanup-Fallback
- Admin-Aufräumbutton
- UI-/Controller-Anbindung für Join, Leave, Cancel und Cleanup
- Browser-Test der späteren UI-Flows

---

## Arbeitsregel für die nächsten Patches

Die nächsten technischen Änderungen sollen weiterhin klein und testbar bleiben.

Empfohlene Reihenfolge:

1. Cleanup-Konzept dokumentieren und danach Service planen.
2. Startphase fachlich spezifizieren.
3. Erst danach Controller/UI integrieren.

Bei Codeänderungen gilt weiterhin:

- zuerst relevante Dateien prüfen
- kleine Service-Patches
- gezielte Tests
- danach Volltest
- keine temporären Dateien committen
