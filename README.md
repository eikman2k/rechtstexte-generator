# Rechtstexte Generator

WordPress-Plugin zur Generierung von Impressum und Datenschutzerklärung über einen Frontend-Wizard mit festen, modularen Textbausteinen.

Aktuelle Version: `1.8.1`

## Hinweis

Die erzeugten Texte ersetzen keine anwaltliche Prüfung. Das Plugin arbeitet mit festen Modulen und optionalen KI-Entwürfen für einzelne Blöcke, nicht mit ungeprüftem KI-Freitext als Live-Ausgabe.

## Funktionsumfang

- Frontend-Wizard per Shortcode
- identischer Wizard im geschützten WordPress-Backend
- Generierung von Impressum und Datenschutzerklärung
- optionaler kompakter Lesbarkeitsmodus für die Datenschutzerklärung
- getrennt pflegbare und zentral verteilbare Kompaktbausteine
- Speicherung von Profilen in eigener Datenbanktabelle
- HTML-Ausgabe zum direkten Kopieren
- Seitensynchronisierung als WordPress-Seiten
- Scanner für aktive Plugins und typische externe Dienste
- Backend-Block-Registry mit Live-Overrides und KI-Entwürfen
- geführter Vier-Schritt-Workflow zum Ändern, Prüfen und Veröffentlichen einzelner Textbausteine
- Suche sowie Bereichs- und Statusfilter für Textbausteine
- getrennte Status für redaktionelle Freigabe und dokumentierte juristische Prüfung
- strukturierte Detailangaben zu eingebundenen externen Diensten
- konkrete Angaben zu Backup-Speicherort und zuständiger Datenschutzaufsichtsbehörde
- Export und Import von Profilen und Block-Registry
- Schulungsportal-/Lernplattform-Erweiterungen inklusive SCORM-, Zertifikats- und Rollenlogik
- optionale Multisite-Zentralausgabe für Impressum und Datenschutzerklärung
- optionaler Textbaustein-Feed für voneinander getrennte WordPress-Installationen

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
- `Einstellungen > Rechtstexte erfassen`

Dort verfügbar:

- Kundendaten direkt im Backend erfassen, speichern und als Seiten synchronisieren
- Grundeinstellungen
- rechtlicher Hinweistext
- Seitennamen für Impressum und Datenschutzerklärung
- Profilübersicht
- Block-Registry
- HTML-Kopierbereiche
- OpenAI-Einstellungen für Block-Entwürfe
- Prüfstatus, Prüfer und Prüfquelle je Textblock
- Export / Import

## Multisite

In WordPress Multisite kann ein Superadmin die Ausgabe zentral steuern:

- Netzwerkadmin: `Einstellungen > Rechtstexte Generator`
- Master-Site auswählen
- optional ein zentrales Profil auswählen
- auf Unterseiten `[frg_impressum]`, `[frg_datenschutz]` und `[frg_last_updated]` verwenden

Wenn der zentrale Modus aktiv ist, werden die Ausgabe-Shortcodes auf Unterseiten aus der Master-Site gerendert. Dadurch greifen auch die zentrale Block-Registry und Live-Overrides der Master-Site.

## Textbaustein-Feed

Für voneinander getrennte WordPress-Installationen können veröffentlichte Textbausteine zentral verteilt werden. Kundendaten und Profile bleiben dabei auf der jeweiligen Kundenseite.

Auf der Hauptseite:

1. Unter `Einstellungen > Rechtstexte Generator > Textverteilung` die Rolle `Zentrale` auswählen.
2. Einstellungen speichern, damit ein Verbindungsschlüssel erzeugt wird.
3. Feed-URL und Verbindungsschlüssel kopieren.
4. Neue Textbausteine wie gewohnt prüfen und ausdrücklich veröffentlichen.

Auf einer Kundenseite:

1. Die Rolle `Kundenseite` auswählen.
2. Feed-URL und Verbindungsschlüssel eintragen.
3. `Speichern und Verbindung testen` ausführen.
4. Die tägliche automatische Synchronisierung aktiviert lassen.

Übertragen werden ausschließlich die aktiven Blocktexte und zugehörigen Rechtsgrundlagen. Das ist je Baustein entweder der ausdrücklich veröffentlichte eigene Text oder der aktuelle mitgelieferte Standardtext. Platzhalter bleiben erhalten und werden erst auf der Kundenseite mit deren lokalen Wizard-Daten ausgefüllt. KI-Entwürfe, interne Notizen, Profile und Kundendaten werden nicht übertragen. Bei einem Abruffehler bleibt die zuletzt erfolgreich gespeicherte lokale Version aktiv. Für eine vollständige Kompatibilität sollten Zentrale und Kundenseiten möglichst dieselbe Plugin-Version verwenden.

