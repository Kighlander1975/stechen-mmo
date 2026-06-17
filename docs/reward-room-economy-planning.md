# Reward-, Raum- und Ökonomie-Planung

Stand: Juni 2026  
Status: fachliche Planungsnotiz für Closed Alpha/Beta, Raumversorgung und Reward-Aktionen

---

## 1. Zweck

Dieses Dokument hält aktuelle fachliche Entscheidungen zur frühen Spielgeld-Ökonomie, zur automatischen Raumversorgung und zu zeitlich begrenzten Reward-Multiplikatoren fest.

Die Inhalte dienen als Planungsgrundlage für spätere Umsetzungsschritte in Laravel, Admin Dashboard, Wallet/Ledger, Lobby und Room-Supply-Logik.

---

## 2. Grundsatz: keine durch Spieler erstellten Räume

Für stechen-mmo gilt:

- Spieler erstellen keine eigenen Räume.
- Räume werden systemseitig erzeugt und verwaltet.
- Das System hält ein dynamisches Raumangebot bereit.
- Admins können später Vorlagen, Parameter oder Aktionen verwalten.
- Spieler wählen aus vorhandenen Räumen aus und treten diesen bei, sofern sie berechtigt sind.

Damit werden vermieden:

- Raum-Spam durch Spieler
- frei gewählte problematische Raumtitel
- missbräuchliche private Lobbys
- unnötige Moderationslast
- komplexe Spieler-Limits für Raumerstellung

---

## 3. Autorität und HomeServer-Rolle

Laravel bleibt die autoritative Anwendung.

Laravel ist zuständig für:

- verbindlichen Spielzustand
- Raumstatus
- Join-/Leave-Entscheidungen
- Wallet-Prüfungen
- Buy-in-Reservierungen
- Ledger-Buchungen
- Preispool und Rake
- Spielstart
- persistente Daten

Der HomeServer kann später als Beschleuniger eingesetzt werden für:

- Lobby-Updates
- Tisch-Updates
- Presence-/Online-Informationen
- WebSocket-/Realtime-Verteilung
- schnellere UI-Aktualisierung

Der HomeServer darf nicht zuständig sein für:

- verbindliche Spielregeln
- endgültige Wallet-Entscheidungen
- Ledger-Buchungen
- persistente Wahrheit
- autoritative Raumerzeugung

Fallback-Regel:

- Das System muss ohne HomeServer funktionieren.
- HTTP/Polling bleibt Pflicht-Fallback.
- Realtime ist optionaler Komfort und ersetzt Laravel nicht.

---

## 4. Raumarten

Es gibt für die Raumplanung nur zwei Raumarten beziehungsweise Spielmodi:

- Sit'n'Go
- Scheduled

Nicht vorgesehen sind Poker-artige Geschwindigkeitsmodi wie:

- Normal
- Turbo
- HyperTurbo

Auch ein separater Trainingsmodus als Raumart wird vorerst nicht eingeführt.

---

## 5. Sit'n'Go-Räume

Sit'n'Go-Räume starten, wenn die erforderliche Spieleranzahl erreicht ist.

Geplante Logik:

- Das System hält offene Sit'n'Go-Räume anhand aktivierter Vorlagen bereit.
- Wenn ein Raum startet, soll automatisch oder durch den nächsten Supply-Lauf Ersatz entstehen.
- Zu Beginn ist ein Supply-Command oder geplanter Prozess sicherer als eine aggressive Erzeugung bei jedem Lobby-Aufruf.
- Später kann ein best-effort Nachfüllen direkt nach Spielstart ergänzt werden.

Wichtig:

- Sit'n'Go-Räume werden nicht durch Spieler erzeugt.
- Sit'n'Go-Angebote richten sich an ökonomisch sinnvolle Kombinationen aus Spieleranzahl und Buy-in.
- Nicht jede theoretisch mögliche Kombination muss aktiv sein.

---

## 6. Scheduled Rooms

Scheduled Rooms sind zeitlich geplante Räume.

Für die frühe Testphase gilt:

- Scheduled Rooms sollen deutlich seltener sein als Sit'n'Go-Räume.
- Zu Beginn sollen maximal 5 Scheduled Rooms sichtbar sein.
- Scheduled Rooms können auch als Werbung/Zielbild für spätere Spiele dienen.
- Sie dürfen Räume zeigen, die nicht jeder Spieler sofort betreten kann.
- Fehlende St$ sind dann das primäre Join-Hindernis.

Scheduled Rooms eignen sich besonders für:

- Abendspiele
- geplante Testevents
- größere 6er- oder 8er-Runden
- sichtbare Progressionsziele
- Auswertung von Füllzeiten und Teilnahmebereitschaft

