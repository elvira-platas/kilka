# Kilka

This repository stores:

- the `kilka` theme
- the companion plugin `kilka-second-blog`
- the companion plugin `kilka-exhibitions`
- the companion plugin `kilka-reader`

Development takes place on `main`. The exhibition design is documented in
[`docs/exhibition-architecture.md`](docs/exhibition-architecture.md).

The exhibition content model lives in a separate first-party
`kilka-exhibitions` companion plugin. Version `0.2.0` adds a constrained block
editor: every exhibition contains one Sequence whose ordered children can only
be Image, Text, or Pause Spaces. The plugin is stored in this monorepo but
installed independently from the theme, following the existing Second Blog
packaging model. Image captions can be assigned to a visible caption, the
exhibition information panel, or accessible-only hidden output. The plugin
provides the ordered information-panel data; the Kilka theme owns the panel's
interactive presentation. Text Spaces provide constrained text scale, minimum
height, and vertical-placement presets without exposing arbitrary pixels or
arbitrary decorative styling; one optional short-line marker is available as a
restrained emphasis cue.

An optional public information heading, exhibition-level description, creator,
rights, and information-panel visibility are edited together in a dedicated
document-sidebar panel. The public heading is independent from the
administrative Exhibition title. The values remain portable plugin data while
the theme controls their public presentation.

The theme includes a dedicated single-Exhibition template. It renders the
plugin-owned Sequence inside the shared site header and footer without ordinary
post metadata, post navigation, comments, or blog sidebars.

Second Blog also supports an optional numbered source note. Its text, source
title, and source URL are edited in the Customizer; the marker stays with the
introductory disclosure while the note appears below the content area and
outside the shared footer.

## Attribution

The `kilka` theme is a maintained fork of the original Kilka theme by [Asha Themes](https://ashathemes.com/). Original theme copyright remains with Asha Themes. Elvira is the owner and maintainer of this fork.

## AI assistance

Development of this fork and its companion plugin was carried out with substantial assistance from OpenAI Codex and Google Gemini. These AI systems were used to generate and modify code, review changes, prepare documentation, and guide testing. Elvira directed the work, evaluated the results, and is responsible for published releases.

For deployment/publishing, build separate ZIP files.

The source repository and public download model are described in
[`docs/github-release-plan.md`](docs/github-release-plan.md). Development stays
in this monorepo, while WordPress installation uses separate theme and plugin
ZIP files from GitHub Releases.

## Releases

The theme version is **1.3.0**, tagged **`v1.3.0`**. Download installation ZIPs
from [GitHub Releases](https://github.com/elvira-platas/kilka/releases).
Companion plugins retain independent versions: Second Blog 1.0.0,
Exhibitions 0.2.0, and Reader 0.1.0.

Deployments pin the full commit SHA associated with the release tag.

## Build ZIP packages

```bash
./scripts/build-packages.sh
```

Output:

- `dist/kilka.zip`
- `dist/kilka-second-blog.zip`
- `dist/kilka-exhibitions.zip`
- `dist/kilka-reader.zip`

## Install order on WordPress

1. Upload and activate the optional companion plugins that the site needs:
   - `kilka-second-blog.zip`
   - `kilka-exhibitions.zip`
   - `kilka-reader.zip`
2. Upload and activate `kilka.zip` (theme).

This keeps the theme and portable content functionality separated. The theme
works without the companion plugins, and the plugins are independent of each
other.

Kilka Reader 0.1.0 adds optional text size controls and a related-publication
link to ordinary Reading pages. It preserves existing content and URLs, and
supplies a neutral reading template when the active theme has none. See
[Reading documentation](docs/reading-template.md).
