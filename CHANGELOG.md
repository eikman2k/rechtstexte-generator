# Changelog

## 2.1.4 - 2026-09-09

- JSON-/Feed-URL in der Master-Zentrale und in der Agenturverwaltung direkt kopierbar gemacht.
- Lizenzschlüssel und Feed-URL je Kunden-Website als vollständige Verbindungsdaten zusammengeführt.
- Von externen Agenturen erzeugte Kundenschlüssel auch in der Master-Zentrale sichtbar und kopierbar gemacht.
- Lizenzschlüssel bleiben aus Sicherheitsgründen getrennt von der Feed-URL.
- Produktseite grenzt eine mögliche externe juristische Prüfung ausdrücklich vom angebotenen Leistungsumfang ab.

## 2.1.3 - 2026-09-09

- Absende-Button des Zugangsformulars vollständig von kollidierenden WordPress- und Theme-Buttonklassen isoliert.
- Eigene Button-Komponente mit dauerhaftem Hintergrund, Kontrast und Fokusdarstellung ergänzt.

## 2.1.2 - 2026-09-09

- Primären Button „Zugang anlegen“ im Ruhezustand wieder deutlich sichtbar gemacht.
- Hover- und Tastaturfokus-Zustände des Buttons kontrastreich vereinheitlicht.
- Asset-Version erhöht, damit WordPress und Browser die korrigierten Admin-Stile neu laden.

## 2.1.1 - 2026-09-09

- Kunden-/Agenturformular optisch neu aufgebaut und abgeschnittene Zugangsart entfernt.
- Zugangsarten werden als verständliche, vollständig klickbare Auswahlkarten dargestellt.
- Kundendaten, Kontingent, Laufzeit und interne Notiz in einem responsiven Raster angeordnet.
- Darstellung aller verwendeten Eingabetypen vereinheitlicht.
- Patch-Version zur zuverlässigen Aktualisierung zwischengespeicherter Admin-Assets erhöht.

## 2.1.0 - 2026-09-09

- Agenturen können die Synchronisierung mit dem Master aktivieren oder deaktivieren.
- Eigene Agenturtexte lassen sich als separater veröffentlichter Blockstand an alle zugehörigen Kundenschlüssel verteilen.
- Kundenseiten erhalten abhängig vom Agenturmodus automatisch den Master-Stand oder den veröffentlichten Agenturstand.
- Individuelle Agenturtexte werden beim Wechsel zum Master gesichert und beim späteren Wechsel zurück wiederhergestellt.
- Automatische Master-Abrufe werden gestoppt, solange eine Agentur ihren eigenen Textstand verwendet.
- Master-Zentrale zeigt die aktuell verwendete Textquelle jeder Agentur an.
- Master-Zentrale kann ohne Selbstlizenz gleichzeitig als Agentur für direkt betreute Kunden-Websites verwendet werden.
- Kunden- und Agenturverwaltung unterscheidet sichtbar zwischen eigenen Direktkunden und externen Agenturen.
- Formular zum Anlegen von Kunden und Agenturen mit auswählbaren Zugangskarten und einem responsiven Zweispalten-Raster neu gestaltet.
- E-Mail-, Zahlen-, URL- und Telefonfelder verwenden jetzt dieselbe konsistente Admin-Gestaltung wie die übrigen Eingaben.
- Produktseite um die wählbare Textquelle und selbst verwaltete Agenturtexte erweitert.

## 2.0.0 - 2026-09-09

- Kundenplugin und privates Agency-Hub-Add-on technisch getrennt.
- Feed-Veröffentlichung und Lizenzverwaltung vollständig aus dem Kundenplugin entfernt.
- Zentralmodus ist nur verfügbar, wenn das Agency-Hub-Add-on installiert und aktiviert ist.
- Agency Hub wird in einer Multisite ausschließlich auf der Netzwerk-Hauptseite bereitgestellt.
- Lizenzzentrale für manuell abgerechnete Jahreslizenzen im Agency Hub ergänzt.
- individuelle Lizenzschlüssel mit Ablaufdatum und konfigurierbarem Website-Limit eingeführt.
- Kundendomains werden bei der ersten erfolgreichen Feed-Synchronisierung automatisch registriert.
- verbundene Domains, Plugin-Version und letzter Abruf werden in der Zentrale angezeigt.
- Lizenzen können verlängert, gesperrt und wieder freigegeben werden; Domains lassen sich bei einem Website-Wechsel lösen.
- Textbaustein-Feed authentifiziert Kundenseiten mit Lizenzschlüssel, Website-URL und Plugin-Version.
- abgelaufene oder gesperrte Lizenzen liefern verständliche Fehlermeldungen; die letzte lokale Textversion bleibt aktiv.
- bisherigen gemeinsamen Verbindungsschlüssel als abschaltbaren Übergangszugang beibehalten.
- Textverteilung im Backend um direkten Zugang zur Lizenzverwaltung und Anzeige der Lizenzlaufzeit ergänzt.
- Agenturlizenzen mit zentral festgelegtem Kundenkontingent ergänzt.
- Agenturen können über eine eingeschränkte Remote-Verwaltung individuelle Schlüssel für ihre Kundenwebsites erstellen und sperren.
- Unterlizenzen übernehmen Laufzeit und Sperrstatus der übergeordneten Agenturlizenz und können das zentrale Kontingent nicht erhöhen.
- komplette Plugin-Verwaltung aus dem WordPress-Bereich „Einstellungen“ in einen eigenen Hauptmenüpunkt „Rechtstexte“ verschoben.
- Wizard, Agentur-Kunden und Lizenzzentrale als kontextabhängige Untermenüs eingeordnet.

