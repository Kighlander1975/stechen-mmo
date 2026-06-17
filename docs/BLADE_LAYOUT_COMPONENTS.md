# Blade Layout Components

Diese Notiz dokumentiert projektspezifische Stolperstellen bei Blade-Layouts, Class Components und Vue-Islands im Laravel-Projekt Stechen-MMO.

## Layout-Systeme im Projekt

Aktuell existieren mehrere Layout-Systeme parallel:

- `resources/views/components/layouts/app.blade.php` für Views mit `x-layouts.app`
- `resources/views/layouts/app.blade.php` für Views mit `x-app-layout`
- `resources/views/layouts/guest.blade.php` für Views mit `x-guest-layout`

Wichtig: Vor Layout-Änderungen immer zuerst prüfen, welches Layout die betroffene View tatsächlich verwendet.

Bekannter Stand:

- `home.blade.php` und `rules.blade.php` nutzen aktuell `x-layouts.app`.
- `dashboard.blade.php` nutzt aktuell `x-app-layout`.
- Login/Register nutzen aktuell `x-guest-layout`.

## `x-app-layout` ist eine Class Component

`x-app-layout` ist in diesem Projekt keine rein anonyme Blade-Komponente, sondern wird über diese Class Component aufgelöst:

- `app/View/Components/AppLayout.php`

Die Component rendert anschließend:

- `resources/views/layouts/app.blade.php`

Das ist wichtig, weil neue Props für `x-app-layout` nicht nur in der Blade-Datei per `@props` ergänzt werden dürfen.

Neue Props, die an `<x-app-layout ...>` übergeben werden sollen, müssen im Constructor von `App\View\Components\AppLayout` ergänzt werden.

Beispiel:

- `showWalletPanel`
- `playMoneyBalanceUnits`
- Header-Props wie `headerEyebrow`, `headerTitle`, `headerStatusLabel`, `headerStatusTone`

`@props` in `resources/views/layouts/app.blade.php` allein reicht bei dieser Class Component nicht zuverlässig, wenn die Class Component die Props nicht kennt.

## Prop-Fluss für Dashboard/Header-Werte

Für Dashboard- und Header-Props über `x-app-layout` gilt dieser Fluss:

1. `resources/views/dashboard.blade.php`
2. `App\View\Components\AppLayout::__construct()`
3. `resources/views/layouts/app.blade.php`
4. `<x-site-header>`
5. `resources/views/components/site-header.blade.php`
6. Vue-Island `resources/js/components/layout/SiteHeader.vue`

Wenn Werte im finalen HTML beziehungsweise im JSON von `data-props` auf Defaults bleiben, zuerst diesen gesamten Pfad prüfen.

## Typisches Symptom

Ein typisches Symptom ist:

- Die Attribute stehen sichtbar in `dashboard.blade.php`.
- `resources/views/layouts/app.blade.php` enthält passende `@props`.
- Trotzdem steht im gerenderten `data-props`-JSON weiterhin ein Defaultwert, zum Beispiel:
  - `showWalletPanel` bleibt `false`
  - `playMoneyBalanceUnits` bleibt `0`

In diesem Fall nicht blind zwischen kebab-case, camelCase, Doppelpunkt-Attributen oder `$attributes->get()` wechseln.

Zuerst prüfen:

1. Ist `x-app-layout` beteiligt?
2. Existiert beziehungsweise betrifft es `app/View/Components/AppLayout.php`?
3. Sind die neuen Props im Constructor von `AppLayout` definiert?
4. Wird danach `php artisan optimize:clear` ausgeführt?
5. Kommen die Werte in `resources/views/layouts/app.blade.php` an?
6. Werden die Werte korrekt an `<x-site-header>` weitergereicht?
7. Werden die Werte in `resources/views/components/site-header.blade.php` korrekt in `data-props` serialisiert?
8. Erwartet `SiteHeader.vue` die Props in der passenden Struktur?

## Cache-Hinweis

Nach Änderungen an `AppLayout.php`, Layout-Props oder dem Prop-Fluss ist dieser Befehl sinnvoll, bevor Testergebnisse bewertet werden:

`php artisan optimize:clear`

Der Befehl wird im Laravel-Pfad ausgeführt:

`D:\Projekte\stechen-mmo\laravel-app`

## Tests und Browser-Prüfung

Bei Änderungen am Layout-/Header-/Vue-Island-Prop-Fluss sollten nach Möglichkeit beide Ebenen geprüft werden:

- Feature-Test gegen das gerenderte HTML beziehungsweise `data-props`
- Browser-Test gegen die sichtbare UI

Für Laravel-Tests wird im Laravel-Pfad typischerweise verwendet:

`composer test`

Bekannte Deprecation-Warnings zu `PDO::MYSQL_ATTR_SSL_CA` sind aktuell nicht automatisch Testfehler.
