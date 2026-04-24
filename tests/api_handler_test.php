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

/**
 * Test class for local_mathgpt\api_handler dispatch guard clauses.
 *
 * @package   local_mathgpt
 */
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
