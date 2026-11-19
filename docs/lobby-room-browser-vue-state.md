# Lobby-Raumbrowser mit Vue-Island

Stand: 2026-11-19 (Umbau auf Zielbild v3)

## Zweck

Diese Datei dokumentiert den aktuellen Stand des Lobby-Raumbrowsers in Stechen-MMO.

Die Lobby zeigt die systemseitig bereitgestellten Spieltische. Beitreten
(mit Buy-in-Reservierung), Platz freigeben und das Öffnen des Spielfensters
sind aktiv. Das Zielbild ist Entwurf v3 (`_docs/images/lobby-entwurf-v3.png`,
nicht versioniert), vom Projektinhaber am 2026-11-19 als Basis bestätigt.

Der ältere Spielfeld-Prototyp ist unter `docs/lobby-field-prototype.md`
beschrieben und nur im Dev-Modus erreichbar.

---

## Aktueller Status

Implementiert:

- Tischliste mit fester Kopfzeile und eigenem Scrollbereich, der auf großen
  Bildschirmen mit der Fensterhöhe wächst.
- Filter als kleine Chips, die sofort wirken und pro Benutzer gespeichert
  werden: Tischgröße (2 / 3–4 / 5–6 / 7–11), Buy-in, Status, Startmodus sowie
  „Nur Testräume“ für berechtigte Phase-3-Testbenutzer. Der Chip
  „↺ Zurücksetzen“ ist immer sichtbar und ohne aktiven Filter gedämpft und
  deaktiviert.
- Pro Zeile: Name mit Hinweisen (Dabei, Startet m:ss, Test), Plätze als
  Punkte (gelb = eigener Platz, grau = belegt, Ring = frei), Buy-in,
  Gewinnpool und eine Aktion (Beitreten, Zu teuer, Platz n, Zum Tisch oder
  Status).
- Rechte Spalte: ausgewählter Tisch mit Buy-in, Gewinnpool („Rake bereits
  abgezogen“, ohne Prozentangabe), Countdown während der Startphase, Mini-
  Pokertisch mit Sitzplätzen und den Aktionen Beitreten, Platz freigeben und
  Zum Spieltisch.
- Aktualisierung per Polling alle 3 Sekunden; Fußzeile zeigt die Uhrzeit der
  letzten Aktualisierung oder einen Fehlerhinweis.
- Countdown läuft clientseitig sekundengenau und wird über `meta.serverNow`
  an die Serveruhr angeglichen.

Nur im Dev-Modus (`APP_ENV=local`, Payload `meta.devMode`):

- Raumcode (`publicCode`) als Prüfsumme in Liste und Details
- Platzhalter für den Lobby-Chat
- Reiter „Spielfeld · Test“ (Spielfeld-Prototyp)

Noch nicht umgesetzt (Plan P4 bis P6, siehe `_docs/ai_context/03_NEXT_SESSION.md`):

- Leiste „Deine Tische · x von 3“ mit allen eigenen Anmeldungen unabhängig
  vom Filter
- Hinweis „wird freigegeben, sobald X startet“ (setzt die Führungsregel
  voraus)
- Namen der Mitspieler am Mini-Tisch (das Backend liefert nur die Anzahl)
- deutsche statt englischer Fehlermeldungen beim Beitreten
- echter Chat (kommt mit der Node.js-Echtzeit-Engine)

---

## Relevante Dateien

```text
laravel-app/resources/js/components/lobby/LobbyRoomBrowser.vue
laravel-app/resources/views/lobby/index.blade.php
laravel-app/app/Services/Lobby/LobbyRoomBrowserPayloadService.php
laravel-app/tests/Feature/GameRoomLobbyTest.php
laravel-app/tailwind.config.js
```

Laravel bleibt die autoritative Quelle für Räume, Filter, Teilnahmen und
Wallet. Die Vue-Komponente prüft das Guthaben nur zur Anzeige
(„Zu teuer“, „Guthaben reicht nicht“); die verbindliche Prüfung erfolgt
serverseitig beim Beitritt.

---

## Entwicklungshistorie

1. Blade-/Server-rendered Grundlage mit Links und Query-Parametern.
2. Umbau auf eine Vue-Island-Komponente (Blade-basiert, keine SPA, kein
   Inertia).
