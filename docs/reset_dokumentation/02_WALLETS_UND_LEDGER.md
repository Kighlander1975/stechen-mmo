# Wallets und Ledger

## Dokumentstatus

**Reset-Status:** analysiert und dokumentiert
**Implementierungsstatus:** implementiert
**Offene Aufgaben:** vorhanden, bewusst noch nicht umgesetzt
**Maßgebliche Grundlage:** tatsächlicher Laravel-Codebestand vom Juli 2026

---

## 1. Zweck

Dieser Bereich stellt die finanzielle und buchhalterische Grundlage von Stechen-MMO bereit.

Er umfasst insbesondere:

- abstrakte Wallets
- Spielgeldguthaben
- reserviertes Guthaben
- verfügbares Guthaben
- nachvollziehbare Ledger-Buchungen
- transaktionale Wallet-Operationen
- idempotente Buchungen
- Buy-in-Reservierungen
- Freigabe und Commit von Reservierungen
- Gutschriften aus anderen Fachbereichen
- die Grundlage für spätere Rake-, Settlement- und Admin-Buchungen

Das Wallet ist die autoritative Guthabenquelle.

Kontostände dürfen nicht direkt in Controllern oder anderen aufrufenden Komponenten verändert werden. Alle fachlichen Guthabenänderungen müssen über die dafür vorgesehenen Services erfolgen und im Ledger nachvollziehbar bleiben.

---

## 2. Fachliche Abgrenzung

Stechen-MMO verwendet kein Poker-ähnliches Chip-, Einsatz- oder Pot-System innerhalb einzelner Kartenrunden.

Wallet-Bewegungen entstehen nur an klar definierten fachlichen Übergängen, beispielsweise:

- einmalige Gutschriften
- wiederholbare Rewards
- Buy-in-Reservierung
- Freigabe einer Buy-in-Reservierung
- verbindlicher Buy-in-Commit
- Rake-Gutschrift
- Preisgeld- oder Settlement-Buchung
- administrative Korrektur

Die eigentliche Reward-Fachlogik wird separat in folgendem Reset-Baustein dokumentiert:

`docs/reset_dokumentation/03_REWARD_SYSTEM.md`

Die Raum-, Buy-in- und Settlement-Abläufe werden in ihren jeweiligen Reset-Dokumenten vollständig beschrieben. Dieses Dokument behandelt ausschließlich die Wallet- und Ledger-Seite dieser Vorgänge.

---

## 3. Beteiligte Komponenten

Der aktuelle Wallet- und Ledger-Bereich verwendet insbesondere:

### Models

- `app/Models/Wallet.php`
- `app/Models/LedgerEntry.php`

### Services

- `app/Services/WalletService.php`
- indirekt aufrufende Fachservices, insbesondere:
  - `app/Services/RewardService.php`
  - Buy-in- und Raumservices
  - Settlement-Services
  - administrative Buchungs- oder Backfill-Services

### Angrenzende Models

- `app/Models/RewardClaim.php`
- `app/Models/UserRewardState.php`
- `app/Models/RewardPlan.php`

Diese angrenzenden Reward-Modelle gehören fachlich in den Reset-Baustein `03_REWARD_SYSTEM.md`. Für Wallet und Ledger sind sie nur insofern relevant, als Reward-Gutschriften über den zentralen Wallet-Service gebucht und mit Ledger-Einträgen verknüpft werden.

### Datenbank

- Wallet-Tabelle
- Ledger-Tabelle
- Fremdschlüssel und Beziehungen zu Besitzern beziehungsweise Referenzobjekten
- Eindeutigkeitsregeln für idempotente Buchungen

### Tests

Der tatsächliche Build enthält umfangreiche Tests für Wallet-, Ledger-, Reward-, Buy-in-, Raum- und Settlement-Abläufe.

Für diesen Reset-Baustein sind insbesondere Tests relevant, die folgende Eigenschaften absichern:

- Wallet-Erstellung
- Gutschriften
- Reservierungen
- Freigaben
- Commit
- unzureichendes Guthaben
- Ledger-Nachvollziehbarkeit
- Idempotenz
- Schutz vor Doppelbuchungen
- transaktionales Verhalten

