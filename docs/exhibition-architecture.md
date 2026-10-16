# Exhibition Architecture Draft

Status: architecture draft with the `0.2.0` content model and first editor
vertical slice implemented. This document describes the content model and
editing workflow. It does not define the final visual design.

## Purpose

Kilka exhibitions are small, deliberately composed sequences of images, text,
and empty intervals. They are not automatic media-library galleries, portfolio
grids, or collections of every image used in posts.

The first prototype is based on three related ideas:

- `ma`: an interval is an active part of the composition;
- tokonoma: one object can hold a space without competing with a collection;
- emaki: a sequence is revealed gradually and the visitor controls its pace.

These ideas guide the rhythm of the interface. They do not prescribe Japanese
visual decoration.

## Ownership of Responsibilities

### First-party exhibition plugin

A future `Kilka Exhibitions` companion plugin should own all content that must
survive a theme change:

- the Exhibition post type;
- exhibition metadata;
- the ordered sequence of spaces;
- editor controls and validation;
- neutral semantic fallback markup;
- REST registration needed by the editor.

This should be a separate first-party plugin, not part of `Kilka Second Blog`
and not a dependency on a third-party gallery plugin.

### Theme

The theme should own presentation:

- the shared site frame;
- exhibition spacing and responsive layout;
- typography and wall colours;
- image edge treatment such as shadows or frames;
- the information panel;
- the common footer.

If the theme changes while the plugin remains active, exhibition content must
remain editable and receive usable neutral fallback output.

## Repository and Installation Model

Exhibition development remains in the same GitHub monorepo as the theme and
Second Blog. A separate repository is unnecessary while the packages share one
maintainer, release process, and theme integration.

The installable products remain independent:

- `kilka.zip`: theme presentation;
- `kilka-second-blog.zip`: Second Blog content model;
- `kilka-exhibitions.zip`: exhibition content model and editor foundation.

The theme must work without either plugin. Each plugin must be installable and
activatable independently. The exhibition plugin must not depend on the Second
Blog plugin.

Experimental work is isolated on the `feature/exhibitions` branch until the
content model, editor, fallback rendering, and theme integration are ready for
review. The stable `main` branch remains release-oriented.

Version `0.2.0` of the exhibition plugin registers the portable
`kilka_exhibition` post type, exhibition-level metadata, and the constrained
Sequence and Space block editor. Description, creator, rights, and panel-state
controls are now wired into the editor. Search-summary and wall-tone controls,
the complete neutral fallback, and final theme integration remain separate
implementation stages.

## Shared Site Frame

The site frame is common to posts, pages, blog archives, Second Blog, and
exhibitions. It contains:

- the site name linked to the main page;
- the primary navigation control;
- shared focus and accessibility behaviour;
- the site footer.

On desktop, the site name may move from a large centred header to a quiet
position at the edge of the page. The navigation can occupy the opposite edge.
This direction must be prototyped on normal blog pages before it becomes the
default theme layout.

On mobile, the site name and menu return to one compact horizontal row.

An exhibition may suppress the normal blog masthead and content container, but
it must not create a separate brand identity.

## Footer

The same semantic footer and the same configured links should be available on
every public view.

Within an exhibition, the footer appears only after the last space and its
final interval. It acts as the exit from the exhibition and must not be fixed
over images or inserted between works.

The markup and content remain shared with the rest of the site. Contextual
exhibition styles may make its background blend with the exhibition wall, but
the exhibition must not maintain a second set of footer settings.

## Content Model

### Exhibition

Recommended WordPress post type: `kilka_exhibition`.

Core fields:

- title;
- slug and permalink;
- publication status;
- excerpt used as the curatorial or introductory note;
- optional featured image for links and previews;
- ordered sequence of spaces.

Exhibition-level metadata:

- optional public information-panel heading;
- default creator name;
- default copyright notice;
- information-panel visibility;
- wall-tone preset;
- optional search/social summary if the excerpt is not sufficient.

