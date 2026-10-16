# Reading pages and Kilka Reader

Reading documents are ordinary WordPress Pages using the **Reading** template.
Their text stays in core blocks. The optional Kilka Reader 0.1.0 plugin adds
reading controls, an introduction screen and document import.

## Theme alone and plugin mode

| | Kilka theme alone | With Kilka Reader |
|---|---|---|
| Site header and footer | Shared site frame | Omitted from the reading view |
| Layout | Continuous document | Pages by default when supported; scrolling available |
| Colors | Site color scheme | Independent reader palettes |
| Exit | Site navigation | Related publication or home page |
| Reading controls | None | Text size, alignment, contents and supported fullscreen |

Reader also provides a standalone fallback template for other themes. It uses
the existing `page-templates/reading.php` template key. Activation does not
rewrite content or URLs. Deactivation preserves content and metadata; Kilka
returns to its continuous Reading template.

## Create or edit a document

1. Install and activate `kilka-reader.zip` if its controls are needed.
2. Open **Reader → New document**. Choose **Paste text** for a new draft or
   **Upload file — DOCX or TXT** to import a document.
3. Use **Edit story text** to edit the page in WordPress. Existing pages can
   also use the Reading template without importing.
4. Set the related publication and optional Text language in Reader settings.
5. Configure the optional introduction, preview and publish.

The **Reading document** pattern provides byline, text and note groups. It is
optional. Remove placeholders before publication and use H2/H3 for chapters;
non-empty headings at these levels form the reader's Contents. The author is
explicit content, not inferred from the WordPress account.

Related-publication links accept public, non-password-protected blog entries.
Invalid, private, draft or deleted targets are not exposed; Close reader falls
back to the home page. It does not close the browser tab or use browser history.

## Controls

- Text size: 80–160% in steps of 10%, with reset.
- Alignment: left by default, with optional justification and browser-native
  hyphenation. Text language supplies the document's language tag.
- Palettes: Light (default), Cream, Neutral and Graphite. They are independent
  of the site's saved color preference.
- Contents and Reading settings use separate keyboard-accessible tabs when
  chapters exist. Documents without chapters open settings directly.
- Close reader and Reading settings remain available during ordinary reading.
  Escape closes settings and returns focus to its button.

Reader settings and reading position last only while the document is open.
Reloading starts at the first page with Light selected. Without JavaScript,
the complete document and exit remain available in scrolling mode.

Public controls include English, Russian and German. They use the document's
Text language when supported, then the supported site language, then English.
Admin labels follow the WordPress admin locale. Translation files are local.

## Pagination and fullscreen

Pages use CSS columns on the original document. Scrolling is the fallback
without JavaScript, pagination support or enough viewport height. Page count
changes with text size and viewport dimensions; reflow preserves a text anchor.
Printing restores continuous flow. Existing manual WordPress page breaks retain
their page links.

Turn pages with the icons, arrow keys, Page Up/Down or horizontal swipes.
Dragging from a side gutter, arrow or text edge can hold, reverse or complete
a turn. The animation is a flat slide; there is no 3D or animation selector.
Temporary visual copies are inert, hidden from assistive technology and removed
after the gesture. Reduced motion uses immediate page changes. Text selection
remains available in the middle of the document.

Fullscreen is offered only where the browser supports and permits it, and only
starts after a user action. A rejected request leaves ordinary reading usable.
In fullscreen, the control strip hides; tapping text or pressing Tab reveals
it. Links, selection and scrolling do not toggle it. The first entry shows a
brief translated hint. The page count remains visible in paginated mode.
Leaving fullscreen restores the strip. Device behavior still depends on the
browser's Fullscreen API support.

## Introduction screen

**Introduction — Space** adds an optional cover before the document, disabled
by default. It supports a local image, title, author, short line, colors, font
settings and responsive composition. A separate phone image and phone-specific
title breaks are optional. Playfair Display, Cormorant Garamond, Montserrat and
Ruslan Display are bundled locally with their licenses.

The introduction is outside the story page count and independent of text size,
alignment and palette settings. It remains above the story in scrolling mode
and without JavaScript. Its editor groups controls into Text, Fonts, Image and
Composition with responsive previews.

## Import

Import creates a new draft, never replaces an existing document. Uploads are
processed on the server in temporary storage and are not added to the public
Media Library. The limit is 2 MB, or the server upload limit if lower.

DOCX supports paragraphs, bold, italic, explicit line breaks and Heading 1/2
styles, including inherited styles. Headings become H2/H3. Fonts, sizes, colors
and page layout are not imported. Page/section breaks and headers/footers
produce omission notices.

Images, tables, lists, links, footnotes/endnotes, equations, tracked changes,
comments and other unsupported semantic formatting stop DOCX import before a
draft is created. Protected files, macro-enabled documents and invalid packages
are rejected. Package limits are 512 entries, 20 MB expanded total and 2 MB per
part.

TXT accepts UTF-8 with or without a BOM. Empty lines separate paragraphs; a
single line break stays within a paragraph. Line endings are normalized.
Markdown, HTML and entity-like text remain literal text; headings and emphasis
must be added in the editor. Invalid UTF-8, unsupported control characters and
empty files are rejected.

## Storage and limits

Reader adds no analytics, cookies, browser storage or background requests.
Fonts and scripts are local. Other plugins, embedded content and server logs
are outside its control; use local media when avoiding third-party requests.

Content remains in `post_content`. The related publication and document language
use `_kilka_reader_publication` and `_kilka_reader_language` metadata; editor
writes require WordPress capabilities and nonce checks. Controls and temporary
page-turn copies are not saved into document content.

Pagination targets horizontal, left-to-right prose. Complex layouts and long
documents need their own device checks. EPUB generation is not included.
