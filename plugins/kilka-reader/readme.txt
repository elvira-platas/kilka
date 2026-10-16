=== Kilka Reader ===
Contributors: elvira-platas
Tags: reading, accessibility, pages
Requires at least: 6.6
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Portable reading pages with text size controls and a return to a related publication.

== Description ==

Kilka Reader adds reading controls to ordinary WordPress Pages using the Reading template. Content stays in core blocks, and existing page URLs are preserved. The plugin works with Kilka or its own fallback template on other themes. It does not require the other Kilka plugins.

Reading features:

* Pages by default when supported, with continuous scrolling available in settings.
* Contents generated from non-empty H2/H3 headings.
* Text size from 80 to 160 percent, left alignment or justification, and a document language setting.
* Four palettes: Light (default), Cream, Neutral and Graphite.
* Fullscreen on browsers that support and permit it, entered only on request.
* An optional introduction with a local image, title, author and responsive font and layout settings.
* DOCX and UTF-8 TXT import into new drafts; see supported content below.

The reading view omits the site header and footer. Close reader links to a related public publication or the home page. Private, draft and password-protected targets are not exposed. Contents and Reading settings use separate keyboard-accessible tabs when chapters exist.

Turn pages with icons, arrow or Page Up/Down keys, horizontal swipes or a drag from a side edge. Page turns use a flat slide; reduced motion uses immediate changes. Text size and viewport changes recalculate pages while retaining a text anchor. In fullscreen, tapping text or pressing Tab reveals the hidden controls. Leaving fullscreen restores the control strip. Without JavaScript, the complete document and exit remain available in scrolling mode. EPUB export is not included.

The optional Introduction — Space screen sits before the story and is excluded from its page count. It has separate settings for text, fonts, image and composition, including phone-specific image and title options. Playfair Display, Cormorant Garamond, Montserrat and Ruslan Display are bundled locally with their licenses. The introduction is disabled by default and remains above the story in scrolling mode.

No analytics, telemetry, cookies, browser storage or background requests are added. Reader preferences and position last only while the page is open; reloading starts at the first page with Light selected. The site's color preference is separate. Other plugins, embedded content and server logs are outside Reader's control. Deactivation preserves document content and metadata.

== Installation ==

1. Install and activate the plugin ZIP.
2. Create or edit a Page and choose the Reading template.
3. Insert the Reading document pattern or keep existing core-block content.
4. Open Reader in the admin menu to configure the introduction, related publication and text language. Save reader settings.
5. Use Edit story text for the WordPress content editor. Reader offers a New document section: Upload file — DOCX or TXT opens the import form; Paste text creates a draft with the Reading template already selected. Paste the whole story into that editor.

== Document import ==

Reader > New document > Upload file — DOCX or TXT creates a new draft with the Reading template. Upload a text-only DOCX (up to 2 MB, or the server limit if lower), optionally supply a title, then review the imported text in the editor. The source file is processed locally in temporary upload storage and is not added to the public media library.

Paragraphs, bold, italic, explicit line breaks and Heading 1/2 are supported. Heading styles become H2/H3, including inherited styles. The original title text is retained. Fonts, font sizes, colors and page layout are not imported. Page/section breaks and headers/footers produce omission notices. Non-empty H2/H3 headings form Contents in the reading settings. Readers can jump to chapters in scrolling, paginated and fullscreen modes. The current chapter is highlighted; on a paginated screen with chapter headings, the first heading on that screen is indicated. Documents without these headings have no Contents section.

Images, tables, lists, links, footnotes/endnotes, equations, tracked changes, comments and other unsupported semantic formatting stop the import before a draft is created. Protected files, macro-enabled documents and invalid packages are rejected. Package limits: 512 entries, 20 MB total expanded size, 2 MB per part. Replacement of existing documents is not included in this version.

TXT uses the same upload form and limit. UTF-8 with or without a BOM is supported; LF, CRLF and CR line endings are normalized. Empty lines (including lines containing only spaces or tabs) separate paragraphs; consecutive empty lines form one separator. A single line break stays within its paragraph. Bold, italic, Markdown and chapter headings are not inferred: add formatting in the editor after import. HTML and entity-like text remain literal text. Invalid UTF-8, unsupported control characters and empty files are rejected without creating a draft. The original file stays out of the public media library.

== Development ==

Code for this plugin was generated and modified by OpenAI Codex, which was also used for code review, documentation and testing. Elvira is the project owner and maintainer: she sets requirements, evaluates results and decides what to publish. She does not write the code.

== Changelog ==

= 0.1.0 =
* Preserve existing Reading pages and offer a neutral theme fallback.
* Add a protected editor field for the related publication.
* Add optional text size controls without storing reader activity.

== Translations ==

English, Russian and German reader controls are included. The public controls follow the document's Text language when a supported translation exists; otherwise they use the supported site language and then English. The rest of the site's language is unchanged. Admin labels follow the standard WordPress admin locale. Russian and German PO and compiled MO files are bundled locally.