## 1.9.0 - 2026-09-09

- Bundesland-Auswahl für die zuständige Datenschutzaufsichtsbehörde ergänzt.
- Namen, Anschriften und Websites der allgemeinen Landesaufsichtsbehörden für alle 16 Bundesländer hinterlegt.
- Behördenangaben werden bei Auswahl automatisch übernommen, bleiben aber manuell bearbeitbar.
- Zuständigkeitshinweis für öffentliche Stellen, Kirchen, Presse und Rundfunk ergänzt.
- Hosting-Ausgabe für Völkel EDV Systeme und netcup GmbH vereinheitlicht und bekannte Anschriften vervollständigt.
- Kontaktformular, Server-Logfiles und lokale Google Fonts in ausführlicher und kompakter Ausgabe konsistent gemacht.
- Technische Formularsysteme werden unabhängig vom Lesbarkeitsmodus nicht mehr veröffentlicht.
- Name der Aufsichtsbehörde wird bei vorhandener URL direkt klickbar ausgegeben.
- ältere VServer-Bezeichnungen werden für netcup sprachlich zu „virtuelle Server (VServer) der netcup GmbH“ vereinheitlicht.
- § 18 Abs. 2 MStV erfordert jetzt eine neue ausdrückliche Bestätigung journalistisch-redaktioneller Inhalte; alte pauschale Auswahlen werden nicht übernommen.
- Individuelle Berufsbezeichnungen werden unverändert aus den Stammdaten übernommen.
- pauschale Kammer-Homepage-Links werden nicht mehr als berufsrechtliche Regelung veröffentlicht.
- Staat oder Land der Verleihung wird nur noch nach ausdrücklicher Aktivierung für eine konkret betroffene Berufsbezeichnung veröffentlicht.
- Abschnittsabstände im Impressum für Kontakt, Register, Umsatzsteuer, berufsspezifische Angaben und Berufshaftpflicht vereinheitlicht.

## 1.8.2 - 2026-09-08

- technische Angabe zum verwendeten Formularsystem aus dem Kompakttext entfernt
- Elementor bleibt ein internes Erkennungsmerkmal und wird nicht als Empfänger oder Dienstleister dargestellt
- Login-Datenschutzblock bleibt ausschließlich an eine bewusste Auswahl eines öffentlichen Nutzerbereichs gebunden

## 1.8.1 - 2026-09-08

- eigenständigen Kompaktbaustein für Kontaktformulare ergänzt
- Kontaktformular im Kompaktmodus auf Zweck, Rechtsgrundlage und Speicherdauer reduziert
- verwendetes Formularsystem bleibt als kurze konkrete Angabe erhalten
- Serverstandort-Auswahl kennzeichnet Deutschland und EU/EWR als zu bestätigende Angaben
- Hilfetext empfiehlt bei nicht verifiziertem Serverstandort ausdrücklich „Unbekannt“

## 1.8.0 - 2026-09-08

- eigenständige veröffentlichte Kompaktbausteine unabhängig von ausführlichen Live-Overrides ergänzt
- Hosting, Server-Logfiles und lokale Google Fonts als deutlich kürzere Kompaktmodule umgesetzt
- optionale Kompakttexte pro Baustein im Backend bearbeitbar gemacht
- Kompakttexte in den zentralen Textbaustein-Feed aufgenommen
- Kompaktmodus behält Anbieter, Adressen, Infrastruktur, Serverstandort, Rechtsgrundlage und AV-Angaben bei

## 1.7.0 - 2026-09-08

- optionalen Lesbarkeitsmodus „Kompakt“ für Datenschutzerklärungen ergänzt
- Hosting, Server-Logfiles und lokale Google Fonts im kompakten Modus gestrafft
- kompakte Darstellung auf eine angenehmere Zeilenlänge begrenzt
- Pflichtangaben, Anbieterdaten, Adressen, AV-Angaben und Rechtsgrundlagen bleiben erhalten
- eigene und zentral synchronisierte Live-Overrides werden nicht automatisch umgeschrieben

## 1.6.3 - 2026-09-08

- Cloudflare Turnstile als auswählbaren Datenschutzbaustein ergänzt
- Turnstile-Erkennung anhand der offiziellen Script-URL und Widget-Klasse ergänzt
- Hinweis bei Kontaktformularen ohne ausgewählten externen Spam-Schutz ergänzt
- bestätigt: WordPress-Admin-Login und `/wp-login.php` aktivieren keinen Login-Datenschutzabschnitt

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
