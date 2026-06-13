# HomeServer-Inventar: `kighserv`

**Stand:** 2026-06-13  
**Status:** Inventur nach erster Infrastruktur-Bereinigung  
**Server:** HomeServer `kighserv`  
**Rolle im Projektkontext:** Optionaler HomeServer / möglicher späterer PowerUp-Node  
**Primäre Projektstrategie:** Laravel / ALL-INKL als autoritative Hauptplattform

---

## 1. Zweck dieses Dokuments

Dieses Dokument beschreibt den bekannten Stand des HomeServers `kighserv`.

Es dient als eigenständige Inventur- und Infrastruktur-Dokumentation und soll klar trennen zwischen:

1. vorgefundenem Ausgangszustand,
2. bereits durchgeführten Bereinigungen,
3. aktuellem Ist-Zustand nach Bereinigung,
4. offenen Punkten,
5. späteren projektbezogenen Entscheidungen.

Wichtig: Dieses Dokument beschreibt zunächst den HomeServer selbst.  
Projekt-Deployment, `/srv`-Strukturen oder neue Dienste werden erst nach Abschluss der Inventur und Projektbeschreibung detailliert geplant.

---

## 2. Grunddaten

| Bereich | Wert |
|---|---|
| Hostname | `kighserv` |
| Betriebssystem | Ubuntu 22.04.5 LTS |
| Hardware | Lenovo ideacentre 310S-08ASR |
| Lokale IPv4 | `192.168.178.50` |
| IPv6 | aktiv |
| Firewall | UFW aktiv |
| Administrativer Benutzer | `kighlander` |

---

## 3. Rolle des HomeServers

Der HomeServer ist aktuell **nicht** die autoritative Hauptplattform für `stechen-mmo`.

Die strategische Architektur sieht weiterhin vor:

- **ALL-INKL / Laravel** als Hauptsystem und autoritative Quelle,
- HomeServer `kighserv` optional als:
  - Entwicklungs-/Testsystem,
  - interner Diensteknoten,
  - späterer PowerUp-Node,
  - Experimentier- oder Analyseumgebung.

Zum Stand dieser Inventur wurde **noch keine neue Projektstruktur unter `/srv` angelegt**.

---

## 4. Bekannter Softwarestand

Nach bisheriger Inventur bekannte Kernkomponenten:

| Komponente | Version / Status |
|---|---|
| Apache | 2.4.52, Dienst aktiv |
| PHP | 8.4.22 |
| PHP-FPM | vorhanden, nicht als Apache-Standard aktiviert |
| MariaDB | 10.6.23, Dienst aktiv |
| MongoDB | 7.0.37, Dienst aktiv |
| Node.js | v20.20.2 |
| PM2 | v5.4.3, Altbestand / nicht als Zielbetrieb vorgesehen |
| Webmin | installiert, Dienst deaktiviert/inaktiv |
| UFW | aktiv |

Diese Werte wurden nach Server-Wartung und Reboot am 2026-06-13 teilweise bestätigt beziehungsweise aktualisiert.


Diese Werte sollten im Rahmen der vollständigen Read-Only-Inventur nochmals direkt vom System bestätigt werden.

---

## 4.1 Wartungsstand nach Update am 2026-06-13

Am 2026-06-13 wurde eine Systemwartung durchgeführt.

### Durchgeführt

- `apt update`
- `apt upgrade`
- Aktualisierung von 39 Paketen
- Reboot
- Nachprüfung des Systemzustands

### Ergebnis

- Kernel nach Neustart: `5.15.0-181-generic`
- Kein weiterer Reboot erforderlich
- Keine fehlgeschlagenen systemd-Units
- Alle apt-Pakete aktuell
- SSH-Zugriff über `ssh kighserv` weiterhin funktionsfähig

### Dienststatus nach Reboot

| Dienst | Status |
|---|---|
| `mongod` | active/running |
| `mariadb` | active/running |
| `apache2` | active/running |
| `webmin` | inactive/disabled |

### Offene Ports nach Wartung

| Port | Bindung | Dienst |
|---|---|---|
| 22 | `0.0.0.0`, `[::]` | SSH |
| 80 | `*` | Apache HTTP |
| 3306 | `127.0.0.1` | MariaDB |
| 27017 | `127.0.0.1` | MongoDB |
| 53 | `127.0.0.53` | lokaler DNS-Resolver |

### Bewertung

- Datenbanken sind nur lokal erreichbar.
- SSH ist gehärtet und per Key nutzbar.
- Webmin ist nicht öffentlich offen.
- Der HomeServer ist für spätere Phase-2-Aufgaben vorbereitet.


---

## 5. Speicher / LVM

Bekannter Stand aus bisheriger Inventur:

| Bereich | Status |
|---|---|
| LVM | vorhanden |
| ungenutzter Speicher | ca. 120,5 GiB |
| offene Entscheidung | LVM-Erweiterung vs. Beibehaltung aktueller Aufteilung |

Die genaue Partitionierung, Volume-Gruppen, Logical Volumes und Mountpoints müssen in der finalen Inventur noch per Read-Only-Kommandos dokumentiert werden.

Geplante spätere Prüfbefehle:

```bash
lsblk -f
df -hT
sudo pvs
sudo vgs
sudo lvs
```

---

## 6. Netzwerk

Bekannter Stand:

| Bereich | Wert |
|---|---|
| IPv4 | `192.168.178.50` |
| IPv6 | aktiv |
| Firewall | UFW aktiv |

Noch zu dokumentieren:

- vollständige Interface-Liste,
- Routing,
- DNS-Konfiguration,
- offene Ports,
- UFW-Regeln.

Geplante spätere Prüfbefehle:

```bash
ip -br addr
ip route
resolvectl status
sudo ufw status verbose
ss -tulpn
```

---

## 7. Apache / Webserver

### 7.1 Vorgefundene Altlasten

Im Verlauf der Bereinigung wurden mehrere alte Apache-vHosts und Webroot-Strukturen identifiziert.

Betroffene Alt-vHosts / Konfigurationen:

```text
hhb-kighserv-local
sd-kighlander-de
test-kighserv-local
test2-kighserv-local
kighserv.log.conf
```

Zusätzlich existierte eine veraltete manuelle Include-Referenz in:

```text
/etc/apache2/apache2.conf
```

Diese Referenz zeigte auf nicht mehr passende Legacy-Konfigurationen.

### 7.2 Durchgeführte Bereinigung

Durchgeführt:

```text
alte Apache-vHosts deaktiviert
alte vHost-Konfigurationsdateien entfernt
alte Webroots entfernt
alte Log-Verzeichnisse entfernt
manuelles stale Include in apache2.conf auskommentiert
Apache Reload erfolgreich
Apache final Syntax OK
leere legacy www/www-log Basisordner entfernt
```

Entfernte Legacy-Pfade:

```text
/home/kighlander/www
/home/kighlander/www-log
```

### 7.3 Aktueller bekannter Apache-Stand

Bekannter Stand nach Bereinigung:

```text
Apache configtest: Syntax OK
Apache service: active
sites-enabled: leer
```

Noch zu dokumentieren:

```bash
apache2 -v
apache2ctl -S
apache2ctl configtest
systemctl status apache2 --no-pager -l
ls -la /etc/apache2/sites-available
ls -la /etc/apache2/sites-enabled
```

---

## 8. PHP / PHP-FPM

Bekannter Stand:

```text
PHP 8.4.20
PHP-FPM vorhanden
```

Noch zu dokumentieren:

```bash
php -v
php -m
php-fpm8.4 -v 2>/dev/null || true
systemctl status php8.4-fpm --no-pager -l
ls -la /etc/php
```

Zu klären:

- aktive PHP-Versionen,
- aktive FPM-Pools,
- Apache-Anbindung,
- relevante Extensions,
- CLI-Version vs. FPM-Version.

---

## 9. Datenbanken

### 9.1 MariaDB

Bekannter Stand:

```text
MariaDB 10.6.23
```

Noch zu dokumentieren:

```bash
mariadb --version
systemctl status mariadb --no-pager -l
sudo mysql -e "SHOW DATABASES;"
```

Hinweis: Bei Datenbankinventur keine sensiblen Inhalte, Passwörter oder Dumps in die Dokumentation aufnehmen.

### 9.2 MongoDB

Bekannter Stand:

```text
MongoDB 7.0.31
```

Noch zu dokumentieren:

```bash
mongod --version
systemctl status mongod --no-pager -l
```

Zu klären:

- läuft MongoDB aktiv?
- welche Bind-Adresse?
- lokale Nutzung oder Altlast?
- Authentifizierung aktiv?

---

## 10. Node.js / npm / PM2

### 10.1 Node.js

Bekannter Stand:

```text
Node.js v20.20.2
PM2 v5.4.3
```

Noch zu dokumentieren:

```bash
node -v
npm -v
which node
which npm
pm2 -v
```

### 10.2 User-PM2

Vor Bereinigung:

```text
User-PM2 Daemon aktiv
keine laufenden Prozesse
Warnung: Current process list is not synchronized with saved list
alter gespeicherter stechen-mmo Eintrag im Dump
```

Durchgeführt:

```text
alter User-PM2 Dump gesichert
pm2 save --force mit leerer Prozessliste ausgeführt
Warnung entfernt
```

Aktueller bekannter Stand:

```text
User-PM2 Daemon: aktiv
laufende Prozesse: 0
dump.pm2 entries: 0
```

Backup-Pfad:

```text
/home/kighlander/.pm2/_backup/
```

### 10.3 Root-PM2

Vor Bereinigung:

```text
pm2-root.service: active
pm2-root.service: enabled
verwaltete Prozesse: 0
fehlerhafte/nicht vorhandene Dumps beim Boot
```

Beobachtete Fehler beim Start:

```text
Failed to read dump file in /root/.pm2/dump.pm2
Failed to read dump file in /root/.pm2/dump.pm2.bak
No processes saved
```

Durchgeführt:

```text
pm2-root.service gestoppt
pm2-root.service deaktiviert
versehentlich manuell gestarteter Root-PM2 Daemon beendet
```

Aktueller bekannter Stand:

```text
pm2-root.service: disabled
pm2-root.service: inactive
Root-PM2 Daemon: beendet
```

---

## 11. Webmin

Bekannter Stand:

```text
Webmin aktiv
Port: 10000
Prozess: miniserv.pl
```

Noch zu dokumentieren:

```bash
systemctl status webmin --no-pager -l
ss -tulpn | grep 10000
```

Zu klären:

- Zugriff nur LAN oder extern?
- TLS-Zertifikat?
- Benutzer/Authentifizierung nur dokumentieren, keine Passwörter.

---

## 12. Home-Verzeichnis und Legacy-Projekte

### 12.1 Vorgefundene Legacy-Projekte

Vor Archivierung lagen folgende Projektverzeichnisse direkt unter `/home/kighlander`:

```text
/home/kighlander/Stechen_Karten_MMO
/home/kighlander/stechen-node-server
/home/kighlander/stechengameserver
```

Inventarisierte Größen:

```text
Stechen_Karten_MMO      36M
stechen-node-server     19M
stechengameserver       12K
```

### 12.2 `Stechen_Karten_MMO`

Typ:

```text
Git/Node Legacy-Projekt
```

Git-Status vor Archivierung:

```text
Branch: master...origin/master
lokale Änderungen vorhanden
untracked Dateien vorhanden
```

Geänderter/veränderter Stand:

```text
M server/package-lock.json
M server/package.json
M server/src/index.js
```

Untracked u. a.:

```text
.vscode/
CodeSnippets.txt
server/scripts/
server/src/config/
server/src/controllers/
server/src/data/
server/src/middleware/
server/src/models/
server/src/public/
server/src/routes/
server/src/services/
server/src/socket/
temp.txt
test.txt
workflow_observer.txt
workflow_observer_kanban.txt
```

Remote:

```text
origin https://github.com/Kighlander1975/Stechen_Karten_MMO.git
```

Hinweis: In früheren Schritten wurde ein kompromittierter GitHub Personal Access Token bereinigt und widerrufen. Keine Tokens in die Dokumentation aufnehmen.

### 12.3 `stechen-node-server`

Typ:

```text
Node Legacy-Projekt
kein Git-Repo
```

Bekannte Besonderheit:

```text
enthielt self-signed Zertifikatsdateien myserver.key / myserver.crt
```

### 12.4 `stechengameserver`

Typ:

```text
Node Legacy-Projekt / Altbestand
kein Git-Repo
```

---

## 13. Archivierte Legacy-Projekte

Die Legacy-Projekte wurden verschoben nach:

```text
/home/kighlander/_archive/legacy-home-2026-06-13/
```

Archivinhalt:

```text
/home/kighlander/_archive/legacy-home-2026-06-13/Stechen_Karten_MMO
/home/kighlander/_archive/legacy-home-2026-06-13/stechen-node-server
/home/kighlander/_archive/legacy-home-2026-06-13/stechengameserver
```

Größen nach Archivierung:

```text
12K     /home/kighlander/_archive/legacy-home-2026-06-13/stechengameserver
36M     /home/kighlander/_archive/legacy-home-2026-06-13/Stechen_Karten_MMO
19M     /home/kighlander/_archive/legacy-home-2026-06-13/stechen-node-server
```

Nach der Archivierung waren die drei Legacy-Projektverzeichnisse nicht mehr direkt im Home-Root vorhanden.

---

## 14. Sicherheit / Secrets

### 14.1 GitHub Token

Im Rahmen der Bereinigung wurde ein kompromittierter GitHub Personal Access Token behandelt.

Durchgeführt:

```text
kompromittierter PAT widerrufen
Git-Remotes bereinigt
PM2-Dumps bereinigt
```

Dokumentationsregel:

```text
Keine Tokens, Passwörter, privaten Schlüssel oder sonstigen Secrets in dieses Dokument aufnehmen.
```

### 14.2 Zertifikate / Schlüssel

Bekannter Altbestand:

```text
/home/kighlander/_archive/legacy-home-2026-06-13/stechen-node-server/
```

enthielt früher:

```text
myserver.key
myserver.crt
```

Zu klären:

- Sind diese Dateien noch vorhanden?
- Handelt es sich ausschließlich um alte self-signed Testzertifikate?
- Müssen sie separat entfernt, rotiert oder nur archiviert markiert werden?

Keine privaten Schlüssel im Klartext dokumentieren.

---

## 15. Durchgeführte Bereinigungen am 2026-06-13

### 15.1 Apache / Webroot

```text
alte Apache-vHosts deaktiviert
alte vHost-Konfigurationen entfernt
stale Include in apache2.conf auskommentiert
alte Webroots entfernt
alte Webroot-Logs entfernt
/home/kighlander/www entfernt
/home/kighlander/www-log entfernt
Apache Syntax OK geprüft
Apache Dienst aktiv geprüft
```

### 15.2 PM2

```text
User-PM2 geprüft
User-PM2 Dump gesichert
User-PM2 mit leerer Prozessliste gespeichert
Root-PM2 Service gestoppt
Root-PM2 Service deaktiviert
Root-PM2 Daemon beendet
```

### 15.3 Legacy-Projekte

```text
Legacy-Projekte inventarisiert
Legacy-Projekte nach /home/kighlander/_archive/legacy-home-2026-06-13/ verschoben
Home-Root bereinigt
```

---

## 16. Explizit noch NICHT durchgeführt

Wichtig zur Abgrenzung:

```text
/srv/apps/stechen-mmo wurde NICHT angelegt
/srv/backups/stechen-mmo wurde NICHT angelegt
/srv/scripts/stechen-mmo wurde NICHT angelegt
keine neue Deployment-Struktur erstellt
kein neues Projekt auf dem Server eingerichtet
keine neuen systemd-Services für stechen-mmo eingerichtet
keine neuen PM2-Prozesse für stechen-mmo eingerichtet
keine neue Apache-Site für stechen-mmo eingerichtet
keine neuen Datenbanken für stechen-mmo eingerichtet
```

---

## 17. Offene Inventurpunkte

Die folgenden Bereiche müssen noch vollständig per Read-Only-Inventur ergänzt werden.

### 17.1 System / OS / Hardware

```bash
hostnamectl
lsb_release -a 2>/dev/null || cat /etc/os-release
uname -a
date
timedatectl
uptime
who -b 2>/dev/null || true
sudo dmidecode -t system 2>/dev/null || true
lscpu
free -h
```

### 17.2 Netzwerk

```bash
ip -br addr
ip route
resolvectl status
ss -tulpn
```

### 17.3 Firewall

```bash
sudo ufw status verbose
sudo ufw app list
```

### 17.4 Speicher / LVM

```bash
lsblk -f
df -hT
sudo pvs
sudo vgs
sudo lvs
sudo fdisk -l
```

### 17.5 Benutzer / Gruppen

```bash
id kighlander
groups kighlander
getent passwd kighlander
getent group www-data
sudo -l
```

### 17.6 Apache

```bash
apache2 -v
apache2ctl -S
apache2ctl configtest
systemctl status apache2 --no-pager -l
ls -la /etc/apache2/sites-available
ls -la /etc/apache2/sites-enabled
```

### 17.7 PHP

```bash
php -v
php -m
php-fpm8.4 -v 2>/dev/null || true
systemctl status php8.4-fpm --no-pager -l
ls -la /etc/php
```

### 17.8 Datenbanken

```bash
mariadb --version
systemctl status mariadb --no-pager -l
mongod --version
systemctl status mongod --no-pager -l
```

### 17.9 Node / PM2

```bash
node -v
npm -v
pm2 -v
pm2 status
pm2 jlist
systemctl status pm2-root --no-pager -l 2>/dev/null || true
```

### 17.10 Webmin

```bash
systemctl status webmin --no-pager -l
ss -tulpn | grep 10000
```

### 17.11 Home / Archive

```bash
ls -la /home/kighlander
find /home/kighlander/_archive -maxdepth 3 -type d -print
du -sh /home/kighlander/_archive/* 2>/dev/null || true
```

---

## 18. Offene Entscheidungen

| Thema | Entscheidung offen |
|---|---|
| LVM | Speicher erweitern oder unverändert lassen |
| HomeServer-Rolle | reiner Inventur-/Archivstand vs. späterer PowerUp-Node |
| Legacy-Projekte | nur archivieren oder später auswerten/migrieren |
| alte Zertifikate | löschen, rotieren oder als ungefährliche Testzertifikate archivieren |
| Webmin | dauerhaft behalten, einschränken oder entfernen |
| MongoDB | aktiv benötigt oder Altlast |
| MariaDB | aktiv benötigt oder Altlast |
| Apache | Standard-Webserver behalten oder später gezielt neu konfigurieren |
| PM2 User-Daemon | dauerhaft behalten oder später ebenfalls deaktivieren |
| `/srv`-Struktur | erst nach Inventur und Projektbeschreibung anlegen |

---

## 19. Nächste empfohlene Schritte

Aktueller Arbeitsmodus:

```text
Inventur / Dokumentation
keine neuen Projektstrukturen
keine Deployments
keine Service-Änderungen
keine weiteren Bereinigungen ohne explizite Freigabe
```

Empfohlene Reihenfolge:

1. vollständige Read-Only-Inventur durchführen,
2. dieses Dokument mit echten Systemausgaben ergänzen,
3. Projektbeschreibung und Zielarchitektur detaillieren,
4. erst danach Serverstruktur und mögliche Deployments planen,
5. anschließend konkrete Umsetzungsschritte definieren.

---

## 20. Änderungsverlauf

| Datum | Änderung |
|---|---|
| 2026-06-13 | Erstfassung der HomeServer-Inventur nach Apache-/PM2-/Legacy-Bereinigung erstellt |

---

## 21. Verifizierte Inventurdaten: Block 1 - System / OS / Hardware

**Erfasst am:** 2026-06-13, ca. 17:50 CEST  
**Quelle:** Read-Only-Inventur auf HomeServer `kighserv`

