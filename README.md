# Rechtstexte Generator

WordPress-Plugin zur Generierung von Impressum und Datenschutzerklärung über einen Frontend-Wizard mit festen, modularen Textbausteinen. Die kommerzielle Textverteilung ist in ein Kundenplugin und ein separates privates Agency-Hub-Add-on getrennt.

Aktuelle Version: `2.1.4`

## Gemeinsame Plugin-Suite

Dieses Repository enthält zwei zusammengehörige WordPress-Plugins mit derselben Versionslinie:

| Komponente | Plugin-Ordner | Einsatzort |
| --- | --- | --- |
| Frontend Rechtstexte Generator | `frontend-rechtstexte-generator` | Basisplugin für Master-, Agentur- und Kunden-Websites |
| Rechtstexte Generator Agency Hub | `frontend-rechtstexte-generator-agency-hub` | Privates Zusatzplugin ausschließlich für die Master-Zentrale |

Das Agency Hub benötigt den Frontend Rechtstexte Generator und wird zusätzlich auf der Master-Website installiert. Kundenseiten erhalten ausschließlich das Basisplugin. Beide Komponenten sollten immer mit derselben Versionsnummer betrieben werden.

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
- Bundesland-Auswahl mit vorbelegten Kontaktdaten der allgemeinen Landesaufsichtsbehörden
- Export und Import von Profilen und Block-Registry
- Schulungsportal-/Lernplattform-Erweiterungen inklusive SCORM-, Zertifikats- und Rollenlogik
- optionale Multisite-Zentralausgabe für Impressum und Datenschutzerklärung
- Empfang eines zentralen Textbaustein-Feeds auf voneinander getrennten Kundeninstallationen
- separates Agency-Hub-Add-on mit Lizenzzentrale für manuell abgerechnete Jahreslizenzen
- wählbare Textquelle je Agentur: Master-Synchronisierung oder eigener veröffentlichter Agenturstand

## Shortcodes

- `[frg_rechtstexte_wizard]`
- `[frg_impressum]`
- `[frg_datenschutz]`
- `[frg_last_updated]`

## Installation

1. Ordner `frontend-rechtstexte-generator` nach `wp-content/plugins/` kopieren.
2. Plugin im WordPress-Backend aktivieren.
3. Unter `Rechtstexte > Übersicht & Textbausteine` die Grundeinstellungen prüfen.
4. Eine Seite mit dem Shortcode `[frg_rechtstexte_wizard]` anlegen.

## Backend

Zu finden unter:

- `Rechtstexte > Übersicht & Textbausteine`
- `Rechtstexte > Kundendaten erfassen`

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

- Netzwerkadmin: eigener Hauptmenüpunkt `Rechtstexte`
- Master-Site auswählen
- optional ein zentrales Profil auswählen
- auf Unterseiten `[frg_impressum]`, `[frg_datenschutz]` und `[frg_last_updated]` verwenden

Wenn der zentrale Modus aktiv ist, werden die Ausgabe-Shortcodes auf Unterseiten aus der Master-Site gerendert. Dadurch greifen auch die zentrale Block-Registry und Live-Overrides der Master-Site.

## Textbaustein-Feed

Für voneinander getrennte WordPress-Installationen können veröffentlichte Textbausteine zentral verteilt werden. Kundendaten und Profile bleiben dabei auf der jeweiligen Kundenseite.

### Plugin-Aufteilung

- Auf der zentralen Agentur-Website werden `frontend-rechtstexte-generator` und das private Add-on `frontend-rechtstexte-generator-agency-hub` installiert.
- Kunden erhalten ausschließlich `frontend-rechtstexte-generator`. Diese Ausgabe kann Textbausteine empfangen, aber weder einen Feed veröffentlichen noch Lizenzen verwalten.
- Das Agency-Hub-Add-on darf nicht in einem öffentlichen Kunden-Download oder öffentlichen Repository-Release enthalten sein.

Auf der Hauptseite:

1. Basisplugin und Agency-Hub-Add-on aktivieren.
2. Unter `Rechtstexte > Übersicht & Textbausteine > Textverteilung` die Rolle `Zentrale` auswählen.
3. Einstellungen speichern und anschließend `Rechtstexte > Rechtstexte Lizenzen` öffnen.
4. Eine Jahreslizenz mit Kundennamen, Ablaufdatum und Website-Limit anlegen.
5. Feed-URL und individuellen Lizenzschlüssel direkt aus der Lizenzkarte für die Kundenseite kopieren.
6. Neue Textbausteine wie gewohnt prüfen und ausdrücklich veröffentlichen.

