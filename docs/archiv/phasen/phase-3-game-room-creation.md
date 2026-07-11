# Phase 3: Game Room Creation und Room Supply

Stand: Juni 2026  
Version: v0.1  
Status: fachliche Planungsnotiz für Phase 3

---

## 1. Zweck

Dieses Dokument sammelt die fachlichen Entscheidungen und offenen Modellierungsfragen zur Phase 3: Raumerstellung, Raumversorgung und Raumbeitritt.

Die Datei ist bewusst als eigenständige Dokumentation angelegt, weil die Raumerstellung ein eigener größerer Entwicklungsabschnitt wird.

---

## 2. Grundsatz: systemseitige Räume

Spieler erstellen keine eigenen Räume.

Räume werden systemseitig erzeugt, verwaltet und bereitgestellt.

Ziele:

- kontrolliertes Raumangebot
- keine beliebigen User-Raumnamen
- keine Spieler-erzeugten Spam-Räume
- weniger Moderationslast
- bessere Kontrolle über Buy-ins, Spieleranzahl und Startlogik
- bessere Auswertbarkeit der Raumökonomie

Spieler können später:

- Lobby ansehen
- Räume filtern
- einem Raum beitreten
- einen Raum verlassen, solange erlaubt
- mehrere wartende Sitze reservieren
- optional später bevorzugte Raumtypen favorisieren

---

## 3. Autorität

Laravel bleibt die autoritative Anwendung für Raum- und Spielentscheidungen.

Laravel entscheidet verbindlich über:

- welche Räume existieren
- Raumstatus
- Join-/Leave-Entscheidungen
- Spielberechtigung
- Wallet-Prüfung
- Buy-in-Reservierung
- Buy-in-Einzug
- Reservierungsfreigabe
- Spielstart
- Preispool
- Rake
- Ledger-Buchungen
- persistente Daten

Der HomeServer kann später als Realtime-Beschleuniger eingesetzt werden, ist aber nicht Quelle der Wahrheit.

---

## 4. Festgelegter Service-Name

Der spätere Service für die automatische Raumversorgung heißt:

- GameRoomSupplyService

Der Name ist fachlich festgelegt.

Aufgaben des GameRoomSupplyService:

- Raumangebot systemseitig sicherstellen
- offene Sit'n'Go-Räume bereitstellen
- Scheduled Rooms rechtzeitig erzeugen
- dynamische Supply-Regeln auswerten
- ökonomische Leitplanken berücksichtigen
- potenzielle Teilnehmer je Buy-in prüfen
- maximale sichtbare Scheduled Rooms respektieren
- Entscheidungen nachvollziehbar loggen

Der GameRoomSupplyService ist kein Join-Service.

Er entscheidet nicht final, ob ein konkreter User einem Raum beitreten darf. Diese Prüfung bleibt Teil der Join-/Eligibility-Logik.

---

## 5. Raumerzeugung unabhängig vom Loginstatus

Raumerzeugung darf nicht davon abhängen, ob gerade Spieler eingeloggt sind.

Grundsatz:

- Räume werden unabhängig vom aktuellen Loginstatus bereitgestellt.
- Geplante Räume müssen existieren, bevor Spieler online kommen.
- Scheduled Rooms dürfen nicht erst erzeugt werden, wenn ein Spieler die Lobby öffnet.
- Sit'n'Go-Basisangebote sollen ebenfalls unabhängig von einzelnen User-Sessions vorhanden sein.

Beispiel:

- Ein Scheduled Room mit Startzeit 00:00 Uhr muss rechtzeitig vorher existieren.
- Er darf nicht erst erstellt werden, wenn um 23:55 Uhr ein Spieler online ist.
- Er darf auch nicht davon abhängen, dass gerade jemand online ist.

Nicht gewünscht:

- blinde Raumerzeugung bei jedem Lobby-Aufruf
- Raumerzeugung abhängig vom Login
- Raumerzeugung abhängig von einer konkreten User-Session

---

## 6. Öffentliche Lobby und Gäste-Sichtbarkeit

Auch nicht eingeloggte Gäste sollen grundsätzlich sehen können, welche Räume vorhanden sind.

Gäste sollen sehen können:

- vorhandene öffentliche Räume
- Raumname
- Raumart
- Buy-in
- aktuelle Belegung
- maximale Spielerzahl
- geplante Startzeit bei Scheduled Rooms
- öffentlichen Raumstatus

