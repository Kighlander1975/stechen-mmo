# stechen-mmo — MVP-Konzept

Stand: Juni 2026  
Status: Erste konsolidierte Konzeptfassung

---

## 1. Ziel des Dokuments

Dieses Dokument beschreibt den aktuellen Konzeptstand für das Projekt `stechen-mmo`.

Es fasst die bisher getroffenen Grundentscheidungen zusammen und dient als Arbeitsgrundlage für die erste technische Umsetzung.

Das Projekt wird zunächst als Spielgeld-Plattform umgesetzt. Die Architektur soll jedoch so vorbereitet werden, dass sie später auch geldspielnahe Anforderungen erfüllen kann.

---

## 2. Grundprinzip

`stechen-mmo` wird technisch so konzipiert, als wäre es ein Echtgeldspiel, auch wenn im MVP kein echtes Geld eingesetzt wird.

Daraus folgen folgende Grundprinzipien:

- serverautoritatives Spielsystem
- keine vertrauenswürdige Clientlogik
- registrierte Benutzerkonten für aktive Spielteilnahme
- Wallet- und Ledger-Struktur für Spielgeld
- Buy-in-, Preispool- und Rake-Logik vorbereitet
- vollständige Nachvollziehbarkeit von Spiel- und Kontobewegungen
- Auditierbarkeit wichtiger Aktionen
- Schutz vor Manipulation, Doppelaktionen und Race Conditions
- kein Echtgeld im MVP
- keine Auszahlungen im MVP
- rechtliche Prüfung vor jeder Einführung von Echtgeld oder geldwerten Vorteilen

Der Client darf nur Aktionen anfragen. Die Entscheidung, ob eine Aktion gültig ist und welche Folgen sie hat, liegt ausschließlich beim Server.

---

## 3. Nutzerrollen und Zugriffsrechte

### 3.1 Besucher ohne Login

Besucher ohne Login dürfen:

- Regeln lesen
- öffentliche Spiele beobachten
- Login aufrufen
- Registrierung aufrufen
- Passwort-vergessen-Funktion nutzen

Besucher ohne Login dürfen nicht:

- aktiv spielen
- ohne temporären Nickname chatten
- Tisch-Chat lesen
- Tisch-Chat schreiben
- Wallet- oder Chipfunktionen nutzen
- spielrelevante Aktionen ausführen

---

### 3.2 Gast mit temporärem Nickname

Gäste mit temporärem Nickname dürfen:

- öffentliche Spiele beobachten
- die allgemeine Lobby betreten
- in der allgemeinen Lobby chatten

Gäste mit temporärem Nickname dürfen nicht:

- an Spielen teilnehmen
- an fremden Tischen kommentieren
- Tisch-Chat lesen
- Tisch-Chat schreiben
- Wallet- oder Chipfunktionen nutzen
- spielrelevante Aktionen ausführen

Ein Gast darf ein Spiel nur passiv beobachten.

---

### 3.3 Registrierter Nutzer

Registrierte und eingeloggte Nutzer dürfen:

- spielen
- Spielgeld bzw. Chips verwenden
- an Spielräumen teilnehmen
- die globale Lobby nutzen
- eigene Statistiken einsehen
- öffentliche Spiele beobachten

Registrierte Nutzer dürfen nicht:

- an fremden Tischen kommentieren
- fremden Tisch-Chat lesen
- fremden Tisch-Chat schreiben
- an mehr als einem laufenden Spiel gleichzeitig teilnehmen

Multitabling ist im MVP ausgeschlossen.

---

## 4. Gast-Nicknames

Gäste können einen temporären Nickname wählen.

Vor Nutzung eines Gast-Nicknames muss geprüft werden:

- existiert der Name bereits als registrierter Benutzername?
- wird der Name aktuell von einem anderen Gast verwendet?
- ist der Name reserviert oder gesperrt?
- ähnelt der Name einem Admin-, Moderator- oder Systemnamen?
- enthält der Name unerlaubte Zeichen oder Begriffe?

Definitive Namen aus der Datenbank dürfen von Gästen nicht verwendet werden.

