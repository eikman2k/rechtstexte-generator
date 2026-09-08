# Changelog

## 1.6.2 - 2026-09-08

- Checkboxen und Schalter im Wizard als klar erkennbare, vollständig klickbare Auswahlkarten gestaltet
- Login-Auswahl eindeutig von `wp-admin` und dem normalen WordPress-Login abgegrenzt
- leere Hinweiszeilen auch bei verschachtelter oder als Absatz gespeicherter Formatierung entfernt
- Hosting-Standardtext gegenüber dem Server-Logfile-Abschnitt gestrafft
- lokalen Google-Fonts-Abschnitt auf die wesentliche Aussage gekürzt

## 1.6.1 - 2026-09-08

- weitere Umlautersatzschreibweisen und den Begriff `Resource` in Live-Overrides normalisiert
- technische Überschrift „Verbindliche Angaben zu diesem Bereich“ aus ergänzten Pflichtdaten entfernt
- leere Hinweisüberschriften unabhängig von ihrer Position aus veröffentlichten Texten entfernt
- Backup-Rechtsgrundlage und Artikelangaben bei Betroffenenrechten ergänzt
- unsichere pauschale Drittlandaussage im Kontaktformular-Override wird nicht veröffentlicht
- automatische Qualitätsindikatoren für aktive Texte und Arbeitsentwürfe im Backend ergänzt

## 1.6.0 - 2026-09-08

- unfertige KI-, SMTP- und Vimeo-Abschnitte werden bis zur Eingabe konkreter Anbieter- und Konfigurationsdaten nicht veröffentlicht
- allgemeinen Sammelblock für eingebettete externe Ressourcen aus der Ausgabe entfernt, um Doppelungen mit konkreten Dienstblöcken zu vermeiden
- Hosting-Rechtsgrundlage aus älteren Live-Overrides direkt auf Art. 6 Abs. 1 lit. f DSGVO normalisiert
- offene Platzhalter, leere Elemente und verwaiste Hinweisüberschriften werden aus der finalen Ausgabe entfernt
- häufige deutsche ASCII-Ersatzschreibweisen in zentralen und KI-basierten Live-Overrides korrigiert
- lokale Google-Fonts-Ausgabe gekürzt sowie Vimeo- und Consent-Management-Ausgabe präzisiert
- Login-Hinweis im Wizard vom reinen WordPress-Admin-Zugang abgegrenzt

## 1.5.1 - 2026-09-08

- deaktivierter Drittland-Schalter entfernt nun auch allgemeine Drittlandpassagen aus älteren Hosting-, KI- und zentral synchronisierten Live-Overrides

## 1.5.0 - 2026-09-08

- eindeutigen Schalter „Drittlandtransfer vorhanden“ ergänzt; der allgemeine Drittlandabschnitt erscheint nur noch bei aktiver Bestätigung und konkretem Text
- Datenschutzerklärung auf konkrete Tatsachen statt Eventualformulierungen umgestellt: Drittlandabschnitt nur bei konkretem Bezug, Social-Media-Links getrennt von Einbettungen und präzisere Vimeo-/SMTP-Ausgabe
- Hosting-Rechtsgrundlage `Art. 6 Abs. 1 lit. f DSGVO` wird auch bei zentralen oder KI-basierten Live-Overrides systemseitig ergänzt
- veröffentlichte Rechtstexte enthalten keine internen Generator- oder Rechtsberatungshinweise mehr; diese bleiben im Wizard sichtbar
- Backup-Text konzentriert sich auf Speicherort und Aufbewahrung statt auf den Namen des verwendeten WordPress-Plugins
- Netcup-Anschrift im internen Backend-Beispieldatensatz auf Emmy-Noether-Straße 10, 76131 Karlsruhe aktualisiert
- redaktionelle Prüfanweisungen werden zuverlässig aus veröffentlichten Datenschutzerklärungen entfernt
- doppelte allgemeine Angaben zu Speicherdauer und Drittlandtransfer bereinigt
- konkrete Datenschutzaufsichtsbehörde und Backup-Speicherziel im Wizard ergänzt
- Google-Fonts-Auswahl gegen widersprüchliche lokale und externe Ausgabe abgesichert
- Hosting-Infrastruktur sprachlich als Unterauftragnehmer des Hosting-Dienstleisters präzisiert

