# Project Analysis Tools

Projektinterne, versionierte Werkzeuge für reproduzierbare und zunächst
ausschließlich lesende Analysen von Stechen MMO.

## Ziel

- Analysen über versionierte Profile beschreiben
- Analyseaufträge reproduzierbar ausführen
- Produktionscode und Tests später gemeinsam auswerten
- vollständige Berichte unter `_docs/analysis_output/` erzeugen
- temporäre Berichte vom Git-Repository fernhalten

## Standardaufruf

Vom Projektroot aus:

```powershell
python .\tools\project_analysis\analyze.py `
    .\tools\project_analysis\profiles\lobby-transition-ist.analysis.txt
```

## Aktueller Funktionsumfang

Der aktuelle Stand enthält:

1. Profilmodell
2. Profilparser und Validierung
3. Scanner-Grundgerüst
4. Reporter-Grundgerüst
5. TXT-Ausgabe unter `_docs/analysis_output/`

Die eigentliche Projektsuche und Priorisierung werden in weiteren
atomaren Arbeitsschritten ergänzt.

## Sicherheitsgrenzen

Die Werkzeuge sollen standardmäßig:

- ausschließlich lesend arbeiten,
- keine Projektdateien verändern,
- keine Tests, Builds oder Browserprüfungen starten,
- keine Git-Aktionen ausführen,
- Berichte ausschließlich unter `_docs/analysis_output/` schreiben.

## Profilformat

Analyseprofile liegen unter `tools/project_analysis/profiles/` und verwenden
ein bewusst einfaches, diff-freundliches Textformat.

Erforderliche skalare Felder:

- `Name`
- `Purpose`
- `Output`
- `ProjectRoot`
- `Mode`

Erforderliche Listenfelder:

- `Scope`
- `Exclude`
- `AnalysisQuestions`
- `ReportSections`
- `EvidenceRequirements`

Listenfelder werden mit einer leeren Schlüsselzeile begonnen und enthalten
anschließend Einträge mit `- `.

Sicherheitsregeln:

- `Mode` ist aktuell ausschließlich `read-only`.
- `ProjectRoot` ist aktuell ausschließlich `.`.
- `Output` muss relativ unter `_docs/analysis_output/` liegen.
- Absolute Pfade und Pfade mit `..` werden abgelehnt.
- Unbekannte, doppelte oder leere Felder führen zu einem Profilfehler.

Aktuelles Lobby-Profil:

```text
tools/project_analysis/profiles/lobby-transition-ist.analysis.txt
```

Standardaufruf:

```powershell
python .\tools\project_analysis\analyze.py `
    .\tools\project_analysis\profiles\lobby-transition-ist.analysis.txt
```

## Scanner

Der Scanner wertet die im Profil definierten Felder `Scope` und `Exclude`
aus und erstellt ein ausschließlich lesendes Dateiinventar.

Erfasst werden relative Pfade, Dateiendungen, Dateigrößen und Warnungen.

Sicherheitsverhalten:

- absolute Scope-Pfade und `..` werden abgelehnt,
- Symlinks werden nicht verfolgt,
- Pfade außerhalb des Projektroots werden nicht akzeptiert,
- Ausschlüsse werden auf Dateien und Verzeichnisse angewendet,
- Projektdateien werden nicht verändert,
- Berichte werden nur unter `_docs/analysis_output/` geschrieben.

Tests:

```powershell
python -m unittest discover `
    .\tools\project_analysis\tests `
    -p "test_*.py"
```

## Ausschlusssemantik

Ausschlüsse mit nur einem Pfadsegment, zum Beispiel `node_modules`,
`vendor`, `storage`, `.git` oder `__pycache__`, gelten auf jeder
Verzeichnisebene.

Mehrteilige Ausschlüsse, zum Beispiel `bootstrap/cache`,
`public/build` oder `_docs/analysis_output`, werden als zusammenhängende
Pfadsequenz an beliebiger Tiefe ausgewertet.

Sensible lokale Dateien müssen zusätzlich explizit im Analyseprofil
ausgeschlossen werden. Das Lobby-Profil schließt aktuell unter anderem aus:

- `laravel-app/.env`
- `laravel-app/.env.testing`
- `laravel-app/database/database.sqlite`
- `laravel-app/.phpunit.result.cache`

## Textleser

Nach der Dateiinventur liest das Werkzeug unterstützte Textdateien
ausschließlich lesend ein.

Sicherheits- und Stabilitätsregeln:

- Standardlimit: 1.000.000 Bytes pro Textdatei,
- Binärdateien werden über Nullbytes erkannt und übersprungen,
- unterstützte Kodierungen: UTF-8 mit oder ohne BOM sowie CP1252,
- Pfade außerhalb des Projektroots werden abgelehnt,
- nicht unterstützte Dateitypen werden ignoriert,
- Warnungen werden im Bericht getrennt ausgewiesen.

Der Textleser führt noch keine fachliche Bewertung durch. Er stellt nur die
sichere, zeilenorientierte Grundlage für spätere Such- und Belegfunktionen
bereit.

## Such- und Belegengine

Die Such- und Belegengine leitet reproduzierbare Suchbegriffe aus den
`AnalysisQuestions` des Profils ab.

Sie erzeugt ausschließlich belegbare Fundstellen mit:

- relativem Dateipfad,
- Quellgruppe,
- Zeilennummer,
- gekürztem Zeilentext,
- auslösendem Suchbegriff,
- zugehöriger Analysefrage.

Quellgruppen:

- `documentation`
- `backend`
- `frontend`
- `tests`
- `other`

Begrenzungen:

- maximal 40 Treffer je Suchbegriff,
- maximal 300 Treffer insgesamt,
- Überschreitungen werden als gekürzte Analyse gekennzeichnet.

Die Engine bewertet Treffer noch nicht fachlich. Sie stellt nur
reproduzierbare Rohbelege für die spätere Architektur- und UX-Auswertung
bereit.

## Quellenbalancierte Relevanzsteuerung

Die Suchengine verwendet getrennte Trefferbudgets:

- Backend: 100
- Frontend: 80
- Tests: 80
- aktuelle Dokumentation: 60
- historische Dokumentation: 20
- Sonstiges: 10

Historische Dateien unter einem `archiv`-Pfad werden separat klassifiziert.

Mehrere Suchbegriffe in derselben Zeile erzeugen nur eine Fundstelle. Die
passenden Begriffe und Analysefragen werden gesammelt.

Treffer werden nach einem reproduzierbaren Score sortiert. Code, Frontend und
Tests erhalten höhere Basiswerte als Dokumentation. Zusätzliche Bonuspunkte
gelten für Lobby-, GameRoom-, Gameplay- und Routing-Pfade.

Der Bericht weist Trefferzahl, Budget und Kürzungsstatus je Quellgruppe aus.

## Fragebezogene Verdichtung

Die Rohfundstellen werden zusätzlich pro `AnalysisQuestion` verdichtet.

Maximale Treffer je Frage und Quellgruppe:

- Backend: 8
- Frontend: 8
- Tests: 8
- aktuelle Dokumentation: 5
- historische Dokumentation: 3
- Sonstiges: 2

Die Zuordnung erfolgt ausschließlich über die Suchbegriffe, die aus der
jeweiligen Analysefrage abgeleitet wurden.

Der Bericht weist für jede Frage getrennt aus:

- Gesamtzahl der verdichteten Treffer,
- Fundstellen je Quellgruppe,
- Kürzungsstatus je Quellgruppe.

Diese Stufe erzeugt weiterhin keine fachlichen Schlussfolgerungen. Sie ordnet
und verdichtet nur belegbare Fundstellen.

## Kompakter Hauptbericht und separate Evidenzdatei

Das Werkzeug erzeugt zwei Dateien:

```text
<output>.analysis-result.txt
<output>.evidence.txt
```

Der Hauptbericht enthält Kennzahlen, Warnungen, Trefferbudgets und die
fragebezogene Verdichtung.

Die Evidenzdatei enthält das vollständige Dateiinventar und die globalen
Rohfundstellen.

`tools/project_analysis` bleibt im Scan-Inventar sichtbar, wird aber aus der
fachlichen Such- und Frageanalyse ausgeschlossen.

Zusätzlich ergänzt die Suchengine technische Synonyme für Lobbyzustand,
Spielstart, Countdown, Navigation und Reconnect.

## Architecture Object Extractor

Der Extractor erkennt deterministisch Architekturbausteine aus Pfaden und
Deklarationen.

Aktuell unterstützte Objekttypen:

- Controller
- Services
- Middleware
- Requests
- Models
- Jobs
- Events
- Listener
- Commands
- Policies
- PHP-Klassen, Interfaces und Traits
- Vue-Komponenten
- Blade-Views
- Konfigurationsdateien
- Routendateien
- Dokumentation
- Tests

Der Extractor erzeugt noch keine Beziehungen zwischen Objekten. Er stellt
zunächst nur ein reproduzierbares Architektur-Inventar bereit.
