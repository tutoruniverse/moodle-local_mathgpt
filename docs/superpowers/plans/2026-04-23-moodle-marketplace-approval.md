# Moodle Marketplace Approval Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add the missing files and modifications required to publish `local_mathgpt` on plugins.moodle.org.

**Architecture:** No changes to existing API logic. Pure additions: Privacy API null_provider, GPL license headers on all PHP files, `local_oauth2` dependency declaration in `version.php`, a `README.md`, and PHPUnit tests covering auth, dispatch guard clauses, and LTI manager configuration guard.

**Tech Stack:** PHP 8.1+, Moodle 4.3+ PHPUnit (`advanced_testcase`), no build tools.

---

## File Map

| File | Action | Responsibility |
|---|---|---|
| `classes/privacy/provider.php` | **Create** | null_provider declaration |
| `lang/en/local_mathgpt.php` | **Modify** | Add `privacy:metadata` lang string |
| `tests/privacy_provider_test.php` | **Create** | TDD test for privacy provider |
| `tests/auth_test.php` | **Create** | Tests for `local_mathgpt\auth` |
| `tests/api_handler_test.php` | **Create** | Tests for `local_mathgpt\api_handler` dispatch guard clauses |
| `tests/lti_manager_test.php` | **Create** | Tests for `local_mathgpt\lti_manager` config guard |
| `api.php` | **Modify** | Add GPL license header |
| `classes/auth.php` | **Modify** | Add GPL license header |
| `classes/api_handler.php` | **Modify** | Add GPL license header |
| `classes/lti_manager.php` | **Modify** | Add GPL license header |
| `settings.php` | **Modify** | Add GPL license header |
| `version.php` | **Modify** | Add GPL license header + `$plugin->dependencies` |
| `README.md` | **Create** | Plugin documentation |

---

## Pre-requisite: Initialise git and PHPUnit

- [ ] **Initialise git repo** (the plugin directory is not yet a git repository)

```bash
cd /Users/hoaha/apps/moodle-plugin && git init && git add . && git commit -m "chore: initial commit — existing plugin files"
```

- [ ] **Initialise PHPUnit** (run once; compiles autoloaders and creates the test database)

```bash
cd /Users/hoaha/apps/moodle/moodle && php admin/tool/phpunit/cli/init.php
```

Expected: ends with `PHPUnit environment initialization complete.`

---

## Task 1: Privacy Provider (TDD)

**Files:**
- Create: `classes/privacy/provider.php`
- Modify: `lang/en/local_mathgpt.php`

- [ ] **Step 1: Write the failing test**

Create `tests/privacy_provider_test.php`:

```php
<?php
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
 * Privacy provider tests for local_mathgpt.
 *
 * @package   local_mathgpt
 * @copyright 2026 MathGPT <backend@gotitapp.co>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class local_mathgpt_privacy_provider_test extends advanced_testcase {

    public function test_provider_implements_null_provider(): void {
        $this->assertTrue(
            is_a(\local_mathgpt\privacy\provider::class, \core_privacy\local\metadata\null_provider::class, true),
            'provider must implement null_provider interface'
        );
    }

    public function test_get_reason_returns_non_empty_string(): void {
        $reason = \local_mathgpt\privacy\provider::get_reason();
        $this->assertIsString($reason);
        $this->assertNotEmpty($reason);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
cd /Users/hoaha/apps/moodle/moodle && vendor/bin/phpunit --filter local_mathgpt_privacy_provider_test
```

Expected: ERROR — `Class "local_mathgpt\privacy\provider" not found`

- [ ] **Step 3: Implement the privacy provider**

Create `classes/privacy/provider.php`:

```php
<?php
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
 * Privacy provider for local_mathgpt.
 *
 * @package   local_mathgpt
 * @copyright 2026 MathGPT <backend@gotitapp.co>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_mathgpt\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\null_provider;

class provider implements null_provider {

    public static function get_reason(): string {
        return 'privacy:metadata';
    }
}
```

- [ ] **Step 4: Add the privacy lang string**

In `lang/en/local_mathgpt.php`, add this line at the end (before the closing `?>` if present, or just append):

```php
$string['privacy:metadata'] = 'The MathGPT API plugin does not store any personal data. It acts as a REST bridge that orchestrates Moodle-internal APIs; any personal data (including LTI custom parameters) is stored and managed by mod_lti.';
```

- [ ] **Step 5: Run test to verify it passes**

```bash
cd /Users/hoaha/apps/moodle/moodle && vendor/bin/phpunit --filter local_mathgpt_privacy_provider_test
```

