# Aufgabe 4.2.4 – Postman-Tests der Asset-Web-API

## Ziel und Lieferumfang

Die Testsuite prüft die fünf Asset-Endpunkte funktional gegen eine laufende
Anwendung. Sie besteht aus:

- `postman/Mertens-Asset-API.postman_collection.json`
- `postman/Mertens-Local.postman_environment.example.json`

Die Collection verwendet das Postman-Collection-Format v2.1. Sie kann damit
in Postman importiert und ohne Konvertierung mit Newman ausgeführt werden. Die
50 definierten Requests werden in genau einer Runner-Iteration ausgeführt. Der
letzte Request wiederholt sich kontrolliert, sodass insgesamt 110 HTTP-Aufrufe
entstehen.

## Sicherheit und Testdaten

Die Umgebungsvorlage enthält keine gültigen Zugangsdaten. Vor einem Lauf wird
sie als `postman/Mertens-Local.postman_environment.json` kopiert und nur lokal
mit vier kurzlebigen Sanctum-Tokens ergänzt. Diese lokale Datei und optionale
Runner-Ergebnisse unter `postman/results/` sind durch `.gitignore` vom Commit
ausgeschlossen.

| Variable | Fähigkeiten | Verwendung |
| --- | --- | --- |
| `fullAccessToken` | `assets:read`, `assets:write` | CRUD, Listen- und Validierungstests |
| `readOnlyToken` | `assets:read` | Positiver Lese- und negativer Schreibtest |
| `writeOnlyToken` | `assets:write` | Positiver Schreib- und negativer Lesetest |
| `rateLimitToken` | `assets:read` | Isolierter Rate-Limit-Test |

Die Collection erzeugt zu Beginn jedes Laufs eine zufällige `runId`. Alle
Inventar- und Seriennummern beginnen mit `AST-PM-<runId>-` beziehungsweise
`SN-PM-<runId>-`. Cleanup-Requests verwenden ausschließlich IDs, die während
desselben Laufs gespeichert wurden. Tokenwerte werden weder protokolliert noch
in Testresultate exportiert.

## Voraussetzungen

- MongoDB ist erreichbar und die Migrationen sind ausgeführt.
- Die Laravel-Anwendung läuft lokal auf `http://127.0.0.1:8000`.
- Ein temporärer Benutzer besitzt vier getrennte Tokens gemäß der Tabelle.
- Postman Desktop oder Node.js mit `npx` ist installiert.
- Die komplette Collection wird mit einer Runner-Iteration gestartet. Einzelne
  Requests sind nur zur Diagnose geeignet.

## Ausführung in Postman

1. Collection und Umgebungsvorlage in Postman importieren.
2. Die Vorlage duplizieren, die vier Tokenwerte als Secret-Variablen eintragen
   und die lokale Umgebung auswählen.
3. In der Collection den Runner öffnen, alle Ordner in ihrer vorhandenen
   Reihenfolge belassen und exakt eine Iteration ausführen.
4. Prüfen, dass der Lauf ohne fehlgeschlagene Assertions endet. Der Request
   `RATE-01` muss 61-mal laufen; die ersten 60 Antworten sind `200`, die letzte
   Antwort ist `429`.
5. Für die Abgabe die Runner-Zusammenfassung ohne Authorization-Header oder
   Tokenwerte als Nachweis sichern.

