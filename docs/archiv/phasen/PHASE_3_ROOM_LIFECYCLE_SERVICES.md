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

---

## Startprüfung, Countdown und Teilnahmelimits

Dieser Abschnitt hält die fachliche Entscheidung für die spätere Startphase fest.

Die Startphase ist der kritische Übergang zwischen Lobby-/Warteraum-Logik und laufendem Spiel.

Ziel:

- Raum wird startbereit.
- Raum wird für eine kurze Prüfphase blockiert.
- Spieler-Konflikte werden aufgelöst.
- Pro Spieler darf nur ein aktiver/startender/laufender Raum entstehen.
- Wenn alles gültig ist, beginnt das Spiel.
- Wenn zu viele Spieler durch Konflikte wegfallen, wird der Raum zurückgesetzt oder abgebrochen.

---

### Technische Dauer der Prüfung

Die eigentliche technische Prüfung soll kurz sein.

Erwartung für das MVP:

- typischerweise wenige Millisekunden bis deutlich unter eine Sekunde;
- keine absichtlich lange Datenbanktransaktion;
- keine dauerhaften Locks während des Countdowns.

Wichtig:

- Datenbank-Locks dürfen nur innerhalb kurzer Transaktionen gehalten werden.
- Es darf keine Transaktion geben, die wegen des Countdowns 10 Sekunden offen bleibt.
- Die Countdown-Zeit ist UX-, Fairness-, Polling- und Race-Condition-Pufferzeit, nicht Datenbank-Arbeitszeit.

---

### Countdown

Für das MVP wird die Startprüfung mit einem festen Countdown modelliert.

Beschlossene MVP-Regel:

- Countdown: 10 Sekunden

Ablauf:

1. Raum erreicht Startbereitschaft.
2. Raum wechselt in den späteren Status `starting`.
3. `starting_at` wird gesetzt.
4. `starts_at` wird gesetzt.
5. Die UI zeigt den Countdown an.
6. Nach Ablauf wird die Startfinalisierung ausgeführt.

Beispiel:

- `starting_at`: Zeitpunkt, an dem der Raum den Startanspruch bekommt.
- `starts_at`: geplanter Zeitpunkt, an dem der Raum finalisiert und gestartet wird.

`starting_at` dient der Konfliktentscheidung.

`starts_at` dient der UI, dem Polling und der Finalisierung.

---

### Kein langes Locking während des Countdowns

Der Countdown darf nicht durch eine offene Datenbanktransaktion umgesetzt werden.

Nicht erlaubt:

- Raum locken;
- 10 Sekunden warten;
- danach weiterarbeiten.

Stattdessen:

1. Kurze Transaktion für Startanforderung:
   - Raum locken;
   - Startfähigkeit prüfen;
   - Status auf `starting` setzen;
   - `starting_at` setzen;
   - `starts_at` setzen;
   - Transaktion beenden.

2. Countdown läuft ohne Datenbank-Lock.

3. Kurze Transaktion für Startfinalisierung:
   - Raum erneut locken;
   - Teilnehmer und Konflikträume prüfen;
   - Konflikte auflösen;
   - Reservierungen anderer Räume freigeben;
   - Buy-ins des Gewinnerraums finalisieren;
   - Raum auf `running` setzen oder zurücksetzen/abbrechen.

---

### Gewinnerregel bei konkurrierenden Starts

Ein Spieler darf gleichzeitig in mehreren wartenden Räumen angemeldet sein.

Aber:

- Ein Spieler darf maximal in einem aktiven/startenden/laufenden Raum landen.

Wenn mehrere Räume zeitnah starten und denselben Spieler enthalten, gewinnt der Raum mit dem ältesten Startanspruch.

Regel:

1. Älteres `starting_at` gewinnt.
2. Bei gleichem `starting_at` gewinnt die kleinere `game_rooms.id`.

Kurzform:

- Gewinnerraum: `ORDER BY starting_at ASC, id ASC`

Der Gewinnerraum behält den Spieler.

Verliererräume verlieren den Spieler:

- Reservierung wird freigegeben.
- `GameRoomPlayer`-Zeile wird entfernt.
- Raumstatus wird abhängig vom Startmodus neu bewertet.

---

### Verhalten bei Verliererräumen

Wenn ein Raum durch Konfliktauflösung Spieler verliert, muss geprüft werden, ob er noch startfähig ist.

