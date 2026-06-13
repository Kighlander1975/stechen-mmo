# Architektur- und Fallback-Konzept für Kartenspiel auf ALL-INKL + optionalem HomeServer

Stand: 13.06.2026

## 1. Ausgangslage

Für das Kartenspiel-MVP wird zunächst ALL-INKL Shared Hosting verwendet. Die gemessene Umgebung ist für einen MVP-Betrieb geeignet:

- PHP 8.4.x
- OPcache aktiv
- ca. 15 parallele PHP-FPM-Worker
- `memory_limit` ca. 512 MB
- Redis-/PDO-MySQL-Unterstützung vorhanden

Das Hosting eignet sich für einen kontrollierten Start mit optimierten API-Endpunkten, adaptivem Polling und klarer Begrenzung aktiver Spiele.

---

## 2. Grundprinzip der Architektur

Die wichtigste Architekturregel lautet:

> Laravel/ALL-INKL bleibt immer die autoritative Quelle für Spielstand, Spielregeln, Authentifizierung und Datenbank.

Der HomeServer darf später optional eingesetzt werden, aber nur als Realtime-Beschleuniger.

### ALL-INKL / Laravel übernimmt

- Login und Accounts
- Spielregeln
- Spielzüge validieren
- Spielstand speichern
- Datenbank
- State-Versionierung
- Anti-Cheat-Prüfungen
- Matchmaking und Warteschlange
- API-Endpunkte

### HomeServer übernimmt optional

- WebSocket-Verbindungen
- Live-Benachrichtigungen
- Presence/Online-Status
- Events wie `game.updated`
- optional Chat-Live-Events

Der HomeServer besitzt nicht die Wahrheit über das Spiel.

---

## 3. Spielzug- und Update-Fluss

### Normaler Spielzug

```text
Client -> Laravel/ALL-INKL -> Datenbank
```

Laravel prüft:

- Ist der Spieler im Spiel?
- Ist er am Zug?
- Besitzt er die Karte?
- Ist die Aktion regelkonform?
- Ist der Idempotency-Key neu?

Danach:

- Spielzug speichern
- `state_version` erhöhen
- aktualisierten State zurückgeben
- optional HomeServer über Änderung informieren

### WebSocket-Benachrichtigung

Wenn HomeServer aktiv ist:

```text
Laravel -> HomeServer -> Clients
```

Der HomeServer sendet nur einen Hinweis, z. B.:

```json
{
  "type": "game.updated",
  "game_id": 42,
  "version": 124
}
```

Clients laden den echten Spielstand weiterhin von Laravel.

---

## 4. Polling-Konzept

Polling bleibt immer als Fallback verfügbar.

### State-Version statt Full-State-Polling

Clients senden ihre bekannte Version:

```http
GET /api/games/42/state?since=123
```

Wenn nichts geändert wurde:

```json
{
  "changed": false,
  "version": 123,
  "next_poll_in_ms": 5000
}
```

Wenn geändert wurde:

```json
{
  "changed": true,
  "version": 124,
  "state": {
    "phase": "playing",
    "current_player_id": 7
  },
  "next_poll_in_ms": 3000
}
```

### Adaptive Polling-Intervalle

Mit WebSocket:

| Zustand | Polling |
|---|---:|
| Spieler am Zug | Safety-Poll 5–10s |
| Wartende Spieler | 10–15s |
| Hintergrundtab | 20–30s |

Ohne WebSocket / Fallback:

| Zustand | Polling |
|---|---:|
| Spieler am Zug | 1–3s |
| Wartende Spieler | 5–8s |
| Lobby/Warteschlange | 10–15s |
| Hintergrundtab | 20–60s |

Wichtig: Der Server sollte `next_poll_in_ms` vorgeben können.

---

## 5. Warteschlange für Spielstarts

Die Warteschlange meint keine technische Request-Queue, sondern eine Spielstart-Warteschlange.

