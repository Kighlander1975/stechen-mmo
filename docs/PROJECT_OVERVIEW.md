# Project Overview: stechen-mmo

Stand: 2026-06-13

Dieses Dokument beschreibt Ziel, Umfang und technische Ausrichtung des Projekts **stechen-mmo**.

---

## 1. Projektziel

**stechen-mmo** ist die geplante digitale Umsetzung des Kartenspiels **Stechen**.

Das Projekt soll es ermöglichen, Stechen online mit mehreren Personen zu spielen. Die Anwendung soll die Spielregeln serverseitig validieren, den Spielstand verwalten und die Punktewertung automatisch durchführen.

Langfristig soll daraus eine stabile, webbasierte Multiplayer-Anwendung entstehen.

---

## 2. Was ist Stechen?

**Stechen** ist ein Stichspiel auf Basis des Kartenspiels **11er Raus**.

Das Spiel kombiniert:

- Kartenglück
- Einschätzung der eigenen Handkarten
- strategische Ansagen
- Farbbedienpflicht
- Trumpfmechanik
- automatische Punktewertung

Die detaillierten Spielregeln sind in folgender Datei dokumentiert:

```text
docs/GAME_RULES.md
```

---

## 3. Grundidee der Anwendung

Die Anwendung ersetzt den manuellen Schriftführer und verwaltet digitale Spielrunden.

Sie soll insbesondere:

- Spieler verwalten
- Spiele erstellen
- Karten mischen und austeilen
- Trumpf bestimmen
- Ansagen entgegennehmen
- Spielzüge validieren
- Stiche auswerten
- Punkte berechnen
- Gewinner bestimmen

Die App soll sicherstellen, dass nur gültige Spielaktionen akzeptiert werden.

---

## 4. Zielplattform

Die autoritative Plattform ist eine Laravel-Webanwendung auf Shared Hosting.

### Geplante Hauptplattform

```text
ALL-INKL Shared Hosting
Laravel 11/12
MariaDB
PHP 8.4
```

### Rolle des HomeServers

Der HomeServer `kighserv` ist nicht die primäre Plattform für die Spielautorität.

Er kann später optional als Realtime-Beschleuniger eingesetzt werden, zum Beispiel für:

- WebSocket-Server
- Live-Events
- Spielraum-Aktualisierungen
- experimentelle Dienste
- interne Testumgebungen

Die Spielregeln und autoritativen Transaktionen bleiben jedoch in Laravel.

---

## 5. Architekturentscheidung

Für Phase 1 gilt:

```text
Laravel ist die autoritative Spielinstanz.
```

Das bedeutet:

- Alle Spielaktionen werden serverseitig geprüft.
- Der Client darf keine Spielentscheidung eigenständig treffen.
- Spielzüge werden nur gespeichert, wenn sie regelkonform sind.
- Punkte werden serverseitig berechnet.
- Der Gewinner wird serverseitig bestimmt.

Diese Entscheidung ist wichtig, um Manipulationen durch Clients zu verhindern.

---

## 6. Technische Leitlinien

### Server authoritative

Der Server entscheidet über:

- gültige Ansagen
- erlaubte Karten
- Einhaltung der Farbbedienpflicht
- Stichgewinner
- Rundenabschluss
- Punktevergabe
- Spielende

### Validierung vor Persistenz

Jede Aktion wird vor dem Speichern geprüft.

Beispiele:

- Ist der Spieler am Zug?
- Gehört die Karte dem Spieler?
- Ist die Spielphase korrekt?
- Muss eine Farbe bedient werden?
- Ist die Ansage erlaubt?

### Nachvollziehbarkeit

Spielaktionen sollen so gespeichert werden, dass ein Spielverlauf nachvollziehbar bleibt.

Mögliche spätere Erweiterungen:

- Spielprotokoll
- Replay
- Audit-Log
- Statistiken

---

## 7. MVP-Ziel

Der MVP soll eine erste vollständig spielbare digitale Version ermöglichen.

Der Fokus liegt nicht auf perfekter Optik, sondern auf korrekter Spiellogik.

### MVP-Kernfunktionen

- Spiel erstellen
- Spieler anlegen oder beitreten lassen
- Sitzreihenfolge festlegen
- Dealer verwalten
- Karten generieren
- Karten mischen
- Karten austeilen
- Trumpfkarte bestimmen
- Ansagephase durchführen
- Spielphase durchführen
- Farbbedienpflicht validieren
- Stichgewinner ermitteln
- Rundenwertung berechnen
- Gesamtpunkte speichern
- Spielgewinner bestimmen

