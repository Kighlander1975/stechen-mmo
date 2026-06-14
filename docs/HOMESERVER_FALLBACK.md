# stechen-mmo — HomeServer-Fallback-Prinzip

Stand: Juni 2026  
Status: Architekturentscheidung für Phase 1

---

## 1. Ziel

Dieses Dokument beschreibt das Fallback-Prinzip für eine spätere Realtime- oder HomeServer-Anbindung.

Das Projekt soll auch dann lauffähig bleiben, wenn kein separater Realtime-Server oder HomeServer verfügbar ist.

---

## 2. Grundsatz

Laravel ist die autoritative Anwendung.

Das bedeutet:

```text
Laravel speichert den verbindlichen Spielzustand.
Laravel validiert Aktionen.
Laravel entscheidet über gültige Zustandsänderungen.
Laravel stellt den aktuellen Zustand über HTTP-Endpunkte bereit.
```

Ein externer HomeServer darf diese Autorität nicht ersetzen.

---

## 3. MVP-Verhalten

Für das MVP gilt:

```text
HTTP zuerst.
Polling ist erlaubt.
Realtime ist optional.
```

Clients können den aktuellen Zustand über normale HTTP- oder JSON-Endpunkte abrufen.

Wenn später ein Realtime-Server verfügbar ist, darf dieser die Benutzeroberfläche schneller aktualisieren, aber nicht die alleinige Quelle der Wahrheit sein.

---

## 4. Rolle eines späteren HomeServers

Ein späterer HomeServer kann verwendet werden für:

- schnellere Zustandsverteilung
- Push-Benachrichtigungen
- WebSocket-Verbindungen
- Lobby-Updates
- Tisch-Updates
- Chat-Events
- Anwesenheitsinformationen

Er ist jedoch nicht zuständig für:

- verbindliche Spielregeln
- endgültige Spielentscheidungen
- Wallet-Buchungen
- Ledger-Einträge
- persistente Spielzustände als einzige Quelle

---

## 5. Fallback-Regel

Wenn kein HomeServer verfügbar ist, muss das System weiterhin funktionieren.

Fallback-Verhalten:

```text
Client fragt Laravel per HTTP ab.
Laravel liefert aktuellen Zustand.
Client rendert den Zustand.
Aktionen werden an Laravel gesendet.
Laravel validiert und speichert.
```

Realtime verbessert nur die Aktualität, ersetzt aber nicht HTTP.

---

## 6. Keine harte WebSocket-Abhängigkeit

In Phase 1 und für das MVP dürfen keine Architekturentscheidungen getroffen werden, die voraussetzen, dass WebSockets vorhanden sind.

Das bedeutet:

- keine Spiellogik ausschließlich im WebSocket-Server
- keine Spielaktionen nur über WebSocket
- keine UI, die ohne WebSocket grundsätzlich unbedienbar ist
- keine persistenten Zustände ausschließlich im Realtime-Prozess

---

## 7. Adapter-Idee

Später kann eine Schnittstelle eingeführt werden, z. B.:

```text
RealtimeBroadcasterInterface
```

Mögliche Implementierungen:

```text
NullBroadcaster
PollingBroadcaster
WebSocketBroadcaster
HomeServerBroadcaster
```

Die Anwendung kann dadurch Events auslösen, ohne die konkrete Transporttechnik kennen zu müssen.

---

## 8. Beispielhafter Ablauf

```text
Spieler spielt Karte.
Browser sendet Aktion an Laravel.
Laravel validiert Spielzug.
Laravel speichert neuen Spielzustand.
Laravel löst optional Realtime-Event aus.
Andere Clients erhalten Update per Realtime oder Polling.
```

Wichtig:

```text
Auch wenn das Realtime-Event fehlschlägt, bleibt der Spielzustand korrekt.
```

---

## 9. Architekturentscheidung

Für stechen-mmo gilt:

```text
Laravel ist autoritativ.
HTTP/Polling ist der MVP-Fallback.
Realtime/HomeServer ist eine optionale Erweiterung.
Spielzustand und Wallet-Daten liegen nicht ausschließlich im Realtime-Server.
```

---

## 10. Bezug zu Phase 1

Dieses Dokument erfüllt die Phase-1-Anforderung:

```text
HomeServer-Fallback-Prinzip ist dokumentiert.
```

Die technische Implementierung eines HomeServers ist nicht Bestandteil von Phase 1.
