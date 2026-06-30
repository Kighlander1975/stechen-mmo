# Settlement und Preisverteilung

Stand: Juni 2026
Status: MVP-Planungsregel für Spielgeldräume

## 1. Zweck

Dieses Dokument beschreibt die aktuelle MVP-Regel für:

- Preisverteilung nach Spielende;
- bezahlte Plätze nach Spieleranzahl;
- fixe Spielgeld-Rake;
- Rundung und Rundungsreste;
- Mindestgewinn für den letzten bezahlten Platz;
- Abgrenzung von KI-Spielern;
- Disconnect-/KI-Vertretung;
- Ledger- und RakePool-Einordnung.

Die Regeln gelten für `PLAY_MONEY` / `St$`.

Echte Echtgeld-Einzahlungen, echte Echtgeld-Auszahlungen und variable Echtgeld-Rake-Tabellen sind nicht Bestandteil dieser MVP-Regel.

## 2. Grundannahmen

Für die MVP-Phase gilt:

- Rake ist fix `2 %`.
- Rake wird immer abgerundet zugunsten der Spieler.
- Auszahlungen erfolgen aus dem Netto-Preispool.
- Auszahlungen werden in ganzen `St$` gebucht.
- Es gibt keine Nachkommastellen.
- Wertende Buy-in-Räume mit mehr als einem bezahlten Platz müssen mindestens `10 St$` Buy-in haben.
- Kostenlose Räume sind davon ausgenommen und folgen eigenen Trainings-/Testregeln.

Grundformeln:

| Wert | Formel |
|---|---|
| Brutto-Preispool | `gross_prize_pool = player_count * buy_in` |
| Rake | `rake_amount = floor(gross_prize_pool * 200 / 10000)` |
| Netto-Preispool | `net_prize_pool = gross_prize_pool - rake_amount` |

## 3. Mindestregel letzter bezahlter Platz

Bei mehr als einem bezahlten Platz muss der letzte bezahlte Platz mindestens das `1,3`-fache des Brutto-Buy-ins erhalten.

Technisch:

| Wert | Formel |
|---|---|
| Mindestgewinn letzter bezahlter Platz | `ceil(buy_in * 13 / 10)` |

Beispiele:

| Buy-in | Mindestgewinn letzter bezahlter Platz |
|---:|---:|
| 10 St$ | 13 St$ |
| 25 St$ | 33 St$ |
| 50 St$ | 65 St$ |
| 100 St$ | 130 St$ |

Die Regel bezieht sich auf den einzelnen Brutto-Buy-in eines Spielers, nicht auf den Netto-Preispool.

## 4. Auszahlungstabelle

Die folgende Tabelle ist die aktuelle MVP-Auszahlungstabelle für wertende Spielgeldräume.

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

Einordnung:

- 2 bis 4 Spieler bleiben Winner-takes-all.
- 5 bis 7 Spieler zahlen zwei Plätze aus.
- 8 bis 9 Spieler zahlen drei Plätze aus.
- 10 bis 11 Spieler zahlen vier Plätze aus.
- Die unteren bezahlten Plätze bleiben oberhalb der Mindestregel.
- Die Unterschiede zwischen den Spieleranzahl-Stufen bleiben spürbar.
- Bei zwei bezahlten Plätzen darf der zweite Platz bewusst mehr als das Mindest-`1,3x` erhalten.

## 5. Rundungsregel

Auszahlungen werden aus dem Netto-Preispool berechnet.

Ablauf:

1. Für jeden bezahlten Platz wird der Prozentanteil aus dem Netto-Preispool berechnet.
2. Jeder Betrag wird auf ganze `St$` abgerundet.
3. Der dadurch verbleibende Rundungsrest bleibt Teil des Netto-Preispools.
4. Der Rundungsrest wird nach der folgenden Regel an die unteren bezahlten Plätze verteilt.

### 5.1 Verteilung von Rundungsresten

| Rundungsrest | Verteilung |
|---:|---|
| 0 St$ | keine Zusatzverteilung |
| 1 St$ | +1 St$ an den letzten bezahlten Platz |
| 2 St$ | +1 St$ an den letzten und +1 St$ an den vorletzten bezahlten Platz |
| 3 St$ | +2 St$ an den letzten und +1 St$ an den vorletzten bezahlten Platz |

Beispiele:

| Bezahlte Plätze | Rundungsrest | Zusatz auf Plätze |
|---:|---:|---|
| 2 | 1 St$ | Platz 2 +1 |
| 2 | 2 St$ | Platz 1 +1, Platz 2 +1 |
| 3 | 2 St$ | Platz 2 +1, Platz 3 +1 |
| 4 | 3 St$ | Platz 3 +1, Platz 4 +2 |

Diese Regel stärkt den unteren bezahlten Bereich und erhöht den Wiederkehrwert für Spieler.

## 6. Simulationsergebnis

Die aktuelle Tabelle und Rundungsregel wurden für folgende Szenarien simuliert:

| Kennzahl | Wert |
|---|---:|
| Spielerzahlen | 2 bis 11 |
| Buy-ins | 1 bis 500 St$ |
| Rake | 2 %, abgerundet |
| Maximaler Rundungsrest | 3 St$ |
| Maximal benötigter RakePool-Ausgleich | 0 St$ |
| Fälle mit Rundungsrest > 4 St$ | 0 |
| Fälle mit RakePool-Ausgleich > 4 St$ | 0 |
| MVP-Mehrplatz-Szenarien mit Buy-in >= 10 St$ | 3437 |
| MVP-Szenarien mit RakePool-Ausgleich | 0 |

Fazit:

- Der Rundungsrest überschreitet in der Simulation nie `3 St$`.
- Es wurde kein Ausgleich aus dem RakePool benötigt.
- Die Mindest-Buy-in-Regel von `10 St$` für wertende Mehrplatz-Auszahlungen ist plausibel.
- Die Auszahlungstabelle erfüllt die Mindestregel für den letzten bezahlten Platz in den simulierten Szenarien.

## 7. Beispiel Buy-in 10 St$

| Spieler | Brutto | Rake 2 % | Netto | Verteilung | Basis-Auszahlung | Rundungsrest | Mindestwert letzter Platz | RakePool-Ausgleich | Finale Auszahlung |
|---:|---:|---:|---:|---|---|---:|---:|---:|---|
| 2 | 20 | 0 | 20 | 100 % | 20 | 0 | - | 0 | 20 |
| 3 | 30 | 0 | 30 | 100 % | 30 | 0 | - | 0 | 30 |
| 4 | 40 | 0 | 40 | 100 % | 40 | 0 | - | 0 | 40 |
| 5 | 50 | 1 | 49 | 68 / 32 | 33 / 15 | 1 | 13 | 0 | 33 / 16 |
| 6 | 60 | 1 | 59 | 70 / 30 | 41 / 17 | 1 | 13 | 0 | 41 / 18 |
| 7 | 70 | 1 | 69 | 72 / 28 | 49 / 19 | 1 | 13 | 0 | 49 / 20 |
| 8 | 80 | 1 | 79 | 52 / 30 / 18 | 41 / 23 / 14 | 1 | 13 | 0 | 41 / 23 / 15 |
| 9 | 90 | 1 | 89 | 54 / 29 / 17 | 48 / 25 / 15 | 1 | 13 | 0 | 48 / 25 / 16 |
| 10 | 100 | 2 | 98 | 43 / 27 / 16 / 14 | 42 / 26 / 15 / 13 | 2 | 13 | 0 | 42 / 26 / 16 / 14 |
| 11 | 110 | 2 | 108 | 42 / 27 / 18 / 13 | 45 / 29 / 19 / 14 | 1 | 13 | 0 | 45 / 29 / 19 / 15 |

## 8. Beispiel Buy-in 50 St$

| Spieler | Brutto | Rake 2 % | Netto | Verteilung | Basis-Auszahlung | Rundungsrest | Mindestwert letzter Platz | RakePool-Ausgleich | Finale Auszahlung |
|---:|---:|---:|---:|---|---|---:|---:|---:|---|
| 2 | 100 | 2 | 98 | 100 % | 98 | 0 | - | 0 | 98 |
| 3 | 150 | 3 | 147 | 100 % | 147 | 0 | - | 0 | 147 |
| 4 | 200 | 4 | 196 | 100 % | 196 | 0 | - | 0 | 196 |
| 5 | 250 | 5 | 245 | 68 / 32 | 166 / 78 | 1 | 65 | 0 | 166 / 79 |
| 6 | 300 | 6 | 294 | 70 / 30 | 205 / 88 | 1 | 65 | 0 | 205 / 89 |
| 7 | 350 | 7 | 343 | 72 / 28 | 246 / 96 | 1 | 65 | 0 | 246 / 97 |
| 8 | 400 | 8 | 392 | 52 / 30 / 18 | 203 / 117 / 70 | 2 | 65 | 0 | 203 / 118 / 71 |
| 9 | 450 | 9 | 441 | 54 / 29 / 17 | 238 / 127 / 74 | 2 | 65 | 0 | 238 / 128 / 75 |
| 10 | 500 | 10 | 490 | 43 / 27 / 16 / 14 | 210 / 132 / 78 / 68 | 2 | 65 | 0 | 210 / 132 / 79 / 69 |
| 11 | 550 | 11 | 539 | 42 / 27 / 18 / 13 | 226 / 145 / 97 / 70 | 1 | 65 | 0 | 226 / 145 / 97 / 71 |