Gäste dürfen nicht:

- einem Raum beitreten
- Plätze reservieren
- Buy-ins reservieren
- interne Admin- oder Diagnosedaten sehen
- personenbezogene Detaildaten anderer Spieler sehen

Zweck:

- Das Spiel wirkt lebendig, bevor man sich registriert.
- Interessenten sehen, ob Aktivität vorhanden ist.
- Scheduled Rooms können als Event-Hinweis dienen.
- Buy-in-Stufen und Raumgrößen vermitteln Progression.

Die Lobby unterscheidet daher zwischen Sichtbarkeit und Aktion:

- Gäste dürfen schauen.
- Eingeloggte und spielberechtigte User dürfen bei erfüllten Bedingungen beitreten.

---

## 7. Datenschutz- und UI-Hinweise

Für die frühe Entwicklung darf die Lobby pragmatisch umgesetzt werden.

Trotzdem soll bereits früh datensparsam gedacht werden.

Öffentlich unkritischer sind:

- Raumname
- Raumtyp
- Buy-in
- Belegung als Zahl
- maximale Spielerzahl
- öffentliche Startzeit
- öffentlicher Status

Nicht öffentlich oder nur sehr vorsichtig zu verwenden sind:

- echte Spielernamen
- E-Mail-Adressen
- interne User-IDs
- Wallet-Stände anderer Spieler
- IP-Adressen
- detaillierte Presence-Daten
- Admin-/Debugdaten

Vor Open Beta oder Live-Betrieb muss die Lobby- und Presence-Darstellung an Datenschutz, DSGVO und interne Datenverarbeitungsrichtlinien angepasst werden.

---

## 8. Raumarten

Für Phase 3 werden zwei Raumarten geplant:

- Sit'n'Go
- Scheduled

Nicht vorgesehen sind Poker-artige Geschwindigkeitsmodi wie:

- Normal
- Turbo
- HyperTurbo

Ein separater Trainingsmodus wird für die frühe Phase nicht als eigener Raumtyp geplant.

Kostenlose Räume kommen erst später, wenn Spiellogik und KI-/Bot-Logik vorhanden sind.

---

## 9. Sit'n'Go

Sit'n'Go-Räume starten, wenn die notwendige Spieleranzahl erreicht ist.

Geplante Grundregeln:

- Sit'n'Go ist der primäre automatische Raumtyp.
- Das System hält passende offene Sit'n'Go-Räume bereit.
- Wenn ein Sit'n'Go startet, soll später Ersatz erzeugt werden.
- Die Ersatz-Erzeugung erfolgt nicht blind durch jeden Lobby-Aufruf.
- Der GameRoomSupplyService ist für das Angebot zuständig.

---

## 10. Scheduled Rooms

Scheduled Rooms sind zeitlich geplante Räume.

Geplante Grundregeln:

- Scheduled Rooms müssen rechtzeitig vor Start existieren.
- Scheduled Rooms werden nicht erst erzeugt, wenn Spieler online sind.
- Scheduled Rooms können als Event- oder Zielräume sichtbar sein.
- Scheduled Rooms dürfen auch für Gäste sichtbar sein.
- Für die frühe Phase sollen maximal 5 Scheduled Rooms gleichzeitig sichtbar sein.

Scheduled Rooms eignen sich für:

- geplante Abendspiele
- Testevents
- größere Runden
- Progressionsziele
- Auswertung von Füllzeiten und Teilnahmebereitschaft

---

## 11. Dynamische Raumversorgung statt starrer Matrix

Es soll keine starre, fest verdrahtete Template-Matrix geben.

Stattdessen bleibt die Raumversorgung dynamisch.

Grundsatz:

- Das System entscheidet anhand von Regeln, welche Räume angeboten werden.
- Vorlagen oder Regeln dienen als Leitplanken.
- Die konkrete Raumversorgung kann sich an Spielerbasis, Ökonomie, Aktivität und Bedarf orientieren.
- Nicht jede theoretisch mögliche Kombination aus Spielerzahl und Buy-in muss existieren.

Nicht gewünscht:

- dauerhaft fest codierte Matrix aus Raumgrößen und Buy-ins
- blindes Erzeugen aller Kombinationen
- Raumangebot ohne Bezug zur tatsächlichen Ökonomie

