# Lobby-Spielfeld-Prototyp

Stand: 2026-06-18

## Status

Der Spielfeld-Bereich der Lobby ist aktuell ein Prototyp.

Der UI-Einstieg über den Lobby-Tab ist bewusst deaktiviert. Der direkte URL-Zugriff bleibt für Entwicklung und Tests weiterhin möglich.

Grund: Die aktuelle Sektor- und Sitzplatzlogik ist als technische Grundlage nützlich und kann später für die echte Anzeige des aktiven Spielers verwendet werden.

## Direkte Test-URLs

Die Spielerzahl wird über den Query-Parameter `seats` gesteuert. Gültig ist aktuell der Bereich 2 bis 11.

- `/lobby?tab=field&seats=2`
- `/lobby?tab=field&seats=3`
- `/lobby?tab=field&seats=4`
- `/lobby?tab=field&seats=5`
- `/lobby?tab=field&seats=6`
- `/lobby?tab=field&seats=7`
- `/lobby?tab=field&seats=8`
- `/lobby?tab=field&seats=9`
- `/lobby?tab=field&seats=10`
- `/lobby?tab=field&seats=11`

## Aktuelle Prototyp-Logik

- Der Hero sitzt immer unten.
- Der Hero-Sektor ist fix ca. 70 Grad breit.
- Bei 2 Spielern sitzt der Gegner oben und bekommt ebenfalls ca. 70 Grad.
- Ab 3 Spielern teilen sich die Non-Hero-Spieler den restlichen Bogen von ca. 290 Grad.
- Die Spieler-Badges sitzen jeweils in der Mitte ihres berechneten Sektors.
- Die Spielerreihenfolge läuft im Uhrzeigersinn.
- Sichtbare Sektorlinien sind ausgeblendet.
- Nur der aktuell aktive Sektor wird markiert.

## Aktive Sektor-Markierung

Die aktive Sektor-Markierung ist derzeit Testlogik:

- Bei 4 Gesamtspielern wird der Hero-Sektor markiert.
- Bei anderen Spielerzahlen wird Spieler 2 markiert.

Später soll dieser Wert aus dem echten Spielzustand kommen, zum Beispiel aus dem aktuell aktiven Spieler beziehungsweise der aktuellen Turn-Information.

## Hinweise für spätere Umsetzung

Die URL-Testlogik soll nicht als fertiges Produktverhalten verstanden werden. Sie dient dazu, die Spielfeld-Geometrie unabhängig vom späteren Spielserver oder Spielzustand zu prüfen.

Vor einer produktiven Aktivierung des Spielfeld-Tabs sollten mindestens diese Punkte geklärt sein:

- Woher kommt die echte Sitzordnung?
- Welcher Spieler ist der Hero aus Sicht des eingeloggten Accounts?
- Woher kommt die Information zum aktiven Spieler?
- Wie wird der aktive Sektor aktualisiert?
- Wie verhält sich das Layout bei laufender Runde, Stich, Ablage und Chat?
