\# Infrastructure Status



Stand: 2026-06-13



\## Projekt



\*\*Name:\*\* stechen-mmo  

\*\*Repository:\*\* git@github.com:Kighlander1975/stechen-mmo.git



Dieses Dokument beschreibt den aktuellen Infrastrukturstand nach der initialen Inventur, SSH-Härtung, GitHub-SSH-Anbindung und Server-Wartung.



\---



\## 1. HomeServer



\### Hardware



\- Modell: Lenovo ideacentre 310S-08ASR

\- CPU: AMD A9-9425

\- RAM: 15 GiB

\- Hostname: `kighserv`



\### Betriebssystem



\- Distribution: Ubuntu 22.04.5 LTS

\- Kernel nach Wartung: `5.15.0-181-generic`

\- Architektur: x86-64



\### Netzwerk



\- IPv4: `192.168.178.50`

\- SSH-Host-Alias lokal: `kighserv`



\---



\## 2. Systemzustand nach Wartung



Die Server-Wartung wurde am 2026-06-13 durchgeführt und abgeschlossen.



\### Ergebnis



\- System aktualisiert

\- 39 Pakete aktualisiert

\- Neustart durchgeführt

\- Neuer Kernel aktiv

\- Kein weiterer Reboot erforderlich

\- Keine fehlgeschlagenen systemd-Units

\- Alle apt-Pakete aktuell



\### Prüfungen nach Neustart



```text

Kein Reboot erforderlich

0 loaded units listed.

Alle Pakete sind aktuell.

```



\### Speicherstatus



Root-Dateisystem:



```text

/dev/mapper/ubuntu--vg-ubuntu--lv

Größe: ca. 98G

Belegt: ca. 14G

Auslastung: ca. 13.9% - 15%

```



RAM nach Neustart:



```text

15 GiB gesamt

ca. 2% genutzt

Swap: 0%

```



\---



\## 3. SSH-Härtung



Die SSH-Konfiguration wurde gehärtet.



\### Status



\- Passwort-Login deaktiviert

\- Root-Login deaktiviert

\- X11-Forwarding deaktiviert

\- Login per SSH-Key aktiv

\- Zugriff über lokalen SSH-Alias `kighserv`



\### Zugriff



Von der lokalen Windows-Entwicklungsumgebung:



```powershell

ssh kighserv

```



\### Verwendeter Server-Key



Lokaler Key:



```text

kighserv\_ed25519

```



Der Key wird über den Windows `ssh-agent` verwaltet.



\---



\## 4. Lokale SSH-Agent-Konfiguration



Auf Windows wurde der OpenSSH Authentication Agent aktiviert.



\### Status



\- Dienst läuft automatisch

\- StartupType: Automatic

\- Server-Key ist geladen

\- GitHub-Key ist geladen



\### Geladene Schlüssel



```text

kighlander@kighserv

github-kighl

```



\---



\## 5. GitHub-SSH-Integration



Die GitHub-Anbindung wurde von HTTPS auf SSH umgestellt.



\### Repository-Remote



```text

origin git@github.com:Kighlander1975/stechen-mmo.git

```



Prüfung:



```powershell

git remote -v

```



Ergebnis:



```text

origin  git@github.com:Kighlander1975/stechen-mmo.git (fetch)

origin  git@github.com:Kighlander1975/stechen-mmo.git (push)

```



\### GitHub-Key



Lokaler Key:



```text

github\_ed25519

```



GitHub erkennt den Key erfolgreich.



Test:



```powershell

ssh -T git@github.com

```



\---



\## 6. Git für Windows / OpenSSH



Git für Windows wurde so konfiguriert, dass es den Windows-eigenen OpenSSH-Client verwendet.



\### Globale Git-Konfiguration



```powershell

git config --global core.sshCommand "C:/Windows/System32/OpenSSH/ssh.exe"

```



\### Grund



Damit verwendet Git denselben SSH-Agent wie die normale PowerShell-SSH-Verbindung.



\### Ergebnis



```text

git fetch

```



funktioniert ohne wiederholte Passphrase-Abfrage.



\---



\## 7. Laufende Dienste auf dem HomeServer



Nach der Wartung wurden folgende Dienste geprüft.



\### MongoDB



```text

mongod.service

Status: active (running)

Port: 127.0.0.1:27017

```



\### MariaDB



```text

mariadb.service

Status: active (running)

Port: 127.0.0.1:3306

```



\### Apache



```text

apache2.service

Status: active (running)

Port: \*:80

```



\### Webmin



```text

webmin.service

Status: inactive (dead)

Start: disabled

```



Webmin ist installiert, aber nicht aktiv. Dies ist für den aktuellen Stand akzeptiert und aus Sicherheitssicht unkritisch beziehungsweise sogar vorteilhaft, solange Webmin nicht benötigt wird.



\---



\## 8. Offene Ports



Auszug aus `ss -tulpn` nach Wartung:



```text

0.0.0.0:22          SSH

\*:80                Apache HTTP

127.0.0.1:3306      MariaDB lokal

127.0.0.1:27017     MongoDB lokal

127.0.0.53:53       lokaler DNS Resolver

```



\### Bewertung



\- Datenbanken sind nur lokal gebunden.

\- SSH ist erreichbar und gehärtet.

\- HTTP ist über Apache erreichbar.

\- Webmin ist nicht offen.



\---



\## 9. Bewusste Entscheidungen



\### Ubuntu 24.04 Upgrade



Ubuntu meldet:



```text

New release '24.04.4 LTS' available.

```



Das Upgrade wird vorerst nicht durchgeführt.



Begründung:



\- Ubuntu 22.04 LTS ist stabil.

\- Der Server ist aktuell und funktionsfähig.

\- Drittquellen sind aktiv:

&#x20; - NodeSource

&#x20; - MongoDB Repository

&#x20; - Ondřej PHP PPA

&#x20; - Webmin Repository

&#x20; - Symfony Repository

\- Ein Distributionsupgrade soll später separat geplant werden.



\### Ubuntu Pro / ESM Apps



Ubuntu meldet zusätzliche ESM-App-Sicherheitsupdates.



Aktuell wurde Ubuntu Pro nicht aktiviert.  

Dies kann später optional geprüft werden.



\---



\## 10. Projektphasen



\### Abgeschlossen



\- Inventur

\- SSH-Härtung

\- GitHub-SSH-Anbindung

\- Server-Wartung

\- Reboot und Nachprüfung



\### Verschoben auf Phase 2



\- HomeServer-Deployment

\- systemd-Service für `stechen-mmo`

\- produktiver Autostart

\- Deployment-Prozess

\- Serverbetrieb der Anwendung



\### Nächster Fokus



Phase 1 wird zunächst auf der Entwicklerseite fortgeführt:



\- Laravel-Projektbeschreibung

\- grobe Spielidee

\- Entwicklungsziele

\- MVP-Abgrenzung

\- technische Grundstruktur



\---



\## 11. Kurzstatus



```text

Inventur:              abgeschlossen

SSH-Härtung:           abgeschlossen

GitHub-SSH:            abgeschlossen

Server-Wartung:        abgeschlossen

Deployment/systemd:    Phase 2

Laravel-Konzeption:    nächster Schritt

```



