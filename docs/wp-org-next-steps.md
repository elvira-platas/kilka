# WordPress.org Publishing and Companion Plugin Notes

This file is maintainer documentation for publishing the Kilka theme and its companion plugins. It is kept in the repository and excluded from the installable ZIP packages.

## Completed

- The theme and companion plugins are separated.
- The `Second Blog` logic is in `plugins/kilka-second-blog/kilka-second-blog.php`.
- The exhibition content model and constrained block editor are in `plugins/kilka-exhibitions/`.
- Reader pages, controls and import are in `plugins/kilka-reader/`.
- Each plugin uses its own text domain: `kilka-second-blog`,
  `kilka-exhibitions` or `kilka-reader`.
- Separate packages are built with `./scripts/build-packages.sh`.
- Original-author attribution is present in `style.css` and `readme.txt`.
- Public readmes identify Codex and Gemini as the tools that generated and
  modified code, and Elvira as the project owner who sets requirements and
  decides what to publish, not as the programmer.
- Package validation rejects legacy promotional content and repository-only directories.

## Remaining steps for the WordPress.org Theme Directory

1. Confirm that the theme name and slug meet directory requirements.
2. Review the metadata in `style.css`.
3. Verify that `Contributors` in `readme.txt` contains the correct WordPress.org username.
4. Review the license notices for all bundled third-party resources, including Bootstrap, SlickNav, and fonts.
5. Run Theme Check and resolve required findings.
6. Build the theme ZIP separately with `./scripts/build-packages.sh`.
7. Submit the theme to WordPress.org and complete the review process.

## Plugin publication steps

1. Review each plugin header:
   - `plugins/kilka-second-blog/kilka-second-blog.php`;
   - `plugins/kilka-exhibitions/kilka-exhibitions.php`;
   - `plugins/kilka-reader/kilka-reader.php`.
2. Maintain each plugin readme source:
   - `plugins/kilka-second-blog/readme.txt`;
   - `plugins/kilka-exhibitions/readme.txt`;
   - `plugins/kilka-reader/readme.txt`.
3. Verify that the plugin `Contributors` value matches the WordPress.org username.
4. Request a plugin slug at `https://wordpress.org/plugins/developers/add/`.
5. After approval, use the assigned WordPress.org SVN repository:
   - code in `trunk/`;
   - each release in `tags/x.y.z/`;
   - optional banners and icons in `assets/`.
6. Keep the `Stable tag` in the plugin `readme.txt` aligned with the release.
7. Commit the plugin to SVN and verify the directory page after publication.

## Readme locations

1. Git repository:
   - `plugins/kilka-second-blog/readme.txt`;
   - `plugins/kilka-exhibitions/readme.txt`;
   - `plugins/kilka-reader/readme.txt`.
2. WordPress.org SVN after approval:
   - `trunk/readme.txt`;
   - `tags/x.y.z/readme.txt` for each release.

## Theme and plugin relationship

1. The theme may recommend the optional companion plugins.
2. The theme must not bundle or auto-install any companion plugin in a way that violates WordPress.org rules.
3. Custom post types, taxonomies, and custom editor blocks must remain in their respective plugins.

## Package build

```bash
./scripts/build-packages.sh
```

The command creates:

- `dist/kilka.zip`;
- `dist/kilka-second-blog.zip`;
- `dist/kilka-exhibitions.zip`;
- `dist/kilka-reader.zip`.

Repository documentation under `docs/` is not included in any installable package.

## Recommended release sequence

1. Update the theme or plugin version when appropriate.
2. Build all packages.
3. Test on a clean WordPress installation:
   - activate the required companion plugins first;
   - activate the theme second.
4. Verify the key scenarios:
   - `second-blog` archive;
   - `second-blog` single entry;
   - tag, category, date, and author archives;
   - search with `post_type=world_note`;
   - `Customizer -> Second Blog Intro`;
   - exhibition post type registration and REST metadata;
   - Sequence template locking and Image, Text, and Pause Space editing;
   - block insertion, reordering, saving, reopening, and mobile preview;
   - exhibition output with and without the Kilka theme;
   - Reader creation, DOCX/TXT import and chapter navigation;
   - Reader scrolling, pagination, settings and supported fullscreen behavior;
   - Reader output with Kilka and the plugin fallback template.
5. Publish only after the clean-install checks pass.

## Useful links

- Theme requirements: `https://make.wordpress.org/themes/handbook/review/required/`
- `style.css` header: `https://developer.wordpress.org/themes/classic-themes/basics/main-stylesheet-style-css/`
- Plugin Handbook: `https://developer.wordpress.org/plugins/`
- Plugin submission: `https://wordpress.org/plugins/developers/add/`
- WordPress license: `https://wordpress.org/about/license/`
- Trademark policy: `https://wordpressfoundation.org/trademark-policy/`