## Seitensynchronisierung

Standardmäßig werden neu synchronisierte Impressums- und Datenschutzseiten mit den Shortcodes `[frg_impressum]` beziehungsweise `[frg_datenschutz]` angelegt. Änderungen am Profil oder an live geschalteten Textblöcken erscheinen dadurch ohne erneutes Überschreiben der Seite.

Bereits früher als statisches HTML angelegte Seiten müssen einmal über den Wizard erneut synchronisiert werden. Die dynamische Ausgabe kann in den Plugin-Einstellungen deaktiviert werden.

## Prüfworkflow

KI-generierte Blocktexte werden ausschließlich als Entwurf gespeichert. Eine Übernahme in die Live-Ausgabe gilt als redaktionelle Freigabe, nicht als juristische Prüfung. Der Status `Juristisch geprüft` setzt zusätzlich ein Prüfdatum und die Angabe der prüfenden Person oder Stelle voraus.

Die mitgelieferten Module sind technische Ausgangstexte. Vor dem Einsatz als Ersatz für einen spezialisierten Rechtstexte-Dienst sollten insbesondere Rechtsgrundlagen, Anbieterangaben, Drittlandtransfers, Einwilligungssteuerung, Speicherfristen und branchenspezifische Pflichtangaben fachlich geprüft werden.

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

Aktueller Release: `1.6.1`

## Changelog

### 1.6.1

- automatische Textqualitätsprüfung im Backend ergänzt
- Pflichtdaten ohne technische Generatorüberschrift ausgegeben
- Backup-Rechtsgrundlage und DSGVO-Artikel der Betroffenenrechte ergänzt
- weitere Umlaut-, Hinweis- und Kontaktformular-Reste aus Live-Overrides bereinigt

### 1.6.0

- unvollständige KI-, Vimeo- und SMTP-Blöcke werden nicht mehr veröffentlicht
- offene Platzhalter und interne Hinweisreste werden aus der Ausgabe entfernt
- konkrete Dienstblöcke ersetzen den bisherigen generischen Embed-Sammelblock
- Hosting-Rechtsgrundlage und deutsche Umlaute werden auch in Live-Overrides normalisiert
- Consent-Management, Vimeo und lokale Google Fonts textlich präzisiert

### 1.5.1

- deaktivierter Drittland-Schalter bereinigt auch entsprechende Passagen aus älteren Live-Overrides

### 1.5.0

- Datenschutzerklärung konsequenter aus tatsächlich aktivierten Funktionen und Diensten zusammengesetzt
- eindeutigen Schalter für den allgemeinen Drittlandtransfer ergänzt
- Hosting-, Vimeo-, SMTP-, Backup- und Google-Fonts-Texte präzisiert
- Social-Media-Verlinkungen von technisch eingebetteten Inhalten getrennt
- konkrete Aufsichtsbehörde, Backup-Ziel und Formularsysteme ergänzt
- interne Generator- und Prüfanweisungen aus veröffentlichten Texten entfernt

### 1.4.0

- Rechtstexte-Wizard zusätzlich direkt im geschützten WordPress-Backend verfügbar
- Backend-Workflow für Prüfung, KI-Entwürfe und kontrollierte Veröffentlichung überarbeitet
- geschützten zentralen Textbaustein-Feed für getrennte Kunden-Websites ergänzt
- automatische tägliche Synchronisierung mit lokalem Fallback und manuellem Verbindungstest ergänzt
- zentrale Texte behalten Platzhalter und werden erst lokal mit Kundendaten ausgefüllt
- Datenschutz- und Impressumslogik um weitere Pflichtangaben, Dienste und Unternehmensformen erweitert
- dynamische Shortcode-Seiten übernehmen synchronisierte Textänderungen automatisch
- Generator-Regressionstests für wichtige Ausgabe- und Verteilungsregeln ergänzt

### 1.3.0

- Multisite-Zentralausgabe fuer Impressum und Datenschutzerklaerung ergaenzt
- Netzwerk-Einstellungen fuer Master-Site und zentrales Profil hinzugefuegt
- Shortcodes auf Unterseiten koennen Inhalte aus der zentralen Master-Site ausgeben
- Wizard auf Unterseiten zeigt bei aktivem Zentralmodus einen Verwaltungshinweis
- Netzwerk-Aktivierung legt Profiltabellen fuer bestehende Sites an
- Neue Sites erhalten die Profiltabelle automatisch

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
