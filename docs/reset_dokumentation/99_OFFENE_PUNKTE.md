
# Offene Punkte

## Dokumentstatus

**Reset-Status:** Abschlussdokument des Projekt-Resets

---

# 1. Zweck

Dieses Dokument bildet den Abschluss der Reset-Dokumentation.

Es sammelt ausschließlich Themen, die nach Abschluss des Resets
bewusst offen bleiben und als Grundlage für die weitere Entwicklung
dienen.

---

# 2. Domänenstatus

| Domäne | Status |
|---|---|
| Registrierung | Dokumentiert |
| Wallet & Ledger | Dokumentiert |
| Reward-System | Dokumentiert |
| Lobby | Dokumentiert |
| Spielräume | Dokumentiert |
| Spielzyklus | Dokumentiert |
| Settlement & Prize Distribution | Dokumentiert |
| Admin & Testmodus | Dokumentiert |
| GameEngine-Vorbereitung | Dokumentiert |
| KI & Disconnect | Dokumentiert |

---

# 3. Hohe Priorität

## Spielräume: Führungsregel beim Spielstart

Ergänzt am 2026-11-16 nach Abgleich mit dem Code.

**Muss vor Beginn der GameEngine geschlossen sein** (Entscheidung des
Projektinhabers vom 2026-11-16: erst muss das Drumherum einwandfrei laufen).

Fachlich beschlossen, aber nicht implementiert:

- Der zuerst startende Raum übernimmt für den Spieler die Führung.
- Andere wartende Teilnahmen des Spielers werden beim Start automatisch
  verlassen und ihre Buy-in-Reservierungen freigegeben.
- Ein Spieler darf nie in mehr als einem startenden oder laufenden Spiel
  sein. Konflikte werden per `starting_at` entschieden, Tie-Breaker ist
  die kleinere Raum-ID.

Ist-Stand: `GameRoomStartCoordinatorService::finalizeStartIfDue()` prüft
andere Teilnahmen des Spielers nicht. `GameRoomJoinService` blockiert
einen Join nur, wenn der Spieler anderswo bereits `playing` ist. Füllen
sich zwei Räume, in denen derselbe Spieler wartet, können nach aktuellem
Code beide starten.

Zusätzlich: Die Teilnahmelimits in `config/game_rooms.php` werden nicht
gelesen. `GameRoomJoinService` nutzt eine eigene Konstante.

---

## Weitere Lücken im „Drumherum“ (Code-Analyse 2026-11-19)

Ebenfalls vor Beginn der GameEngine zu schließen. Ist-Stand laut Code:

- **Raumversorgung läuft nicht automatisch.** `game-rooms:supply` ist weder
  im Scheduler (`routes/console.php`) noch in der Lobby angebunden und muss
  manuell ausgeführt werden.
- **Der Netto-Preispool wird auf keinem Konto gebucht (kritisch, Geld).**
  `GameRoomStartCoordinatorService` bucht beim Start den vollen Buy-in jedes
  Spielers ab (`commit`), schreibt aber nur den Rake einem Gegenkonto
  (Rake-Wallet) gut. Die übrigen Beträge existieren danach in keinem Wallet.
  Benötigt wird ein Preispool- beziehungsweise Treuhand-Wallet bis zum
  Settlement.
- **Abbruch und Aufräumen haben keinen Aufrufer im Betrieb.**
  `GameRoomCancellationService` und `GameRoomCleanupService` sind
  implementiert und getestet, werden aber nirgends ausgelöst. Nicht volle
  Räume und ihre Reservierungen bleiben unbegrenzt bestehen. Der
  Verlassensgrund `logout` ist definiert, aber ungenutzt.
- **Ein Start kann dauerhaft hängen.** Schlägt das Abbuchen eines Spielers
  fehl, wird die Transaktion zurückgerollt; der Raum bleibt auf `starting`
  und der Scheduler versucht es endlos erneut. `cancelRoom()` erlaubt den
  Status `starting` nicht.
- **Englische Fehlermeldungen.** Exceptions der Join-/Leave-Services und
  Validierungsmeldungen erscheinen englisch in der Oberfläche (keine
  deutschen Sprachdateien, `APP_LOCALE=en`).

---

## Lobby

Lobby-Review und Umbau nach Zielbild v3 (Desktop und mobil) wurden am
2026-11-19 umgesetzt, siehe `docs/lobby-room-browser-vue-state.md`.

Noch offen:

- Hinweis „wird freigegeben, sobald X startet“ an eigenen Tischen (setzt die
  Führungsregel voraus)
- Namen der Mitspieler am Mini-Pokertisch (Backend liefert nur die Anzahl)
- deutsche Fehlermeldungen beim Beitreten und Verlassen
- Gäste-Ansicht der Lobby (fachlich bestätigt 2026-11-19): Gäste sehen Tische
  und ihren Zustand, aber keinen Chat und können nicht beitreten. Im Code
  verlangt `/lobby` derzeit Login und bestätigte E-Mail-Adresse.

---

## Settlement

Die grundlegende Architektur steht.

Noch umzusetzen sind u. a.:

- PrizePoolDistributionService
- GameRoomSettlementService
- automatische Settlement-Ausführung
- Rankings
- Tier-System
- Statistikaufbereitung

---

## GameEngine

Die Schnittstellen sind definiert.

Die eigentliche Engine bleibt als eigenständige Domäne vollständig
umzusetzen.

---

# 4. Mittlere Priorität

- Node.js-Spielserver und Echtzeit (fachlich beschlossen, Variante B)
- Hosting auf Hetzner, Staging/Produktion, Deployment (fachlich beschlossen;
  offene Einzelentscheidungen in `docs/HOSTING_UND_BETRIEB.md`, Abschnitt 4)
- Presence
- Chat
- Matchmaking
- Favoriten
- Auto-Join
- weitere Adminfunktionen
- Diagnosewerkzeuge

---

# 5. Langfristige Themen

- KI-Vertretung bei Disconnects
- KI-gestützte Auswertungen
- Saison- und Ranglistensystem
- Achievements
- Replay-Metadaten
- Analytics
- Anti-Cheat- und Abuse-Erkennung

---

# 6. Architekturprinzipien

Für die weitere Entwicklung gelten weiterhin:

- Code ist die maßgebliche Quelle.
- Dokumentation gehört zur Definition of Done.
- Domänen bleiben klar getrennt.
- Fachlogik gehört in Services.
- Wallet- und Ledger-Buchungen bleiben transaktional und idempotent.
- Die GameEngine bleibt unabhängig von Wallet, Rewards und Settlement.

---

# 7. Abschluss des Resets

Mit Abschluss dieses Dokuments ist der Projekt-Reset beendet.

Die weitere Entwicklung beginnt auf Basis der dokumentierten
Ist-Architektur und der fachlich beschlossenen Zielarchitektur.

Änderungen erfolgen künftig wieder featureorientiert und werden
fortlaufend dokumentiert.