Wenn die maximale Kapazität erreicht ist, starten neue Spiele nicht sofort, sondern werden in eine Queue gesetzt.

### Spielstatus

Empfohlene Statuswerte:

```text
lobby
queued
starting
active
finished
cancelled
```

Gegen die Kapazität zählen:

```text
starting
active
```

### Regel

Laufende Spiele werden nie wegen Last abgebrochen. Nur neue Spielstarts werden begrenzt.

---

## 6. Konservative Kapazitätsregel pro Spiel

Ein Spiel kann maximal 11 Spieler haben. Daher kann jedes aktive Spiel konservativ als 11 Slots zählen, auch wenn tatsächlich nur 2 Spieler spielen.

```text
GAME_MAX_ACTIVE_SLOTS = 150
GAME_RESERVED_SLOTS_PER_GAME = 11
```

Daraus ergibt sich:

```text
floor(150 / 11) = 13 aktive Spiele
```

Ein 14. Spiel wartet.

Vorteil:

- einfache Regel
- planbare Last
- viele kleine 1vs1-Spiele können den Server nicht unkontrolliert belasten

Nachteil:

- Kapazität wird konservativ genutzt

Für den MVP ist diese konservative Variante sinnvoll.

---

## 7. Hybrid-Betrieb mit HomeServer

Später kann der HomeServer genutzt werden, um große Spiele zu entlasten.

### Vorschlag

```text
Kleine Spiele bis 6 Spieler:
  Laravel/ALL-INKL mit Polling

Große Spiele ab 7 Spielern:
  HomeServer-WebSocket für Realtime-Updates
```

Aber weiterhin gilt:

```text
Spielzüge und Spielstand bleiben bei Laravel/ALL-INKL.
```

### Beispiel-Konfiguration

```env
GAME_MAX_ACTIVE_GAMES_FALLBACK=13
GAME_MAX_ACTIVE_GAMES_HYBRID=25
GAME_BIG_GAME_THRESHOLD=7
```

---

## 8. Betriebsmodi

Empfohlene globale Systemmodi:

```text
normal
degraded
all_inkl_only
recovering
maintenance
```

### normal

HomeServer ist verfügbar. Neue große Spiele können WebSocket nutzen.

### degraded

HomeServer ist ausgefallen. Laufende HomeServer-Spiele wechseln auf Polling-Fallback. Neue Spielstarts werden begrenzt.

### all_inkl_only

HomeServer wird nicht genutzt. Alles läuft über ALL-INKL/Polling.

### recovering

HomeServer ist wieder erreichbar, aber Hybridbetrieb wurde noch nicht freigegeben.

---

## 9. Ausfall des HomeServers während laufender Spiele

Dieser Fall ist besonders wichtig.

### Ziel

```text
HomeServer fällt aus
-> laufende Spiele werden nicht abgebrochen
-> Clients wechseln auf Polling-Fallback
-> neue Spiele werden begrenzt/queued
-> bestehende Spiele laufen langsam, aber kontrolliert zu Ende
-> danach läuft das System mit ALL-INKL weiter
```

### Ablauf

1. Client erkennt geschlossene WebSocket-Verbindung.
2. Client aktiviert Fallback-Polling.
3. Laravel erkennt HomeServer-Ausfall über Healthcheck oder Broadcast-Fehler.
4. Globaler Modus wird auf `degraded` gesetzt.
5. Aktive Spiele mit `home_ws` werden zu `polling_fallback`.
6. Neue Spiele starten nur noch nach ALL-INKL-Fallback-Limit.
7. Wenn zu viele Spiele aktiv sind, gehen neue Spiele in die Warteschlange.

---

## 10. HomeServer kommt zurück

Empfohlene Strategie:

> Laufende Fallback-Spiele bleiben bis Spielende im Polling-Fallback. Nur neue große Spiele nutzen nach Freigabe wieder den HomeServer.

Das vermeidet:

- Reconnect-Rennen
- doppelte Events
- unterschiedliche Client-Zustände
- Umschaltfehler mitten im Spiel

### Regel

```text
Wenn ein Spiel wegen Ausfall in polling_fallback gewechselt ist,
bleibt es bis zum Spielende in polling_fallback.
```

### Recovery

Wenn HomeServer wieder erreichbar ist:

```text
home_server_status = online
system_mode = recovering
```

Danach entweder:

- Admin aktiviert Hybridbetrieb manuell
- oder automatische Freigabe nach stabilen Healthchecks

Empfehlung für den Anfang:

```text
automatisch in degraded bei Ausfall,
manuell zurück in normal.
```

---

## 11. Kapazitätsmodell während Recovery

Wichtig: Wenn HomeServer zurückkommt, laufen möglicherweise noch viele Fallback-Spiele. Diese belasten weiterhin ALL-INKL.

Daher sollten Polling-/Fallback-Spiele separat gezählt werden.

Beispiel:

```text
fallback_limit = 13
hybrid_total_limit = 25
```

Regeln:

```text
polling + polling_fallback <= 13
total_active_games <= 25
```

Neue große WebSocket-Spiele dürfen nur starten, wenn das Gesamtlimit passt. Neue kleine Polling-Spiele dürfen nur starten, wenn das Fallback-Limit passt.

---

## 12. WebSocket-Fallback im Frontend

Client-Logik:

```js
ws.onclose = () => {
  realtimeConnected = false;
  enableFallbackPolling();
};

ws.onerror = () => {
  realtimeConnected = false;
  enableFallbackPolling();
};
```

Polling mit Serversteuerung:

```js
async function pollGameState() {
  const res = await fetch(`/api/games/42/state?since=${lastVersion}`);
  const data = await res.json();

  if (data.changed) {
    lastVersion = data.version;
    renderState(data.state);
  }

  schedulePoll(data.next_poll_in_ms || 5000);
}
```

Jitter sollte genutzt werden, z. B. 0–500ms, damit nicht alle Clients gleichzeitig pollen.

---

## 13. Spielaktionen und Idempotency

Spielaktionen sollten mit Pending-Lock und Idempotency-Key gesendet werden.

```js
let actionPending = false;

async function playCard(cardId) {
  if (actionPending) return;

  actionPending = true;

  try {
    const res = await fetch(`/api/games/42/play-card`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "Idempotency-Key": crypto.randomUUID()
      },
      body: JSON.stringify({ card_id: cardId })
    });

    const data = await res.json();

    if (!res.ok) {
      showError(data.message || "Aktion fehlgeschlagen");
      return;
    }

    lastVersion = data.version;
    renderState(data.state);
  } finally {
    actionPending = false;
  }
}
```

Pro Spieler sollte im Normalfall nur eine ausstehende Aktion erlaubt sein.

---

## 14. Broadcast darf Laravel nicht blockieren

Wenn Laravel den HomeServer informiert, darf ein Ausfall des HomeServers nicht die Spielzüge verlangsamen.

Daher:

- kurze Timeouts
- Fehler loggen
- Broadcast best-effort
- Spielzug nie vom Broadcast abhängig machen

Beispiel:

```php
if (app(SystemStatus::class)->homeServerAvailable()) {
    try {
        Http::timeout(0.3)->post(config('live.broadcast_url'), [
            'type' => 'game.updated',
            'game_id' => $game->id,
            'version' => $result['version'],
        ]);
    } catch (\Throwable $e) {
        app(SystemStatus::class)->markHomeServerMaybeDown();
    }
}
```

---

## 15. Sicherheit des HomeServers

Der HomeServer sollte möglichst wenig können und möglichst wenig Zugriff haben.

### Öffentlich erreichbar

Idealerweise nur:

```text
443/tcp
```

Optional:

```text
80/tcp für Let's Encrypt
```

SSH möglichst nur über LAN oder VPN.

### Maßnahmen

