# Phase 3: Wallet, Buy-in, Preispool und Lobby

Stand: Juni 2026  
Status: Vorläufige Planungs- und Umsetzungsgrundlage

## 1. Ziel der Phase

Phase 3 legt die technische und spielerische Grundlage für:

- abstrakte Wallets;
- Spielgeldbetrieb mit `St$` / StechenDollar;
- spätere Echtgeldfähigkeit über getrennte Wallets;
- Buy-in-Reservierungen;
- Preispool-Bildung;
- Rake-Berechnung;
- öffentliche Lobby;
- Räume mit unterschiedlichen Startmodi;
- Ranglisten-/Rating-Vorbereitung.

Phase 3 baut noch nicht die vollständige Karten-, Stich-, Ansage- und Punkte-Engine. Sie schafft aber die Grundlage, damit Spieler später sauber und nachvollziehbar in Spiele einsteigen können.

## 2. Zentrale fachliche Abgrenzung

Für Stechen-MMO gilt ausdrücklich:

> Es gibt keine Chips, Chipstacks oder laufenden Einsätze innerhalb einzelner Runden, Stiche oder Ansagen.

Wirtschaftliche Bewegungen entstehen nur an klaren Spiel-Lifecycle-Punkten:

- Raumbeitritt;
- Buy-in-Reservierung;
- Verlassen vor Spielstart;
- Spielstart;
- Spielabbruch vor Start;
- Spielende;
- Preisgeldverteilung.

Das System darf daher nicht wie Poker mit Blinds, Antes, Bets, Calls, Raises oder laufenden Pots modelliert werden.

## 3. Währung und Asset-Codes

### 3.1 UI-Währung

Die Spielgeldwährung heißt:

- Symbol: `St$`
- Name: `StechenDollar`

Beispiele im UI:

- Guthaben: `1.000 St$`
- Buy-in: `50 St$`
- Preispool: `440 St$`
- Rake: `10 St$`

### 3.2 Technische Asset-Codes

Technisch werden zwei abstrakte Asset-Typen vorgesehen:

- `PLAY_MONEY`
- `REAL_MONEY`

Für Phase 3 aktiv:

- `PLAY_MONEY`

Für später vorbereitet:

- `REAL_MONEY`

## 4. Abstraktes Wallet-System

Das Wallet-System wird bewusst abstrakt gebaut.

Grund:

- Im späteren ManagedServer sollen Spieler zwei getrennte Wallets führen können:
  - `PLAY_MONEY` Wallet;
  - `REAL_MONEY` Wallet.

Es soll kein hartes Feld wie `users.play_money_balance` als zentrale Guthabenquelle verwendet werden.

Stattdessen wird eine Wallet-/Ledger-Struktur vorbereitet:

- `wallets`
- `ledger_entries`

Wallets sollen perspektivisch nicht nur für Nutzer existieren können, sondern auch für System-, Rake-, Event- oder Admin-Konten.

## 5. Wallet-Grundmodell

Ein Wallet braucht mindestens:

- Owner;
- Asset-Typ;
- Gesamtguthaben;
- reserviertes Guthaben.

Fachliche Bedeutung:

- `balance` = Gesamtguthaben;
- `reserved_balance` = gebundener Betrag;
- `available_balance` = `balance - reserved_balance`.

Beträge werden immer als Integer gespeichert.

Es werden keine Floats für Guthaben, Buy-ins, Preispools oder Rake-Beträge verwendet.

Für `St$` gilt zunächst:

- `1 St$ = 1 Einheit`.

## 6. Ledger-Grundmodell

Alle relevanten Guthabenbewegungen müssen nachvollziehbar im Ledger landen.

Mögliche Buchungstypen:

- `registration_grant`
- `daily_grant`
- `buy_in_reserved`
- `buy_in_released`
- `buy_in_committed`
- `rake_collected`
- `prize_awarded`
- `game_cancelled_refund`
- `admin_adjustment`

Ledger-Einträge sollten speichern:

- `wallet_id`
- `asset_type`
- `direction`
- `amount`
- `balance_before`
- `balance_after`
- `reserved_before`
- `reserved_after`
- `type`
- `reference_type`
- `reference_id`
- `idempotency_key`
- `meta`
- `created_at`

Wichtig:

> Controller dürfen Kontostände nicht direkt verändern.

Stattdessen sollen Services genutzt werden, zum Beispiel:

- `WalletService`
- `BuyInService`
- `RakeService`
- `PrizePoolService`

## 7. Buy-in-Lifecycle

### 7.1 Beitritt vor Spielstart

Wenn ein Spieler einem offenen Raum beitritt:

1. System prüft Authentifizierung.
2. System prüft verfügbares Guthaben.
3. System prüft Raumstatus.
4. System prüft freie Plätze.
5. System prüft, ob der Spieler bereits in einem aktiven Raum oder Spiel ist.
6. Buy-in wird reserviert.
7. Spieler wird dem Raum hinzugefügt.

Buchung:

- `buy_in_reserved`

### 7.2 Verlassen vor Spielstart

Wenn ein Spieler vor Spielstart aussteigt:

- Buy-in-Reservierung wird freigegeben.

Buchung:

- `buy_in_released`

Es wird kein Rake erhoben.

### 7.3 Spielstart

Beim tatsächlichen Spielstart:

- reservierte Buy-ins werden verbindlich;
- Preispool wird gebildet;
- Rake wird berechnet und gebucht;
- Teilnehmerfeld wird geschlossen.

Buchungen:

- `buy_in_committed`
- `rake_collected`

### 7.4 Kein Spielstart

Wenn ein Spiel vor Start abgebrochen oder abgesagt wird:

- Buy-ins werden vollständig freigegeben oder erstattet;
- es wird kein Rake erhoben;
- Spieler werden für andere Räume und Spiele freigegeben.

## 8. Kein Late Registration nach Spielstart

Nach Spielstart können keine neuen Spieler mehr beitreten.

Das gilt für alle Spieltypen.

Begründung:

- Preispool soll eindeutig nachvollziehbar bleiben;
- Statistik soll eindeutig nachvollziehbar bleiben;
- Rangliste soll eindeutig nachvollziehbar bleiben;
- Spielverlauf soll eindeutig nachvollziehbar bleiben.

## 9. Startmodi für Räume

Es gibt mindestens zwei Startmodi.

### 9.1 Start wenn voll

Vergleichbar mit Sit'n'Go:

- `start_mode = when_full`

Regel:

- Spiel startet, sobald die maximale Spieleranzahl erreicht ist.

Teilnehmerfeld:

- Mit Spielstart geschlossen.

### 9.2 Feste Startzeit

Zeitgeplantes Spiel:

- `start_mode = scheduled`

Beispiel:

- Start: 20:00 Uhr;
- Min Spieler: 3;
- Max Spieler: 11.

Regel:

- Bis zur Startzeit können Spieler beitreten, solange Plätze frei sind.
- Zur Startzeit startet das Spiel, wenn mindestens `min_players` erreicht ist.
- Wenn `min_players` nicht erreicht ist, wird das Spiel cancelled.

Beispiel:

- Start: 20:00 Uhr;
- Max Spieler: 11;
- Min Spieler: 3;
- Aktuell: 2 Spieler.

Ergebnis:

- Game cancelled;
- Buy-in wird freigegeben oder erstattet;
- kein Rake;
- Spieler werden für andere Räume und Spiele freigegeben.

## 10. Spieleranzahl und Filter

Stechen-MMO soll Spiele mit unterschiedlicher Spielerzahl unterstützen:

- 2 bis 11 Mitspieler.

Die Lobby soll gezielt danach filtern können.

Mögliche Filter:

- Spieleranzahl;
- Buy-in;
- Startmodus;
- freie Plätze;
- Startzeit;
- Status.

Für Phase 3 wichtig:

- Spieleranzahl;
- Buy-in;
- Startmodus.

## 11. Räume und Status

Mögliche Raumstatus:

- `open`
- `locked`
- `running`
- `finished`
- `cancelled`

Bedeutung:

- `open` = Beitritt/Verlassen möglich;
- `locked` = Start wird vorbereitet, keine freien Änderungen mehr;
- `running` = Spiel läuft;
- `finished` = Spiel beendet;
- `cancelled` = Spiel abgesagt oder abgebrochen.

Wichtig:

- Join ist nur bei `open` erlaubt.

## 12. Preispool

Der Preispool entsteht beim Spielstart aus den verbindlich teilnehmenden Buy-ins.

Grundformel:

- `gross_prize_pool = Summe aller Buy-ins`
- `rake_amount = berechneter Rake`
- `net_prize_pool = gross_prize_pool - rake_amount`

Nach Spielstart bleibt das Teilnehmerfeld geschlossen.

Dadurch bleiben Preispool, Spielverlauf, Statistik und Ranglistenwertung sauber nachvollziehbar.

## 13. Rake

Rake ist ein zentraler Bestandteil der Economy.

### 13.1 Rake im Spielgeldbetrieb

Auch im `PLAY_MONEY`-Betrieb soll Rake existieren.

Nutzung:

- Extra-Aktionen;
- Rundungs-Pool;
- Spezialaktionen;
- Ökonomie-Steuerung.

Dafür soll ein besonderes Konto existieren, zum Beispiel:

- Rake-Wallet;
- System-Wallet;
- Admin-/Event-Wallet.

Wichtig:

> Rake verschwindet nie. Rake wird immer nachvollziehbar gebucht.

### 13.2 Rake im späteren Echtgeldbetrieb

Für `REAL_MONEY` später:

- Kosten des Betreibers;
- Profit für Bereitstellung des Services;
- Spezialaktionen.

Phase 3 implementiert aber ausdrücklich:

- keine Echtgeld-Einzahlung;
- keine Echtgeld-Auszahlung;
- keinen Payment-Provider;
- keinen echten Echtgeldbetrieb.

## 14. Rake-Berechnung

Rake soll buy-in-abhängig sein.

Richtwert:

- 2 % bis 4 %

Staffelung:

- 0,2 %-Schritte

Beispiele:

- 2,0 %
- 2,2 %
- 2,4 %
- 2,6 %
- 2,8 %
- 3,0 %
- 3,2 %
- 3,4 %
- 3,6 %
- 3,8 %
- 4,0 %

Je höher das Buy-in, desto eher Richtung 4 %.

Rundung:

- immer zugunsten der Spieler.

Technisch bedeutet das für Rake:

- abrunden.

Beispiel:

- Brutto-Preispool: 333 St$
- Rake: 2,4 %
- Berechnet: 7,992 St$
- Gebucht: 7 St$
- Netto-Preispool: 326 St$

Technisch sinnvoll:

- `basis_points`

Beispiele:

- 2,0 % = 200 basis points;
- 2,4 % = 240 basis points;
- 4,0 % = 400 basis points.

Berechnung:

- `rake = floor(amount * basis_points / 10000)`

## 15. Kein Rake ohne Spielstart

Rake wird nur erhoben, wenn ein Spiel tatsächlich startet.

Kein Rake bei:

- Raumbeitritt;
- Buy-in-Reservierung;
- Verlassen vor Start;
- Raumabbruch vor Start;
- Scheduled Cancellation wegen zu wenig Spielern.

Rake entsteht erst beim Übergang:

- `open/locked -> running`

## 16. Rake-Konfiguration und Admin-Bereich

Die Rake-Konfiguration soll perspektivisch über einen speziellen Admin-Bereich verwaltet werden.

Grund:

- Die Spielgeld-Ökonomie muss beobachtet und gesteuert werden.

Der Admin-Bereich soll später helfen bei:

- Rake-Staffeln;
- asset-spezifischen Regeln;
- spieltyp-spezifischen Regeln;
- System-/Rake-Wallets;
- Ökonomie-Kennzahlen;
- Ledger-Auswertung;
- Spezialaktionen;
- Rundungs-Pool.

Rake-Änderungen sollten auditierbar sein.

Wichtig:

> Der konkret verwendete Rake-Wert muss am Spiel oder Raum gespeichert werden, damit spätere Konfigurationsänderungen alte Spiele nicht verändern.

## 17. Satellites

Später sind Satellite-Spiele gewünscht.

Dabei muss Buy-in/Rake anders betrachtet werden.

Ziel:

- Satellite-Gewinner erhalten Eintritt oder Ticket für ein Zielspiel.

Dabei muss das Buy-in bereits so kalkuliert sein, dass:

- das Zielspiel-Buy-in korrekt gedeckt ist;
- Rake bereits berücksichtigt wird.

Daraus folgt:

- Buy-in-, Rake- und Preispool-Logik dürfen nicht hart in Controllern stehen.

Stattdessen sollen Services genutzt werden, zum Beispiel:

- `PricingService`
- `RakeService`
- `PrizePoolService`

Phase 3 muss Satellites noch nicht vollständig implementieren, aber die Architektur darf sie nicht verhindern.

## 18. Kostenlose Räume

Kostenlose Räume sind gewünscht.

Eigenschaften:

- `buy_in_amount = 0`
- kein Preispool;
- kein Rake;
- Training/Test/Einstieg;
- KI-Spieler möglich.

Kostenlose Räume dienen:

- Entwicklung;
- Regeltests;
- KI-Tests;
- Onboarding;
- Training;
- Vorbereitung der Closed Beta.

## 19. KI-Spieler

KI-Spieler sollen bereits während der Entwicklung definiert und genutzt werden können.

Vor allem für:

- kostenlose Räume;
- Tests;
- Training;
- Closed-Beta-Vorbereitung.

KI-Spieler können an Spielen teilnehmen, zählen aber nicht als menschliche Spieler.

Für die spätere Datenmodellierung wichtig:

- Teilnehmermodell sollte Menschen und KI unterscheiden können.

## 20. Rangliste und Wertung

Ranglisten/Wertungen gelten nicht nur für kostenlose Räume, sondern auch für Spielgeldspiele.

Also:

- kostenlose Spiele können wertungsfähig sein;
- `PLAY_MONEY`-Spiele können wertungsfähig sein.

Die Wertungsfähigkeit hängt nicht am Buy-in, sondern an der Teilnehmerstruktur und Spielregel.

## 21. Mindestanforderung für Wertung

Ein Spiel ist nur wertungsfähig, wenn mindestens drei menschliche Spieler teilnehmen.

Regel:

- `human_player_count >= 3`

KI-Spieler zählen nicht als menschliche Spieler.

## 22. Beispiele für Wertung mit KI

### Beispiel 1

Konstellation:

- Max Spieler: 2;
- 1 echter Spieler;
- 1 KI-Spieler.

Ergebnis:

- Spiel möglich;
- keine Wertung.

### Beispiel 2

Konstellation:

- Max Spieler: 4;
- 2 echte Spieler;
- 2 KI-Spieler.

Ergebnis:

- Spiel möglich;
- keine Wertung.

### Beispiel 3

Konstellation:

- Max Spieler: 4;
- 3 echte Spieler;
- 1 KI-Spieler.

Ergebnis:

- Spiel möglich;
- Wertung möglich;
- KI-Spieler fällt aus der Wertung;
- menschliche Spieler rücken nach.

Beispiel Gesamtergebnis:

1. KI
2. Spieler A
3. Spieler B
4. Spieler C

Ranglistenwertung:

1. Spieler A
2. Spieler B
3. Spieler C

## 23. Rating-/Elo-Ziel

Langfristig soll eine Elo- oder Ratingwertung entstehen.

Ziele:

- Rangliste;
- Spielstärke-Bewertung;
- Raumfilter nach Wertung;
- Raumerstellung nach Wertungsbereich;
- Matchmaking;
- Einsteiger-/Fortgeschrittenenräume.

Beispiele:

- Einsteigerraum: Rating 0-999;
- Fortgeschritten: Rating 1000-1499;
- Experte: Rating 1500+.

Technisch sollte zunächst neutral von `rating` gesprochen werden, weil später eventuell nicht klassisches 1v1-Elo, sondern eine Multiplayer-taugliche Variante genutzt wird.

Mögliche spätere Systeme:

- Elo mit Multiplayer-Anpassung;
- Glicko;
- Glicko-2;
- TrueSkill-ähnliches System;
- eigene Ratingformel.

## 24. Lobby

Die Lobby soll die Spielräume sichtbar machen.

Mögliche Route:

- `/lobby`

Anzeigen:

- Raumliste;
- Startmodus;
- Startzeit;
- Spieleranzahl;
- freie Plätze;
- Buy-in;
- Rake-Hinweis;
- Preispool-Vorschau;
- eigener Guthabenstand;
- Beitreten/Verlassen;
- Filter.