Gast-Nicknames sind zeitlich begrenzt.

Geplante Regeln:

- Timeout verlängert sich bei Aktivität
- Maximaldauer z. B. 2 Stunden
- optional IP- oder Session-Prüfung
- nach Ablauf wird der Nickname freigegeben

Ziel ist der Schutz vor Identitätsmissbrauch und Phishing.

---

## 5. Chat-System

Das Chat-System wird in verschiedene Bereiche getrennt.

### 5.1 Globale Lobby

Im MVP gibt es zunächst eine globale Lobby.

Die globale Lobby darf genutzt werden von:

- Gästen mit temporärem Nickname
- registrierten Nutzern
- Moderatoren
- Administratoren

Später können bei höherer Auslastung dynamisch mehrere Lobbys erzeugt werden.

Da das MVP zunächst auf Shared Hosting laufen soll, muss ein Lastschutz vorgesehen werden.

Mögliche Maßnahmen:

- Rate Limits
- Cooldowns
- Warteschlange
- maximale Nachrichtenfrequenz

---

### 5.2 Lokaler Tisch-Chat

Jeder Tisch kann einen lokalen Tisch-Chat haben.

Zugriff auf den Tisch-Chat haben nur:

- aktive Spieler an diesem Tisch
- ggf. Moderation/Admins zu Prüfzwecken

Nicht zugriffsberechtigt sind:

- Gäste
- Zuschauer
- registrierte Nutzer, die nicht am Tisch sitzen

Zuschauer dürfen den Tisch-Chat nicht lesen und nicht schreiben.

---

### 5.3 Turnier-Chat

Ein Turnier-Chat ist für eine spätere Turnierfunktion vorgesehen.

Eigenschaften:

- turnierübergreifender Chat
- nur für Teilnehmer dieses Turniers
- getrennt von Tisch-Chats
- keine Schreibrechte an Tischen, an denen man nicht sitzt

---

### 5.4 Link- und Phishing-Schutz

Direkt klickbare Links im Chat sollen verboten werden.

Geplante Schutzmaßnahmen:

- Chat-Ausgabe immer escapen
- keine HTML-Ausgabe aus Nutzereingaben
- linkähnliche Strukturen erkennen
- Links blockieren oder neutralisieren
- keine automatisch klickbaren URLs
- Schutz vor Admin-/Moderator-Imitation

Beispiele für zu prüfende Muster:

- `http://`
- `https://`
- `www.`
- typische Kurzlink-Dienste
- Discord-/Telegram-/Messenger-Links
- offensichtliche Domainstrukturen

Die Prüfung darf nicht so tief werden, dass der Chat dadurch blockiert oder stark verlangsamt wird.

---

## 6. Zuschauer-Modus

Öffentliche Spiele dürfen grundsätzlich beobachtet werden.

Zuschauer dürfen:

- öffentliche Spiele beobachten
- nach Spielern suchen
- öffentliche Tischinformationen sehen
- möglichst in Echtzeit zuschauen

Zuschauer dürfen nicht:

- verdeckte Karten sehen
- Spielerhände sehen
- Tisch-Chat sehen
- kommentieren
- in das Spiel eingreifen

Öffentliche Informationen können sein:

- Spielraum
- Spieler bzw. Nicknames
- Spielphase
- öffentlicher Punktestand
- ausgespielte Karten, sobald öffentlich
- Ergebnis nach Spielende
- Spielstatistik nach Spielende

Private oder nicht beobachtbare Spiele sind im MVP vorerst nicht vorgesehen.

Ein Zuschauer-Delay kann später als Anti-Cheat-Maßnahme geprüft werden, ist aber nicht MVP-kritisch.

---

## 7. Spielstatistiken

Nach Spielende wird eine Statistik erzeugt.

Diese kann enthalten:

- Spielnummer
- Teilnehmer
- Platzierungen
- Punkte
- korrekte Tipps
- Tipphöhen
- Spielstärke-Werte
- Ranglistenwerte
- ggf. später Replay- oder Analyseinformationen

Spielstatistiken sollen grundsätzlich öffentlich auffindbar sein, damit Spielstärken vergleichbar werden.