Der Rate-Limit-Ablauf verwendet `pm.execution.setNextRequest()` und funktioniert
daher nur in einem Collection Run. Die Postman-Dokumentation beschreibt diesen
[Workflow für wiederholte Requests](https://learning.postman.com/docs/tests-and-scripts/running-collections/building-workflows/).

## Reproduzierbarer Newman-Lauf

```bash
cp postman/Mertens-Local.postman_environment.example.json \
  postman/Mertens-Local.postman_environment.json

php artisan serve --host=127.0.0.1 --port=8000

npx --yes newman run \
  postman/Mertens-Asset-API.postman_collection.json \
  -e postman/Mertens-Local.postman_environment.json \
  --reporters cli \
  --bail \
  --timeout-request 10000
```

Die vier leeren Secret-Werte werden vor dem Newman-Aufruf in der lokalen Kopie
ergänzt. Newman wird nur temporär durch `npx` geladen und ist keine
Projektabhängigkeit. Das v2.1-Format bleibt laut
[Postman-Dokumentation zur Newman-Migration](https://learning.postman.com/docs/reference/newman-cli/migrate-to-postman-cli/)
für Newman geeignet.

## Testfallmatrix

| Bereich | Definierte Requests | Wesentliche Prüfungen |
| --- | ---: | --- |
| Setup | 1 | Basis-URL, vier Tokens, Server, JSON-Array |
| Authentisierung | 6 | Alle fünf Routen ohne Token sowie ungültiger Token ergeben `401` |
| Autorisierung | 8 | Read-/Write-Fähigkeiten, verbotene Zugriffe ergeben `403` |
| CRUD und Vertrag | 7 | `201`, `200`, `204`, danach `404`; exakte CamelCase-Felder und Datentypen |
| Listenabfrage | 14 | Defaults, Limit, Offset, inklusives `updatedSince`, stabile Sortierung, `400` |
| Validierung | 11 | Pflichtfelder, Wertebereiche, Standort, Datum, Währung, Duplikate, `422` |
| ObjectId | 2 | Ungültige und unbekannte IDs ergeben `404` |
| Rate-Limit | 1, 61 Ausführungen | 60 erfolgreiche GETs, danach `429` |

Der Antwortvertrag prüft zusätzlich eine 24-stellige ObjectId, ISO-Zeitstempel,
einen numerischen Anschaffungswert, den JSON-Content-Type und das Fehlen interner
Felder wie `supplier`, `maintenance` und `asset_number`.

## Verifizierter Lauf vom 16. August 2026

Der vollständige Lauf wurde lokal mit einem eigens erstellten Benutzer und vier
realen MongoDB-gespeicherten Sanctum-Tokens ausgeführt. Die Tokens befanden sich
nur in `/private/tmp` und wurden danach gelöscht.

| Newman-Kennzahl | Ergebnis |
| --- | ---: |
| Iterationen | 1 |
| HTTP-Requests | 110 |
| Fehlgeschlagene Requests | 0 |
| Test-Skripte | 220 |
| Fehlgeschlagene Test-Skripte | 0 |
| Pre-Request-Skripte | 172 |
| Assertions | 404 |
| Fehlgeschlagene Assertions | 0 |
| Laufzeit | 4,2 Sekunden |
| Exit-Code | 0 |

Die ersten 60 Aufrufe mit dem isolierten Rate-Limit-Token ergaben `200`; der
61. Aufruf ergab erwartungsgemäß `429 Too Many Requests`.

Die ergänzende PHP-Verifikation ergab:

| Prüfung | Ergebnis |
| --- | --- |
| Pest-Regressionstest für `updatedSince` | 1 Test, 4 Assertions, bestanden |
| Vollständige Pest-Suite | 75 Tests, 72 bestanden, 3 übersprungen, 412 Assertions, Exit-Code 0 |
| Laravel Pint | bestanden |
| PHPStan für `ListAssetsRequest` | keine Fehler |
| JSON-Syntax beider Postman-Dateien | gültig |

## Gefundene Probleme und Behebung

### API-Abweichung: eigener Zeitstempel wurde abgelehnt

Der erste vollständige Lauf stoppte bei `LIST-07`: Die API gab `updatedAt` als
UTC-Zeitstempel mit sechs Nachkommastellen und `Z` aus, akzeptierte denselben
Wert jedoch nicht als `updatedSince` und antwortete mit `400`.

Die Validierung in `ListAssetsRequest` wurde um die von der API selbst
ausgegebenen UTC-Formate mit `Z` erweitert. Zusätzlich sichert ein neuer
Pest-Regressionstest ab, dass ein ausgegebener `updatedAt`-Wert direkt als
inklusiver `updatedSince`-Filter wiederverwendet werden kann.

### Testartefakt: Punkt im Validierungsfehler-Key

Im zweiten Lauf interpretierte die Chai-Assertion den wörtlichen Laravel-Key
`location.room` als verschachtelten Objektpfad. Die Assertion prüft den Key nun
direkt innerhalb von `errors`. Dies war kein Fehler der API.

Nach beiden Korrekturen endete der vollständige Newman-Lauf mit Exit-Code 0.

## Bereinigung

Nach dem verifizierten Lauf wurden alle vier Tokens widerrufen, der temporäre
Benutzer gelöscht und die lokale Secret-Umgebung entfernt. Eine anschließende
MongoDB-Prüfung ergab:

- Assets mit Präfix `AST-PM-`: 0
- Benutzer mit Präfix `postman-run-`: 0

Wird ein Lauf vor den Cleanup-Requests abgebrochen, darf nur anhand der im Lauf
verwendeten `runId` bereinigt werden. Das Zielpräfix lautet dann
`AST-PM-<runId>-`; andere Assets dürfen nicht gelöscht werden.
