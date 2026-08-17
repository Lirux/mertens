---
name: changed-files-documentation
description: >
  Erstellt eine dokumentationsfähige Markdown-Tabelle der Projektdateien, die
  für eine Teilaufgabe, seit einer Teilaufgabe oder im gesamten Projekt
  erstellt, bearbeitet, gelöscht oder umbenannt wurden. Verwende diesen Skill,
  wenn der Benutzer eine Dateiliste, Dateiübersicht, Änderungsübersicht, einen
  Dateianhang oder eine nachvollziehbare Dokumentation geänderter Dateien
  verlangt. Analysiert Git und den verfügbaren Aufgaben-Kontext ausschließlich
  lesend und kennzeichnet unklare Zuordnungen, statt sie zu erfinden.
---

# Geänderte Dateien dokumentieren

## Zweck

Eine kompakte, nachvollziehbare Dateiliste für die technische Dokumentation
oder den Anhang einer Projektarbeit erzeugen. Für jede relevante Datei angeben:

- zu welcher Teilaufgabe sie gehört,
- welcher Pfad betroffen ist,
- ob sie erstellt, bearbeitet, gelöscht oder umbenannt wurde,
- welchen fachlichen oder technischen Zweck die Änderung erfüllt.

Das Repository ausschließlich lesend untersuchen. Keine Dateien verändern,
formatieren, erzeugen, löschen, verschieben, stagen oder committen.

## Aufruf

Folgende Aufrufe als gleichwertige Anforderungen behandeln:

- `$changed-files-documentation 4.2.2`
- `Dateiliste 4.2.2`
- `Erstelle die Dateiliste für 4.2.2`
- `Dateiliste seit 4.2.2`
- `Dateiliste gesamt`
- `Erstelle die Dateiübersicht für das gesamte Projekt`
- `Welche Dateien wurden für 4.2.2 geändert?`

Bei einer angegebenen Teilaufgabe nur diese Teilaufgabe dokumentieren. Bei
`seit` alle zuverlässig abgrenzbaren Teilaufgaben ab dem genannten Bezugspunkt
dokumentieren. Bei `gesamt` eine Gesamttabelle über alle zuverlässig
ermittelbaren Teilaufgaben erzeugen.

## Eingaben

Folgende Angaben aus dem Aufruf und dem verfügbaren Kontext ermitteln:

1. **Umfang**: einzelne Teilaufgabe, `seit` oder `gesamt`.
2. **Teilaufgabe**: Kennung wie `4.2.2`, Bezeichnung oder aktuelle Aufgabe.
3. **Vergleichsgrenzen**: Git-Referenzen, Commit-Bereich, Branch-Ausgangspunkt,
   Zeitbereich oder dokumentierter Aufgabenbeginn, sofern vorhanden.
4. **Repository**: betroffener Projektstamm, sofern mehrere Repositories im
   Arbeitsbereich liegen.
5. **Ausgabevariante**: standardmäßig Markdown-Tabelle; abweichende Formate nur
   auf ausdrücklichen Wunsch verwenden.

Fehlende Angaben aus dem aktuellen Thread, dem Arbeitsverzeichnis und Git
ableiten. Keine Grenze oder Teilaufgabenzuordnung erfinden. Wenn der gewünschte
historische Umfang nicht zuverlässig rekonstruierbar ist, den sicher
ermittelbaren Umfang ausgeben und die Einschränkung knapp kennzeichnen.

## Informationsquellen

Quellen in dieser Reihenfolge auswerten und gegeneinander prüfen:

1. ausdrückliche Angaben des Benutzers,
2. aktuelle Aufgabe und nachvollziehbare Arbeitsschritte im Thread,
3. bereits geführte Dateilisten oder Aufgabenprotokolle,
4. Git-Status, Index, Arbeitsbaum und unversionierte Dateien,
5. Git-Diffs und Commit-Historie innerhalb sicherer Vergleichsgrenzen,
6. relevante Dateiinhalte und Diff-Ausschnitte zur Formulierung des Zwecks,
7. Commit-Nachrichten und bestehende Projektdokumentation als ergänzende
   Hinweise.

Git als Beleg für betroffene Pfade und Status verwenden. Den Thread- und
Aufgaben-Kontext zur Zuordnung und Zweckbeschreibung verwenden. Commit-Nachrichten
nicht ungeprüft als alleinige Wahrheit übernehmen.

## Git-Analyse

Nur lesende Git-Befehle verwenden. Typischer Ablauf:

