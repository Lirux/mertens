# Docker

Das Projekt kann mit Docker gestartet werden. Die Anwendung und MongoDB laufen dabei in zwei Containern. Voraussetzung ist Docker mit Docker Compose, z. B. Docker Desktop.

## Starten

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
