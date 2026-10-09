# GitHub Source and Release Packaging Plan

## Decision

Keep the theme and the first-party companion plugins in one Git repository,
but distribute and install them as four independent ZIP packages.

The repository is the shared development workspace. A GitHub Release is the
download point for WordPress-ready files.

## Repository layout

```text
kilka/
├── theme files at the repository root
├── plugins/
│   ├── kilka-second-blog/
│   ├── kilka-exhibitions/
│   └── kilka-reader/
├── docs/
└── scripts/build-packages.sh
```

This monorepo keeps cross-component work, documentation, and compatibility
checks together. The plugins remain separate from the theme at runtime and
must not be copied into the theme package.

## Release assets

Each GitHub Release should attach these WordPress-ready files:

- `kilka.zip` — the theme;
- `kilka-second-blog.zip` — the optional Second Blog plugin;
- `kilka-exhibitions.zip` — the optional Exhibitions plugin;
- `kilka-reader.zip` — the optional Reader plugin;
- `SHA256SUMS` — checksums for the attached ZIP files, when release automation
  is added.

Do not publish a combined installable ZIP containing the theme and plugins.
WordPress cannot route the contents of such an archive to both the themes and
plugins directories. GitHub's automatic `Source code` archives are development
snapshots and must not be presented as WordPress installation packages.

## Versions and release names

The theme and plugins keep independent versions in their own headers and
readme files. A collection release records which component versions were
tested together without assigning them a false shared semantic version.

Release naming scheme:

- tag: `v<theme-version>` (first public release: `v1.3.0`);
- title: `Kilka 1.3.0`;
- release notes: list the exact theme and plugin versions.

The tag identifies a repository snapshot whose theme has that version. Bundled
plugins retain their independent versions; a tag does not assign the theme
version to plugins. Deployments pin the full commit SHA of the tested tag.
Do not move published release tags; create a new version for later changes.

## Installation

Users download only the components they need:

1. Upload optional plugins through `Plugins -> Add New Plugin -> Upload Plugin`.
2. Upload the theme through `Appearance -> Themes -> Add New Theme -> Upload
   Theme`.
3. Activate the selected plugins and theme.

Supported combinations are the theme alone or with any subset of companion
plugins. The theme must continue to work when no plugin is active, and the
plugins must remain independent of each other.

## Release checklist

1. Start from a clean, reviewed branch with no experimental or uncommitted
   files.
2. Merge the approved work intended for the release.
3. Update only the versions of components that actually changed.
4. Align plugin `Stable tag` values and public readme information.
5. Run `./scripts/build-packages.sh`.
6. Inspect all four archives and confirm that each has one correctly named
   top-level directory.
7. Confirm that repository-only files, plugins inside the theme, credentials,
   backups, and experimental drafts are absent.
8. Install the ZIP files on a clean WordPress instance and test the supported
   component combinations.
9. Create the release tag from the exact tested commit.
10. Create the GitHub Release, list component versions and compatibility notes,
    and attach the four ZIP files and checksums.

## Possible later separation

Keep the monorepo while the components are developed and tested together. A
plugin may move to its own Git repository later if it gains an independent
release cycle, users outside this theme, or separate maintainers. WordPress.org
distribution, if pursued, remains separate for the theme and for each plugin
even when GitHub development stays in this monorepo.
