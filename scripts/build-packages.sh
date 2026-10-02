#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DIST_DIR="${ROOT_DIR}/dist"
BUILD_DIR="${ROOT_DIR}/.build"

THEME_SLUG="kilka"
PLUGIN_SLUGS=(
	"kilka-second-blog"
	"kilka-exhibitions"
	"kilka-reader"
)

THEME_STAGING_DIR="${BUILD_DIR}/${THEME_SLUG}"

if ! command -v rsync >/dev/null 2>&1; then
	echo "Error: rsync is required but not installed." >&2
	exit 1
fi

if ! command -v zip >/dev/null 2>&1; then
	echo "Error: zip is required but not installed." >&2
	exit 1
fi

if ! command -v rg >/dev/null 2>&1; then
	echo "Error: rg is required but not installed." >&2
	exit 1
fi

if ! command -v unzip >/dev/null 2>&1; then
	echo "Error: unzip is required but not installed." >&2
	exit 1
fi

if ! command -v git >/dev/null 2>&1; then
	echo "Error: git is required but not installed." >&2
	exit 1
fi

for plugin_slug in "${PLUGIN_SLUGS[@]}"; do
	if [ ! -d "${ROOT_DIR}/plugins/${plugin_slug}" ]; then
		echo "Error: plugin directory not found: plugins/${plugin_slug}" >&2
		exit 1
	fi
done

# Reject legacy promo code and repository-only directories before packaging.
check_package_directory() {
	local package_dir="$1"
	local forbidden_dir
	local forbidden_path
	local sensitive_path

	for forbidden_dir in welcome .agents .codex; do
		forbidden_path="$(find "${package_dir}" -type d -name "${forbidden_dir}" -print -quit)"
		if [ -n "${forbidden_path}" ]; then
			echo "Error: forbidden directory found in package: ${forbidden_path}" >&2
			return 1
		fi
	done

	sensitive_path="$(find "${package_dir}" -type f \( \
		-name '.env' -o -name '.env.*' -o -name '*.sql' -o -name '*.sql.gz' \
		-o -name '*.bak' -o -name '*.backup' -o -name '*.orig' \
		-o -name '*.swp' -o -name '*.swo' -o -name '*.pem' \
		-o -name '*.key' -o -name '*.log' \
	\) -print -quit)"
	if [ -n "${sensitive_path}" ]; then
		echo "Error: sensitive or backup file found in package: ${sensitive_path}" >&2
		return 1
	fi

	if rg -n -i --hidden \
		--glob '*.php' --glob '*.js' --glob '*.css' --glob '*.txt' \
		'(wpashathemes|index\.php/cart|buy pro|view demo)' "${package_dir}"; then
		echo "Error: legacy promotional content found in package: ${package_dir}" >&2
		return 1
	fi
}

# Verify the final archive as well as the staging directory.
check_package_archive() {
	local archive="$1"
	local archive_file

	if unzip -Z1 "${archive}" | rg -n -i '(^|/)(welcome|\.agents|\.codex)(/|$)'; then
		echo "Error: forbidden directory found in archive: ${archive}" >&2
		return 1
	fi

	if unzip -Z1 "${archive}" | rg -n -i '(^|/)(\.env(\..*)?|[^/]+\.(sql\.gz|sql|bak|backup|orig|swp|swo|pem|key|log))$'; then
		echo "Error: sensitive or backup file found in archive: ${archive}" >&2
		return 1
	fi

	while IFS= read -r archive_file; do
		if unzip -p "${archive}" "${archive_file}" | rg -n -i '(wpashathemes|index\.php/cart|buy pro|view demo)'; then
			echo "Error: legacy promotional content found in archive: ${archive}" >&2
			return 1
		fi
	done < <(unzip -Z1 "${archive}" | rg '\.(php|js|css|txt)$')
}

PACKAGE_EXCLUDES_DIR="$(mktemp -d)"
trap 'rm -rf "${PACKAGE_EXCLUDES_DIR}"' EXIT