Beitrittsaktionen müssen serverseitig geprüft werden.

Frontend entscheidet nichts autoritativ.

## 25. Vue-/Blade-Ansatz

Phase 3 kann die bestehende Vue-Insel-Architektur nutzen.

Mögliche Vue-Island:

- `game-lobby`
- `lobby-room-list`

Wichtig:

- Laravel bleibt autoritativ.
- Join/Leave/Start erfolgen per POST mit CSRF.

Für Realtime ist Phase 3 noch nicht zwingend auf Homeserver/WebSocket angewiesen.

Mögliche erste Umsetzung:

- Blade-Seite + Vue-Island + Polling/Refresh.

## 26. Sicherheit und Transaktionen

Wichtige Regeln:

- Nur eingeloggte Nutzer können Räumen beitreten.
- Join/Leave/Start sind POST-Aktionen.
- CSRF ist Pflicht.
- Server prüft Wallet, Guthaben, Raumstatus und aktive Teilnahme.
- Kein Nutzer darf in mehreren aktiven Räumen oder Spielen gleichzeitig sein.
- Wallet-Änderungen erfolgen in Datenbanktransaktionen.
- Rake/Preispool/Buy-in-Commit muss atomar sein.

Bei Fehlern:

- entweder alle relevanten Buchungen gelingen;
- oder keine.

## 27. Multitabling

Für das MVP gilt:

- kein Multitabling.

Ein Spieler darf nur in einem aktiven Raum oder Spiel gleichzeitig sein.

Aktiv bedeutet mindestens:

- offener Raum mit reserviertem Buy-in;
- locked room;
- running game.

## 28. Tests

Phase 3 braucht Tests für die folgenden Bereiche.

### 28.1 Wallet

- Wallet-Erstellung;
- Startguthaben;
- Reservierung;
- Freigabe;
- Commit;
- Ledger-Buchungen;
- Idempotenz;
- zu wenig Guthaben.

### 28.2 Räume/Lobby

- Lobby sichtbar für eingeloggte Nutzer;
- Gast wird zum Login geleitet;
- Beitritt zu offenem Raum;
- Verlassen vor Start;
- kein Beitritt bei vollem Raum;
- kein Beitritt bei laufendem Raum;
- kein Multitabling;
- Filterlogik.

### 28.3 Scheduled Games

- Start bei erreichter Mindestspielerzahl;
- Cancel bei zu wenig Spielern;
- Buy-in-Freigabe bei Cancel;
- kein Rake bei Cancel.

### 28.4 Sit'n'Go

- Start wenn voll;
- Teilnehmerfeld geschlossen;
- Preispool-Bildung;
- Rake-Buchung.

### 28.5 Rake

- Staffel nach Buy-in;
- Berechnung in basis points;
- Abrundung zugunsten Spieler;
- Buchung auf Rake/System-Wallet;
- kein Rake ohne Spielstart.

### 28.6 Ranking/KI

- keine Wertung unter 3 menschlichen Spielern;
- Wertung ab 3 menschlichen Spielern;
- KI zählt nicht als Mensch;
- KI wird in Wertung übersprungen.

## 29. Nicht-Ziele von Phase 3

Phase 3 implementiert bewusst noch nicht:

- vollständige Kartenlogik;
- vollständige Stichlogik;
- Ansage-/Trumpf-/Punkte-Engine;
- vollständige Preisverteilung nach Spielende;
- echte Echtgeld-Einzahlung;
- echte Echtgeld-Auszahlung;
- Payment-Provider;
- KYC/Compliance;
- vollständiges Elo-System;
- vollständige KI-Spielstrategie;
- vollständiger Homeserver-Realtime-Betrieb;
- Chat;
- private Räume;
- Turniere/Satellites vollständig.

Satellites werden nur architektonisch vorbereitet.

## 30. Offene Entscheidungen

Folgende Punkte müssen vor oder während der Umsetzung noch konkretisiert werden:

- Höhe des Startguthabens in `St$`;
- genaue Buy-in-Stufen für Standardräume;
- genaue Rake-Staffeln pro Buy-in-Bereich;
- ob Rake-Staffeln in Phase 3 bereits aus der Datenbank oder zunächst aus Config kommen;
- ob der Rake-Admin-Bereich direkt in Phase 3 oder in einer späteren Admin-Phase umgesetzt wird;
- Datenmodell für menschliche Spieler und KI-Spieler;
- erster Umfang der Lobby-Vue-Island;
- konkrete Raum-Seed-Daten für Entwicklung und Closed Beta;
- genaue spätere Ratingformel.

## 31. Aktueller Konsens in Kurzform

Phase 3 baut ein abstraktes Wallet-/Ledger-System.

`PLAY_MONEY` ist aktiv, `REAL_MONEY` wird vorbereitet.

Die UI-Währung für `PLAY_MONEY` ist `St$` / StechenDollar.

Buy-ins werden vor Spielstart reserviert. Bei Spielstart werden Buy-ins committed. Dann entstehen Preispool und Rake.

Kein Spielstart bedeutet kein Rake.

Es gibt Sit'n'Go-artige Räume und zeitgeplante Räume. Zeitgeplante Räume starten bei Mindestspielerzahl oder werden cancelled.

Nach Spielstart gibt es keinen Einstieg mehr.

Räume unterstützen 2 bis 11 Spieler.

Lobby-Filter nach Spieleranzahl, Buy-in und Startmodus sind vorgesehen.

Rake ist buy-in-abhängig, 2-4 %, in 0,2 %-Schritten, immer abgerundet.

Rake wird über ein besonderes System-/Rake-Konto geführt.