Expected: `OK (2 tests, 2 assertions)`

- [ ] **Step 6: Commit**

```bash
git add classes/privacy/provider.php lang/en/local_mathgpt.php tests/privacy_provider_test.php
git commit -m "feat: add Privacy API null_provider"
```

---

## Task 2: Auth Class Tests

**Files:**
- Create: `tests/auth_test.php`

These tests cover `extract_bearer_token` (pure logic, no DB) and `validate_bearer_token` (reads from `local_oauth2_access_token`). The DB tests are skipped automatically if `local_oauth2` is not installed in the test environment.

- [ ] **Step 1: Create the test file**

Create `tests/auth_test.php`:

```php
<?php
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
 * Tests for local_mathgpt\auth.
 *
 * @package   local_mathgpt
 * @copyright 2026 MathGPT <backend@gotitapp.co>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use local_mathgpt\auth;

class local_mathgpt_auth_test extends advanced_testcase {

    // --- extract_bearer_token ---

    public function test_extract_bearer_token_valid(): void {
        $this->assertEquals('abc123', auth::extract_bearer_token('Bearer abc123'));
    }

    public function test_extract_bearer_token_valid_mixed_case(): void {
        $this->assertEquals('tok', auth::extract_bearer_token('bearer tok'));
    }

    public function test_extract_bearer_token_empty_string(): void {
        $this->assertNull(auth::extract_bearer_token(''));
    }

    public function test_extract_bearer_token_wrong_scheme(): void {
        $this->assertNull(auth::extract_bearer_token('Basic abc123'));
    }

    public function test_extract_bearer_token_bearer_only_no_token(): void {
        $this->assertNull(auth::extract_bearer_token('Bearer'));
    }

    // --- validate_bearer_token ---

    private function skip_if_no_oauth2_table(): void {
        global $DB;
        if (!$DB->get_manager()->table_exists('local_oauth2_access_token')) {
            $this->markTestSkipped('local_oauth2 plugin not installed — skipping DB-dependent auth tests');
        }
    }

    public function test_validate_bearer_token_valid(): void {
        global $DB;
        $this->resetAfterTest();
        $this->skip_if_no_oauth2_table();

        $user = $this->getDataGenerator()->create_user();
        $DB->insert_record('local_oauth2_access_token', [
            'access_token' => 'validtoken',
            'user_id'      => $user->id,
            'expires'      => time() + 3600,
        ]);

        $this->assertEquals((int) $user->id, auth::validate_bearer_token('validtoken'));
    }

    public function test_validate_bearer_token_expired(): void {
        global $DB;
        $this->resetAfterTest();
        $this->skip_if_no_oauth2_table();

        $user = $this->getDataGenerator()->create_user();
        $DB->insert_record('local_oauth2_access_token', [
            'access_token' => 'expiredtoken',
            'user_id'      => $user->id,
            'expires'      => time() - 100,
        ]);

        $this->expectException(\moodle_exception::class);
        auth::validate_bearer_token('expiredtoken');
    }

    public function test_validate_bearer_token_nonexistent(): void {
        $this->resetAfterTest();
        $this->skip_if_no_oauth2_table();

        $this->expectException(\moodle_exception::class);
        auth::validate_bearer_token('doesnotexist');
    }
}
```

- [ ] **Step 2: Run tests**

```bash
cd /Users/hoaha/apps/moodle/moodle && vendor/bin/phpunit --filter local_mathgpt_auth_test
```

Expected: `OK (8 tests, ...)` — DB tests may show as skipped if `local_oauth2` is not installed, which is acceptable.

- [ ] **Step 3: Commit**

```bash
git add tests/auth_test.php
git commit -m "test: add auth class tests"
```

---

## Task 3: API Handler Dispatch Tests

**Files:**
- Create: `tests/api_handler_test.php`

These tests only exercise guard-clause paths (missing required params) — no actual course or LTI operations are performed, so no special fixtures are needed.

- [ ] **Step 1: Create the test file**

Create `tests/api_handler_test.php`:

```php
<?php
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
 * Tests for local_mathgpt\api_handler dispatch guard clauses.
 *
 * @package   local_mathgpt
 * @copyright 2026 MathGPT <backend@gotitapp.co>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use local_mathgpt\api_handler;

class local_mathgpt_api_handler_test extends advanced_testcase {

    private api_handler $handler;

    protected function setUp(): void {
        parent::setUp();
        $this->handler = new api_handler();
    }

    public function test_dispatch_unknown_function(): void {
        $this->expectException(\invalid_parameter_exception::class);
        $this->handler->dispatch('nonexistent_function', []);
    }

    public function test_dispatch_get_course_contents_missing_courseid(): void {
        $this->expectException(\invalid_parameter_exception::class);
        $this->handler->dispatch('get_course_contents', []);
    }

    public function test_dispatch_create_lti_activity_missing_courseid(): void {
        $this->expectException(\invalid_parameter_exception::class);
        $this->handler->dispatch('create_lti_activity', [
            'sectionnum'     => 0,
            'name'           => 'Test',
            'module_item_id' => 'item1',
            'educator_uid'   => 'user1',
        ]);
    }

    public function test_dispatch_create_lti_activity_missing_name(): void {
        $this->expectException(\invalid_parameter_exception::class);
        $this->handler->dispatch('create_lti_activity', [
            'courseid'       => 1,
            'sectionnum'     => 0,
            'module_item_id' => 'item1',
            'educator_uid'   => 'user1',
        ]);
    }

    public function test_dispatch_update_lti_activity_missing_cmid(): void {
        $this->expectException(\invalid_parameter_exception::class);
        $this->handler->dispatch('update_lti_activity', []);
    }

    public function test_dispatch_delete_lti_activity_missing_cmid(): void {
        $this->expectException(\invalid_parameter_exception::class);
        $this->handler->dispatch('delete_lti_activity', []);
    }

    public function test_dispatch_create_section_missing_courseid(): void {
        $this->expectException(\invalid_parameter_exception::class);
        $this->handler->dispatch('create_section', []);
    }

    public function test_dispatch_update_section_missing_sectionid(): void {
        $this->expectException(\invalid_parameter_exception::class);
        $this->handler->dispatch('update_section', []);
    }

    public function test_dispatch_delete_section_missing_sectionid(): void {
        $this->expectException(\invalid_parameter_exception::class);
        $this->handler->dispatch('delete_section', []);
    }
}
```

- [ ] **Step 2: Run tests**

```bash
cd /Users/hoaha/apps/moodle/moodle && vendor/bin/phpunit --filter local_mathgpt_api_handler_test
```

Expected: `OK (9 tests, 9 assertions)`

- [ ] **Step 3: Commit**

```bash
git add tests/api_handler_test.php
git commit -m "test: add api_handler dispatch guard-clause tests"
```

---

## Task 4: LTI Manager Tests

**Files:**
- Create: `tests/lti_manager_test.php`

- [ ] **Step 1: Create the test file**

Create `tests/lti_manager_test.php`:

```php
<?php
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
 * Tests for local_mathgpt\lti_manager.
 *
 * @package   local_mathgpt
 * @copyright 2026 MathGPT <backend@gotitapp.co>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use local_mathgpt\lti_manager;

class local_mathgpt_lti_manager_test extends advanced_testcase {

    public function test_create_throws_when_ltitoolid_not_configured(): void {
        $this->resetAfterTest();
        // Explicitly unset the config to ensure it is not configured.
        unset_config('ltitoolid', 'local_mathgpt');

        $this->expectException(\moodle_exception::class);
        (new lti_manager())->create(1, 0, 'Test Activity', 'item-uuid-1', 'educator-uid-1');
    }
}
```

- [ ] **Step 2: Run tests**

```bash
cd /Users/hoaha/apps/moodle/moodle && vendor/bin/phpunit --filter local_mathgpt_lti_manager_test
```

Expected: `OK (1 test, 1 assertion)`

- [ ] **Step 3: Commit**

```bash
git add tests/lti_manager_test.php
git commit -m "test: add lti_manager config guard test"
```

---

## Task 5: GPL License Headers

Add the standard Moodle GPL v3+ docblock to every PHP file that is missing it. The `classes/privacy/provider.php` file already has it from Task 1 — skip it here.

The docblock goes immediately after `<?php`, before any other content. Each file gets a one-line description tailored to its purpose.

- [ ] **Step 1: Add header to `version.php`**

Replace the first line `<?php` with:

```php
<?php
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
 * Plugin version metadata for local_mathgpt.
 *
 * @package   local_mathgpt
 * @copyright 2026 MathGPT <backend@gotitapp.co>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
```

- [ ] **Step 2: Add header to `settings.php`**

Replace `<?php` with:

```php
<?php
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
 * Admin settings for local_mathgpt.
 *
 * @package   local_mathgpt
 * @copyright 2026 MathGPT <backend@gotitapp.co>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
```

- [ ] **Step 3: Add header to `api.php`**

The file currently starts with `<?php` followed by a docblock comment (`/** ... */`). Insert the GPL block between `<?php` and the existing docblock:

```php
<?php
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
 * local_mathgpt REST entry point.
 *
 * @package   local_mathgpt
 * @copyright 2026 MathGPT <backend@gotitapp.co>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
```

Remove the old inline docblock (lines 2–9 of the original file — the `/** ... */` block that describes the request/auth/response format). Move that documentation to `README.md` instead (it will be covered there in Task 7).

- [ ] **Step 4: Add header to `classes/auth.php`**

Replace `<?php` with:

```php
<?php
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
 * Bearer token authentication for local_mathgpt.
 *
 * @package   local_mathgpt
 * @copyright 2026 MathGPT <backend@gotitapp.co>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
```

- [ ] **Step 5: Add header to `classes/api_handler.php`**

Replace `<?php` with:

```php
<?php
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
 * API dispatcher for local_mathgpt.
 *
 * @package   local_mathgpt
 * @copyright 2026 MathGPT <backend@gotitapp.co>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
```

- [ ] **Step 6: Add header to `classes/lti_manager.php`**

Replace `<?php` with:

```php
<?php
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
 * LTI 1.3 activity CRUD helpers for local_mathgpt.
 *
 * @package   local_mathgpt
 * @copyright 2026 MathGPT <backend@gotitapp.co>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
```

- [ ] **Step 7: Add header to `lang/en/local_mathgpt.php`**

Replace `<?php` with:

```php
<?php
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
 * English language strings for local_mathgpt.
 *
 * @package   local_mathgpt
 * @copyright 2026 MathGPT <backend@gotitapp.co>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
```

- [ ] **Step 8: Commit**

```bash
git add api.php classes/auth.php classes/api_handler.php classes/lti_manager.php settings.php version.php lang/en/local_mathgpt.php
git commit -m "chore: add GPL v3 license headers to all PHP files"
```

---

## Task 6: Declare `local_oauth2` Dependency

**Files:**
- Modify: `version.php`

- [ ] **Step 1: Add dependency declaration**

In `version.php`, add one line after `$plugin->release`:

```php
$plugin->dependencies = ['local_oauth2' => ANY_VERSION];
```

The full file should now read:

```php
<?php
// [license header from Task 5] ...

defined('MOODLE_INTERNAL') || die();

$plugin->component    = 'local_mathgpt';
$plugin->version      = 2026041400;
$plugin->requires     = 2023100900; // Moodle 4.3
$plugin->maturity     = MATURITY_STABLE;
$plugin->release      = '1.0.0';
$plugin->dependencies = ['local_oauth2' => ANY_VERSION];
```

- [ ] **Step 2: Verify Moodle upgrade still runs cleanly**

```bash
php /Users/hoaha/apps/moodle/moodle/admin/cli/upgrade.php --non-interactive
```

Expected: no errors; ends with `Upgrade completed successfully.`

- [ ] **Step 3: Commit**

```bash
git add version.php
git commit -m "chore: declare local_oauth2 dependency in version.php"
```

---

## Task 7: README.md

**Files:**
- Create: `README.md`

- [ ] **Step 1: Create README.md at the repo root**

```markdown
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
```

- [ ] **Step 2: Commit**

```bash
git add README.md
git commit -m "docs: add README for Moodle Plugin Directory submission"
```

---

## Task 8: Run Full Test Suite

Verify all tests pass together before submission.

- [ ] **Step 1: Run all plugin tests**

```bash
cd /Users/hoaha/apps/moodle/moodle && vendor/bin/phpunit --filter local_mathgpt
```

Expected output (exact counts may vary if oauth2 tests are skipped):

```
OK (N tests, N assertions)
```

No failures. Skipped tests (for `local_oauth2` DB tests) are acceptable.

- [ ] **Step 2: Verify plugin upgrade still works**

```bash
php /Users/hoaha/apps/moodle/moodle/admin/cli/upgrade.php --non-interactive
```

Expected: `Upgrade completed successfully.`

---

## Post-Implementation: Operational Steps (Manual)

These are not code changes — do them after all tasks are complete:

1. **Create a public GitHub repository** with the plugin directory as the repo root (`version.php` at root, not inside a subdirectory).
2. **Enable GitHub Issues** on the repo as the public issue tracker.
3. **Create a release tag** (e.g., `v1.0.0`) and attach the plugin ZIP.
4. **Register on [plugins.moodle.org](https://moodle.org/plugins)** and submit, providing:
   - Repository URL
   - Issue tracker URL
   - Short description (~150 chars)
   - Full description
   - Screenshots of the plugin settings page