The first version should support multiple exhibitions even if the live site
initially publishes only one.

### Sequence

The sequence should be edited with a dedicated, constrained WordPress block
rather than a core Gallery block. The sequence contains only supported Space
blocks and provides a simple add/reorder workflow.

Proposed first-version space types:

1. Image Space
2. Text Space
3. Pause Space

A pair, diptych, audio, or video space can be considered later. They should not
be included until a real exhibition requires them.

### Image Space

Required data:

- media-library attachment;
- accessible description, normally inherited from attachment alt text;
- order within the exhibition.

Curatorial controls:

- image presence: `35–100` in increments of `5`; the presentation constrains
  both spatial width and viewport height rather than treating every image as a
  percentage-width post image;
- alignment: `left`, `centre`, or `right`;
- interval after the image: `short`, `normal`, or `long`;
- caption mode: `information panel`, `visible`, or `hidden`;
- optional creator and copyright overrides.

Caption-mode behaviour is deliberately explicit:

- `information panel` keeps the saved `figcaption` accessible but visually
  quiet and includes the caption in the ordered exhibition panel;
- `visible` shows the caption directly below the image and does not duplicate
  it in the panel;
- `hidden` keeps an accessible `figcaption` but does not place the caption in
  the visual panel or below the image.

The plugin exposes the ordered panel data, including exhibition-level rights
fallbacks, without generating theme-specific drawer markup. A supporting theme
may present that data as a dialog or another accessible interface.

The editor must not expose pixel sizes, arbitrary CSS classes, per-image wall
colours, shadows, frames, animations, or desktop/mobile duplicates in the first
version. Those choices belong to the shared visual system.

### Text Space

A Text Space supports a short curatorial passage, quotation, transition, or
section marker without turning the exhibition into a normal article.

Initial controls:

- text;
- width: `compact`, `narrow`, `standard`, `wide`, or `full width`;
- position within the exhibition width: `left`, `centre`, or `right`;
- text alignment: `left` or `centre`;
- text scale: `small`, `normal`, `large`, or `statement`;
- minimum space height: `by content`, `half viewport`, or `full viewport`;
- vertical position within a taller space: `top`, `centre`, or `bottom`;
- optional text marker: `none` or one short horizontal line;
- interval after the text.

Height presets use `min-height`, never a fixed height, so content remains
readable when it wraps, text size changes, or a narrow screen requires more
lines. Vertical position is disabled when height follows the content because
there is no free vertical space to distribute. The short line is the only
initial emphasis treatment; borders, backgrounds, and decorative variants are
intentionally deferred.

Long essays should remain normal pages or posts and may be linked from the
information panel.

### Pause Space

A Pause Space contains no visible content. It creates an intentional interval
that can be perceived independently of the work before it.

Initial lengths:

- `short`;
- `normal`;
- `long`;
- `full viewport`.

## Editing Experience

The editor should feel like arranging an exhibition plan, not configuring a
gallery plugin.

Recommended workflow:

1. Create an Exhibition.
2. Enter its title, slug, introductory note, and default rights information.
3. Select **Add space**.
4. Choose Image, Text, or Pause.
5. Select an image through the standard WordPress media library when needed.
6. Choose from the limited curatorial presets.
7. Reorder spaces with drag-and-drop and WordPress List View controls.
8. Preview the full sequence on desktop and mobile.
9. Publish the exhibition and add it to navigation only when approved.

The editor should automatically insert one Sequence container and restrict its
children to supported Space blocks. It should not require authors to remember
custom classes or edit serialized metadata manually.

## Information and Search Context

Visual silence must not remove semantic information. Published output should
include:

- one page-level heading;
- meaningful image alt text;
- captions or descriptions available in the information panel;
- semantic figures and captions where appropriate;
- structured `CollectionPage` and `ImageObject` data;
- explicit creator and copyright data only when supplied by the author;
- a useful excerpt and normal WordPress document metadata.