Rake-Konfiguration soll später adminseitig steuerbar sein.

Kostenlose Räume mit KI sind möglich.

Rangliste gilt auch für Spielgeldspiele.

Wertung ist ab mindestens 3 menschlichen Spielern möglich.

KI zählt nicht für menschliche Wertung und wird übersprungen.

Langfristig soll ein Rating-/Elo-System für Rangliste und Raumzuordnung entstehen.

## 32. Registrierungsbonus, Daily Rewards und Spielberechtigung

Zusätzlich zum Wallet-, Buy-in- und Lobby-Fundament gehört zu Phase 3 ein grundlegendes Reward-System für Spielgeld.

Wichtig ist die fachliche Trennung zwischen:

- vorhandenes Guthaben;
- nutzbares Guthaben;
- Spielberechtigung.

Ein Nutzer kann also bereits `St$` auf dem Wallet haben, ohne deshalb schon Spielräumen beitreten zu dürfen.

### 32.1 Registrierungsbonus

Nach erfolgreicher Account-Erstellung erhält ein neuer Nutzer automatisch einen einmaligen Registrierungsbonus.

Regel:

- Betrag: `1.000 St$`
- Zeitpunkt: direkt nach Account-Erstellung
- Abholung: nicht notwendig
- Häufigkeit: genau einmal pro Account
- Ledger-Typ: `registration_grant`

Der Registrierungsbonus ist eine Gutschrift auf das Spielgeld-Wallet.

Wichtig:

> Die Gutschrift bedeutet nicht automatisch, dass der Nutzer bereits spielen darf.

Die Teilnahme an Spielräumen bleibt zusätzlich an die Spielberechtigung des Accounts gekoppelt.

Mögliche Idempotency-Key-Struktur:

- `reward:registration:user:{user_id}`

### 32.2 Spielberechtigung vor Raumbeitritt

Ein Nutzer darf Spielräumen erst beitreten, wenn die fachlichen Voraussetzungen erfüllt sind.

Mindestens vorgesehen:

- E-Mail-Adresse bestätigt;
- UserDetails vollständig ausgefüllt;
- Account nicht gesperrt;
- Account nicht eingeschränkt;
- Fairplay-/Abuse-Status erlaubt Teilnahme.

Die genaue Definition der UserDetails ist noch offen und wird separat konkretisiert.

Für den späteren `BuyInService` bedeutet das:

- ausreichendes Wallet-Guthaben allein reicht nicht;
- vor der Buy-in-Reservierung muss geprüft werden, ob der Nutzer spielberechtigt ist.

Diese Prüfung soll nicht direkt im `WalletService` liegen.

Mögliche spätere technische Bausteine:

- `PlayerEligibilityService`
- Policy/Gate für Raumbeitritt
- Account-Status-Felder
- UserDetails-Vollständigkeitsprüfung

### 32.3 Daily Claim Bonus

Neben dem Registrierungsbonus soll es einen täglichen Bonus geben.

Der tägliche Bonus wird nicht automatisch beim Login gutgeschrieben.

Regel:

- Der Nutzer muss eingeloggt sein.
- Der Nutzer muss aktiv auf eine Abholen-Aktion klicken.
- Ein Popup/Modal nach dem Login darf auf den Bonus hinweisen.
- Das Modal darf weggeklickt werden.
- Der Bonus kann später im Spieler-Dashboard abgeholt werden.
- Ohne aktive Abholung gibt es keine Gutschrift.

Login ist also nur ein möglicher Hinweiszeitpunkt, aber nicht die eigentliche Auszahlungsaktion.

Möglicher Ledger-Typ:

- `daily_grant`

Mögliche Idempotency-Key-Struktur:

- `reward:daily-login:user:{user_id}:date:{claim_date}`

### 32.4 Daily Claim erst ab dem nächsten Reward-Tag nach Registrierung

Der Tag der Registrierung ist nicht automatisch der erste Daily-Bonus-Tag.

Ein neuer Account soll nicht direkt mit `1.200 St$` starten können.

Regel:

> Am Reward-Tag der Registrierung ist kein Daily Claim möglich.

Der erste Daily Claim ist frühestens am nächsten Reward-Tag nach der Registrierung möglich.

Entscheidend ist dabei nicht ein Mindestabstand von 24 Stunden, sondern der Wechsel des Reward-Tags.

Beispiel 1:

- Registrierung: Montag 10:00
- Reward-Tag: Montag 04:00 bis Dienstag 03:59:59
- erster Daily Claim: Dienstag ab 04:00

Beispiel 2:

- Registrierung: Montag 03:00
- Reward-Tag der Registrierung: Sonntag 04:00 bis Montag 03:59:59
- erster Daily Claim: Montag ab 04:00

Diese technische Finesse ist ausdrücklich erlaubt.

Beispiel 3:

- Registrierung: Montag 04:01
- Reward-Tag der Registrierung: Montag 04:00 bis Dienstag 03:59:59
- erster Daily Claim: Dienstag ab 04:00

Technische Regel:

- `current_claim_date` muss größer sein als `registration_claim_date`.

### 32.5 Reward-Tag und Tageswechsel

Der Daily Claim verwendet keinen gleitenden 24-Stunden-Timer.

Stattdessen gilt ein fester Reward-Tag.

Definition:

- Zeitzone: `Europe/Berlin`
- Tageswechsel: 04:00 lokale Zeit
- Reward-Tag: 04:00 bis 03:59:59 am Folgetag

Damit werden Sommerzeit und Winterzeit über die lokale Zeitzone korrekt berücksichtigt.

Beispiele:

- Claim Montag 03:00
- Claim Montag 04:01

