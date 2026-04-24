# MathGPT API — Moodle Local Plugin

A Moodle local plugin that exposes a REST API for the [MathGPT](https://mathgpt.com) application to programmatically manage Moodle course content: courses, sections, and LTI 1.3 activities.

## Requirements

- Moodle 4.3 or later
- [`local_oauth2`](https://moodle.org/plugins/local_oauth2) plugin installed and configured
- A configured LTI 1.3 external tool in Moodle (the tool ID is entered in the plugin settings)

## Installation

1. Download the plugin ZIP from the Moodle Plugin Directory or from the [GitHub releases](../../releases) page.
2. In Moodle, go to **Site Administration → Plugins → Install plugins** and upload the ZIP.
3. Follow the on-screen upgrade steps.

Alternatively, extract the ZIP to `{moodle_root}/local/mathgpt/` and run:

```bash
php admin/cli/upgrade.php --non-interactive
```

## Configuration

After installation, go to **Site Administration → Plugins → Local plugins → MathGPT API** and enter the **LTI Tool ID** — the numeric ID of the LTI 1.3 external tool that MathGPT activities should use. You can find this ID in **Site Administration → Plugins → Activity modules → External tool → Manage tools**.

## Authentication

Every API request must include a Bearer token in the `Authorization` header:

```
Authorization: Bearer <token>
```

Tokens are issued by the `local_oauth2` plugin. The token owner becomes the active Moodle user for the request, so that user must have the appropriate course/module editing capabilities.

## API Reference

Send a `POST` request to `/local/mathgpt/api.php` with a JSON body:

```json
{ "function": "<function_name>", "params": { ... } }
```

Successful response:
```json
{ "success": true, "data": ... }
```

Error response:
```json
{ "success": false, "error": "description" }
```

### Functions

| Function | Required params | Optional params | Returns |
|---|---|---|---|
| `get_courses` | — | — | `[{id, fullname, shortname, visible}]` |
| `get_course_contents` | `courseid` | — | `[{id, name, modules:[{id, modname, name, visible}]}]` |
| `create_lti_activity` | `courseid`, `sectionnum`, `name`, `module_item_id`, `educator_uid` | — | `{cmid, coursemodule_url}` |
| `update_lti_activity` | `cmid` | `name`, `module_item_id`, `visible` | `{cmid}` |
| `delete_lti_activity` | `cmid` | — | `{success: true}` |
| `create_section` | `courseid` | `name`, `position` | `{id, section, name}` |
| `update_section` | `sectionid` | `name`, `visible`, `summary` | `{id, section, name, visible}` |
| `delete_section` | `sectionid` | `force` | `{success: true}` |

**Notes:**
- `module_item_id` — MathGPT content UUID stored as an LTI custom parameter
- `educator_uid` — MathGPT user identifier stored as an LTI custom parameter
- `delete_section` with non-empty sections requires `force: true`

## Running Tests

```bash
cd {moodle_root}
php admin/tool/phpunit/cli/init.php
vendor/bin/phpunit --filter local_mathgpt
```

## License

GNU General Public License v3 or later — see [COPYING](https://www.gnu.org/licenses/gpl-3.0.html).