Die konkreten Testdateien werden bei Bedarf in einem späteren Testinventur-Schritt separat dokumentiert.

---

## 4. Architekturgrundsätze

### 4.1 Laravel ist autoritativ

Laravel ist die verbindliche Instanz für:

- Guthaben
- Reservierungen
- Ledger-Buchungen
- Buy-in-Entscheidungen
- Rake
- Settlement
- Reward-Gutschriften
- administrative Korrekturen

Ein späterer HomeServer darf keine autoritativen Wallet- oder Ledger-Entscheidungen treffen.

---

### 4.2 Servicezentrierte Buchungslogik

Wallet-Buchungen erfolgen zentral über den `WalletService`.

Controller und andere aufrufende Komponenten dürfen:

- fachliche Eingaben entgegennehmen
- Berechtigungen prüfen
- Services aufrufen
- Ergebnisse zurückgeben

Sie dürfen jedoch keine Wallet-Kontostände direkt verändern.

Dadurch werden:

- Geschäftslogik gebündelt
- Buchungsregeln nicht doppelt implementiert
- Transaktionen zentral kontrolliert
- Ledger-Einträge konsistent erzeugt
- Idempotenz einheitlich umgesetzt

---

### 4.3 Integer statt Float

Beträge werden als ganzzahlige Einheiten gespeichert.

Für Spielgeld gilt aktuell:

```text
1 St$ = 1 Einheit
```

Floats werden für Guthaben, Reservierungen und Buchungsbeträge vermieden.

Damit werden Rundungsfehler und schwer nachvollziehbare Abweichungen verhindert.

---

### 4.4 Transaktionalität

Relevante Wallet-Operationen laufen innerhalb von Datenbanktransaktionen.

Ziel:

- entweder die gesamte fachliche Buchung gelingt,
- oder keine beteiligte Änderung bleibt bestehen.

Das gilt insbesondere für Vorgänge, bei denen gleichzeitig:

- Wallet-Felder verändert,
- Reservierungen angepasst,
- Ledger-Einträge erzeugt,
- Fachobjekte referenziert

werden.

---

### 4.5 Zeilensperren

Für kritische Wallet-Operationen wird das betroffene Wallet beim Schreiben gesperrt.

Dadurch werden konkurrierende Änderungen kontrolliert und typische Race Conditions vermieden, beispielsweise:

- zwei gleichzeitige Reservierungen
- parallele Gutschriften
- mehrfacher Commit derselben Reservierung
- gleichzeitige Freigabe und Commit
- parallele Reward-Claims

Die Sperre wird innerhalb einer möglichst kurzen Datenbanktransaktion gehalten.

---

### 4.6 Idempotenz

Jede fachlich wiederholbare oder durch Retries gefährdete Buchung verwendet einen eindeutigen Idempotenzschlüssel.

Vor einer neuen Buchung wird geprüft, ob eine passende Ledger-Buchung bereits existiert.

Ist die Buchung bereits vorhanden, darf derselbe fachliche Vorgang nicht erneut gutgeschrieben oder belastet werden.

Idempotenz schützt insbesondere vor:

- doppelten Registrierungsboni
- mehrfachen Reward-Gutschriften
- doppelten Buy-in-Reservierungen
- mehrfacher Freigabe
- mehrfachem Commit
- wiederholten Admin- oder Backfill-Aktionen
- Retries nach Netzwerk- oder Prozessfehlern

---

## 5. Walletmodell

Ein Wallet repräsentiert ein Guthabenkonto für einen bestimmten Besitzer und einen bestimmten Asset-Typ.

Der aktuelle Build ist auf ein abstraktes Walletmodell ausgelegt.

### 5.1 Zentrale Werte

Fachlich werden mindestens folgende Werte unterschieden:

- Gesamtguthaben
- reserviertes Guthaben
- verfügbares Guthaben

Die grundlegende Beziehung lautet:

```text
verfügbares Guthaben = Gesamtguthaben - reserviertes Guthaben
```

### 5.2 Gesamtguthaben