Diese Claims liegen in zwei unterschiedlichen Reward-Tagen und sind daher grundsätzlich beide möglich, sofern alle anderen Bedingungen erfüllt sind.

### 32.6 Daily-Streak und Bonusstaffel

Die Daily-Rewards folgen einer Streak-Logik.

Bonusstaffel:

| Streak-Tag | Bonus |
|---:|---:|
| 1 | 200 St$ |
| 2 | 300 St$ |
| 3 | 400 St$ |
| 4 | 500 St$ |
| 5 | 700 St$ |
| 6 | 850 St$ |
| 7 | 1.000 St$ |
| 8 bis 30 | 1.000 St$ |
| 31 | 5.000 St$ |

Regel:

- Wird jeden Reward-Tag aktiv geclaimt, steigt der Streak.
- Wird ein Reward-Tag ausgelassen, fällt der nächste Claim zurück auf Tag 1.
- Ohne aktive Abholung gibt es keine Gutschrift.
- Bereits geclaimte Reward-Tage können nicht erneut geclaimt werden.

Beispiel:

- Montag Claim
- Dienstag kein Claim
- Mittwoch Claim

Ergebnis:

- Mittwoch ist wieder Streak-Tag 1.

### 32.7 Tag-31-Milestone

Wenn ein Nutzer 30 Daily Claims in Folge durchhält, erhält er am 31. Claim-Tag einen besonderen Bonus.

Regel:

- Tag 31: `5.000 St$`
- danach wird der Streak zurückgesetzt
- der nächste erfolgreiche Daily Claim ist wieder Tag 1

Empfohlene technische Interpretation:

- Nach erfolgreichem Tag-31-Claim wird der gespeicherte Streak-Zähler auf `0` gesetzt.
- Der nächste erlaubte Claim startet wieder mit Streak-Tag `1`.

### 32.8 Daily Claim und Spielberechtigung

Der Registrierungsbonus wird direkt gebucht.

Der Daily Claim soll jedoch stärker geschützt werden, weil er wiederholbar ist.

Empfohlene Regel:

> Daily Claims sind erst erlaubt, wenn der Account spielberechtigt ist.

Das bedeutet mindestens:

- E-Mail bestätigt;
- UserDetails vollständig;
- Account nicht eingeschränkt;
- Account nicht gesperrt.

Dadurch kann ein neuer Nutzer zwar den Registrierungsbonus erhalten, aber keine Daily-Rewards farmen, solange der Account nicht vollständig freigeschaltet ist.

### 32.9 Geplante technische Umsetzung

Das Reward-System soll auditierbar und idempotent umgesetzt werden.

Mögliche Tabellen:

- `reward_claims`
- `user_reward_states`

Mögliche Models:

- `RewardClaim`
- `UserRewardState`

Möglicher Service:

- `RewardService`

Mögliche Service-Methoden:

- `grantRegistrationBonus(User $user)`
- `getDailyClaimStatus(User $user)`
- `claimDailyLoginBonus(User $user)`

Mögliche Felder für `reward_claims`:

- `user_id`
- `reward_type`
- `claim_date`
- `streak_day`
- `amount_units`
- `ledger_entry_id`
- `claimed_at`
- `metadata`

Mögliche Felder für `user_reward_states`:

- `user_id`
- `reward_type`
- `streak_count`
- `last_claim_date`
- `last_claimed_at`

Wichtiger Datenbankschutz:

- Ein Nutzer darf pro Reward-Typ und Claim-Date nur einen Claim erhalten.

Möglicher Unique-Index:

- `user_id`
- `reward_type`
- `claim_date`

Alle Gutschriften laufen über den `WalletService` und erzeugen Ledger-Einträge.

Controller dürfen Rewards nicht direkt auf Wallets buchen.

### 32.10 Backfill für bestehende Accounts und Admin-Auslösung

Bestehende Accounts, die vor Einführung des Reward-Systems erstellt wurden, sollen ihr Startguthaben nachträglich erhalten können.

Diese Nachbuchung darf nicht per direkter Datenbankänderung erfolgen.

Insbesondere verboten:

- direkte Erhöhung von `wallets.balance_units`;
- manuelle SQL-Updates ohne Ledger-Eintrag;
- separate Admin-Logik, die Wallets direkt verändert.

Stattdessen muss die Nachbuchung denselben fachlichen Weg verwenden wie neue Registrierungen.

Umgesetzte technische Bausteine:

- `RewardService`
- `RegistrationBonusBackfillService`
- Artisan Command
- Admin-Read-only-Liste offener Accounts
- Admin-Einzel-Backfill für verifizierte Accounts
- Admin-Bulk-Backfill für alle verifizierten offenen Accounts

Der Artisan Command heißt:

- `rewards:backfill-registration-bonus`

Verfügbare Optionen:

- `--dry-run`: zeigt berechtigte Nutzer an, ohne Rewards zu schreiben;
- `--user-id=`: beschränkt den Backfill auf einen einzelnen Nutzer.

Die zentrale Backfill-Logik prüft für jeden bestehenden Nutzer:

1. Existiert bereits ein Registrierungsbonus-Claim?
2. Existiert bereits eine passende idempotente Ledger-Buchung?
3. Falls ja: Nutzer überspringen.
4. Falls nein: Registrierungsbonus über `RewardService` und `WalletService` buchen.

Dadurch bleiben alle Nachbuchungen:

- auditierbar;
- idempotent;
- über Ledger-Einträge nachvollziehbar;
- wiederholbar ohne Doppelgutschrift.

Admin-Backfill-Regeln:

- Admin-Aktionen arbeiten per `POST`.
- CSRF ist erforderlich.
- Die Admin-Oberfläche nutzt dieselbe Backfill-Service-Logik wie der Artisan Command.
- Die Admin-Liste zeigt Accounts ohne Registrierungsbonus-Claim.
- Verifizierte offene Accounts können einzeln abgefertigt werden.
- Unverifizierte offene Accounts werden nicht gebucht und bleiben sichtbar.
- Der Bulk-Backfill verarbeitet alle verifizierten offenen Accounts.
- Der Bulk-Backfill verarbeitet auch den handelnden Admin, wenn dieser selbst ein verifizierter offener Account ohne Registrierungsbonus ist.
- Bereits versorgte Accounts werden durch Idempotenz nicht doppelt gebucht.
- Controller dürfen keine eigene Direktbuchungslogik enthalten.

Aktueller Umsetzungsstand:

- C1 Read-only-Liste: umgesetzt.
- C2 Einzel-Backfill: umgesetzt.
- C3 Bulk-Backfill: umgesetzt.

Tests decken insbesondere ab:

- Zugriffsschutz für Gäste und Nicht-Admins;
- Anzeige offener Accounts;
- Einzel-Backfill für verifizierte Accounts;
- Blockade unverifizierter Accounts;
- Bulk-Backfill für alle verifizierten offenen Accounts;
- Idempotenz und Schutz vor Doppelgutschriften.

Wichtig:

> Es darf nur eine zentrale Backfill-Logik geben. Artisan Command und Admin-Button dürfen keine redundante Buchungslogik enthalten.

### 32.11 Tests für Rewards

Zusätzlich zu den bestehenden Wallet- und Lobby-Tests werden Tests für Rewards benötigt.

Zu testen:

- Registrierungsbonus wird einmalig gebucht.
- Registrierungsbonus ist idempotent.
- Daily Claim ist am Reward-Tag der Registrierung nicht möglich.
- Daily Claim ist ab dem nächsten Reward-Tag möglich.
- Daily Claim muss aktiv ausgelöst werden.
- Mehrfachclaim am selben Reward-Tag ist nicht möglich.
- Streak steigt bei aufeinanderfolgenden Reward-Tagen.
- Streak fällt auf Tag 1 zurück, wenn ein Reward-Tag ausgelassen wird.
- Tag 31 gibt `5.000 St$`.
- Nach Tag 31 wird der Streak zurückgesetzt.
- Daily Claim ist für nicht spielberechtigte Accounts nicht möglich.
- Ledger-Einträge enthalten passende Typen, Beträge, Idempotency Keys und Referenzen.

### 32.12 Geplanter Daily-Bonus-Prompt

Status: geplant, noch nicht umgesetzt.

Der Daily-Bonus-Prompt ist ein separates UX- und Reward-State-Thema. Er darf den Nutzer auf einen verfügbaren Daily Claim hinweisen, aber niemals automatisch eine Gutschrift auslösen.

Grundregel:

- Der Nutzer muss authentifiziert sein.
- Der aktuelle Daily-Claim-Zyklus wird geprüft.
- Wenn ein neuer Claim-Zyklus verfügbar ist und der Prompt in diesem Zyklus noch nicht angezeigt wurde, darf ein Modal einmalig angezeigt werden.
- Der Einstieg darf über eine beliebige Seite erfolgen.
- Das Modal darf weggeklickt werden.
- Wegklicken bucht keinen Bonus.
- Der Bonus bleibt weiterhin im Spieler-Dashboard aktiv abholbar.
- Ohne aktive Abholen-Aktion gibt es keine Gutschrift.

Dafür wird ein persistenter Status benötigt, der pro Nutzer und Claim-Zyklus festhält, ob der Prompt bereits angezeigt wurde.

Mögliche technische Varianten:

- Erweiterung von `user_reward_states`;
- separate Prompt-/Reward-State-Struktur;
- Speicherung eines `prompt_shown_cycle_key`;
- optional zusätzlich `prompt_dismissed_at`.

Wichtig:

> Der Prompt ist nur ein Hinweis. Die fachliche Claim-Entscheidung bleibt im `RewardService`.

Offene Entscheidung:

- Wird der Daily-Bonus-Prompt noch in Phase 3 umgesetzt?
- Oder wird er als späteres UX-Paket nach dem Reward-/Wallet-Fundament eingeplant?

## 33. Fairplay, Abuse- und Collusion-Erkennung

Mit Wallets, Buy-ins und Rewards entsteht Missbrauchspotenzial.

Phase 3 muss nicht alle Fairplay-Mechanismen vollständig implementieren, soll aber die Architektur darauf vorbereiten.

### 33.1 Grundsatz

Einzelne Signale beweisen keinen Missbrauch.

Beispiel:

- gleiche IP-Adresse;
- wiederholte gemeinsame Spiele;
- auffällige Gewinnverteilung.

Solche Signale sollen zunächst als Risiko- oder Review-Hinweise betrachtet werden.

Sperren oder harte Einschränkungen sollten erst bei belastbaren Mustern oder nach manueller Prüfung erfolgen.

### 33.2 Collusion-Risiken

Mögliche Collusion-Muster:

- Ein Spieler gewinnt auffällig oft mit denselben Mitspielern.
- Bestimmte Mitspieler verlieren auffällig oft an denselben Spieler.
- Spielergruppen treten wiederholt zusammen Räumen bei.
- Neue Accounts verlieren wiederholt Guthaben an denselben Zielaccount.
- Mitspieler treffen auffällig unlogische oder offensichtlich nachteilige Entscheidungen.
- Spiele enden auffällig schnell oder mit ungewöhnlichen Ergebnisverteilungen.
- Guthaben wandert wiederholt von mehreren Accounts zu einem Account.