### 21.1 Host / Betriebssystem

| Feld | Wert |
|---|---|
| Static hostname | `kighserv` |
| Chassis | `desktop` |
| Betriebssystem | Ubuntu 22.04.5 LTS |
| Release | 22.04 |
| Codename | jammy |
| Kernel | Linux 5.15.0-179-generic |
| Architektur | x86-64 |
| Hardware Vendor | Lenovo |
| Hardware Model | ideacentre 310S-08ASR |

Kernel-Detail:

```text
Linux kighserv 5.15.0-179-generic #189-Ubuntu SMP Tue May 5 18:20:56 UTC 2026 x86_64 x86_64 x86_64 GNU/Linux
```

### 21.2 Zeit / NTP / Boot

| Feld | Wert |
|---|---|
| Lokale Zeit bei Inventur | Sa 2026-06-13 17:50:32 CEST |
| Zeitzone | Europe/Berlin |
| UTC Offset | +0200 |
| System clock synchronized | yes |
| NTP service | active |
| RTC in local TZ | no |
| Uptime bei Inventur | ca. 3 Stunden 24 Minuten |
| letzter Systemboot | 2026-06-13 14:26 |

### 21.3 Hardware

| Feld | Wert |
|---|---|
| Hersteller | LENOVO |
| Produktname | 90G9007VGE |
| Modell / Version | ideacentre 310S-08ASR |
| Gerätefamilie | ideacentre 310S-08ASR |
| SMBIOS | 3.0.0 |
| DMI Boot Status | No errors detected |

Hinweis: Seriennummer und Hardware-UUID wurden aus Datenschutz-/Inventarsicherheitsgründen nicht in diese Dokumentation übernommen.

### 21.4 CPU

| Feld | Wert |
|---|---|
| Architektur | x86_64 |
| CPU | AMD A9-9425 RADEON R5, 5 COMPUTE CORES 2C+3G |
| CPU-Kerne logisch | 2 |
| Sockets | 1 |
| Kerne pro Socket | 2 |
| Threads pro Kern | 1 |
| Virtualisierung | AMD-V |
| CPU min MHz | 1400 |
| CPU max MHz | 3100 |
| NUMA Nodes | 1 |

Kurzbewertung:

```text
Kleiner Dual-Core-HomeServer mit AMD-V-Unterstützung.
Geeignet für leichte Dienste, Inventur, Entwicklung, kleinere Node-/PHP-/DB-Testworkloads.
Für rechenintensive parallele Game-Server-Workloads nur eingeschränkt geeignet.
```

### 21.5 Arbeitsspeicher

| Feld | Wert |
|---|---|
| RAM gesamt | 15 GiB |
| RAM verwendet bei Inventur | 506 MiB |
| RAM frei bei Inventur | 9,3 GiB |
| Buffer/Cache | 5,3 GiB |
| verfügbar | 14 GiB |
| Swap gesamt | 4,0 GiB |
| Swap verwendet | 0 B |

Kurzbewertung:

```text
RAM-Situation zum Inventurzeitpunkt sehr entspannt.
Für Webserver-, Datenbank- und Entwicklungsdienste ausreichend Reserven vorhanden.
```

### 21.6 Blockgeräte Kurzüberblick

| Gerät | Größe | Typ | Dateisystem | Mountpoint | Modell |
|---|---:|---|---|---|---|
| `sda` | 223,6G | disk | - | - | SanDisk SSD PLUS |
| `sda1` | 1G | part | vfat | `/boot/efi` | - |
| `sda2` | 2G | part | ext4 | `/boot` | - |
| `sda3` | 220,5G | part | LVM2_member | - | - |
| `ubuntu--vg-ubuntu--lv` | 100G | lvm | ext4 | `/` | - |

Zusätzlich vorhanden sind mehrere Snap-Loopdevices:

```text
/snap/core20
/snap/lxd
/snap/snapd
```

### 21.7 Speicherbewertung aus Block 1

Die SSD hat eine Gesamtkapazität von ca. 223,6G.  
Davon liegt der Hauptanteil in `sda3` als LVM Physical Volume.

Aktuell gemountetes Root-Logical-Volume:

```text
/dev/mapper/ubuntu--vg-ubuntu--lv -> /, 100G, ext4
```

Daraus ergibt sich weiterhin die bekannte Beobachtung:

```text
Es existiert voraussichtlich ungenutzter LVM-Spielraum innerhalb der Volume Group.
Die genaue freie Kapazität muss in Block 4 mit pvs/vgs/lvs verifiziert werden.
```

### 21.8 Block-1-Zwischenfazit

Der HomeServer `kighserv` ist ein Lenovo ideacentre 310S-08ASR mit Ubuntu 22.04.5 LTS, 15 GiB RAM, einer 223,6G SanDisk SSD und einem kleinen AMD-Dual-Core-Prozessor.

Der Systemzustand zum Zeitpunkt der Inventur:

```text
System läuft stabil
NTP aktiv und synchronisiert
Boot ohne DMI-Fehler gemeldet
RAM-Auslastung niedrig
Swap ungenutzt
Root-Dateisystem liegt auf LVM
```

Für die weitere Inventur sind insbesondere noch relevant:

```text
Netzwerk / offene Ports
UFW-Regeln
vollständige LVM-Auswertung
laufende Dienste
Apache/PHP/MariaDB/MongoDB/Node/PM2/Webmin
Home-Verzeichnis und Archivstand
```

---

## 22. Verifizierte Inventurdaten: Block 2 - Netzwerk / DNS / Routing / Ports

**Erfasst am:** 2026-06-13  
**Quelle:** Read-Only-Inventur auf HomeServer `kighserv`

### 22.1 Netzwerkinterfaces

| Interface | Status | Adressen / Hinweise |
|---|---|---|
| `lo` | UNKNOWN | `127.0.0.1/8`, `::1/128` |
| `enp2s0` | UP | primäres Ethernet-Interface |
| `wlp1s0` | DOWN | WLAN-Interface vorhanden, aktuell nicht aktiv |

Adressen auf `enp2s0`:

```text
IPv4: 192.168.178.50/24
IPv6 ULA: fd24:e65a:3326:0:2d8:61ff:fe1c:189d/64
IPv6 global: 2a02:3100:4145:cf00:2d8:61ff:fe1c:189d/64
IPv6 link-local: fe80::2d8:61ff:fe1c:189d/64
```

Kurzbewertung:

```text
Der Server ist per Ethernet aktiv im LAN eingebunden.
IPv4 wird per DHCP vergeben.
IPv6 ist aktiv, inklusive globaler IPv6-Adresse.
WLAN ist vorhanden, aber nicht aktiv.
```

### 22.2 IPv4 Routing

```text
default via 192.168.178.1 dev enp2s0 proto dhcp src 192.168.178.50 metric 100
192.168.178.0/24 dev enp2s0 proto kernel scope link src 192.168.178.50 metric 100
192.168.178.1 dev enp2s0 proto dhcp scope link src 192.168.178.50 metric 100
```

Bewertung:

```text
Standardgateway ist 192.168.178.1, vermutlich FritzBox.
IPv4-Konfiguration erfolgt über DHCP.
```

### 22.3 IPv6 Routing

Auszug:

```text
default via fe80::1eed:6fff:fe27:19f5 dev enp2s0 proto ra metric 100 mtu 1492
2a02:3100:4145:cf00::/64 dev enp2s0 proto ra metric 100
fd24:e65a:3326::/64 dev enp2s0 proto ra metric 100
fe80::/64 dev enp2s0 proto kernel metric 256
```

Bewertung:

```text
IPv6 wird über Router Advertisements konfiguriert.
Der Server besitzt eine globale IPv6-Adresse.
Für spätere Sicherheitsbewertung muss IPv6-Firewall/Erreichbarkeit explizit berücksichtigt werden.
```

### 22.4 DNS

`resolvectl` meldet:

| Feld | Wert |
|---|---|
| resolv.conf mode | stub |
| DNS Server IPv4 | `192.168.178.1` |
| DNS Server IPv6 global | `2a02:3100:4145:cf00:1eed:6fff:fe27:19f5` |
| DNS Server IPv6 ULA | `fd24:e65a:3326:0:1eed:6fff:fe27:19f5` |
| DNS Domain | `fritz.box` |
| DNSSEC | no/unsupported |
| DNSOverTLS | deaktiviert |
| mDNS | deaktiviert |
| LLMNR | aktiv auf Link `enp2s0` |

Bewertung:

```text
DNS läuft über die lokale FritzBox.
Die lokale Suchdomain ist fritz.box.
```

### 22.5 Hostname / Hosts

`/etc/hostname`:

```text
kighserv
```

`/etc/hosts`:

```text
127.0.0.1 localhost
127.0.1.1 kighserv

::1     ip6-localhost ip6-loopback
fe00::0 ip6-localnet
ff00::0 ip6-mcastprefix
ff02::1 ip6-allnodes
ff02::2 ip6-allrouters
```

Bewertung:

```text
Hostname ist konsistent auf kighserv gesetzt.
Keine projektspezifischen Host-Mappings dokumentiert.
```

### 22.6 Netplan-Konfiguration

Vorhandene Dateien:

```text
/etc/netplan/00-installer-config-wifi.yaml
/etc/netplan/00-installer-config.yaml
```

`00-installer-config.yaml`:

```yaml
network:
  ethernets:
    enp2s0:
      dhcp4: true
  version: 2
```

`00-installer-config-wifi.yaml`:

```yaml
network:
  version: 2
  wifis: {}
```

Bewertung:

```text
Netzwerk wird über Netplan konfiguriert.
Ethernet enp2s0 nutzt DHCP für IPv4.
WLAN ist in Netplan leer konfiguriert und aktuell nicht aktiv.
```

### 22.7 Lauschende Dienste / Ports

Aus `sudo ss -tulpn` wurden folgende relevante Listener erfasst:

| Protokoll | Adresse | Port | Dienst / Prozess | Bewertung |
|---|---|---:|---|---|
| TCP | `0.0.0.0` | 22 | `sshd` | SSH auf IPv4 |
| TCP | `[::]` | 22 | `sshd` | SSH auf IPv6 |
| TCP | `*` | 21 | `vsftpd` | FTP aktiv |
| TCP | `*` | 80 | `apache2` | Apache HTTP aktiv |
| TCP | `0.0.0.0` | 10000 | `miniserv.pl` | Webmin IPv4 |
| TCP | `[::]` | 10000 | `miniserv.pl` | Webmin IPv6 |
| UDP | `0.0.0.0` | 10000 | `miniserv.pl` | Webmin UDP |
| TCP | `0.0.0.0` | 139 | `smbd` | Samba NetBIOS/SMB IPv4 |
| TCP | `[::]` | 139 | `smbd` | Samba NetBIOS/SMB IPv6 |
| TCP | `0.0.0.0` | 445 | `smbd` | Samba SMB IPv4 |
| TCP | `[::]` | 445 | `smbd` | Samba SMB IPv6 |
| UDP | `0.0.0.0` / LAN-Adressen | 137 | `nmbd` | NetBIOS Name Service |
| UDP | `0.0.0.0` / LAN-Adressen | 138 | `nmbd` | NetBIOS Datagram Service |
| TCP | `127.0.0.1` | 3306 | `mariadbd` | MariaDB nur lokal |
| TCP | `127.0.0.1` | 27017 | `mongod` | MongoDB nur lokal |
| TCP | `127.0.0.53` | 53 | `systemd-resolve` | lokaler DNS Stub |
| UDP | `127.0.0.53` | 53 | `systemd-resolve` | lokaler DNS Stub |
| UDP | `192.168.178.50` | 68 | `systemd-networkd` | DHCP Client |
| UDP | IPv6 link-local | 546 | `systemd-networkd` | DHCPv6 Client |

### 22.8 Port-/Dienstbewertung aus Block 2

Positive Beobachtungen:

```text
MariaDB lauscht nur auf 127.0.0.1:3306.
MongoDB lauscht nur auf 127.0.0.1:27017.
Damit sind beide Datenbanken nach aktuellem Listener-Stand nicht direkt im LAN/WAN exponiert.
```

Zu bewertende offene Dienste:

```text
SSH ist auf IPv4 und IPv6 aktiv.
Apache HTTP ist auf Port 80 aktiv.
Webmin ist auf IPv4 und IPv6 Port 10000 aktiv.
FTP/vsftpd ist auf Port 21 aktiv.
Samba/NetBIOS ist auf IPv4 und IPv6 aktiv.
```

Wichtige Sicherheitsnotiz:

```text
Da der Server eine globale IPv6-Adresse besitzt, muss die tatsächliche externe Erreichbarkeit nicht nur über IPv4/NAT, sondern auch über IPv6 und UFW/Router-Firewall bewertet werden.
```

Noch keine Maßnahme im Rahmen dieses Blocks:

```text
Keine Ports geschlossen.
Keine Dienste gestoppt.
Keine Firewall-Regeln geändert.
Dieser Block war rein lesend.
```

### 22.9 Block-2-Zwischenfazit

Der HomeServer `kighserv` ist aktuell ein per DHCP eingebundener LAN-Server mit aktiver IPv4- und IPv6-Konnektivität.

Die wichtigsten aktiven Netzwerkdienste sind:

```text
SSH
Apache HTTP
Webmin
FTP/vsftpd
Samba/SMB
MariaDB lokal
MongoDB lokal
systemd-resolved
```

Für die weitere Inventur ist als nächstes besonders wichtig:

```text
UFW-Regeln vollständig erfassen
prüfen, welche Ports absichtlich erlaubt sind
bewerten, ob FTP/Samba/Webmin dauerhaft benötigt werden
IPv6-Erreichbarkeit gesondert berücksichtigen
```

---

## 23. Verifizierte Inventurdaten: Block 3 - Firewall / UFW

**Erfasst am:** 2026-06-13  
**Quelle:** Read-Only-Inventur auf HomeServer `kighserv`

### 23.1 UFW-Grundstatus

`sudo ufw status verbose` meldet:

```text
Status: active
Logging: on (low)
Default: deny (incoming), allow (outgoing), disabled (routed)
New profiles: skip
```

Bewertung:

```text
UFW ist aktiv.
Eingehende Verbindungen werden standardmäßig verweigert.
Ausgehende Verbindungen sind standardmäßig erlaubt.
Routing/Forwarding ist deaktiviert.
Logging ist auf low aktiv.
```

### 23.2 UFW Application Profiles

Verfügbare UFW-Profile:

```text
Apache
Apache Full
Apache Secure
OpenSSH
Samba
```

### 23.3 Nummerierte UFW-Regeln

Aktueller Stand laut `sudo ufw status numbered`:

```text
[ 1] 53                         ALLOW IN    Anywhere
[ 2] 80/tcp                     ALLOW IN    Anywhere
[ 3] 21                         ALLOW IN    192.168.1.0/24
[ 4] 80                         ALLOW IN    192.168.1.0/24
[ 5] 22/tcp                     ALLOW IN    Anywhere
[ 6] 3000                       ALLOW IN    Anywhere
[ 7] Samba                      ALLOW IN    Anywhere
[ 8] 5000                       ALLOW IN    Anywhere
[ 9] 3001                       ALLOW IN    Anywhere
[10] 3456/tcp                   ALLOW IN    Anywhere
[11] 53 (v6)                    ALLOW IN    Anywhere (v6)
[12] 80/tcp (v6)                ALLOW IN    Anywhere (v6)
[13] 22/tcp (v6)                ALLOW IN    Anywhere (v6)
[14] 3000 (v6)                  ALLOW IN    Anywhere (v6)
[15] Samba (v6)                 ALLOW IN    Anywhere (v6)
[16] 5000 (v6)                  ALLOW IN    Anywhere (v6)
[17] 3001 (v6)                  ALLOW IN    Anywhere (v6)
[18] 3456/tcp (v6)              ALLOW IN    Anywhere (v6)
```

### 23.4 Zusammenfassung erlaubter eingehender Ports

| Port / Dienst | IPv4-Regel | IPv6-Regel | Bewertung |
|---|---|---|---|
| `53` | Anywhere | Anywhere (v6) | DNS-Port erlaubt, tatsächlicher Listener nur lokal durch `systemd-resolve` |
| `80/tcp` | Anywhere | Anywhere (v6) | Apache HTTP global erlaubt |
| `22/tcp` | Anywhere | Anywhere (v6) | SSH global erlaubt |
| `3000` | Anywhere | Anywhere (v6) | vermutlich alte Node-/Entwicklungsregel |
| `3001` | Anywhere | Anywhere (v6) | vermutlich alte Node-/Entwicklungsregel |
| `5000` | Anywhere | Anywhere (v6) | vermutlich alte Entwicklungs-/Service-Regel |
| `3456/tcp` | Anywhere | Anywhere (v6) | vermutlich alte Projekt-/Testregel |
| Samba | Anywhere | Anywhere (v6) | SMB/NetBIOS global erlaubt |
| `21` | nur `192.168.1.0/24` | keine v6-Regel sichtbar | FTP-Regel passt nicht zum aktuellen LAN |
| `80` | zusätzlich `192.168.1.0/24` | - | redundante/alte HTTP-Regel |

### 23.5 Auffälligkeit: falsches altes LAN-Netz

Aktuelles LAN laut Block 2:

```text
192.168.178.0/24
```

Vorhandene UFW-Regeln:

```text
21 ALLOW IN 192.168.1.0/24
80 ALLOW IN 192.168.1.0/24
```

Bewertung:

```text
Die Regeln für 192.168.1.0/24 passen nicht zum aktuellen LAN 192.168.178.0/24.
Diese Regeln sind wahrscheinlich Altlasten aus einer früheren Netzkonfiguration.
```

Noch keine Maßnahme:

```text
Die Regeln wurden im Rahmen der Inventur nicht geändert.
```

### 23.6 Vergleich Listener vs. UFW

Aus Block 2 bekannte Listener und passende UFW-Sicht:

| Dienst | Listener laut `ss` | UFW-Regel | Bewertung |
|---|---|---|---|
| SSH | `0.0.0.0:22`, `[::]:22` | erlaubt für Anywhere/v6 | erreichbar gemäß Firewallregel |
| Apache | `*:80` | erlaubt für Anywhere/v6 | erreichbar gemäß Firewallregel |
| Webmin | `0.0.0.0:10000`, `[::]:10000`, UDP `0.0.0.0:10000` | keine explizite UFW-Allow-Regel sichtbar | Listener aktiv, aber UFW blockt vermutlich extern |
| FTP/vsftpd | `*:21` | nur `192.168.1.0/24` | passt nicht zum aktuellen LAN, daher vermutlich blockiert aus `192.168.178.0/24` |
| Samba | `0.0.0.0:139/445`, `[::]:139/445`, UDP `137/138` | erlaubt für Anywhere/v6 | weit geöffnet gemäß Firewallregel |
| MariaDB | `127.0.0.1:3306` | keine externe Regel nötig | nur lokal gebunden |
| MongoDB | `127.0.0.1:27017` | keine externe Regel nötig | nur lokal gebunden |
| DNS Stub | `127.0.0.53:53` | Port 53 erlaubt | Dienst lauscht nur lokal, Regel wirkt aktuell unnötig weit |

### 23.7 Raw-/iptables-Bewertung

`iptables -S` und `ip6tables -S` bestätigen:

```text
IPv4 INPUT policy: DROP
IPv4 FORWARD policy: DROP
IPv4 OUTPUT policy: ACCEPT

IPv6 INPUT policy: DROP
IPv6 FORWARD policy: DROP
IPv6 OUTPUT policy: ACCEPT
```

Bewertung:

```text
Die Kernel-Firewall-Regeln entsprechen dem aktiven UFW-Modell.
Eingehend wird grundsätzlich gedroppt, außer UFW-Regeln erlauben explizit.
IPv6 wird ebenfalls durch UFW-Regelketten behandelt.
```