3. 2026-11-19: Umbau auf Zielbild v3 (Lesbarkeit, Chips, Tischliste mit
   Aktionen, rechte Spalte mit Mini-Pokertisch, Dev-Only-Elemente).

---

## Aktuelles Layout

```text
[ Tischliste (Filter-Chips, Tabelle, Fußzeile) ] [ Ausgewählter Tisch ]
[                                              ] [ Chat (nur Dev)     ]
```

Technisch `lg:grid-cols-[minmax(0,1fr)_24rem]`. Die Lobby nutzt die
Layout-Option `fit-viewport` von `x-app-layout`: Ab `lg` ist die Seite genau
fensterhoch (mindestens 720px), Seitenkopf und Footer bleiben immer sichtbar,
und der Lobby-Bereich füllt den Rest. Die Tischliste scrollt intern.
Unterhalb von `lg` stapeln sich die Bereiche, die Seite scrollt normal und
die Liste ist auf 60vh begrenzt.

---

## Eigene Tische angeheftet

Tische, an denen der Benutzer teilnimmt, stehen immer oben in der Liste. Das
Backend (`LobbyRoomQueryService::getFilteredRooms`) liefert sie unabhängig vom
Filter zuerst; abgesichert durch
`test_lobby_payload_lists_joined_rooms_first_even_when_filters_hide_them`.

Im Frontend (`tableSections` in `LobbyRoomBrowser.vue`) besteht die Liste aus
zwei Bereichen mit derselben Spaltenaufteilung (`colgroup`):

```text
[ Spaltenköpfe                       ]  fest
[ eigene Tische (leicht hinterlegt)  ]  fest
=========== gelbe Trennlinie ==========
[ übrige Tische                     |]  scrollt, Scrollbalken nur hier
```

Beide Bereiche reservieren mit `scrollbar-gutter: stable` gleich viel Platz
für den Scrollbalken, damit die Spalten exakt übereinanderstehen. Der
Zeilenaufbau existiert nur einmal (gemeinsame Schleife über beide Bereiche).
Namen brechen nicht um, sondern werden gekürzt.

---

## Mobile Ansicht (unter `lg`, Stand 2026-11-19)

Umgesetzt nach `_docs/images/lobby-mobil-entwurf-v1.png` (nicht versioniert),
zunächst nur für die Lobby:

- Seite app-artig genau bildschirmhoch (`100dvh`), kein Seiten-Footer.
- Kompakter Kopf (`SiteHeader` mit `mobileCompact`): Logo mit Benutzername
  darunter, Spielgeld mit reserviertem Betrag, Menü-Knopf mit
  „Angemeldet als …“, allen Navigationslinks und Logout.
  Seitentitel und Info-Kasten entfallen mobil.
- Filterleiste: Knopf „Filter“ mit Zahl aktiver Filter und Schnellwahl der
  Tischgröße. Der Knopf öffnet ein Filter-Sheet mit allen Gruppen (Chips
  wirken sofort, „x Tische anzeigen“ schließt nur).
- Liste randlos mit zweizeiligen Zeilen: Name mit Hinweisen, Sitzpunkte und
  „n / max · Platz“ bzw. „· Pool“, rechts Buy-in und Aktion. Eigene Tische
  bleiben wie am Desktop als fester Block oben.
- Tisch antippen öffnet die Details als Bottom-Sheet (Mini-Pokertisch oben,
  Buy-in und Gewinnpool nebeneinander, Aktionen). Schließen per Tippen auf den
  Hintergrund oder den Griff oder per Wischen nach unten.
- Start-Banner über der Liste, sobald ein eigener Tisch startet (Countdown,
  „Zum Tisch“).
- Dev-Reiter „Spielfeld“ und Chat-Platzhalter sind mobil ausgeblendet; der
  Raumcode erscheint im Dev-Modus nur im Detail-Sheet.
- Animationen (Vue `<Transition>`): Sheets gleiten von unten herein und
  blenden dabei auf, Hintergrund und Start-Banner blenden ein und aus, das
  Menü im Kopf klappt von oben auf. Bei „Bewegung reduzieren“
  (`motion-reduce`) ohne Animation. Damit das Detail-Sheet beim Schließen
  nicht leer herausgleitet, zeigt es bis zum Ende der Animation den zuletzt
  gewählten Tisch (`detailRoom`). Die Desktop-Erkennung (`isDesktop`) nutzt
  dieselbe Grenze wie Tailwind `lg` (1024px); auf dem Desktop wird nichts
  animiert.