---

## 7. Bevorzugte Spieleranzahl

Aus praktischer Spielerfahrung gilt:

- Stechen macht mit 4 bis 8 Spielern am meisten Spaß.

Daraus folgt für die Raumplanung:

- 4er-Räume sind gut für kleine Online-Zahlen.
- 6er-Räume sind wahrscheinlich ein Kernformat.
- 8er-Räume eignen sich gut für Scheduled Events oder stärkere Aktivitätsphasen.

Weniger priorisiert:

- 2er
- 3er
- 9er
- 10er
- 11er

Technisch können größere Spielerzahlen möglich sein, aber die automatische Raumversorgung soll den Bereich 4 bis 8 bevorzugen.

---

## 8. Kostenlose Räume

Kostenlose Räume werden nicht sofort angeboten.

Sie kommen erst später, wenn mindestens folgende Voraussetzungen erfüllt sind:

- Spiellogik ist implementiert.
- KI-/Bot-Logik ist vorhanden.

Begründung:

- Kostenlose Räume sind vor allem für Training, Einstieg, Testen und KI-Spiel sinnvoll.
- Ohne Spiel-Engine und ohne brauchbare KI wären kostenlose Räume nur leere Hüllen.
- Für die frühe Closed Alpha/Beta wird stattdessen mit niedrigen Buy-ins gearbeitet.

---

## 9. Buy-in-Angebot und potenzielle Teilnehmer

Eine Buy-in-Stufe soll nur angeboten werden, wenn genug potenzielle Teilnehmer für die konkrete Tischgröße existieren.

Grundregel:

- Ein Raum mit einer bestimmten Spieleranzahl und einem bestimmten Buy-in ist nur sinnvoll, wenn mindestens genug spielberechtigte Nutzer dieses Buy-in bezahlen können.

Beispiel:

- Ein 6-Spieler-Raum mit 1.000.000 St$ Buy-in macht nur Sinn, wenn mindestens 6 spielberechtigte Nutzer mindestens 1.000.000 St$ verfügbar haben.

Perspektivisch kann ein Pufferfaktor genutzt werden:

- benötigte potenzielle Teilnehmer = max_players * Faktor

Beispiel:

- 6er-Tisch
- Faktor 2
- benötigt mindestens 12 potenzielle Teilnehmer mit ausreichendem verfügbarem Guthaben

Wichtig ist verfügbares Guthaben:

- available_units = balance_units - reserved_units

Reserviertes Spielgeld zählt nicht als frei verfügbar.

---

## 10. Sichtbar ist nicht gleich betretbar

Die Lobby darf Räume sichtbar machen, die ein einzelner Spieler noch nicht betreten kann.

Gründe für Nichtbeitritt können sein:

- fehlende St$
- fehlende Spielberechtigung
- Raum voll
- Raum nicht offen
- Account eingeschränkt
- Account gesperrt
- UserDetails unvollständig
- E-Mail nicht bestätigt
- Spieler bereits in laufendem Spiel

Für die frühe Closed Testphase ist besonders wichtig:

- Fehlende St$ werden häufig das Hauptkriterium sein.
- Zielräume dürfen sichtbar sein, um Progression zu zeigen.
- Die UI soll später klar anzeigen, warum ein Raum nicht betreten werden kann.

---

## 11. Closed Alpha/Beta Rahmen

Für die Closed Alpha/Beta wird mit ungefähr 20 bis 40 Testpersonen gerechnet.

Diese Testpersonen stammen voraussichtlich aus einer bestehenden Gilde beziehungsweise einem bekannten Umfeld.

Während der Closed Testphase kann der Admin Spielern, die blank sind, wieder St$ zukommen lassen.

Admin-Gutschriften sollen nicht direkt als Datenbankänderung erfolgen.

Stattdessen gilt:

- Admin-Gutschriften laufen über Wallet/Ledger.
- Buchungen müssen nachvollziehbar sein.
- Eine Admin-Gutschrift braucht Betrag, Empfänger, Admin, Zeitpunkt und optionalen Grund.
- Direkte Änderungen an Wallet-Kontoständen ohne Ledger sind zu vermeiden.

Mögliche Buchungstypen:

- admin_grant
- closed_alpha_refill
- test_compensation
- bug_compensation
- manual_adjustment

---

## 12. Reward-Basiswerte

Die Basiswerte bleiben die in der bestehenden Planung dokumentierten Werte.

Registrierungsbonus:

- 1.000 St$
- einmalig pro Account
- direkt nach Account-Erstellung
- Ledger-Typ: registration_grant

Daily Claims:

| Streak-Tag | Basisbetrag |
|---:|---:|
| 1 | 200 St$ |
| 2 | 300 St$ |
| 3 | 400 St$ |
| 4 | 500 St$ |
| 5 | 700 St$ |
| 6 | 850 St$ |
| 7 | 1.000 St$ |
| 8 bis 30 | 1.000 St$ |
| 31 | 5.000 St$ |

Weitere Regeln:

- Daily Claim wird nicht automatisch gutgeschrieben.
- Nutzer muss aktiv abholen.
- Daily Claim ist am Reward-Tag der Registrierung nicht erlaubt.
- Erster Daily Claim ist frühestens ab dem nächsten Reward-Tag nach Registrierung möglich.
- Reward-Tag orientiert sich an Europe/Berlin mit Tageswechsel 04:00.
- Wird ein Reward-Tag ausgelassen, fällt der nächste Claim auf Streak-Tag 1 zurück.
- Daily Claims sollen nur spielberechtigten Accounts erlaubt sein.

---

## 13. Woche-1-Basiswert ohne Aktion

Bei Basiswerten ohne Multiplikator ergibt sich für die erste Woche:

Registrierungsbonus:

- 1.000 St$

Daily Claims Tag 1 bis 7:

- 200 + 300 + 400 + 500 + 700 + 850 + 1.000 = 3.950 St$

Gesamt:

- 1.000 + 3.950 = 4.950 St$

Damit ist der theoretische Woche-1-Basiswert ohne Aktion:

- 4.950 St$

---

## 14. Reward-Aktionen statt harter Config

Reward-Multiplikatoren werden nicht hart in einer Config-Datei gepflegt.

Stattdessen sollen sie über das Admin Dashboard verwaltet werden.

Begründung:

- Closed Tests brauchen flexible Anpassung.
- Open Tests brauchen schnelle ökonomische Nachsteuerung.
- Live-Betrieb kann zeitlich begrenzte Events und Werbeaktionen nutzen.
- Admins sollen Faktoren ohne Deployment ändern können.
- Ökonomie-Auswertung soll auf echten Aktionsdaten beruhen.

Die Basiswerte bleiben unverändert.

Reward-Aktionen verändern nur die effektiv gebuchten Beträge während ihres gültigen Zeitraums.

---

## 15. Admin Dashboard Card

Das Admin Dashboard soll eine klickbare Card für Reward-Aktionen enthalten.

Beispiel bei aktiver Aktion:

- Aktive Reward-Aktion: Closed Alpha Boost
- Registrierung: 10.0x
- Daily Claims: 3.0x
- Aktiv bis: Datum/Uhrzeit
- Hinweis: Basiswerte bleiben unverändert. Alle Buchungen werden über Wallet/Ledger dokumentiert.

Beispiel ohne aktive Aktion:

- Keine aktive Reward-Aktion
- Registrierung: 1.0x
- Daily Claims: 1.0x
- Hinweis: Es gelten die Basiswerte. Über Reward-Aktionen können zeitlich begrenzte Multiplikatoren für Tests, Events oder Werbeaktionen erstellt werden.

Klickziel:

- Admin-Bereich für Reward-Aktionen mit CRUD-Verwaltung

---

## 16. Reward-Aktionen CRUD

Reward-Aktionen sollen adminseitig per CRUD verwaltet werden.

Eine Reward-Aktion enthält mindestens:

- Name
- Beschreibung oder Hinweistext
- Startzeit
- Endzeit
- Registrierungsbonus-Multiplikator
- Daily-Claim-Multiplikator
- Aktivstatus
- Ersteller/Admin
- Änderungsadmin
- Zeitstempel

Später optional:

- interne Admin-Notiz
- öffentliche Bezeichnung
- Auswertungslabel
- Kampagnenbezug
- Quelle für Business-Auswertung

---

## 17. Keine überlappenden Reward-Aktionen

Es darf maximal eine Reward-Aktion gleichzeitig gültig sein.

Regel:

- Zeiträume von Reward-Aktionen dürfen sich nicht überlappen.
- Das Admin-CRUD muss Überlappungen validieren und Speichern verhindern.
- Multiplikatoren werden nicht gestapelt.

Nicht erlaubt:

- Closed Alpha 10.0x Registrierung gleichzeitig mit Wochenendaktion 2.0x Registrierung

Erlaubt:

- Aktion A endet vor Aktion B.
- Aktion B beginnt erst nach Ende von Aktion A.

Fallback:

- Wenn keine Aktion aktuell gültig ist, gelten automatisch die Basiswerte mit Faktor 1.0.

Die Basis ist kein eigenes Event, sondern der Standardzustand.

