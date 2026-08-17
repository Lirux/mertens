# Aufgabe 4.2.3 – Asset-Web-API

Stand: 16. August 2026

## Ziel und Ergebnis

Die MongoDB-Asset-Persistenz wird über eine versionierte REST-API unter
`/api/v1/assets` angeboten. Die API entspricht der OpenAPI-Datei
`openapi.yaml`. Sie verwendet MongoDB-`ObjectId` als technischen
Primärschlüssel und gibt diese IDs als undurchsichtige Strings aus.

Laravel Sanctum schützt sämtliche Endpunkte mit Bearer Tokens. Fortify und
die bestehende Browser-Anmeldung bleiben davon getrennt und unverändert.
Lesezugriffe benötigen `assets:read`; Schreibzugriffe benötigen
`assets:write`. Zusätzlich begrenzt Laravel jeden Token auf 60 Aufrufe pro
Minute.

## Endpunkte

| Methode | Pfad | Fähigkeit | Ergebnis |
|---|---|---|---|
| `GET` | `/api/v1/assets` | `assets:read` | Unverpackte, deterministisch sortierte Asset-Liste |
| `POST` | `/api/v1/assets` | `assets:write` | Neues Asset, Status `201` |
| `GET` | `/api/v1/assets/{id}` | `assets:read` | Einzelnes Asset |
| `PUT` | `/api/v1/assets/{id}` | `assets:write` | Vollständige Aktualisierung |
| `DELETE` | `/api/v1/assets/{id}` | `assets:write` | Leere Antwort, Status `204` |

Die Listenabfrage unterstützt `updatedSince`, `limit` und `offset`. `limit`
ist standardmässig 50 und maximal 100, `offset` standardmässig 0. Die
Sortierung erfolgt aufsteigend nach `updated_at` und danach nach `_id`.
`updatedSince` verwendet einen inklusiven Vergleich (`>=`).

## API- und Persistenzabbildung

| API-Feld | MongoDB-Feld | Bemerkung |
|---|---|---|
| `id` | `_id` | `ObjectId`, nach aussen ein undurchsichtiger String |
| `inventoryNumber` | `asset_number` | Eindeutiger Index |
| `serialNumber` | `serial_number` | Optionaler, sparse eindeutiger Index |
| `acquisitionDate` | `acquired_at` | API-Datum `YYYY-MM-DD`, intern BSON-Datum |
| `acquisitionValue` | `acquisition_value` | Intern `Decimal128`, API-Antwort als JSON-Zahl |
| `updatedAt` | `updated_at` | ISO-8601-Zeitstempel |

Die API Resource liefert nur die dokumentierten CamelCase-Felder. Interne
Persistenzfelder wie `supplier`, `maintenance` und `warranty_until` werden
nicht veröffentlicht.

## Authentisierung und Token-Verwaltung

Sanctum verwendet ein eigenes MongoDB-`PersonalAccessToken`-Modell. In der
Collection `personal_access_tokens` werden nur SHA-256-Hashes gespeichert.
Der Klartext-Token wird von Sanctum ausschliesslich unmittelbar nach der
Erzeugung zurückgegeben und kann später nicht aus der Datenbank rekonstruiert
werden.

Tokens werden nicht über einen öffentlichen HTTP-Endpunkt, Seeder oder eine
Konfigurationsdatei erzeugt. Ein vollständig berechtigter Token kann von einer
Administratorin oder einem Administrator einmalig in Tinker erstellt werden:

```bash
php artisan tinker --execute '$user = App\Models\User::where("email", "integration@example.com")->firstOrFail(); echo $user->createToken("ERP", ["assets:read", "assets:write"])->plainTextToken.PHP_EOL;'
```

Der ausgegebene Wert ist unmittelbar in einem Secret Manager des Umsystems zu
speichern. Er darf nicht in Git, Seedern, Tickets, Screenshots oder Logs
gelangen. Nicht mehr benötigte Tokens werden über die `tokens()`-Beziehung des
betroffenen Benutzers widerrufen.

## MongoDB-Struktur

Die Migration erstellt die Collection `personal_access_tokens` mit:

- strengem JSON-Schema-Validator;
- eindeutigem Index auf dem Token-Hash;
- zusammengesetztem Index auf `tokenable_type` und `tokenable_id`;
- Index auf `expires_at`.