Das Gesamtguthaben beschreibt die aktuell vorhandenen Einheiten eines Wallets.

Es enthält auch Beträge, die momentan reserviert sind.

Eine Reservierung reduziert daher nicht sofort das Gesamtguthaben.

---

### 5.3 Reserviertes Guthaben

Reserviertes Guthaben ist bereits für einen konkreten fachlichen Vorgang gebunden, aber noch nicht endgültig abgezogen.

Typischer Anwendungsfall:

- Buy-in eines Spielers in einem wartenden Raum

Der reservierte Betrag steht für andere Buchungen nicht mehr frei zur Verfügung.

---

### 5.4 Verfügbares Guthaben

Verfügbares Guthaben ist der tatsächlich frei nutzbare Betrag.

Es wird aus Gesamtguthaben und reserviertem Guthaben abgeleitet.

Vor einer neuen Reservierung muss geprüft werden, ob das verfügbare Guthaben ausreicht.

---

### 5.5 Asset-Typen

Die Architektur ist auf abstrakte Asset-Typen vorbereitet.

Aktiv verwendet wird aktuell insbesondere:

- `PLAY_MONEY`

Die Spielgeldwährung wird im UI als `St$` beziehungsweise StechenDollar dargestellt.

Eine spätere Echtgeldfähigkeit ist nur architektonisch vorbereitet.

Aktuell nicht implementiert sind insbesondere:

- Echtgeld-Einzahlungen
- Echtgeld-Auszahlungen
- Payment-Provider
- KYC
- AML
- regulatorischer Echtgeldbetrieb

`REAL_MONEY` darf daher nicht als produktiv vorhandene Echtgeldfunktion interpretiert werden.

---

## 6. Ledgermodell

Das Ledger dokumentiert relevante Wallet-Bewegungen und Zustandsänderungen.

Es dient insbesondere:

- der Nachvollziehbarkeit
- der Auditierbarkeit
- der Fehleranalyse
- der Idempotenz
- der Verknüpfung mit Fachvorgängen
- späteren Ökonomie-Auswertungen

Das Ledger ist nicht nur eine optionale Protokollierung.

Es ist Bestandteil der fachlichen Buchungsarchitektur.

---

## 7. Inhalt eines Ledger-Eintrags

Ein Ledger-Eintrag kann insbesondere folgende Informationen enthalten:

- betroffenes Wallet
- Asset-Typ
- Buchungsrichtung
- Betrag
- Kontostand vor der Buchung
- Kontostand nach der Buchung
- reservierter Betrag vor der Buchung
- reservierter Betrag nach der Buchung
- Buchungstyp
- fachliche Referenz
- Referenz-ID
- Idempotenzschlüssel
- Metadaten
- Erstellungszeitpunkt

Nicht jeder Vorgang muss alle optionalen Felder verwenden.

Die gespeicherten Informationen müssen jedoch ausreichen, um die fachliche Wirkung der Buchung nachvollziehen zu können.

---

## 8. Buchungstypen

Der tatsächliche Build verwendet beziehungsweise unterstützt fachlich verschiedene Arten von Wallet- und Ledger-Vorgängen.

Dazu gehören insbesondere:

- Gutschriften
- Balance-Anpassungen
- Buy-in-Reservierungen
- Freigabe von Reservierungen
- Commit von Reservierungen
- Registrierungsbonus
- Daily Reward
- Rake-Gutschrift
- Settlement- oder Preisgeldbuchung
- administrative Anpassung
- Backfill-Buchung

Die abschließende Liste der tatsächlich verwendeten Typkonstanten soll bei einer späteren technischen Referenzinventur direkt aus Code und Datenbankschema übernommen werden.

Für den Reset ist entscheidend:

> Jeder fachlich relevante Typ muss klar benannt, idempotent ausführbar und über das Ledger nachvollziehbar sein.

---

## 9. Gutschriften

Eine Gutschrift erhöht das Gesamtguthaben eines Wallets.

Typische Quellen sind:

- Registrierungsbonus
- Daily Reward
- Preisgeld
- Rückerstattung, sofern ein Betrag bereits belastet wurde
- administrative Gutschrift
- Test- oder Backfill-Buchung
- Rake-Gutschrift auf ein Systemwallet

