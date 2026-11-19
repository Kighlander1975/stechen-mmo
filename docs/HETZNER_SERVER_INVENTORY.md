# Hetzner-Cloud-Server: Inventar

## Dokumentstatus

- Inventur: 2026-11-19 (drei lesende Konsolenblöcke des Projektinhabers),
  abgeschlossen
- Änderungen am Server: keine
- Bewusst nicht dokumentiert: öffentliche IP-Adressen, Zugangsdaten,
  Schlüssel (auch öffentliche WireGuard-Schlüssel), Namen fremder Websites
- Rolle: später Staging und Produktion; Einrichtung erst zur Closed Beta,
  Produktion zunächst leer (`docs/HOSTING_UND_BETRIEB.md`)

---

## 1. System und Ressourcen

| Bereich | Inventar |
| --- | --- |
| Virtualisierung | KVM (Hetzner Cloud) |
| Betriebssystem | Ubuntu 26.04.1 LTS |
| Kernel | Linux 7.0.0-43-generic |
| CPU | AMD EPYC-Genoa, 4 vCPUs, 1 Thread pro Kern |
| RAM | 7,6 GiB, davon ca. 7,0 GiB verfügbar (Inventurzeitpunkt) |
| Swap | keiner |
| Systemdisk | 150 GB, ca. 3,6 GB belegt |
| Backup-Disk | 49 GB unter `/backup`, ca. 285 MB belegt |
| Ausstehende Updates | 6 |
| Neustart erforderlich | nein |

Kein Swap ist bei 7,6 GiB aktuell unkritisch, muss aber vor Last- und
Beta-Betrieb bewertet werden (ohne Swap greift bei Speichermangel direkt der
OOM-Killer).

---

## 2. Software

| Baustein | Version / Status |
| --- | --- |
| Apache | 2.4.66, laufend, beim Start aktiviert |
| Apache-Module (relevant) | mpm_event, proxy_fcgi, proxy, proxy_http, proxy_wstunnel, ssl, http2, headers, rewrite, setenvif, status |
| PHP | 8.5.4 CLI und FPM (`php8.5-fpm.service` laufend, aktiviert) |
| PHP-FPM-Pools | nur `www` (Standard) |
| PHP-Erweiterungen | bcmath, ctype, curl, dom, exif, fileinfo, gd, intl, mbstring, mysqli/mysqlnd, openssl, pcntl, PDO, pdo_mysql, posix, redis, sodium, tokenizer, xml, zip, OPcache |
| Node.js | 24.22.3 (Installation unter `/opt/nodejs/`, nicht über apt) |
| npm | 11.19.1, global zusätzlich nur corepack |
| MariaDB | 11.8.6, `bind-address = 127.0.0.1`, aktiviert |
| Redis | 8.0.5, aktiviert, siehe Abschnitt 4 |
| Certbot | 4.0.0 |
| Cockpit | Server-Weboberfläche über systemd-Socket, nur Loopback und VPN |
| phpMyAdmin | eigener Apache-VirtualHost auf Port 8081, nur Loopback und VPN |
| PM2, Docker, Fail2ban | nicht installiert |

Alle von Laravel benötigten PHP-Erweiterungen sind vorhanden. Apache kann
WebSocket-Verbindungen zum Node.js-Spielserver ohne zusätzliche Module
weiterreichen (`proxy_http`, `proxy_wstunnel`).

Empfehlungen für die Einrichtung zur Closed Beta:

- **systemd** statt PM2 für Queue-Worker, Laravel-Scheduler und
  Node.js-Spielserver, getrennte Units je Umgebung.
- **Eigene PHP-FPM-Pools** für Staging und Produktion (eigener Systembenutzer,
  eigene Limits, Trennung von der bestehenden Website).
- Der Laravel-Scheduler läuft lokal mit Sekundenintervallen
  (`schedule:work`); auf dem Server entsprechend als dauerhafter
  systemd-Dienst statt als minütlicher Cron-Eintrag.

---

## 3. Netzwerk und Sicherheit

### Öffentlich erreichbar

- TCP 22: SSH
- TCP 80 und 443: Apache
- UDP 51820: WireGuard

### Nur lokal oder über VPN

- MariaDB `127.0.0.1:3306`
- Redis `127.0.0.1:6379` und `[::1]:6379`
- phpMyAdmin (Apache) Port 8081
- Cockpit Port 9090

### SSH (wirksame Werte)

`passwordauthentication no`, `permitrootlogin no`, `pubkeyauthentication
yes`, `maxauthtries 3`, `x11forwarding no`. Anmeldung nur per Schlüssel.

