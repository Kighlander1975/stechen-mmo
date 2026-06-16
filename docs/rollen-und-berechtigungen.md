# Rollen, Spielerstatus und Berechtigungen

Dieses Dokument beschreibt das geplante Rollen-, Spielerstatus- und Berechtigungssystem von **Stechen-MMO**.

Ziel ist es, frühzeitig eine saubere Struktur für Spieler, Premium-/Aktivitätsstufen, VIP-Status, Staff-/Adminrollen und konkrete Berechtigungen vorzubereiten, ohne bereits ein vollständiges Rollen- und Rechteverwaltungssystem mit eigener Administrationsoberfläche umzusetzen.

Der aktuelle Stand dient als technische und fachliche Grundlage für spätere Entwicklungsphasen.

---

## 1. Grundprinzip

Ein Benutzerkonto kann mehrere voneinander getrennte Eigenschaften besitzen:

- Account-Typ
- Spielerstatus / Player-Tier
- VIP-Status
- Staff-/Adminrolle
- konkrete Berechtigungen / Permissions

Diese Eigenschaften werden bewusst getrennt modelliert, damit spätere Regeln klar und flexibel umgesetzt werden können.

Beispiele:

- Ein normaler Benutzer ist ein Spieler mit dem Tier `common`.
- Ein aktiver Spieler kann später `bronze`, `silver`, `gold` oder `platinum` erreichen.
- Ein Spieler kann zusätzlich VIP sein.
- Ein Moderator kann ebenfalls einen gespeicherten Spielerstatus besitzen, z.B. `moderator[silver]`.
- Adminrechte entstehen nicht durch den Spielerstatus, sondern durch Permissions.
- Staff-Accounts dürfen nur spielen, wenn sie ausdrücklich die Permission `play.game` besitzen.

---

## 2. Guests

Unangemeldete Besucher werden nicht als Rolle in der Datenbank gespeichert.

Ein Gast ist technisch einfach ein Benutzer ohne aktive Authentifizierung.

Beispiel:

```php
Auth::guest()
```

oder:

```php
! Auth::check()
```

Es gibt daher keinen Datenbankwert `guest`.

---

## 3. Account-Typen

Jeder registrierte Benutzer erhält einen Account-Typ.

Geplante Werte:

| Wert | Bedeutung |
|---|---|
| `player` | normales Spielerkonto |
| `staff` | Team-, Moderator- oder Admin-Konto |

Standard nach Registrierung:

```text
account_type = player
```

---

## 4. Player-Tiers

Jeder Benutzer erhält einen gespeicherten Spielerstatus.

Geplante Werte:

| Tier | Bedeutung |
|---|---|
| `common` | Standard nach Registrierung / Premium-Spieler Stufe 0 |
| `bronze` | Aktivitätsstatus Stufe 1 |
| `silver` | Aktivitätsstatus Stufe 2 |
| `gold` | Aktivitätsstatus Stufe 3 |
| `platinum` | Aktivitätsstatus Stufe 4 |

Standard nach Registrierung:

```text
player_tier = common
```

Der Spielerstatus soll später auf Basis von Aktivität, Verhalten oder anderen Spielkriterien verliehen werden können.

Ebenso soll es später möglich sein, diesen Status schrittweise wieder zu verlieren.

Diese automatische Vergabe und Herabstufung ist noch nicht Teil der aktuellen Umsetzung, wird aber durch die Datenstruktur vorbereitet.

---

## 5. Player-Tier bei Staff-Konten

Auch Staff-Konten können einen Player-Tier besitzen.

Beispiele:

```text
moderator[silver]
game_admin[gold]
super_admin[platinum]
```

Wichtig:

- Der Player-Tier ist kein Adminrecht.
- Der Player-Tier gibt keine technischen Administrationsrechte.
- Der Player-Tier kann bei Staff-Konten gespeichert werden, auch wenn er nicht überall sichtbar angezeigt wird.
- Ein Staff-Konto darf nicht automatisch spielen, nur weil es einen Player-Tier besitzt.

---

## 6. VIP-Status

Der VIP-Status ist ein spielerbezogener Zusatzstatus.

Geplante Spalte:

```text
is_vip
```

Standard:

```text
false
```

### Wichtige Regel

VIP gilt nur, wenn der Benutzer ein normales Spielerkonto ist:

```text
account_type = player
```

Das bedeutet:

- Spieler können VIP sein.
- Staff-/Adminrollen brauchen keinen VIP-Status.
- VIP soll bei Staff-Konten nicht wirksam sein.
- Selbst wenn `is_vip = true` technisch gesetzt wäre, soll die Anwendungslogik VIP nur für `account_type = player` auswerten.

Beispielhafte Model-Logik:

```php
public function isVip(): bool
{
    return $this->isPlayer() && (bool) $this->is_vip;
}
```

---

## 7. Staff- und Adminrollen

Staff-/Adminrollen werden getrennt vom Player-Tier gespeichert.

Geplante Spalte:

```text
staff_role
```

Geplante Werte:

| Rolle | Bedeutung |
|---|---|
| `moderator` | beschränkter Moderationszugriff |
| `game_admin` | Gameplay, Spielräume, Chaträume und Spielbetrieb |
| `tech_admin` | technische Verwaltung, Wartung, Systemstatus |
| `super_admin` | vollständiger administrativer Zugriff |

Normale Spieler haben keine Staff-Rolle:

```text
staff_role = null
```

Ein Staff-Konto hat:

```text
account_type = staff
```

und zusätzlich eine passende `staff_role`.

---

## 8. Permissions

Konkrete Rechte werden über Permissions abgebildet.

Geplante Spalte:

```text
permissions
```

Vorgeschlagener Typ:

```text
json
```

Beispiel:

```json
[
  "admin.access",
  "admin.game",
  "play.game"
]
```

Permissions steuern konkrete Funktionen und sind bewusst feiner als Rollen.

Eine Staff-Rolle beschreibt grob die Zuständigkeit. Permissions entscheiden konkret, was der Account tun darf.

---

## 9. Geplante Permissions

### Spielbezogene Permissions

| Permission | Bedeutung |
|---|---|
| `play.game` | Staff-Konto darf am Spielbetrieb teilnehmen |
| `chat.use` | Chat nutzen |
| `chat.moderate` | Chat moderieren |
| `room.join` | Spielräumen beitreten |
| `room.create` | Spielräume erstellen |
| `room.manage` | Spielräume verwalten |
| `tournament.join` | Turnieren beitreten |

### Admin-/Staff-Permissions

| Permission | Bedeutung |
|---|---|
| `admin.access` | Zugriff auf den Adminbereich |
| `admin.moderation` | Moderationsbereich nutzen |
| `admin.game` | Spielbetrieb administrieren |
| `admin.tech` | technische Verwaltung nutzen |
| `admin.users` | Benutzerverwaltung nutzen |
| `admin.system` | System-/Wartungsfunktionen nutzen |

---

## 10. Spielen als Staff-Konto

Normale Spieler dürfen grundsätzlich spielen.

Staff-Konten dürfen nicht automatisch spielen.

Ein Staff-Konto darf nur dann spielen, wenn es die Permission besitzt:

```text
play.game
```

Beispielhafte Model-Logik:

```php
public function canPlayGame(): bool
{
    return $this->isPlayer() || $this->hasPermission(self::PERMISSION_PLAY_GAME);
}
```

Damit gilt:

| Konto | Darf spielen? |
|---|---|
| `player[common]` | ja |
| `player[gold]` | ja |
| `player[gold] + vip` | ja |
| `moderator[silver]` ohne `play.game` | nein |
| `moderator[silver]` mit `play.game` | ja |
| `game_admin[gold]` mit Standardpermissions | ja |
| `tech_admin[common]` ohne `play.game` | nein |
| `super_admin[platinum]` mit Standardpermissions | ja |

---

## 11. Standardpermissions für Staff-Rollen

### Moderator

```json
[
  "admin.access",
  "admin.moderation",
  "chat.moderate"
]
```

Moderatoren dürfen standardmäßig nicht spielen, außer `play.game` wird zusätzlich vergeben.

### Game Admin

```json
[
  "admin.access",
  "admin.game",
  "admin.moderation",
  "chat.moderate",
  "room.manage",
  "play.game"
]
```

Game Admins erhalten `play.game` standardmäßig.

### Tech Admin

```json
[
  "admin.access",
  "admin.tech",
  "admin.system"
]
```

Tech Admins dürfen standardmäßig nicht spielen, außer `play.game` wird zusätzlich vergeben.

### Super Admin

```json
[
  "admin.access",
  "admin.moderation",
  "admin.game",
  "admin.tech",
  "admin.users",
  "admin.system",
  "chat.moderate",
  "room.manage",
  "play.game"
]
```

Super Admins erhalten `play.game` standardmäßig.

---

## 12. Vorgeschlagene Datenbankspalten

Für die erste technische Umsetzung sollen die folgenden Spalten in der Tabelle `users` ergänzt werden:

| Spalte | Typ | Standard | Beschreibung |
|---|---|---|---|
| `account_type` | string | `player` | Haupttyp des Kontos |
| `player_tier` | string | `common` | Spielerstatus |
| `is_vip` | boolean | `false` | VIP-Status für Spieler |
| `staff_role` | string nullable | `null` | Staff-/Adminrolle |
| `permissions` | json nullable | `[]` | konkrete Rechte |

---

## 13. Zusammenfassung

Das geplante System trennt bewusst:

```text
Account-Typ
Player-Tier
VIP-Status
Staff-Rolle
Permissions
```

Wichtige Regeln:

- `guest` wird nicht gespeichert.
- Jeder registrierte Benutzer hat einen Player-Tier.
- `common` ist der Standard nach Registrierung.
- VIP gilt nur für `account_type = player`.
- Staff-Rollen bekommen Adminrechte nicht durch den Player-Tier.
- Adminrechte werden über Permissions gesteuert.
- Staff darf nur mit `play.game` spielen.
- `game_admin` und `super_admin` erhalten `play.game` standardmäßig.
- Player-Tiers können später aktivitätsbasiert vergeben und wieder entzogen werden.