Eine Gutschrift muss:

1. das richtige Wallet bestimmen,
2. innerhalb einer Transaktion arbeiten,
3. das Wallet sperren,
4. Idempotenz prüfen,
5. den Kontostand ändern,
6. einen Ledger-Eintrag erzeugen,
7. die fachliche Referenz speichern.

---

## 10. Direkte Balance-Anpassung

Der `WalletService` enthält auch eine Operation, mit der ein Wallet auf einen definierten Zielstand gebracht werden kann.

Diese Art der Änderung ist besonders sensibel.

Sie darf nicht als bequemer Ersatz für normale fachliche Buchungen verwendet werden.

Zulässige Anwendungsfälle können beispielsweise sein:

- kontrollierte administrative Korrektur
- Migration oder Backfill
- reproduzierbarer Testaufbau
- definierte Reparatur nach bestätigtem Fehler

Auch eine Balance-Anpassung muss:

- transaktional erfolgen
- idempotent sein
- im Ledger dokumentiert werden
- einen fachlichen Grund besitzen
- nachvollziehbare Vorher-/Nachher-Werte speichern

---

## 11. Buy-in-Reservierung

Bei einer Reservierung wird der Buy-in noch nicht vom Gesamtguthaben abgezogen.

Stattdessen wird der Betrag im Wallet als reserviert markiert.

### 11.1 Voraussetzungen

Vor einer Reservierung muss mindestens geprüft werden:

- Wallet existiert oder wird kontrolliert bereitgestellt
- Asset-Typ ist korrekt
- Betrag ist gültig
- verfügbares Guthaben reicht aus
- fachliche Teilnahme ist zulässig
- der Vorgang wurde noch nicht ausgeführt

Die vollständige Join-Eligibility gehört nicht in den `WalletService`.

Der Wallet-Service entscheidet über die finanzielle Durchführbarkeit, nicht über vollständige Account-, Raum- oder Fairplay-Berechtigung.

---

### 11.2 Wirkung

Bei erfolgreicher Reservierung:

- Gesamtguthaben bleibt unverändert
- reserviertes Guthaben steigt
- verfügbares Guthaben sinkt
- Ledger dokumentiert die Reservierung

Beispiel:

```text
Gesamtguthaben vorher:      1.000 St$
Reserviert vorher:              0 St$
Verfügbar vorher:           1.000 St$

Reservierung:                 250 St$

Gesamtguthaben nachher:     1.000 St$
Reserviert nachher:           250 St$
Verfügbar nachher:            750 St$
```

---

## 12. Freigabe einer Reservierung

Wird der zugrunde liegende Vorgang vor dem verbindlichen Commit beendet, kann die Reservierung freigegeben werden.

Typischer Anwendungsfall:

- Spieler verlässt einen wartenden Raum vor Spielstart
- Raum wird vor Start abgebrochen
- ein anderer Raum startet zuerst und die übrigen wartenden Teilnahmen werden beendet

### Wirkung

Bei erfolgreicher Freigabe:

- Gesamtguthaben bleibt unverändert
- reserviertes Guthaben sinkt
- verfügbares Guthaben steigt
- Ledger dokumentiert die Freigabe

Eine Freigabe darf denselben Betrag nicht mehrfach zurückgeben.

Sie muss deshalb ebenfalls idempotent sein.

---

## 13. Commit einer Reservierung

Beim Commit wird eine bisher nur vorgemerkte Reservierung verbindlich belastet.

Typischer Anwendungsfall:

- Spiel startet
- der reservierte Buy-in wird endgültig übernommen

### Wirkung

Bei erfolgreichem Commit:

- Gesamtguthaben sinkt
- reserviertes Guthaben sinkt
- der Betrag ist endgültig gebucht
- Ledger dokumentiert den Commit

Beispiel:

```text
Gesamtguthaben vorher:      1.000 St$
Reserviert vorher:            250 St$
Verfügbar vorher:             750 St$

Commit:                       250 St$

Gesamtguthaben nachher:       750 St$
Reserviert nachher:             0 St$
Verfügbar nachher:            750 St$
```

