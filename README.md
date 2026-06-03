# local_mathgpt — Moodle LTI API Plugin

A Moodle local plugin that exposes a REST API for programmatically managing Moodle course content: courses, sections, and LTI 1.3 activities. Designed to be service-agnostic — any backend can use it by passing arbitrary LTI custom parameters.

## Requirements

- Moodle 4.3 or later
- [`local_oauth2`](https://moodle.org/plugins/local_oauth2) plugin installed and configured
- A configured LTI 1.3 external tool in Moodle (the tool ID is entered in the plugin settings)

## Installation

1. Download the plugin ZIP from the Moodle Plugin Directory or from the [GitHub releases](https://github.com/tutoruniverse/moodle-local_mathgpt/releases) page.
2. In Moodle, go to **Site Administration → Plugins → Install plugins** and upload the ZIP.
3. Follow the on-screen upgrade steps.

Alternatively, extract the ZIP to `{moodle_root}/local/mathgpt/` and run:

```bash
php admin/cli/upgrade.php --non-interactive
```

## Configuration

After installation, go to **Site Administration → Plugins → Local plugins → MathGPT API** and enter the **LTI Tool ID** — the numeric ID of the LTI 1.3 external tool that MathGPT activities should use. You can find this ID in **Site Administration → Plugins → Activity modules → External tool → Manage tools**.

## Authentication

Tokens are issued by the `local_oauth2` plugin. The token owner becomes the active Moodle user for the request and must have the `local/mathgpt:useapi` capability (granted to the Manager and Editing Teacher roles by default).

The token is passed in the JSON request body (not an `Authorization` header) because Apache strips that header before PHP sees it in many default configurations.

## API Reference

Send a `POST` request to `/local/mathgpt/api.php` with a JSON body. Include `token` in every request:

```json
{ "token": "<access_token>", "function": "<function_name>", "params": { ... } }
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

---

#### `get_courses`

Returns all courses except the site home.

_No params._

**Returns:** array of `{ id: int, fullname: string, shortname: string, visible: 0|1, startdate: int, enddate: int, timecreated: int, summary: string }`

---

#### `get_course`

Returns a single course by ID. Returns `null` if the course does not exist or the token owner is not enrolled in it.

| Param | Type | Required | Description |
|---|---|---|---|
| `courseid` | int | yes | Moodle course ID |

**Returns:** `{ id: int, fullname: string, shortname: string, visible: 0|1, startdate: int, enddate: int, timecreated: int, summary: string }` or `null`

---

#### `get_course_contents`

Returns all sections and their modules for a course.

| Param | Type | Required | Description |
|---|---|---|---|
| `courseid` | int | yes | Moodle course ID |

**Returns:** array of `{ id: int, name: string, component: string, modules: [{ id: int, modname: string, name: string, visible: 0|1, custom_params?: object }] }`

`custom_params` is only present on modules where `modname === "lti"` and custom parameters are set.

---

#### `create_lti_activity`

Creates an LTI 1.3 activity inside a course section.

| Param | Type | Required | Description |
|---|---|---|---|
| `courseid` | int | yes | Target course ID |
| `sectionnum` | int | yes | Section number within the course (0 = top/general section) |
| `name` | string | yes | Display name shown in the course |
| `custom_params` | object | no | Arbitrary key/value pairs forwarded to the LTI tool as custom parameters (e.g. `{"module_item_id": "abc", "educator_uid": "user@example.com"}`) |

**Returns:** `{ cmid: int, coursemodule_url: string }`

```json
{
  "function": "create_lti_activity",
  "params": {
    "courseid": 5,
    "sectionnum": 1,
    "name": "Week 1 Assignment",
    "custom_params": { "module_item_id": "abc123", "educator_uid": "teacher@example.com" }
  }
}
```

---

#### `update_lti_activity`

Updates an existing LTI activity. Only supplied fields are changed.

| Param | Type | Required | Description |
|---|---|---|---|
| `cmid` | int | yes | Course module ID of the activity |
| `name` | string | no | New display name |
| `visible` | 0\|1 | no | Show or hide the activity |
| `custom_params` | object | no | Fully replaces all existing LTI custom parameters. Omit to leave them unchanged. |

**Returns:** `{ cmid: int }`

---

#### `delete_lti_activity`

Permanently removes an LTI activity.

| Param | Type | Required | Description |
|---|---|---|---|
| `cmid` | int | yes | Course module ID of the activity |

**Returns:** `{ success: true }`

---

#### `create_section`

Adds a new section to a course.

| Param | Type | Required | Description |
|---|---|---|---|
| `courseid` | int | yes | Target course ID |
| `name` | string | no | Section name (if omitted Moodle uses its default naming, e.g. "Topic N") |
| `position` | int | no | Insert position (0 = after the last section, default) |

**Returns:** `{ id: int, section: int, name: string }`

---

#### `update_section`

Updates metadata on an existing section.

| Param | Type | Required | Description |
|---|---|---|---|
| `sectionid` | int | yes | Section record ID (not the section number) |
| `name` | string | no | New section name |
| `visible` | 0\|1 | no | Show or hide the section |
| `summary` | string | no | Section summary HTML |

At least one of `name`, `visible`, or `summary` must be provided.

**Returns:** `{ id: int, section: int, name: string, visible: 0|1 }`

---

#### `delete_section`

Removes a section. Fails if the section contains modules unless `force` is set.

| Param | Type | Required | Description |
|---|---|---|---|
| `sectionid` | int | yes | Section record ID |
| `force` | bool | no | Pass `true` to delete even if the section contains activities (default `false`) |

**Returns:** `{ success: true }`

## Security notes

This plugin is a stateless REST API. Two Moodle security mechanisms that apply to HTML form submissions are intentionally absent:

**`require_login()`** — This function validates a session cookie. This endpoint has no session (`NO_MOODLE_COOKIES` is defined). The Bearer token in the request body serves the same purpose: it identifies and authenticates the caller. This is the same pattern used by Moodle's own web service layer (`webservice/lib.php`).

**`sesskey`** — This is a session-bound CSRF token. CSRF is not a threat for token-authenticated REST APIs: a cross-origin request cannot include the Bearer token because it is not stored in a cookie that the browser attaches automatically. Requiring sesskey would also break all legitimate API clients, since no session exists to generate one from.

## Running Tests

```bash
cd {moodle_root}
php admin/tool/phpunit/cli/init.php
vendor/bin/phpunit --filter local_mathgpt
```

## Reporting Bugs

Use the [GitHub issue tracker](https://github.com/tutoruniverse/moodle-local_mathgpt/issues) to report bugs or request features.

## License

GNU General Public License v3 or later — see [LICENSE](LICENSE) or <https://www.gnu.org/licenses/gpl-3.0.html>.