## 1.4.0 - 2026-09-07

- vollständigen Rechtstexte-Wizard zusätzlich als geschützte Backend-Ansicht ergänzt
- Backend als geführten Aktualisierungs- und Veröffentlichungsworkflow neu gestaltet
- Such-, Bereichs- und Statusfilter für Textbausteine ergänzt
- konkrete Änderungsaufträge und Quellenhinweise werden an KI-Entwürfe übergeben
- verständliche Statusanzeigen und eindeutige Speichermeldungen ergänzt
- Statuskarten als Schnellfilter mit direkter Navigation zur Bausteinliste verlinkt
- Standardmodell auf GPT-5.6 Terra aktualisiert und verständliche Modellauswahl ergänzt
- optionalen geschützten Textbaustein-Feed für getrennte WordPress-Installationen ergänzt
- tägliche Client-Synchronisierung mit lokalem Fallback und manuellem Verbindungstest ergänzt
- Feed überträgt keine Profile, Kundendaten, Entwürfe oder internen Notizen
- aktive Standardtexte werden ebenfalls verteilt; kundenspezifische Platzhalter werden erst lokal ersetzt
- Prüfstatus der Textblöcke in redaktionelle Freigabe und dokumentierte juristische Prüfung getrennt
- Verantwortlichen der Datenschutzerklärung unabhängig vom Websitebetreiber erfassbar gemacht
- konkrete Anbieter-, Zweck-, Rechtsgrundlagen-, Speicher- und Drittlandangaben für ausgewählte Dienste ergänzt
- Vollständigkeitshinweise für ausgewählte Dienste und KI-Transparenz im Vorschauprozess ergänzt
- Rechtsform- und Vertretungslogik für weitere Unternehmensformen erweitert
- fehlerhafte Shop-Aktivierung durch eine reine Benutzerregistrierung behoben
- Seitensynchronisierung optional auf dynamische Shortcode-Inhalte umgestellt
- Scanner berücksichtigt in Multisite auch netzwerkweit aktivierte Plugins
- Generator-Regressionstests für zentrale Ausgabe- und Statusregeln ergänzt

## 1.3.0

- Multisite-Zentralausgabe fuer Impressum und Datenschutzerklaerung ergaenzt
- Netzwerk-Einstellungen fuer Master-Site und zentrales Profil hinzugefuegt
- Shortcodes auf Unterseiten koennen Inhalte aus der zentralen Master-Site ausgeben
- Wizard auf Unterseiten zeigt bei aktivem Zentralmodus einen Verwaltungshinweis
- Netzwerk-Aktivierung legt Profiltabellen fuer bestehende Sites an
- Neue Sites erhalten die Profiltabelle automatisch

## 1.2.0

- Wizard in Schritt 5 und 6 thematisch neu gegliedert
- Schulungsportal-Details nur noch sichtbar, wenn der Hauptbereich aktiv ist
- Datenschutzbereich um Hosting-Anschrift sowie Telefon und Anschrift des Datenschutzbeauftragten erweitert
- Wizard in Schritt 2 bis 4 übersichtlicher aufgebaut und optionale Felder konditional eingeblendet
- Adressen im Ergebnis als eigene Adressblöcke formatiert
- Pflichtangaben werden bei KI- oder Live-Overrides systemseitig ergänzt, wenn sie im Blocktext fehlen
- Hosting-Block ergänzt Hosting-Anbieter, Serverstandort, Hoster-Anschrift und AV-Hinweis zuverlässig aus den Formulardaten

## 1.1.0

- Frontend-Wizard, Scanner, Block-Registry und HTML-Export ausgebaut
- Datenschutz- und Impressumsmodule erweitert
- Schulungsportal- und Lernplattform-Logik ergänzt