### 23.8 Sicherheitsrelevante Beobachtungen

Wichtige Punkte für spätere Bewertung:

```text
SSH ist für IPv4 und IPv6 von Anywhere erlaubt.
Apache HTTP ist für IPv4 und IPv6 von Anywhere erlaubt.
Samba ist für IPv4 und IPv6 von Anywhere erlaubt.
Mehrere alte Entwicklungsports sind für IPv4 und IPv6 von Anywhere erlaubt: 3000, 3001, 5000, 3456.
Port 53 ist für IPv4 und IPv6 von Anywhere erlaubt, obwohl aktuell nur ein lokaler DNS-Stub sichtbar ist.
Webmin lauscht zwar auf Port 10000, ist aber in UFW nicht explizit erlaubt.
FTP lauscht auf Port 21, ist aber nur für ein altes Netz 192.168.1.0/24 erlaubt.
```

### 23.9 Block-3-Zwischenfazit

UFW ist aktiv und grundsätzlich restriktiv konfiguriert, enthält aber mehrere vermutlich historisch gewachsene oder projektbezogene Allow-Regeln.

Besonders auffällig:

```text
alte LAN-Regeln für 192.168.1.0/24
offene Entwicklungsports 3000/3001/5000/3456
Samba für Anywhere und Anywhere(v6)
SSH für Anywhere und Anywhere(v6)
HTTP für Anywhere und Anywhere(v6)
Port 53 für Anywhere und Anywhere(v6)
```

Noch keine Änderung durchgeführt:

```text
Keine UFW-Regeln entfernt.
Keine UFW-Regeln hinzugefügt.
Keine Dienste gestoppt.
Dieser Block war rein lesend.
```

Empfohlene spätere Folgeaufgabe nach Abschluss der Inventur:

```text
Firewall-Regeln gegen tatsächliche Zielrolle des HomeServers prüfen.
Nicht benötigte Altregeln entfernen.
IPv6-Erreichbarkeit explizit bewerten.
Samba, FTP, Webmin und alte Entwicklungsports gesondert entscheiden.
```

---

## 24. Verifizierte Inventurdaten: Block 4 - Speicher / Dateisysteme / LVM

**Erfasst am:** 2026-06-13  
**Quelle:** Read-Only-Inventur auf HomeServer `kighserv`

### 24.1 Physisches Laufwerk

Erkanntes Hauptlaufwerk:

| Gerät | Größe | Typ | Modell |
|---|---:|---|---|
| `sda` | 223,6G | disk | SanDisk SSD PLUS |

Partitionierung:

| Gerät | Größe | Typ | Dateisystem / Rolle | Mountpoint |
|---|---:|---|---|---|
| `sda1` | 1G | part | vfat / FAT32 | `/boot/efi` |
| `sda2` | 2G | part | ext4 | `/boot` |
| `sda3` | 220,5G | part | LVM2_member | Physical Volume für LVM |

Hinweis:

```text
UUIDs wurden in der zusammenfassenden Dokumentation bewusst nicht vollständig übernommen.
Sie sind in der Live-Ausgabe vorhanden, werden hier aber nicht für die Architekturentscheidung benötigt.
```

### 24.2 Dateisysteme und Mountpoints

Aus `df -hT`:

| Filesystem | Typ | Größe | Genutzt | Frei | Nutzung | Mountpoint |
|---|---|---:|---:|---:|---:|---|
| `/dev/mapper/ubuntu--vg-ubuntu--lv` | ext4 | 98G | 20G | 74G | 22% | `/` |
| `/dev/sda2` | ext4 | 2,0G | 260M | 1,6G | 15% | `/boot` |
| `/dev/sda1` | vfat | 1,1G | 6,1M | 1,1G | 1% | `/boot/efi` |
| `tmpfs` | tmpfs | 1,6G | 2,8M | 1,6G | 1% | `/run` |
| `tmpfs` | tmpfs | 7,6G | 0 | 7,6G | 0% | `/dev/shm` |
| `tmpfs` | tmpfs | 5,0M | 0 | 5,0M | 0% | `/run/lock` |
| `tmpfs` | tmpfs | 1,6G | 4,0K | 1,6G | 1% | `/run/user/1000` |

Bewertung:

```text
Das Root-Dateisystem ist nur moderat belegt.
Zum Inventurzeitpunkt waren ca. 74G auf / frei.
Boot- und EFI-Partition sind unauffällig.
```

### 24.3 LVM Physical Volume

Aus `sudo pvs`:

```text
PV         VG        Fmt  Attr PSize   PFree
/dev/sda3  ubuntu-vg lvm2 a--  220,52g 120,52g
```

Bewertung:

```text
/dev/sda3 ist ein LVM Physical Volume.
Es gehört zur Volume Group ubuntu-vg.
Von 220,52 GiB sind 120,52 GiB frei.
```

### 24.4 LVM Volume Group

Aus `sudo vgs` und `sudo vgdisplay`:

| Feld | Wert |
|---|---:|
| VG Name | `ubuntu-vg` |
| Physical Volumes | 1 |
| Logical Volumes | 1 |
| VG Size | 220,52 GiB |
| Allocated | 100,00 GiB |
| Free | 120,52 GiB |
| VG Status | resizable |
| PE Size | 4,00 MiB |
| Total PE | 56454 |
| Allocated PE | 25600 |
| Free PE | 30854 |

Bewertung:

```text
Die Volume Group ubuntu-vg ist erweiterbar/resizable.
Es existiert erheblicher freier LVM-Spielraum von 120,52 GiB.
```

### 24.5 LVM Logical Volume

Aus `sudo lvs` und `sudo lvdisplay`:

| Feld | Wert |
|---|---|
| LV Path | `/dev/ubuntu-vg/ubuntu-lv` |
| LV Name | `ubuntu-lv` |
| VG Name | `ubuntu-vg` |
| LV Size | 100,00 GiB |
| LV Status | available |
| Open LV | 1 |
| Dateisystem | ext4 |
| Mountpoint | `/` |
| Erstellung | 2024-07-12 11:04:01 +0200 |
| Creation Host | ubuntu-server |

Bewertung:

```text
Das Root-Dateisystem liegt auf dem Logical Volume ubuntu-lv.
Das LV ist 100,00 GiB groß und aktuell aktiv gemountet.
```

### 24.6 Snap Loop Devices

Erkannte Snap-Loopdevices:

```text
/snap/core20/2866
/snap/core20/2769
/snap/lxd/38800
/snap/lxd/38469
/snap/snapd/26865
/snap/snapd/26382
```

Bewertung:

```text
Die Loopdevices sind normale Snap-Mounts.
Sie werden als 100% genutzt angezeigt, was bei squashfs-Snap-Images normal ist.
```

### 24.7 Fstab

Relevante Einträge aus `/etc/fstab`:

```text
/          ext4  defaults  0 1
/boot      ext4  defaults  0 1
/boot/efi  vfat  defaults  0 1
/swap.img  swap  sw        0 0
```

Original verwendet robuste Gerätepfade über `/dev/disk/by-id/...` und `/dev/disk/by-uuid/...`.

Bewertung:

```text
fstab enthält die erwarteten Einträge für Root, Boot, EFI und Swap-Datei.
Keine zusätzlichen Projekt- oder Datenmounts dokumentiert.
Keine separaten Mountpoints für /srv, /home, Datenbanken oder Backups vorhanden.
```

### 24.8 Swap

Aus `/proc/swaps`:

| Datei | Typ | Größe | Genutzt | Priorität |
|---|---|---:|---:|---:|
| `/swap.img` | file | 4194300 KiB | 0 KiB | -2 |

Bewertung:

```text
Swap ist als Datei /swap.img mit ca. 4,0 GiB eingerichtet.
Zum Inventurzeitpunkt war Swap ungenutzt.
```

### 24.9 Speicherbewertung

Bestätigter Speicherstand:

```text
Physische SSD: 223,6G
LVM Physical Volume: 220,52 GiB
Volume Group ubuntu-vg: 220,52 GiB
Root Logical Volume: 100,00 GiB
Freier LVM-Bereich: 120,52 GiB
Root-Dateisystem frei: ca. 74G
Swap: 4,0G Datei, ungenutzt
```

### 24.10 Offene Speicherentscheidung

Die Inventur bestätigt, dass ca. 120,52 GiB innerhalb der Volume Group frei sind.

Mögliche spätere Optionen:

```text
Option A: Root-LV vergrößern
Option B: separates LV für /srv anlegen
Option C: separates LV für Backups/Daten anlegen
Option D: Speicher zunächst ungenutzt lassen
```

Noch keine Maßnahme im Rahmen dieses Blocks:

```text
Kein LV erweitert.
Kein neues LV erstellt.
Keine Partition verändert.
Keine fstab geändert.
Keine Mountpoints angelegt.
Dieser Block war rein lesend.
```

### 24.11 Block-4-Zwischenfazit

Der HomeServer nutzt eine einfache LVM-Struktur:

```text
/dev/sda3 -> ubuntu-vg -> ubuntu-lv -> /
```

Die aktuelle Root-Partition ist mit 100 GiB dimensioniert und nur zu ca. 22% belegt.  
Der freie LVM-Spielraum von 120,52 GiB ist ein wichtiger späterer Planungspunkt, sollte aber erst nach Abschluss von Inventur und Projektbeschreibung entschieden werden.

Für die weitere Inventur sind als nächstes relevant:

```text
Benutzer / Gruppen / sudo
laufende Dienste
Apache/PHP
Datenbanken
Node/PM2
Webmin/Samba/FTP
Home-Verzeichnis und Archivstand
```

---

## 25. Verifizierte Inventurdaten: Block 5 - Benutzer / Gruppen / sudo

**Erfasst am:** 2026-06-13  
**Quelle:** Read-Only-Inventur auf HomeServer `kighserv`

### 25.1 Aktueller Benutzer

Aktueller Benutzer während der Inventur:

```text
kighlander
```

`id`:

```text
uid=1000(kighlander) gid=1000(kighlander) groups=1000(kighlander),4(adm),24(cdrom),27(sudo),30(dip),46(plugdev),110(lxd)
```

Bewertung:

```text
Die Inventur wurde als Benutzer kighlander durchgeführt.
Der Benutzer besitzt Mitgliedschaft in sudo und kann administrative Aufgaben ausführen.
```

### 25.2 Benutzerkonto `kighlander`

`getent passwd kighlander`:

```text
kighlander:x:1000:1000:Kai Akkermann:/home/kighlander:/bin/bash
```

Zusammenfassung:

| Feld | Wert |
|---|---|
| Benutzer | `kighlander` |
| UID | `1000` |
| Primäre GID | `1000` |
| Home | `/home/kighlander` |
| Shell | `/bin/bash` |
| Kommentar/GECOS | `Kai Akkermann` |

Gruppen:

```text
kighlander adm cdrom sudo dip plugdev lxd
```

### 25.3 Wichtige Gruppen

Erfasste Gruppen:

```text
sudo:x:27:kighlander
www-data:x:33:
adm:x:4:syslog,kighlander
sambashare:x:124:
lxd:x:110:kighlander
```

Bewertung:

```text
Der Benutzer kighlander ist Mitglied der Gruppen sudo, adm und lxd.
Die Gruppe www-data enthält keine expliziten zusätzlichen Benutzer.
Die Gruppe sambashare enthält keine expliziten Mitglieder.
```

Sicherheitsnotiz:

```text
Die Mitgliedschaft in der Gruppe lxd ist sicherheitsrelevant.
LXD-Gruppenmitgliedschaft kann in vielen Setups sehr weitgehende Systemrechte ermöglichen.
Vor einer Änderung muss geprüft werden, ob LXD tatsächlich benötigt wird.
```

### 25.4 Angemeldete Benutzer

`who`:

```text
kighlander pts/0 2026-06-13 14:26 (192.168.178.23)
```

`w`:

```text
17:58:05 up 3:31, 1 user, load average: 0,02, 0,01, 0,00
USER     TTY      FROM             LOGIN@   IDLE   JCPU   PCPU WHAT
kighland pts/0    192.168.178.23   14:26    4.00s  0.43s  0.01s w
```

Bewertung:

```text
Zum Zeitpunkt der Inventur war genau eine Benutzersitzung aktiv.
Die Sitzung kam aus dem LAN von 192.168.178.23.
```

### 25.5 sudo-Rechte

`sudo -l` meldet:

```text
Matching Defaults entries for kighlander on kighserv:
    env_reset, mail_badpass, secure_path=/usr/local/sbin\:/usr/local/bin\:/usr/sbin\:/usr/bin\:/sbin\:/bin\:/snap/bin,
    use_pty

User kighlander may run the following commands on kighserv:
    (ALL : ALL) ALL
```

Bewertung:

```text
Der Benutzer kighlander besitzt vollständige sudo-Rechte.
sudo verwendet die üblichen Default-Sicherheitsoptionen env_reset, mail_badpass, secure_path und use_pty.
```

### 25.6 Home-Verzeichnis Berechtigungen

Erfasste Berechtigungen:

```text
drwxr-xr-x  3 root       root       /home
drwxr-x--x 18 kighlander kighlander /home/kighlander
```

Bewertung:

```text
/home gehört root:root und ist allgemein les-/betretbar.
/home/kighlander gehört kighlander:kighlander.
Das Home-Verzeichnis ist nicht allgemein lesbar, aber für andere Benutzer betretbar/executable.
```

### 25.7 Auffällige Dateien und Verzeichnisse in `/home/kighlander`

Ausschnitt aus dem Home-Verzeichnis:

```text
_archive/
.cache/
.config/
.dotnet/
.java/
.local/
.mongodb/
.npm/
.nvm/
.pm2/
projects/
.ssh/
.symfony5/
temp/
.tmp/
.vscode-server/
```

Auffällige Dateien:

```text
ApacheGUI-1.12.0.tar.gz
composer-setup.php
setup-repos.sh
php82_modules.txt
php82_packages.txt
php84_available_packages.txt
php84_candidate_packages.txt
php84_installed_packages.txt
php84_install_packages.txt
php84_missing_packages.txt
php84_modules.txt
phpini_apache2_82_vs_84.diff
phpini_fpm_82_vs_84.diff
php_packages_before_cleanup.txt
modules_missing_in_php84.txt
modules_new_in_php84.txt
```

Bewertung:

```text
Das Home-Verzeichnis enthält noch verschiedene historische Installations-, Diagnose- und Entwicklungsartefakte.
Die großen Legacy-Projektverzeichnisse wurden bereits nach _archive verschoben.
Einige kleinere Altdateien und Tool-Verzeichnisse sind weiterhin vorhanden.
```

### 25.8 Besondere Auffälligkeit: ApacheGUI-Archiv

Erfasst:

```text
-rw-r--r-- 1 root root 54063420 Mär 12 2018 ApacheGUI-1.12.0.tar.gz
```

Bewertung:

```text
Im Home-Verzeichnis liegt ein altes ApacheGUI-Archiv mit root:root-Eigentümer.
ApacheGUI gehört nicht zur Zielarchitektur und sollte später als Bereinigungs-/Archivkandidat bewertet werden.
```

Noch keine Maßnahme:

```text
Die Datei wurde im Rahmen dieser Inventur nicht gelöscht oder verschoben.
```

### 25.9 Shell-/Entwicklungsumgebung

Vorhandene Hinweise auf Entwicklungsumgebungen:

```text
.nvm
.npm
.pm2
.dotnet
.java
.symfony5
.vscode-server
.mongodb
```

Bewertung:

```text
Das Home-Verzeichnis zeigt Spuren mehrerer Entwicklungs- und Laufzeitumgebungen.
Für die spätere Zielstruktur unter /srv/apps sollte entschieden werden, welche nutzerlokalen Toolchains weiterhin benötigt werden.
```

### 25.10 Block-5-Zwischenfazit

Der HomeServer wird aktuell primär über den Benutzer `kighlander` administriert.

Kurzprofil:

```text
Benutzer: kighlander
UID/GID: 1000/1000
sudo: vollständig
wichtige Gruppen: sudo, adm, lxd
Home: /home/kighlander
aktive Sitzung: 192.168.178.23
```

Sicherheits-/Bereinigungspunkte für später:

```text
lxd-Gruppenmitgliedschaft prüfen
alte Dateien im Home-Verzeichnis bewerten
ApacheGUI-Archiv bewerten
PM2-/NVM-/NPM-Reste prüfen
SSH-Konfiguration und authorized_keys separat erfassen
```

Noch keine Änderung durchgeführt:

```text
Keine Benutzer geändert.
Keine Gruppen geändert.
Keine sudo-Regeln geändert.
Keine Dateien gelöscht oder verschoben.
Dieser Block war rein lesend.
```

---

## 26. Verifizierte Inventurdaten: Block 6 - systemd-Dienste

**Erfasst am:** 2026-06-13  
**Quelle:** Read-Only-Inventur auf HomeServer `kighserv`

### 26.1 Failed Units

`systemctl --failed` meldet:

```text
0 loaded units listed.
```

Bewertung:

```text
Zum Inventurzeitpunkt lagen keine fehlgeschlagenen systemd Units vor.
```

### 26.2 Laufende Services

`systemctl list-units --type=service --state=running` meldete 30 laufende Services:

```text
apache2.service
cron.service
dbus.service
getty@tty1.service
irqbalance.service
mariadb.service
ModemManager.service
mongod.service
multipathd.service
networkd-dispatcher.service
nmbd.service
packagekit.service
php8.4-fpm.service
polkit.service
rsyslog.service
smbd.service
snapd.service
ssh.service
systemd-journald.service
systemd-logind.service
systemd-networkd.service
systemd-resolved.service
systemd-timesyncd.service
systemd-udevd.service
udisks2.service
unattended-upgrades.service
user@1000.service
vsftpd.service
webmin.service
wpa_supplicant.service
```

### 26.3 Enabled Services

`systemctl list-unit-files --type=service --state=enabled` meldete 59 enabled service unit files.

Für das Projekt und die Serverrolle besonders relevante enabled Services:

```text
apache2.service
mariadb.service
mongod.service
nmbd.service
php8.4-fpm.service
smbd.service
snapd.service
ssh.service
ufw.service
vsftpd.service
webmin.service
```

Weitere auffällige/zu bewertende enabled Services:

```text
cloud-config.service
cloud-final.service
cloud-init-local.service
cloud-init.service
lxd-agent.service
snap.lxd.activate.service
ModemManager.service
multipathd.service
open-iscsi.service
open-vm-tools.service
thermald.service
wpa_supplicant.service
```

Bewertung:

```text
Der Server hat neben den erwarteten Basisdiensten mehrere aktivierte Zusatzdienste.
Einige davon sind möglicherweise durch Ubuntu Server Defaults, Snap/LXD, Virtualisierungs-/Cloud-Images oder frühere Experimente entstanden.
```

### 26.4 Schlüsselservices: Statusübersicht

| Service | Zustand | Enabled | Rolle / Bewertung |
|---|---|---|---|
| `apache2` | active running | enabled | HTTP-Webserver aktiv |
| `php8.4-fpm` | active running | enabled | PHP-FPM 8.4 aktiv |
| `mariadb` | active running | enabled | MariaDB 10.6.23 aktiv |
| `mysql` | Alias/Verweis auf MariaDB | enabled über MariaDB | MySQL-Aufruf zeigt MariaDB-Service |
| `mongod` | active running | enabled | MongoDB aktiv |
| `ssh` | active running | enabled | SSH aktiv |
| `vsftpd` | active running | enabled | FTP aktiv |
| `smbd` | active running | enabled | Samba SMB aktiv |
| `nmbd` | active running | enabled | Samba NetBIOS aktiv |
| `webmin` | active running | enabled | Webmin aktiv |
| `pm2-root` | inactive dead | disabled | Root-PM2 gestoppt/deaktiviert |
| `pm2-kighlander` | kein Status ausgegeben | vermutlich nicht vorhanden | kein aktiver systemd-Service sichtbar |
| `snapd` | active running | enabled | Snap Daemon aktiv |
| `ufw` | active exited | enabled | Firewall aktiv |

