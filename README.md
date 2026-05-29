# Rechtstexte Generator

WordPress-Plugin zur Generierung von Impressum und Datenschutzerklärung über einen Frontend-Wizard mit festen, modularen Textbausteinen.

## Hinweis

Die erzeugten Texte ersetzen keine anwaltliche Prüfung. Das Plugin arbeitet mit festen Modulen und optionalen KI-Entwürfen für einzelne Blöcke, nicht mit ungeprüftem KI-Freitext als Live-Ausgabe.

## Funktionsumfang

- Frontend-Wizard per Shortcode
- Generierung von Impressum und Datenschutzerklärung
- Speicherung von Profilen in eigener Datenbanktabelle
- HTML-Ausgabe zum direkten Kopieren
- Seitensynchronisierung als WordPress-Seiten
- Scanner für aktive Plugins und typische externe Dienste
- Backend-Block-Registry mit Live-Overrides und KI-Entwürfen
- Export und Import von Profilen und Block-Registry
- Schulungsportal-/Lernplattform-Erweiterungen inklusive SCORM-, Zertifikats- und Rollenlogik

## Shortcodes

- `[frg_rechtstexte_wizard]`
- `[frg_impressum]`
- `[frg_datenschutz]`
- `[frg_last_updated]`

## Installation

1. Ordner `frontend-rechtstexte-generator` nach `wp-content/plugins/` kopieren.
2. Plugin im WordPress-Backend aktivieren.
3. Unter `Einstellungen > Rechtstexte Generator` die Grundeinstellungen prüfen.
4. Eine Seite mit dem Shortcode `[frg_rechtstexte_wizard]` anlegen.

## Backend

Zu finden unter:

- `Einstellungen > Rechtstexte Generator`

Dort verfügbar:

- Grundeinstellungen
- rechtlicher Hinweistext
- Seitennamen für Impressum und Datenschutzerklärung
- Profilübersicht
- Block-Registry
- HTML-Kopierbereiche
- OpenAI-Einstellungen für Block-Entwürfe
- Export / Import

## Frontend-Ablauf

1. Unternehmens- und Pflichtangaben erfassen
2. Datenschutz-Grunddaten ausfüllen
3. Website-Funktionen und Dienste auswählen
4. Vorschau erzeugen
5. Profil speichern
6. Seiten erstellen oder HTML direkt kopieren

## Schulungsportal / Lernplattform

Das Plugin enthält einen eigenen Datenschutzbereich für Schulungsportale mit Optionen für:

- Lernfortschritt
- Tests / Prüfungen
- Zertifikate
- Mitarbeiterschulungen
- SCORM-Tracking
- Pflichtunterweisungs-Nachweise
- Dozenten-, Manager- und Admin-Zugriffe
- Mandanten- oder Firmenzugriffe

## Technische Basis

- PHP 8.x kompatibel
- objektorientierter Aufbau
- keine Composer-Abhängigkeiten
- keine externen Frameworks
- Shortcode-basiert
- Elementor-kompatibel
- Nonces, Sanitizing, Validation und Escaping
- Textdomain: `frontend-rechtstexte-generator`

## Version

Aktueller Release: `1.2.0`

## Changelog

### 1.2.0

- Wizard in Schritt 5 und 6 thematisch neu gegliedert
- Schulungsportal-Details nur noch sichtbar, wenn der Hauptbereich aktiv ist
- Datenschutzbereich um Hosting-Anschrift sowie Telefon und Anschrift des Datenschutzbeauftragten erweitert
- Wizard in Schritt 2 bis 4 übersichtlicher aufgebaut und optionale Felder konditional eingeblendet
- Adressen im Ergebnis als eigene Adressblöcke formatiert
- Pflichtangaben werden bei KI- oder Live-Overrides systemseitig ergänzt, wenn sie im Blocktext fehlen
- Hosting-Block ergänzt Hosting-Anbieter, Serverstandort, Hoster-Anschrift und AV-Hinweis zuverlässig aus den Formulardaten

### 1.1.0

- Frontend-Wizard, Scanner, Block-Registry und HTML-Export ausgebaut
- Datenschutz- und Impressumsmodule erweitert
- Schulungsportal- und Lernplattform-Logik ergänzt