Text hidden only visually for accessibility remains available to assistive
technology. Search text must not be fabricated or repeated merely to attract
traffic.

## Responsive Rules

Authors choose curatorial intent, not a separate mobile composition.

The theme translates the same sequence automatically:

- desktop preserves left/right placement and greater variation in scale;
- mobile centres most works and reduces extreme scale differences;
- intervals remain meaningful but become shorter on small screens;
- the footer follows the final interval on every viewport;
- the information panel remains keyboard- and touch-accessible.

Mobile-specific overrides should be added only after real examples prove that
automatic translation is insufficient.

## Deliberately Deferred Visual Decisions

The following details remain experimental until the editing model is proven:

- shadows versus frames;
- exact wall colours;
- precise spacing values;
- transitions and motion;
- the final position of the site name;
- the relationship between the common footer and the exhibition wall;
- visible caption typography.

## Proposed Implementation Order

1. Completed: approve the initial content model and editor controls.
2. Completed: scaffold the separate first-party exhibition plugin, post type,
   metadata, and independent ZIP package.
3. Completed: build the constrained Sequence and Space editor blocks.
4. In progress: optional information heading, description, creator, rights, and
   panel-state metadata are editable; search-summary and wall-tone controls plus
   the complete neutral, accessible fallback output remain.
5. In progress: connect the current theme prototype to the new data model. The
   caption data reaches the theme-owned information panel and the dedicated
   `single-kilka_exhibition.php` renders the plugin-owned Sequence without
   ordinary post metadata, navigation, or comments.
6. Completed structurally: restore the shared footer at the end of the
   exhibition. Its final surface treatment remains part of visual work.
7. Prototype the edge-positioned site identity on blog, single-post, and
   exhibition views.
8. Finalise visual treatments only after the complete workflow is usable.

## Initial Working Decisions

1. Visible captions are exceptional; the information panel is the default.
2. The first editor version includes Image, Text, and Pause Spaces.
3. The post type has no public archive initially. Exhibitions are reached
   through deliberate links and navigation.
4. The information panel may contain both exhibition-level context and an
   ordered list of works.
5. The footer preserves shared markup and content everywhere, while its
   surface colour may adapt to the exhibition wall.

The block editor exposes a dedicated `Exhibition information` document panel
for an optional public heading, the brief description, creator, rights notice,
and information-panel toggle. The public heading is independent from the
administrative Exhibition title and is omitted visually when empty; the dialog
keeps a screen-reader name in that state. These values belong to the exhibition
as a whole. Image-level creator or rights overrides are shown only for the
affected work, avoiding repeated global credit lines throughout the works list.

The current theme presents this information in three ordered sections: optional
centred heading and description, one compact creator/rights group, and the
ordered works list. Bold labels followed by colons distinguish Creator, Rights,
and Works from their values. Short centred rules separate the sections. The
complete information surface is a floating white panel derived from the global
menu treatment: an inset position, thin border, restrained corner radius, and
soft shadow keep it distinct from both the exhibition wall and the modal
backdrop.

For the first theme-level composition pass, the exhibition wall is white while
the shared footer keeps its normal treatment. The shared site title starts in
the upper-left corner but remains part of the document header and scrolls away
before it can cover later works. The global menu remains the rightmost fixed
control, with the exhibition-specific information button directly below it;
both remain available while scrolling and keep separate gaze/touch targets.
The two controls use equal 42px circles with transparent surfaces so they cover
less of a work on narrow screens. A larger conventional information glyph makes
the second control recognisable without introducing a text label over the wall.

The first image-edge experiment uses no frame and no interaction. A compact
near-edge shadow establishes the physical edge of the photograph, while a much
broader shadow is displaced downwards to suggest distance between the image and
the white wall. Mobile uses the same two-layer model with shorter offsets and
less spread, preserving the hovering impression without turning each work into
a conventional card.
