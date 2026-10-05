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

Use ordinary WordPress Pages with the Reading template. Content stays in core blocks. Existing Kilka Reading pages keep their content and URLs. A supporting theme controls presentation; other themes receive a neutral standalone reading page. This plugin does not require the Kilka theme or other Kilka plugins.

Open Reader in the admin menu, choose a reading document and set its related published blog post. A Close reader icon links to the related publication, or the site home page if no public target is available. Private, draft and password-protected targets are hidden. Optional JavaScript controls adjust text size from 80 to 160 percent and reset it. Align left is the default; readers may choose Justify with browser-native automatic hyphenation. Set the optional Text language field for correct pronunciation and language-specific hyphenation. Alignment also lasts only while the page is open. Four reading palettes are available: Light (initial), Cream, Neutral and Graphite. The reader palette does not change the site color preference and resets to Light on reload. The settings are grouped in a collapsible panel; Close reader and Reading settings remain visible during reading. The Kilka template and neutral fallback omit the site header and footer. Without JavaScript, the complete document and exit remain usable.

The Introduction — Space section in Reader settings adds a full-screen opening composition before the story. Choose a local background image, title, optional author and short line, text colors and system serif/sans-serif fonts. Titles and supporting text also offer locally bundled variable Playfair Display, Cormorant Garamond and Montserrat fonts, weight sliders and italic. The author and optional short line share font family, weight and italic settings, independent of the title. The author and short line have separate size controls. Ruslan Display is also available in its original Regular 400 style; weight and italic controls are disabled for it, without synthesized styles. The font files and their OFL licenses are included in the plugin; visitors do not contact an external font service. Desktop, tablet and phone crops have independent focal positions; a separate phone image and hiding the short line on phones are optional. The editor groups controls into Text, Fonts, Image and Composition, with Text open initially. Responsive previews remain outside the collapsible sections and sit alongside the controls on wide screens. Optional phone-specific title breaks select boundaries between the same title words; desktop and tablet keep the main title line breaks. With no phone breaks selected, phones wrap automatically. Changing title words clears the selected phone breaks in the editor. The introduction uses normal page turns but is not included in the story page count: the first text page starts at 1 of N. It stays independent of reading text size, alignment and palettes. It is disabled by default and remains visible above the story in scrolling mode or without JavaScript.

No analytics, telemetry, cookies, browser storage or background requests are added. Text size lasts only while the page is open. Separate plugins, embedded content and server logs are outside this plugin's control. Use locally hosted media for a reading experience without third-party requests.

Deactivation preserves content and metadata. An optional fullscreen button is available inside settings on browsers supporting the standard Fullscreen API. It only activates on request, follows browser exit events, and handles denial without interrupting reading. In fullscreen the reader strip hides and a one-time translated hint explains how to reveal it by tapping text. A second tap hides it; scrolling, selection and links do not toggle controls. Tab restores keyboard access. Leaving fullscreen restores the strip. Reading opens on the first page when pagination is supported. Continuous scrolling remains available in settings and is the fallback without JavaScript or sufficient viewport height. Use previous/next icons, arrow or Page Up/Down keys, or horizontal touch swipes. Pages reflow with text size and viewport changes while retaining a text anchor. Fullscreen hides the buttons but retains a centered page count below the text. Paginated reading uses a short 2D slide by default, without a separate animation setting. Pull from a side gutter, a side arrow or an available text edge to hold, reverse or complete a turn. Letters remain flat and unchanged in size. Two temporary visual copies are inert, hidden from assistive technology and removed after the gesture. Reduced motion uses immediate page changes. EPUB generation is not included.

== Installation ==

1. Install and activate the plugin ZIP.
2. Create or edit a Page and choose the Reading template.
3. Insert the Reading document pattern or keep existing core-block content.
4. Open Reader in the admin menu to configure the introduction, related publication and text language. Save reader settings.
5. Use Edit story text for the WordPress content editor. Reader offers a New document section: Upload file — DOCX or TXT opens the import form; Paste text creates a draft with the Reading template already selected. Paste the whole story into that editor.

== Document import (first version) ==

Reader > New document > Upload file — DOCX or TXT creates a new draft with the Reading template. Upload a text-only DOCX (up to 2 MB, or the server limit if lower), optionally supply a title, then review the imported text in the editor. The source file is processed locally in temporary upload storage and is not added to the public media library.

Paragraphs, bold, italic, explicit line breaks and Heading 1/2 are supported. Heading styles become H2/H3, including inherited styles. The original title text is retained. Fonts, font sizes, colors and page layout are not imported. Page/section breaks and headers/footers produce omission notices. Chapters are preserved as headings; automatic table-of-contents navigation is not included yet.

Images, tables, lists, links, footnotes/endnotes, equations, tracked changes, comments and other unsupported semantic formatting stop the import before a draft is created. Protected files, macro-enabled documents and invalid packages are rejected. Package limits: 512 entries, 20 MB total expanded size, 2 MB per part. Replacement of existing documents is not included in this version.

TXT uses the same upload form and limit. UTF-8 with or without a BOM is supported; LF, CRLF and CR line endings are normalized. Empty lines (including lines containing only spaces or tabs) separate paragraphs; consecutive empty lines form one separator. A single line break stays within its paragraph. Bold, italic, Markdown and chapter headings are not inferred: add formatting in the editor after import. HTML and entity-like text remain literal text. Invalid UTF-8, unsupported control characters and empty files are rejected without creating a draft. The original file stays out of the public media library.

== AI Assistance ==

OpenAI Codex substantially assisted code generation, review, documentation and testing. Elvira directed development and is responsible for published releases.

== Changelog ==

= 0.1.0 =
* Preserve existing Reading pages and offer a neutral theme fallback.
* Add a protected editor field for the related publication.
* Add optional text size controls without storing reader activity.

== Translations ==

English, Russian and German reader controls are included. The public controls follow the document's Text language when a supported translation exists; otherwise they use the supported site language and then English. The rest of the site's language is unchanged. Admin labels follow the standard WordPress admin locale. Russian and German PO and compiled MO files are bundled locally.