Der Commit muss atomar mit den fachlich zusammengehörenden Spielstart-Buchungen ausgeführt werden.

Die vollständige Spielstart- und Preispool-Logik wird in den späteren Reset-Bausteinen dokumentiert.

---

## 14. Rake-Wallet und Systemwallets

Die Wallet-Architektur unterstützt nicht nur Spielerwallets.

Auch System-, Rake-, Event- oder Administrationskonten können als Wallets abgebildet werden.

Rake darf nicht verschwinden oder nur als berechnetes Feld ohne Gegenbuchung bestehen.

Wenn Rake entsteht, muss er:

- eindeutig berechnet werden
- auf ein vorgesehenes Zielwallet gebucht werden
- im Ledger nachvollziehbar sein
- mit Raum, Spiel oder Settlement referenziert werden
- idempotent sein

Die genaue Rake-Berechnung und der Spielstartzeitpunkt werden in den Reset-Dokumenten zu Räumen, Spielzyklus und Settlement behandelt.

---

## 15. Wallet-Erstellung

Ein Benutzer benötigt für Spielgeldvorgänge ein passendes Wallet.

Die Wallet-Erstellung wird kontrolliert über die Servicearchitektur beziehungsweise die aufrufenden Fachservices ausgelöst.

Im Registrierungsablauf wird der Registrierungsbonus über den `RewardService` vergeben. Dieser nutzt die Wallet- und Ledger-Logik, wodurch bei Bedarf das erforderliche Spielgeldwallet bereitgestellt und die Gutschrift nachvollziehbar gebucht wird.

Damit wird die im Registrierungsdokument beschriebene Wallet-Erzeugung fachlich bestätigt, aber technisch präzisiert:

> Das Wallet wird nicht direkt im Registrierungscontroller manipuliert. Die Einrichtung und Gutschrift laufen über Reward- und Wallet-Service.

Diese Präzisierung kann später bei Bedarf in `01_REGISTRIERUNG.md` ergänzt werden.

---

## 16. Schnittstelle zum Reward-System

Das Reward-System entscheidet fachlich:

- ob ein Reward gewährt werden darf
- welcher Reward-Typ vorliegt
- welcher Betrag gilt
- welcher Streak- oder Planstatus gilt
- welche fachliche Idempotenz notwendig ist

Der `WalletService` entscheidet dagegen:

- welches Wallet gebucht wird
- ob die Wallet-Operation technisch zulässig ist
- wie Guthaben und Reservierungen verändert werden
- welcher Ledger-Eintrag entsteht
- ob dieselbe Buchung bereits ausgeführt wurde

Diese Trennung verhindert, dass Reward-Fachlogik in die Wallet-Domäne wandert.

Die vollständige Reward-Dokumentation folgt in:

`docs/reset_dokumentation/03_REWARD_SYSTEM.md`

---

## 17. Schnittstelle zu Räumen und Buy-ins

Raum- und Buy-in-Services entscheiden fachlich:

- ob ein Spieler beitreten darf
- ob der Raum offen ist
- ob Plätze frei sind
- ob parallele Teilnahmen zulässig sind
- welche Reservierung zu welchem Raum gehört
- wann eine Reservierung freigegeben oder committed wird

Der Wallet-Service entscheidet nur über die finanzielle Operation.

Dadurch bleibt die Verantwortlichkeit getrennt:

```text
Raum-/Buy-in-Domäne
    entscheidet über den fachlichen Lifecycle

Wallet-Domäne
    führt die finanzielle Zustandsänderung aus

Ledger
    dokumentiert die Wirkung
```

---

## 18. Schnittstelle zum Settlement

Settlement entscheidet fachlich:

- welche Ergebnisse gelten
- welcher Preispool verteilt wird
- welche Teilnehmer Auszahlungen erhalten
- welcher Rake oder Restbetrag berücksichtigt wird
- ob der Vorgang bereits abgeschlossen wurde

Wallet und Ledger führen die daraus entstehenden Gutschriften und Belastungen aus.

Settlement muss:

- transaktional
- idempotent
- auditierbar
- referenzierbar

