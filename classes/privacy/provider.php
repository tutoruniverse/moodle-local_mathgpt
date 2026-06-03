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

use core_privacy\local\metadata\collection;
use core_privacy\local\metadata\provider as metadata_provider;

/**
 * Privacy provider for local_mathgpt.
 *
 * This plugin stores no data of its own, but forwards caller-supplied custom
 * parameters (which may include PII) to the configured LTI 1.3 external tool
 * via mod_lti. That external data flow is declared below.
 *
 * @package   local_mathgpt
 * @category  privacy
 * @copyright 2026 MathGPT <backend@gotitapp.co>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements metadata_provider {
    /**
     * Describe all data sent to external systems by this plugin.
     *
     * @param collection $collection Metadata collection to populate.
     * @return collection Updated collection.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_external_location_link(
            'lti_tool',
            [
                'name'          => 'privacy:metadata:lti_tool:name',
                'custom_params' => 'privacy:metadata:lti_tool:custom_params',
            ],
            'privacy:metadata:lti_tool'
        );
        return $collection;
    }
}
