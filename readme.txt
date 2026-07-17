=== DWZ-Vereinsliste ===
Contributors: Marius Nürenberg
Tags: schach, dwz, schachbund, verein, widget
Requires at least: 5.0
Tested up to: 7.0
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Zeigt die DWZ-Liste eines Schachvereins via Widget. Die Daten werden von der API des Deutschen Schachbundes abgerufen und von der Homepage der FIDE heruntergeladen.

== Description ==

Das Plugin "DWZ-Vereinsliste" ist speziell für Schachvereine entwickelt und bietet die Möglichkeit, die aktuelle DWZ-Liste (Deutsche Wertungszahl) eines Vereins direkt auf der Website anzuzeigen.

**Features:**

* Einfaches Widget zum Anzeigen der DWZ-Liste
* Automatische Abfrage der Daten von der API des Deutschen Schachbundes
* 24-Stunden-Caching zur Entlastung der API
* Responsive Design - funktioniert auf allen Geräten
* Nach DWZ sortierte Spielerliste
* Optional zuschaltbare Elo-, Rapid- und Blitz-Spalten

**Wie es funktioniert:**

1. Das Plugin fragt die Daten von der API des Deutschen Schachbundes ab
2. Die Spielerliste wird nach DWZ-Rang sortiert und angezeigt
3. Die Daten werden 24 Stunden gecacht, um die API nicht zu überlasten

**Anforderungen:**

* WordPress 5.0 oder höher
* PHP 7.4 oder höher
* Internetverbindung zum Abrufen der Daten (mit 24h Cache)

**Verwendung:**

1. Das Plugin installieren und aktivieren
2. Das Widget "DWZ Vereinsliste" auf einer Seite hinzufügen
3. Den Verein auswählen
4. Optional die anzuzeigenden Spalten konfigurieren

== Installation ==

1. Das Plugin-Verzeichnis hochladen oder via WordPress-Plugin-Suche installieren
2. Das Plugin in WordPress aktivieren
3. Das Widget "DWZ-Vereinsliste" dort hinzufügen, wo die DWZ-Liste angezeigt werden soll
4. Den Verein auswählen 
5. Optional die anzuzeigenden Spalten konfigurieren

== Frequently Asked Questions ==

= Was ist die VKZ-Nummer meines Vereins? =

Die VKZ-Nummer (Vereinskennzahl) ist die eindeutige Nummer des Schachvereins im Deutschen Schachbund. Im Widget kann man Vereine über die VKZ oder den Vereinsnamen finden.

= Wie lange werden die Daten gecacht? =

Alle Daten gecacht, um die API des Deutschen Schachbundes nicht zu überlasten. 
Die Daten des DSB haben eine Cache-Dauer von 24 Stunden.
Die Daten der FIDE ändern sich nur monatlich, deshalb werden diese immer am 1. eines Monats um 8 Uhr per Cron-Job neu heruntergeladen.
Im Wordpress-Admin-Dashboard kann der Cache gelöscht werden oder FIDE-Daten manuell aktualisiert werden.

= Welche Daten werden angezeigt? =

Das Plugin zeigt Name, DWZ (Deutsche Wertungszahl), DWZ-Index und die Platzierung des Spielers an. Optional können FIDE-Nation, Status, Elo, Rapid und Blitz angezeigt werden. Aus Datenschutzgründen werden Geburtsdatum und Geschlecht nicht angezeigt.

= Kann ich mehrere Widgets mit unterschiedlichen Vereinen verwenden? =

Ja, Sie können mehrere Widgets mit unterschiedlichen Vereinslisten auf der gleichen Homepage hinzufügen.

= Was ist die API des Deutschen Schachbundes? =

Die API ist eine technische Schnittstelle, die es erlaubt, DWZ-Daten vom Deutschen Schachbund abzurufen. Weitere Informationen finden Sie unter: https://www.schachbund.de/rest-schnittstelle-des-wertungsportals.html

= Woher kommen die Elo-Daten? =

Für die Elo-Daten stellt die FIDE keine API bereit, deshalb werden diese monatlich komplett auf den Server heruntergeladen und in einer SQLite Datenbank gespeichert. Download-Quelle: https://ratings.fide.com/download_lists.phtml

= Ich habe Probleme, wenn ich einen Verband auswähle =

Prinzipiell sollte auch die Auswahl von Verbänden eine DWZ-Liste liefern. 

= Warum werden bei Verbänden bei entsprechenden Einstellungen passive Mitglieder nicht mit "P" markiert? =

Das ist technisch nicht möglich.


== Screenshots ==

1. Das DWZ-Widget im Frontend
2. Die Widget-Konfiguration im Backend

== Changelog ==

= 1.0.0 =
* Initiale Veröffentlichung
* Widget zum Anzeigen der DWZ-Liste
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
