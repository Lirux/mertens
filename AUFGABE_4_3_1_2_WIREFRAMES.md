# Aufgabe 4.3.1 und 4.3.2 – Wireframes

Für das Asset Management der Mertens AG wurden Wireframes für eine SPA
Web-App sowie für iOS und Android erstellt. Die wichtigsten Abläufe sind:

> Login → Dashboard → Asset-Liste → Asset-Detail → Erfassen, Bearbeiten,
> Wartung oder Löschen

Die vollständigen Wireframes sind in der
[Figma-Datei](https://www.figma.com/design/EkqUbhufvQoIiENXOxCoIH) verfügbar.

## Aufgabe 4.3.1 – SPA Web-App

Die Web-App orientiert sich am shadcn/ui-Designsystem. Eine Seitenleiste und
Breadcrumbs unterstützen die Navigation. Für kleinere Bildschirmbreiten
wurden responsive Varianten erstellt.

### Bildschirmseiten

| Login                                                        | Dashboard                                                            |
| ------------------------------------------------------------ | -------------------------------------------------------------------- |
| ![Web – Login](output/screenshots/aufgabe-4-3/web-login.png) | ![Web – Dashboard](output/screenshots/aufgabe-4-3/web-dashboard.png) |

| Asset-Liste und Suche                                                         | Asset-Detail                                                         |
| ----------------------------------------------------------------------------- | -------------------------------------------------------------------- |
| ![Web – Asset-Liste und Suche](output/screenshots/aufgabe-4-3/web-search.png) | ![Web – Asset-Detail](output/screenshots/aufgabe-4-3/web-detail.png) |

| Asset erfassen                                                         | Asset bearbeiten                                                       |
| ---------------------------------------------------------------------- | ---------------------------------------------------------------------- |
| ![Web – Asset erfassen](output/screenshots/aufgabe-4-3/web-create.png) | ![Web – Asset bearbeiten](output/screenshots/aufgabe-4-3/web-edit.png) |

| Wartung erfassen                                                              | Löschen und Zustände                                                                           |
| ----------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------- |
| ![Web – Wartung erfassen](output/screenshots/aufgabe-4-3/web-maintenance.png) | ![Web – Löschen, Validierung und Rückmeldungen](output/screenshots/aufgabe-4-3/web-states.png) |

| Tablet, 768 px                                                                    | Smartphone, 390 px                                                                    |
| --------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------- |
| ![Web – responsive Tablet-Ansicht](output/screenshots/aufgabe-4-3/web-tablet.png) | ![Web – responsive Smartphone-Ansicht](output/screenshots/aufgabe-4-3/web-mobile.png) |

Die fünf zentralen Formulare sind Login, Suche und Filter, Asset erfassen,
Asset bearbeiten und Wartung erfassen. Zusätzlich zeigen die Wireframes die
Detailansicht, den Löschdialog, Validierungsfehler, eine leere Trefferliste und
eine Erfolgsmeldung.

## Aufgabe 4.3.2 – Mobile-App

iOS und Android verwenden dieselben Funktionen und Daten. Navigation,
Formulare, Schaltflächen und Dialoge wurden jedoch an die Vorgaben der
jeweiligen Plattform angepasst. Das umfangreiche Erfassungsformular ist auf
drei Schritte verteilt.

### Bildschirmseiten

| Ansicht                                | iOS                                                                              | Android                                                                                  |
| -------------------------------------- | -------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------- |
| Login                                  | ![iOS – Login](output/screenshots/aufgabe-4-3/ios-login.png)                     | ![Android – Login](output/screenshots/aufgabe-4-3/android-login.png)                     |
| Dashboard                              | ![iOS – Dashboard](output/screenshots/aufgabe-4-3/ios-dashboard.png)             | ![Android – Dashboard](output/screenshots/aufgabe-4-3/android-dashboard.png)             |
| Suche und Filter                       | ![iOS – Suche und Filter](output/screenshots/aufgabe-4-3/ios-search.png)         | ![Android – Suche und Filter](output/screenshots/aufgabe-4-3/android-search.png)         |
| Asset-Detail                           | ![iOS – Asset-Detail](output/screenshots/aufgabe-4-3/ios-detail.png)             | ![Android – Asset-Detail](output/screenshots/aufgabe-4-3/android-detail.png)             |
| Asset erfassen: Stammdaten             | ![iOS – Stammdaten](output/screenshots/aufgabe-4-3/ios-create-1.png)             | ![Android – Stammdaten](output/screenshots/aufgabe-4-3/android-create-1.png)             |
| Asset erfassen: Standort und Lieferant | ![iOS – Standort und Lieferant](output/screenshots/aufgabe-4-3/ios-create-2.png) | ![Android – Standort und Lieferant](output/screenshots/aufgabe-4-3/android-create-2.png) |
| Asset erfassen: Wartung und Termine    | ![iOS – Wartung und Termine](output/screenshots/aufgabe-4-3/ios-create-3.png)    | ![Android – Wartung und Termine](output/screenshots/aufgabe-4-3/android-create-3.png)    |
| Asset bearbeiten                       | ![iOS – Asset bearbeiten](output/screenshots/aufgabe-4-3/ios-edit.png)           | ![Android – Asset bearbeiten](output/screenshots/aufgabe-4-3/android-edit.png)           |
| Wartung erfassen                       | ![iOS – Wartung erfassen](output/screenshots/aufgabe-4-3/ios-maintenance.png)    | ![Android – Wartung erfassen](output/screenshots/aufgabe-4-3/android-maintenance.png)    |
| Asset löschen                          | ![iOS – Asset löschen](output/screenshots/aufgabe-4-3/ios-delete.png)            | ![Android – Asset löschen](output/screenshots/aufgabe-4-3/android-delete.png)            |

Die mobile Navigation verwendet auf iOS eine Tab Bar und auf Android eine
Navigation Bar mit Floating Action Button. Systemleisten, Touch-Ziele und
Löschdialoge entsprechen den jeweiligen Plattformmustern. Alle Speichern-,
Abbrechen- und Zurück-Aktionen führen zu einem definierten Ziel.

## Verwendete UI-Guidelines

- [shadcn/ui – Figma-Ressourcen](https://ui.shadcn.com/docs/figma)
- [Apple Human Interface Guidelines](https://developer.apple.com/design/human-interface-guidelines)
- [Material Design 3](https://m3.material.io/)