---

## Raumauswahl

- Klick auf eine Zeile wählt den Tisch aus, erneuter Klick hebt die Auswahl
  auf.
- Der Name ist zusätzlich ein Link mit der alten Deep-Link-URL
  (`/lobby?room=…`) und per Tastatur erreichbar.
- Beitreten direkt in der Zeile wählt den Tisch gleichzeitig aus.

---

## Plätze und Mini-Pokertisch

Das Backend liefert pro Tisch nur `activePlayersCount`, `maxPlayers` und den
eigenen `seatNumber`. Der eigene Platz wird an seiner Platznummer markiert,
fremde belegte Plätze werden der Reihe nach aufgefüllt. Am Mini-Tisch sitzt
der eigene Platz (sonst Platz 1) immer unten, die übrigen Plätze sind
gleichmäßig im Oval verteilt. Die Darstellung als Pokertisch ist laut
Projektinhaber die Grundlage für das spätere Spielfeld.

---

## Raumversorgung und Raumerstellung

Die Lobby zeigt vorbereitete Spielräume. Die fachliche Raumversorgung gehört zur Phase Wallet/Buy-in/Lobby.

Wichtig für die Einordnung:

- Räume werden nicht als endgültiges Spiel verstanden.
- Räume sind zunächst vorbereitete Sit-and-Go-/Tischangebote.
- Der aktuelle UI-Stand zeigt verfügbare Raumkandidaten und deren Parameter.
- Spätere Logik muss entscheiden, wann ein Raum betreten, reserviert, gefüllt und gestartet wird.

Die Raumerstellung wurde zunächst im Laravel-/Blade-Kontext betrachtet und danach für die Anzeige und Interaktion in die Vue-Insel überführt.

Bei Änderungen an der Raumversorgung sind Controller, Services, Tests und Vue-Props gemeinsam zu prüfen.

---

## Kritischer Hinweis: Tailwind muss Vue-Dateien scannen

Dieser Punkt ist wichtig und darf bei zukünftigen UI-Problemen nicht vergessen werden.

Tailwind erzeugt nur CSS-Klassen, die es beim Content-Scan findet. Wenn Klassen ausschließlich in Vue-Komponenten stehen, Tailwind aber nur Blade-Dateien scannt, werden diese Klassen nicht ins CSS geschrieben.

Dann sieht man im Browser zwar die richtigen Klassen im HTML, aber sie haben keine Wirkung.

### Konkreter Fehlerfall

Beim Lobby-Raumbrowser waren Klassen wie diese in der Vue-Komponente vorhanden:

```text
lg:grid-cols-2
lg:grid-cols-5
lg:col-span-4
lg:col-span-1
```

Trotzdem griff das Layout nicht korrekt.

Ursache:

```text
tailwind.config.js hat nur Blade-Dateien gescannt.
resources/js/**/*.vue war nicht im Tailwind-content-Array enthalten.
```

Folge:

```text
Vue renderte korrekt.
Die Klassen standen im DOM.
Tailwind hatte aber kein CSS dafür erzeugt.
Das Layout blieb optisch kaputt.
```

### Erforderliche Tailwind-Konfiguration

Die Tailwind-Konfiguration muss Vue- und JS-Dateien scannen:

```js
content: [
    './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
    './storage/framework/views/*.php',
    './resources/views/**/*.blade.php',
    './resources/js/**/*.js',
    './resources/js/**/*.vue',
],
```

### Diagnose-Regel

Wenn ein Vue-Layout optisch nicht greift:

1. Im Browser prüfen, ob die erwarteten Klassen im HTML vorhanden sind.
2. Wenn ja: prüfen, ob die Klassen im erzeugten CSS vorhanden sind.
3. Wenn nicht: `tailwind.config.js` prüfen.
4. Sicherstellen, dass `resources/js/**/*.vue` im Content-Scan enthalten ist.
5. Vite/Tailwind-Devserver neu starten.
6. Mit `npm run build` prüfen, ob der Production-Build sauber ist.