Datenschutz ist zu beachten.

Öffentliche Statistiken sollen keine sensiblen Accountdaten enthalten. Die Anzeige erfolgt über Nicknames oder geeignete öffentliche Spielernamen.

---

## 8. Account- und Spielgeldsystem

Aktive Spielteilnahme erfordert ein registriertes Konto.

Bei Registrierung erhält ein Spieler ein Startguthaben in Spielgeld bzw. Chips.

Spielgeld:

- hat keinen Echtgeldwert
- kann im MVP nicht ausgezahlt werden
- dient als Einsatzmittel für Spielräume
- wird über Wallet und Ledger nachvollziehbar verwaltet

---

### 8.1 Tägliche Gutschrift

Eine tägliche Gutschrift ist vorgesehen, aber nicht passiv.

Regeln:

- Spieler muss die Gutschrift aktiv abrufen
- kein Vermögensaufbau durch Abwesenheit
- Gutschrift nur bis zu einem bestimmten Kontostand möglich
- ggf. nur ab oder unter bestimmten Kontoständen möglich
- genaue Schwellenwerte werden später definiert

Ziel:

Spielgeld soll Teilnahme ermöglichen, aber nicht unbegrenzt passiv anwachsen.

---

## 9. Wallet, Ledger, Rake und Systemkonten

Spielgeldbewegungen werden nicht als einfache Kontostandsänderungen modelliert, sondern über nachvollziehbare Buchungen.

Benötigte Strukturen:

- User-Wallet
- Admin-/Rake-Wallet
- ggf. System-Wallet
- ggf. Tisch-/Preispool-Wallet
- Ledger-Einträge
- Buchungstypen
- Transaktionsreferenzen

Mögliche Buchungstypen:

- Registrierungsbonus
- tägliche aktive Gutschrift
- Buy-in reserviert
- Buy-in freigegeben
- Buy-in in Preispool überführt
- Buy-in zurückerstattet
- Rake entnommen
- Preisgeld ausgezahlt
- Abbruch-Rückerstattung
- Admin-Korrektur

---

### 9.1 Rake

Vom Preispool wird eine Rake entnommen.

Geplante Regeln:

- Rake: 2 bis 4 Prozent
- Entnahme bei Spielbeginn
- nur bei tatsächlich gestarteten Spielen
- Rundung immer zugunsten der Spieler
- Rake wird auf ein separates Admin-/Rake-Konto gebucht

Die Rake darf nicht einfach verschwinden. Sie muss als Ledger-Buchung nachvollziehbar sein.

---

## 10. Spielräume

Das System soll Spielräume automatisch erstellen können.

Ein Spielraum kann haben:

- Buy-in
- maximale Spieleranzahl
- feste Spieleranzahl
- Mindestspieleranzahl
- Startzeit
- Modus
- Status
- Preispool-Regel
- Rake-Regel

Die automatische Raumerstellung kann abhängig sein von:

- im Umlauf befindlichem Spielgeldvermögen
- Nutzungslogs
- Auslastung
- Tageszeiten
- gefüllten Räumen

Wenn ein Raum voll ist, können automatisch weitere Räume erstellt werden.

Private Räume sind im MVP vorerst ausgeschlossen.

Später denkbar:

- private Ligen
- eigene Liga-Räume durch Spieler
- Turniermodus

---

## 11. Raumtypen und Spieleranzahl

Technisch möglich sind bis zu 11 Spieler pro Spiel.

Geplante Raumtypen:

- Heads-Up
- gecappte Räume
- Räume mit fester Spieleranzahl
- Sit'n'Go-Räume
- Räume mit fixer Startzeit
- Räume mit Mindestspieleranzahl
- unterschiedliche Buy-in-Stufen

Multitabling ist im MVP ausgeschlossen.

Ein Spieler kann nur an einem laufenden Spiel teilnehmen.

---

## 12. Buy-in und Preispool

Ablauf:

1. Spieler tritt einem Raum bei.
2. System prüft Wallet/Kontostand.
3. Buy-in wird zunächst reserviert.
4. Vor Spielstart kann der Spieler aussteigen.
5. Bei Ausstieg vor Start wird der Buy-in vollständig zurückgegeben.
6. Bei Spielstart wird der Buy-in in den Preispool überführt.
7. Rake wird bei Spielbeginn entnommen.
8. Ab Spielbeginn können Spieler nicht mehr frei aussteigen.

Rake wird nur bei gestarteten Spielen entnommen.

Rundungen erfolgen immer zugunsten der Spieler.

---

## 13. Preisverteilung

Die aktuelle MVP-Preisverteilung ist in einer eigenen Detaildatei festgelegt:

- `docs/settlement-prize-distribution-planning.md`

Kurzfassung:

- Auszahlungen erfolgen aus dem Netto-Preispool.
- Für die MVP-Phase gilt eine fixe Rake von `2 %`.
- Rake wird immer abgerundet zugunsten der Spieler.
- Wertende Buy-in-Räume mit mehr als einem bezahlten Platz müssen mindestens `10 St$` Buy-in haben.
- Kostenlose Räume sind davon ausgenommen und folgen eigenen Trainings-/Testregeln.
- Bei mehr als einem bezahlten Platz muss der letzte bezahlte Platz mindestens das `1,3`-fache des Brutto-Buy-ins erhalten.
- KI-Spieler erhalten keine Gewinne.
- Wenn eine KI einen disconnected echten Spieler vertritt, bleibt die Auszahlung dem ursprünglichen echten Spieler zugeordnet und der Fall wird für spätere Abuse-Prüfung markiert.

Aktuelle MVP-Auszahlungstabelle:

| Spieleranzahl | Bezahlte Plätze | Platz 1 | Platz 2 | Platz 3 | Platz 4 |
|---:|---:|---:|---:|---:|---:|
| 2 | 1 | 100 % | - | - | - |
| 3 | 1 | 100 % | - | - | - |
| 4 | 1 | 100 % | - | - | - |
| 5 | 2 | 68 % | 32 % | - | - |
| 6 | 2 | 70 % | 30 % | - | - |
| 7 | 2 | 72 % | 28 % | - | - |
| 8 | 3 | 52 % | 30 % | 18 % | - |
| 9 | 3 | 54 % | 29 % | 17 % | - |
| 10 | 4 | 43 % | 27 % | 16 % | 14 % |
| 11 | 4 | 42 % | 27 % | 18 % | 13 % |

Rundungsregel:

| Rundungsrest | Verteilung |
|---:|---|
| 0 St$ | keine Zusatzverteilung |
| 1 St$ | +1 St$ an den letzten bezahlten Platz |
| 2 St$ | +1 St$ an den letzten und +1 St$ an den vorletzten bezahlten Platz |
| 3 St$ | +2 St$ an den letzten und +1 St$ an den vorletzten bezahlten Platz |

Simulationsstand:

| Kennzahl | Wert |
|---|---:|
| Geprüfte Spielerzahlen | 2 bis 11 |
| Geprüfte Buy-ins | 1 bis 500 St$ |
| Maximaler Rundungsrest | 3 St$ |
| Maximal benötigter RakePool-Ausgleich | 0 St$ |
| Fälle mit Rundungsrest > 4 St$ | 0 |
| Fälle mit RakePool-Ausgleich > 4 St$ | 0 |

---

## 14. Gleichstand und Tiebreaker

Da ein Spiel über mehrere Spielrunden geht, werden Gleichstände über Statistiken aufgelöst.

Tiebreaker-Reihenfolge:

1. normale Platzierung bzw. Punkte
2. häufiger korrekt getippt
3. Tipphöhen als weiteres Kriterium
4. falls weiterhin Gleichstand: verbleibender Preispool wird geteilt

Falls beim Teilen halbe Chips oder ungerade Restwerte entstehen, können diese aus dem Rake-Pool ausgeglichen werden.

Ziel ist eine faire Auszahlung ohne Nachteil für Spieler.

---

## 15. Spielstruktur

Es gelten folgende Begriffsdefinitionen:

### Spiel

Ein Spiel ist die gesamte Partie.

