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

/**
 * Test class for local_mathgpt\lti_manager.
 *
 * @package   local_mathgpt
 */
class local_mathgpt_lti_manager_test extends advanced_testcase {

    public function test_create_throws_when_ltitoolid_not_configured(): void {
        $this->resetAfterTest();
        // Explicitly unset the config to ensure it is not configured.
        unset_config('ltitoolid', 'local_mathgpt');

        $this->expectException(\moodle_exception::class);
        (new lti_manager())->create(1, 0, 'Test Activity', 'item-uuid-1', 'educator-uid-1');
    }
}
