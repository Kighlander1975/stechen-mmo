
# Reward System

## Dokumentstatus

**Reset-Status:** analysiert und dokumentiert
**Implementierungsstatus:** implementiert
**Maßgebliche Grundlage:** tatsächlicher Laravel-Codebestand (Reset)

---

# 1. Zweck

Das Reward-System bildet die fachliche Domäne für alle automatischen und manuellen
Spielgeld-Belohnungen.

Es entscheidet insbesondere:

- ob ein Reward zulässig ist,
- welcher Reward vergeben wird,
- welcher Reward-Plan gilt,
- welcher Betrag anzuwenden ist,
- welcher Streak-Tag erreicht wurde,
- wann ein Reward-Tag beginnt oder endet,
- ob ein Claim bereits erfolgt ist.

Die eigentliche Guthabenänderung erfolgt anschließend über Wallet und Ledger.

---

# 2. Fachliche Abgrenzung

Dieses Dokument beschreibt ausschließlich die Reward-Domäne.

Nicht Bestandteil dieses Dokuments sind:

- Wallet-Implementierung
- Ledger-Buchungslogik
- Buy-in-Logik
- Settlement
- Lobby

Diese Themen wurden bzw. werden in eigenen Reset-Bausteinen dokumentiert.

---

# 3. Beteiligte Komponenten

## Services

- RewardService
- RegistrationBonusBackfillService

## Models

- RewardPlan
- RewardPlanEntry
- RewardClaim
- UserRewardState

## Controller

- RewardController
- RegistrationBonusBackfillController

## Weitere Komponenten

- RewardPlanSeeder
- Artisan Backfill Command
- Admin Dashboard (Reward Card)
- Registrierungsprozess

---

# 4. Architektur

Die Reward-Domäne ist vollständig vom Wallet-System getrennt.

```text
RewardPlan
      │
RewardPlanEntry
      │
RewardService
      │
 ┌────┴──────────┐
 ▼               ▼
RewardClaim  UserRewardState
      │
      ▼
WalletService
      │
      ▼
Ledger
```

Damit entscheidet das Reward-System ausschließlich über die fachliche Berechtigung.
Wallet und Ledger führen anschließend die finanzielle Buchung aus.

---

# 5. RewardPlan

Reward-Pläne sind datengetrieben.

Ein Plan definiert u. a.:

- Aktivierungszeitraum
- Zeitzone
- Reward-Day-Cutoff
- Reset-Verhalten
- Priorität

Die eigentlichen Staffelungen liegen in RewardPlanEntry.

---

# 6. RewardPlanEntry

RewardPlanEntry beschreibt die einzelnen Reward-Stufen.

Der Standardplan wird über den RewardPlanSeeder bereitgestellt und umfasst die
aktuell dokumentierte Staffel einschließlich des Meilensteins an Tag 31.

---

# 7. RewardService

Der RewardService bildet die zentrale Fachlogik.

Er koordiniert:

- Registrierungsbonus
- Daily Claims
- aktiven RewardPlan
- Streak-Ermittlung
- Idempotenz
- Übergabe an Wallet/Ledger

Controller enthalten keine Reward-Geschäftslogik.

---

# 8. Registrierungsbonus

Der Registrierungsbonus wird nach erfolgreicher Registrierung über den RewardService vergeben.

Eigenschaften:

- einmal pro Account
- idempotent
- Wallet-/Ledger-Integration
- nachvollziehbarer RewardClaim

---

# 9. Daily Claims

Daily Claims basieren auf:

- aktivem RewardPlan
- RewardPlanEntry
- UserRewardState
- RewardClaim

Der Reward-Tag verwendet den konfigurierbaren Tageswechsel
(aktuell Europe/Berlin, 04:00 Uhr).

---

# 10. RewardClaim

RewardClaim dokumentiert jeden einzelnen Claim.

Er enthält u. a.:

- Reward-Typ
- Betrag
- Status
- Streak
- Referenzen
- Idempotenz
- Wallet-/Ledger-Bezug

---

# 11. UserRewardState

Der fortlaufende Reward-Zustand eines Spielers ist bewusst von einzelnen Claims getrennt.

Dadurch bleiben Historie und aktueller Zustand getrennt modelliert.

---

# 12. Registrierungsbonus-Backfill

Der Backfill besitzt eine gemeinsame Servicearchitektur.

Vorhanden sind:

- RegistrationBonusBackfillService
- Artisan Command
- Admin Controller
- Admin-Oberfläche
- Einzel- und Sammel-Backfill

Die eigentliche Buchungslogik existiert ausschließlich im Service.

---

# 13. Schnittstelle zu Wallet und Ledger

Rewards verändern Wallets niemals direkt.

Alle finanziellen Änderungen erfolgen über den WalletService und werden
über Ledger-Einträge nachvollziehbar dokumentiert.

---

# 14. Tests

Der aktuelle Build enthält Tests u. a. für:

- Foundation
- RewardPlan
- RewardService
- Claim-Routen
- Registrierungsbonus
- Backfill

Die Tests bestätigen die Architektur und bilden einen wichtigen Teil der
Dokumentationsgrundlage.

---

# 15. Implementierter Ist-Stand

Bestätigt sind insbesondere:

- datengetriebene Reward-Pläne
- RewardPlanEntry
- Registrierungsbonus
- Daily Claims
- Reward-Day mit Cutoff
- Streak-System
- UserRewardState
- RewardClaim
- Backfill
- Admin-Oberfläche
- Artisan Command
- Wallet-/Ledger-Integration
- Idempotenz

---

# 16. Offene Themen

- zukünftige Reward-Aktionen und Multiplikatoren weiter dokumentieren
- technische Referenz (Migrationen, Constraints, Tests)
- spätere Business-/Echtgeld-Auswirkungen getrennt bewerten

---

# 17. Definition of Done

Dieser Reset-Baustein dokumentiert den aktuell implementierten Reward-Bereich
auf Basis des tatsächlichen Codes und trennt ihn klar von Wallet/Ledger sowie
anderen Fachdomänen.
