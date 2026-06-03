---
name: bundle-plugin
description: Use when creating a distributable Moodle plugin zip for admin installation. Triggers on requests to "bundle", "package", "zip", or "compress" a Moodle plugin.
---

# bundle-plugin

Packages a Moodle plugin into an admin-installable zip at `./plugin_bundle/<component>.zip`.

## Environment-based Naming

The plugin folder name inside the zip (and the zip filename) depends on the target environment:

| Environment | Component name | Shortname (zip dir) | Zip filename |
|-------------|---------------|---------------------|--------------|
| `prod` | `local_mathgpt` | `mathgpt` | `local_mathgpt.zip` |
| `dev` | `local_mathgpt_dev` | `mathgpt_dev` | `local_mathgpt_dev.zip` |
| `staging` | `local_mathgpt_staging` | `mathgpt_staging` | `local_mathgpt_staging.zip` |
| *(any env)* | `local_mathgpt_{env}` | `mathgpt_{env}` | `local_mathgpt_{env}.zip` |

**If no environment is specified, ask the user before proceeding.**

## Moodle Zip Requirements

- The zip **must** contain a single top-level directory matching the shortname
- Shortname = component name with type prefix stripped: `local_mathgpt` → `mathgpt`, `local_mathgpt_dev` → `mathgpt_dev`
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

**1. Determine the target environment**

If the user specified an environment, use it. Otherwise ask. Then set:
- `ENV` = the environment string (e.g. `prod`, `dev`, `staging`)
- `COMPONENT` = `local_mathgpt` if `ENV=prod`, else `local_mathgpt_${ENV}`
- `SHORTNAME` = strip `local_` prefix from `COMPONENT` (e.g. `mathgpt`, `mathgpt_dev`)

**2. Read `version.php` to confirm the base component name**

```bash
grep "\$plugin->component" version.php
# e.g. $plugin->component = 'local_mathgpt';
```

**3. Create output dir and a temp staging dir**

```bash
mkdir -p plugin_bundle
TMPDIR=$(mktemp -d)
mkdir "$TMPDIR/$SHORTNAME"    # e.g. mathgpt or mathgpt_dev
```

**4. Copy source files into the staging dir**

```bash
rsync -a \
  --exclude=".git/" \
  --exclude=".claude/" \
  --exclude=".gstack/" \
  --exclude="tests/" \
  --exclude="docs/" \
  --exclude="plugin_bundle/" \
  --filter="- *.md" \
  --filter="- *.txt" \
  --filter="+ *.php" \
  --filter="+ *.xml" \
  --filter="+ */" \
  --filter="- *" \
  . "$TMPDIR/$SHORTNAME/"
```

**5. For non-prod: rewrite `local_mathgpt` → `local_mathgpt_{env}` inside the staging dir**

Skip this step entirely when `ENV=prod`.

```bash
# Replace component name (underscore form): local_mathgpt → local_mathgpt_{env}
find "$TMPDIR/$SHORTNAME" -name "*.php" \
  -exec sed -i '' "s/local_mathgpt/${COMPONENT}/g" {} \;

# Replace capability key prefix (slash form): local/mathgpt: → local/mathgpt_{env}:
# Anchored at the colon so it won't double-match on a re-run.
BASE_SHORTNAME="mathgpt"   # strip type prefix from the prod component
find "$TMPDIR/$SHORTNAME" -name "*.php" \
  -exec sed -i '' "s|local/${BASE_SHORTNAME}:|local/${SHORTNAME}:|g" {} \;

# Replace capability lang string keys: 'mathgpt:{cap}' → 'mathgpt_{env}:{cap}'
# Moodle derives the display string key from the capability name by stripping the
# type prefix (local/), so local/mathgpt_lab:useapi looks up 'mathgpt_lab:useapi'.
# Anchored by the opening single quote to avoid touching unrelated strings.
find "$TMPDIR/$SHORTNAME" -name "*.php" \
  -exec sed -i '' "s/'${BASE_SHORTNAME}:/'${SHORTNAME}:/g" {} \;

# Rename the lang file to match the new component name
mv "$TMPDIR/$SHORTNAME/lang/en/local_mathgpt.php" \
   "$TMPDIR/$SHORTNAME/lang/en/${COMPONENT}.php"
```

**6. Remove any existing zip, then zip from inside the temp dir, then clean up**

```bash
rm -f "$OLDPWD/plugin_bundle/${COMPONENT}.zip"
cd "$TMPDIR" && zip -r "$OLDPWD/plugin_bundle/${COMPONENT}.zip" "$SHORTNAME"
rm -rf "$TMPDIR"
```

**7. Verify the zip structure**

```bash
unzip -l "plugin_bundle/${COMPONENT}.zip" | head -20
```

The first column of paths must all start with `$SHORTNAME/`. If the top-level directory is wrong, Moodle will reject the upload.

## Common Mistakes

| Mistake | Fix |
|---------|-----|
| Zip root is `.` not `$SHORTNAME/` | Always zip from the temp dir, not from the plugin dir |
| `version.php` missing from zip | Check rsync filter — `*.php` must be included before the catch-all `- *` |
| `lang/` directory empty | `lang/` only contains `.php` files, which are covered by `*.php` filter |
| Wrong shortname | Strip only the `local_` prefix: `local_mathgpt_dev` → `mathgpt_dev` |
| Bundling as prod for non-prod env | The zip dir name must match the installed plugin folder name in Moodle |
| Capability string key not renamed | `db/access.php` capability `local/mathgpt_lab:useapi` needs lang key `'mathgpt_lab:useapi'` — add the `sed "s/'mathgpt:/'mathgpt_lab:/g"` step after the slash-form rewrite |