### 26.5 Apache

Status:

```text
apache2.service - The Apache HTTP Server
Loaded: loaded (/lib/systemd/system/apache2.service; enabled; vendor preset: enabled)
Active: active (running)
Main PID: 2843 (apache2)
Tasks: 6
Memory: 18.3M
```

Log-Auszug zeigt Reloads während der Bereinigungs-/Inventurphase:

```text
Jun 13 17:33:51 Reloading The Apache HTTP Server...
Jun 13 17:34:24 Reloading The Apache HTTP Server...
Jun 13 17:35:55 Reloading The Apache HTTP Server...
Jun 13 17:37:02 Reloading The Apache HTTP Server...
```

Bewertung:

```text
Apache läuft stabil und ist enabled.
Die Reloads passen zeitlich zu den vorherigen Apache-Bereinigungen.
```

### 26.6 PHP-FPM

Status:

```text
php8.4-fpm.service - The PHP 8.4 FastCGI Process Manager
Loaded: loaded (/lib/systemd/system/php8.4-fpm.service; enabled)
Active: active (running)
Main PID: 793
Status: Processes active: 0, idle: 2, Requests: 0
Memory: 26.3M
```

Bewertung:

```text
PHP 8.4 FPM ist installiert, aktiv und enabled.
Zum Inventurzeitpunkt gab es keine aktiven Requests.
```

### 26.7 MariaDB

Status:

```text
mariadb.service - MariaDB 10.6.23 database server
Loaded: loaded (/lib/systemd/system/mariadb.service; enabled)
Active: active (running)
Main PID: 880 (mariadbd)
Status: Taking your SQL requests now...
Memory: 93.7M
```

Zusatzhinweis aus Log:

```text
This installation of MariaDB is already upgraded to 10.6.18-MariaDB.
There is no need to run mysql_upgrade again for 10.6.23-MariaDB, because they're both 10.6.
```

Bewertung:

```text
MariaDB läuft und ist enabled.
Der Listener aus Block 2 zeigt MariaDB nur auf 127.0.0.1:3306.
```

### 26.8 MongoDB

Status:

```text
mongod.service - MongoDB Database Server
Loaded: loaded (/lib/systemd/system/mongod.service; enabled)
Active: active (running)
Main PID: 788 (mongod)
Memory: 258.8M
CPU: 1min 36.468s
Command: /usr/bin/mongod --config /etc/mongod.conf
```

Bewertung:

```text
MongoDB läuft und ist enabled.
Der Listener aus Block 2 zeigt MongoDB nur auf 127.0.0.1:27017.
```

### 26.9 SSH

Status:

```text
ssh.service - OpenBSD Secure Shell server
Loaded: loaded (/lib/systemd/system/ssh.service; enabled)
Active: active (running)
Main PID: 846 (sshd)
Server listening on 0.0.0.0 port 22.
Server listening on :: port 22.
```

Log-Auszug:

```text
Accepted password for kighlander from 192.168.178.23 port 60452 ssh2
```

Bewertung:

```text
SSH läuft auf IPv4 und IPv6.
Mindestens ein erfolgreicher Passwort-Login wurde während der Inventur protokolliert.
Eine spätere SSH-Härtung sollte separat betrachtet werden.
```

### 26.10 FTP / vsftpd

Status:

```text
vsftpd.service - vsftpd FTP server
Loaded: loaded (/lib/systemd/system/vsftpd.service; enabled)
Active: active (running)
Main PID: 816 (vsftpd)
Command: /usr/sbin/vsftpd /etc/vsftpd.conf
```

Bewertung:

```text
FTP ist aktiv und enabled.
Da FTP meist nicht zur Zielarchitektur moderner Deployments gehört, ist dieser Dienst ein späterer Prüf-/Bereinigungskandidat.
```

### 26.11 Samba

`smbd`:

```text
smbd.service - Samba SMB Daemon
Loaded: loaded (/lib/systemd/system/smbd.service; enabled)
Active: active (running)
Status: smbd: ready to serve connections...
Memory: 18.9M
```

`nmbd`:

```text
nmbd.service - Samba NMB Daemon
Loaded: loaded (/lib/systemd/system/nmbd.service; enabled)
Active: active (running)
Status: nmbd: ready to serve connections...
Memory: 13.0M
```

Bewertung:

```text
Samba ist aktiv und enabled.
In Kombination mit Block 2 und Block 3 ist Samba aktuell netzwerkseitig breit exponiert.
Die tatsächlichen Shares müssen in einem späteren Samba-Block separat geprüft werden.
```

### 26.12 Webmin

Status:

```text
webmin.service - Webmin server daemon
Loaded: loaded (/lib/systemd/system/webmin.service; enabled)
Active: active (running)
Main PID: 1115 (miniserv.pl)
Memory: 458.2M
Command: /usr/bin/perl /usr/share/webmin/miniserv.pl /etc/webmin/miniserv.conf
```

Log-Auszug:

```text
pam_unix(webmin:auth): authentication failure; user=root
Webmin starting
```

Bewertung:

```text
Webmin ist aktiv und enabled.
Der Dienst lauscht laut Block 2 auf Port 10000, ist laut Block 3 aber nicht explizit in UFW erlaubt.
Der Speicherverbrauch ist im Vergleich zu den anderen Diensten auffällig hoch.
Ein fehlgeschlagener root-Loginversuch beim Start wurde protokolliert.
```

### 26.13 PM2

`pm2-root`:

```text
pm2-root.service - PM2 process manager
Loaded: loaded (/etc/systemd/system/pm2-root.service; disabled; vendor preset: enabled)
Active: inactive (dead)
```

Log-Auszug:

```text
No process found
All Applications Stopped
PM2 Daemon Stopped
Stopped PM2 process manager.
```

`pm2-kighlander`:

```text
Kein Status ausgegeben.
```

Bewertung:

```text
Root-PM2 wurde erfolgreich gestoppt und ist disabled.
Es ist kein aktiver pm2-kighlander systemd-Service sichtbar.
Dies passt zur vorherigen PM2-Bereinigung.
```

### 26.14 Snap / LXD

`snapd`:

```text
snapd.service - Snap Daemon
Loaded: loaded (/lib/systemd/system/snapd.service; enabled)
Active: active (running)
TriggeredBy: snapd.socket
Main PID: 747
Memory: 79.7M
```

Auffälliger Log-Hinweis:

```text
/lib/systemd/system/snapd.service:23: Unknown key name 'RestartMode' in section 'Service', ignoring.
```

Bewertung:

```text
Snap ist aktiv und enabled.
LXD-Komponenten sind laut enabled Services ebenfalls vorhanden.
Der Benutzer kighlander ist Mitglied der Gruppe lxd.
Der RestartMode-Hinweis deutet auf eine systemd/snapd-Kompatibilitätswarnung hin, ist aber nicht als failed unit sichtbar.
```

### 26.15 UFW

Status:

```text
ufw.service - Uncomplicated firewall
Loaded: loaded (/lib/systemd/system/ufw.service; enabled)
Active: active (exited)
```

Bewertung:

```text
UFW ist enabled und wurde beim Boot erfolgreich angewendet.
Die detaillierten Firewall-Regeln wurden bereits in Block 3 dokumentiert.
```

### 26.16 Block-6-Zwischenfazit

Zum Inventurzeitpunkt gibt es keine fehlgeschlagenen systemd Units.

Aktive und enabled Kern-/Zusatzdienste:

```text
Apache
PHP 8.4 FPM
MariaDB
MongoDB
SSH
UFW
Samba
FTP/vsftpd
Webmin
Snap/LXD
```

Bereits bereinigt:

```text
pm2-root ist inactive und disabled.
Kein aktiver pm2-kighlander systemd-Service sichtbar.
```

Wichtige spätere Entscheidungen:

```text
Soll Apache dauerhaft aktiv bleiben?
Soll PHP-FPM 8.4 dauerhaft aktiv bleiben?
Welche Datenbank(en) werden für stechen-mmo tatsächlich benötigt?
Soll MongoDB dauerhaft aktiv bleiben?
Soll MariaDB dauerhaft aktiv bleiben?
Soll FTP/vsftpd deaktiviert werden?
Soll Samba eingeschränkt oder deaktiviert werden?
Soll Webmin aktiv bleiben oder entfernt/deaktiviert werden?
Soll LXD/Snap weiter genutzt werden?
Soll SSH auf Key-only gehärtet werden?
```

Noch keine Änderung durchgeführt:

```text
Keine Services gestoppt.
Keine Services deaktiviert.
Keine Services aktiviert.
Keine Pakete entfernt.
Dieser Block war rein lesend.
```

---

## 27. Verifizierte Inventurdaten: Block 7 - Apache / PHP

**Erfasst am:** 2026-06-13  
**Quelle:** Read-Only-Inventur auf HomeServer `kighserv`

### 27.1 Apache Version

`apache2 -v`:

```text
Server version: Apache/2.4.52 (Ubuntu)
Server built:   2026-06-03T15:42:24
```

Bewertung:

```text
Apache 2.4.52 ist installiert und aktiv.
```

### 27.2 Apache Module

Aktiv geladene Apache-Module laut `apache2ctl -M`:

```text
access_compat_module
alias_module
auth_basic_module
authn_core_module
authn_file_module
authz_core_module
authz_host_module
authz_user_module
autoindex_module
core_module
deflate_module
dir_module
env_module
filter_module
http_module
log_config_module
logio_module
mime_module
mpm_prefork_module
negotiation_module
php_module
reqtimeout_module
rewrite_module
setenvif_module
so_module
status_module
unixd_module
userdir_module
version_module
watchdog_module
```

Bewertung:

```text
Apache nutzt mpm_prefork.
Das PHP-Modul ist geladen.
mod_rewrite, userdir, status und autoindex sind aktiv.
```

Architekturhinweis:

```text
Da zusätzlich PHP-FPM 8.4 aktiv ist, muss später entschieden werden, ob PHP über mod_php oder PHP-FPM betrieben werden soll.
Aktuell ist mindestens mod_php im Apache geladen.
```

### 27.3 Apache Sites

`/etc/apache2/sites-enabled`:

```text
total 8
drwxr-xr-x 2 root root 4096 Jun 13 17:37 .
drwxr-xr-x 8 root root 4096 Jun 13 17:35 ..
```

Bewertung:

```text
Es sind aktuell keine Apache Sites enabled.
Die vorherige Bereinigung der Legacy-vHosts ist damit bestätigt.
```

`/etc/apache2/sites-available`:

```text
000-default.conf
default-ssl.conf
```

Bewertung:

```text
Nur die Standard-vHost-Dateien sind noch in sites-available vorhanden.
Keine projektspezifischen vHost-Dateien sichtbar.
```

### 27.4 Apache conf-enabled

Aktive Apache-Konfigurationsfragmente:

```text
charset.conf
javascript-common.conf
localized-error-pages.conf
other-vhosts-access-log.conf
phpmyadmin.conf
security.conf
serve-cgi-bin.conf
```

Bewertung:

```text
phpmyadmin.conf ist aktiv eingebunden.
Das ist relevant, obwohl keine Sites enabled sind.
Weitere Standard-Konfigurationen sind aktiv.
```

Sicherheits-/Architekturhinweis:

```text
phpMyAdmin sollte später separat geprüft werden.
Es muss geklärt werden, ob phpMyAdmin für die Zielarchitektur weiterhin benötigt wird.
```

### 27.5 Apache Ports

`/etc/apache2/ports.conf`:

```text
Listen 80

<IfModule ssl_module>
        Listen 443
</IfModule>

<IfModule mod_gnutls.c>
        Listen 443
</IfModule>
```

Bewertung:

```text
Apache ist grundsätzlich für Port 80 konfiguriert.
Port 443 wird nur bei geladenem SSL- bzw. GnuTLS-Modul konfiguriert.
In Block 2 wurde Apache nur auf Port 80 als Listener sichtbar.
```

### 27.6 Apache Configtest

`sudo apache2ctl configtest`:

```text
Syntax OK
```

Bewertung:

```text
Die aktuelle Apache-Konfiguration ist syntaktisch gültig.
```

### 27.7 PHP CLI

`php -v`:

```text
PHP 8.4.20 (cli) (built: Apr 11 2026 07:43:00) (NTS)
Zend Engine v4.4.20
Zend OPcache v8.4.20
```

Bewertung:

```text
PHP CLI 8.4.20 ist installiert.
OPcache ist vorhanden.
```

### 27.8 PHP-FPM

`php-fpm8.4 -v`:

```text
PHP 8.4.20 (fpm-fcgi) (built: Apr 11 2026 07:43:00) (NTS)
Zend Engine v4.4.20
Zend OPcache v8.4.20
```

Bewertung:

```text
PHP-FPM 8.4.20 ist installiert.
Der systemd-Dienst php8.4-fpm ist laut Block 6 aktiv und enabled.
```

### 27.9 PHP Module

Aktive PHP-Module laut `php -m`:

```text
apcu
bz2
calendar
Core
ctype
curl
date
dom
exif
FFI
fileinfo
filter
ftp
gd
gettext
hash
iconv
imagick
intl
json
libxml
mbstring
mysqli
mysqlnd
OAuth
openssl
pcntl
pcre
PDO
pdo_mysql
Phar
posix
random
readline
Reflection
session
shmop
SimpleXML
sockets
sodium
SPL
standard
sysvmsg
sysvsem
sysvshm
tokenizer
xml
xmlreader
xmlwriter
xsl
Zend OPcache
zip
zlib
```

Bewertung:

```text
Die PHP-Installation enthält typische Web-/Datenbank-/Bild-/XML-Module.
Für Datenbankzugriff sind mysqli, mysqlnd, PDO und pdo_mysql vorhanden.
Für Bildverarbeitung sind gd und imagick vorhanden.
APCu und OPcache sind vorhanden.
```

### 27.10 PHP-FPM Pool

Vorhandene Pool-Datei:

```text
/etc/php/8.4/fpm/pool.d/www.conf
```

Aktive nicht-kommentierte Kerneinstellungen:

```text
[www]
user = www-data
group = www-data
listen = /run/php/php8.4-fpm.sock
listen.owner = www-data
listen.group = www-data
pm = dynamic
pm.max_children = 5
pm.start_servers = 2
pm.min_spare_servers = 1
pm.max_spare_servers = 3
```

Bewertung:

```text
Der PHP-FPM Pool www läuft unter www-data.
Der Pool lauscht auf einem Unix-Socket unter /run/php/php8.4-fpm.sock.
Die Prozesszahl ist klein dimensioniert und für leichte Nutzung passend.
```

### 27.11 Block-7-Zwischenfazit

Apache und PHP sind installiert und aktiv, aber aktuell ohne enabled Apache Sites.

Bestätigter Stand:

```text
Apache 2.4.52 installiert
Apache läuft laut Block 6
Apache configtest Syntax OK
Keine Sites enabled
Nur Standard-Sites in sites-available
phpmyadmin.conf in conf-enabled aktiv
Apache lädt php_module
PHP CLI 8.4.20 vorhanden
PHP-FPM 8.4.20 vorhanden und aktiv
PHP-FPM Pool www über Unix-Socket
```

Wichtige spätere Entscheidungen:

```text
Soll Apache Teil der Zielarchitektur für stechen-mmo sein?
Soll PHP überhaupt für stechen-mmo benötigt werden?
Soll PHP über mod_php oder PHP-FPM betrieben werden?
Soll phpMyAdmin aktiv bleiben?
Soll userdir aktiv bleiben?
Soll Apache ohne enabled Sites weiterhin laufen?
```

Noch keine Änderung durchgeführt:

```text
Keine Apache-Sites aktiviert oder deaktiviert.
Keine Apache-Module geändert.
Keine PHP-FPM-Konfiguration geändert.
Keine PHP-Module geändert.
Keine Dateien gelöscht.
Dieser Block war rein lesend.
```

---

## 28. Verifizierte Inventurdaten: Block 8 - Datenbanken / MariaDB / MongoDB

**Erfasst am:** 2026-06-13  
**Quelle:** Read-Only-Inventur auf HomeServer `kighserv`

### 28.1 MariaDB Version

`mariadb --version`:

```text
mariadb  Ver 15.1 Distrib 10.6.23-MariaDB, for debian-linux-gnu (x86_64) using EditLine wrapper
```

Bewertung:

```text
MariaDB 10.6.23 ist installiert.
Der systemd-Dienst mariadb ist laut Block 6 aktiv und enabled.
```

### 28.2 MariaDB sichere Service-Variablen

Erfasste Variablen:

| Variable | Wert |
|---|---|
| `version` | `10.6.23-MariaDB-0ubuntu0.22.04.1` |
| `version_comment` | `Ubuntu 22.04` |
| `datadir` | `/var/lib/mysql/` |
| `port` | `3306` |
| `socket` | `/run/mysqld/mysqld.sock` |
| `bind_address` | `127.0.0.1` |
| `skip_networking` | `OFF` |
| `character_set_server` | `utf8mb4` |
| `collation_server` | `utf8mb4_general_ci` |

Bewertung:

```text
MariaDB verwendet /var/lib/mysql/ als Datenverzeichnis.
Der Dienst lauscht auf Port 3306, ist aber an 127.0.0.1 gebunden.
skip_networking ist OFF, Netzwerkbetrieb ist also grundsätzlich aktiv, aber lokal begrenzt.
Der Server nutzt utf8mb4 als Standard-Zeichensatz.
```

Sicherheitsbewertung:

```text
MariaDB ist nach aktuellem Stand nicht direkt im LAN/WAN exponiert.
Die Bind-Adresse 127.0.0.1 bestätigt die Beobachtung aus Block 2.
```

### 28.3 MariaDB Datenbanken

`SHOW DATABASES`:

```text
hhb_database
information_schema
mysql
performance_schema
phpmyadmin
sys
```

Bewertung:

```text
Neben Systemdatenbanken existieren zwei besonders relevante Datenbanken:
hhb_database
phpmyadmin
```

Hinweis:

```text
Es wurden keine Tabelleninhalte oder personenbezogenen Daten ausgelesen.
Nur Datenbanknamen und Größen wurden erfasst.
```

### 28.4 MariaDB Datenbankgrößen

Erfasste Größen:

| Datenbank | Größe |
|---|---:|
| `mysql` | 3.38 MB |
| `phpmyadmin` | 0.39 MB |
| `hhb_database` | 0.36 MB |
| `information_schema` | 0.20 MB |
| `sys` | 0.03 MB |
| `performance_schema` | 0.00 MB |

Bewertung:

```text
Die MariaDB-Installation enthält nur sehr kleine Datenbestände.
hhb_database ist vorhanden, aber sehr klein.
phpmyadmin ist ebenfalls vorhanden und korrespondiert mit der aktiven Apache-Konfiguration phpmyadmin.conf aus Block 7.
```

### 28.5 MongoDB Version

`mongod --version`:

```text
db version v7.0.31
gitVersion: 6a3bdfa2794c261d3ce011c74f818e970e563609
OpenSSLVersion: OpenSSL 3.0.2 15 Mar 2022
distmod: ubuntu2204
distarch: x86_64
target_arch: x86_64
```

Bewertung:

```text
MongoDB 7.0.31 ist installiert.
Der systemd-Dienst mongod ist laut Block 6 aktiv und enabled.
```

### 28.6 MongoDB Konfiguration

Aktive, nicht kommentierte Konfiguration aus `/etc/mongod.conf`:

```yaml
storage:
  dbPath: /var/lib/mongodb
systemLog:
  destination: file
  logAppend: true
  path: /var/log/mongodb/mongod.log
net:
  port: 27017
  bindIp: 127.0.0.1
processManagement:
  timeZoneInfo: /usr/share/zoneinfo
```

Bewertung:

```text
MongoDB verwendet /var/lib/mongodb als Datenverzeichnis.
Logs werden nach /var/log/mongodb/mongod.log geschrieben.
Der Dienst lauscht auf Port 27017, ist aber an 127.0.0.1 gebunden.
```

Sicherheitsbewertung:

```text
MongoDB ist nach aktuellem Stand nicht direkt im LAN/WAN exponiert.
Die Bind-Adresse 127.0.0.1 bestätigt die Beobachtung aus Block 2.
```

### 28.7 MongoDB Datenbanken

Erfasste Datenbanken über `listDatabases`:

| Datenbank | Größe auf Disk | Empty |
|---|---:|---|
| `admin` | 40960 Bytes | false |
| `config` | 98304 Bytes | false |
| `gamedb` | 376832 Bytes | false |
| `local` | 73728 Bytes | false |

Gesamt:

```text
totalSize: 589824 Bytes
totalSizeMb: 0
```

Bewertung:

```text
Neben den MongoDB-Systemdatenbanken existiert die Datenbank gamedb.
Die Datenbestände sind sehr klein.
Es wurden keine Collections oder Dokumentinhalte ausgelesen.
```

### 28.8 Datenbank-Zwischenbewertung

Aktive Datenbanksysteme:

```text
MariaDB 10.6.23
MongoDB 7.0.31
```

Beide Datenbanksysteme sind:

```text
aktiv
enabled
lokal gebunden
nicht direkt extern über ihre DB-Ports exponiert
mit sehr kleinen Datenbeständen versehen
```

Relevante nicht-systemische Datenbanken:

```text
MariaDB: hhb_database
MariaDB: phpmyadmin
MongoDB: gamedb
```

### 28.9 Architekturfragen für später

Für die Zielarchitektur von `stechen-mmo` muss entschieden werden:

```text
Wird MariaDB benötigt?
Wird MongoDB benötigt?
Soll gamedb erhalten, migriert oder archiviert werden?
Soll hhb_database erhalten, migriert oder archiviert werden?
Soll phpMyAdmin aktiv bleiben?
Soll eine Datenbank dauerhaft auf diesem HomeServer laufen?
Soll eine separate Datenbank-Backupstrategie eingerichtet werden?
```

### 28.10 Block-8-Zwischenfazit

Die Datenbankdienste sind technisch sauber lokal gebunden und enthalten nur kleine Datenmengen.

Positiv:

```text
MariaDB bindet an 127.0.0.1.
MongoDB bindet an 127.0.0.1.
Keine externen DB-Listener sichtbar.
Datenvolumen gering.
```

Zu entscheiden:

```text
MariaDB und MongoDB laufen beide dauerhaft.
Für die Zielarchitektur sollte nur behalten werden, was tatsächlich benötigt wird.
phpMyAdmin ist vorhanden und Apache-seitig aktiv eingebunden.
```

Noch keine Änderung durchgeführt:

```text
Keine Datenbank gestoppt.
Keine Datenbank deaktiviert.
Keine Datenbank gelöscht.
Keine Tabellen/Collections ausgelesen.
Keine Dumps erstellt.
Keine Benutzer/Rechte verändert.
Dieser Block war rein lesend.
```

---

## 29. Verifizierte Inventurdaten: Block 9 - Samba / FTP / Webmin

**Erfasst am:** 2026-06-13  
**Quelle:** Read-Only-Inventur auf HomeServer `kighserv`

### 29.1 Samba Version

`smbd --version`:

```text
Version 4.15.13-Ubuntu
```

Bewertung:

```text
Samba 4.15.13 ist installiert.
Die Dienste smbd und nmbd sind laut Block 6 aktiv und enabled.
```

### 29.2 Samba Konfiguration

Aus `testparm -s`:

```ini
[global]
        log file = /var/log/samba/log.%m
        logging = file
        map to guest = Bad User
        max log size = 1000
        obey pam restrictions = Yes
        pam password change = Yes
        panic action = /usr/share/samba/panic-action %d
        passwd program = /usr/bin/passwd %u
        server role = standalone server
        server string = %h server (Samba, Ubuntu)
        unix password sync = Yes
        usershare allow guests = Yes
        idmap config * : backend = tdb
```

Bewertung:

```text
Samba läuft als standalone server.
Logging erfolgt dateibasiert unter /var/log/samba/log.%m.
usershare allow guests ist aktiv.
map to guest ist auf Bad User gesetzt.
```

### 29.3 Samba Shares

Erfasste Shares:

```ini
[printers]
        browseable = No
        comment = All Printers
        create mask = 0700
        path = /var/spool/samba
        printable = Yes

[print$]
        comment = Printer Drivers
        path = /var/lib/samba/printers

[kighlander]
        guest ok = Yes
        path = /home/kighlander
        read only = No
        valid users = kighlander
```

Bewertung:

```text
Neben den Standard-Printer-Shares existiert ein Share [kighlander].
Dieser Share verweist direkt auf /home/kighlander.
Der Share ist beschreibbar, da read only = No gesetzt ist.
Als gültiger Benutzer ist kighlander eingetragen.
guest ok = Yes ist gesetzt.
```

Sicherheitsnotiz:

```text
Ein beschreibbarer Samba-Share auf das komplette Home-Verzeichnis /home/kighlander ist sicherheitsrelevant.
In Kombination mit den UFW-Regeln aus Block 3 ist Samba derzeit breit erlaubt.
Die tatsächliche Erreichbarkeit hängt zusätzlich von Router-/IPv6-Firewall und Samba-Authentifizierung ab.
```

### 29.4 Samba Benutzer

Erfasst über `pdbedit -L`:

```text
kighlander:1000:Kai Akkermann
```

Bewertung:

```text
Der Benutzer kighlander ist als Samba-Benutzer vorhanden.
```

### 29.5 vsftpd Version / Paket

`vsftpd -v`:

```text
vsftpd: version 3.0.5
```

Paketstand:

```text
ii  vsftpd  3.0.5-0ubuntu1.1  amd64  lightweight, efficient FTP server written for security
```

Bewertung:

```text
vsftpd 3.0.5 ist installiert.
Der Dienst vsftpd ist laut Block 6 aktiv und enabled.
```

### 29.6 vsftpd Konfiguration

Aktive, nicht kommentierte Konfiguration aus `/etc/vsftpd.conf`:

```ini
listen=NO
listen_ipv6=YES
anonymous_enable=NO
local_enable=YES
write_enable=YES
dirmessage_enable=YES
use_localtime=YES
xferlog_enable=YES
connect_from_port_20=YES
secure_chroot_dir=/var/run/vsftpd/empty
pam_service_name=vsftpd
rsa_cert = [REDACTED]
rsa_private_key_file = [REDACTED]
ssl_enable=NO
```

Bewertung:

```text
vsftpd lauscht über IPv6-Modus.
Anonyme Logins sind deaktiviert.
Lokale Benutzerlogins sind erlaubt.
Schreibzugriff ist erlaubt.
SSL/TLS ist deaktiviert.
```

Sicherheitsnotiz:

```text
Da ssl_enable=NO gesetzt ist, ist FTP nach aktuellem Stand unverschlüsselt.
FTP ist für moderne Zielarchitekturen in der Regel kein bevorzugter Dienst.
Die UFW-Regel aus Block 3 erlaubt Port 21 nur aus dem alten Netz 192.168.1.0/24, nicht aus dem aktuellen LAN 192.168.178.0/24.
Der Dienst selbst läuft jedoch aktiv.
```

### 29.7 Webmin Version / Paket

Paketstand:

```text
ii  webmin  2.630  all  web-based administration interface for Unix systems
```

Versionsdatei:

```text
2.630
```

Bewertung:

```text
Webmin 2.630 ist installiert.
Der Dienst webmin ist laut Block 6 aktiv und enabled.
Laut Block 2 lauscht Webmin auf Port 10000.
Laut Block 3 ist Port 10000 in UFW nicht explizit erlaubt.
```

### 29.8 Webmin miniserv.conf

Für `WEBMIN MINISERV CONFIG SAFE` wurde in der vorliegenden Ausgabe keine Konfiguration sichtbar ausgegeben.

Bewertung:

```text
Die Webmin-Hauptkonfiguration /etc/webmin/miniserv.conf wurde in diesem Block nicht vollständig dokumentiert.
Ein separater Nachtrag kann diese Datei später gefiltert erfassen.
```

Noch keine Maßnahme:

```text
Die Webmin-Konfiguration wurde nicht geändert.
```

### 29.9 Webmin ACL

Erfasster ACL-Ausschnitt:

```text
/etc/webmin/webmin.acl
root: acl adsl-client apache at backup-config bacula-backup bandwidth bind8 change-user cluster-copy cluster-cron ...
Kighlander:
```

Hinweis:

```text
Ein Teil der ACL-Ausgabe wurde durch den Sicherheitsfilter redigiert.
Die Ausgabe zeigt, dass /etc/webmin/webmin.acl vorhanden ist.
```

Bewertung:

```text
Webmin besitzt ACL-Konfigurationen.
Die vorliegende Ausgabe reicht zur groben Inventarisierung, aber nicht zur vollständigen Rechtebewertung.
Eine detaillierte Webmin-Rechteprüfung sollte nur erfolgen, wenn Webmin dauerhaft genutzt werden soll.
```

### 29.10 Block-9-Zwischenfazit

Aktive Zusatzdienste:

```text
Samba
vsftpd
Webmin
```

Wichtigste Erkenntnisse:

```text
Samba teilt /home/kighlander als beschreibbaren Share [kighlander].
Samba-Benutzer kighlander existiert.
vsftpd erlaubt lokale Benutzer und Schreibzugriff.
vsftpd nutzt kein SSL/TLS.
Webmin 2.630 ist installiert und läuft.
Webmin-Port 10000 ist laut UFW nicht explizit erlaubt.
```

Sicherheits-/Architekturfragen für später:

```text
Soll Samba dauerhaft aktiv bleiben?
Soll /home/kighlander als Samba-Share erhalten bleiben?
Soll guest ok beim Samba-Share entfernt werden?
Soll vsftpd deaktiviert oder entfernt werden?
Soll Webmin dauerhaft aktiv bleiben?
Soll Webmin nur lokal/VPN/LAN erreichbar sein?
Soll Webmin komplett entfernt werden?
```

Noch keine Änderung durchgeführt:

```text
Keine Samba-Konfiguration geändert.
Keine Samba-Shares entfernt.
Keine FTP-Konfiguration geändert.
Kein Dienst gestoppt.
Kein Dienst deaktiviert.
Keine Webmin-Konfiguration geändert.
Dieser Block war rein lesend.
```

---

## 29B. Nachtrag zu Block 9 - Webmin miniserv.conf

**Erfasst am:** 2026-06-13  
**Quelle:** Read-Only-Inventur auf HomeServer `kighserv`

### 29B.1 Webmin MiniServ Konfiguration

Gefilterte, nicht kommentierte Konfiguration aus `/etc/webmin/miniserv.conf`:

```ini
port=10000
addtype_cgi=internal/cgi
realm=Webmin Server
logfile=/var/webmin/miniserv.log
errorlog=/var/webmin/miniserv.error
pidfile=/var/webmin/miniserv.pid
logtime=672
ssl=1
no_ssl2=1
no_ssl3=1
ssl_honorcipherorder=1
no_sslcompression=1
env_WEBMIN_CONFIG=/etc/webmin
env_WEBMIN_VAR=/var/webmin
atboot=1
logout=/etc/webmin/logout-flag
listen=10000
denyfile=\.pl$
log=1
blockhost_failures=5
blockhost_time=60
syslog=1
ipv6=1
session=[REDACTED]
premodules=WebminCore
userfile=/etc/webmin/miniserv.users
key=[REDACTED]
pass=[REDACTED]
preroot=authentic-theme
cipher_list_def=1
logout_script=/etc/webmin/logout.pl
login_script=/etc/webmin/login.pl
failed_script=/etc/webmin/failed.pl
sudo=1
error_handler_403=403.cgi
error_handler_401=401.cgi
error_handler_404=404.cgi
logouttimes=
preroot_root=authentic-theme
libwrap=
trust_real_ip=0
alwaysresolve=0
sockets=
no_resolv_myname=0
logclear=1
loghost=0
logclf=0
root=/usr/share/webmin
mimetypes=/usr/share/webmin/mime.types
server=MiniServ/2.630
ssl_hsts=1
ssl_enforce=2
no_trust_ssl=1
```

Hinweis:

```text
Session-, Key- und Passwort-/Hash-Zeilen wurden redigiert.
```

### 29B.2 Webmin Bewertung

Bestätigte technische Eigenschaften:

```text
Webmin lauscht auf Port 10000.
Webmin verwendet SSL/TLS.
IPv6 ist in Webmin aktiviert.
Autostart ist aktiviert.
Logging und syslog sind aktiviert.
MiniServ-Version ist 2.630.
sudo-Unterstützung ist aktiviert.
HSTS ist aktiviert.
SSL-Erzwingung ist konfiguriert.
```

Einordnung mit früheren Blöcken:

```text
Block 2: Webmin lauscht auf 0.0.0.0:10000 und [::]:10000.
Block 3: UFW erlaubt Port 10000 nicht explizit.
Block 6: webmin.service ist active running und enabled.
```

Sicherheits-/Architekturhinweis:

```text
Webmin ist technisch aktiv und auf IPv4/IPv6 gebunden.
Die UFW-Regeln blockieren Port 10000 vermutlich für eingehende Verbindungen, sofern keine anderen Pfade/Routerregeln greifen.
Ob Webmin dauerhaft benötigt wird, sollte nach Abschluss der Inventur entschieden werden.
```

Noch keine Änderung durchgeführt:

```text
Keine Webmin-Konfiguration geändert.
Keine Webmin-Benutzer geändert.
Kein Dienst gestoppt oder deaktiviert.
Dieser Nachtrag war rein lesend.
```

---

## 30. Verifizierte Inventurdaten: Block 10 - Node / npm / nvm / PM2

**Erfasst am:** 2026-06-13  
**Quelle:** Read-Only-Inventur auf HomeServer `kighserv`

### 30.1 PATH

Erfasster PATH-Ausschnitt:

```text
/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/home/kighlander/.local/bin:/usr/games
/usr/local/games
/snap/bin
/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/games:/usr/local/games:/snap/bin
```

Bewertung:

```text
Die PATH-Ausgabe enthält Standardpfade, /home/kighlander/.local/bin und Snap-Pfade.
Die Darstellung enthält mehrere Zeilen, vermutlich durch Shell-/Profilinitialisierung.
```

### 30.2 Node / npm / npx aus PATH

Erfasste Programme:

```text
/usr/bin/node
v20.20.2

/usr/bin/npm
10.8.2

/usr/bin/npx
10.8.2
```

Bewertung:

```text
Node.js ist systemweit unter /usr/bin/node verfügbar.
npm und npx sind ebenfalls systemweit verfügbar.
Die aktive Node-Version aus PATH ist v20.20.2.
```

### 30.3 NVM

`~/.nvm` ist vorhanden:

```text
/home/kighlander/.nvm
/home/kighlander/.nvm/nvm.sh
/home/kighlander/.nvm/alias
```

Installierte NVM-Node-Versionen:

```text
/home/kighlander/.nvm/versions/node/v16.20.2
/home/kighlander/.nvm/versions/node/v20.18.3
```

Bewertung:

```text
NVM ist für den Benutzer kighlander installiert.
Zusätzlich zum systemweiten Node.js existieren zwei NVM-verwaltete Node-Versionen.
```

Architekturhinweis:

```text
Für die spätere Zielarchitektur sollte entschieden werden, ob stechen-mmo über systemweites Node.js, NVM oder eine andere Runtime-Strategie betrieben wird.
```

### 30.4 Globale npm-Pakete

`npm list -g --depth=0`:

```text
/usr/lib
├── corepack@0.34.6
├── npm@10.8.2
└── pm2@5.4.3
```

Bewertung:

```text
PM2 ist global installiert.
Corepack ist global verfügbar.
```

### 30.5 PM2 aus PATH

Erfasst:

```text
/usr/bin/pm2
5.4.3
```

Bewertung:

```text
PM2 Version 5.4.3 ist systemweit verfügbar.
```

### 30.6 PM2 User-Status

`pm2 status`:

```text
┌────┬────────────────────┬──────────┬──────┬───────────┬──────────┬──────────┐
│ id │ name               │ mode     │ ↺    │ status    │ cpu      │ memory   │
└────┴────────────────────┴──────────┴──────┴───────────┴──────────┴──────────┘
```

`pm2 jlist`:

```json
[]
```

Bewertung:

```text
Im PM2-Kontext des Benutzers kighlander sind aktuell keine Anwendungen registriert.
Dies bestätigt die vorherige PM2-Bereinigung.
```

### 30.7 PM2 Home des Benutzers

`~/.pm2` enthält:

```text
_backup
dump.pm2
dump.pm2.bak
logs
module_conf.json
modules
pids
pm2.log
pm2.pid
pub.sock
rpc.sock
touch
```

Gefundene Dateien:

```text
/home/kighlander/.pm2/_backup/dump.pm2.bak.before-cleanup-2026-06-13-173929
/home/kighlander/.pm2/_backup/dump.pm2.before-cleanup-2026-06-13-173929
/home/kighlander/.pm2/dump.pm2
/home/kighlander/.pm2/dump.pm2.bak
/home/kighlander/.pm2/logs/stechen-mmo-error.log
/home/kighlander/.pm2/logs/stechen-mmo-out.log
/home/kighlander/.pm2/module_conf.json
/home/kighlander/.pm2/pids/stechen-mmo-0.pid
/home/kighlander/.pm2/pm2.log
/home/kighlander/.pm2/pm2.pid
/home/kighlander/.pm2/touch
```

Bewertung:

```text
Obwohl PM2 keine laufenden bzw. registrierten Anwendungen zeigt, existieren noch PM2-Arbeitsdateien, Logs, alte PID-Dateien und Backups.
dump.pm2 ist sehr klein und wurde offenbar geleert.
dump.pm2.bak sowie Backups aus der Bereinigung sind noch vorhanden.
```

Sicherheits-/Bereinigungshinweis:

```text
Da PM2-Logs und ältere Dumps historische Umgebungsdaten enthalten können, sollten sie später separat geprüft oder archiviert/bereinigt werden.
Aktuell wurde nichts verändert.
```

### 30.8 Root-PM2 Service-Datei

Vorhandene Datei:

```text
/etc/systemd/system/pm2-root.service
```

Dateiinhalt:

```ini
[Unit]
Description=PM2 process manager
Documentation=https://pm2.keymetrics.io/
After=network.target

[Service]
Type=forking
User=root
LimitNOFILE=infinity
LimitNPROC=infinity
LimitCORE=infinity
Environment=PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/snap/bin:/bin:/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin
Environment=PM2_HOME=/root/.pm2
PIDFile=/root/.pm2/pm2.pid
Restart=on-failure

ExecStart=/usr/lib/node_modules/pm2/bin/pm2 resurrect
ExecReload=/usr/lib/node_modules/pm2/bin/pm2 reload all
ExecStop=/usr/lib/node_modules/pm2/bin/pm2 kill

[Install]
WantedBy=multi-user.target
```

Bewertung:

```text
Die Root-PM2-Service-Datei existiert weiterhin.
Laut Block 6 ist pm2-root.service inactive und disabled.
Die Datei verweist auf PM2_HOME=/root/.pm2.
```

### 30.9 Node-bezogene Debian-Pakete