Besser:

- dynamische Room-Supply-Regeln
- konfigurierbare Leitplanken
- ökonomische Plausibilitätsprüfungen
- spätere Admin-Steuerung

---

## 12. Bevorzugte Spieleranzahl

Aus Spielerfahrung gilt:

- Stechen macht mit 4 bis 8 Spielern am meisten Spaß.

Daraus folgt:

- 4er-Räume sind gut für kleine Online-Zahlen.
- 6er-Räume sind ein wahrscheinliches Kernformat.
- 8er-Räume eignen sich gut für Scheduled Events oder stärkere Aktivitätsphasen.

Weniger priorisiert:

- 2er
- 3er
- 9er
- 10er
- 11er

Technisch können weitere Größen möglich sein, aber die dynamische Raumversorgung soll den Bereich 4 bis 8 bevorzugen.

---

## 13. Benannte Räume und Favoriten

Raumnamen sollen später nicht nur dekorativ sein.

Sie können als Grundlage für Favoriten und Auto-Join dienen.

Aus Poker-Sit'n'Go-Systemen ist bekannt:

- bestimmte Sit'n'Go-Varianten haben wiedererkennbare Namen
- Spieler favorisieren diese Raumtypen
- das System kann automatisch dem nächsten passenden freien Raum beitreten

Für stechen-mmo bedeutet das:

- Raumvorlagen oder Supply-Regeln sollten stabile Namen oder Keys haben
- Räume können mit wiedererkennbarem Namen entstehen
- Spieler können später bevorzugte Raumtypen speichern
- ein Auto-Join kann später den nächsten passenden offenen Raum suchen

Beispiele für spätere Favoritenlogik:

- bevorzugte Spieleranzahl
- bevorzugter Buy-in-Bereich
- bevorzugter Raumname
- bevorzugter Template- oder Rule-Key
- nur Sit'n'Go
- keine Scheduled Rooms
- nur Räume mit sofort verfügbarem Platz

Favoriten sind eine spätere Komfortfunktion.

Für die erste Umsetzung reicht es, die Modellierung so anzulegen, dass stabile Namen oder Keys möglich sind.

---

## 14. Sichtbar ist nicht gleich betretbar

Ein Raum darf sichtbar sein, auch wenn ein konkreter Spieler ihn aktuell nicht betreten kann.

Gründe können sein:

- nicht eingeloggt
- fehlende Spielberechtigung
- E-Mail nicht bestätigt
- UserDetails unvollständig
- Account eingeschränkt
- Account gesperrt
- fehlendes verfügbares Guthaben
- Raum voll
- Raum nicht offen
- Spieler bereits in einem gestarteten Spiel
- Buy-in kann nicht reserviert werden

Die UI soll später klar anzeigen, warum ein Raum nicht betreten werden kann.

---

## 15. Mehrfachanmeldung in wartenden Räumen

Ein Spieler darf in mehreren wartenden Räumen angemeldet sein.

Das gilt für:

- Scheduled Rooms
- offene Sit'n'Go-Räume
- Kombinationen aus Scheduled und Sit'n'Go

Wichtig:

- Mehrfachanmeldung bedeutet nicht, dass ein Spieler parallel in mehreren gestarteten Spielen sein darf.
- Der Raum, dessen Spiel für den Spieler zuerst startet, übernimmt die Führung.
- Beim Start eines führenden Raums wird der Spieler automatisch aus anderen wartenden Räumen entfernt.

---

## 16. Buy-in-Reservierung bei jedem reservierten Sitz

Für jeden reservierten Sitz in einem Buy-in-Raum wird das Buy-in reserviert.

Das gilt unabhängig davon, ob dieser Raum später die Führung übernimmt.

Grundsatz:

- Raumbeitritt reserviert Buy-in.
- Reserviertes Guthaben zählt nicht als verfügbar.
- Jeder parallele reservierte Sitz benötigt ausreichend verfügbares Guthaben.
- Dadurch wird Überbuchung vermieden.

Beispiel:

Ein Spieler reserviert Sitze in:

- Scheduled Room A mit 5.000 St$
- Sit'n'Go B mit 1.000 St$
- Sit'n'Go C mit 2.000 St$

Dann sind insgesamt reserviert:

- 8.000 St$