### Merksatz

```text
Wenn Vue die Klasse rendert, aber Tailwind sie nicht kennt, ist der Vue-Code nicht das Problem.
Dann fehlt fast immer der passende Tailwind-content-Scan.
```

---

## Vite-Hinweis

Nach Änderungen an `tailwind.config.js` sollte der laufende Vite-Devserver normalerweise neu gestartet werden.

Typischer Ablauf:

```powershell
cd "E:\Projekte\stechen-mmo\laravel-app"
npm run dev
```

Bei laufendem Devserver:

```text
Strg + C
npm run dev
```

Für eine technische Prüfung vor Commit:

```powershell
cd "E:\Projekte\stechen-mmo\laravel-app"
npm run build
```

---

## Tests und Prüfung

Für Änderungen am Lobby-Raumbrowser sind sinnvoll:

```powershell
cd "E:\Projekte\stechen-mmo\laravel-app"
npm run build
```

Zusätzlich vor Commit, wenn Laravel-Projektstand geprüft werden soll:

```powershell
cd "E:\Projekte\stechen-mmo\laravel-app"
composer test
```

Bekannter aktueller Testhinweis:

```text
Deprecation-Warnings zu PDO::MYSQL_ATTR_SSL_CA können auftreten.
Diese sind nicht automatisch Testfehler.
```

---

## Abgrenzung zum Spielfeld-Prototyp

Nicht verwechseln:

```text
docs/lobby-field-prototype.md
```

beschreibt den späteren Spielfeld-/Sitzplatz-/Sektor-Prototyp.

Diese Datei beschreibt:

```text
docs/lobby-room-browser-vue-state.md
```

und damit:

- Raumliste;
- Filter;
- Rauminformationen;
- clientseitige Auswahl;
- Vue-Island-Struktur;
- Tailwind-/Vite-Fallstrick;
- vorbereitete Lobby-Interaktion vor echtem Spielbeitritt.

---

## Geklärte Grundlagen und offene Umsetzungsfragen

Einige Punkte zur späteren Lobby-Teilnahme sind fachlich bereits geklärt und sollen nicht mehr als vollständig offene Fragen behandelt werden.

Bereits geklärte Grundlagen:

- Spieler erstellen normale Spielräume nicht selbst; Räume werden systemseitig bereitgestellt und gepflegt.
- Normale Ersatzräume sollen im Tagesbetrieb nicht manuell durch Admins erzeugt werden müssen.
- Admin- oder Eventräume sind ein Sonderfall und müssen später klar als besondere Räume gekennzeichnet werden, zum Beispiel WM-Eventräume, Geburtstagsräume oder andere Spezialaktionen.
- Ein Benutzer darf einem Raum nur beitreten, wenn Spielberechtigung, Accountstatus, E-Mail-Bestätigung, vollständige UserDetails, Raumstatus, Kapazität und verfügbares Guthaben passen.
- Sichtbar ist nicht gleich betretbar: Räume dürfen in der Lobby angezeigt werden, auch wenn ein Benutzer wegen fehlender St$ oder fehlender Spielberechtigung nicht beitreten kann.
- Buy-ins werden vor Spielstart reserviert.
- Bei Ausstieg oder Abbruch vor Spielstart wird die Reservierung vollständig freigegeben.
- Bei Spielstart werden Buy-ins committed; erst dann entstehen Preispool und Rake.
- Kein Spielstart bedeutet kein Rake.
- Mehrere Vorstart-Anmeldungen in verschiedenen Räumen sind erlaubt, sofern das verfügbare Wallet für alle Reservierungen ausreicht.
- Mehrere gleichzeitig laufende Spiele pro Spieler sind nicht erlaubt.
- Das zuerst startende Spiel übernimmt die Führung für den Spieler.
- Sobald ein Spiel die Führung übernommen hat, wird der Spieler aus allen anderen wartenden Räumen entfernt.
- Die dort reservierten Buy-ins werden 1:1 ohne Abzüge freigegeben beziehungsweise erstattet.
- Laravel bleibt autoritativ.
- Polling bleibt mindestens Fallback.
- HomeServer oder WebSocket sind nur optionale spätere Realtime-Beschleuniger und nicht Quelle der Wahrheit.