Ein Spiel beginnt, wenn alle Spieler bei 0 Punkten starten, und endet, wenn nach mehreren Spielrunden ein Gewinner bzw. eine Endplatzierung feststeht.

### Spielrunde

Ein Spiel besteht aus mehreren Spielrunden.

Eine Spielrunde ist ein vollständiger Durchlauf mit neu verteilten Karten bzw. definierter Kartenanzahl.

Kein Spieler kann in der ersten Spielrunde bereits das Gesamtziel erreichen. Das ist technisch nicht möglich.

### Spielzug

Eine Spielrunde besteht aus mehreren Spielzügen.

Ein Spielzug ist der einzelne Moment, in dem Spieler Karten legen bzw. Aktionen ausführen.

---

## 16. Kartenanzahl und Spielzüge pro Runde

Die Anzahl der Spielzüge pro Spielrunde hängt von der Spieleranzahl ab.

| Spieleranzahl | Karten pro Spieler | Spielzüge pro Runde | Besonderheit |
|---:|---:|---:|---|
| bis 6 Spieler | 9 Karten | 9 Spielzüge | letzter Spielzug automatisch |
| 7–11 Spieler | 7 Karten | 7 Spielzüge | letzter Spielzug automatisch |

Der letzte Spielzug einer Spielrunde ist immer automatisch.

Das ergibt sich aus den Spielregeln und dem Spielablauf, weil am Ende keine echte Entscheidung mehr möglich ist.

---

## 17. Vorauswahl

Wenn ein Spieler noch nicht an der Reihe ist, aber bereits sieht, was im aktuellen Stack bzw. Stich liegt, darf er eine Vorauswahl treffen.

Regeln:

- Vorauswahl nur für den aktuellen Spielzug
- keine mehreren Züge im Voraus
- keine Aktionsketten
- keine automatische Strategie-Sequenz
- Vorauswahl wird erst beim tatsächlichen Zug serverseitig validiert
- bei Reconnect wird eine vorhandene Vorauswahl gelöscht

Die Vorauswahl ist nur eine Absichtserklärung.

Beim tatsächlichen Zug prüft der Server:

- ist der Spieler jetzt am Zug?
- gehört die Karte noch dem Spieler?
- ist die Karte nach aktueller Lage gültig?
- ist die Vorauswahl noch aktuell?
- ist der Spieler nicht disconnected oder gesperrt?
- ist die Spielrunde noch im gleichen Spielzug?

Nur wenn alles gültig ist, wird die Vorauswahl ausgeführt.

---

## 18. Zugzeit, Timebank und Inaktivität

Jeder Spieler erhält pro Zug eine feste Grundzeit.

Beispiel:

- 15 Sekunden pro Zug

Zusätzlich erhält jeder Spieler eine persönliche Timebank.

Eigenschaften:

- zu Beginn für alle Spieler gleich
- wird genutzt, wenn die normale Zugzeit abläuft
- kann sich nach bestimmten Spielabschnitten leicht erhöhen
- z. B. nach 5, 10 oder 15 gespielten Runden
- darf nicht unbegrenzt wachsen

Wenn ein Spieler online ist, aber nicht handelt:

1. normale Zugzeit läuft ab
2. Timebank läuft ab
3. System/KI wählt eine gültige Karte
4. Karte wird automatisch gespielt
5. Spiel läuft weiter

Ein einzelner Timeout-Zug führt nicht automatisch zum Verlust von Wertung oder Gewinnanspruch.

---

## 19. Disconnect und Rückkehr

Ein Spieler gilt spielmechanisch als abwesend/disconnected, wenn:

- globale Zugzeit abgelaufen ist
- individuelle Timebank abgelaufen ist

Danach übernimmt eine KI bzw. Automatik regelkonform.

Die Ursache kann unterschiedlich sein:

- Spieler ist online, reagiert aber nicht
- Browser/Tab wurde geschlossen
- Netzwerkverbindung ist verloren
- Session ist unterbrochen
- Nutzer hat die Seite verlassen

Für die Spiellogik ist entscheidend, ob der Spieler rechtzeitig zurückkehrt.

---

### 19.1 Rückkehr bei aktiver Session