Das verfügbare Guthaben sinkt entsprechend.

---

## 17. Führender Raum bei Spielstart

Der Raum, der für einen Spieler zuerst startet, übernimmt die Führung.

Beim Spielstart gilt:

- Die Reservierung im führenden Raum wird eingezogen.
- Der Spieler bleibt Teilnehmer des führenden Raums.
- Der Spieler wird automatisch aus anderen wartenden Räumen entfernt.
- Reservierungen in anderen wartenden Räumen werden freigegeben.
- Freigegebene Beträge stehen dem Wallet wieder als verfügbar zur Verfügung.

Beispiel:

Ein Spieler ist angemeldet in:

- Scheduled Room A mit 5.000 St$
- Sit'n'Go B mit 1.000 St$
- Sit'n'Go C mit 2.000 St$

Sit'n'Go B startet zuerst.

Dann:

- Sit'n'Go B übernimmt die Führung.
- 1.000 St$ werden eingezogen.
- Der Spieler wird aus Scheduled Room A entfernt.
- Der Spieler wird aus Sit'n'Go C entfernt.
- 5.000 St$ werden freigegeben.
- 2.000 St$ werden freigegeben.
- Insgesamt werden 7.000 St$ wieder verfügbar.

---

## 18. Automatisches Leave aus anderen wartenden Räumen

Beim Start eines führenden Raums werden andere wartende Raumteilnahmen automatisch verlassen.

Dieses automatische Leave muss:

- nachvollziehbar sein
- Wallet-Reservierungen freigeben
- Raumbelegung aktualisieren
- nicht als freiwilliges manuelles Leave missverstanden werden
- für spätere Auswertung erkennbar sein

Möglicher späterer Teilnahme-Status:

- auto_left

Möglicher späterer Leave-Grund:

- leading_room_started

---

## 19. Konsistenz und Race Conditions

Die Startlogik muss später besonders sorgfältig umgesetzt werden.

Risiken:

- zwei Räume starten gleichzeitig
- ein Spieler ist in beiden Räumen angemeldet
- Reservierungen werden doppelt eingezogen
- Reservierungen werden nicht freigegeben
- Raumbelegungen werden inkonsistent

Deshalb muss die spätere Umsetzung vermutlich nutzen:

- Datenbanktransaktionen
- geeignete Locks
- eindeutige Statusübergänge
- idempotente Startlogik
- klare Trennung zwischen Reservierung und Einzug
- klare Trennung zwischen wartender Anmeldung und aktiver Spielteilnahme

---

## 20. Statusmodelle

Mit Statusmodell sind die Zustände von Räumen, Teilnahmen und Reservierungen gemeint.

Ein einfaches Raumstatusmodell könnte später enthalten:

- open
- scheduled
- starting
- running
- finished
- cancelled
- expired

Ein einfaches Teilnahme- oder Sitzstatusmodell für wartende Räume könnte später enthalten:

- reserved
- committed
- left
- auto_left

Für laufende Spiele ist zusätzlich zwischen Spielteilnahme und Verbindungs-/Steuerungszustand zu unterscheiden.

Mögliche Connectivity- oder Control-Zustände während eines laufenden Spiels:

- connected
- disconnected
- controlled_by_ai

Diese Statuswerte sind noch nicht final.

Wichtig ist nur:

- Raumstatus und Teilnahme-Status sind getrennte Konzepte.
- Ein Raum kann offen sein, während ein Spieler dort reserviert ist.
- Ein Spieler kann aus einem wartenden Raum automatisch entfernt werden, ohne dass der Raum selbst beendet ist.
- Ein laufender Raum unterscheidet sich von einem wartenden Raum.
- In einem laufenden Spiel bleibt ein Teilnehmer regeltechnisch im Spiel.
- Ein Disconnect entfernt den Spieler nicht automatisch aus dem laufenden Spiel.
- Bei Disconnect greifen die bestehenden Disconnect-Regeln; der Spieler wird dann durch KI/Auto-Play übernommen.

Die konkreten Statusmodelle werden in einer späteren Version detailliert ausgearbeitet.

---

## 21. Tabellen und Modelle

Die konkreten Datenbanktabellen und Laravel-Modelle werden später aus den fachlichen Regeln abgeleitet.

Mögliche spätere Bausteine:

- game_rooms
- game_room_participants
- game_room_seats
- game_room_supply_rules
- game_room_templates
- Wallet-Reservierungen
- Ledger-Buchungen

Diese Liste ist nicht final.

Für v0.1 gilt:

- Erst werden die fachlichen Regeln festgehalten.
- Danach werden Tabellen, Modelle und Migrationen daraus abgeleitet.

---

## 22. Scheduler, Queue und Auslösung

Die konkrete Scheduler-/Queue-Mechanik wird später ausführlicher dokumentiert.

Für v0.1 gilt:

- Raumerzeugung ist unabhängig vom Loginstatus.
- Der GameRoomSupplyService wird nicht blind bei jedem Lobby-Aufruf ausgeführt.
- Scheduled Rooms müssen rechtzeitig vor ihrer Startzeit existieren.
- Sit'n'Go-Angebote sollen durch geplante oder kontrollierte Prozesse bereitgestellt werden.

Mögliche spätere Mechanismen:

- Artisan Command
- Laravel Scheduler
- Cronjob
- Queue Job
- Admin-Auslösung
- Event-getriggerte Ergänzung nach Spielstart
- Locking gegen parallele Supply-Läufe

Diese Mechanik wird in einer späteren Version vertieft.

---

## 23. Trennung von Supply und Join Eligibility

Supply und Join Eligibility sind getrennte Entscheidungen.

Supply-Frage:

- Soll dieser Raum existieren oder sichtbar angeboten werden?

Join-Frage:

- Darf dieser konkrete User jetzt beitreten?

Supply-Kriterien können sein:

- Raumart erlaubt
- dynamische Regel aktiv
- ökonomisch sinnvoll
- genug potenzielle Teilnehmer
- gewünschtes Basisangebot noch nicht erfüllt
- Scheduled-Limit nicht überschritten

Join-Kriterien können sein:

- User ist eingeloggt
- User ist spielberechtigt
- Account ist nicht gesperrt
- UserDetails sind vollständig
- E-Mail ist bestätigt
- Raum ist offen
- Raum hat freie Plätze
- User hat genug verfügbares Guthaben
- Buy-in kann reserviert werden
- User ist nicht bereits in einem gestarteten Spiel

---

## 24. Offene Punkte für v0.2

Folgende Punkte sollen in späteren Versionen genauer ausgearbeitet werden:

- konkrete Datenmodelle
- konkrete Migrationen
- genaue Raumstatuswerte
- genaue Teilnahme-/Sitzstatuswerte
- genaue Wallet-Reservierungsstruktur
- Ledger-Typen für Reservierung, Einzug und Freigabe
- genaue Scheduler-/Queue-Architektur
- Locking-Strategie für Spielstart
- Admin-Steuerung der dynamischen Supply-Regeln
- Favoriten- und Auto-Join-Konzept
- Gäste-Lobby-UI
- eingeloggte Lobby-UI
- Datenschutzprüfung für Open Beta und Live-Betrieb

---

## 25. Aktueller Konsens

Aktueller Stand:

- Die Phase-3-Raumerstellung bekommt eine eigene Dokumentation.
- Der Service heißt GameRoomSupplyService.
- Spieler erstellen keine Räume.
- Räume werden systemseitig erzeugt.
- Raumerzeugung ist unabhängig vom Loginstatus.
- Gäste dürfen öffentliche Raumangebote sehen.
- Laravel bleibt autoritativ.
- HomeServer ist nur optionaler Realtime-Beschleuniger.
- Raumarten sind Sit'n'Go und Scheduled.
- Es gibt keine starre Raum-Matrix.
- Die Raumversorgung bleibt dynamisch.
- 4 bis 8 Spieler sind der bevorzugte Spaßbereich.
- Benannte Räume sollen spätere Favoriten ermöglichen.
- Spieler dürfen in mehreren wartenden Räumen angemeldet sein.
- Jeder reservierte Sitz reserviert das Buy-in.
- Der zuerst startende Raum übernimmt die Führung.
- Der führende Raum zieht sein reserviertes Buy-in ein.
- Andere wartende Raumteilnahmen werden automatisch verlassen.
- Reservierungen anderer Räume werden freigegeben.
- Ein Spieler darf nicht parallel in mehreren gestarteten Spielen landen.
- Statusmodelle, Tabellen und Scheduler-Mechanik werden in späteren Versionen vertieft.

