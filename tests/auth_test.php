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

/**
 * Test class for local_mathgpt\auth.
 *
 * @package   local_mathgpt
 */
class local_mathgpt_auth_test extends advanced_testcase {

    // --- extract_bearer_token ---

    public function test_extract_bearer_token_valid(): void {
        $this->assertEquals('abc123', auth::extract_bearer_token('Bearer abc123'));
    }

    public function test_extract_bearer_token_lowercase_scheme(): void {
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
        $this->skip_if_no_oauth2_table();

        $this->expectException(\moodle_exception::class);
        auth::validate_bearer_token('doesnotexist');
    }
}
