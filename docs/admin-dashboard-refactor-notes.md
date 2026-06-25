# Admin-Dashboard-Refactor Kurznotiz

Stand: 2026-06-25

Diese Kurznotiz dokumentiert zwei kleine Projektänderungen am Admin- und Layout-Bereich. Sie ist eine sekundäre Doku-Datei und wird nicht direkt in die Haupt-KB aufgenommen.

## Admin-Dashboard

Das Admin-Dashboard `/admin` wurde von einer Closure in `routes/web.php` auf einen eigenen Controller umgestellt:

```text
App\Http\Controllers\Admin\AdminDashboardController
```

Die Route `admin.dashboard` bleibt erhalten. Die View wurde in kleinere Section-Views unterhalb von `resources/views/admin/dashboard/sections/` aufgeteilt.

Auswirkungen:

- `routes/web.php` bleibt schlanker.
- Dashboard-Daten werden zentral im Controller vorbereitet.
- Neue Admin-Karten können künftig als eigene Section ergänzt werden.
- Bestehende Admin-Aktionen wie Room-Supply-Testmodus und Startguthaben-Backfill bleiben eigene Controller/Routen.

## Design-Korrektur

Der Vue-SiteHeader wurde an die projektweite Content-Breite angepasst.

Vorher nutzte der sichtbare Vue-Header `max-w-6xl`, während Main-Content, Footer und Noscript-Fallback bereits `max-w-[1600px]` nutzten.

Jetzt sind Header, Main-Content und Footer projektweit bündig.

Betroffene Datei:

```text
laravel-app/resources/js/components/layout/SiteHeader.vue
```

## Einordnung

Diese Datei ist nur eine kurze Umsetzungsnotiz. Bei zukünftigen Admin-Dashboard-Erweiterungen ist sie hilfreich, aber nicht Teil der dauerhaft empfohlenen Haupt-KB.
