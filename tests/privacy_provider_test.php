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

namespace local_mathgpt;

/**
 * Privacy provider tests for local_mathgpt.
 *
 * @package   local_mathgpt
 * @covers    \local_mathgpt\privacy\provider
 */
final class privacy_provider_test extends \advanced_testcase {
    public function test_provider_implements_metadata_provider(): void {
        $this->assertTrue(
            is_a(\local_mathgpt\privacy\provider::class, \core_privacy\local\metadata\provider::class, true),
            'provider must implement metadata\provider interface'
        );
    }

    public function test_get_metadata_declares_lti_tool_link(): void {
        $collection = new \core_privacy\local\metadata\collection('local_mathgpt');
        $result = \local_mathgpt\privacy\provider::get_metadata($collection);

        $this->assertInstanceOf(\core_privacy\local\metadata\collection::class, $result);

        $items = $result->get_collection();
        $this->assertNotEmpty($items, 'metadata collection must not be empty');

        $names = array_map(fn($item) => $item->get_name(), $items);
        $this->assertContains('lti_tool', $names, 'collection must declare the lti_tool external link');
    }
}
