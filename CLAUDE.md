# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is `local_mathgpt`, a Moodle local plugin that exposes a REST API for the MathGPT application to programmatically manage Moodle course content (courses, sections, LTI activities).

- **Component name**: `local_mathgpt`
- **Requires**: Moodle 4.3+ (2023100900)
- **Moodle installation**: `/Users/hoaha/apps/moodle/moodle/` (read-only reference — do not modify)
- **Plugin lives at**: `/Users/hoaha/apps/moodle-plugin/` (symlinked or copied to `moodle/local/mathgpt/`)

## Development Setup

To test changes, the plugin directory must be present at `{moodle_root}/local/mathgpt/`. After adding/changing files, trigger Moodle's upgrade process:

```bash
php /Users/hoaha/apps/moodle/moodle/admin/cli/upgrade.php --non-interactive
```

Run PHPUnit tests (once a `tests/` directory exists):
```bash
cd /Users/hoaha/apps/moodle/moodle
php admin/tool/phpunit/cli/init.php
vendor/bin/phpunit --filter local_mathgpt
```

## Architecture

### Request Flow

```
POST /local/mathgpt/api.php
  └── auth.php: extract + validate Bearer token (local_oauth2_access_token table)
  └── api_handler.php: dispatch($function, $params) → handler method
  └── lti_manager.php: LTI 1.3 CRUD helpers (used by api_handler)
```

### Authentication

- Bearer token in `Authorization` header
- Validated against `local_oauth2_access_token` table (Moodle's OAuth2 plugin)
- Token owner becomes the active Moodle session user via `$USER = get_complete_user_data('id', $userid)`
- Expired tokens return HTTP 401; missing tokens return HTTP 400

### API Dispatcher (`classes/api_handler.php`)

`dispatch($function, $params)` routes to private methods. Supported functions:

| Function | Description |
|---|---|
| `get_courses` | Lists all Moodle courses |
| `get_course_contents` | Returns sections + modules for a course |
| `create_lti_activity` | Creates an LTI 1.3 activity in a section |
| `update_lti_activity` | Updates name, visibility, custom params |
| `delete_lti_activity` | Removes an LTI activity |
| `create_section` | Adds a section to a course |
| `update_section` | Updates section name/summary |
| `delete_section` | Removes a section |

### LTI Manager (`classes/lti_manager.php`)

Wraps Moodle's `mod_lti` internals. The `ltitoolid` setting (configured in plugin admin) must point to a valid LTI 1.3 external tool. The `module_item_id` custom parameter links LTI activities back to MathGPT's internal item IDs.

### Key Moodle APIs Used

- `course_modinfo` / `get_fast_modinfo()` — course structure traversal
- `create_module()` / `update_module()` / `course_delete_modules()` — module CRUD
- `course_create_sections()` / `update_section_visibility()` — section management
- `$DB->get_record()` / `$DB->update_record()` — direct DB access for LTI table

## Plugin Configuration

One admin setting: **Site Admin → Plugins → Local Plugins → MathGPT API → LTI Tool ID** (`$CFG->local_mathgpt_ltitoolid`). This must be set before `create_lti_activity` will work.

## No External Dependencies

No `composer.json`, no `package.json`, no build step. All dependencies come from Moodle core.
