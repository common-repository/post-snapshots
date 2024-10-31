=== Post Snapshots ===
Contributors: blackbam
Tags: snapshot, revision, history, post-status
Requires at least: 4.8
Tested up to: 4.9.6
Requires PHP: 7.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Create, manage and view snapshots of any post (or custom post type) whenever you want. Like user-managed revisions with a lot of useful functionality.

== Description ==

Create, manage and view snapshots of any post (or custom post type) whenever you want. Like user-managed revisions with a lot of useful functionality.

Features:
* Create snapshots of any post type including all metadata at any point manually as a secure history and backup of your posts
* Uses the standard WordPress posts table (like revisions)
* Uses a custom post status (and therefore works for any post type)
* Easy snapshot management meta box
* Create Snapshots comfortably in the publish post box
* Choose which post types you want to enable the snapshots feature for

PHP Developer API:
`pos_create_snapshot_from($post_id)`: Creates a new snapshot for a given post ID
`pos_delete_snapshot($snap_id)`: Delete a snapshot by its ID
`pos_get_latest_snapshot($post_id)`: Returns the ID of the latest snapshot for a given post ID
`pos_get_plainview_url($snap_id)`: Get the URL to the plain snapshot view


This Plugin is sponsored by ready2order GmbH, the company which is producing Austria's best point-of-sale system. Visit us at https://www.ready2order.com/.

NOTE: The use of a custom post status in WordPress is still in beta. The developers of this plugin are in no possible case responsible for any data loss.
If you want to be sure nothing happens, backup your database on a regular basis. You should do it anyway.


== Installation ==

This section describes how to install the plugin and get it working.

e.g.

1. Upload `plugin-name.php` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to 'Settings' -> 'Post Snapshots' and set the post types to enable snapshots for
4. Use the meta boxes in your edit posts screen

== Frequently Asked Questions ==

Currently none.

== Screenshots ==

1. Extended post publish box with snapshot option
2. Post snapshot management meta box
3. Simple settings page
4. View of a snapshots plain contents (also view in site is possible)

== Changelog ==

= 0.9 =
* Initial - according to the first tests fully functional