bleiben.

Die vollständige Settlement-Logik wird in folgendem Reset-Baustein dokumentiert:

`docs/reset_dokumentation/07_SETTLEMENT_UND_PRIZE_DISTRIBUTION.md`

---

## 19. Fehler- und Konsistenzverhalten

Bei einer fehlgeschlagenen Wallet-Operation darf kein inkonsistenter Teilzustand zurückbleiben.

Beispiele unzulässiger Teilzustände:

- Wallet geändert, aber kein Ledger-Eintrag
- Ledger-Eintrag vorhanden, aber Wallet nicht geändert
- Reservierung erhöht, obwohl der Raumbeitritt fehlgeschlagen ist
- Buy-in committed, aber Spielstart zurückgerollt
- RewardClaim erfolgreich, aber keine Gutschrift
- Gutschrift erfolgt, aber fachlicher Claim fehlt

Deshalb müssen fachlich zusammengehörende Datenänderungen möglichst in einer gemeinsamen Transaktion ausgeführt werden.

Bei Fehlern gilt:

1. Ursache analysieren
2. keine Folgeänderung auf Verdacht
3. Idempotenzstatus prüfen
4. Ledger und Fachreferenz vergleichen
5. gezielte Reparatur entwerfen
6. Reparatur ebenfalls über dokumentierte Buchungswege durchführen

Direkte SQL-Korrekturen ohne Ledger sind grundsätzlich zu vermeiden.

---

## 20. Auditierbarkeit

Wallet- und Ledger-Daten bilden eine zentrale Grundlage für:

- Support
- Fehleranalyse
- Missbrauchsanalyse
- Admin-Prüfungen
- Ökonomie-Auswertung
- Settlement-Nachweis
- Reward-Nachweis
- spätere regulatorische Bewertung

Dafür müssen relevante Buchungen mindestens beantworten können:

- welches Wallet war betroffen?
- welcher Betrag wurde bewegt?
- welcher Zustand bestand vorher?
- welcher Zustand bestand danach?
- welcher Buchungstyp lag vor?
- welcher fachliche Vorgang war die Ursache?
- wurde der Vorgang bereits zuvor ausgeführt?
- wann wurde die Buchung erstellt?

---

## 21. Sicherheitsgrenzen

Wallet- und Ledger-Funktionen gehören zu den kritischen Projektbereichen.

Daher gelten insbesondere:

- keine direkten Controller-Buchungen
- keine ungeschützten Admin-Anpassungen
- keine ungeprüften Datenbankänderungen
- keine Floats für Beträge
- keine Buchung ohne nachvollziehbaren Typ
- keine wiederholbare Operation ohne Idempotenz
- keine konkurrierende Änderung ohne Transaktions- und Sperrkonzept
- keine Echtgeldinterpretation des aktuellen Spielgeldsystems
- keine Wallet-Autorität im HomeServer

Änderungen an Datenmodell, Migrationen oder Kernbuchungslogik benötigen vor Umsetzung eine ausdrückliche Bestätigung.

---

## 22. Implementierter Ist-Stand

Auf Basis des geprüften Codes ist bestätigt:

- abstraktes Walletmodell vorhanden
- Ledger-Modell vorhanden
- zentrale Buchungslogik im `WalletService`
- Gutschriften über Service
- Balance-Anpassungen über Service
- Reservierung von Guthaben
- Freigabe von Reservierungen
- Commit von Reservierungen
- Rake-Gutschrift über Wallet-/Ledger-Weg
- Datenbanktransaktionen
- Wallet-Sperren bei kritischen Änderungen
- Idempotenz über eindeutige Schlüssel
- Ledger-Einträge mit Vorher-/Nachher-Zuständen
- Integration mit dem Reward-System
- Grundlage für Buy-in, Rake und Settlement
- keine direkte Wallet-Manipulation im Registrierungscontroller

---

## 23. Bewusst nicht vollständig in diesem Dokument beschrieben

Folgende Bereiche sind angrenzend, werden aber separat dokumentiert:

### Reward-System

