# Registrierung, Accounts und Spielberechtigung

## Dokumentstatus

**Reset-Status:** analysiert und dokumentiert
**Implementierungsstatus:** überwiegend implementiert
**Offene Aufgaben:** vorhanden, bewusst noch nicht umgesetzt
**Maßgebliche Grundlage:** tatsächlicher Laravel-Codebestand vom Juli 2026

---

## 1. Zweck

Dieser Bereich stellt die Accountgrundlage von Stechen-MMO bereit.

Er umfasst insbesondere:

- Registrierung
- Anmeldung und Abmeldung
- E-Mail-Verifikation
- Passwortverwaltung
- Profilverwaltung
- grundlegende Accounttypen
- Mitarbeiterrollen
- technische Berechtigungen
- vorbereitete Spieler-Tier-Stufen
- die Grundlage der späteren fachlichen Spielberechtigung

Die Registrierung ist nicht nur die Anlage eines Benutzerdatensatzes.

Sie ist aktuell außerdem mit folgenden fachlichen Vorgängen verbunden:

- verpflichtende Zustimmung zu AGB und Datenschutzerklärung
- Anlage des Spielgeld-Wallets
- Vergabe des Registrierungsbonus
- Erzeugung nachvollziehbarer Reward- und Ledger-Daten

---

## 2. Beteiligte Komponenten

Der aktuelle Registrierungs- und Accountbereich verwendet insbesondere:

### Models

- `app/Models/User.php`

### Controller

- `app/Http/Controllers/Auth/RegisteredUserController.php`
- `app/Http/Controllers/Auth/AuthenticatedSessionController.php`

### Middleware

- `app/Http/Middleware/EnsureUserHasPermission.php`

### Services

- `app/Services/RewardService.php`
- indirekt die Wallet- und Ledger-Services

### Views

- `resources/views/auth/register.blade.php`
- weitere Laravel-Breeze-Views unter `resources/views/auth/`

### Routen

- `routes/auth.php`
- geschützte Anwendungsrouten unter `routes/web.php`

### Datenbank

- Basis-Migration der Benutzer
- Migration für Accounttypen, Rollen und Berechtigungen
- Wallet-, Reward- und Ledger-Tabellen

### Tests

- `tests/Feature/Auth/RegistrationTest.php`
- `tests/Feature/Auth/AuthenticationTest.php`
- `tests/Feature/Auth/EmailVerificationTest.php`
- weitere Breeze-Authentifizierungstests
- `tests/Feature/AccessControlTest.php`

---

## 3. Aktueller Registrierungsablauf

Der aktuelle Ablauf ist:

1. Ein Gast öffnet das Registrierungsformular.
2. Der Gast gibt die erforderlichen Accountdaten ein.
3. Die Zustimmung zu AGB und Datenschutzerklärung muss über eine Checkbox bestätigt werden.
4. Laravel validiert die Eingaben.
5. Der Benutzeraccount wird angelegt.
6. Das Laravel-Event `Registered` wird ausgelöst.
7. Das Spielgeld-Wallet wird angelegt.
8. Der Registrierungsbonus wird über den Reward-Bereich vergeben.
9. Die Gutschrift wird über Wallet und Ledger nachvollziehbar gespeichert.
10. Der Benutzer wird angemeldet.
11. Die E-Mail-Verifikation folgt über den vorhandenen Laravel-Breeze-Ablauf.

Ohne bestätigte Checkbox wird die Registrierung abgelehnt.

Dabei entstehen weder:

- Benutzer
- Wallet
- Reward Claim
- Registrierungsbonus
- Ledger-Buchung

---

## 4. Implementierter Funktionsumfang

### 4.1 Registrierung

Implementiert und getestet:

- Namenseingabe
- E-Mail-Adresse
- Passwort und Passwortbestätigung
- serverseitige Validierung
- Anlage eines neuen Benutzeraccounts
- automatische Anmeldung nach erfolgreicher Registrierung
- Auslösen des `Registered`-Events
- verpflichtende Bestätigung der Rechtstexte
- Anlage des initialen Wallets
- Vergabe des Registrierungsbonus
- nachvollziehbare Reward- und Ledger-Daten

### 4.2 Authentifizierung

Implementiert:

- Login
- Logout
- Passwort vergessen
- Passwort zurücksetzen
- Passwort bestätigen
- Passwort ändern
- E-Mail-Verifikation
- erneuter Versand der Verifikationsnachricht

