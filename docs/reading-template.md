# Reading template

The optional **Reading** template presents an ordinary WordPress Page as a
continuous document. It uses the shared site header, menu, color scheme and
footer, with a bounded text column and no blog metadata, sidebar or comments.
The template alone introduces no reader-specific JavaScript, cookies, storage
or background requests.

## Editing

1. Create a Page and choose the **Reading** template.
2. Use the page title for the document title; do not repeat it as another H1.
3. Insert the **Reading document** pattern from the Text patterns category.
4. Replace the byline with the author name, then put the document in the
   **Reading text** group. Use ordinary paragraphs, headings, images and
   separators in their reading order. Add images from the local Media Library
   and supply appropriate alt text and optional captions.
5. Edit or remove the final **Reading note** group. It can contain a source
   link or a rights statement. Remove all pattern placeholder text before
   publishing.

The pattern is a starting point, not a locked template. Existing page content
also works without the pattern. The author is explicit content, not inferred
from the WordPress account that edited the page. Theme styles identify the
byline, story and optional note; all three remain ordinary readable core-block
HTML when switching themes.

## Scope and boundaries

- One canonical `post_content`, without a duplicate text file in the theme.
- The template controls presentation; it introduces no post type, custom
  database table, content metadata or new installable package.
- No generated page numbers or fixed-height text boxes. Long text can grow,
  browser zoom can reflow it, and the document remains readable without JS.
- Existing manually inserted page breaks retain WordPress page links. There
  is no automatic pagination or download generator in this first pass.
- Shared site color preference behavior is unchanged: an explicit selection
  can be stored locally under `kilka-color-scheme`. Reading position and
  reader activity are neither stored nor transmitted.
- Emoji CDN fallback and the core embedded-content messaging script are
  disabled on Reading pages. Use locally hosted media and ordinary source
  links, not third-party embeds or tracking HTML. A theme cannot guarantee
  the behavior of separately installed plugins or server configuration.
- An initial privacy check must use an anonymous browser context and inspect
  actual requests, cookies and storage, including interactions. Administrative
  WordPress login is separate from the public reading experience.

Reader assets are loaded only for the page template. Editor styles are scoped
to the three document block styles so unrelated content retains its typography.

## Optional Reader plugin

Install `kilka-reader.zip` independently from the theme. Version 0.1.0 adds a
Reader panel to the page editor: select a related published blog post for the
return links at the beginning and end. Password-protected, private, draft and
deleted targets are never exposed through these links. The relation is stored
as the integer `_kilka_reader_publication` page metadata. The editor uses
WordPress capability and nonce checks; no public write endpoint is added.

The plugin recognizes the existing `page-templates/reading.php` template key;
activation does not rewrite the page, change its URL or create content. A
supporting theme supplies the site frame and typography. With another theme,
the plugin supplies a neutral standalone reading page with a home link. When
disabled, the document remains ordinary page content; Kilka also retains its
original continuous-reading template and pattern.

Text-size controls progressively enhance rendered content with a local script.
They offer 80–160% in 10% steps and a reset. The setting lasts only while the
page is open. No cookies, storage, identifiers or network requests are used.
Without JavaScript, the controls stay hidden and the complete document and
return links remain available. Browser zoom remains available independently.
Resizing the viewport recalculates text from the theme's original font sizes.
Controls are not inserted into stored content or exports. Optional pagination
is described below; EPUB export is not included.

The plugin's fourth ZIP contains only its own runtime and documentation. The
three existing components retain independent versions and installation paths.

### Alignment and document language

The optional Align left / Justify buttons affect prose paragraphs and list
items, preserving bylines, headings, captions and source notes. Left alignment
is the initial state, including without JavaScript. Justification leaves the
last line left-aligned and requests automatic browser hyphenation. Availability
of language dictionaries and exact breaks depend on the browser. No external
hyphenation library or dictionary request is added by the plugin.

Set Text language in the Reader editor panel when the document language differs
from the site's language. The optional `_kilka_reader_language` metadata holds
a validated language tag such as `ru` or `en-GB`, rendered as `lang` on the
document body. Empty values inherit the site language. This also helps assistive
technology pronounce the document. Alignment selection is kept only in the
open document and resets on reload; it does not alter stored content.

### Reader colors

The reading surface offers three independent palettes: Cream (`#F5ECD9`),
Light (`#FAFAF8`) and Graphite (`#18191D`). Cream is the initial palette, including
without JavaScript. Each palette defines matched text, heading, link, focus and
control colors. Color swatches are 48px buttons with accessible names and pressed
states. The site header/footer and the site's color preference are unaffected.
No automatic mode or persistence is added; reloading starts in Cream again.
The plugin supplies baseline colors independently of the active theme. Print
output uses a light surface. These are preview values subject to visual review.

### Interface status

Version 0.1.0 establishes the functional baseline. Palette shades, the initial
Cream selection, and the placement and visibility of reading controls remain
provisional. The visibility of the site header, menu and footer during reading
will be evaluated separately from the document controls and content model.

### Focused reading preview

With Kilka Reader active, the Kilka Reading template omits the site header,
footer, menu and back-to-top button. The browser interface remains unchanged;
this is not the Fullscreen API. Two 48px targets stay available: Close reader
and Reading settings. Close reader links to the related public publication or,
when none is available, the site's home page. It never closes the browser tab
or relies on browser history. The neutral plugin fallback follows the same rule.

Settings use a non-modal disclosure panel, initially hidden, containing size,
alignment and palettes. Toggle, Escape and outside pointer interaction close
it; Escape returns focus to the settings button. Keyboard focus may leave the
panel normally, closing it. Without JS the exit remains available and settings
stay hidden. Controls remain runtime HTML, never stored document content.
Disabling the plugin restores the theme's original shared site frame.