- Registrierungsbonus
- Daily Claims
- Reward-Pläne
- Streaks
- Reward-Tage
- Reward-Backfill
- Reward-Adminfunktionen

Dokument:

`03_REWARD_SYSTEM.md`

### Lobby und Raumbeitritt

- Join-Eligibility
- Mehrfachanmeldungen in wartenden Räumen
- Raumstatus
- Lobby-Anzeige
- Startmodi

Dokumente:

- `04_LOBBY.md`
- `05_SPIELRAEUME.md`

### Spielstart und Lifecycle

- Übernahme der führenden Raumteilnahme
- Freigabe anderer Reservierungen
- Commit der Buy-ins
- Rake-Berechnung
- Preispool-Bildung

Dokument:

`06_SPIELZYKLUS.md`

### Settlement

- Ergebnisverarbeitung
- Preisgeldverteilung
- Abschlussbuchungen
- Idempotenz des Settlements

Dokument:

`07_SETTLEMENT_UND_PRIZE_DISTRIBUTION.md`

---

## 24. Noch offene Anforderungen und Prüfungen

Trotz des implementierten Wallet-/Ledger-Fundaments bleiben Punkte offen.

### 24.1 Vollständige technische Referenz

Später sollte eine kompakte technische Referenz ergänzt werden mit:

- tatsächlich vorhandenen Spalten
- tatsächlich verwendeten Buchungstypen
- tatsächlich verwendeten Asset-Codes
- eindeutigen Indizes
- Fremdschlüsseln
- Service-Methoden
- zugehörigen Tests

Diese Referenz muss direkt aus Migrationen, Models und Tests abgeleitet werden.

---

### 24.2 Admin-Anpassungen

Für administrative Guthabenänderungen ist dauerhaft sicherzustellen:

- ausreichende Permission-Prüfung
- verpflichtender Grund
- handelnder Admin
- Empfänger
- Betrag
- Zeitpunkt
- Ledger-Referenz
- Idempotenz
- Auditierbarkeit

Direkte Änderungen an Wallet-Feldern sind nicht zulässig.

---

### 24.3 Reparatur- und Reconciliation-Konzept

Vor externen oder umfangreicheren Tests sollte geklärt werden, wie Inkonsistenzen systematisch erkannt werden.

Mögliche spätere Bausteine:

- Wallet-/Ledger-Reconciliation-Command
- Prüfung von Ledger-Endsaldo gegen Wallet
- Prüfung negativer Reservierungen
- Prüfung `reserved > balance`
- Prüfung verwaister Fachreferenzen
- Prüfung mehrfacher Idempotenzschlüssel
- kontrollierter Reparaturworkflow

---

### 24.4 Echtgeldfähigkeit

Die abstrakte Architektur darf eine spätere Echtgeldprüfung nicht verhindern.

Vor einer Echtgeldumsetzung sind jedoch separate Entscheidungen zwingend:

- rechtliche Zulässigkeit
- Lizenzierung
- Zahlungsdienstleister
- KYC und AML
- Trennung von Kundengeldern
- Auszahlungen
- Chargebacks
- Betrugsprävention
- Buchhaltung
- Steuer
- Datenschutz
- Aufbewahrung
- Incident Management

Aktuell bleibt das System ein Spielgeldsystem.

---

### 24.5 Datenaufbewahrung

Für Ledger-Daten sind später klare Regeln erforderlich:

- Aufbewahrungsdauer
- Löschbarkeit
- Pseudonymisierung
- Umgang mit gelöschten Accounts
- Export für Support oder Auskunft
- Schutz vor nachträglicher Manipulation

Diese Entscheidung ist kritisch und vor einer produktiven Echtgeld- oder Zahlungsfunktion zwingend zu treffen.

---

## 25. Statusübersicht

