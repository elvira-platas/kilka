=== Kilka Exhibitions ===
Contributors: elvira-platas
Tags: exhibition, images, storytelling, custom post type
Requires at least: 5.7
Tested up to: 6.7
Requires PHP: 5.6
Stable tag: 0.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Provides portable exhibition content and editor foundations for the Kilka ecosystem.

== Description ==

Kilka Exhibitions is an optional first-party companion plugin for the Kilka theme fork. The theme works without it, and the plugin does not depend on Kilka Second Blog.

Version 0.2.0 provides the portable Exhibition post type, exhibition-level metadata, and a constrained block editor. Each exhibition contains one Sequence whose ordered children can only be Image, Text, or Pause Spaces.

The editor exposes curatorial presets rather than arbitrary pixel sizes or per-work CSS. Image Spaces use the WordPress media library and retain accessible alternative text and caption information.

An Image Space caption can be shown below the image, assigned to an information panel, or kept in accessible-only hidden output. The plugin exposes ordered information-panel data so a supporting theme can present it without making the stored exhibition content theme-dependent.

Text Spaces provide constrained width, horizontal and vertical placement, text-scale, minimum-height, alignment, interval, and optional short-line marker presets. Taller spaces use minimum heights so wrapped or enlarged text is never clipped. Vertical placement is only enabled when a taller space has been selected.

The Exhibition information document panel edits an optional public heading, the brief description, creator, rights notice, and public information-panel state. The administrative Exhibition title remains independent, so a supporting theme can omit or replace the visible panel heading while retaining a useful title for content management and accessibility. A supporting theme can retrieve these portable values together with the ordered work captions.

== AI Assistance ==

Development of this plugin was carried out with substantial assistance from OpenAI Codex and Google Gemini. These AI systems were used to generate and modify code, review changes, prepare documentation, and guide testing. Elvira directed the work, evaluated the results, and is responsible for published releases.

== Installation ==

1. Upload the `kilka-exhibitions` folder to `/wp-content/plugins/` or install its ZIP from the Plugins screen.
2. Activate the plugin through the "Plugins" screen in WordPress.
3. Open `Exhibitions` in the WordPress administration menu.

== Frequently Asked Questions ==

= Is this plugin required by the Kilka theme? =

No. The Kilka theme works without this plugin. Install it only when exhibition content is needed.

= Does this plugin require Kilka Second Blog? =

No. The two plugins are independent.

= Will exhibition content stay if I switch themes? =

Yes. The Exhibition post type and its metadata are registered by this plugin, so the content remains available while the plugin is active.

= Does version 0.2.0 include the final exhibition presentation? =

No. Version 0.2.0 provides the first working editor vertical slice, core information controls, and neutral structural block output. Search and wall-tone controls, final theme integration, the shared footer, and visual treatments remain in development.

== Changelog ==

= 0.2.0 =
* Adds one locked Sequence container to every new Exhibition.
* Adds constrained Image, Text, and Pause Space blocks.
* Adds curatorial presets for scale, alignment, intervals, captions, and rights overrides.
* Adds structural Text Space presets for text scale, minimum height, and vertical placement.
* Adds one restrained optional short-line marker for Text Spaces.
* Adds neutral structural styles and accessible saved markup.
* Exposes ordered caption and rights data for a theme-owned exhibition information panel.
* Adds document-sidebar controls for an optional information heading, exhibition description, creator, rights, and panel visibility.

= 0.1.0 =
* Adds the portable `kilka_exhibition` post type.
* Registers exhibition-level metadata for future editor controls.
* Provides independent activation and rewrite handling.

== Upgrade Notice ==

= 0.2.0 =
Adds the first working Sequence and Space editor. The final exhibition presentation is still in development.

= 0.1.0 =
Initial development foundation. The dedicated sequence editor is not included yet.