1. Repository-Stamm und aktuellen Branch feststellen.
2. Arbeitsbaum, Index und unversionierte Dateien getrennt erfassen.
3. Umbenennungen und Kopien erkennen lassen.
4. Bei einem bekannten Bereich die effektive Differenz zwischen Ausgangs- und
   Zielstand bestimmen.
5. Nur für die Zweckermittlung die inhaltlichen Diffs relevanter Dateien lesen.
6. Ergebnisse mit den im Thread tatsächlich ausgeführten Arbeiten abgleichen.

Geeignete Befehle sind beispielsweise:

```text
git rev-parse --show-toplevel
git status --short
git diff --name-status --find-renames
git diff --cached --name-status --find-renames
git ls-files --others --exclude-standard
git diff --name-status --find-renames <basis>...<ziel>
git log --name-status --find-renames <bereich>
```

Dateinamen mit Leerzeichen oder Sonderzeichen sicher behandeln; bei
maschineller Auswertung nach Möglichkeit nullterminierte Git-Ausgaben nutzen.

Für eine einzelne aktuelle, noch nicht abgeschlossene Aufgabe standardmäßig die
Änderungen gegenüber `HEAD` einschließlich Index, Arbeitsbaum und relevanter
unversionierter Dateien betrachten. Bereits zur Aufgabe gehörende Commits nur
einbeziehen, wenn ihre Grenzen zuverlässig feststehen.

Für `gesamt` zuerst vorhandene teilaufgabenbezogene Listen zusammenführen. Fehlen
diese, einen ausdrücklich angegebenen Projekt-Basisstand verwenden. Ist kein
Basisstand vorhanden, nicht die gesamte Historie willkürlich einer Aufgabe
zurechnen. Stattdessen nur den sicher feststellbaren Bereich dokumentieren und
die Begrenzung als Hinweis nennen.

Änderungen, die innerhalb des betrachteten Bereichs vollständig rückgängig
gemacht wurden und im Ergebnis keine Projektänderung hinterlassen, im finalen
Anhang auslassen. Sie nur aufnehmen, wenn der Benutzer ausdrücklich ein
Aktivitätsprotokoll statt einer Ergebnisdokumentation verlangt.

## Status-Zuordnung

Nur diese Ausgabewerte verwenden:

| Git-Befund oder Lebenszyklus | Ausgabe unter „Änderung“ |
|---|---|
| Unversioniert (`??`) oder hinzugefügt (`A`) | `Erstellt` |
| Innerhalb des Bereichs hinzugefügt und danach inhaltlich geändert | `Erstellt/bearbeitet` |
| Geändert (`M`) oder Typ geändert (`T`) | `Bearbeitet` |
| Gelöscht (`D`) | `Gelöscht` |
| Umbenannt (`R`) | `Umbenannt` |
| Kopiert (`C`) | `Erstellt` |

Zusammengesetzte Git-Zustände als Lebenszyklus auswerten:

- `A` mit anschließender Änderung als `Erstellt/bearbeitet` ausgeben.
- Mehrere Änderungen einer bestehenden Datei zu `Bearbeitet` zusammenfassen.
- Eine umbenannte und zusätzlich bearbeitete Datei als `Umbenannt` ausgeben und
  die inhaltliche Anpassung im Zweck erwähnen.
- Eine innerhalb des Bereichs zuletzt gelöschte Datei als `Gelöscht` ausgeben.
- Eine Kopie als `Erstellt` ausgeben und den Ursprung bei Relevanz im Zweck
  nennen.
- Ungelöste Merge-Konflikte nicht als endgültigen Status darstellen; die
  Unsicherheit ausdrücklich nennen.

Status nicht allein aus dem Vorhandensein einer Datei ableiten. Eine neue Datei
ist nur dann `Erstellt`, wenn Basisstand, Git oder Aufgaben-Kontext dies belegt.

## Relevante und ignorierte Dateien

Dateien aufnehmen, wenn ihre Änderung einen nachvollziehbaren Beitrag zur
Aufgabe oder zum Projektergebnis leistet, insbesondere:

- Anwendungs- und Bibliothekscode,
- Konfiguration und versionierte Umgebungsbeispiele,
- Datenbankschemata, Migrationen und Seeds,
- Tests, Fixtures und testrelevante Hilfsdateien,
- projektrelevante Skripte und Automatisierung,
- Benutzeroberflächen, Styles und produktive Assets,
- API-Spezifikationen und technische Projektdokumentation,
- Abhängigkeits- und Lock-Dateien bei tatsächlichen Abhängigkeitsänderungen.

Standardmäßig ignorieren:

- Abhängigkeitsverzeichnisse wie `vendor/` und `node_modules/`,
- Cache-, Laufzeit-, Sitzungs- und temporäre Dateien,
- Logs, Coverage-Ausgaben und Testartefakte,
- kompilierte oder generierte Build-Ausgaben,
- Betriebssystem- und Editor-Metadaten,
- Sicherungskopien und rein lokale Hilfsdateien,
- Dateien, deren einzige Änderung automatisch erzeugtes Rauschen ist.

Eine normalerweise ignorierte Datei trotzdem aufnehmen, wenn sie bewusstes
Projektergebnis oder ausdrücklich Teil der Aufgabe ist. Eine versionierte,
generierte Datei nicht pauschal auslassen, wenn das Projekt sie absichtlich
ausliefert.

Keine geheimen Werte aus `.env`, Zugangsdaten, Schlüsseln oder Zertifikaten in
den Zweck oder in Hinweise übernehmen. Solche Pfade nur aufnehmen, wenn ihre
Änderung dokumentationsrelevant und die Nennung des Pfads unbedenklich ist.

## Pfade

Pfade einheitlich darstellen:

- relativ zum Repository-Stamm,
- mit `/` als Trennzeichen,
- ohne führendes `./`,
- in Markdown-Codeformatierung,
- bei Löschungen mit dem zuletzt gültigen Pfad,
- bei Umbenennungen als `` `alter/pfad` → `neuer/pfad` ``.

Bei mehreren Repositories einen kurzen, eindeutigen Repository-Präfix verwenden,
zum Beispiel `` `backend:app/Models/Asset.php` ``. Absolute lokale Pfade nicht
in die Tabelle aufnehmen.

## Zweck-Formulierungen

Den Zweck aus Aufgabenbeschreibung und tatsächlichem Diff ableiten. Eine kurze,
präzise Formulierung mit Objekt und Wirkung verwenden, zum Beispiel:

- `MongoDB als einzige Datenbankverbindung konfiguriert`
- `MongoDB-Modell für Asset-Dokumente bereitgestellt`
- `Repository-Schnittstelle für Asset-Zugriffe eingeführt`
- `Tests für die Validierung ungültiger Seriennummern ergänzt`
- `Relationale Persistenz aus dem Projekt entfernt`

Folgende Regeln einhalten:

- eine konkrete fachliche oder technische Wirkung nennen,
- möglichst einen präzisen Satzteil statt einer Tätigkeitschronik schreiben,
- redundante Hinweise wie „Datei bearbeitet“ vermeiden,
- keine Absicht behaupten, die weder Diff noch Kontext belegen,
- Implementierungsdetails nur nennen, wenn sie die Nachvollziehbarkeit erhöhen,
- bei unklarem Zweck `Zweck aus Änderung nicht eindeutig ableitbar` verwenden.

## Zusammenführen

Mehrere Befunde derselben Datei innerhalb derselben Teilaufgabe zu genau einer
Zeile zusammenführen.

Dabei:

1. Umbenennungen über den alten und neuen Pfad hinweg als dieselbe Datei
   behandeln.
2. Den endgültigen Lebenszyklus nach der Status-Zuordnung bestimmen.
3. Doppelte oder bedeutungsgleiche Zweckangaben entfernen.
4. Mehrere wesentliche Zwecke knapp mit Semikolon verbinden.
5. Rein zwischenzeitliche, vollständig rückgängig gemachte Änderungen auslassen.

Nicht zwei Zeilen erzeugen, nur weil eine Änderung zunächst im Index und später
zusätzlich im Arbeitsbaum erscheint.

## Dateien über mehrere Teilaufgaben

Eine Datei darf in der Gesamttabelle mehrfach vorkommen, wenn sie in mehreren
Teilaufgaben einen jeweils eigenen Beitrag leistet. Standardmäßig eine Zeile pro
Kombination aus Teilaufgabe und Datei ausgeben. Dadurch bleiben Status und Zweck
je Teilaufgabe nachvollziehbar.

Für jede Zeile nur den innerhalb dieser Teilaufgabe belegbaren Status und Zweck
angeben. Eine spätere Bearbeitung nicht rückwirkend in die frühere Teilaufgabe
eintragen.

Kann eine Datei keinem Abschnitt zuverlässig zugeordnet werden, als
Teilaufgabe `Nicht eindeutig zugeordnet` verwenden und einen Hinweis ergänzen.
Keine Teilaufgabennummer aus Reihenfolge, Dateiname oder Vermutung ableiten.

Nur wenn der Benutzer ausdrücklich eine auf genau eine Zeile pro Datei
konsolidierte Gesamtliste verlangt, die Teilaufgaben kommasepariert bündeln und
den effektiven Gesamtstatus sowie einen zusammengefassten Zweck ausgeben.

