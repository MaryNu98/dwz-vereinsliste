=== DWZ-Vereinsliste ===
Contributors: nuerenberg
Tags: chess, dwz, german-chess, club, block
Requires at least: 5.0
Tested up to: 7.0
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Displays a club's DWZ (German rating) list as a widget. Data is fetched from the German Chess Federation API and requires an API token.

== Description ==

DWZ Club List fetches and displays the current DWZ list for a club using the DSB API.

Features:

* Fetches member ratings from the DSB API (requires API token)
* 24-hour caching to reduce API load
* Configurable columns (DWZ, Elo, Rapid, Blitz, nation, status)
* Sorts players by DWZ
* Responsive table design

== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Insert the 'DWZ-Liste' block on a page and configure VKZ and API token

== Frequently Asked Questions ==

= How do I get an API token? =

Generate an API token on the German Chess Federation's website: https://www.schachbund.de/wertungsportal-api.html

= Can I use multiple blocks with different clubs? =

Yes — each block instance can be configured with a different VKZ and token.

== Screenshots ==

1. Club list in the frontend
2. Block configuration UI in the editor

== Changelog ==

= 1.0.0 =
* Initial release
* Block to display club DWZ lists
* Caching implemented

== Support ==

For support contact the plugin author.

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
