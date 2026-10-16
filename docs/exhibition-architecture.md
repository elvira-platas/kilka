# Exhibitions

This document describes Kilka Exhibitions 0.2.0 with Kilka 1.3.0.
Development takes place on `main`.

## Responsibilities

The plugin registers the `kilka_exhibition` post type, metadata and editor
blocks. It owns the saved content, structural styles and helpers for captions
and credits. It has no public archive; link to individual exhibitions from
pages or navigation.

The theme supplies `single-kilka_exhibition.php`, the shared site header and
footer, image shadows and the information panel. It omits ordinary post
metadata, blog sidebars, post navigation and comments. A visually hidden H1
preserves the exhibition title.

The plugin is independent of Second Blog and Reader. Switching themes leaves
content editable while the plugin is active. Blocks retain their saved markup
and structural styles; another theme does not automatically receive Kilka's
information dialog or full exhibition presentation.

Install `kilka-exhibitions.zip` separately from `kilka.zip`. See
[release packaging](github-release-plan.md) for all four project packages.

## Content model

Each new Exhibition contains one locked **Sequence**. Its children can only
be **Image Space**, **Text Space** and **Pause Space**, which can be added and
reordered in the block editor.

The design uses intervals between works rather than a gallery grid. The
original references were `ma` (an active interval), tokonoma (a place for one
focused encounter) and emaki (a sequence revealed at the viewer's pace).
They guide spacing, not decorative styling.

### Exhibition information

The document sidebar provides:

- optional public information heading, separate from the administrative title;
- brief description, stored as the standard excerpt;
- creator and rights notice;
- information-panel visibility.

An empty public heading hides the visible heading in the panel; the dialog
still has an accessible name. Exhibition-level credits appear once. Image-level
overrides appear only beside the relevant work.

Wall-tone and search-summary metadata are registered, but their editor
controls are not implemented. They should not be described as available
settings.

### Image Space

Select an image from the Media Library and supply meaningful alt text. Controls
include:

- image presence from 35 to 100, in steps of 5;
- left, centre or right placement;
- short, normal or long interval after the image;
- caption mode: information panel, visible or hidden;
- optional creator and rights overrides.

Width and viewport height both constrain image size. Visible captions follow
the displayed image width, including portrait images limited by height.

| Caption mode | Visible output | Saved markup |
|---|---|---|
| Information panel | Ordered list in the theme's information panel | Accessible `figcaption` |
| Visible | Below the image, without duplication in the panel | `figcaption` |
| Hidden | No visual caption | Visually hidden `figcaption` |

The plugin exposes caption and credit data; the theme renders the dialog.

### Text Space

Text Space supports paragraphs, quotations and section markers. Controls cover
width, horizontal placement, left or centre text alignment, interval after the
text, and these presets:

- text scale: small, normal, large or statement;
- minimum height: by content, half viewport or full viewport;
- vertical position: top, centre or bottom;
- marker: none or a short line.

Height uses `min-height` so long or enlarged text can grow. Vertical position
is disabled when height follows content. Arbitrary pixel sizes, frames and
per-block decorative CSS are not editor options.

### Pause Space

An empty interval with short, normal, long or full-viewport length.

## Theme presentation

Exhibitions use the light wall color `#FAFAF8`, independently of the site's
saved light/dark preference. The color switcher is absent on exhibition pages;
the visitor's preference still applies elsewhere.

The site title starts at the upper left and scrolls with the header. The global
menu and information button stay fixed on the right. Images have a two-layer
shadow without frames or hover animation. Smaller screens use shorter shadow
offsets. Current color and shadow values are in the
[color-scheme document](dark-theme-color-plan.md).

The information panel contains the optional heading and description, shared
credits, and an ordered list of works. It closes with its button, the backdrop
or Escape, keeps focus inside while open and returns focus on close. Without
JavaScript, information remains available in the page.

The footer uses the site's shared markup and settings and follows the last
space. It is not fixed over the works. The separate Reading view with Reader
active intentionally omits the site frame.

## Editing and checks

1. Create an Exhibition and enter its administrative title.
2. Add Image, Text and Pause Spaces to the Sequence.
3. Set alt text, caption modes and intervals.
4. Fill in Exhibition information as needed.
5. Reorder with the editor or List View and preview on desktop and mobile.
6. Publish and add a link where the exhibition should be reached.

Before a release, check saving and reopening blocks, caption modes, credit
overrides, keyboard focus, no-JavaScript output, narrow screens and enlarged
text. Also check another theme: portable content and structural output should
remain usable without assuming Kilka's dialog exists.

## Remaining work

- Editor controls and presentation rules for wall tone and search summary.
- Further validation of the complete information experience with other themes.
- Additional space types only when needed by actual content.