The focused preview uses a separate 64px top control strip (plus device safe
area), with Close reader on the left and settings on the right. Icons retain
48px targets without circular backgrounds or shadows. The document scrolls in
the region below the strip, so text cannot pass under the persistent controls.
The strip follows the reader palette. This layout is independent of browser
fullscreen, which is not implemented in this pass. Print restores normal flow.

### Optional browser fullscreen

The settings panel offers an icon button for fullscreen when the standard
Fullscreen API is available and permitted. Entry only follows a user action;
there is no automatic entry, keyboard lock, or orientation lock. The document
root enters fullscreen so the exit and settings controls remain available.
The same button leaves fullscreen. Its accessible name, icon and pressed state
follow `fullscreenchange`, including exits initiated by the browser. Escape is
not cancelled while fullscreen is active. A rejected request leaves reading
available and displays a short status message. Unsupported browsers keep the
existing focused reading layout without the button. No cookies, persistence
or network calls are added. Actual mobile and headset behavior requires device
review in addition to desktop browser tests.

### Interface translations

English source strings and bundled Russian and German PO/MO catalogs are supplied.
Public reader controls use the document's Text language when supported (`ru`, `en` and `de`, including regional tags), otherwise the supported site language,
otherwise English. The dock and settings panel declare their UI language for
assistive technology separately from the prose language. Selecting the UI
language does not change the WordPress locale, site settings, or content.
Only the `kilka-reader` text domain is affected on reading pages. In the admin
area the plugin uses standard WordPress translation loading and the admin
locale. Public translation catalogs are loaded locally and reused within the
request; English uses the source strings. No browser language detection,
network requests, storage, or new user preference is introduced.

### Immersive fullscreen preview

Entering fullscreen now closes settings and hides the reader strip, reclaiming
its height. The first entry in an open document shows a translated hint for
six seconds: tap the text to show reading controls. A single click/tap on prose
reveals the strip; another hides it. There is no automatic hiding timer for
controls. Links, interactive content, selected text, double clicks and scrolling
gestures do not toggle the strip. Tab reveals controls for keyboard navigation.
Leaving fullscreen restores the ordinary strip, including exits initiated by
the browser. The visible text fragment is anchored relative to the reading
viewport when the strip changes height. Hint state is in memory only; reloading
allows the hint again. The document, fullscreen support checks and browser exit
mechanisms remain unchanged. Page-turn animation is a separate future stage.


### Optional paginated reading preview

Reading opens on the first page when JavaScript and pagination support are
available. Two icon buttons in Reading settings choose scrolling or pages.
Without JavaScript, without pagination support, or with insufficient viewport
height, the document retains continuous scrolling. The 3D effect stays off
initially. Reloading starts on the first page; no preference is stored. The pages mode uses native CSS columns on
the original document, with a single column visible at a time. The existing
heading moves into the first column and returns to its original position when
scrolling is restored. Canonical content is never rewritten or copied back to
WordPress. The optional interactive turn creates only short-lived, hidden-from-
accessibility-tree presentation copies during a gesture.
This first pass targets horizontal, left-to-right prose; complex publication
layouts are separate work. An optional animation preview is described below.

Previous/next buttons and a translated page count occupy a separate bottom
strip. Arrow Left/Right and Page Up/Down turn pages outside settings and
interactive content; horizontal touch swipes also turn pages. Selection,
vertical gestures, links and multi-touch are excluded. Fullscreen hides the buttons and keeps a centered page count in a reserved
bottom area, clear of text and the initial hint. A text tap or Tab restores
controls using the same interaction
as scrolling mode. Browser zoom and native fullscreen exit remain available.

Page count depends on viewport, font size and alignment. The reading anchor is
a text node and character offset, retained across consecutive setting changes,
resizing and fullscreen transitions. Turning a page establishes a new anchor.
Column width uses whole pixels to prevent accumulated horizontal drift. Font
and media loading trigger recalculation, with viewport changes coalesced into
animation frames. Recalculation is suspended for print media; print and no-JS
reading retain continuous document flow. All mode, page and anchor state stays
in memory and disappears on reload. No libraries, network requests, cookies or
browser storage are introduced.


### Two-dimensional page transitions

Paginated reading uses a short, flat slide by default. There is no animation
switch or 3D renderer in the release interface. Buttons and keyboard commands
complete the slide automatically. A pointer starting in a side gutter, on a
side arrow, or near a text viewport edge can drag, hold and return the page.
Mouse capture extends 64px from each edge. Touch capture extends up to 96px,
capped at 28% of the viewport width, to make the gesture easier to start on
phones. Ordinary text selection remains available in the middle of the text.
A short arrow press still turns a page. Sufficient distance or a fresh flick
completes a drag; otherwise the page returns to its starting position.

The screen-sized sheet includes the blank margins and does not bend or scale
letters. Forward turns move the current page left over the next page; backward
turns bring the previous page in from the left. Two temporary visual copies are
clipped to screen-sized windows. They are inert, hidden from assistive technology
and removed after completion or cancellation. The original text remains the
source of pagination and accessibility. Long documents still require device
performance checks because preparing two DOM copies has a cost.

Pointer movement is coalesced into animation frames. Resize, mode/settings
changes, printing, backgrounding, lost capture and secondary pointers clean up
active gestures. Reduced motion uses immediate page changes, with buttons,
keyboard and ordinary touch swipes retained. No network requests, telemetry,
cookies or browser storage are added.