- Firewall
- SSH-Key statt Passwort
- Root-Login deaktivieren
- regelmäßige Updates
- eigener Systemuser für WebSocket-Dienst
- Fail2ban, falls SSH öffentlich
- TLS/WSS
- Logs und Logrotate
- Healthcheck-Endpoint
- Monitoring

### Keine direkte DB-Verbindung

Der HomeServer sollte sich nicht direkt mit der ALL-INKL-Datenbank verbinden.

Besser:

```text
Laravel sendet signierte Events an HomeServer.
HomeServer broadcastet nur.
```

---

## 16. DNS und TLS

Für Browser-WebSockets auf einer HTTPS-Seite wird WSS benötigt:

```text
wss://live.stechen-helper.de
```

Mögliches Setup:

```text
live.stechen-helper.de -> MyFRITZ-/DynDNS-Adresse
```

Der HomeServer benötigt ein gültiges TLS-Zertifikat, z. B. via:

- Caddy
- Nginx Proxy Manager
- Traefik
- Certbot
- Let's Encrypt DNS-Challenge

---

## 17. Empfohlene Strategie in Phasen

### Phase 1: MVP nur mit ALL-INKL

- Laravel-App
- API
- Datenbank
- optimiertes Polling
- Spielstart-Warteschlange
- konservatives Limit, z. B. 13 aktive Spiele

### Phase 2: HomeServer als optionaler Realtime-Beschleuniger

- WebSocket für große Spiele ab 7 Spielern
- Fallback-Polling bleibt Pflicht
- Laravel bleibt autoritativ

### Phase 3: Einnahmen/Last vorhanden

- optional Managed Server/VPS
- Laravel, Redis, Queues und WebSocket zentralisieren
- professionelles Monitoring und Deployments

---

## 18. Kernregeln als Kurzfassung

1. Laravel/ALL-INKL bleibt immer die Wahrheit.
2. HomeServer ist nur Realtime-Beschleuniger.
3. Alle Spielaktionen laufen über Laravel.
4. WebSocket ist optional; Polling funktioniert immer.
5. State-Version statt Full-State-Polling verwenden.
6. Server gibt `next_poll_in_ms` vor.
7. Neue Spielstarts werden bei Last queued.
8. Laufende Spiele werden nicht wegen Last abgebrochen.
9. Jedes aktive Spiel kann konservativ als 11 Slots zählen.
10. HomeServer-Ausfall führt zu `polling_fallback`.
11. Fallback-Spiele bleiben bis Spielende im Fallback.
12. Wenn HomeServer zurückkommt, nutzen nur neue große Spiele wieder WebSocket.
13. Polling-/Fallback-Spiele zählen weiter gegen ein separates ALL-INKL-Limit.
14. Broadcasts zum HomeServer dürfen Laravel nie blockieren.
15. Rückkehr in Hybridbetrieb sollte anfangs manuell freigegeben werden.

---

## 19. Empfohlene Startwerte

```env
GAME_MAX_ACTIVE_SLOTS=150
GAME_RESERVED_SLOTS_PER_GAME=11
GAME_MAX_ACTIVE_GAMES_FALLBACK=13
GAME_MAX_ACTIVE_GAMES_HYBRID=25
GAME_BIG_GAME_THRESHOLD=7
```

Diese Werte sind konservative Startwerte und sollten nach echten Messungen angepasst werden.

---

## 20. Fazit

Die beste Gesamtstrategie ist ein robuster Hybridansatz:

```text
ALL-INKL trägt die Anwendung, Datenbank und Spielwahrheit.
HomeServer beschleunigt später große Spiele über WebSocket.
Bei Ausfall läuft alles kontrolliert über ALL-INKL weiter.
Neue Spiele werden bei Bedarf in eine Warteschlange gesetzt.
```

Dadurch bleiben Kosten niedrig, das MVP bleibt einfach und trotzdem ist später eine sinnvolle Skalierung möglich.