## Ausgabeformat

Standardmäßig ausschließlich diese Markdown-Tabelle ausgeben:

| Teilaufgabe | Datei | Änderung | Zweck |
|---|---|---|---|
| 4.2.2 | `config/database.php` | Bearbeitet | MongoDB als einzige Datenbankverbindung konfiguriert |
| 4.2.2 | `app/Models/Asset.php` | Erstellt/bearbeitet | MongoDB-Modell für Asset-Dokumente bereitgestellt |
| 4.2.2 | `app/Repositories/AssetRepository.php` | Erstellt | Repository-Schnittstelle für Asset-Zugriffe eingeführt |
| 4.2.2 | `database/database.sqlite` | Gelöscht | Relationale Persistenz aus dem Projekt entfernt |

Keine zusätzliche Einleitung oder Zusammenfassung ausgeben. Nur bei relevanter
Unsicherheit direkt unter der Tabelle einen knappen Absatz mit `Hinweis:`
ergänzen. Keine zusätzlichen Spalten hinzufügen, sofern der Benutzer sie nicht
verlangt.

## Sortierung

Zeilen deterministisch sortieren:

1. Teilaufgaben in natürlicher numerischer Reihenfolge, sodass `4.2.2` vor
   `4.2.10` steht.
2. Nicht nummerierte Aufgaben in nachvollziehbarer Arbeits- oder
   Dokumentationsreihenfolge.
3. `Nicht eindeutig zugeordnet` zuletzt.
4. Innerhalb einer Teilaufgabe alphabetisch nach dem dargestellten Dateipfad;
   bei Umbenennungen nach dem neuen Pfad.

Status nicht als primäres Sortiermerkmal verwenden.

## Unsicherheit

Unsicherheit sichtbar und knapp behandeln:

- Keine Teilaufgabe, Vergleichsgrenze oder Zweckbeschreibung erfinden.
- Unsichere Zeilen möglichst mit belegbarem Pfad und Status ausgeben.
- Nicht belegbare Teilaufgaben als `Nicht eindeutig zugeordnet` markieren.
- Einen unklaren Zweck mit der festgelegten neutralen Formulierung kennzeichnen.
- Unter der Tabelle genau erklären, welcher Bereich oder welche Zuordnung nicht
  vollständig rekonstruierbar war.

Beispiel:

```text
Hinweis: Für Änderungen vor Commit abc123 war keine verlässliche Zuordnung zu einzelnen Teilaufgaben verfügbar.
```

Wenn überhaupt keine zuverlässige Git- oder Kontextgrundlage verfügbar ist,
keine scheinbar vollständige Tabelle erzeugen. Stattdessen die fehlende
Grundlage in einem Satz benennen.

## Keine Änderungen vorhanden

Wenn im sicher abgegrenzten Umfang keine relevanten Änderungen vorhanden sind,
keine leere Tabelle erzeugen. Genau diesen Satz ausgeben und den Platzhalter
ersetzen:

```text
Keine relevanten Dateiänderungen für <Umfang> festgestellt.
```

Ein nicht ermittelbarer Umfang ist nicht mit „keine Änderungen“ gleichzusetzen.
In diesem Fall die fehlende Vergleichsgrundlage als Unsicherheit nennen.

## Abschlussprüfung

Vor der Ausgabe prüfen:

- Ist der gewünschte Umfang korrekt abgegrenzt?
- Wurden Thread-Kontext und vorhandene Aufgabenangaben berücksichtigt?
- Wurden Index, Arbeitsbaum und relevante unversionierte Dateien erfasst?
- Wurden erforderliche Commits nur innerhalb belegbarer Grenzen einbezogen?
- Sind generierte, temporäre und irrelevante Dateien sinnvoll ausgeschlossen?
- Verwendet jede Zeile genau einen erlaubten Änderungsstatus?
- Sind alle Pfade relativ, einheitlich und korrekt formatiert?
- Ist jede Zweckbeschreibung konkret, knapp und durch Kontext oder Diff belegt?
- Wurden Mehrfachbefunde innerhalb derselben Teilaufgabe zusammengeführt?
- Bleiben Änderungen derselben Datei über mehrere Teilaufgaben nachvollziehbar?
- Ist die Sortierung deterministisch und natürlich?
- Sind Unsicherheiten und Einschränkungen sichtbar, ohne zu spekulieren?
- Werden keinerlei Geheimnisse oder sensible Dateiinhalte offengelegt?
- Wurde das Repository während der Analyse nicht verändert?
- Besteht die Ausgabe nur aus der Tabelle und gegebenenfalls einem notwendigen
  Hinweis?
