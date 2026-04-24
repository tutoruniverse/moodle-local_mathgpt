---
name: bundle-plugin
description: Use when creating a distributable Moodle plugin zip for admin installation. Triggers on requests to "bundle", "package", "zip", or "compress" a Moodle plugin.
---

# bundle-plugin

Packages a Moodle plugin into an admin-installable zip at `./plugin_bundle/<component>.zip`.

## Moodle Zip Requirements

- The zip **must** contain a single top-level directory named after the plugin's shortname
- Shortname = component name with type prefix stripped: `local_mathgpt` → `mathgpt`, `block_foo` → `foo`
- Only PHP-related source files; no tests, docs, or markdown

## What to Include / Exclude

| Include | Exclude |
|---------|---------|
| `*.php` | `tests/` |
| `*.xml` (db schema) | `docs/` |
| `lang/` | `*.md` |
| `pix/` (icons) | `plugin_bundle/` |
| `templates/` | `.git/` |
| `db/` | `*.txt` |

## Steps

**1. Read `version.php` to get the component name**

```bash
grep "\$plugin->component" version.php
# e.g. $plugin->component = 'local_mathgpt';
```

**2. Derive the shortname** (strip everything up to and including the first `_`)

`local_mathgpt` → `mathgpt`

**3. Create output dir and a temp staging dir**

```bash
mkdir -p plugin_bundle
TMPDIR=$(mktemp -d)
mkdir "$TMPDIR/mathgpt"    # use actual shortname
```

**4. Copy source files into the staging dir**

```bash
rsync -a \
  --exclude=".git/" \
  --exclude="tests/" \
  --exclude="docs/" \
  --exclude="plugin_bundle/" \
  --filter="- *.md" \
  --filter="- *.txt" \
  --filter="+ *.php" \
  --filter="+ *.xml" \
  --filter="+ */" \
  --filter="- *" \
  . "$TMPDIR/mathgpt/"
```

**5. Zip from inside the temp dir, then clean up**

```bash
cd "$TMPDIR" && zip -r "$OLDPWD/plugin_bundle/local_mathgpt.zip" mathgpt
rm -rf "$TMPDIR"
```

**6. Verify the zip structure**

```bash
unzip -l plugin_bundle/local_mathgpt.zip | head -20
```

The first column of paths must all start with `mathgpt/`. If the top-level directory is wrong, Moodle will reject the upload.

## Common Mistakes

| Mistake | Fix |
|---------|-----|
| Zip root is `.` not `mathgpt/` | Always zip from the temp dir, not from the plugin dir |
| `version.php` missing from zip | Check rsync filter — `*.php` must be included before the catch-all `- *` |
| `lang/` directory empty | `lang/` only contains `.php` files, which are covered by `*.php` filter |
| Wrong shortname | Strip only the first segment: `mod_quiz` → `quiz`, not `mod_qui` |
