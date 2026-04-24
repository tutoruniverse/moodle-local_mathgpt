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

/**
 * Privacy provider tests for local_mathgpt.
 *
 * @package   local_mathgpt
 */
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
        // Verify the key actually resolves in the lang system (missing key returns '[[key]]').
        $resolved = get_string($reason, 'local_mathgpt');
        $this->assertStringNotContainsString('[[', $resolved);
    }
}