### WireGuard

Interface `wg0` mit zwei Gegenstellen im privaten Netz `10.77.0.0/24`
(Server `10.77.0.1`). Verwaltungsdienste (Cockpit, phpMyAdmin) sind nur
darüber erreichbar.

### Firewall

UFW ist inaktiv, nftables enthält keine Regeln. Auf dem Server selbst gibt es
damit keine Paketfilterung. Laut Projektinhaber ist eine **Hetzner-Cloud-
Firewall** vorgeschaltet; sie wurde bei der Einrichtung von WireGuard getestet.
Ihre Regeln sind hier nicht dokumentiert. Beim Einrichten von Staging und
Produktion ist zu prüfen, dass sie 80/TCP und 443/TCP zulässt. Keine Firewall-
oder SSH-Änderung ohne separate Analyse und ausdrückliche Bestätigung (Gefahr,
sich auszusperren).

---

## 4. Gemeinsam genutzte Dienste

Auf dem Server läuft bereits **eine andere Website** (eigene VirtualHosts für
HTTP und HTTPS, eigenes Let's-Encrypt-Zertifikat). Für Port 80 und 443 gibt es
jeweils einen **Auffang-VirtualHost** als Standard (`000-default.conf`,
`000-catchall-ssl.conf`, Servername `*.invalid`): Anfragen an unbekannte
Domains landen dort und nicht auf der anderen Website. phpMyAdmin hat einen
eigenen VirtualHost auf Port 8081 (nur Loopback und VPN). Daraus folgt:

- Stechen-MMO bekommt **eigene VirtualHosts** für `<hauptdomain>` und
  `beta.<hauptdomain>`; bestehende Sites werden nicht verändert.
- **MariaDB** wird geteilt: eigene Datenbanken und eigene Benutzer je
  Stechen-Umgebung, ohne Rechte auf fremde Datenbanken.
- **Redis** wird geteilt. Konfiguration: `maxmemory 512M`,
  `maxmemory-policy noeviction`, AOF aktiv (`appendonly yes`) und
  RDB-Snapshots. Folgen für Stechen:
  - eigene Schlüssel-Präfixe beziehungsweise Datenbank-Nummern je Umgebung
  - Cache-Einträge nur mit Ablaufzeit; bei `noeviction` scheitern
    Schreibzugriffe, wenn der Speicher voll ist
  - geeignet für Queue und Session, als Cache nur mit Bedacht
- Die Server-Ressourcen (4 vCPUs, 7,6 GiB RAM) teilen sich alle Dienste.

---

## 5. Backups

Unter `/backup` existieren bereits die Bereiche `mariadb`, `system` und
`exports` (zusammen ca. 285 MB). Die MariaDB wird täglich gegen 02:30 UTC
durch einen eigenen systemd-Timer (`shk-mariadb-backup.timer`) gesichert.
Aufbewahrungsdauer und Umfang (welche Datenbanken) sind noch nicht geprüft.
Stechen-Datenbanken werden später in dieses Schema aufgenommen; zusätzlich ist
mindestens eine Kopie außerhalb des Servers vorgesehen
(`docs/HOSTING_UND_BETRIEB.md`).

Weitere Zeitpläne: Zertifikatserneuerung (`certbot.timer`), Logrotation,
tägliche Paketprüfung und automatische Sicherheitsupdates
(`apt-daily`, `apt-daily-upgrade`, `unattended-upgrades`). Es gibt keinen
Root- oder Benutzer-Crontab.

---

## 6. Eignung für Stechen-MMO

Der Server bringt alle benötigten Laufzeiten mit: Apache mit PHP-FPM 8.5,
MariaDB, Redis mit PHP-Erweiterung, Node.js 24 und Certbot. Wir müssen keine
Grundplattform aufbauen, sondern zur Closed Beta zwei getrennte Umgebungen
neben der bestehenden Website einrichten.

Versionsabgleich mit der lokalen Umgebung: PHP 8.5 und Node 24 sind auf beiden
Seiten dieselbe Hauptversion (Server PHP 8.5.4 / Node 24.22.3, lokal PHP 8.5.12
/ Node 24.11.0). Vor dem ersten Deployment werden Anforderungen und Lockfiles
gegen die Serverversionen geprüft.

---

## 7. Noch offen (vor der Closed Beta)

- Regeln der Hetzner-Cloud-Firewall gegen den Bedarf von Staging und
  Produktion prüfen
- Aufbewahrung und Umfang der vorhandenen MariaDB-Sicherung
- externe Backup-Kopie außerhalb des Servers