Wenn der Spieler noch eingeloggt ist, aber als abwesend gilt, wird ein Button angezeigt:

- "Bin zurück"

Nach Klick:

- Spieler übernimmt wieder Kontrolle
- Status wird wieder aktiv
- nächster möglicher Zug kann wieder selbst gespielt werden

---

### 19.2 Rückkehr nach Login oder Reconnect

Wenn der Spieler ausgeloggt oder getrennt war:

- Login über Loginformular
- oder direkte Reconnect-Schaltfläche, falls Session/Token noch gültig

Nach erfolgreicher Rückkehr:

- offenes Spiel wird automatisch in den Vordergrund gebracht
- aktueller Spielstand wird neu geladen
- vorhandene Vorauswahl wird gelöscht
- Spieler muss wieder aktiv entscheiden

---

## 20. Gewinnanspruch bei KI-Übernahme

Ein Spieler bleibt grundsätzlich in Wertung und Gewinnberechtigung, wenn er vor dem vorletzten Spielzug wieder aktiv eingreift.

Hintergrund:

- der letzte Spielzug ist automatisch
- Rückkehr muss daher vor dem vorletzten Spielzug erfolgen
- sonst kann der Spieler faktisch nicht mehr aktiv eingreifen

Ein einzelner Timeout-Zug zählt nicht automatisch als vollständige KI-Übernahme mit Verlust des Gewinnanspruchs.

Sonderfälle, insbesondere KI-Übernahme ab dem vorletzten Spielzug in Spielgeld- oder Echtgeldspielen, müssen später gesondert geprüft werden.

---

## 21. KI-Spieler

Für Entwicklung und Testbetrieb sollen KI-Spieler erstellt werden.

Ziele:

- Spiele ohne echte Mitspieler testen
- Regeln validieren
- Spielabläufe durchspielen
- UI testen
- Settlement testen
- Timebank/Disconnect testen
- später Trainingsmodus ermöglichen

Die erste KI muss nicht taktisch stark sein. Sie muss vor allem regelkonform spielen.

---

### 21.1 KI-Stufen

Geplant ist ein dreistufiges KI-System.

#### Stufe 1: Einfach / regelkonform

- spielt gültige Karten
- keine tiefe Taktik
- einfache Auswahl aus legalen Zügen
- geeignet für Entwicklung und Tests

#### Stufe 2: Mittel / grundtaktisch

- berücksichtigt einfache Spielsituationen
- vermeidet offensichtlich schlechte Züge
- nutzt einfache Heuristiken
- geeignet für Übungsmodus

#### Stufe 3: Stark / erfahren

- nutzt fortgeschrittenere Heuristiken
- berücksichtigt bisherige Stiche und Runden
- bewertet Tipps und Spielverlauf
- orientiert sich an menschlicher Erfahrung

Details werden später ausgearbeitet.

---

### 21.2 KI in Wertspielen

KI-Spieler dürfen niemals von Anfang an in Echtgeld- oder Spielgeldräumen mit Einsatz teilnehmen.

Technische Regel:

- Buy-in-Spiel darf nicht mit KI-Spielern aufgefüllt werden
- Buy-in-Spiel darf nicht starten, wenn ein Sitz durch KI belegt ist

KI darf in Wertspielen nur als Notfall-Automatik für einen echten Spieler auftreten, der während des Spiels disconnected oder inaktiv wird.

---

### 21.3 KI-Kennzeichnung

KI bzw. automatische Übernahme muss klar sichtbar sein.

In Wertspielen:

- Status: `Disconnected`
- Beispiel: `Spieler123 (Disconnected)`

In kostenlosen Spielen ohne Einsatz:

- Status: `Computer`
- Beispiel: `Computer Einfach`, `Computer Mittel`, `Computer Schwer`

KI-Schwierigkeitsgrade sollen sichtbar angezeigt werden, z. B. mit Symbol oder Legende.

---

### 21.4 KI und Ranglisten

Grundsatz:

- Nur echte Menschen werden bewertet.
- KI-Spieler tauchen nicht in Ranglisten auf.
- KI-Spieler können keine Gewinne erhalten.

