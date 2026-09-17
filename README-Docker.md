# Docker

Das Projekt kann mit Docker gestartet werden. Die Anwendung und MongoDB laufen dabei in zwei Containern. Voraussetzung ist Docker mit Docker Compose, z. B. Docker Desktop.

## Möglichkeit 1: Docker Desktop unter Windows

1. [Docker Desktop für Windows](https://docs.docker.com/desktop/setup/install/windows-install/) installieren, bei der Einrichtung WSL 2 verwenden und Docker Desktop starten.
2. Die Datei `compose.yaml` herunterladen und in einem eigenen Ordner speichern, z. B. `Downloads\mertens`. Der restliche Quellcode wird nicht benötigt.
3. Den Ordner im Explorer öffnen und per Rechtsklick **Im Terminal öffnen** auswählen.
4. Diese Befehle ausführen:

```powershell
docker compose pull
docker compose up -d --wait
```

Danach `http://localhost:8080` im Browser öffnen. Unter **Containers** in Docker Desktop erscheint `mertens-demo` mit den Containern `app` und `mongodb`. Dort lässt sich die Anwendung stoppen und wieder starten.

Voraussetzung ist, dass das Image bereits veröffentlicht und das GitHub-Package öffentlich zugänglich ist. Ein GitHub-Login ist dann nicht nötig.

## Möglichkeit 2: Direkt über das Terminal

Nach der Veröffentlichung des Images reicht es, die Datei `compose.yaml` herunterzuladen und im gleichen Ordner diese Befehle auszuführen:

```sh
docker compose pull
docker compose up -d --wait
```

Die Anwendung ist anschliessend unter `http://localhost:8080` erreichbar. Die Datenbank und die Beispieldaten werden beim ersten Start automatisch eingerichtet.

## Zugangsdaten

| Rolle | E-Mail | Passwort |
| --- | --- | --- |
| Anlagenverwalter | test@example.com | password |
| Mitarbeiter | mitarbeiter@example.com | password |

Die Konten und Beispieldaten sind für die lokale Demo gedacht.

## Stoppen und zurücksetzen

```sh
docker compose down
```

Die Daten bleiben beim Stoppen erhalten. Mit `docker compose up -d --wait` wird die Anwendung wieder gestartet.

Zum vollständigen Zurücksetzen:

```sh
docker compose down --volumes
```

Dabei werden alle Demo-Daten gelöscht. Beim nächsten Start werden die Beispieldaten neu angelegt.

## MongoDB startet nicht

Falls der MongoDB-Container als `unhealthy` angezeigt wird, zuerst die Logs prüfen:

```sh
docker compose logs --tail=50 mongodb
docker info --format '{{.KernelVersion}}'
```

Steht dort `MongoDB cannot start: Linux kernel versions 6.19 and newer ...`, liegt eine bekannte Kernel-Inkompatibilität vor. Betroffen sind Linux-Kernel-Versionen von 6.19 bis 7.0.13, darunter `7.0.12-linuxkit`. Entscheidend ist der von Docker verwendete Kernel, unabhängig vom Betriebssystem des Rechners.

Als vorübergehende Lösung kommt Docker Desktop **4.86.0** infrage, sofern damit ein kompatibler Kernel verwendet wird. Der problematische mitgelieferte Kernel wurde mit Version 4.87.0 eingeführt. Downloads stehen in den [Release Notes](https://docs.docker.com/desktop/release-notes/#4860). Wichtige Docker-Daten vorher sichern; keinen Factory Reset durchführen.

Danach den Kernel erneut prüfen. Ein Wechsel der Docker-Version allein ändert diesen je nach Umgebung nicht zwingend. Liegt er unter 6.19, die Anwendung mit `docker compose up -d --wait` starten. Dieser Weg wurde hier noch nicht praktisch bestätigt.

Die Volumes müssen dafür nicht gelöscht werden. Laut [MongoDB-Dokumentation](https://www.mongodb.com/docs/manual/administration/production-notes/) ist das Kernel-Problem ab Version 7.0.14 behoben.

## Image selbst bauen

Im Projektordner ausführen (macOS/Linux):

```sh
docker build -t mertens:local .
export DOCKER_IMAGE=mertens:local
docker compose up -d --wait
```

Die Variable `DOCKER_IMAGE` muss auch für weitere Compose-Aufrufe gesetzt sein. In PowerShell lautet der Befehl dafür `$env:DOCKER_IMAGE = 'mertens:local'`.

## Veröffentlichung

Ein zu GitHub gepushter Versions-Tag wie `v1.0.0` startet den Docker-Workflow. Nach erfolgreichen Tests wird das Image für AMD64 und ARM64 unter `ghcr.io/lirux/mertens` veröffentlicht. Das Package muss auf GitHub auf **Public** stehen, damit es ohne Anmeldung heruntergeladen werden kann.
