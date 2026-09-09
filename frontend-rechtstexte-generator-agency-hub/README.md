# Rechtstexte Generator Agency Hub

Privates Zusatzplugin der gemeinsamen Rechtstexte-Generator-Suite für die zentrale Textbaustein- und Lizenzverwaltung.

Version: `2.1.4`

Dieses Plugin gehört technisch zum Basisplugin `frontend-rechtstexte-generator`, benötigt dieselbe Plugin-Version und ist ohne das Basisplugin nicht funktionsfähig.

## Installation

1. `frontend-rechtstexte-generator` auf der zentralen WordPress-Website installieren und aktivieren.
2. Dieses Agency-Hub-Add-on zusätzlich installieren und aktivieren.
3. Unter `Rechtstexte > Übersicht & Textbausteine > Textverteilung` die Rolle `Zentrale` auswählen.
4. Unter `Rechtstexte > Rechtstexte Lizenzen` individuelle Kundenlizenzen anlegen.

Für Agenturkunden wird eine Lizenz vom Typ `Agentur mit Kundenschlüsseln` angelegt. Das Website-/Kundenlimit bestimmt, wie viele aktive Kundenschlüssel die Agentur in ihrer eingeschränkten Remote-Verwaltung erzeugen darf. Unterlizenzen übernehmen die Laufzeit und den Status der übergeordneten Agenturlizenz.

Dieses Add-on darf nicht an Kunden verteilt werden. Kundenseiten erhalten ausschließlich das Plugin `frontend-rechtstexte-generator`.

In einer WordPress-Multisite wird der Agency Hub ausschließlich auf der Hauptseite des Netzwerks bereitgestellt. Eine Netzwerkaktivierung schaltet die Lizenzzentrale nicht auf den Unterseiten frei.
