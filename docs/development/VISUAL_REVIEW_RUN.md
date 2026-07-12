# Visual Review Runs

## Zweck

Visual Review Runs erzeugen reproduzierbare Browserzustände als gemeinsame
Grundlage für UX-, Produkt- und Designreviews.

Diese Arbeitsweise wurde eingeführt, weil ein Screenshot den sichtbaren
Produktzustand, die Informationshierarchie und das Zusammenspiel mehrerer
Oberflächenbereiche häufig klarer vermittelt als eine reine Textbeschreibung.
Sie erleichtert dadurch fachliche Entscheidungen, ohne visuelle Bewertungen mit
der technischen Testautomatisierung zu vermischen.

## Grundprinzip

Ein Visual Review Run bildet einen vorher fachlich abgegrenzten Benutzerfluss im
Browser ab und hält ausgewählte, eindeutig benannte Zustände fest.

Dabei gelten weiterhin die normalen Projektregeln:

- Die Anwendung und ihre fachlichen Regeln bleiben maßgeblich.
- Der Browserlauf darf keine unzulässigen Produktzustände erzwingen.
- Screenshots belegen ausschließlich den sichtbar geprüften Zustand.
- Nicht erreichte Zustände werden als nicht geprüft beziehungsweise bewusst
  abgebrochen dokumentiert.
- Zugangsdaten, Tokens, Cookies und andere sensible Informationen gehören weder
  in Screenshots noch in Begleitnotizen.

## Rollenverteilung

Der Desktop-Agent stellt den vereinbarten Browserzustand reproduzierbar her,
prüft sichtbare Übergänge und Fehlerzustände und legt die Review-Artefakte
nachvollziehbar ab. Er dokumentiert außerdem technische Grenzen, Abbrüche und
Browserauffälligkeiten.

Das Architektur-, Produkt- und UX-Review bewertet anschließend die erzeugten
Zustände. Dazu gehören insbesondere Informationshierarchie, Verständlichkeit,
Konsistenz, Benutzerführung und Übereinstimmung mit dem fachlichen Zielbild.

Die Browsersteuerung ersetzt diese Bewertung nicht. Sie schafft eine gemeinsame,
visuell belastbare Entscheidungsgrundlage.

## Lokaler Review-Arbeitsbereich

Visual-Review-Artefakte liegen unter dem projektweiten, nicht versionierten
Ordner `_review/`:

```text
_review/
└── <domäne>/
    └── <datum>_<kurzbezeichnung>/
        ├── 010_<zustand>.png
        ├── 020_<zustand>.png
        └── run_notes.md
```

Der gesamte Ordner ist durch `/_review/` in `.gitignore` ausgeschlossen. Er ist
ein lokaler Arbeitsbereich und keine dauerhafte Projektdokumentation. Vorhandene
Runs dürfen nicht ungeprüft überschrieben oder gelöscht werden.

Dauerhafte Erkenntnisse, Entscheidungen und Regeln werden nach einem Review in
die passenden versionierten Dokumente unter `docs/` überführt. Screenshots
selbst bleiben standardmäßig lokal.

## Benennung der Screenshots

Screenshots erhalten eine dreistellige Schrittfolge und einen kurzen,
zustandsbezogenen Namen:

```text
010_startzustand.png
020_angemeldet.png
030_lobby.png
040_raum_beigetreten.png
```

Die Nummerierung bildet die fachliche Reihenfolge ab und lässt Raum für spätere
Zwischenschritte. Der Dateiname beschreibt den sichtbaren Zustand, nicht die
ausgeführte technische Aktion.

## Empfohlener Ablauf

Ein Visual Review Run wird vor Beginn fachlich begrenzt. Festgelegt werden der
zu prüfende Benutzerfluss, die benötigten Konten oder Rollen, die erwarteten
Zustände und die erforderlichen Screenshots.

Anschließend werden:

1. Ausgangszustand und lokale Voraussetzungen geprüft,
2. ein stabiler Browser- und Fensterzustand hergestellt,
3. die vereinbarten Zustände in fachlicher Reihenfolge aufgerufen,
4. Navigation, Rendering und relevante Aktualisierungen vor jeder Aufnahme
   abgewartet,
5. sichtbare Identität, Statusmeldungen und erwartete Zustandswechsel geprüft,
6. Konsolenfehler und Auffälligkeiten festgehalten,
7. Dateien, Größen, unterschiedliche Bildinhalte und Git-Ignore-Wirkung
   abschließend kontrolliert.

Eine kurze `run_notes.md` kann Benutzerrolle, Browser, Fensterzustand,
betrachtete Objekte, erreichte Schritte, Abbruchgrund und Auffälligkeiten
festhalten. Sie darf keine sensiblen Zugangsdaten enthalten.

## Einsatzgebiete

Visual Review Runs sind besonders sinnvoll für:

- neue oder wesentlich veränderte Benutzerflüsse,
- Informationshierarchie und responsive Darstellung,
- Mehrfenster- und Mehrbenutzerzustände,
- Rollen-, Eligibility- und Fehlerzustände,
- Übergänge zwischen fachlichen Bereichen,
- Produkt- und Designentscheidungen mit mehreren sichtbaren Zuständen,
- Abnahme eines größeren UX-Arbeitsblocks.

## Abgrenzung zu automatisierten Tests

Visual Review Runs sind keine automatisierten UI-, Feature- oder
Regressionstests. Sie liefern keine dauerhafte maschinelle Garantie und sollen
nicht bei jedem Codewechsel wiederholt werden.

Automatisierte Tests prüfen reproduzierbare technische Verträge und fachliche
Regeln. Normale Browserprüfungen verifizieren gezielt eine konkrete UI-Änderung.
Visual Review Runs ergänzen beides um eine bewusst kuratierte visuelle Grundlage
für menschliche Architektur-, Produkt- und UX-Entscheidungen.

## Fachlicher Abbruch

Ein geplanter Zustand darf nicht durch Manipulation von Testdaten, Umgehung von
Eligibility oder Verletzung anderer Produktregeln erzwungen werden.

Ist ein Schritt unter den vereinbarten Voraussetzungen fachlich nicht zulässig,
wird der Lauf an dieser Stelle beendet. Der zuletzt verlässlich erreichte
Zustand, der Abbruchgrund und die nicht erzeugten beziehungsweise nicht
geprüften Folgeartefakte werden klar dokumentiert.

Ein solcher Abbruch ist ein valides Review-Ergebnis. Er zeigt, dass die
Browserprüfung dieselben fachlichen Grenzen respektiert wie das Produkt.

## Kostenbewusster Einsatz

Browsersteuerung benötigt deutlich mehr Modellressourcen als normale
Entwicklungs-, Analyse- und Testarbeit. Visual Review Runs werden deshalb nur
eingesetzt, wenn die visuelle Zustandsfolge einen echten zusätzlichen
Erkenntniswert bietet.

Standard ist ein **Quick Visual Review** mit einem eng begrenzten Ablauf und nur
den entscheidenden Screenshots. Für alltägliche Entwicklungsarbeit bleiben
gezielte normale Browserprüfungen die bevorzugte Methode.

Ein **Full Visual Review** umfasst mehrere Rollen, Fenster, responsive Zustände
oder längere Produktflüsse. Es ist größeren UX-, Architektur- oder
Produktentscheidungen vorbehalten und muss vorab ausdrücklich abgegrenzt sein.

Ziel ist nicht eine möglichst große Screenshot-Sammlung, sondern die kleinste
visuelle Evidenz, die eine konkrete Entscheidung zuverlässig unterstützt.