Diese Muster sollen später anhand von Spiel-, Ergebnis-, Raum- und Wallet-Daten ausgewertet werden.

### 33.3 Gleiche IP-Adresse im selben Raum

Mehrere Spieler mit derselben IP-Adresse in einem Raum sind ein relevantes Risiko-Signal.

Gleiche IP ist aber kein Beweis für Missbrauch.

Legitime Gründe können sein:

- Familie;
- Wohngemeinschaft;
- Büro;
- Schule oder Universität;
- Mobilfunk-Carrier-NAT;
- Hotel-WLAN;
- VPN.

Empfohlene Regel:

- gleiche IP im selben Raum erzeugt ein Risk Signal;
- wiederholte gleiche IP-Konstellationen erhöhen das Risiko;
- auffällige Spiel- oder Ergebnisdaten erhöhen das Risiko weiter;
- erst kombinierte Muster lösen Review, Warnung oder Einschränkung aus.

### 33.4 Mögliche gestufte Reaktion

Eine spätere Fairplay-Logik kann gestuft reagieren.

Mögliche Stufen:

1. Risk Event speichern.
2. Risiko-Score erhöhen.
3. Interne Review-Markierung setzen.
4. Admin-/Moderationsprüfung anstoßen.
5. Warnung aussprechen.
6. Temporäre Einschränkung setzen.
7. Account bei starkem oder bestätigtem Missbrauch sperren.

Dauerhafte Sperren sollten nicht allein auf Basis einer IP-Übereinstimmung erfolgen.

### 33.5 Daten, die später für Fairplay relevant sein können

Mögliche Datenquellen:

- Raumteilnahmen;
- Spielteilnahmen;
- Spielresultate;
- Gewinner und Platzierungen;
- Wallet-Bewegungen;
- Buy-in- und Preisgeldflüsse;
- IP-Adresse bei Login;
- IP-Adresse bei Raumbeitritt;
- User-Agent;
- Session-Daten;
- spätere Spielzug-Historie;
- auffällige Zeitmuster;
- wiederholte gleiche Spielergruppen.

Sobald echte Spielzüge existieren, können zusätzlich geprüft werden:

- auffällig schlechte Entscheidungen;
- wiederholtes absichtliches Verlieren;
- ungewöhnliche Pass-/Ansage-/Spielmuster;
- unplausible Kooperation zwischen Accounts.

### 33.6 Datenschutz und Zugriff

Fairplay- und Abuse-Daten können sensible Informationen enthalten.

Daher gelten folgende Grundsätze:

- nur notwendige Daten speichern;
- Zweck klar dokumentieren;
- Zugriff auf Admin-/Moderationsrollen beschränken;
- Speicherfristen später definieren;
- keine unnötig invasive Fingerprinting-Logik im MVP;
- IP- und Gerätedaten nicht als alleinige Sperrgrundlage verwenden.

## 34. Ergänzter Konsens zu Rewards und Fairplay

Der Registrierungsbonus beträgt `1.000 St$` und wird direkt nach Account-Erstellung automatisch gebucht.

Der Registrierungsbonus macht den Account nicht automatisch spielberechtigt.

Spielräume dürfen erst betreten werden, wenn mindestens E-Mail-Bestätigung und vollständige UserDetails vorliegen und der Account nicht eingeschränkt ist.

Daily Rewards werden nicht automatisch beim Login gebucht.

Daily Rewards müssen aktiv abgeholt werden.

Ein Popup/Modal nach dem Login darf auf den Bonus hinweisen, kann aber weggeklickt werden. Der Claim kann später im Spieler-Dashboard nachgeholt werden.

Der Reward-Tag verwendet `Europe/Berlin` mit Tageswechsel um 04:00 lokale Zeit.

Der Daily Claim ist am Reward-Tag der Registrierung nicht erlaubt.

Der erste Daily Claim ist frühestens ab dem nächsten Reward-Tag nach der Registrierung möglich.

Es gibt keinen Mindestabstand von 24 Stunden zwischen zwei Claims, wenn durch den 04:00-Cutoff bereits ein neuer Reward-Tag begonnen hat.

Wird ein Reward-Tag ausgelassen, fällt der nächste Daily Claim auf Streak-Tag 1 zurück.

Nach 30 erfolgreichen Daily Claims in Folge gibt es am 31. Claim-Tag `5.000 St$`. Danach wird der Streak zurückgesetzt.

Collusion- und Abuse-Erkennung wird architektonisch vorbereitet, aber nicht als harte automatische Sperrlogik in die erste Reward-Implementierung eingebaut.

Gleiche IP-Adressen im selben Raum sind ein Risiko-Signal, aber kein alleiniger Beweis für Missbrauch.

## Hinweis zu `game_room_players.rake_units`

`game_room_players.rake_units` bleibt in Phase 3 vorerst als Kompatibilitäts- und Historienfeld bestehen.

Die aktuelle Phase-3-Logik verwendet room-based Rake:

- Beim Raumbeitritt wird nur das Buy-in des Spielers reserviert.
- Der Player-Level-Rake bleibt für neue Teilnahmen `0`.
- Der tatsächliche Rake wird beim Spielstart raumbasiert berechnet.
- Die Rake-Buchung läuft über das Rake-Wallet und den Ledger.

Vor dem Produktiv-Gehen beziehungsweise vor einer Nicht-Beta-Version soll geprüft werden, ob `game_room_players.rake_units` vollständig entfernt, final migriert oder weiterhin als historisches Kompatibilitätsfeld behalten wird.