### 4.3 Profil

Grundlegende Profilverwaltung ist vorhanden.

Dazu gehören insbesondere:

- Profil anzeigen
- Profildaten bearbeiten
- Passwort ändern
- Account löschen

Eine vollständige fachliche Definition verpflichtender Spielerprofildaten ist noch nicht abgeschlossen.

---

## 5. AccountType

`AccountType` beschreibt die grundlegende Identität eines Accounts.

Aktuell vorgesehene Typen:

- `player`
- `staff`

### Player

Ein normaler Spieleraccount.

Neue registrierte Accounts werden standardmäßig als `player` angelegt.

### Staff

Ein Mitarbeiteraccount.

Ein Staff-Account ist nicht automatisch ein normaler Spieleraccount.

Soll ein Mitarbeiteraccount ebenfalls spielen dürfen, benötigt er die entsprechende technische Berechtigung.

### Fachliche Bedeutung

`AccountType` beantwortet:

> Handelt es sich grundsätzlich um einen Spieler oder um einen Mitarbeiteraccount?

`AccountType` beschreibt nicht:

- Sperrstatus
- Profilvollständigkeit
- aktuelle Spielberechtigung
- PlayerTier
- Mitarbeiterfunktion

---

## 6. StaffRole

`StaffRole` beschreibt die organisatorische Funktion eines Mitarbeiteraccounts.

Aktuell vorbereitet:

- Moderator
- Game-Admin
- Tech-Admin
- Super-Admin

Die Rollen besitzen teilweise eine fachliche Hierarchie.

Beispielsweise können höher privilegierte Rollen Funktionen niedrigerer Rollen einschließen.

### Zweck

StaffRoles sollen später unter anderem folgende Aufgaben unterstützen:

- Moderation
- Spielverwaltung
- technische Administration
- Benutzerverwaltung
- Systemverwaltung
- Bearbeitung von Sperren und Einschränkungen

`StaffRole` ist nicht identisch mit einer einzelnen technischen Permission.

---

## 7. Permissions

Permissions beschreiben konkrete technische Fähigkeiten.

Vorbereitete Beispiele sind:

- `play.game`
- `chat.use`
- `chat.moderate`
- `room.join`
- `room.create`
- `room.manage`
- `admin.access`
- `admin.users`
- `admin.system`

Die Middleware `EnsureUserHasPermission` prüft technische Zugriffsrechte.

Beispiel:

```text
permission:admin.access
```

### Abgrenzung

Permissions beantworten:

> Welche konkrete Aktion darf dieser Account technisch ausführen?

Permissions beschreiben nicht vollständig:

- ob das Spielerprofil vollständig ist
- ob ein Account fachlich gesperrt wurde
- ob ein Spieler aktuell an Echtgeldspielen teilnehmen darf
- ob Fairplay- oder Abuse-Prüfungen bestanden wurden
- ob zusätzliche regulatorische Voraussetzungen erfüllt sind

---

## 8. PlayerTier

`PlayerTier` ist ein eigenständiger, dynamischer Spielerstatus.

Aktuell vorbereitete Stufen:

1. Common
2. Bronze
3. Silber
4. Gold
5. Platin

`Common` ist die Einstiegstufe.

### Fachliche Bedeutung

Der PlayerTier soll langfristig mehrere Faktoren berücksichtigen, beispielsweise:

- Spielhäufigkeit
- Höhe der Buy-ins
- Häufigkeit von Buy-ins bestimmter Stufen
- Spielerfolg und Ergebnisse
- nachhaltige Aktivität

Der PlayerTier ist ausdrücklich nicht direkt an die Rangliste gekoppelt.

Folgender Zustand ist möglich:

```text
Ranglistenplatz: 1
PlayerTier: Bronze
```

Ebenso kann ein sehr aktiver Spieler einen hohen Tier besitzen, ohne Ranglistenerster zu sein.

### Aufstieg und Erhalt

Eine Tier-Stufe wird nicht dauerhaft verliehen.

Spieler müssen ihren Status:

- erarbeiten
- durch weitere Aktivität erhalten

Aufstieg und Abstieg sind vorgesehen.

### Spätere Verwendung

Bestimmte Tier-Stufen können später Voraussetzungen sein für:

- besondere Events
- exklusive Räume oder Turniere
- Sponsorenveranstaltungen
- Sachpreise
- Marketingkooperationen
- besondere Aktionen

### Bewusst vertagter Algorithmus