Für Sit'n'Go-artige Räume ist eine kurze Startverzögerung vorgesehen:

- Ein startbereiter Raum startet nicht sofort hart.
- Stattdessen geht er für ungefähr 5 bis maximal 10 Sekunden in eine Startphase.
- In dieser Startphase werden finale Prüfungen durchgeführt.
- Geprüft wird insbesondere, ob Spieler parallel in weiteren Räumen angemeldet sind.
- Wenn mehrere Räume mit überschneidenden Spielern fast gleichzeitig starten wollen, entscheidet der Startphasen-Zeitstempel.
- Der zuerst in die Startphase gegangene Raum erhält die Führung.
- Spätere konkurrierende Räume verlieren die betroffenen Spieler.
- Wenn dadurch die Mindestspielerzahl unterschritten wird, wird der spätere Raum abgebrochen oder wieder in einen wartenden Zustand versetzt.
- Betroffene Spieler erhalten einen Hinweis, warum der Raum nicht gestartet ist.
- Reservierte Buy-ins aus nicht gestarteten oder abgebrochenen Räumen werden vollständig ohne Abzüge freigegeben.

Für Raumlisten und Betrieb gelten folgende Zielrichtungen:

- Beim Betrieb der App soll grundsätzlich eine Raumliste vorhanden sein.
- Diese Raumliste wird fortlaufend aktualisiert.
- Die konkrete technische Aktualisierung der Lobby ist noch zu definieren, voraussichtlich zunächst über Laravel und Polling beziehungsweise später optional beschleunigt über Realtime-Signale.
- Ein regelmäßiger Wartungszeitraum ist denkbar, zum Beispiel einmal pro Woche.
- Während eines Wartungszeitraums sollen keine neuen Spiele mehr starten.
- Laufende Spiele sollen nicht hart abgebrochen werden, sondern zu Ende laufen.
- Erst wenn keine Spiele mehr laufen, kann eine kurze echte Wartungspause aktiv werden.
- Online-Benutzer können in dieser Phase temporär in einen Wartungsmodus geschickt beziehungsweise ausgesperrt werden.
- Wartung soll möglichst automatisiert erfolgen.
- Ein Admin soll nur bei schwerwiegenden Problemen eingreifen müssen.

Der echte Chat ist bewusst nachgelagert. Er wird erst sinnvoll, wenn mindestens diese Voraussetzungen erfüllt sind:

- die Lobby ist vollständig umgesetzt;
- die Spiellogik ist implementiert;
- mindestens Autopilot- beziehungsweise KI-Logik Stufe 1 ist implementiert.

HomeServer oder WebSocket werden ebenfalls nicht sofort benötigt. Sie werden erst sinnvoll eingebunden, wenn eine Alpha-Version existiert, in der mindestens testweise 1-vs-1-Spiele möglich sind, idealerweise auch mit KI-Spieler.

Noch technisch zu konkretisieren sind vor allem:

- konkrete Umsetzung der Spielberechtigungsprüfung, zum Beispiel über PlayerEligibilityService, Policy oder Gate;
- atomare Join- und Buy-in-Reservierungslogik;
- Transaktionen, Locks und Idempotency-Regeln für parallele Raumstarts;
- Datenbank-Constraints für Raumteilnahmen und Buy-in-Reservierungen;
- konkrete Raumstatuswerte und Statusübergänge;
- genaue Startbedingungen für Sit'n'Go- und Scheduled-Räume;
- technische Raumversorgung und Ersatzraumerzeugung über Admin-Bereich oder Monitoring-System;
- Lobby-Aktualisierung per Polling, Versionierung oder später optional WebSocket;
- UI-Hinweise, warum ein sichtbarer Raum nicht betretbar ist oder warum ein Start abgebrochen wurde.

---

## Kurzfassung

Der Lobby-Raumbrowser ist aktuell eine Vue-Island-Komponente innerhalb der Laravel-/Blade-Anwendung.

Laravel liefert die Daten. Vue übernimmt die lokale Interaktion. Tailwind gestaltet das Layout.

Der wichtigste technische Fallstrick ist der Tailwind-Content-Scan:

```text
Vue-Dateien müssen in tailwind.config.js enthalten sein.
Sonst existieren Vue-only Tailwind-Klassen nicht im erzeugten CSS.
```