Für inkrementelle Asset-Synchronisationen ergänzt eine zweite Migration den
Index `assets_updated_at_id_sync` auf `updated_at` und `_id`.

## Fehlerverhalten

| Status | Bedeutung |
|---:|---|
| `400` | Ungültige Listenparameter |
| `401` | Fehlender oder ungültiger Bearer Token |
| `403` | Token besitzt die benötigte Fähigkeit nicht |
| `404` | Ungültige oder unbekannte Asset-ID |
| `422` | Ungültige oder doppelte Asset-Daten |
| `429` | 60 Aufrufe pro Minute und Token überschritten |

Eindeutigkeitsfehler aus den MongoDB-Indizes werden in feldbezogene
`422`-Antworten für `inventoryNumber` oder `serialNumber` übersetzt. Dadurch
bleibt auch ein Race zwischen Vorabvalidierung und Schreiboperation
API-konform.

## Arbeitsprotokoll

| Schritt | Tätigkeit | Ergebnis |
|---:|---|---|
| 1 | OpenAPI und bestehendes MongoDB-Modell abgeglichen | `ObjectId`, Statuswerte, Geld- und Feldabbildung stimmen überein. `limit`, `offset`, Sortierung, Geldpräzision und `429` wurden ergänzt. |
| 2 | Sanctum über Laravels API-Installation eingebunden | `routes/api.php` ist registriert; Sanctum 4.3.3 ist installiert. SPA-Cookie-Routen sind deaktiviert. |
| 3 | Sanctum auf MongoDB angepasst | Eigenes Tokenmodell, strikter Collection-Validator und drei Tokenindizes funktionieren mit real erzeugten Tokens. |
| 4 | API-Schicht implementiert | Controller, Form Requests, API Resource, fünf explizite Routen und Repository-Listenabfrage sind umgesetzt. |
| 5 | Sicherheit umgesetzt | Alle Routen verwenden `auth:sanctum`, Fähigkeiten und ein Token-bezogenes Limit von 60 Aufrufen pro Minute. |
| 6 | Fehler vereinheitlicht | Listenfehler liefern `400`; Authentisierungs-, Berechtigungs-, ID-, Validierungs-, Duplicate-Key- und Rate-Limit-Fehler liefern die vorgesehenen Statuscodes. |
| 7 | Produktionsdatenbank migriert und geprüft | Beide neuen Migrationen sind ausgeführt. MongoDB enthält die erwarteten Validatoren und Indizes. |
| 8 | OpenAPI validiert | Redocly bestätigt eine gültige OpenAPI-3.1-Beschreibung. Es bleibt nur der optionale Hinweis auf eine fehlende Lizenzangabe. |
| 9 | Gezielte Tests ausgeführt | API-, Repository- und Infrastrukturtests: 39 Tests, keine Fehler. |
| 10 | Vollständige Regression ausgeführt | 74 Tests, 71 bestanden, 3 optionale übersprungen, 408 Assertions, keine Fehler. |
| 11 | Formatierung und statische Analyse ausgeführt | Pint und PHPStan Level 7 sind für alle geänderten Produktionsdateien fehlerfrei. Der projektweite PHPStan-Lauf zeigt zwei bereits bestehende, nicht API-bezogene Befunde in `UserFactory` und der alten Jobs-Migration. |
| 12 | Abhängigkeiten geprüft | `composer audit` meldet keine bekannten Sicherheitslücken. |
| 13 | HTTP-Smoke-Test ausgeführt | Der lokale Server antwortet auf `GET /api/v1/assets` ohne Token mit `401` und `{"message":"Unauthenticated."}`. |

## Manueller Smoke-Test

Für einen manuellen Test wird ein administrativ erzeugter Token als lokale,
nicht ausgegebene Umgebungsvariable gesetzt. Beispiel für die Listenabfrage:

```bash
curl --fail-with-body \
  --header "Accept: application/json" \
  --header "Authorization: Bearer $ASSET_API_TOKEN" \
  "http://localhost:8000/api/v1/assets?limit=1&offset=0"
```

Der Tokenwert ist vor Terminalaufzeichnungen und Screenshots auszublenden.
