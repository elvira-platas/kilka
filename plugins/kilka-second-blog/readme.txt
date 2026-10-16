=== Kilka Second Blog ===
Contributors: elvira-platas
Tags: custom post type, taxonomy, blog
Requires at least: 5.7
Tested up to: 6.7
Requires PHP: 5.6
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds the "Second Blog" content layer for the Kilka theme by registering a dedicated post type, taxonomies, and contextual helpers.

== Description ==

Kilka Second Blog is a companion plugin for the Kilka theme fork.

Second Blog entry text uses locally bundled IBM Plex Sans by default. The editor's
Second Blog typography panel offers Literata, IBM Plex Sans, Kelly Slab,
Bad Script and Hachi Maru Pop for the entry text. Paragraph font controls
offer the same five families. The Classic block has a Paragraph font menu; Paragraph blocks
have the same choice in their block settings. Heading blocks also offer all
five families through Heading font, preserving their heading level and size.
Choose Use entry font to reset.
Entry title font defaults to Use entry font, or can independently use any of
the five families. It applies to entry titles in the Kilka list, search and
single-entry templates and previews in the editor. Title sizes and spacing
stay controlled by the theme. Metadata, tags, site/section headings and site
controls retain the theme's typography.

Bad Script paragraphs have the same modest size increase whether inherited
from the entry or chosen individually. Second Blog prose uses an 18px mobile
base size. All five families are available without content-length restrictions. Static families
use their original Regular face without simulated bold or italic; semantic
emphasis is preserved in the content. Literata and IBM Plex Sans include real
normal, bold and italic faces. Fonts are served from the site's own plugin
directory; there are no external font requests, analytics or storage.

Entry font selection is portable post metadata; paragraph choices are stored
as HTML classes. Deactivation leaves the text intact, using the active theme's
fonts. The font files, OFL licenses and source notices are in assets/fonts/.

== AI Assistance ==

Development of this plugin was carried out with substantial assistance from OpenAI Codex and Google Gemini. These AI systems were used to generate and modify code, review changes, prepare documentation, and guide testing. Elvira directed the work, evaluated the results, and is responsible for published releases.

It registers:

* `world_note` custom post type (Second Blog)
* `world_note_category` taxonomy
* `world_note_tag` taxonomy

It also provides contextual helpers used by the theme:

* contextual search post type handling
* second-blog taxonomy query filtering
* term links with preserved post type context
* slug setting integration and rewrite rule maintenance

== Installation ==

1. Upload the `kilka-second-blog` folder to `/wp-content/plugins/` or install from the Plugins screen.
2. Activate the plugin through the "Plugins" screen in WordPress.
3. Go to `Settings -> Reading` and adjust the Second Blog slug if needed.

== Frequently Asked Questions ==

= Is this plugin required by the theme? =

The Kilka theme works without this plugin, but Second Blog features require it.

= Can I change the Second Blog URL slug? =

Yes. Use `Settings -> Reading` and update the "Second Blog URL Slug" field.

= Will content stay if I switch themes? =

Yes. The CPT and taxonomies are stored by this plugin, so content stays available.

== Changelog ==

= 1.0.0 =
* Initial public release as a companion plugin.
* Registers `world_note` CPT and related taxonomies.
* Adds contextual helper functions used by the Kilka theme.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
