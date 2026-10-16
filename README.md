# Kilka

A WordPress theme for a blog, with three optional plugins.

## Components

| Component | Version | Purpose |
|---|---|---|
| Kilka | 1.3.0 | Blog layouts, navigation, light and dark color schemes |
| Kilka Second Blog | 1.0.0 | A separate blog with its own entries, taxonomies and typography |
| Kilka Exhibitions | 0.2.0 | Sequences of images, text and pauses, with an information panel in Kilka |
| Kilka Reader | 0.1.0 | Reading pages with contents, pagination, text settings and DOCX/TXT import |

The theme works without the plugins. Each plugin is installed separately and
can be used without the other plugins. Reader requires WordPress 6.6 or later
and PHP 7.4 or later; see each component's readme for its requirements.

## Installation

Download the packages from [GitHub Releases](https://github.com/elvira-platas/kilka/releases).
Release `v1.3.0` includes the versions listed above.

1. Install any plugins you need through **Plugins → Add New Plugin → Upload Plugin**.
2. Install `kilka.zip` through **Appearance → Themes → Add New Theme → Upload Theme**.
3. Activate the selected plugins and theme.

Use the individual ZIP files. GitHub's **Source code** archives contain the
whole development repository and are not WordPress installation packages.
`SHA256SUMS` lists checksums for the four release ZIPs.

## Settings

- **Theme:** Appearance → Customize. Assign site navigation links to Primary Menu.
- **Second Blog:** create entries under Second Blog. Set its URL slug in
  Settings → Reading, its introduction in the Customizer, and fonts in the
  entry editor. An optional source-note marker follows the introduction's
  description; the note appears before the footer.
- **Exhibitions:** create an Exhibition and arrange Image, Text and Pause
  Spaces inside its Sequence. Use Exhibition information for the description,
  credits and information-panel settings.
- **Reader:** open Reader to create or import a document, edit its cover and
  settings, and link it to a related publication.

## Development

Theme files are at the repository root; plugins are under `plugins/`.
Development takes place on `main`.

Build packages from a checkout with Bash, Git, rsync, ripgrep, zip and unzip:

```bash
./scripts/build-packages.sh
```

The command writes `kilka.zip`, `kilka-second-blog.zip`, `kilka-exhibitions.zip`
and `kilka-reader.zip` to `dist/`. It uses working-tree contents of tracked
files, so a local build can include uncommitted edits. It does not create
`SHA256SUMS` or publish a release.

GitHub Actions checks PHP and shell syntax, package contents and diff whitespace.

## Documentation

- [Reading pages and Reader](docs/reading-template.md)
- [Exhibition content and presentation](docs/exhibition-architecture.md)
- [Blog typography](BLOG-STYLES-REFERENCE.md) (Russian)
- [Color schemes](docs/dark-theme-color-plan.md) (Russian)
- [Release packaging](docs/github-release-plan.md)
- [WordPress.org publication checklist](docs/wp-org-next-steps.md)

## Credits and license

Kilka is a fork of the original theme by [Asha Themes](https://ashathemes.com/).
Elvira is the project owner and maintainer, not the programmer.
Original copyright notices are preserved.
The theme and plugins use GPL-2.0-or-later; bundled libraries and fonts have
[separate notices](THIRD-PARTY-NOTICES.txt).

Code for this fork and its companion plugins was generated and modified by
OpenAI Codex and Google Gemini. These tools were also used for code review,
documentation and testing. Elvira sets requirements, evaluates results and
decides what to publish. She does not write the code.