Der konkrete Tier-Algorithmus ist noch nicht festgelegt.

Diese Entscheidung wird bewusst bis zu Closed- und Open-Beta-Tests vertagt.

Gründe:

- reale Aktivitätsdaten fehlen
- echtes Buy-in-Verhalten ist noch unbekannt
- geeignete Auf- und Abstiegsgrenzen müssen datenbasiert ermittelt werden
- kurzfristige Ausreißer und Missbrauch müssen berücksichtigt werden

Die umfangreiche Protokollierung des späteren Spielbetriebs soll als Datengrundlage dienen.

---

## 9. Aktuelle Spielberechtigung

Die aktuelle Hilfsmethode `canPlayGame()` ist bewusst einfach gehalten.

Derzeit gilt grundsätzlich:

- Ein normaler `player` darf spielen.
- Ein `staff`-Account darf spielen, wenn er die Permission `play.game` besitzt.

Zusätzlich schützen bestimmte Routen bereits über:

- Authentifizierung
- E-Mail-Verifikation

### Grund für die aktuelle Vereinfachung

Der aktuelle Build wird lokal getestet.

Dafür werden mehrere Spieler über:

- verschiedene Browser
- Inkognito-Fenster
- getrennte Sessions
- lokale Testaccounts

simuliert.

Für diesen Entwicklungsstand ist die vereinfachte Regel bewusst akzeptiert.

---

## 10. Spätere fachliche Eligibility

Vor dem ersten externen Test muss die Spielberechtigung verschärft werden.

Mindestens zu prüfen sind später:

- Account ist authentifiziert
- E-Mail-Adresse ist verifiziert
- erforderliche Profildaten sind vollständig
- Account ist nicht gesperrt
- Account ist nicht eingeschränkt
- Fairplay- oder Abuse-Status erlaubt die Teilnahme
- die aktuelle Testphase erlaubt den Zugriff
- gegebenenfalls erforderliche Alters- oder regulatorische Voraussetzungen sind erfüllt
- der Account darf den konkreten Raumtyp verwenden
- Wallet- und Buy-in-Voraussetzungen sind erfüllt

Die vollständige Eligibility soll zentral gekapselt werden.

Sie darf nicht als verteilte Sammlung voneinander abweichender Controller-Prüfungen entstehen.

### Status

**Fachlich erforderlich, technisch noch nicht vollständig umgesetzt.**

Die Umsetzung erfolgt vor dem ersten externen Test.

---

## 11. AGB- und Datenschutz-Zustimmung

### Aktueller Stand

Die Registrierung verlangt bereits verbindlich die Zustimmung zu:

- AGB
- Datenschutzerklärung

Die Zustimmung erfolgt aktuell über eine Checkbox.

Ohne Zustimmung ist keine Registrierung möglich.

### Fehlende Nachweisbarkeit

Der aktuelle Build speichert noch nicht ausreichend:

- welcher konkreten AGB-Version zugestimmt wurde
- welcher konkreten Datenschutzversion zugestimmt wurde
- wann die Zustimmung erfolgte
- welche früheren Versionen bereits akzeptiert wurden
- ob nach relevanten Änderungen erneut zugestimmt wurde

Die aktuelle Checkbox ist daher funktional vorhanden, aber noch nicht vollständig historisiert und versioniert.

---

## 12. Zielmodell für Rechtstexte

Später soll ein Benutzer nicht abstrakt „den AGB“ zustimmen.

Er stimmt einer konkreten veröffentlichten Version eines Dokuments zu.

Beispiel:

```text
AGB: Version 1.0
Datenschutzerklärung: Version 1.0
```

Die Zustimmung muss mindestens zuordenbar sein zu:

- Benutzer
- Dokumenttyp
- Dokumentversion
- Zustimmungszeitpunkt

Nach einer relevanten Änderung kann eine erneute Zustimmung erforderlich werden.

Beispiel:

```text
Bisher akzeptiert: AGB 1.0
Aktuell erforderlich: AGB 1.1
Erneute Zustimmung: erforderlich
```

Nicht jede redaktionelle Änderung muss zwingend eine neue Zustimmung verlangen.

Das spätere Modell muss unterscheiden können, ob eine neue Dokumentversion erneut akzeptiert werden muss.

---

## 13. Erwartete Historisierung

Voraussichtlich wird eine eigene Historienstruktur benötigt.

Mögliche fachliche Bausteine:

### LegalDocument

