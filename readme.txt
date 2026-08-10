=== DWZ-Vereinsliste ===
Contributors: Marius Nürenberg
Tags: schach, dwz, schachbund, verein, block
Requires at least: 5.0
Tested up to: 7.0
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Zeigt die DWZ-Liste eines Schachvereins direkt im WordPress-Block oder Widget an. Alle Daten werden jetzt über die API des Deutschen Schachbundes geladen. Für den Zugriff wird ein API-Token benötigt, den man unter https://www.schachbund.de/wertungsportal-api.html generieren kann.

== Description ==

Das Plugin "DWZ-Vereinsliste" ist speziell für Schachvereine entwickelt und bietet die Möglichkeit, die aktuelle DWZ-Liste (Deutsche Wertungszahl) eines Vereins direkt auf der Website anzuzeigen.

**Features:**

* Anzeige der aktuellen DWZ-Liste eines Vereins
* Alle Daten werden direkt aus der API des Deutschen Schachbundes geladen
* Kein zusätzlicher FIDE-Import mehr erforderlich
* API-Token für den Zugriff auf die DSB-API erforderlich
* 24-Stunden-Caching zur Entlastung der API
* Responsive Design - funktioniert auf allen Geräten
* Nach DWZ sortierte Spielerliste

**Wie es funktioniert:**

1. Der Block oder das Widget wird auf der Seite eingebunden
2. Die VKZ des Vereins und ein API-Token werden eingegeben
3. Das Plugin lädt die Daten über die API des Deutschen Schachbundes
4. Die Spielerliste wird nach DWZ-Rang sortiert und angezeigt
5. Die Daten werden 24 Stunden gecacht, um die API nicht zu überlasten

**Anforderungen:**

* WordPress 5.0 oder höher
* PHP 7.4 oder höher
* Internetverbindung zum Abrufen der Daten (mit 24h Cache)
* API-Token des Deutschen Schachbundes

**Verwendung:**

1. Das Plugin installieren und aktivieren
2. Den Block oder das Widget "DWZ-Vereinsliste" auf einer Seite einfügen
3. Die VKZ des Vereins eingeben
4. Den API-Token eintragen
5. Optional die anzuzeigenden Spalten konfigurieren

== Installation ==

1. Das Plugin-Verzeichnis hochladen oder via WordPress-Plugin-Suche installieren
2. Das Plugin in WordPress aktivieren
3. Den Block oder das Widget "DWZ-Vereinsliste" dort einfügen, wo die DWZ-Liste angezeigt werden soll
4. Die VKZ des Vereins eingeben
5. Den API-Token eintragen
6. Optional die anzuzeigenden Spalten konfigurieren

== Frequently Asked Questions ==

= Was ist die VKZ-Nummer meines Vereins? =

Die VKZ-Nummer (Vereinskennzahl) ist die eindeutige Nummer des Schachvereins im Deutschen Schachbund. Man findet die VKZ auf der Website des DSB über die Vereinssuche.

= Wie bekomme ich einen API-Token (Zugangsschlüssel)? =

Den API-Token (Zugangsschlüssel) muss man auf der Seite des Deutschen Schachbundes generieren: https://www.schachbund.de/wertungsportal-api.html

= Wie lange werden die Daten gecacht? =

Alle Daten werden 24 Stunden gecacht, um die API des Deutschen Schachbundes nicht zu überlasten.

= Welche Daten werden angezeigt? =

Das Plugin zeigt Name, DWZ (Deutsche Wertungszahl), DWZ-Index und die Platzierung des Spielers an. Optional können weitere Informationen wie Status und Elo-Daten angezeigt werden. 

= Kann ich mehrere Blöcke oder Widgets mit unterschiedlichen Vereinen verwenden? =

Ja, Sie können mehrere Blöcke oder Widgets mit unterschiedlichen Vereinslisten auf der gleichen Homepage hinzufügen.

= Was ist die API des Deutschen Schachbundes? =

Die API ist eine technische Schnittstelle, die es erlaubt, DWZ-Daten vom Deutschen Schachbund abzurufen. 


== Screenshots ==

1. Die DWZ-Liste im Frontend
2. Die Block- oder Widget-Konfiguration im Backend

== Changelog ==

= 1.0.0 =
* Initiale Veröffentlichung
* Block bzw. Widget zum Anzeigen der DWZ-Liste
* Caching implementiert
* Responsive Design

== Support ==

Bei Fragen oder Problemen kontaktieren Sie Marius Nürenberg.

== License ==

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1335 USA