# Working drafts must not become release assets merely because they are stored
# inside the repository directory. Tracked files still come from the working
# tree, so approved uncommitted changes remain available for preview builds.
git -C "${ROOT_DIR}" ls-files --others --exclude-standard > "${PACKAGE_EXCLUDES_DIR}/theme-untracked"

rm -rf "${BUILD_DIR}"
mkdir -p "${THEME_STAGING_DIR}" "${DIST_DIR}"

for plugin_slug in "${PLUGIN_SLUGS[@]}"; do
	mkdir -p "${BUILD_DIR}/${plugin_slug}"
	git -C "${ROOT_DIR}" ls-files --others --exclude-standard -- "plugins/${plugin_slug}/" |
		while IFS= read -r untracked_path; do
			echo "${untracked_path#plugins/${plugin_slug}/}"
		done > "${PACKAGE_EXCLUDES_DIR}/${plugin_slug}-untracked"
done

# Keep library source copies in Git; ship only assets used by the theme.
# Bootstrap JavaScript is not used. Bootstrap CSS and SlickNav remain required.
# Build theme package without repository-only and plugin files.
rsync -a \
	--exclude ".git/" \
	--exclude ".github/" \
	--exclude ".build/" \
	--exclude "dist/" \
	--exclude "docs/" \
	--exclude "plugins/" \
	--exclude "scripts/" \
	--exclude ".env" \
	--exclude ".env.*" \
	--exclude "*.sql" \
	--exclude "*.sql.gz" \
	--exclude "*.bak" \
	--exclude "*.backup" \
	--exclude "*.orig" \
	--exclude "*.swp" \
	--exclude "*.swo" \
	--exclude "*.pem" \
	--exclude "*.key" \
	--exclude "*.log" \
	--exclude ".agents/" \
	--exclude ".codex/" \
	--exclude "welcome/" \
	--exclude ".gitignore" \
	--exclude "AGENTS.md" \
	--exclude "README.md" \
	--exclude "/BLOG-STYLES-REFERENCE.md" \
	--exclude "/assets/css/bootstrap.css" \
	--exclude "/assets/js/bootstrap.js" \
	--exclude "/assets/js/bootstrap.min.js" \
	--exclude "/assets/css/slicknav.css" \
	--exclude "/assets/js/jquery.slicknav.js" \
	--exclude-from "${PACKAGE_EXCLUDES_DIR}/theme-untracked" \
	"${ROOT_DIR}/" "${THEME_STAGING_DIR}/"

# Build each companion plugin package from its own directory.
for plugin_slug in "${PLUGIN_SLUGS[@]}"; do
	rsync -a \
		--exclude-from "${PACKAGE_EXCLUDES_DIR}/${plugin_slug}-untracked" \
		"${ROOT_DIR}/plugins/${plugin_slug}/" "${BUILD_DIR}/${plugin_slug}/"
done

check_package_directory "${THEME_STAGING_DIR}"

for plugin_slug in "${PLUGIN_SLUGS[@]}"; do
	check_package_directory "${BUILD_DIR}/${plugin_slug}"
done

rm -f "${DIST_DIR}/${THEME_SLUG}.zip"

for plugin_slug in "${PLUGIN_SLUGS[@]}"; do
	rm -f "${DIST_DIR}/${plugin_slug}.zip"
done

(
	cd "${BUILD_DIR}"
	zip -qr "${DIST_DIR}/${THEME_SLUG}.zip" "${THEME_SLUG}"

	for plugin_slug in "${PLUGIN_SLUGS[@]}"; do
		zip -qr "${DIST_DIR}/${plugin_slug}.zip" "${plugin_slug}"
	done
)

check_package_archive "${DIST_DIR}/${THEME_SLUG}.zip"

for plugin_slug in "${PLUGIN_SLUGS[@]}"; do
	check_package_archive "${DIST_DIR}/${plugin_slug}.zip"
done

echo "Done."
echo "Theme package: ${DIST_DIR}/${THEME_SLUG}.zip"

for plugin_slug in "${PLUGIN_SLUGS[@]}"; do
	echo "Plugin package: ${DIST_DIR}/${plugin_slug}.zip"
done
