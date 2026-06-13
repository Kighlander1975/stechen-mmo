# Game Rules: Stechen

Stand: 2026-06-13  
Quelle: https://stechen-helper.de/regeln

Dieses Dokument beschreibt die Spielregeln von **Stechen** als Grundlage für die spätere Umsetzung in Laravel.

---

## 1. Kurzbeschreibung

**Stechen** ist ein Stichspiel mit dem Kartenspiel **11er Raus**.

Ziel des Spiels ist es, durch Analyse der eigenen Karten möglichst genau vorherzusagen, wie viele Stiche man in einer Runde machen wird. Für genaue Ansagen erhalten Spieler Bonuspunkte. Wer als erster die Zielpunktzahl erreicht, gewinnt das Spiel.

---

## 2. Kartensatz

Verwendet wird das Kartenspiel **11er Raus**.

### Farben

- Rot
- Gelb
- Grün
- Blau

### Werte

- 1 bis 20

### Gesamtanzahl Karten

```text
4 Farben * 20 Werte = 80 Karten
```

---

## 3. Spieleranzahl

```text
Minimum: 2 Spieler
Maximum: 11 Spieler
Empfohlen: ab 3 Spielern
```

---

## 4. Kartenverteilung

Die Anzahl der Karten pro Spieler hängt von der Spieleranzahl ab.

| Spieleranzahl | Karten pro Spieler |
|---|---:|
| 2 bis 6 | 9 |
| 7 bis 11 | 7 |

Die restlichen Karten bleiben als Stapel übrig.

Nach dem Austeilen wird eine Karte vom Stapel aufgedeckt. Die Farbe dieser Karte bestimmt die Trumpf-Farbe der Runde.

---

## 5. Dealer und Spielerreihenfolge

Zu Beginn wird ein Dealer bestimmt.

Nach jeder Runde wechselt der Dealer im Uhrzeigersinn.

Für die jeweilige Runde gilt:

- Der Spieler links vom Dealer beginnt die Ansagephase.
- Der Spieler links vom Dealer spielt auch die erste Karte aus.
- Die Auswertung erfolgt ebenfalls reihum, beginnend beim Spieler links vom Dealer.

---

## 6. Rundenstruktur

Eine Runde besteht aus drei Phasen:

```text
1. Ansagen
2. Spielen
3. Auswerten
```

---

## 7. Phase: Ansagen

In der Ansagephase sagt jeder Spieler voraus, wie viele Stiche er in dieser Runde machen wird.

Die Ansagen erfolgen reihum, beginnend beim Spieler links vom Dealer.

### Zulässige Ansagen

| Spieleranzahl | Karten pro Spieler | Erlaubte Ansagen |
|---|---:|---|
| 2 bis 6 | 9 | 0 bis 9 |
| 7 bis 11 | 7 | 0 bis 7 |

Die nachfolgenden Spieler können die bereits gemachten Ansagen sehen und ihre eigene Ansage strategisch anpassen.

---

## 8. Phase: Spielen

Der Spieler links vom Dealer spielt die erste Karte aus.

Danach spielen die weiteren Spieler reihum jeweils eine Karte.

Ein vollständiger Stich besteht aus genau einer gespielten Karte pro Spieler.

---

## 9. Farbbedienpflicht

Die zuerst ausgespielte Farbe eines Stichs ist die geforderte Farbe.

Jeder nachfolgende Spieler muss diese Farbe bedienen, wenn er mindestens eine Karte dieser Farbe auf der Hand hat.

### Wenn ein Spieler die geforderte Farbe besitzt

Er muss eine Karte dieser Farbe spielen.

### Wenn ein Spieler die geforderte Farbe nicht besitzt

Er darf entweder:

- eine Trumpfkarte spielen,
- oder eine beliebige andere Farbe abwerfen.

Das bewusste Nichtbedienen trotz vorhandener Farbe ist ein Regelverstoß.

Für die digitale Umsetzung gilt:

```text
Ein illegaler Zug wird serverseitig abgelehnt.
```

---

## 10. Trumpf

Die Trumpf-Farbe wird zu Beginn jeder Runde durch eine aufgedeckte Karte bestimmt.

Trumpfkarten schlagen Karten der ausgespielten Farbe, wenn mindestens eine Trumpfkarte im Stich liegt.

---

## 11. Stichgewinner

Der Gewinner eines Stichs wird nach folgenden Regeln bestimmt:

1. Wenn mindestens eine Trumpfkarte gespielt wurde:
   - Die höchste Trumpfkarte gewinnt.
2. Wenn keine Trumpfkarte gespielt wurde:
   - Die höchste Karte der zuerst ausgespielten Farbe gewinnt.

Der Gewinner eines Stichs erhält den Stich und spielt die erste Karte des nächsten Stichs aus.

---

## 12. Ende der Spielphase

Die Spielphase endet, wenn alle Spieler keine Karten mehr auf der Hand haben.

Die Anzahl der Stiche pro Runde entspricht der Anzahl der Karten, die jeder Spieler zu Beginn der Runde erhalten hat.