| Bereich | Status |
|---|---|
| Walletmodell | Implementiert |
| Spielgeldwallet | Implementiert |
| Abstrakter Asset-Ansatz | Implementiert beziehungsweise vorbereitet |
| Gesamtguthaben | Implementiert |
| Reserviertes Guthaben | Implementiert |
| Verfügbares Guthaben | Aus Gesamt- und Reservierungswert ableitbar |
| Ledger-Modell | Implementiert |
| Servicezentrierte Buchungen | Implementiert |
| Gutschriften | Implementiert |
| Balance-Anpassung | Implementiert |
| Buy-in-Reservierung | Implementiert |
| Reservierungsfreigabe | Implementiert |
| Reservierungs-Commit | Implementiert |
| Rake-Gutschrift | Implementiert |
| Datenbanktransaktionen | Implementiert |
| Wallet-Sperren | Implementiert |
| Idempotenz | Implementiert |
| Reward-Integration | Implementiert |
| Settlement-Grundlage | Implementiert |
| Admin-Auditierung | Teilweise beziehungsweise angrenzend umgesetzt |
| Reconciliation-Werkzeug | Noch zu prüfen beziehungsweise nicht dokumentiert |
| Echtgeldbetrieb | Nicht implementiert |
| Payment-Provider | Nicht implementiert |
| KYC/AML | Nicht implementiert |

---

## 26. Spätere Umsetzungstasks

Nach Abschluss der vollständigen Reset-Dokumentation entstehen für Wallet und Ledger mindestens folgende Prüf- oder Umsetzungstasks:

### Technische Referenz

- Wallet-Migrationen vollständig inventarisieren
- Ledger-Migrationen vollständig inventarisieren
- Indizes und Constraints dokumentieren
- alle Buchungstypen aus dem Code erfassen
- alle Asset-Codes erfassen
- alle WalletService-Methoden referenzieren
- zugehörige Tests zuordnen

### Konsistenz und Betrieb

- Reconciliation-Konzept entwerfen
- Diagnose-Command für Wallet-/Ledger-Abweichungen prüfen
- Monitoring für fehlgeschlagene Buchungen planen
- negative oder unmögliche Zustände absichern
- kontrollierten Reparaturworkflow dokumentieren

### Administration

- Permission-Modell für Wallet-Korrekturen prüfen
- Pflichtgrund für Admin-Buchungen sicherstellen
- Admin und Empfänger im Ledger referenzieren
- Admin-UI und Audit-Ansicht prüfen
- Export- und Filtermöglichkeiten später planen

### Echtgeldperspektive

Erst nach ausdrücklicher fachlicher, rechtlicher und technischer Entscheidung:

- Asset-Trennung finalisieren
- Payment-Architektur entwerfen
- regulatorische Anforderungen dokumentieren
- Einzahlung und Auszahlung modellieren
- KYC-/AML-Prozesse planen
- Kundengeldtrennung prüfen
- unveränderliche Audit-Anforderungen bestimmen

---

## 27. Definition of Done für diesen Reset-Block

Dieser Dokumentationsblock gilt als abgeschlossen, wenn:

- das tatsächliche Walletmodell dokumentiert ist
- Gesamt-, reserviertes und verfügbares Guthaben klar getrennt sind
- die servicezentrierte Buchungslogik dokumentiert ist
- direkte Controller-Buchungen ausdrücklich ausgeschlossen sind
- Ledger und Auditierbarkeit beschrieben sind
- Transaktionen und Wallet-Sperren dokumentiert sind
- Idempotenz als implementierter Bestandteil dokumentiert ist
- Reservierung, Freigabe und Commit fachlich getrennt sind
- die Schnittstellen zu Reward, Buy-in, Rake und Settlement beschrieben sind
- Echtgeldfähigkeit klar von aktuellem Spielgeldbetrieb getrennt ist
- offene Anforderungen sichtbar als offen gekennzeichnet sind
- noch keine vorgezogene Implementierung begonnen wurde
- Reward-Fachlogik nicht unnötig aus `03_REWARD_SYSTEM.md` vorweggenommen wird

Diese Bedingungen sind mit dem aktuellen Dokument erfüllt.

---

## 28. Nächster Reset-Baustein

Als Nächstes folgt:

`docs/reset_dokumentation/03_REWARD_SYSTEM.md`

Vor dessen Erstellung werden die vorhandenen Reward-Dokumente sowie die relevanten Models, Services, Controller, Commands, Migrationen und Tests erneut atomar anhand des tatsächlichen Codes geprüft.