Erfasst:

```text
ii  nodejs  20.20.2-1nodesource1  amd64  Node.js event-based server-side javascript engine
```

Bewertung:

```text
Node.js wurde offenbar über NodeSource installiert.
Ein separates Debian-Paket npm wurde nicht als installiertes Paket angezeigt, npm ist aber über die NodeSource-/Node.js-Installation vorhanden.
```

### 30.10 Block-10-Zwischenfazit

Aktiver JavaScript-/Node-Stand:

```text
System-Node: v20.20.2
System-npm: 10.8.2
System-npx: 10.8.2
Global PM2: 5.4.3
NVM vorhanden mit Node v16.20.2 und v20.18.3
PM2 User-App-Liste leer
Root-PM2-Service deaktiviert/inaktiv, Service-Datei aber noch vorhanden
```

Wichtige spätere Entscheidungen:

```text
Soll für stechen-mmo systemweites Node.js oder NVM verwendet werden?
Soll PM2 wieder als Prozessmanager genutzt werden?
Soll stattdessen systemd direkt verwendet werden?
Sollen alte PM2-Logs/Dumps/PIDs archiviert oder gelöscht werden?
Soll die pm2-root.service-Datei entfernt werden?
Soll nur eine Node-Version als Zielruntime definiert werden?
```

Noch keine Änderung durchgeführt:

```text
Keine Node-Version geändert.
Keine npm-Pakete installiert oder entfernt.
Keine PM2-Prozesse gestartet oder gestoppt.
Keine PM2-Dateien gelöscht.
Keine systemd-Dateien geändert.
Dieser Block war rein lesend.
```

---

## 31. Verifizierte Inventurdaten: Block 11 - Benutzer / Gruppen / SSH

**Erfasst am:** 2026-06-13  
**Quelle:** Read-Only-Inventur auf HomeServer `kighserv`

### 31.1 Aktueller Benutzer

`whoami`:

```text
kighlander
```

`id`:

```text
uid=1000(kighlander) gid=1000(kighlander) groups=1000(kighlander),4(adm),24(cdrom),27(sudo),30(dip),46(plugdev),110(lxd)
```

`groups`:

```text
kighlander adm cdrom sudo dip plugdev lxd
```

Bewertung:

```text
Der primäre Administrationsbenutzer ist kighlander.
Der Benutzer besitzt sudo-Rechte über die Gruppe sudo.
Zusätzlich ist kighlander Mitglied der Gruppe lxd.
```

Sicherheitsnotiz:

```text
Die lxd-Gruppenmitgliedschaft ist sicherheitsrelevant, da LXD-Mitglieder auf vielen Systemen effektiv weitreichende Rechte erlangen können.
Dies sollte später gezielt bewertet werden.
```

### 31.2 Relevante passwd-Einträge

Gefilterte Ausgabe ohne Passwort-Hashes:

```text
kighlander:1000:1000:/home/kighlander:/bin/bash
www-data:33:33:/var/www:/usr/sbin/nologin
root:0:0:/root:/bin/bash
nobody:65534:65534:/nonexistent:/usr/sbin/nologin
```

Bewertung:

```text
kighlander ist ein regulärer Login-Benutzer mit Bash.
root hat eine Shell, wird aber über SSH gesondert geregelt.
www-data und nobody sind nicht als interaktive Login-Benutzer konfiguriert.
```

### 31.3 Relevante Gruppen

Erfasste Gruppen:

```text
sudo:x:27:kighlander
adm:x:4:syslog,kighlander
www-data:x:33:
lxd:x:110:kighlander
sambashare:x:124:
users:x:100:
```

Bewertung:

```text
kighlander ist Mitglied von sudo, adm und lxd.
www-data hat keine zusätzlichen Mitglieder.
sambashare und users enthalten in der Ausgabe keine Mitglieder.
Eine docker-Gruppe wurde nicht angezeigt.
```

### 31.4 Home- und SSH-Rechte

Verzeichnisrechte:

```text
drwxr-xr-x  3 root       root       4096 Jul 12  2024 /home
drwxr-x--x 18 kighlander kighlander 4096 Jun 13 17:59 /home/kighlander
drwx------  2 kighlander kighlander 4096 Mär 10  2025 /home/kighlander/.ssh
```

SSH-Dateirechte:

```text
-rw------- kighlander kighlander 743 /home/kighlander/.ssh/authorized_keys
-rw------- kighlander kighlander 978 /home/kighlander/.ssh/known_hosts
-rw-r--r-- kighlander kighlander 142 /home/kighlander/.ssh/known_hosts.old
```

Bewertung:

```text
/home/kighlander ist nicht weltlesbar, aber weltbetretbar.
.ssh ist mit 700 restriktiv.
authorized_keys und known_hosts sind mit 600 restriktiv.
known_hosts.old ist mit 644 lesbar, enthält aber keine privaten Schlüssel.
```

Sicherheitsnotiz:

```text
Das x-Bit für others auf /home/kighlander erlaubt grundsätzlich Traversal, sofern Pfade bekannt sind.
Ob dies gewünscht ist, sollte später mit Samba/Webserver-/Dienstanforderungen abgeglichen werden.
```

### 31.5 authorized_keys Metadaten

Erfasst:

```text
1 /home/kighlander/.ssh/authorized_keys
4096 SHA256:Oyd3Gos+s4HBwQm/zQGusQYYNq6vVO8LYDXIrCzRoZw kighl@AMD-RECHNER (RSA)
```

Bewertung:

```text
Es ist genau ein öffentlicher SSH-Schlüssel für kighlander hinterlegt.
Der Schlüssel ist ein 4096-Bit RSA-Key mit Kommentar kighl@AMD-RECHNER.
Es wurden keine Key-Inhalte dokumentiert, nur Metadaten/Fingerprint.
```

### 31.6 Effektive SSHD-Konfiguration

Aus `sshd -T`, sicher gefiltert:

```text
addressfamily any
allowtcpforwarding yes
authenticationmethods any
clientalivecountmax 3
clientaliveinterval 0
kbdinteractiveauthentication no
listenaddress 0.0.0.0:22
listenaddress [::]:22
loglevel INFO
maxauthtries 6
passwordauthentication yes
permitemptypasswords no
permitrootlogin without-password
port 22
pubkeyauthentication yes
usedns no
x11forwarding yes
```

Bewertung:

```text
SSH lauscht auf IPv4 und IPv6 Port 22.
Public-Key-Authentifizierung ist aktiviert.
Passwort-Authentifizierung ist ebenfalls aktiviert.
Root-Login ist nur ohne Passwort bzw. per Schlüssel erlaubt.
Leere Passwörter sind deaktiviert.
Keyboard-interactive Authentication ist deaktiviert.
X11-Forwarding und TCP-Forwarding sind aktiviert.
```

Sicherheitsnotiz:

```text
Für eine spätere Härtung sollten insbesondere PasswordAuthentication, X11Forwarding und AllowTcpForwarding bewertet werden.
```

### 31.7 SSH-Konfigurationsdateien

Erfasste Dateien:

```text
/etc/ssh/ssh_host_dsa_key
/etc/ssh/ssh_host_rsa_key
/etc/ssh/sshd_config.d/50-cloud-init.conf
/etc/ssh/ssh_host_ed25519_key
/etc/ssh/ssh_host_ecdsa_key
/etc/ssh/ssh_config
/etc/ssh/ssh_host_ecdsa_key.pub
/etc/ssh/sshd_config
/etc/ssh/ssh_import_id
/etc/ssh/moduli
/etc/ssh/ssh_host_rsa_key.pub
/etc/ssh/ssh_host_dsa_key.pub
/etc/ssh/ssh_host_ed25519_key.pub
```

Bewertung:

```text
SSH-Hostkeys für RSA, ECDSA, ED25519 und DSA sind vorhanden.
Die Datei /etc/ssh/sshd_config.d/50-cloud-init.conf existiert.
Private Hostkeys sind restriktiv mit 600 geschützt.
```

Sicherheitsnotiz:

```text
Der vorhandene DSA-Hostkey ist historisch/alt und sollte später geprüft werden.
```

### 31.8 Sudoers

Erfasste nicht-kommentierte sudoers-Zeilen:

```text
/etc/sudoers:Defaults   env_reset
/etc/sudoers:Defaults   mail_badpass
/etc/sudoers:Defaults   secure_path="/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/snap/bin"
/etc/sudoers:Defaults   use_pty
/etc/sudoers:root       ALL=(ALL:ALL) ALL
/etc/sudoers:%admin ALL=(ALL) ALL
/etc/sudoers:%sudo      ALL=(ALL:ALL) ALL
/etc/sudoers:@includedir /etc/sudoers.d
```

Bewertung:

```text
root und Mitglieder der Gruppe sudo besitzen volle sudo-Rechte.
kighlander erhält sudo-Rechte über die Gruppe sudo.
Es wurde keine aktive NOPASSWD-Regel angezeigt.
```

### 31.9 Block-11-Zwischenfazit

Bestätigter Zustand:

```text
kighlander ist Haupt-Adminbenutzer.
kighlander hat sudo-Rechte.
kighlander ist Mitglied der lxd-Gruppe.
Ein SSH-Key ist für kighlander hinterlegt.
SSH erlaubt sowohl Public-Key- als auch Passwort-Authentifizierung.
SSH lauscht auf IPv4 und IPv6.
Home- und SSH-Dateirechte sind überwiegend restriktiv.
```

Wichtige spätere Entscheidungen:

```text
Soll SSH auf Key-only umgestellt werden?
Soll X11Forwarding deaktiviert werden?
Soll AllowTcpForwarding eingeschränkt werden?
Soll die lxd-Gruppenmitgliedschaft entfernt werden?
Soll der DSA-Hostkey entfernt bzw. deaktiviert werden?
Soll /home/kighlander das other-execute-Bit behalten?
Soll SSH auf bestimmte Benutzer oder Adressbereiche eingeschränkt werden?
```

Noch keine Änderung durchgeführt:

```text
Keine Benutzer geändert.
Keine Gruppen geändert.
Keine SSH-Konfiguration geändert.
Keine SSH-Keys geändert.
Keine sudoers-Dateien geändert.
Dieser Block war rein lesend.
```

---

## 32. Verifizierte Inventurdaten: Block 12 - Projektpfade / Webroots / relevante Verzeichnisse

**Erfasst am:** 2026-06-13  
**Quelle:** Read-Only-Inventur auf HomeServer `kighserv`

### 32.1 Top-Level Web-/Service-Verzeichnisse

`/var/www`:

```text
total 12
drwxr-xr-x  3 root root 4096 Jul 12  2024 .
drwxr-xr-x 15 root root 4096 Jul 12  2024 ..
drwxr-xr-x  2 root root 4096 Apr 29 08:56 html
```

`/srv`:

```text
total 12
drwxr-xr-x  3 root root 4096 Aug  2  2024 .
drwxr-xr-x 20 root root 4096 Jul 12  2024 ..
drwxr-xr-x  2 root ftp  4096 Aug  2  2024 ftp
```

`/opt`:

```text
total 8
drwxr-xr-x  2 root root 4096 Feb 16  2024 .
drwxr-xr-x 20 root root 4096 Jul 12  2024 ..
```

Bewertung:

```text
/var/www enthält nur das Standardverzeichnis html.
/srv enthält ein FTP-Verzeichnis /srv/ftp.
/opt ist leer.
Es wurden keine aktiven Projektdeployments in /var/www, /srv oder /opt sichtbar.
```

### 32.2 Home-Verzeichnis Top-Level

Auszug aus `/home/kighlander`:

```text
ApacheGUI-1.12.0.tar.gz
_archive
.bash_history
.bashrc
.bashrc.before_disable_fnm
.bashrc.before_disable_nvm
.bashrc.before_python_path_cleanup
.cache
composer-setup.php
.config
.dotnet
.gitconfig
.java
.local
.mongodb
.npm
.nvm
.pm2
.profile
.profile.before_final_dedup
.profile.before_hard_path_fix
.profile.before_path_normalize
.profile.before_python_path_cleanup
projects
setup-repos.sh
.ssh
.symfony5
temp
.tmp
.vscode-server
```

Zusätzlich diverse PHP-8.2-/PHP-8.4-Inventur- und Vergleichsdateien:

```text
php82_modules.txt
php82_packages.txt
php84_available_packages.txt
php84_candidate_packages.txt
php84_installed_packages.txt
php84_install_packages.txt
php84_missing_packages.txt
php84_modules.txt
phpini_apache2_82_vs_84.diff
phpini_fpm_82_vs_84.diff
php_packages_before_cleanup.txt
modules_missing_in_php84.txt
modules_new_in_php84.txt
```

Bewertung:

```text
Das Home-Verzeichnis enthält zahlreiche Entwicklungs-, Inventur- und Legacy-Artefakte.
ApacheGUI-1.12.0.tar.gz ist als altes Archiv im Home-Verzeichnis vorhanden.
Mehrere Shell-Profil-Backups deuten auf frühere PATH-/Runtime-Bereinigungen hin.
```

### 32.3 Gefundene relevante Projektverzeichnisse

In `/var/www`, `/srv` und `/opt` wurden mit den Suchmustern keine relevanten Projektverzeichnisse gefunden.

Relevante Treffer unter `/home/kighlander`:

```text
/home/kighlander/_archive/legacy-home-2026-06-13/stechengameserver
/home/kighlander/_archive/legacy-home-2026-06-13/Stechen_Karten_MMO
/home/kighlander/_archive/legacy-home-2026-06-13/stechen-node-server
```

Weitere Node-bezogene Treffer:

```text
/home/kighlander/.nvm/test/installation_node
/home/kighlander/.nvm/versions/node
```

Bewertung:

```text
Die relevanten alten stechen-/game-/mmo-Projektordner liegen aktuell unter _archive/legacy-home-2026-06-13.
Es wurden keine aktiven Projektordner außerhalb dieses Archivs sichtbar.
```

### 32.4 Package.json-Dateien

Gefundene relevante `package.json`-Dateien:

```text
/home/kighlander/_archive/legacy-home-2026-06-13/stechengameserver/package.json
/home/kighlander/_archive/legacy-home-2026-06-13/stechen-node-server/package.json
/home/kighlander/_archive/legacy-home-2026-06-13/Stechen_Karten_MMO/server/package.json
```

Weitere nicht-projektspezifische Treffer:

```text
/home/kighlander/.mongodb/mongosh/snippets/package.json
/home/kighlander/.nvm/package.json
/home/kighlander/.cache/typescript/5.7/package.json
/home/kighlander/.vscode-server/extensions/ms-ceintl.vscode-language-pack-de-*/package.json
```

Bewertung:

```text
Die alten Node-/JavaScript-Projektartefakte sind im Archiv vorhanden.
Für die spätere Migration oder Rekonstruktion sind insbesondere die package.json-Dateien in Stechen_Karten_MMO/server, stechen-node-server und stechengameserver relevant.
```

### 32.5 ENV-/Konfigurationsdateien nur als Metadaten

Gefundene relevante Datei:

```text
-rw-rw-r-- kighlander kighlander 439 2025-03-03 10:58 /home/kighlander/_archive/legacy-home-2026-06-13/Stechen_Karten_MMO/server/.env
```

Weitere nicht-projektspezifische Konfigurationsdatei:

```text
/home/kighlander/.cache/composer/repo/https---repo.packagist.org/provider-symfony~config.json
```

Bewertung:

```text
Eine .env-Datei des alten Stechen_Karten_MMO/server-Projekts existiert im Archiv.
Es wurden keine Inhalte dieser Datei ausgelesen.
Die Dateirechte sind 664, damit gruppenschreibbar und weltlesbar.
```

Sicherheitsnotiz:

```text
.env-Dateien können Secrets enthalten.
Die Datei sollte später gezielt gesichert, rotiert oder entfernt werden.
Für die Inventur wurden nur Pfad und Metadaten dokumentiert.
```

### 32.6 Git-Repositories

Gefundene `.git`-Verzeichnisse:

```text
/home/kighlander/_archive/legacy-home-2026-06-13/Stechen_Karten_MMO/.git
/home/kighlander/.nvm/.git
/home/kighlander/.config/JetBrains/PhpStorm2024.1/settingsSync/.git
```

Bewertung:

```text
Das archivierte Projekt Stechen_Karten_MMO ist ein Git-Repository.
NVM und JetBrains Settings Sync enthalten ebenfalls Git-Verzeichnisse, sind aber nicht als Projektdeployment zu werten.
```

### 32.7 Verzeichnisgrößen

Ausgewählte Größen:

```text
/var/www                         20K
/var/www/html                    16K
/srv                             8,0K
/srv/ftp                         4,0K
/opt                             4,0K
/home/kighlander/projects        21M
/home/kighlander/_archive        55M
/home/kighlander/.npm            56M
/home/kighlander/.local          93M
/home/kighlander/.nvm            322M
/home/kighlander/.vscode-server  668M
/home/kighlander/.pm2            2,5G
/home/kighlander/.cache          3,2G
/home/kighlander                 6,9G
```

Besonders auffällig:

```text
/home/kighlander/.pm2/logs       2,5G
/home/kighlander/.cache/JetBrains 3,1G
```

Bewertung:

```text
Der belegte Speicher im Home-Verzeichnis stammt überwiegend aus PM2-Logs und Caches.
Die eigentlichen archivierten Projektordner sind mit ca. 55M vergleichsweise klein.
```

### 32.8 Block-12-Zwischenfazit

Bestätigter Stand:

```text
Keine aktiven Projektdeployments in /var/www, /srv oder /opt sichtbar.
Alte stechen-/mmo-/game-Projekte liegen im Archiv unter /home/kighlander/_archive/legacy-home-2026-06-13.
Eine alte .env-Datei existiert im archivierten Stechen_Karten_MMO/server-Projekt.
PM2-Logs belegen ca. 2,5G.
JetBrains-Cache belegt ca. 3,1G.
```

Wichtige spätere Entscheidungen:

```text
Welches archivierte Projekt ist die relevante Ausgangsbasis für stechen-mmo?
Soll Stechen_Karten_MMO/server migriert werden?
Soll stechen-node-server oder stechengameserver erhalten bleiben?
Soll die alte .env-Datei gesichert und anschließend rotiert/entfernt werden?
Sollen alte PM2-Logs archiviert oder gelöscht werden?
Sollen große Caches bereinigt werden?
Soll ein neuer sauberer Zielpfad unter /srv oder /opt angelegt werden?
```

Noch keine Änderung durchgeführt:

```text
Keine Projektdateien gelesen außer Metadaten.
Keine .env-Inhalte geöffnet.
Keine Dateien gelöscht.
Keine Verzeichnisse verschoben.
Keine Bereinigung durchgeführt.
Dieser Block war rein lesend.
```

---

## 33. Verifizierte Inventurdaten: Block 13 - APT / Pakete / Repositories

**Erfasst am:** 2026-06-13  
**Quelle:** Read-Only-Inventur auf HomeServer `kighserv`

### 33.1 Betriebssystem

`/etc/os-release`:

```text
PRETTY_NAME="Ubuntu 22.04.5 LTS"
NAME="Ubuntu"
VERSION_ID="22.04"
VERSION="22.04.5 LTS (Jammy Jellyfish)"
VERSION_CODENAME=jammy
ID=ubuntu
ID_LIKE=debian
UBUNTU_CODENAME=jammy
```

Bewertung:

```text
Der Server läuft auf Ubuntu 22.04.5 LTS (Jammy Jellyfish).
```

### 33.2 APT Paketquellen

Aktive Ubuntu-Standardquellen:

```text
deb http://de.archive.ubuntu.com/ubuntu/ jammy main restricted
deb http://de.archive.ubuntu.com/ubuntu/ jammy-updates main restricted
deb http://de.archive.ubuntu.com/ubuntu/ jammy universe
deb http://de.archive.ubuntu.com/ubuntu/ jammy-updates universe
deb http://de.archive.ubuntu.com/ubuntu/ jammy multiverse
deb http://de.archive.ubuntu.com/ubuntu/ jammy-updates multiverse
deb http://de.archive.ubuntu.com/ubuntu/ jammy-backports main restricted universe multiverse
deb http://security.ubuntu.com/ubuntu/ jammy-security main restricted
deb http://security.ubuntu.com/ubuntu/ jammy-security universe
deb http://security.ubuntu.com/ubuntu/ jammy-security multiverse
```

Aktive Fremd-/Zusatzrepositories:

```text
/etc/apt/sources.list.d/mongodb-org-7.0.list:
deb [ arch=amd64,arm64 signed-by=/usr/share/keyrings/mongodb-server-7.0.gpg ] https://repo.mongodb.org/apt/ubuntu jammy/mongodb-org/7.0 multiverse

/etc/apt/sources.list.d/nodesource.list:
deb [arch=amd64 signed-by=/usr/share/keyrings/nodesource.gpg] https://deb.nodesource.com/node_20.x nodistro main

/etc/apt/sources.list.d/ondrej-ubuntu-php-jammy.list:
deb https://ppa.launchpadcontent.net/ondrej/php/ubuntu/ jammy main

/etc/apt/sources.list.d/symfony-stable.list:
deb [signed-by=/usr/share/keyrings/symfony-stable-archive-keyring.gpg] https://dl.cloudsmith.io/public/symfony/stable/deb/ubuntu jammy main

/etc/apt/sources.list.d/webmin.list:
deb [signed-by=/usr/share/keyrings/ubuntu-webmin-developers.gpg] https://download.webmin.com/download/newkey/repository stable contrib
```

Bewertung:

```text
Neben den Ubuntu-Standardquellen sind mehrere externe Paketquellen eingebunden.
Diese liefern insbesondere MongoDB, Node.js, PHP 8.4, Symfony-Komponenten und Webmin.
```

Pflegehinweis:

```text
Fremdrepositories erhöhen den Wartungs- und Sicherheitsprüfungsaufwand.
Für die Zielarchitektur sollte geprüft werden, welche Repositories dauerhaft benötigt werden.
```

### 33.3 APT Keyring-/GPG-Metadaten

Erfasste GPG-Dateien unter `/etc/apt`:

```text
/etc/apt/trusted.gpg.d/ubuntu-keyring-2018-archive.gpg
/etc/apt/trusted.gpg.d/ubuntu-keyring-2012-cdimage.gpg
/etc/apt/trusted.gpg.d/ondrej-ubuntu-php.gpg
```

Bewertung:

```text
Der Ondřej-PHP-Key liegt unter trusted.gpg.d.
Weitere Repositories referenzieren signed-by-Dateien unter /usr/share/keyrings, wurden mit dieser Suchgrenze nicht vollständig als Metadaten ausgegeben.
```

### 33.4 Ausgewählte installierte Serverpakete

Relevante installierte Pakete:

```text
apache2 2.4.52-1ubuntu4.21
certbot 1.21.0-1build1
mariadb-server 10.6.23-0ubuntu0.22.04.1
mongodb-org 7.0.31
mongodb-mongosh 2.8.2
nodejs 20.20.2-1nodesource1
php8.4 8.4.20-1+ubuntu22.04.1+deb.sury.org+1
php8.4-fpm 8.4.20-1+ubuntu22.04.1+deb.sury.org+1
phpmyadmin 5.1.1+dfsg1-5ubuntu1
samba 4.15.13+dfsg-0ubuntu1.12
snapd 2.74.1+ubuntu22.04.4
ufw 0.36.1-4ubuntu0.1
vsftpd 3.0.5-0ubuntu1.1
webmin 2.630
```

Bewertung:

```text
Die installierten Pakete bestätigen die zuvor erfassten aktiven Dienste und Toolchains.
Apache, PHP, MariaDB, MongoDB, Node.js, Samba, FTP und Webmin sind paketbasiert installiert.
```

### 33.5 PHP-Pakete

Relevante PHP-8.4-Pakete:

```text
php8.4
php8.4-apcu
php8.4-bz2
php8.4-cgi
php8.4-cli
php8.4-common
php8.4-curl
php8.4-fpm
php8.4-gd
php8.4-imagick
php8.4-intl
php8.4-mbstring
php8.4-mysql
php8.4-oauth
php8.4-opcache
php8.4-readline
php8.4-xml
php8.4-xsl
php8.4-zip
```

Weitere PHP-/phpMyAdmin-nahe Pakete:

```text
phpmyadmin
php-mysql
php-json
php-google-recaptcha
php-twig
php-twig-i18n-extension
php-tcpdf
php-phpmyadmin-sql-parser
php-phpmyadmin-motranslator
php-phpmyadmin-shapefile
php-phpseclib
php-symfony-*
```

Bewertung:

```text
PHP 8.4 ist vollständig mit CLI, CGI, FPM, MySQL, XML, GD, Imagick, Intl, Mbstring, Zip und OPcache installiert.
phpMyAdmin ist installiert und erklärt die aktive Apache-Konfiguration aus Block 7.
```

### 33.6 Node / MongoDB / Webmin Pakete

Node.js:

```text
nodejs 20.20.2-1nodesource1
```

MongoDB:

```text
mongodb-database-tools 100.16.1
mongodb-mongosh 2.8.2
mongodb-org 7.0.31
mongodb-org-database 7.0.31
mongodb-org-database-tools-extra 7.0.31
mongodb-org-mongos 7.0.31
mongodb-org-server 7.0.31
mongodb-org-shell 7.0.31
mongodb-org-tools 7.0.31
```

Webmin:

```text
webmin 2.630
```

Bewertung:

```text
Node.js stammt aus NodeSource.
MongoDB stammt aus dem offiziellen MongoDB-org Repository.
Webmin stammt aus dem Webmin-Repository.
```

### 33.7 Manuell markierte relevante Pakete

Auszug aus `apt-mark showmanual`:

```text
apache2
certbot
libapache2-mod-php
mariadb-server
mongodb-org
nodejs
openssh-server
php8.4
php8.4-apcu
php8.4-cgi
php8.4-curl
php8.4-fpm
php8.4-gd
php8.4-imagick
php8.4-intl
php8.4-mbstring
php8.4-mysql
php8.4-oauth
php8.4-xml
php8.4-xsl
php8.4-zip
php-json
phpmyadmin
pipx
python3
python3-dev
python3-pip
python3-venv
samba
vsftpd
webmin
```

Bewertung:

```text
Viele Server- und Entwicklungsbestandteile sind manuell markiert.
Dies ist wichtig für spätere Paketbereinigung, da diese Pakete nicht automatisch als ungenutzte Abhängigkeiten entfernt würden.
```

### 33.8 Aktualisierbare Pakete

Auszug relevanter aktualisierbarer Pakete:

```text
cloud-init 25.3 -> 26.1
fwupd 1.7.9 -> 2.0.20
iproute2 5.15.0 -> 5.15.0-1ubuntu2.1
mongodb-org 7.0.31 -> 7.0.37
mongodb-mongosh 2.8.2 -> 2.8.3
php8.4 8.4.20 -> 8.4.22
php8.4-cli 8.4.20 -> 8.4.22
php8.4-fpm 8.4.20 -> 8.4.22
php8.4-mysql 8.4.20 -> 8.4.22
php8.4-opcache 8.4.20 -> 8.4.22
snapd 2.74.1 -> 2.75.2
webmin 2.630 -> 2.641
```

Bewertung:

```text
Zum Inventurzeitpunkt sind mehrere Pakete aktualisierbar.
Besonders relevant sind MongoDB, PHP 8.4, snapd und Webmin.
Während der Inventur wurden keine Updates durchgeführt.
```

### 33.9 Block-13-Zwischenfazit

Bestätigter Stand:

```text
Ubuntu 22.04.5 LTS
Mehrere aktive Fremdrepositories
NodeSource für Node.js 20
Ondřej-PHP-PPA für PHP 8.4
MongoDB-org Repository für MongoDB 7.0
Webmin-Repository für Webmin
Symfony-Repository vorhanden
Serverpakete für Apache, PHP, MariaDB, MongoDB, Samba, FTP, Webmin installiert
Mehrere relevante Pakete aktualisierbar
```

Wichtige spätere Entscheidungen:

```text
Welche Fremdrepositories sollen dauerhaft aktiv bleiben?
Wird PHP 8.4 samt Ondřej-PPA benötigt?
Wird MongoDB 7.0 samt Repository benötigt?
Wird NodeSource für Node.js dauerhaft genutzt?
Wird Webmin samt Repository benötigt?
Wird Symfony-Repository noch benötigt?
Soll phpMyAdmin behalten oder entfernt werden?
Wann und in welcher Reihenfolge werden Updates durchgeführt?
```

Noch keine Änderung durchgeführt:

```text
Keine Paketquellen geändert.
Keine Pakete installiert.
Keine Pakete entfernt.
Keine Updates durchgeführt.
Keine apt clean/autoremove-Aktion durchgeführt.
Dieser Block war rein lesend.
```

---

## 34. Verifizierte Inventurdaten: Block 14 - Cron / Timer / Autostarts

**Erfasst am:** 2026-06-13  
**Quelle:** Read-Only-Inventur auf HomeServer `kighserv`

### 34.1 Systemd Timer

`systemctl list-timers --all` zeigte 17 Timer.

Relevante aktive/geplante Timer:

```text
phpsessionclean.timer
apt-daily.timer
dpkg-db-backup.timer
logrotate.timer
certbot.timer
fwupd-refresh.timer
e2scrub_all.timer
apt-daily-upgrade.timer
motd-news.timer
man-db.timer
update-notifier-download.timer
systemd-tmpfiles-clean.timer
fstrim.timer
update-notifier-motd.timer
apport-autoreport.timer
snapd.snap-repair.timer
ua-timer.timer
```

Bewertung:

```text
Es sind überwiegend Standard-Wartungstimer aktiv.
Projektbezogene Timer wurden nicht sichtbar.
```

### 34.2 Enabled Timer Units

`systemctl list-unit-files --type=timer --state=enabled` zeigte 19 enabled Timer:

```text
apport-autoreport.timer
apt-daily-upgrade.timer
apt-daily.timer
certbot.timer
dpkg-db-backup.timer
e2scrub_all.timer
fstrim.timer
fwupd-refresh.timer
logrotate.timer
man-db.timer
mdcheck_continue.timer
mdcheck_start.timer
mdmonitor-oneshot.timer
motd-news.timer
phpsessionclean.timer
snapd.snap-repair.timer
ua-timer.timer
update-notifier-download.timer
update-notifier-motd.timer
```

Bewertung:

```text
Die enabled Timer entsprechen typischen Ubuntu-/Paketwartungsaufgaben.
certbot, PHP Session Cleaning, Snap Repair und Firmware Update Refresh sind für spätere Architektur-/Bereinigungsentscheidungen relevant.
```

### 34.3 User- und Root-Crontabs

Aktueller Benutzer:

```text
no user crontab for current user
```

Root:

```text
no root crontab
```

Bewertung:

```text
Für kighlander und root sind keine persönlichen Crontabs eingerichtet.
```

### 34.4 System-Cron-Verzeichnisse

`/etc/cron.d`:

```text
certbot
e2scrub_all
php
.placeholder
```

`/etc/cron.daily`:

```text
apache2
apport
apt-compat
dpkg
logrotate
man-db
samba
.placeholder
```

`/etc/cron.hourly`:

```text
.placeholder
```

`/etc/cron.weekly`:

```text
man-db
.placeholder
```

`/etc/cron.monthly`:

```text
.placeholder
```

Bewertung:

```text
Es sind keine projektspezifischen Cron-Dateien sichtbar.
Die vorhandenen Einträge stammen von installierten System-/Serverpaketen.
```

### 34.5 System-Cron-Inhalte

`/etc/crontab` enthält die Standard-run-parts-Zeitpläne:

```text
17 *    * * *   root    cd / && run-parts --report /etc/cron.hourly
25 6    * * *   root    test -x /usr/sbin/anacron || ( cd / && run-parts --report /etc/cron.daily )
47 6    * * 7   root    test -x /usr/sbin/anacron || ( cd / && run-parts --report /etc/cron.weekly )
52 6    1 * *   root    test -x /usr/sbin/anacron || ( cd / && run-parts --report /etc/cron.monthly )
```

`/etc/cron.d/certbot`:

```text
0 */12 * * * root test -x /usr/bin/certbot -a \! -d /run/systemd/system && perl -e 'sleep int(rand(43200))' && certbot -q renew
```

Hinweis:

```text
Der certbot-Cronjob wird laut Kommentar bei systemd-Systemen nicht ausgeführt, da certbot.timer Vorrang hat.
```

`/etc/cron.d/e2scrub_all`:

```text
30 3 * * 0 root test -e /run/systemd/system || SERVICE_MODE=1 /usr/lib/x86_64-linux-gnu/e2fsprogs/e2scrub_all_cron
10 3 * * * root test -e /run/systemd/system || SERVICE_MODE=1 /sbin/e2scrub_all -A -r
```

`/etc/cron.d/php`:

```text
09,39 *     * * *     root   [ -x /usr/lib/php/sessionclean ] && if [ ! -d /run/systemd/system ]; then /usr/lib/php/sessionclean; fi
```

Bewertung:

```text
Die Cron-Dateien enthalten Standard-Fallbacks für systemd-lose Systeme.
Auf diesem Server übernehmen systemd Timer die entsprechenden Aufgaben.
```

### 34.6 Cron-Script-Metadaten

Erfasste Script-Metadaten:

```text
/etc/cron.daily/apache2
/etc/cron.daily/apport
/etc/cron.daily/apt-compat
/etc/cron.daily/dpkg
/etc/cron.daily/logrotate
/etc/cron.daily/man-db
/etc/cron.daily/samba
/etc/cron.weekly/man-db
```

Bewertung:

```text
Die vorhandenen ausführbaren Cron-Scripte gehören zu Standardpaketen bzw. installierten Serverdiensten.
Keine stechen-/node-/pm2-spezifischen Cron-Scripte wurden sichtbar.
```

### 34.7 rc.local / Init-Autostart

Für `/etc/rc.local` wurde keine Datei sichtbar ausgegeben.

`/etc/init.d` enthält Init-Scripte u. a. für:

```text
apache2
apache-htcacheclean
apparmor
apport
cron
dbus
dnsmasq
irqbalance
lm-sensors
lvm2
mariadb
nmbd
open-iscsi
open-vm-tools
php8.4-fpm
rsync
samba-ad-dc
smbd
ssh
ufw
unattended-upgrades
vsftpd
x11-common
```

Bewertung:

```text
Es wurden keine projektspezifischen rc.local-Autostarts sichtbar.
Die Init-Scripte entsprechen installierten Paketen.
```

### 34.8 User-systemd-Units

Ausgabe:

```text
Keine Dateien sichtbar.
```

Bewertung:

```text
Für den Benutzer kighlander wurden keine User-systemd-Units unter ~/.config/systemd/user gefunden.
```

### 34.9 Custom systemd Units

Gefundene Dateien unter `/etc/systemd/system`:

```text
snap-lxd-38469.mount
snap-lxd-38800.mount
snap-snapd-26382.mount
snap-core20-2769.mount
snap-snapd-26865.mount
snap-core20-2866.mount
snap.lxd.daemon.unix.socket
snap.lxd.user-daemon.unix.socket
sshd-keygen@.service.d/disable-sshd-keygen-if-cloud-init-active.conf
snap.lxd.user-daemon.service
snap.lxd.activate.service
snap.lxd.daemon.service
pm2-root.service
```

Bewertung:

```text
Es existieren Snap-/LXD-bezogene systemd Units.
Zusätzlich existiert weiterhin die Custom Unit pm2-root.service.
Laut Block 6 ist pm2-root.service inactive und disabled.
```

### 34.10 Block-14-Zwischenfazit

Bestätigter Stand:

```text
Keine User-Crontabs.
Kein Root-Crontab.
Keine projektspezifischen Cronjobs sichtbar.
Keine User-systemd-Units sichtbar.
Keine rc.local sichtbar.
Standard-systemd-Timer aktiv.
certbot.timer aktiv.
phpsessionclean.timer aktiv.
snapd.snap-repair.timer enabled.
pm2-root.service-Datei existiert weiterhin, ist aber laut Block 6 inactive/disabled.
```

Wichtige spätere Entscheidungen:

```text
Soll certbot installiert/aktiv bleiben?
Soll PHP Session Cleaning relevant bleiben?
Soll Snap/LXD weiter genutzt werden?
Soll pm2-root.service entfernt werden?
Soll ein sauberer systemd-Service für stechen-mmo erstellt werden?
Soll Cron bewusst leer bleiben und Autostart nur über systemd erfolgen?
```

Noch keine Änderung durchgeführt:

```text
Keine Crontabs geändert.
Keine Timer aktiviert oder deaktiviert.
Keine systemd Units geändert.
Keine Dateien gelöscht.
Dieser Block war rein lesend.
```

---

## 35. Verifizierte Inventurdaten: Block 15 - Backups / Logs / große Dateien

**Erfasst am:** 2026-06-13  
**Quelle:** Read-Only-Inventur auf HomeServer `kighserv`

### 35.1 Backup-artige Pfade

Unter `/home/kighlander`:

```text
/home/kighlander/.pm2/_backup
/home/kighlander/_archive
/home/kighlander/.pm2/_backup/dump.pm2.bak.before-cleanup-2026-06-13-173929
/home/kighlander/.pm2/dump.pm2.bak
/home/kighlander/ApacheGUI-1.12.0.tar.gz
```

Unter `/var/backups`:

```text
dpkg.arch.*.gz
dpkg.diversions.*.gz
dpkg.statoverride.*.gz
alternatives.tar.*.gz
apt.extended_states.*.gz
dpkg.status.*.gz
```

Unter `/var/lib` wurden hauptsächlich Paketlisten-/APT-/dpkg-bezogene Dateien sowie ein MariaDB-Log gefunden:

```text
/var/lib/mysql/ddl_recovery-backup.log
/var/lib/systemd/deb-systemd-helper-enabled/timers.target.wants/dpkg-db-backup.timer
/var/lib/apt/lists/*
/var/lib/dpkg/*
```

Unter `/srv` und `/opt`:

```text
Keine relevanten Backup-Treffer.
```

Unter `/root`:

```text
/root/.launchpadlib/...ondrej...getSigningKeyData...
```

Bewertung:

```text
Es wurden keine klaren Projekt- oder Datenbank-Backups wie .sql, .dump, .bson oder vergleichbare produktive Backups sichtbar.
Die sichtbaren Backups sind überwiegend System-/Paketverwaltungs-Backups.
Das Home-Verzeichnis enthält ein Legacy-Archiv und PM2-Dump-Backups.
```

### 35.2 Datenbank-Backup-Suche

Die Suche nach Datenbank-Backup-Metadaten fand keine projektbezogenen Datenbankexports.

Gefunden wurden im Wesentlichen:

```text
/var/backups/dpkg*.gz
/var/backups/alternatives*.gz
/var/backups/apt.extended_states*.gz
/home/kighlander/ApacheGUI-1.12.0.tar.gz
```

Bewertung:

```text
Es gibt keinen sichtbaren aktuellen Datenbankexport für MariaDB oder MongoDB.
Vor Bereinigung oder Migration sollte daher ein dedizierter Backup-/Export-Schritt eingeplant werden.
```

### 35.3 Log-Verzeichnisgrößen

Erfasste Größen:

