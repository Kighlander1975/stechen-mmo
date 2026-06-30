# Settlement, Prize-Pool-Wallet und Simulation - Umsetzungsplan

Stand: 2026-06-30

Diese Datei dokumentiert die nächsten geplanten Schritte für Settlement, Prize-Pool-Buchhaltung und die spätere Economy-Simulation in Stechen-MMO.

## 1. Zielbild

Für Phase 3 sollen die Economy- und Settlement-Services so vorbereitet werden, dass spätere Spiellogik nur noch die fachlichen Ergebnisse liefert, zum Beispiel Rangliste, Gewinner und Auszahlungsdaten.

Die Services sollen danach nachvollziehbar und idempotent buchen:

- Buy-in-Commit;
- Bruttozufluss in den Preispool;
- Rake-Abzug aus dem Preispool;
- Rake-Gutschrift auf das Rake-Wallet;
- Payout-Abfluss aus dem Preispool;
- Payout-Gutschrift auf User-Wallets.

Die erste Simulation soll fortlaufend laufen und die Wallet-Dynamik über viele Räume hinweg abbilden.

## 2. Wichtige Leitplanken

### 2.1 Aktuelles Verhalten bleibt zunächst unverändert

Die neuen Services werden vorbereitet und getestet, aber noch nicht automatisch in den aktuellen Browser-/Live-Flow eingehängt.

Insbesondere gilt zunächst:

- `GameRoomFinishService` löst noch kein Settlement automatisch aus.
- Der Finish-Button im Browser soll sein aktuelles Verhalten behalten.
- Bestehende sichtbare User-Flows sollen durch die ersten Patches nicht verändert werden.
- Neue Services dürfen von Tests und später von der Simulation direkt aufgerufen werden.

### 2.2 KI und Disconnects sind nicht Teil der ersten Simulation

KI-/Disconnect-Fälle bleiben fachlich wichtig, werden aber erst relevant, wenn die eigentliche Spiellogik weiter implementiert ist.

Für die erste Settlement-/Economy-Simulation gilt:

- nur echte User;
- keine KI-Spieler;
- keine KI-Vertretung;
- keine Disconnect-Auditlogik;
- Ranking wird für die Simulation kontrolliert erzeugt.

### 2.3 Simulation läuft isoliert auf SQLite

Die Simulation darf nicht mit der aktuellen lokalen Entwicklungsdatenbank vermischt werden.

Ziel:

- eigene SQLite-Datenbank für Simulationsläufe;
- keine Nutzung der normalen aktiven Datenbank;
- Markdown-Reports unter `_docs/tests/`;
- optional SQLite-Datei ebenfalls unter `_docs/tests/` oder einem eindeutig lokalen Simulationsordner.

Da `_docs/` in `.gitignore` steht, sind lokale Reports und Simulationsartefakte geschützt.

## 3. Entscheidung: B1 Prize-Pool-Buchungsmodell

Es wird ein echtes zentrales Prize-Pool-Wallet modelliert.

Entscheidung:

- ein allgemeines Prize-Pool-Wallet pro Asset/Currency;
- kein separates Wallet pro Raum;
- Raumbezug über Ledger-Referenzen und Metadaten.

Beispiel für das zentrale Play-Money-Prize-Pool-Wallet:

- `user_id = null`
- `wallet_type = prize_pool`
- `asset_type = PLAY_MONEY`
- `currency_code = ST$`
- `metadata.source = system_prize_pool_wallet`

Der Raumbezug erfolgt über:

- `reference_type = App\Models\GameRoom`
- `reference_id = game_rooms.id`
- `metadata.game_room_id`
- `metadata.game_room_public_code`

## 4. B1-Buchungsfluss

### 4.1 Start eines Raums

Beispiel:

- 2 Spieler;
- Buy-in 1.000 St$;
- Brutto-Pool 2.000 St$;
- Rake 2 Prozent = 40 St$;
- Netto-Pool 1.960 St$.

#### User-Wallets

Pro Spieler:

- User-Wallet DEBIT 1.000;
- Ledger-Typ: `commit`;
- Referenz: GameRoom;
- Metadaten mit GameRoomPlayer, User, Seat und Buy-in.

Diese Buchung existiert aktuell bereits über `commitReservedUnits()`.

#### Prize-Pool-Wallet

Einmal pro Raum:

- Prize-Pool-Wallet CREDIT 2.000;
- Ledger-Typ: `commit`;
- Referenz: GameRoom;
- Metadaten mit Brutto-Pool, Buy-in, Spieleranzahl und Raumkennung.

#### Rake aus Prize-Pool

Für Rake:

- Prize-Pool-Wallet DEBIT 40;
- Ledger-Typ: `rake`;
- Referenz: GameRoom.

Zusätzlich:

- Rake-Wallet CREDIT 40;
- Ledger-Typ: `rake`;
- Referenz: GameRoom.

Danach ist der zentrale Prize-Pool-Saldo um den Netto-Pool erhöht.

### 4.2 Settlement

Beim Settlement wird die Rangliste von der späteren Spiellogik geliefert.

Pro payout-berechtigtem Spieler:

- Prize-Pool-Wallet DEBIT Payoutbetrag;
- User-Wallet CREDIT Payoutbetrag;
- beide Ledger-Einträge mit Typ `payout`;
- beide Ledger-Einträge mit Referenz auf den GameRoom;
- Metadaten mit Rang, Spieler, RoomPlayer, Brutto-Pool, Rake, Netto-Pool und Payoutbetrag.

Empfohlen wird eine Gegenbuchung pro payout-berechtigtem Spieler, nicht nur ein Sammel-Debit. Das ist besser nachvollziehbar und später für Echtgeldfähigkeit sauberer.

## 5. Idempotency und Nachvollziehbarkeit

Alle Economy-Buchungen müssen idempotent sein.

Bereits vorhanden:

- `ledger_entries.idempotency_key` ist unique;
- `WalletService` prüft bestehende Idempotency Keys;
- Wallet-Operationen laufen in Transaktionen;
- Wallets werden per `lockForUpdate()` gelockt.

Geplante Idempotency-Key-Muster:

- `game-room:{roomId}:prize-pool:commit`
- `game-room:{roomId}:prize-pool:rake-debit`
- `game-room:{roomId}:rake-credit`
- `game-room-player:{roomPlayerId}:payout:prize-pool-debit`
- `game-room-player:{roomPlayerId}:payout:user-credit`

Die finalen Namen können bei der Implementierung noch präzisiert werden, müssen aber stabil und eindeutig bleiben.

## 6. Geplante Service-Struktur

### 6.1 WalletService-Erweiterungen

Nicht-invasive Erweiterungen, die bestehendes Verhalten nicht ändern, solange sie nicht aufgerufen werden:

- `getOrCreatePlayMoneyPrizePoolWallet()`
- Methode zum Credit des Prize-Pool-Wallets beim Buy-in-Commit;
- Methode zum Debit des Prize-Pool-Wallets für Rake;
- Methode zum Debit des Prize-Pool-Wallets für Payouts;
- Methode zum Credit von User-Wallets mit Ledger-Typ `payout`.

### 6.2 PrizePoolDistributionService

Rein mathematischer Service ohne Datenbankbuchungen.

Aufgaben:

- Brutto-Pool bestimmen;
- Rake berücksichtigen;
- Netto-Pool bestimmen;
- Auszahlungsverteilung anhand Spieleranzahl und Rangliste berechnen;
- Rundungsreste kontrolliert behandeln;
- sicherstellen, dass Summe der Payouts dem Netto-Pool entspricht.

### 6.3 GameRoomSettlementService

Service für echtes Settlement, aber zunächst noch nicht automatisch in den Browserflow eingebunden.

Möglicher Input:

- GameRoom;
- vollständige Rangliste;
- optional weitere Settlement-Metadaten.

Validierung:

- Raum existiert;
- Raum ist settlementfähig;
- Ranking enthält nur Teilnehmer dieses Raums;
- keine doppelten Spieler;
- keine fehlenden aktiven Spieler;
- Payoutsumme ist korrekt;
- keine Doppelbuchung durch Idempotency.

Buchungen:

- Prize-Pool-Wallet Payout-Debit;
- User-Wallet Payout-Credit;
- Ledger-Metadaten mit Raum, Spieler, Rang und Pooldaten.

### 6.4 SimulationService und SimulationCommand

Späterer lokaler Simulationslauf, nicht als Unit-Test.

Eigenschaften:

- läuft gegen eigene SQLite-Datenbank;
- erzeugt 20 Spieler mit je 10.000 St$;
- nutzt Raumgrößen 2 bis 11;
- nutzt Buy-ins 100 bis 1.000 St$;
- erzeugt keine neuen Spieler während der Simulation;
- reduziert Teilnehmerzahl, wenn Spieler nicht mehr zahlen können;
- stoppt, wenn weniger als 2 Spieler mindestens den Mindest-Buy-in zahlen können;
- schreibt Markdown-Report nach `_docs/tests/`;
- zeigt Fortschritt oder Statusausgaben.

## 7. Simulation: Fortlaufende Economy

Die Simulation ist fortlaufend.

Das bedeutet:

- Walletstände werden über alle Runden hinweg weitergeführt;
- Gewinne und Verluste beeinflussen spätere Teilnahmefähigkeit;
- keine künstliche Wiederauffüllung;
- keine neuen Spieler nach Start;
- Abbruch, wenn kein Raum mit mindestens 2 zahlungsfähigen Spielern mehr möglich ist.

