# Design: Moodle Plugin Marketplace Approval

**Date:** 2026-04-23  
**Plugin:** `local_mathgpt`  
**Goal:** Make the plugin publishable on plugins.moodle.org (Option A — minimal viable submission with tests)

---

## Scope

No structural changes to existing code. All changes are pure additions or small modifications to existing files. The plugin's REST API behaviour, authentication flow, and LTI management logic are unchanged.

---

## 1. Privacy API (`classes/privacy/provider.php`)

**New file.** Implements Moodle's `\core_privacy\local\metadata\null_provider` interface.

This plugin owns no database tables. All personal data that flows through it (including the `educator_uid` custom LTI parameter) is written into and managed by `mod_lti`'s tables — `mod_lti`'s own privacy provider is responsible for that data. This plugin only orchestrates Moodle-internal APIs.

The `null_provider` implementation requires one method:

```php
public static function get_reason(): string {
    return 'privacy:metadata';
}
```

A corresponding lang string `$string['privacy:metadata']` must be added to `lang/en/local_mathgpt.php` explaining that no personal data is stored by this plugin.

---

## 2. GPL v3+ License Headers

**Modified files:** `api.php`, `classes/auth.php`, `classes/api_handler.php`, `classes/lti_manager.php`, `lang/en/local_mathgpt.php`, `settings.php`, `version.php`, `classes/privacy/provider.php`

Every PHP file gets a standard Moodle-style file-level docblock immediately after `<?php`:

```php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * [one-line description]
 *
 * @package   local_mathgpt
 * @copyright 2026 MathGPT <backend@gotitapp.co>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
```

---

## 3. Dependency Declaration (`version.php`)

**Modified file:** `version.php`

Add one line:

```php
$plugin->dependencies = ['local_oauth2' => ANY_VERSION];
```

This ensures Moodle blocks installation if `local_oauth2` is absent. `ANY_VERSION` is used because no minimum version of `local_oauth2` needs to be pinned.

---

## 4. README.md

**New file** at repository root. Sections:

1. **What it does** — One-paragraph description: REST API bridge enabling MathGPT to programmatically manage Moodle course content (courses, sections, LTI 1.3 activities) via Bearer token authentication.
2. **Requirements** — Moodle 4.3+, `local_oauth2` plugin installed, a configured LTI 1.3 external tool in Moodle.
3. **Installation** — Standard zip install via Site Administration → Plugins → Install plugins, then run the upgrade CLI.
4. **Configuration** — Navigate to Site Administration → Plugins → Local Plugins → MathGPT API and enter the LTI Tool ID.
5. **Authentication** — Bearer tokens are issued by `local_oauth2`. Pass the token as `Authorization: Bearer <token>` on every request.
6. **API Reference** — Table of all 8 functions with required/optional parameters and return shapes:

| Function | Required params | Optional params | Returns |
|---|---|---|---|
| `get_courses` | — | — | `[{id, fullname, shortname, visible}]` |
| `get_course_contents` | `courseid` | — | `[{id, name, modules:[{id, modname, name, visible}]}]` |
| `create_lti_activity` | `courseid, sectionnum, name, module_item_id, educator_uid` | — | `{cmid, coursemodule_url}` |
| `update_lti_activity` | `cmid` | `name, module_item_id, visible` | `{cmid}` |
| `delete_lti_activity` | `cmid` | — | `{success: true}` |
| `create_section` | `courseid` | `name, position` | `{id, section, name}` |
| `update_section` | `sectionid` | `name, visible, summary` | `{id, section, name, visible}` |
| `delete_section` | `sectionid` | `force` | `{success: true}` |

7. **License** — GNU GPL v3 or later.

---

## 5. PHPUnit Tests (`tests/local_mathgpt_test.php`)

**New file.** Extends `advanced_testcase`.

### `auth` class tests

| Test | Method | Scenario |
|---|---|---|
| Valid Bearer header returns token string | `extract_bearer_token` | `Authorization: Bearer abc123` |
| Missing header returns null | `extract_bearer_token` | Empty string |
| Malformed scheme returns null | `extract_bearer_token` | `Basic abc123` |
| Valid token returns user ID | `validate_bearer_token` | Insert fixture row, not expired |
| Expired token throws `moodle_exception` | `validate_bearer_token` | Insert fixture row with past expiry |
| Non-existent token throws `moodle_exception` | `validate_bearer_token` | No row in table |

### `api_handler` dispatch tests

| Test | Scenario |
|---|---|
| Unknown function throws `invalid_parameter_exception` | `dispatch('nonexistent', [])` |
| `get_course_contents` missing `courseid` throws | `dispatch('get_course_contents', [])` |
| `create_lti_activity` missing required params throws | Each required param omitted in turn |
| `update_lti_activity` missing `cmid` throws | `dispatch('update_lti_activity', [])` |
| `delete_lti_activity` missing `cmid` throws | `dispatch('delete_lti_activity', [])` |
| `update_section` missing `sectionid` throws | `dispatch('update_section', [])` |
| `delete_section` missing `sectionid` throws | `dispatch('delete_section', [])` |
| `create_section` missing `courseid` throws | `dispatch('create_section', [])` |

### `lti_manager` tests

| Test | Scenario |
|---|---|
| `create()` throws `moodle_exception` when `ltitoolid` config is not set | `set_config` not called |

---

## Operational Requirements (not code changes)

These must be done manually before submitting to plugins.moodle.org:

- **Public GitHub repository** — root of repo = root of plugin (`version.php` at root)
- **Public issue tracker** — GitHub Issues enabled on the repo
- **plugins.moodle.org account** — register and submit, providing repo URL, issue tracker URL, screenshots, and short/long descriptions

---

## Files Changed Summary

| File | Change |
|---|---|
| `version.php` | Add license header + `$plugin->dependencies` |
| `api.php` | Add license header |
| `classes/auth.php` | Add license header |
| `classes/api_handler.php` | Add license header |
| `classes/lti_manager.php` | Add license header |
| `settings.php` | Add license header |
| `lang/en/local_mathgpt.php` | Add license header + `privacy:metadata` string |
| `classes/privacy/provider.php` | **New** — null_provider implementation |
| `tests/local_mathgpt_test.php` | **New** — PHPUnit test class |
| `README.md` | **New** — plugin documentation |