Kostenlose Spiele mit KI dürfen statistisch berücksichtigt werden, wenn mindestens drei echte Menschen teilnehmen.

Wenn eine KI eine Platzierung erreicht, wird sie für die Bewertung ignoriert und echte Spieler rücken nach.

Beispiel:

```text
Endplatzierung:
1. Mensch A
2. KI
3. Mensch B
4. Mensch C

Bewertung:
1. Mensch A
2. Mensch B
3. Mensch C
```

---

### 21.5 KI und Preispool

KI von Anfang an in Wertspielen ist nicht erlaubt.

Wenn ein echter Spieler das Buy-in bezahlt hat und während des Spiels durch KI/Automatik ersetzt wird:

- Buy-in bleibt im Preispool
- Spiel läuft weiter
- KI spielt regelkonform weiter
- aktueller Arbeitsstand: kein Gewinnanspruch bei dauerhaftem Fernbleiben
- nachfolgende echte Spieler rücken ggf. nach

Für spätere Echtgeldfälle muss diese Regel juristisch und fachlich gesondert geprüft werden.

Möglicher Sonderfall:

- Spieler ist weit vorne
- Disconnect kurz vor Ende
- nur noch wenige Züge
- Spieler könnte trotz KI-Übernahme rechnerisch in Gewinnrängen bleiben

Dieser Fall wird für Echtgeld bewusst offen gehalten.

---

## 22. Rules Engine

Die Rules Engine muss vollständig serverseitig arbeiten.

Verantwortlichkeiten:

- Karten mischen
- Karten verteilen
- gültige Aktionen prüfen
- Spielphasen steuern
- Tipps erfassen
- Kartenzüge validieren
- Stiche auswerten
- Runden auswerten
- Endplatzierungen bestimmen
- Tiebreaker anwenden
- Settlement anstoßen

Der Client darf keine Ergebnisse liefern.

Beispiel für eine Client-Anfrage:

```json
{
  "action": "play_card",
  "card_id": "..."
}
```

Der Server entscheidet, ob die Aktion gültig ist und welche Folgen sie hat.

---

## 23. Game-State und Persistenz

Benötigt werden:

- persistenter Spielzustand
- Event-Historie
- Zug-Historie
- Audit-Log
- Reconnect-Fähigkeit
- Transaktionen pro Spielaktion
- Schutz vor parallelen oder doppelten Requests

Spielzüge sollen atomar verarbeitet werden:

1. Request prüfen
2. Spielzustand sperren
3. Regelvalidierung durchführen
4. State aktualisieren
5. Events schreiben
6. ggf. Ledger-Buchungen schreiben
7. Commit durchführen
8. UI/Polling/Realtime aktualisieren

---

## 24. Realtime und Polling

Das MVP kann zunächst mit Polling arbeiten.

Wichtiger als WebSockets sind:

- korrekter Serverzustand
- Sicherheit
- Transaktionslogik
- keine Client-Wahrheit

Später möglich:

- WebSockets
- Laravel Reverb
- HomeServer als Realtime-Beschleuniger
- Managed Server oder eigener Server

---

## 25. Moderation und Abuse-Schutz

Benötigt werden:

- Mute
- Ban
- Chat-Sperren
- IP-/Session-basierte Maßnahmen
- Account-basierte Maßnahmen
- Rate Limits
- Schutz vor Spam
- Schutz vor Phishing
- Schutz vor Nickname-Imitation

Ein Report-System ist für das MVP nicht zwingend erforderlich.

Es wird wichtiger, wenn:

- Echtgeld eingeführt wird
- geldwerte Preise möglich werden
- Sponsoring oder Shop-Funktionen entstehen
- größere Spielerzahlen erreicht werden

Moderationsaktionen müssen geloggt werden.

Reports dürfen später nicht automatisch zu harten Sanktionen führen, da Missbrauch möglich ist.

---

## 26. Sicherheit und Anti-Cheat

Wichtige Prinzipien:

- keine verdeckten Informationen an unberechtigte Clients
- keine gegnerischen Hände im Client-State
- serverseitiger Shuffle
- kryptographisch geeigneter Zufall
- keine manipulierbaren Ergebnisse
- keine mehrfachen Spielteilnahmen
- kein Multitabling im MVP
- Race-Condition-Schutz
- idempotente Aktionen
- Auditierbarkeit
- Chat- und Namensschutz

Bekannte externe Risiken:

- Discord
- Teamspeak
- Messenger
- andere Drittanbieter-Kommunikation
- Collusion
- Zuschauerinformationen

Diese Risiken werden zunächst akzeptiert bzw. später vertieft, wenn Echtgeld, geldwerte Preise oder größere Spielerzahlen relevant werden.

---

## 27. Deployment und Betrieb

Aktuelle Betriebsannahmen:

- Laravel auf ALL-INKL Shared Hosting als autoritative Plattform
- HomeServer optional später für Realtime-Unterstützung
- Shared Hosting erfordert einfache und robuste Lösung
- globale Lobby zunächst nur einmal
- Polling zunächst akzeptabel
- Lastschutz über Limiter/Warteschlange wichtig

---

## 28. Spätere Erweiterungen

Mögliche spätere Module:

- private Ligen
- Turniere
- Turnier-Chat
- dynamische Lobbys
- Echtgeld-/Zahlungsmodul nur nach rechtlicher Prüfung
- KYC/2FA/Compliance
- Sponsoring/Preise
- Report-System
- Replay-System
- Ranglisten
- Spielstärke-Berechnung
- Anti-Collusion-Systeme
- Zuschauer-Delay
- Bots/Testspieler
- Trainingsmodus

---

## 29. Bewusst offene Themen

Die folgenden Themen bleiben bewusst offen und werden während der konkreten Entwicklung an passender Stelle entschieden:

### Auszahlungstabellen

- exakte Prozentverteilung je Spieleranzahl
- konkrete Buy-in-Stufen
- Rundungsregeln
- Mindestfaktor letzter bezahlter Platz
- Umgang mit Sonderfällen

### Spielgeld-Ökonomie

- Startguthaben
- tägliche aktive Gutschrift
- Mindest-/Max-Kontostände
- Buy-in-Stufen
- Raumangebot abhängig vom Umlaufvermögen
- Nutzung der Rake

### Chat-Schutz

- exakte Linkfilter-Regeln
- Nickname-Ähnlichkeitsprüfung
- Rate Limits
- Gast-Cooldowns
- Moderationsstufen

### Disconnect-Sonderfälle

- KI-Übernahme kurz vor Spielende
- Gewinnanspruch bei spätem Disconnect
- Unterschiede zwischen Spielgeld und Echtgeld
- Reconnect-Fristen
- mögliche Missbrauchsmuster

### Spielregeln / Rules Engine

Die technische Rules Engine wird aus den offiziellen Spielregeln abgeleitet.

Zu modellieren bleiben:

- Rundenstruktur im Detail
- Tipp-Logik
- Stichlogik
- Punkteberechnung
- Spielende
- Platzierung
- Tiebreaker

---

## 30. Zusammenfassung

`stechen-mmo` wird als serverautoritativ kontrollierte Multiplayer-Spielplattform umgesetzt.

Das MVP verwendet Spielgeld, bereitet aber eine geldspielnahe Architektur vor.

Zentrale Eigenschaften:

- registrierte Nutzer für aktive Spiele
- Gäste nur als Zuschauer und Lobby-Chat-Teilnehmer
- klare Trennung von globalem Chat und Tisch-Chat
- öffentliche Zuschauerfunktion ohne verdeckte Informationen
- Spielgeld über Wallet und Ledger
- Buy-in, Preispool und Rake vorbereitet
- automatische Spielräume
- kein Multitabling
- Timebank und KI-Übernahme bei Inaktivität
- KI nur für Entwicklung, Training oder Disconnect-Automatik
- keine KI von Anfang an in Wertspielen
- vollständige serverseitige Rules Engine
- persistenter Game-State mit Event-Historie
- spätere Erweiterbarkeit für Turniere, Ligen, Ranglisten und ggf. rechtlich geprüfte Echtgeldfunktionen