---

## 8. MVP-Nichtziele

Folgende Funktionen gehören zunächst nicht zwingend zum MVP:

- öffentlicher Matchmaking-Modus
- Ranglisten
- Chat
- Avatare
- Freundeslisten
- Push-Benachrichtigungen
- vollständige Mobile-App
- Bots
- Replay-System
- Zuschauer-Modus
- Echtgeld- oder Turniersysteme
- komplexes Rechtemanagement

Diese Funktionen können später in separaten Phasen geplant werden.

---

## 9. Geplante Phasen

### Phase 1: Laravel-MVP

Ziel:

```text
Eine spielbare Webversion mit korrekter Regelvalidierung.
```

Schwerpunkte:

- Datenmodell
- Spiellogik
- Rundenablauf
- Punktewertung
- einfache Oberfläche
- klassische HTTP-Requests oder Polling

### Phase 2: Realtime-Erweiterung

Ziel:

```text
Verbesserung der Live-Aktualisierung.
```

Mögliche Bausteine:

- Laravel Broadcasting
- WebSockets
- HomeServer als Realtime-Dienst
- Live-Spielraum
- automatische UI-Aktualisierung

### Phase 3: Komfortfunktionen

Ziel:

```text
Verbesserung von Bedienung, Spielgefühl und Langzeitmotivation.
```

Mögliche Funktionen:

- Benutzerkonten
- Spielhistorie
- Statistiken
- Ranglisten
- private Einladungslinks
- Reconnect-Unterstützung
- responsive/mobile Optimierung

---

## 10. Dokumentationsstruktur

Aktuell relevante Dokumente:

```text
docs/GAME_RULES.md
docs/INFRASTRUCTURE_STATUS.md
docs/homeserver-inventory.md
docs/Stechen-Serverkonzept.md
```

Geplante oder mögliche weitere Dokumente:

```text
docs/PROJECT_OVERVIEW.md
docs/MVP_SCOPE.md
docs/DATA_MODEL.md
docs/GAME_ENGINE.md
docs/API_CONTRACT.md
docs/DEPLOYMENT.md
```

---

## 11. Abgrenzung zu bestehenden Projekten

`stechen-mmo` ist ein eigenständiges Softwareprojekt zur digitalen Umsetzung eines Mehrspieler-Kartenspiels.

Das Projekt steht in keiner technischen, organisatorischen oder offiziellen Verbindung zu bestehenden Webseiten, Regelübersichten oder Hilfsprojekten rund um das zugrunde liegende Live-Spiel.

Bestehende öffentlich zugängliche Informationen können lediglich als allgemeine Orientierung zum Verständnis des Spiels dienen. Die konkrete technische Umsetzung, Architektur, Benutzerführung und Spielmodellierung von `stechen-mmo` werden unabhängig entwickelt.

Ziel ist nicht die Nachbildung, Erweiterung oder Ablösung bestehender Hilfsangebote, sondern die Entwicklung einer eigenständigen Online-Spielplattform.

---

## 12. Zentrale Entwicklungsfragen

Vor oder während der MVP-Umsetzung sind insbesondere folgende Fragen zu klären:

- Wie wird ein Spielraum erstellt?
- Wie treten Spieler einem Spiel bei?
- Gibt es im MVP registrierte Benutzer oder Gastspieler?
- Wird die Sitzreihenfolge automatisch oder manuell festgelegt?
- Wie wird der erste Dealer bestimmt?
- Wird die Zielpunktzahl beim Spielstart fixiert?
- Wie wird mit Verbindungsabbrüchen umgegangen?
- Gibt es einen Spielleiter oder Host?
- Wie viel Spielverlauf soll gespeichert werden?
- Wie einfach oder detailliert soll die erste UI sein?

---

## 13. Aktueller Projektstatus

Die Infrastruktur-Grundlage ist dokumentiert.

Die Spielregeln wurden analysiert und in `docs/GAME_RULES.md` festgehalten.

Der aktuelle Fokus liegt auf:

```text
Konzeption des MVP
Ableitung des Datenmodells
Planung der Laravel-Implementierung
```

---

## 14. Nächster sinnvoller Schritt

Nach diesem Dokument sollte der MVP-Umfang konkretisiert werden.

Vorgeschlagene nächste Datei:

```text
docs/MVP_SCOPE.md
```

Diese Datei sollte definieren:

- welche Funktionen zwingend in die erste Version gehören,
- welche Funktionen bewusst verschoben werden,
- welche technischen Annahmen für Phase 1 gelten,
- wann der MVP als fertig gilt.
