# Lobby-Raumbrowser mit Vue-Island

Stand: 2026-06-24

## Zweck

Diese Datei dokumentiert den aktuellen Stand des Lobby-Raumbrowsers in Stechen-MMO.

Die Lobby ist aktuell der vorbereitete Einstiegspunkt für Spielräume. Sie zeigt verfügbare Räume, Filter, Raumdetails und einen vorbereiteten Chatbereich. Der tatsächliche Beitritt, die Reservierung von Buy-ins und der spätere Spielstart sind noch nicht produktiv aktiv, aber das UI ist darauf vorbereitet.

Diese Datei ergänzt den älteren Spielfeld-Prototyp unter:

```text
docs/lobby-field-prototype.md
```

Der Spielfeld-Prototyp beschreibt die spätere Sitzplatz- und Sektorlogik. Diese Datei beschreibt dagegen den aktuellen Raumbrowser, die Raumliste, die Rauminformationen und die Vue-basierte UI-Struktur.

---

## Aktueller Status

Der Lobby-Raumbrowser ist als Vue-Island umgesetzt.

Aktuell vorhanden:

- Raumfilter für Status, Buy-in, Startmodus und Tischgröße.
- Liste verfügbarer Spielräume.
- Detailbereich für den ausgewählten Raum.
- Clientseitige Auswahl eines Raums ohne Seitenreload.
- Erneuter Klick auf den ausgewählten Raum hebt die Auswahl wieder auf.
- Vorbereiteter globaler Lobby-Chat als Platzhalter.
- Deaktivierter Beitreten-Button als Vorbereitung für spätere Spielteilnahme.
- Responsive Tailwind-Struktur für die Hauptbereiche der Lobby.

Noch nicht produktiv aktiv:

- echter Raumbeitritt;
- Buy-in-Reservierung;
- Spielstart;
- laufende Spielzustände;
- echter Chat;
- Homeserver-/WebSocket-Realtime-Anbindung.

---

## Relevante Dateien

Aktuell besonders relevant:

```text
laravel-app/resources/js/components/lobby/LobbyRoomBrowser.vue
laravel-app/tailwind.config.js
```

Der Vue-Raumbrowser wird als Insel in die Laravel/Blade-Seite eingebunden. Laravel bleibt weiterhin die autoritative Quelle für Räume, Filter und spätere Spielzustände.

Die genaue Blade-Mount-Datei und Controller-/Service-Dateien sollten bei konkreten Änderungen am Datenfluss erneut geprüft werden, statt aus dieser Dokumentation blind abgeleitet zu werden.

---

## Entwicklungshistorie

Der Lobby-Raumbrowser entstand schrittweise.

### 1. Blade-/Server-rendered Grundlage

Zunächst wurde die Lobby stärker Blade-/Server-rendered gedacht. Die Raumliste und Raumdetails konnten über Links und Query-Parameter dargestellt werden.

Dieses Modell ist robust als Fallback und passt gut zu Laravel, führt aber bei Detailwechseln schnell zu Seitenreloads oder stärker serverseitiger Zustandsführung.

### 2. Umbau auf Vue-Island

Der Raumbrowser wurde anschließend in eine Vue-Komponente verschoben.

Ziel:

- bessere Interaktion im Browser;
- Raumdetails ohne Seitenreload aktualisieren;
- Layout und Zustand an einer klaren Stelle kapseln;
- Laravel weiterhin als Datenquelle behalten;
- keine vollständige SPA/Inertia-Architektur einführen.

Das Projekt bleibt weiterhin Blade-basiert mit Vue-3-Inselarchitektur. Inertia.js ist nicht Projektbasis.

---

## Aktuelles Layout

Der aktuelle Lobby-Raumbrowser ist in zwei Hauptzeilen gegliedert.

### Obere Zeile

```text
Filter | Rauminformationen
```

Technisch:

```text
lg:grid-cols-2
```

Die Filter und die Rauminformationen stehen ab großem Layout nebeneinander.

### Untere Zeile

```text
Raumliste breit | Chat schmal
```

Technisch:

```text
lg:grid-cols-5
lg:col-span-4 für die Raumliste
lg:col-span-1 für den Chat
```

Die Raumliste nimmt den Hauptbereich ein. Der Chat ist aktuell bewusst schmal und als vorbereiteter Platzhalter umgesetzt.

---

## Raumauswahl

Die Raumauswahl erfolgt aktuell clientseitig in Vue.

Der ausgewählte Raumcode wird lokal in der Komponente gehalten. Die Details werden aus der vorhandenen Raumliste ermittelt.

Verhalten:

- Klick auf einen Raum wählt ihn aus.
- Die Detailkarte aktualisiert sich ohne Seitenreload.
- Klick auf den bereits ausgewählten Raum hebt die Auswahl auf.
- Die alten Raum-URLs bleiben als Fallback-/Deep-Link-Struktur im Code erhalten.
- Der Klick wird in der Vue-Komponente aktuell per `@click.prevent` abgefangen.

Damit verbindet der aktuelle Stand eine serverseitig vorbereitete Datenbasis mit einer clientseitigen UI-Interaktion.

---

## Leerer Detailzustand

Wenn kein Raum ausgewählt ist, sieht die Rauminformationskarte bewusst genauso aus wie bei einem ausgewählten Raum.

Der leere Zustand verwendet Platzhalterwerte statt eines abweichenden Hinweisblocks.

Beabsichtigtes Verhalten:

```text
RAUMINFORMATIONEN                     VORBEREITET
Kein Raum ausgewählt
-

BUY-IN        -
SPIELER       -
START         -
STATUS        -
GEWINNPOOL    -
              Raum auswählen

Beitritt, Reservierung und Spielstart folgen später.
Beitreten deaktiviert
```

Grund:

- kein Layoutsprung beim Auswählen eines Raums;
- gleiche Kartenstruktur für leeren und gefüllten Zustand;
- optisch ruhiger und konsistenter;
- spätere Beitrittslogik kann denselben Detailbereich weiterverwenden.

---

## Beitreten-Button

Der Button ist aktuell vorbereitet, aber deaktiviert.

Er soll später erst aktiv werden, wenn die fachlichen Bedingungen geklärt und implementiert sind, zum Beispiel:

- Benutzer ist authentifiziert und verifiziert;
- Raum ist offen;
- Raum ist nicht voll;
- Wallet/Spielgeld reicht aus;
- Buy-in kann reserviert werden;
- Benutzer ist nicht bereits im Raum;
- Spielstart-/Warteschlangenlogik ist definiert.

Bis dahin bleibt der Button ein UI-Platzhalter.

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
cd "D:\Projekte\stechen-mmo\laravel-app"
npm run dev
```

Bei laufendem Devserver:

```text
Strg + C
npm run dev
```

Für eine technische Prüfung vor Commit:

```powershell
cd "D:\Projekte\stechen-mmo\laravel-app"
npm run build
```

---

## Tests und Prüfung

Für Änderungen am Lobby-Raumbrowser sind sinnvoll:

```powershell
cd "D:\Projekte\stechen-mmo\laravel-app"
npm run build
```

Zusätzlich vor Commit, wenn Laravel-Projektstand geprüft werden soll:

```powershell
cd "D:\Projekte\stechen-mmo\laravel-app"
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