---

## 18. Multiplikator-Regeln

Multiplikatoren gelten getrennt für:

- Registrierungsbonus
- Daily Claims

Erlaubt:

- Dezimalwerte mit maximal einer Nachkommastelle
- Minimum 1.0x
- Registrierung maximal 10.0x
- Daily Claims maximal 5.0x

Nicht erlaubt:

- Werte unter 1.0x
- mehr als eine Nachkommastelle
- Registrierungsbonus über 10.0x
- Daily Claims über 5.0x

Beispiele erlaubt:

- 1.0x
- 1.5x
- 2.0x
- 3.7x
- 10.0x für Registrierung
- 5.0x für Daily Claims

Beispiele nicht erlaubt:

- 0.5x
- 1.25x
- 2.75x
- 10.1x Registrierung
- 5.1x Daily Claims

---

## 19. Rundung

Effektive Reward-Beträge werden zugunsten des Betreibers abgerundet.

Regel:

- effective_amount_units = floor(base_amount_units * multiplier)

Beispiele:

| Basis | Faktor | Rechnerisch | Effektiv |
|---:|---:|---:|---:|
| 1.000 | 1.5x | 1.500 | 1.500 |
| 850 | 1.3x | 1.105 | 1.105 |
| 999 | 1.1x | 1.098,9 | 1.098 |
| 101 | 1.5x | 151,5 | 151 |

Technische Empfehlung:

- Multiplikatoren intern als Zehntel-Faktor speichern.
- Beispiel: 15 entspricht 1.5x.
- Beispiel: 30 entspricht 3.0x.
- Beispiel: 100 entspricht 10.0x.

Dadurch werden Float-Rundungsprobleme vermieden.

Berechnung bei Zehntel-Faktor:

- effective_amount_units = floor(base_amount_units * multiplier_tenths / 10)

---

## 20. Beispiel: Closed Alpha Aktion

Beispielhafte Aktion:

- Registrierungsbonus: 10.0x
- Daily Claims: 3.0x

Rechnung:

Registrierung:

- 1.000 * 10.0 = 10.000 St$

Daily Claims Tag 1 bis 7:

- 3.950 * 3.0 = 11.850 St$

Theoretischer Woche-1-Wert:

- 10.000 + 11.850 = 21.850 St$

Damit kann die initiale Lobby Räume enthalten, die sich an ungefähr 22.000 St$ theoretischem Woche-1-Maximum orientieren.

---

## 21. Beispielhafte Buy-in-Stufen für Closed Test

Bei einer Closed Alpha Aktion mit ungefähr 21.850 St$ theoretischem Woche-1-Wert sind folgende Stufen plausibel:

Sehr niedrig bis niedrig:

- 500 St$
- 1.000 St$
- 2.000 St$

Mittel:

- 5.000 St$
- 10.000 St$

Vorsichtig als Zielraum oder Scheduled:

- 20.000 St$

Zu früh für den Start:

- 50.000 St$
- 100.000 St$
- 1.000.000 St$

Diese Liste ist keine finale Vorgabe, sondern eine Planungsgrundlage.

---

## 22. Ökonomie-Auswertung in Closed/Open Beta

Closed und Open Beta dienen ausdrücklich der Ökonomie-Auswertung.

Zu beobachten sind unter anderem:

- Wie schnell sammeln Spieler St$?
- Wie schnell verlieren Spieler St$?
- Wie oft werden Spieler blank?
- Wie viel Admin-Nachschub ist nötig?
- Welche Buy-in-Stufen werden angenommen?
- Welche Räume bleiben leer?
- Welche Räume starten zuverlässig?
- Wie lange dauert es, bis Räume voll werden?
- Wie viel Rake entsteht?
- Wie stark wächst oder schrumpft Spielgeld im Umlauf?
- Wie verteilen sich Vermögen nach 1, 3, 7, 14 und 30 Tagen?
- Welche Reward-Multiplikatoren erzeugen sinnvolle Aktivität?
- Welche Multiplikatoren erzeugen zu viel Inflation?

---

## 23. Business-Plan- und Echtgeld-Perspektive

Die Spielgeld-Ökonomie bleibt Spielgeld.

Grundsätze:

- St$ haben keinen Echtgeldwert.
- St$ sind nicht auszahlbar.
- Spielgeld ist kein Echtgeld.

Trotzdem können die Daten aus Closed/Open Beta später für Hochrechnungen genutzt werden.

Interessante Auswertungen:

- aktive Spieler pro Tag, Woche und Monat
- Retention
- Spiele pro Spieler
- Buy-in-Volumen
- Preispool-Volumen
- Rake-Volumen
- durchschnittlicher Spielgeldverbrauch
- Blank-Rate
- Admin-Refill-Rate
- Raumfüllzeiten
- Startquoten je Raumtyp
- Akzeptanz verschiedener Buy-in-Stufen

Diese Daten können später in eine Business-Plan-Komponente einfließen, falls ein Echtgeld-Feature rechtlich und konzeptionell weiterverfolgt wird.

Wichtig:

- Die Beta-Daten sind Grundlage für Modellierung und Hochrechnung.
- Sie bedeuten nicht automatisch eine Echtgeld-Umsetzung.
- Echtgeld-Features benötigen separate rechtliche, regulatorische und technische Prüfung.

---

## 24. Room Supply Manager als späterer Baustein

Später soll ein systemseitiger Room Supply Manager entstehen.

Mögliche Aufgaben:

- aktive Raumvorlagen prüfen
- aktuelle Ökonomie prüfen
- registrierte Spieler zählen
- Online-Spieler oder Lobby-Spieler berücksichtigen
- potenzielle Teilnehmer je Buy-in-Stufe ermitteln
- offene Räume je Vorlage zählen
- fehlende Räume erzeugen
- Scheduled Rooms begrenzen
- Entscheidungen loggen

Mögliche Kriterien:

- Spielgeld gesamt im Umlauf
- registrierte Spieler gesamt
- aktuelle Online-Spieler
- Basisangebot
- potenzielle Teilnehmer mit ausreichendem verfügbarem Guthaben
- theoretischer Woche-1-Wert inklusive aktiver Reward-Aktion

Der Room Supply Manager soll nicht bei jedem Lobby-Aufruf blind Räume erzeugen.

Besser:

- Artisan Command
- Scheduler
- Admin-Auslösung
- später Queue Job oder Event-getriggerte Ergänzung

---

## 25. Trennung von Supply und Join Eligibility

Die Entscheidung, ob ein Raum angeboten wird, ist getrennt von der Entscheidung, ob ein User beitreten darf.

Supply-Frage:

- Soll dieser Raum existieren beziehungsweise sichtbar angeboten werden?

Join-Frage:

- Darf dieser konkrete User jetzt beitreten?

Supply-Kriterien:

- Raumvorlage aktiv
- Raumtyp erlaubt
- ökonomisch sinnvoll
- genug potenzielle Teilnehmer
- genug registrierte oder aktive Spieler
- Zielangebot noch nicht erreicht

Join-Kriterien:

- User spielberechtigt
- Account nicht gesperrt
- E-Mail bestätigt
- UserDetails vollständig
- genug verfügbares Guthaben
- Raum offen
- Raum nicht voll
- User nicht bereits in laufendem Spiel
- Buy-in kann reserviert werden

---

## 26. Zusammenfassung der aktuellen Entscheidungen

Aktueller Konsens:

- Spieler erstellen keine Räume.
- Räume werden systemseitig erzeugt.
- Raumarten sind Sit'n'Go und Scheduled.
- Keine Poker-artigen Geschwindigkeitsmodi.
- 4 bis 8 Spieler sind der bevorzugte Spaßbereich.
- Kostenlose Räume kommen erst nach Spiellogik und KI/Bot-Logik.
- Scheduled Rooms sind anfangs auf maximal 5 sichtbare Räume begrenzt.
- Räume dürfen sichtbar sein, auch wenn ein Spieler sie wegen fehlender St$ nicht betreten kann.
- Reward-Basiswerte bleiben unverändert.
- Reward-Multiplikatoren werden über Admin Dashboard und CRUD als zeitlich begrenzte Aktionen verwaltet.
- Es gibt keine harte Config-Datei als Zielverwaltung für Multiplikatoren.
- Es darf nur eine Reward-Aktion gleichzeitig gültig sein.
- Zeiträume dürfen sich nicht überlappen.
- Ohne aktive Aktion gelten automatisch 1.0x und damit die Basiswerte.
- Multiplikatoren unter 1.0x sind nicht erlaubt.
- Multiplikatoren dürfen maximal eine Nachkommastelle haben.
- Registrierungsbonus-Multiplikator maximal 10.0x.
- Daily-Claim-Multiplikator maximal 5.0x.
- Ergebnisse werden zugunsten des Betreibers abgerundet.
- Closed/Open Beta dienen auch der Ökonomie-Auswertung.
- Die Daten können später für Business-Plan-Hochrechnungen rund um ein mögliches Echtgeld-Feature genutzt werden.
- Laravel bleibt autoritativ.
- HomeServer ist optionaler späterer Beschleuniger, nicht Quelle der Wahrheit.