Stop-Kriterium:

- weniger als 2 Spieler haben verfügbare Wallet-Einheiten in Höhe des Mindest-Buy-ins.

Der verfügbare Betrag ist:

- `balance_units - reserved_units`.

## 8. Simulation: Reports

Reports werden lokal erzeugt unter:

- `_docs/tests/`

Dateinamen sollen Zeitstempel enthalten, zum Beispiel:

- `settlement-simulation-max-20260630-183000.md`
- `settlement-simulation-fixed-20260630-184500.md`

Ein Report soll enthalten:

- Datum und Uhrzeit;
- verwendete SQLite-Datenbank;
- Seed;
- Spieleranzahl;
- Startguthaben;
- Buy-in-Bereich;
- Raumgrößen;
- Rake-Regel;
- Anzahl Runden;
- Stop-Grund;
- Endstände aller Wallets;
- Summe Buy-ins;
- Summe Rake;
- Summe Payouts;
- auffällige Abweichungen;
- pro Runde Raumdaten, Ranking und Payouts.

## 9. Fortschrittsanzeige

Für fixe Runden kann ein klassischer ProgressBar genutzt werden.

Für den Maximal-Lauf ist die Gesamtanzahl der Runden vorher unbekannt. Dort sind besser:

- Statusausgabe pro Runde;
- Statusausgabe alle N Runden;
- optional ein Sicherheitslimit mit `--max-rounds`.

Beispielstatus:

- Runde;
- Buy-in;
- Spieleranzahl;
- Spieler mit mindestens Mindest-Buy-in;
- höchstes Wallet;
- niedrigstes Wallet;
- bisheriger Rake;
- bisherige Payoutsumme.

## 10. Vorgeschlagene Patch-Reihenfolge

### Patch 1: Wallet-Grundlagen

Ziel:

- Prize-Pool-Wallet-Methoden im WalletService;
- Payout-Credit-Methode;
- Tests im WalletServiceTest.

Keine Änderung an:

- GameRoomStartCoordinatorService;
- GameRoomFinishService;
- Browserflow;
- Commands.

### Patch 2: Distribution

Ziel:

- PrizePoolDistributionService;
- Unit-Tests für Spieleranzahlen 2 bis 11;
- Buy-ins 100 bis 1.000;
- 2 Prozent Rake über `rake_basis_points = 200`;
- Rundungs- und Summenprüfungen.

### Patch 3: Settlement-Service

Ziel:

- GameRoomSettlementService;
- Feature-Tests;
- Ranking-Validierung;
- Payout-Buchungen;
- Idempotency;
- Nicht-Teilnehmer-Schutz;
- doppelte Ranking-Einträge ablehnen;
- unvollständige Ranglisten ablehnen.

Noch keine automatische Einbindung in den aktuellen FinishService.

### Patch 4: Prize-Pool-Funding für Simulation

Ziel:

- sauberer B1-Funding-Ablauf für Simulationsräume;
- Brutto in Prize-Pool;
- Rake aus Prize-Pool;
- Rake-Wallet-Credit nicht doppelt buchen;
- klare Entscheidung, ob bestehender StartCoordinator erweitert oder ein separater Funding-Service für Simulation genutzt wird.

Vorsicht:

- Solange das echte Verhalten nicht verändert werden soll, darf der aktuelle Live-Startflow nicht ungewollt zusätzliche Buchungen auslösen.

### Patch 5: SQLite-Simulation

Ziel:

- SimulationCommand oder vergleichbarer lokaler Runner;
- eigene SQLite-Datenbank;
- Migrationen auf Simulations-DB;
- fortlaufende Economy;
- Markdown-Report;
- Fortschrittsanzeige.

## 11. Aktuelle Nicht-Ziele

Noch nicht Teil der ersten Umsetzung:

- Echtgeldbetrieb;
- echte steuerliche Auswertung;
- automatische Live-Integration des Settlements;
- KI-/Disconnect-Audit;
- vollständige Spiellogik;
- WebSocket-/Homeserver-Anbindung;
- Änderung des aktuellen Browser-Finish-Verhaltens.

## 12. Sicherheitsregeln für kommende Umsetzung

- Vor jedem Patch `git status --short` prüfen.
- Keine temporären Dateien committen.
- Tests gegen SQLite in-memory laufen lassen.
- Nach Laravel-Codeänderungen `php artisan optimize:clear` erwägen.
- Relevante Tests gezielt ausführen.
- Danach vollständigen Testlauf mit `composer test`, wenn technische Änderungen größer werden.
- Bei Markdown-Änderungen Mojibake prüfen.
- Simulationsergebnisse unter `_docs/` nicht committen.