Beschreibt eine konkrete veröffentlichte Version eines Rechtstextes.

Mögliche Inhalte:

- Dokumenttyp
- Version
- Titel
- Veröffentlichungszeitpunkt
- Gültigkeitszeitpunkt
- Kennzeichen für erforderliche erneute Zustimmung
- Inhalts-Hash
- Aktivstatus

### UserLegalAcceptance

Beschreibt die Zustimmung eines Benutzers zu einer konkreten Dokumentversion.

Mögliche Inhalte:

- Benutzer
- Rechtstext-Version
- Zustimmungszeitpunkt
- Kontext der Zustimmung, beispielsweise Registrierung oder erneute Zustimmung

### Datenschutzgrenze

IP-Adresse und User-Agent sollen nicht automatisch gespeichert werden.

Eine solche Speicherung benötigt vorher:

- einen klaren Zweck
- eine Datenschutzprüfung
- eine Aufbewahrungsregel
- eine ausdrückliche Architekturentscheidung

---

## 14. Statusübersicht

| Bereich | Status |
|---|---|
| Registrierung | Implementiert und getestet |
| Login und Logout | Implementiert |
| Passwortverwaltung | Implementiert |
| E-Mail-Verifikation | Implementiert |
| Profil-Grundfunktionen | Implementiert |
| AccountType | Implementiert und fachlich geklärt |
| StaffRole | Implementiert und fachlich geklärt |
| Permissions | Implementiert und fachlich geklärt |
| PlayerTier-Grundstruktur | Implementiert |
| PlayerTier-Algorithmus | Bewusst bis Beta vertagt |
| Lokale Spielberechtigung | Für Entwicklungsbetrieb ausreichend |
| Vollständige Eligibility | Vor externem Test erforderlich |
| Rechtstext-Checkbox | Implementiert und getestet |
| Rechtstext-Versionierung | Noch nicht implementiert |
| Zustimmungshistorie | Noch nicht implementiert |
| Erneute Zustimmung | Noch nicht implementiert |

---

## 15. Spätere Umsetzungstasks

Nach Abschluss der vollständigen Reset-Dokumentation entstehen mindestens folgende Tasks:

### Rechtstexte

- versioniertes Rechtstext-Modell entwerfen
- Zustimmungshistorie entwerfen
- Migrationen erstellen
- Models und Beziehungen erstellen
- Dienst zur Ermittlung der aktuell gültigen Dokumentversionen erstellen
- Registrierung auf konkrete Dokumentversionen umstellen
- Zustimmungen transaktional speichern
- erneute Zustimmung für bestehende Benutzer planen
- Tests für Registrierung und Re-Acceptance ergänzen
- Aufbewahrungs- und Datenschutzregeln dokumentieren

### Eligibility

- zentrale Eligibility-Architektur entwerfen
- Accountstatus-Modell festlegen
- Profilvollständigkeit definieren
- Sperren und Einschränkungen modellieren
- Admin-Workflows planen
- Auditierung von Statusänderungen planen
- Join- und Spielzugriffe zentral absichern
- Tests für alle relevanten Ablehnungsgründe ergänzen

### PlayerTier

Erst anhand realer Beta-Daten:

- Messgrößen festlegen
- Betrachtungszeitraum bestimmen
- Auf- und Abstiegsgrenzen kalibrieren
- Missbrauchsschutz definieren
- Event-Voraussetzungen modellieren
- Tier-Berechnung testen und simulieren

---

## 16. Definition of Done für diesen Reset-Block

Dieser Dokumentationsblock gilt als abgeschlossen, wenn:

- der tatsächliche Registrierungsstand dokumentiert ist
- AccountType, StaffRole, Permissions und PlayerTier fachlich getrennt sind
- die lokale Eligibility-Vereinfachung sichtbar dokumentiert ist
- die spätere vollständige Eligibility als offener Task erfasst ist
- die fehlende Rechtstext-Versionierung sichtbar dokumentiert ist
- die spätere Historisierung als kritische Aufgabe festgehalten ist
- noch keine vorgezogene Implementierung begonnen wurde

Diese Bedingungen sind mit dem aktuellen Dokument erfüllt.

---

## 17. Nächster Reset-Baustein

Als nächstes folgt:

`docs/reset_dokumentation/02_WALLETS_UND_LEDGER.md`

Vor dessen Erstellung werden die relevanten Models, Services, Migrationen und Tests erneut atomar anhand des tatsächlichen Codes geprüft.
