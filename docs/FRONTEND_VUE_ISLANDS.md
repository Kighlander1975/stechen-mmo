# Frontend: Vue-Island-Architektur

Stand: Phase 2 Abschluss

## Ziel

Stechen-MMO nutzt Laravel/Blade weiterhin als serverseitiges Fundament. Dynamische Frontend-Elemente werden schrittweise als kleine Vue-Inseln eingebunden.

Dieses Muster verbindet:

- Laravel-Routing, Authentifizierung und CSRF-Schutz
- Blade-Layouts für stabile serverseitige Seitenstruktur
- Vue 3 für gezielte interaktive UI-Bereiche
- Vite als Build- und Entwicklungswerkzeug

## Zentrale Dateien

### Vue-Integration

- `resources/js/app.js`
- `resources/js/vue-islands.js`
- `resources/js/components/layout/SiteHeader.vue`
- `vite.config.js`

### Blade-Integration

- `resources/views/components/site-header.blade.php`
- `resources/views/components/layouts/app.blade.php`
- `resources/views/layouts/app.blade.php`
- `app/View/Components/AppLayout.php`

## Vue-Island-Mounting

Vue-Komponenten werden über HTML-Attribute in Blade eingebunden.

Ein Blade-Element kann dafür folgende Attribute besitzen:

- `data-vue-component`
- `data-props`

Der generische Mounter in `resources/js/vue-islands.js` sucht diese Elemente und mountet die registrierte Vue-Komponente.

Aktuell registriert:

- `site-header`

## SiteHeader

Der zentrale Seitenkopf wird über die Blade-Komponente `x-site-header` bereitgestellt.

Die Vue-Komponente rendert:

- Brand-Link
- Navigation
- Auth-abhängige Menüeinträge
- Logout-Button
- Eyebrow
- Seitentitel
- Status-Badge

## Datenübergabe Blade zu Vue

Die Props werden in `site-header.blade.php` serverseitig als JSON erzeugt und sicher in das HTML-Attribut `data-props` geschrieben.

Dabei werden JSON-HEX-Optionen verwendet, um kritische Zeichen sicher zu maskieren.

Wichtig:

- Das JSON wird in einfache HTML-Attribut-Anführungszeichen eingebettet.
- Der Browser dekodiert HTML-Entities im Attributwert.
- Der Vue-Island-Mounter liest die Daten über `dataset.props`.

## Authentifizierung und Logout

Der Header berücksichtigt den Laravel-Auth-Status serverseitig.

Bei eingeloggten Nutzern werden zusätzliche Links angezeigt:

- Dashboard
- Admin, falls berechtigt
- Profil
- Logout

Logout bleibt bewusst ein POST-Request mit CSRF-Schutz.

Wichtig:

- Kein Logout per GET-Link.
- CSRF-Token wird serverseitig übergeben.
- Die Vue-Komponente erzeugt beim Klick ein Formular und sendet dieses per POST ab.

## Noscript-Fallback

`x-site-header` enthält einen serverseitigen `<noscript>`-Fallback.

Dadurch bleiben Navigation und Logout auch ohne JavaScript grundsätzlich nutzbar.

## Layout-Status

Aktuell harmonisierte Layouts:

- `x-layouts.app`
- `x-app-layout`

Beide nutzen den gemeinsamen `x-site-header`.

Das authentifizierte Layout `x-app-layout` basiert auf einer Laravel-Klassen-Komponente. Header-Daten werden deshalb über Constructor-Properties in `App\View\Components\AppLayout` entgegengenommen.

Relevante Properties:

- `headerEyebrow`
- `headerTitle`
- `headerStatusLabel`
- `headerStatusTone`

## Profilseite und Form-Design

Die Profilseite wurde an das dunkle Stechen-MMO-Design angepasst.

Angepasst wurden unter anderem:

- Profil-Cards
- Eingabefelder
- Buttons
- Validierungsfehler
- Löschmodal
- globale Scrollbar-Farben
- stabiles Scrollbar-Gutter für Modals

Das Löschmodal sperrt weiterhin das Hintergrund-Scrolling, ohne dabei sichtbare Layoutsprünge durch verschwindende Scrollbars zu verursachen.

## Tests

Die Laravel-Tests laufen isoliert gegen SQLite in-memory.

Standardbefehl im Laravel-Verzeichnis:

`composer test`

Bekannte Deprecation-Warnungen zu `PDO::MYSQL_ATTR_SSL_CA` können auftreten und sind aktuell keine funktionalen Testfehler.

## Build

Der Frontend-Build läuft über Vite.

Standardbefehl im Laravel-Verzeichnis:

`npm run build`

## Hinweise für Phase 3

Für neue interaktive Bereiche sollte bevorzugt das bestehende Vue-Island-Muster verwendet werden.

Empfohlenes Vorgehen:

1. Serverdaten in Blade vorbereiten.
2. Props sicher als JSON an ein Island übergeben.
3. Vue-Komponente klein und klar abgegrenzt halten.
4. Auth-, Rechte- und Sicherheitsentscheidungen weiterhin serverseitig treffen.
5. Fallbacks oder robuste Blade-Ausgaben vorsehen, wenn die Funktion ohne JavaScript relevant bleibt.

Mögliche zukünftige Vue-Islands:

- Spielraum-Liste
- Lobby-Status
- Rundenübersicht
- Spielerstatus
- Server-/Homeserver-Verbindung
- Wallet-/Feature-Status, solange rechtlich und technisch freigegeben