### Master als eigene Agentur

Die Master-Installation kann gleichzeitig als Agentur für direkt betreute Kunden dienen. Dafür ist keine Selbstlizenz und keine zweite WordPress-Installation erforderlich. Unter `Rechtstexte > Kunden & Agenturen` wird für einen eigenen Kunden die Zugangsart `Eigene Kunden-Website – direkt vom Master betreut` gewählt. Der erzeugte Website-Schlüssel erhält unmittelbar den freigegebenen Master-Textstand. Externe Agenturen werden im selben Bereich separat angelegt und erhalten weiterhin einen Agentur-Hauptschlüssel mit eigenem Kontingent.

Auf einer Kundenseite:

1. Die Rolle `Kundenseite` auswählen.
2. Feed-URL und individuellen Lizenzschlüssel eintragen.
3. `Speichern und Verbindung testen` ausführen.
4. Die tägliche automatische Synchronisierung aktiviert lassen.

### Agenturlizenzen

1. In deiner Master-Zentrale eine Lizenz vom Typ `Agentur mit Kundenschlüsseln` anlegen und das Kundenkontingent festlegen.
2. Den Agentur-Hauptschlüssel auf der WordPress-Seite der Agentur als Lizenzschlüssel eintragen und die Verbindung testen.
3. Danach erscheint bei der Agentur unter `Rechtstexte > Agentur-Kunden` eine eingeschränkte Kundenverwaltung.
4. Die Agentur erstellt dort für jede Kundenwebsite einen eigenen Schlüssel. Feed-URL und Schlüssel stehen im Agenturbereich gemeinsam als kopierbare Verbindungsdaten bereit.

Agenturen können Kundenschlüssel innerhalb ihres zentral festgelegten Kontingents erstellen oder sperren. Unter `Rechtstexte > Agentur-Kunden` wählen sie zusätzlich, ob ihre Kundenseiten den freigegebenen Master-Stand oder einen eigenen Agenturstand erhalten. Für einen eigenen Stand deaktiviert die Agentur die Master-Synchronisierung, bearbeitet ihre Textbausteine lokal und veröffentlicht den aktuellen Stand anschließend über die Agenturverwaltung. Beim späteren Wechsel zurück zum Master bleiben die eigenen Texte gesichert.

Laufzeit, Kontingent und Agenturstatus bleiben ausschließlich unter Kontrolle der Master-Zentrale. Dort ist außerdem sichtbar, welche Textquelle eine Agentur aktuell verwendet. Wird die Agenturlizenz gesperrt oder läuft sie ab, werden auch alle zugehörigen Kundenschlüssel beim nächsten Abruf abgewiesen. Bereits veröffentlichte Rechtstexte bleiben sichtbar.

Die erste erfolgreiche Verbindung registriert die Domain automatisch, sofern das Website-Limit nicht erreicht ist. In der Lizenzzentrale können Lizenzen verlängert oder gesperrt und nicht mehr verwendete Domains freigegeben werden. Rechnungen und Zahlungseingänge werden bewusst außerhalb des Plugins verwaltet. Der bisherige gemeinsame Verbindungsschlüssel kann für eine Übergangszeit aktiviert bleiben, besitzt aber weder Laufzeit noch Website-Limit.

Übertragen werden ausschließlich die aktiven Blocktexte und zugehörigen Rechtsgrundlagen. Das ist je Baustein entweder der ausdrücklich veröffentlichte eigene Text oder der aktuelle mitgelieferte Standardtext. Platzhalter bleiben erhalten und werden erst auf der Kundenseite mit deren lokalen Wizard-Daten ausgefüllt. KI-Entwürfe, interne Notizen, Profile und Kundendaten werden nicht übertragen. Bei einem Abruffehler oder nach Ablauf einer Lizenz bleibt die zuletzt erfolgreich gespeicherte lokale Version aktiv. Für eine vollständige Kompatibilität sollten Zentrale und Kundenseiten möglichst dieselbe Plugin-Version verwenden.

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

Aktueller Release: `2.1.4`

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