```text
2 bis 6 Spieler: 9 Stiche
7 bis 11 Spieler: 7 Stiche
```

---

## 13. Auswertung

Nach der Spielphase werden die tatsächlichen Stiche jedes Spielers mit seiner Ansage verglichen.

Grundregel:

```text
Jeder gemachte Stich zählt 1 Punkt.
```

### Exakte Ansage getroffen

Wenn ein Spieler exakt so viele Stiche gemacht hat, wie er angesagt hat:

```text
Punkte = tatsächliche Stiche + 10 Bonuspunkte
```

Beispiele:

| Ansage | Tatsächliche Stiche | Punkte |
|---:|---:|---:|
| 3 | 3 | 13 |
| 5 | 5 | 15 |
| 7 | 7 | 17 |

### Ansage nicht getroffen

Wenn ein Spieler mehr oder weniger Stiche macht als angesagt:

```text
Punkte = tatsächliche Stiche
```

Beispiele:

| Ansage | Tatsächliche Stiche | Punkte |
|---:|---:|---:|
| 3 | 2 | 2 |
| 3 | 4 | 4 |
| 5 | 1 | 1 |

---

## 14. Sonderfall: Ansage 0

Wenn ein Spieler 0 Stiche ansagt und tatsächlich 0 Stiche macht:

```text
Punkte = 20
```

Wenn ein Spieler 0 Stiche ansagt, aber mindestens einen Stich macht:

```text
Punkte = tatsächliche Stiche
```

Beispiele:

| Ansage | Tatsächliche Stiche | Punkte |
|---:|---:|---:|
| 0 | 0 | 20 |
| 0 | 1 | 1 |
| 0 | 3 | 3 |

---

## 15. Zielpunktzahl und Spielende

Das Spiel endet, sobald ein Spieler die Zielpunktzahl erreicht oder überschreitet.

Die Zielpunktzahl ist abhängig vom Tagesdatum.

```text
Zielpunktzahl = 100 + Tag des Monats
```

Beispiel:

```text
Datum: 06.11.
Zielpunktzahl: 106
```

Wenn mehrere Spieler in derselben Auswertung die Zielpunktzahl erreichen, gewinnt der Spieler, der sie in Auswertungsreihenfolge zuerst erreicht.

Für die technische Umsetzung ist deshalb wichtig:

```text
Die Auswertung muss in Spielerreihenfolge erfolgen.
```

---

## 16. Technische Ableitungen

Für die digitale Umsetzung müssen folgende Regelbereiche serverseitig abgebildet werden.

### Spielverwaltung

- Spieler
- Sitzreihenfolge
- Dealer
- aktueller Startspieler
- Spielstatus
- Rundenstatus
- Zielpunktzahl

### Kartensystem

- Kartenfarben
- Kartenwerte
- Deck mit 80 Karten
- Mischlogik
- Austeilung
- Reststapel
- Trumpfkarte
- Trumpffarbe
- Handkarten je Spieler

### Ansagen

- erlaubter Ansagebereich
- Ansage je Spieler
- Reihenfolge der Ansagen
- Sichtbarkeit bereits gemachter Ansagen

### Spielzüge

- aktueller Spieler am Zug
- gespielte Karte
- geforderte Farbe
- Farbbedienpflicht
- Trumpfprüfung
- Stichabschluss
- Stichgewinner
- Startspieler des nächsten Stichs

### Punktewertung

- tatsächliche Stiche je Spieler
- Vergleich mit Ansage
- Bonus bei exakter Ansage
- Sonderfall 0 Ansage / 0 Stiche
- Gesamtpunktestand
- Gewinnerermittlung

---

## 17. Erste Validierungsregeln für die App

Die Anwendung muss mindestens folgende Regeln prüfen:

- Ein Spieler darf nur handeln, wenn er am Zug ist.
- Ein Spieler darf nur Karten spielen, die er auf der Hand hat.
- Eine Ansage muss im erlaubten Bereich liegen.
- Eine Karte darf nur in der Spielphase gespielt werden.
- Die Farbbedienpflicht muss eingehalten werden.
- Ein Stich ist erst vollständig, wenn jeder aktive Spieler eine Karte gespielt hat.
- Ein Stichgewinner muss eindeutig bestimmbar sein.
- Eine Runde darf erst ausgewertet werden, wenn alle Stiche gespielt wurden.
- Das Spiel endet, sobald die Zielpunktzahl erreicht wurde.

---

## 18. Offene Detailfragen

Diese Punkte sollten vor oder während der MVP-Umsetzung noch endgültig geklärt werden:

- Wie wird der erste Dealer bestimmt?
- Wird die Zielpunktzahl nach realem Tagesdatum beim Spielstart fixiert?
- Was passiert bei Spielerabbruch während einer Runde?
- Gibt es Zuschauer?
- Gibt es Bots für fehlende Spieler?
- Gibt es private Spiele mit Einladungscode?
- Gibt es öffentliche Lobbys?
- Wird Chat im MVP benötigt?
- Wird ein Reconnect innerhalb laufender Spiele unterstützt?