## 9. RakePool-Ausgleich

Auszahlungen kommen grundsätzlich aus dem Netto-Preispool.

Mit der aktuellen MVP-Tabelle und der simulierten Rundungsregel wird kein RakePool-Ausgleich benötigt.

Der RakePool bleibt dennoch als nachvollziehbar gebuchtes System-/Rake-Konto relevant, zum Beispiel für spätere Economy-Regeln, Spezialaktionen oder eng begrenzte Sonderfälle.

Falls ein späteres Regelwerk einen Ausgleich aus dem RakePool erlaubt, muss dieser als eigene Ledger-Buchung nachvollziehbar sein.

Für die aktuelle MVP-Regel gilt:

| Quelle | Verwendung |
|---|---|
| Netto-Preispool | Quelle der normalen Auszahlungen |
| Rundungsrest aus Netto-Preispool | wird an untere bezahlte Plätze verteilt |
| RakePool-Ausgleich | in geprüften MVP-Szenarien nicht nötig |
| Geldschöpfung aus dem Nichts | nicht erlaubt |

## 10. KI-Spieler und Disconnects

KI-Spieler erhalten keine Gewinne.

Unterscheidung:

| Fall | Auszahlung |
|---|---|
| Eigenständiger KI-Spieler | keine Auszahlung |
| KI vertritt disconnected echten Spieler | Auszahlung bleibt dem ursprünglichen echten Spieler zugeordnet |

Wenn während eines laufenden Spiels ein echter Spieler disconnected und eine KI oder Automatik weiterspielt:

- das Spiel kann weiterlaufen;
- der echte Spieler bleibt settlement-berechtigt;
- die KI ist nur technische Vertretung;
- ein möglicher Gewinn gehört dem ursprünglichen echten Spieler;
- der Fall wird markiert;
- spätere Abuse-/Exploit-Prüfungen können wiederholte Muster erkennen.

Mögliche spätere technische Markierungen:

| Feld / Signal | Zweck |
|---|---|
| `was_ai_assisted` | Spieler wurde durch KI/Automatik vertreten |
| `ai_assisted_from` | Beginn der KI-Vertretung |
| `ai_assisted_until` | Ende der KI-Vertretung |
| `disconnect_count` | Anzahl Disconnects im Spiel |
| `abuse_review_required` | Fall sollte geprüft werden |

Die genaue technische Umsetzung dieser Felder ist noch nicht festgelegt.

## 11. Ledger-Anforderungen

Settlement muss nachvollziehbar sein.

Mindestens relevant:

- Buy-in-Commit beim Spielstart;
- Rake-Buchung;
- Auszahlung an echte Gewinner;
- mögliche spätere Sonderbuchung für RakePool-Ausgleich;
- Audit-/Risk-Event bei KI-Vertretung nach Disconnect.

Kontobewegungen dürfen nicht direkt im Controller manipuliert werden.

Wallet-, Ledger-, Buy-in-, Rake- und Settlement-Logik muss servicebasiert und transaktional umgesetzt werden.

## 12. Abgrenzung

Nicht Bestandteil dieser MVP-Regel:

- echte Echtgeld-Einzahlung;
- echte Echtgeld-Auszahlung;
- Payment-Provider;
- variable Echtgeld-Rake-Tabelle;
- vollständige Settlement-Implementierung;
- vollständige Karten-, Stich-, Ansage- und Punkte-Engine;
- vollständiges Abuse-Scoring.

Die spätere Echtgeld- oder geldwerte Umsetzung benötigt eine getrennte fachliche, rechtliche und technische Prüfung.