Für `when_full`:

- Wenn nicht mehr genug Spieler vorhanden sind, geht der Raum zurück in einen wartenden Zustand.
- Typischer Zielstatus: `open`
- Reservierungen der entfernten Spieler werden freigegeben.
- Verbleibende Teilnehmer behalten ihre Reservierung.

Für `scheduled`:

- Wenn nach Konfliktauflösung zu wenig Spieler vorhanden sind, fällt das geplante Event aus.
- Typischer Zielstatus: `cancelled`
- Grund: `scheduled_too_few_players`
- Reservierungen werden freigegeben.

---

### Teilnahmelimits pro Spieler

Um Abuse, unnötige Reservierungen und teure Konfliktauflösungen zu vermeiden, wird die Anzahl gleichzeitiger Anmeldungen begrenzt.

Beschlossene MVP-Regeln:

- maximal 3 wartende Räume pro Spieler;
- maximal 1 aktiver/startender/laufender Raum pro Spieler.

Wartend bedeutet:

- Raumstatus: `open` oder `full`;
- Playerstatus: `reserved`, `joined` oder `ready`.

Aktiv blockierend bedeutet:

- Raumstatus: `starting` oder `running`.

Wenn ein Spieler bereits in einem `starting` oder `running` Raum ist, darf er keinem weiteren Raum beitreten.

Wenn ein Spieler bereits in 3 wartenden Räumen angemeldet ist, darf er keinem weiteren wartenden Raum beitreten.

Ausnahme:

- Ein idempotenter erneuter Join in denselben Raum muss weiterhin möglich bleiben und darf nicht durch das Limit blockiert werden.

---

### Warum das Limit nötig ist

Die Wallet-Reservierung begrenzt zwar bereits indirekt, wie viele Räume ein Spieler betreten kann.

Das reicht aber nicht als alleiniger Schutz.

Probleme ohne Limit:

- Spieler könnten sehr viele günstige Räume blockieren.
- Startkonflikte würden unnötig teuer.
- Viele Reservierungs- und Freigabe-Ledger-Einträge würden entstehen.
- Lobby-Zustände könnten künstlich verzerrt werden.
- Ein Spieler kann ohnehin nur ein Spiel aktiv spielen.

Das MVP-Limit von 3 wartenden Räumen ist ein bewusster Sweet Spot:

- genug Flexibilität;
- überschaubare Konfliktauflösung;
- geringer Abuse-Spielraum.

---

### Spätere Config

Die Werte sollen später zentral konfigurierbar sein.

Geplante Datei:

- `config/game_rooms.php`

Geplante Werte:

- `starting_countdown_seconds`: 10
- `max_waiting_rooms_per_player`: 3
- `max_active_rooms_per_player`: 1

Die Config soll später von folgenden Stellen verwendet werden:

- `GameRoomEligibilityService`
- `GameRoomJoinService`
- `GameRoomStartCoordinator`
- Lobby-/Room-Browser-Payload
- UI-Countdown

---

### UI-Countdown

Die spätere Lobby- oder Raum-UI soll bei `starting` einen Countdown anzeigen.

Dafür sollte die Payload mindestens enthalten:

- Raumstatus;
- `starting_at`;
- `starts_at`;
- aktuelle Serverzeit.

Die aktuelle Serverzeit ist sinnvoll, damit der Client den Countdown trotz lokaler Uhrabweichung korrekt berechnen kann.

Beispielhafte Payload-Felder:

- `status`
- `startingAt`
- `startsAt`
- `serverNow`

Der Client kann daraus die verbleibende Zeit berechnen und zwischen Polling-Intervallen lokal herunterzählen.

---

### Offene technische Umsetzung

Noch umzusetzen:

- neuer Raumstatus `starting`;
- Migration für `starting_at`;
- Migration für `starts_at`;
- Config-Datei `config/game_rooms.php`;
- Erweiterung von `GameRoomEligibilityService`;
- Erweiterung von `GameRoomJoinService`;
- `GameRoomStartCoordinator`;
- Startfinalisierung nach Ablauf des Countdowns;
- Konfliktauflösung nach `starting_at`;
- Buy-in-Capture beim tatsächlichen Start;
- UI-/Payload-Erweiterung für Countdown;
- Tests für Teilnahmelimits, Startkonflikte und Countdown-Status.