```text
/var/log/dist-upgrade              4,0K
/var/log/landscape                 4,0K
/var/log/mysql                     4,0K
/var/log/private                   4,0K
/var/log/dbconfig-common           8,0K
/var/log/letsencrypt               56K
/var/log/apache2                   68K
/var/log/samba                     116K
/var/log/unattended-upgrades       144K
/var/log/apt                       196K
/var/log/installer                 1,6M
/var/log/mongodb                   397M
/var/log/journal                   1,1G
/var/log                           1,4G
/home/kighlander/.pm2/logs         2,5G
```

Bewertung:

```text
Die größten Logbereiche sind PM2-Logs, systemd Journal und MongoDB-Logs.
Apache-, Samba-, LetsEncrypt- und sonstige Paketlogs sind vergleichsweise klein.
```

### 35.4 Große Dateien über 100 MB

Unter `/home/kighlander`:

```text
/home/kighlander/.cache/JetBrains/RemoteDev/dist/.../database-plugin.jar        140012588
/home/kighlander/.cache/JetBrains/PhpStorm2024.1/index/.../shared-index.chunks  149484423
/home/kighlander/.cache/JetBrains/RemoteDev/dist/.../jbr/lib/libcef.so          209861688
/home/kighlander/.cache/JetBrains/RemoteDev/dist/.../lib/app-client.jar         222951295
/home/kighlander/.pm2/logs/stechen-mmo-out.log                                  2651955200
```

Unter `/var/log`:

```text
/var/log/mongodb/mongod.log                                                     415996683
```

Unter `/var/lib`, `/srv`, `/opt`:

```text
Keine Dateien über 100 MB sichtbar.
```

Bewertung:

```text
Der größte einzelne Speicherfresser ist /home/kighlander/.pm2/logs/stechen-mmo-out.log mit ca. 2,65 GB.
MongoDB schreibt ein großes Log unter /var/log/mongodb/mongod.log.
Mehrere große JetBrains-/RemoteDev-Cache-Dateien sind vorhanden.
```

### 35.5 Logrotate-Konfigurationen

Gefundene Dateien unter `/etc/logrotate.d`:

```text
alternatives
apache2
apport
apt
bootlog
certbot
cloud-init
dbconfig-common
dpkg
mariadb
php8.4-fpm
rsyslog
samba
ubuntu-pro-client
unattended-upgrades
ufw
vsftpd
wtmp
btmp
```

Bewertung:

```text
Logrotate ist für viele Systemdienste vorhanden.
Eine sichtbare Logrotate-Konfiguration für PM2 wurde nicht gefunden.
Eine sichtbare Logrotate-Konfiguration für MongoDB wurde nicht gefunden.
Eine sichtbare Logrotate-Konfiguration für Webmin wurde nicht gefunden.
```

### 35.6 Ausgewählte Logrotate-Inhalte

Globale Konfiguration `/etc/logrotate.conf`:

```text
weekly
rotate 4
create
include /etc/logrotate.d
compress ist global auskommentiert
```

Apache:

```text
/var/log/apache2/*.log /home/kighlander/www-log/*/*.log {
    daily
    rotate 14
    compress
    delaycompress
    create 640 root adm
    postrotate reload apache2
}
```

PHP-FPM:

```text
/var/log/php8.4-fpm.log {
    rotate 12
    weekly
    compress
    delaycompress
}
```

rsyslog:

```text
/var/log/syslog
/var/log/mail.*
/var/log/daemon.log
/var/log/kern.log
/var/log/auth.log
/var/log/user.log
...
rotate 4
weekly
compress
delaycompress
```

Samba:

```text
/var/log/samba/log.smbd
/var/log/samba/log.nmbd
/var/log/samba/log.samba
weekly
rotate 7
compress
delaycompress
```

vsftpd:

```text
/var/log/vsftpd.log
rotate 4
weekly
create 640 root adm
```

Bewertung:

```text
Die Standarddienste sind weitgehend logrotate-abgedeckt.
PM2-Logs und MongoDB-Logs erscheinen als wichtigste Lücken.
```

### 35.7 Block-15-Zwischenfazit

Bestätigter Stand:

```text
Keine sichtbaren produktiven Projekt-/Datenbank-Backups gefunden.
System-/Paket-Backups unter /var/backups vorhanden.
Legacy-Archiv unter /home/kighlander/_archive vorhanden.
PM2-Dump-Backups vorhanden.
ApacheGUI-1.12.0.tar.gz liegt im Home-Verzeichnis.
PM2-Logdatei stechen-mmo-out.log ist ca. 2,65 GB groß.
MongoDB-Log ist ca. 416 MB groß.
systemd Journal belegt ca. 1,1 GB.
Keine sichtbare PM2-Logrotate-Konfiguration.
Keine sichtbare MongoDB-Logrotate-Konfiguration.
```

Wichtige spätere Entscheidungen:

```text
Vor jeder Bereinigung dedizierte Backups/Exports erstellen.
MariaDB und MongoDB gezielt exportieren, falls Daten erhalten bleiben sollen.
PM2-Logs archivieren oder löschen.
PM2-Logrotate oder systemd-journald-Strategie einführen.
MongoDB-Logging/Rotation prüfen.
systemd Journal-Grenzen setzen.
JetBrains-/RemoteDev-Caches bereinigen.
ApacheGUI-Archiv bewerten und ggf. entfernen.
```

Noch keine Änderung durchgeführt:

```text
Keine Backups erstellt.
Keine Logs gelöscht.
Keine Dateien verschoben.
Keine Logrotate-Konfiguration geändert.
Keine Journal-Konfiguration geändert.
Dieser Block war rein lesend.
```

---

## 36. Inventurabschluss / Risikoliste / Bereinigungskandidaten / empfohlene Zielarchitektur

**Erstellt am:** 2026-06-13  
**Quelle:** Zusammenfassung der verifizierten Inventurblöcke 1 bis 15 auf HomeServer `kighserv`

### 36.1 Inventurstatus

Die technische Read-Only-Inventur des HomeServers `kighserv` ist abgeschlossen.

Erfasste Bereiche:

```text
Systembasis / OS / Kernel
Hardware / CPU / RAM
Datenträger / Partitionen / LVM
Netzwerk / IP-Adressen / Ports
Firewall / UFW
systemd-Dienste
Apache / PHP / phpMyAdmin
MariaDB
MongoDB
Samba
vsftpd
Webmin
Node.js / npm / nvm / PM2
Benutzer / Gruppen / SSH / sudoers
Projektpfade / Webroots / Archive
APT-Repositories / installierte Pakete
Cron / systemd Timer / Autostarts
Backups / Logs / große Dateien
```

Bewertung:

```text
Die Inventur ist vollständig genug, um in die Bewertungs-, Bereinigungs- und Zielarchitekturphase überzugehen.
Es wurden während der Inventur keine produktiven Änderungen durchgeführt.
```

### 36.2 System-Kurzprofil

Bestätigter Systemstand:

```text
Hostname: kighserv
Hardware: Lenovo ideacentre 310S-08ASR
CPU: AMD A9-9425
RAM: ca. 15 GiB
OS: Ubuntu 22.04.5 LTS
Kernel: 5.15.0-179-generic
Root-Dateisystem: LVM, ca. 100 GiB
Freier LVM-Spielraum: ca. 120,52 GiB
```

Bewertung:

```text
Die Hardware ist für einen kleinen privaten HomeServer ausreichend.
Die freie LVM-Kapazität eröffnet Spielraum für separate Daten-, Backup- oder Projekt-Volumes.
```

### 36.3 Dienstelandschaft

Installierte bzw. relevante Serverdienste:

```text
Apache 2.4.52
PHP 8.4.20 FPM/CLI/CGI
MariaDB 10.6.23
MongoDB 7.0.31
Node.js 20.20.2 systemweit
NVM mit Node 16.20.2 und 20.18.3
PM2 5.4.3
Samba
vsftpd
Webmin
OpenSSH
UFW
snapd / LXD
certbot
phpMyAdmin
```

Bewertung:

```text
Der Server enthält mehrere parallele Web-, Datenbank-, Entwicklungs- und Administrationskomponenten.
Ein Teil davon wirkt historisch bzw. aus früheren Setups übernommen.
Für einen sauberen stechen-mmo-Zielbetrieb sollte die Dienstelandschaft reduziert werden.
```

### 36.4 Projektzustand

Aktuell sichtbarer Projektstand:

```text
Keine aktiven stechen-mmo Deployments in /var/www, /srv oder /opt.
Alte Projektordner liegen unter:
/home/kighlander/_archive/legacy-home-2026-06-13/
```

Gefundene relevante Legacy-Projekte:

```text
Stechen_Karten_MMO
stechen-node-server
stechengameserver
```

Relevante Projektartefakte:

```text
/home/kighlander/_archive/legacy-home-2026-06-13/Stechen_Karten_MMO/.git
/home/kighlander/_archive/legacy-home-2026-06-13/Stechen_Karten_MMO/server/package.json
/home/kighlander/_archive/legacy-home-2026-06-13/Stechen_Karten_MMO/server/.env
/home/kighlander/_archive/legacy-home-2026-06-13/stechen-node-server/package.json
/home/kighlander/_archive/legacy-home-2026-06-13/stechengameserver/package.json
```

Bewertung:

```text
Das aktuelle Projekt muss aus den archivierten Artefakten rekonstruiert oder neu sauber deployt werden.
Die alte .env-Datei ist sicherheitsrelevant und sollte nicht ungeprüft weiterverwendet werden.
```

### 36.5 Datenbanken

MariaDB:

```text
Version: 10.6.23
Bind: lokal
Datenbank hhb_database vorhanden
```

MongoDB:

```text
Version: 7.0.31
Bind: lokal
Datenbank gamedb vorhanden
```

Bewertung:

```text
Es existieren mindestens zwei potenziell projekt-/altprojektrelevante Datenbanken.
Es wurden keine produktiven Datenbankexports gefunden.
Vor jeder Bereinigung oder Migration müssen Datenbank-Dumps erstellt werden.
```

### 36.6 Netzwerk und Sicherheit

Bestätigter Sicherheitsstand:

```text
UFW ist aktiv.
SSH lauscht auf Port 22 IPv4 und IPv6.
SSH Public-Key-Authentifizierung ist aktiv.
SSH Passwort-Authentifizierung ist aktiv.
Root-Login ist ohne Passwort bzw. per Schlüssel erlaubt.
Webmin ist aktiv auf Port 10000 mit SSL.
Samba ist aktiv und teilt /home/kighlander.
vsftpd ist aktiv und erlaubt lokale Logins unverschlüsselt.
```

Wichtige Risiken:

```text
PasswordAuthentication yes
PermitRootLogin without-password
X11Forwarding yes
AllowTcpForwarding yes
vsftpd ohne TLS
Webmin als zusätzliche Adminoberfläche
Samba-Share auf /home/kighlander mit guest ok
alte UFW-Regeln für 192.168.1.0/24 trotz aktuellem Netz 192.168.178.0/24
kighlander ist Mitglied der Gruppe lxd
DSA SSH Hostkey vorhanden
/home/kighlander ist für others betretbar (--x)
```

Bewertung:

```text
Der Server ist nicht offen unkontrolliert dokumentiert worden, enthält aber mehrere typische HomeServer-Altlasten.
Die größte Sicherheitsverbesserung wäre die Reduktion externer Zugänge und die Härtung von SSH, Samba, FTP und Webmin.
```

### 36.7 Paketquellen und Updatezustand

Aktive Fremdrepositories:

```text
MongoDB 7.0 Repository
NodeSource Node 20.x
Ondřej PHP PPA
Symfony stable
Webmin Repository
```

Relevante aktualisierbare Pakete:

```text
MongoDB 7.0.31 -> 7.0.37
PHP 8.4.20 -> 8.4.22
Webmin 2.630 -> 2.641
snapd 2.74.1 -> 2.75.2
weitere Ubuntu-Systempakete
```

Bewertung:

```text
Die Paketbasis ist wartbar, aber durch mehrere Fremdrepositories komplexer als nötig.
Vor Updates sollte entschieden werden, welche Komponenten überhaupt bleiben.
```

### 36.8 Speicher- und Logzustand

Auffällige Speicherbereiche:

```text
/home/kighlander/.pm2/logs                 ca. 2,5G
/home/kighlander/.pm2/logs/stechen-mmo-out.log ca. 2,65G
/var/log/journal                           ca. 1,1G
/var/log/mongodb/mongod.log                ca. 416M
/home/kighlander/.cache                    ca. 3,2G
/home/kighlander/.cache/JetBrains          ca. 3,1G
/home/kighlander/.vscode-server            ca. 668M
/home/kighlander/.nvm                      ca. 322M
```

Logrotate-Lücken:

```text
Keine sichtbare PM2-Logrotate-Konfiguration.
Keine sichtbare MongoDB-Logrotate-Konfiguration.
Keine sichtbare Webmin-Logrotate-Konfiguration.
```

Bewertung:

```text
Die größten kurzfristigen Bereinigungskandidaten sind PM2-Logs, JetBrains-Caches, systemd Journal und MongoDB-Log.
Vor Löschung sollte bei relevanten Logs entschieden werden, ob eine Archivierung nötig ist.
```

### 36.9 Backupzustand

Gefundene Backups:

```text
System-/Paketverwaltungs-Backups unter /var/backups
PM2-Dump-Backups unter /home/kighlander/.pm2
Legacy-Archiv unter /home/kighlander/_archive
```

Nicht gefunden:

```text
Kein sichtbarer aktueller MariaDB-Dump.
Kein sichtbarer aktueller MongoDB-Dump.
Kein sichtbarer vollständiger Projekt-Backupstand außerhalb des Legacy-Archivs.
```

Bewertung:

```text
Vor der ersten echten Änderung an Diensten, Datenbanken, Projektdateien oder Logs muss ein Backup-/Export-Schritt erfolgen.
```

### 36.10 Bereinigungskandidaten

Hohe Priorität:

```text
vsftpd deaktivieren/entfernen, falls nicht benötigt
Webmin deaktivieren/entfernen, falls nicht benötigt
Samba-Share prüfen und härten
SSH Passwortlogin deaktivieren, wenn Key-Zugang verifiziert ist
alte UFW-Regeln bereinigen
PM2-Logs archivieren/löschen
MongoDB-Logrotation einrichten oder Log bereinigen
Datenbankexports erstellen
```

Mittlere Priorität:

```text
PM2 Altservice pm2-root.service entfernen oder ersetzen
LXD/Snap-Nutzung bewerten
Ondřej PHP PPA nur behalten, wenn PHP 8.4 benötigt wird
Symfony-Repository bewerten
phpMyAdmin bewerten
certbot bewerten
Apache/PHP-Rolle klären
JetBrains-/VSCode-/NVM-Caches bereinigen
ApacheGUI-1.12.0.tar.gz entfernen, falls nicht benötigt
DSA SSH Hostkey entfernen/deaktivieren
```

Niedrige Priorität:

```text
Shell-Profil-Backups aufräumen
alte PHP-Vergleichsdateien archivieren oder entfernen
known_hosts.old bewerten
/home/kighlander other-execute-Bit prüfen
```

### 36.11 Empfohlene Zielarchitektur für stechen-mmo

Empfohlener Zielpfad:

```text
/srv/stechen-mmo
```

Begründung:

```text
/srv ist für service-/serverbezogene Daten geeignet.
/var/www sollte nur genutzt werden, wenn Apache direkt statische Dateien oder PHP ausliefert.
/opt wäre eher für fremde Binärsoftware geeignet.
Das Home-Verzeichnis sollte nicht als produktiver Deploymentpfad dienen.
```

Empfohlene Runtime-Strategie:

```text
Eine Node.js-Version festlegen.
Bevorzugt systemweites Node.js aus NodeSource oder alternativ bewusst NVM vermeiden.
Produktivbetrieb über systemd-Service statt PM2, sofern keine PM2-spezifischen Features benötigt werden.
Logs über journald mit Limits oder explizite Logrotate-Konfiguration.
```

Empfohlene Datenbankstrategie:

```text
Zuerst klären, ob stechen-mmo MariaDB, MongoDB oder beide benötigt.
Falls nur eine Datenbank benötigt wird, die andere perspektivisch entfernen.
Vorher Dumps erstellen.
```

Empfohlene Web-/Reverse-Proxy-Strategie:

```text
Falls stechen-mmo ein Node-Websocket-/HTTP-Service ist:
Apache entweder als Reverse Proxy konfigurieren oder Apache entfernen/deaktivieren, wenn nicht benötigt.
Falls TLS gebraucht wird:
Zertifikatsstrategie über certbot oder internes/LAN-only Setup entscheiden.
```

Empfohlene Sicherheitsstrategie:

```text
SSH Key-only.
UFW nur für benötigte Ports.
Keine unverschlüsselten FTP-Zugänge.
Webmin nur bei echtem Bedarf und dann auf LAN/VPN beschränken.
Samba nur mit klar definiertem Share, ohne guest ok auf Home-Verzeichnis.
Produktiver Dienst mit eigenem Systemuser, z. B. stechen.
```

### 36.12 Empfohlene Reihenfolge Phase 2

Phase 2 sollte nicht direkt mit Löschen beginnen, sondern in dieser Reihenfolge:

```text
1. Zielentscheidung:
   - Welche Projektbasis?
   - Welche Datenbank?
   - Welche Runtime?
   - Welche Dienste bleiben?

2. Sicherung:
   - MariaDB-Dump
   - MongoDB-Dump
   - Archiv der alten Projektordner
   - optional Archiv wichtiger Logs

3. Sicherheitszugang absichern:
   - SSH-Key-Zugang testen
   - zweiten Notfallzugang prüfen
   - erst danach PasswordAuthentication deaktivieren

4. Dienstbereinigung:
   - vsftpd
   - Webmin
   - Samba
   - PM2 Altservice
   - ungenutzte DB/Web-Komponenten

5. Zieldeployment:
   - /srv/stechen-mmo anlegen
   - eigener Dienstuser
   - Code deployen
   - .env neu erstellen
   - systemd-Service erstellen

6. Firewall:
   - UFW-Regeln an Zielarchitektur und aktuelles Netz anpassen

7. Logging:
   - journald Limits setzen
   - PM2-Logs entfernen oder PM2-Logrotate installieren, falls PM2 bleibt
   - MongoDB-Logging begrenzen/rotieren

8. Updates:
   - erst nach Backup und Dienstentscheidung
   - dann gezielt apt upgrade
```

### 36.13 No-Go-Liste vor Backup

Vor einem vollständigen Sicherungsschritt sollte Folgendes nicht durchgeführt werden:

```text
Keine Datenbanken löschen.
Keine Datenbanken droppen.
Keine Projektarchive löschen.
Keine .env-Dateien löschen.
Keine Paketbereinigung mit autoremove.
Keine Logs löschen, falls sie noch für Fehlersuche benötigt werden.
Keine SSH-Härtung ohne getesteten Key-Zugang.
Keine UFW-Regeländerung ohne lokalen oder sicheren alternativen Zugriff.
```

### 36.14 Inventurabschluss-Fazit

Zusammenfassung:

```text
Der HomeServer ist vollständig genug inventarisiert.
Der Ist-Zustand zeigt eine typische historisch gewachsene HomeServer-Umgebung.
Die wichtigsten Risiken liegen bei SSH-Passwortlogin, unverschlüsseltem FTP, Webmin, Samba-Home-Share, alten Firewall-Regeln und fehlender Logrotation für PM2/MongoDB.
Die wichtigsten Betriebsentscheidungen betreffen Node.js-Runtime, Datenbankwahl, Apache/PHP-Rolle und Prozessmanagement.
Die wichtigsten Sofortmaßnahmen vor Änderungen sind Datenbankexports und ein Projekt-/Konfigurationsbackup.
```

Status:

```text
Read-Only-Inventur abgeschlossen.
Bereinigungs- und Zielarchitekturphase kann beginnen.
```
