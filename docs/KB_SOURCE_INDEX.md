# KB Source Index

Stand: 2026-06-24

Dieses Dokument beschreibt, welche Projektdateien in die KI-Projekt-KB aufgenommen wurden, welche bewusst ausgeschlossen wurden und welche ausgeschlossenen Dateien bei Bedarf gezielt als Quelle nachgereicht werden können.

Ziel dieser Datei ist es, die Haupt-KB schlank, aktuell und sicher zu halten, ohne nützliche Informationen aus ausgeschlossenen Dokumenten zu verlieren.

---

## 1. Zweck

Die KI-Projekt-KB soll dauerhaft nützliches Projektwissen enthalten:

- Projektziel
- aktueller Tech-Stack
- Roadmap
- MVP-Konzept
- Spielregeln
- aktuelle Phasenplanung
- Rollen-/Berechtigungsgrundlagen
- HomeServer-/Fallback-Grundkonzept

Nicht in die Haupt-KB gehören:

- lokale/private Notizen
- veraltete Frühplanungen
- konkrete Infrastruktur-Inventare
- zeitpunktbezogene Wartungsstände
- sensible Server-, Benutzer-, Token-, Key- oder Netzwerkdetails
- Dependency-Dokumentation aus `node_modules/` oder `vendor/`

Wenn Informationen aus ausgeschlossenen Dateien benötigt werden, soll der KI-Assistent gezielt danach fragen, statt anzunehmen, dass diese Inhalte Teil der Haupt-KB sind.

---

## 2. Empfohlene Haupt-KB-Dateien

Die Haupt-KB soll schlank bleiben. Zielgröße ist möglichst maximal 15 Dateien, bevorzugt etwa 12 aktive Referenzdateien.

Aktuell empfohlene Haupt-KB-Auswahl:

```text
docs\KB_SOURCE_INDEX.md
docs\PROJECT_OVERVIEW.md
docs\PHASES_INDEX.md
docs\ROADMAP.md
docs\GAME_RULES.md
docs\PHASE_3_WALLET_BUYIN_AND_LOBBY.md
docs\phase-3-game-room-creation.md
docs\reward-room-economy-planning.md
docs\lobby-room-browser-vue-state.md
docs\FRONTEND_VUE_ISLANDS.md
docs\BLADE_LAYOUT_COMPONENTS.md
docs\HOMESERVER_FALLBACK.md
```

### Einordnung

Diese Dateien gelten als primäre Quelle für:

- Projektüberblick und aktuelles Zielbild
- aktuelle Phasenübersicht
- Roadmap
- Spielregeln
- Phase 3 Wallet, Buy-in, Lobby und Raumversorgung
- Raum-Lifecycle und systemseitige Raumerzeugung
- Reward-, Rake- und Raumökonomie
- aktuellen Lobby-Raumbrowser mit Vue-Island
- Frontend-/Vue-Island-Architektur
- Blade-/Layout-Komponenten
- HomeServer-/Realtime-Fallback-Grundidee

### Bei Bedarf gezielt anfordern

Diese Dateien sind weiterhin nützlich, sollen aber nicht dauerhaft die aktive Haupt-KB vergrößern, solange ihr Inhalt nicht konkret benötigt wird oder teilweise durch aktuellere Dokumente überholt ist:

```text
docs\PHASE_1_FOUNDATION.md
docs\PHASE_2_AUTH_AND_USERS.md
docs\MVP_CONCEPT.md
docs\rollen-und-berechtigungen.md
docs\Stechen-Serverkonzept.md
docs\tech-stack.md
docs\lobby-field-prototype.md
docs\admin-dashboard-refactor-notes.md
```

Bei Fragen zu älteren Phasen, MVP-Grundlagen, Rollen-/Berechtigungsdetails, Serverkonzepten oder dem Spielfeld-Prototyp soll der KI-Assistent diese Dateien gezielt anfordern, statt ihre Inhalte als aktuelle Haupt-KB vorauszusetzen.

---

## 3. Aktuelle Projektannahmen und Entwicklungsstand für die KB

Diese Annahmen gelten für die Einordnung der KB:

```text
Phase 1 ist abgeschlossen.
Phase 2 ist weitgehend umgesetzt.
Phase 3 Wallet, Buy-in, Lobby und Raumversorgung ist aktueller Fokus.
Laravel bleibt autoritative Backend- und Spielstandsquelle.
MVP arbeitet Polling-first.
HomeServer/WebSocket ist optional und späterer Realtime-/Fallback-Baustein.
Blade ist die Basis.
Vue 3 wird als Inselarchitektur verwendet.
Inertia.js ist aktuell nicht Projektbasis.
Rollen-/Berechtigungen sind vorbereitet, aber keine komplexe Rechteverwaltung im aktuellen Fokus.
```

### Technisch belegter Entwicklungsstand am 2026-06-24

Der aktuelle Stand wurde gegen Projektdateien und vorhandene Tests plausibilisiert:

```text
- Auth-geschützte Lobby existiert.
- Verifizierte Benutzer dürfen die Lobby sehen.
- Gäste werden zum Login geleitet.
- Unverifizierte Benutzer werden zur Verifikation geleitet.
- /lobby rendert die Lobby.
- /lobby/rooms liefert eine validierte JSON-Payload für den Raumbrowser.
- LobbyRoomBrowser.vue liegt unter laravel-app/resources/js/components/lobby/.
- vue-islands.js registriert lobby-room-browser.
- Die Lobby nutzt x-app-layout.
- Tailwind scannt resources/js/**/*.vue und resources/js/**/*.js.
- GameRoom und GameRoomPlayer existieren.
- GameRoomPlayer kennt reserved, joined, ready, playing, left und cancelled.
- Wallets haben balance_units und reserved_units.
- LedgerEntry und WalletService unterstützen Reservieren und Freigeben reservierter Einheiten.
- WalletService nutzt Transaktionen, Idempotency und lockForUpdate.
- GameRoomSupplyService erzeugt systemseitig Sit'n'Go-Räume.
- Raumversorgung berücksichtigt verfügbare Wallets.
- Ein lokaler/testing Override für Raumversorgung ohne Wallet-Eignung existiert.
- Tests existieren für Lobby, Wallet, Ledger und Raumversorgung.
```

### Fachlich entschieden, aber noch nicht vollständig technisch umgesetzt

Diese Punkte sind für die nächsten Implementierungen vorgemerkt und dürfen nicht als bereits vollständig umgesetzt interpretiert werden:

```text
- mehrere Vorstart-Anmeldungen in verschiedenen Räumen;
- nur ein laufendes Spiel pro Spieler;
- zuerst startender Raum übernimmt Führung;
- automatische Entfernung aus anderen wartenden Räumen;
- 1:1-Freigabe anderer Buy-in-Reservierungen;
- Startphase 5 bis 10 Sekunden;
- Konfliktlösung per Startphasen-Zeitstempel;
- Abbruch oder Rücksetzung konkurrierender Räume;
- UI-Hinweis bei nicht startendem Raum;
- Chat nachgelagert;
- Homeserver/WebSocket erst ab Alpha.
```

### Nächster Implementierungsblock

Der nächste technische Block sollte voraussichtlich lauten:

```text
Room Join, Buy-in Reservation und Startphase
```

Wahrscheinliche Bausteine:

```text
- PlayerEligibilityService oder vergleichbare Policy-/Gate-Struktur;
- GameRoomJoinService für atomaren Raumbeitritt;
- Nutzung von WalletService::reserveUnits für Buy-in-Reservierungen;
- GameRoomLeaveService oder ReservationReleaseService für Freigaben;
- Datenbank-Constraints gegen doppelte aktive Teilnahme im selben Raum;
- Startphasen-Modellierung, ggf. GameRoom::STATUS_STARTING oder separate Startphasen-Felder;
- Start-Koordinator für Konflikte zwischen mehreren startbereiten Räumen;
- Tests für Mehrfach-Vorstart, zu wenig Guthaben, doppelte Teilnahme, Reservierungsfreigabe und Startkonflikte.
```

---

## 4. Bewusst ausgeschlossene Dateien

### 4.1 Lokale/private Notizen

```text
_docs\ideas.md
_docs\scratchpad.md
_docs\today.md
_docs\tomorrow.md
```

Status:

```text
Nicht in die KB aufnehmen.
```

Grund:

```text
_docs/ ist für lokale, private, temporäre oder informelle Notizen vorgesehen.
Diese Dateien sind nicht autoritativ.
```

Bei Bedarf:

```text
Nur gezielt vom Nutzer anfordern, wenn ausdrücklich lokale Ideen, Tagesplanung oder Scratchpad-Inhalte relevant sind.
```

---

### 4.2 Veraltete oder ersetzte Projektdateien

```text
docs\development-environment.md
docs\next-steps.md
docs\project.md
```

Status:

```text
Nicht in die KB aufnehmen.
```

Grund:

```text
Diese Dateien stammen aus einem frühen Projektstand oder sind durch aktuellere Dokumente ersetzt.
```

Bekannte Probleme:

```text
- teilweise veralteter Stand: Laravel noch nicht installiert
- teilweise veraltete Next-Steps
- teilweise Inertia.js als geplanter Stack-Bestandteil
- project.md ist durch PROJECT_OVERVIEW.md ersetzt
```

Bei Bedarf:

```text
Nur gezielt anfordern, wenn historische Projektentwicklung oder alte Planungsstände rekonstruiert werden sollen.
```

---

### 4.3 Infrastruktur-Inventar

```text
docs\homeserver-inventory.md
```

Status:

```text
Nicht in die Haupt-KB aufnehmen.
```

Grund:

```text
Sehr umfangreiche lokale Admin-/Infrastruktur-Inventur.
Enthält bzw. beschreibt lokale Server-, Benutzer-, Dienst-, Netzwerk-, Firewall-, Speicher-, Datenbank-, Zertifikats-, Schlüssel- und Wartungsdetails.
```

Nützlicher abstrakter Kern:

```text
Es existiert ein optionaler HomeServer.
Der HomeServer ist nicht autoritativ.
Laravel bleibt Hauptsystem und Quelle der Wahrheit.
Der HomeServer kann später als Entwicklungs-/Testsystem, interner Diensteknoten oder Realtime-/PowerUp-Node dienen.
```

Bei Bedarf:

```text
Nur gezielt anfordern, wenn konkrete lokale Infrastrukturfragen beantwortet werden müssen.
Keine sensiblen Details ungeprüft in KB oder öffentliche Dokumentation übernehmen.
```

---

### 4.4 Infrastrukturstatus

```text
docs\INFRASTRUCTURE_STATUS.md
```

Status:

```text
Nicht in die Haupt-KB aufnehmen.
Optional später stark abstrahiert in eine Infrastruktur-Policy überführen.
```

Grund:

```text
Enthält lokale, zeitpunktbezogene und teilweise sensible Infrastrukturdetails.
Außerdem kann der Phasenstand veraltet sein.
```

Nützliche extrahierbare Informationen:

```text
- Es gibt einen optionalen HomeServer.
- SSH-Zugriff ist grundsätzlich gehärtet/keybasiert vorgesehen.
- Datenbanken sollen lokal gebunden sein.
- Webmin ist kein Zielbestandteil des Projektbetriebs.
- Laravel bleibt auch bei HomeServer-Nutzung autoritativ.
```

Bekannte Probleme:

```text
- zeitpunktbezogene Paket-/Systemstände
- lokale Infrastrukturdetails
- escaped Markdown-Formatierung
- möglicher veralteter Phasenstand
```

Bei Bedarf:

```text
Gezielt anfordern, wenn eine abstrakte Infrastruktur-Policy erstellt werden soll.
Keine konkreten Hostnamen, IPs, User, Keys, Ports oder Paketstände in die Haupt-KB übernehmen.
```

---

### 4.5 Entscheidungen

```text
docs\decisions.md
```

Status:

```text
Nicht in aktueller Form in die KB aufnehmen.
Nach Bereinigung sehr sinnvoll als zentrale Entscheidungsdatei.
```

Nützliche Inhalte:

```text
- Laravel-App und HomeServer bleiben getrennte Komponenten.
- docs/ ist offizielle Dokumentation.
- _docs/ ist lokale/private Notizsammlung.
- Polling-first Realtime.
- Optional später Node.js WebSocket service.
```

Bekannte Probleme:

```text
- Inertia.js wird genannt, ist aber aktuell nicht Projektbasis.
- Codeblock-Formatierung ist teilweise defekt.
- Datei stammt aus frühem Projektstand.
```

Bei Bedarf:

```text
Gezielt anfordern, wenn eine aktualisierte Architekturentscheidungsdatei erstellt werden soll.
```

---

### 4.6 Server-/Realtime-Konzept

```text
docs\Stechen-Serverkonzept.md
```

Status:

```text
Nicht vollständig in die Haupt-KB aufnehmen.
Später bereinigt/extrahiert sehr wertvoll.
```

Nützliche extrahierbare Inhalte:

```text
- Laravel bleibt autoritative Quelle.
- HomeServer/WebSocket ist nur optionaler Realtime-Beschleuniger.
- Polling bleibt Pflicht-Fallback.
- Clients laden echten Spielstand von Laravel.
- WebSocket-Events sind nur Hinweise.
- State-Versionierung statt Full-State-Polling.
- Server kann next_poll_in_ms vorgeben.
- Adaptive Polling-Intervalle.
- Spielstart-Warteschlange bei Last.
- Laufende Spiele werden nicht wegen Last beendet.
- Idempotency-Key für Spielaktionen.
- Pro Spieler nur eine ausstehende Aktion.
- Broadcast zum HomeServer ist best-effort und darf Laravel nicht blockieren.
- HomeServer soll keine direkte Datenbankverbindung benötigen.
- Fallback-Spiele bleiben bis Spielende im Fallback.
- Rückkehr in Hybridbetrieb sollte anfangs manuell freigegeben werden.
```

Bekannte Probleme:

```text
- enthält konkrete Hosting-Messwerte aus einem alten Stand
- nennt PHP 8.4.x als gemessene Hosting-Umgebung, während die aktuelle Stacklinie PHP 8.5.x ist
- verwendet ein älteres Phasenmodell
- Beispiel-Domains/WSS-Adressen sind nur Beispiele und keine Projektvorgaben
```

Bei Bedarf:

```text
Gezielt anfordern, wenn ein bereinigtes Realtime-/Fallback-/Capacity-Konzept erstellt werden soll.
Beispiel-Domains nur als Beispiele behandeln.
Keine konkreten Hosting-Messwerte als aktuelle Projektwahrheit übernehmen.
```

---

### 4.7 Root README

```text
README.md
```

Status:

```text
Nicht automatisch in die KB aufnehmen.
Später aktualisieren und dann optional aufnehmen.
```

Grund:

```text
Die spezialisierten docs-Dateien sind aktueller und detaillierter.
README.md sollte später als öffentliche Projektübersicht aktualisiert werden.
```

Bei Bedarf:

```text
Gezielt anfordern, wenn ein neues öffentliches README erstellt oder aktualisiert werden soll.
```

---

### 4.8 Laravel-App README

```text
laravel-app\README.md
```

Status:

```text
Nicht in die KB aufnehmen.
```

Grund:

```text
Vermutlich Laravel-Standard-README oder nicht ausreichend projektspezifisch.
```

Bei Bedarf:

```text
Nur anfordern, wenn geprüft werden soll, ob daraus ein projektspezifisches App-README entstehen soll.
```

---

### 4.9 Lobby-Spielfeld-Prototyp

```text
docs\lobby-field-prototype.md
```

Status:

```text
Nicht in die Haupt-KB aufnehmen.
Bei Bedarf gezielt anfordern.
```

Grund:

```text
Detaildokument zu einem UI-/Spielfeld-Prototyp.
Die Datei ist nützlich, aber zu spezifisch für die begrenzte Haupt-KB.
Die Haupt-KB soll über diesen Index nur wissen, dass die Datei existiert und wofür sie gedacht ist.
```

Nützliche Inhalte:

```text
- deaktivierter UI-Einstieg zum Spielfeld in der Lobby
- direkter URL-Zugriff für Entwicklung und Tests
- Test-URLs für seats=2 bis seats=11
- sektorbasierte Sitzplatzverteilung
- aktive Sektor-Markierung als Prototyp
- aktueller Testwert für aktiven Spieler
- spätere Anbindung an echten Spielzustand
```

Bei Bedarf:

```text
Gezielt anfordern, wenn am Lobby-Spielfeld, an der Sitzplatzgeometrie oder an der aktiven Spieler-Markierung gearbeitet werden soll.
Beispiel: Bitte gib mir docs\lobby-field-prototype.md oder den relevanten Abschnitt daraus.
```

---

### 4.10 Lobby-Raumbrowser mit Vue-Island

```text
docs\lobby-room-browser-vue-state.md
```

Status:

```text
Für die Haupt-KB geeignet oder gezielt als aktuelle Lobby-Quelle aufnehmen.
```

Grund:

```text
Aktuelles Detaildokument zum Lobby-Raumbrowser nach dem Umbau auf Vue-Island.
Dokumentiert Raumliste, Filter, Rauminformationen, clientseitige Raumauswahl ohne Reload und den wichtigen Tailwind-/Vite-Fallstrick bei Vue-Dateien.
```

Nützliche Inhalte:

```text
- aktueller Stand des Lobby-Raumbrowsers
- Abgrenzung zum älteren Lobby-Spielfeld-Prototyp
- Entwicklung von Blade-/Server-rendered Ansatz zu Vue-Island
- aktuelle Layoutstruktur: Filter, Rauminformationen, Raumliste und Chat
- lokale Raumauswahl in Vue ohne Seitenreload
- konsistenter leerer Detailzustand
- deaktivierter Beitreten-Button als Vorbereitung
- Raumversorgung/Raumerstellung als Kontext
- kritischer Hinweis: Tailwind muss resources/js/**/*.vue und resources/js/**/*.js scannen
- Diagnose-Regel für den Fall, dass Vue-Klassen im DOM stehen, aber CSS nicht greift
```

Bei Bedarf:

```text
Als aktuelle Quelle anfordern, wenn am Lobby-Raumbrowser, an Vue-Islands, an Tailwind/Vite-Problemen oder an der Raumlisten-Interaktion gearbeitet wird.
Beispiel: Bitte gib mir docs\lobby-room-browser-vue-state.md oder den relevanten Abschnitt daraus.
```

---

### 4.11 Admin-Dashboard-Refactor Kurznotiz

```text
docs\admin-dashboard-refactor-notes.md
```

Status:

```text
Nicht in die Haupt-KB aufnehmen.
Bei Bedarf gezielt anfordern.
```

Grund:

```text
Kurze Umsetzungsnotiz zum Admin-Dashboard-Refactor und zur projektweiten Header-Breitenkorrektur.
Nützlich für spätere Admin-Dashboard-Erweiterungen, aber zu spezifisch für die aktive Haupt-KB.
```

Nützliche Inhalte:

```text
- Admin-Dashboard wurde von routes/web.php-Closure auf AdminDashboardController umgestellt
- Dashboard-View wurde in Section-Views aufgeteilt
- Auswirkungen auf zukünftige Admin-Erweiterungen
- Vue-SiteHeader wurde an die Content-Breite max-w-[1600px] angepasst
```

Bei Bedarf:

```text
Gezielt anfordern, wenn am Admin-Dashboard, an Dashboard-Sections oder an der projektweiten SiteHeader-Breite gearbeitet wird.
Beispiel: Bitte gib mir docs\admin-dashboard-refactor-notes.md oder den relevanten Abschnitt daraus.
```

---

### 4.12 Dependencies

```text
laravel-app\node_modules\
laravel-app\vendor\
```

Status:

```text
Nicht in die KB aufnehmen.
```

Grund:

```text
Drittanbieter-Dokumentation, Dependency-Dateien und generische Paket-READMEs.
Nicht projektspezifisch.
```

Bei Bedarf:

```text
Nur gezielt technische Dokumentation externer Pakete prüfen, wenn eine konkrete Implementierungsfrage dies erfordert.
```

---

## 5. Bekannte Konflikte und Bereinigungsbedarfe

### 5.1 Phase-2-Stand

Problem:

```text
Einige ältere Dokumente beschreiben Phase 2 noch als geplant.
Aktueller Stand: Phase 2 läuft bereits.
```

Bereinigung:

```text
Phase-2-Dokumentation bei Gelegenheit aktualisieren.
```

---

### 5.2 Rollen und Berechtigungen

Problem:

```text
Phase 2 schließt komplexe Rollen- und Rechteverwaltung aus.
rollen-und-berechtigungen.md plant aber einfache Rollen-/Statusfelder.
```

Einordnung:

```text
Kein echter Widerspruch, wenn klar unterschieden wird:
- einfache vorbereitende Rollen-/Statusfelder sind erlaubt
- komplexe Admin-/Rechteverwaltung bleibt Nicht-Ziel für Phase 2
```

---

### 5.3 Inertia.js

Problem:

```text
Ältere Dateien nennen Inertia.js.
Aktuelle Projektentscheidung: Blade-Basis mit Vue-3-Inselarchitektur.
```

Bereinigung:

```text
Inertia.js aus aktuellen Entscheidungs- und Next-Step-Dokumenten entfernen oder als verworfene/nicht gewählte Option markieren.
```

---

### 5.4 Infrastrukturdetails

Problem:

```text
Einige Dateien enthalten lokale Server-, Netzwerk-, Dienst- oder Wartungsdetails.
```

Bereinigung:

```text
Nicht in Haupt-KB übernehmen.
Nur abstrakte Infrastrukturregeln übernehmen.
```

---

### 5.5 Unterschiedliche Phasenmodelle

Problem:

```text
Einige ältere Konzepte nutzen ein anderes Phasenmodell, z. B. MVP/ALL-INKL/HomeServer-Phasen.
Aktuelle Roadmap nutzt Phase 1 Foundation und Phase 2 Auth/User.
```

Bereinigung:

```text
Ältere Phasenmodelle nicht als aktuelle Roadmap interpretieren.
Technische Inhalte können extrahiert werden, Phasenbezeichnungen aber aktualisieren.
```

---

## 6. Arbeitsregel für den KI-Assistenten

Wenn eine Antwort Informationen benötigen könnte, die nur in ausgeschlossenen Dateien stehen, soll der KI-Assistent nicht raten.

Stattdessen soll er gezielt fragen, z. B.:

```text
Dazu könnten Informationen aus docs\Stechen-Serverkonzept.md hilfreich sein.
Bitte gib mir diese Datei oder den relevanten Abschnitt.
```

oder:

```text
Das betrifft lokale Infrastruktur. Falls du möchtest, prüfe bitte den passenden Abschnitt aus docs\homeserver-inventory.md oder docs\INFRASTRUCTURE_STATUS.md und sende ihn hier.
```

---

## 7. Sicherheitsregel

Ausgeschlossene Dateien dürfen nicht ungeprüft in die Haupt-KB übernommen werden.

Insbesondere nicht übernehmen:

```text
- Hostnamen
- lokale/private IP-Adressen
- Benutzernamen
- SSH-Key-Namen
- Tokens
- Secrets
- Zertifikate
- konkrete Firewallregeln
- konkrete offene Ports
- konkrete Wartungsstände
- konkrete Paketstände
- lokale Hardwaredetails
```

Stattdessen nur abstrahieren:

```text
- HomeServer existiert optional.
- Laravel bleibt autoritativ.
- HomeServer ist nicht Quelle der Wahrheit.
- Polling bleibt Fallback.
- Infrastruktur soll gehärtet und minimal exponiert sein.
```

---

## 8. Empfohlene spätere Bereinigungsdateien

Optional später erstellen:

```text
docs\DECISIONS.md
docs\REALTIME_AND_CAPACITY_CONCEPT.md
docs\INFRASTRUCTURE_POLICY.md
```

Zweck:

```text
DECISIONS.md:
Aktuelle, bereinigte Architekturentscheidungen.

REALTIME_AND_CAPACITY_CONCEPT.md:
Bereinigtes Konzept aus Stechen-Serverkonzept.md ohne alte Hosting-/Phasen-Details.

INFRASTRUCTURE_POLICY.md:
Abstrakte Infrastrukturregeln ohne lokale/sensible Details.
```

---

## 9. Kurzfassung

```text
Die Haupt-KB enthält nur stabile, aktuelle Projektkonzepte.
Ausgeschlossene Dateien bleiben als lokale Quellen erhalten.
Nützliche Informationen aus ausgeschlossenen Dateien werden bei Bedarf gezielt angefragt.
Sensible oder veraltete Details werden nicht ungeprüft übernommen.
```
