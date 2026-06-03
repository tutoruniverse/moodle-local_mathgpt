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

defined('MOODLE_INTERNAL') || die();

$string['cannotdeletesection']      = 'Cannot delete section: it contains activities. Pass force=true to delete anyway.';
$string['insufficientcapabilities'] = 'Insufficient capabilities.';
$string['invalidapitoken']         = 'Invalid or expired token.';
$string['invalidrequestbody']      = 'Request body must be JSON with "token" and "function" keys.';
$string['invalidtoken']            = 'Invalid access token.';
$string['ltitoolid']               = 'LTI tool ID';
$string['ltitoolid_desc']          = 'The Moodle LTI 1.3 tool type ID for MathGPT activities.';
$string['mathgpt:useapi']          = 'Use MathGPT API';
$string['notoolid']                = 'LTI tool ID is not configured. Set it in Site administration → Plugins → Local plugins → MathGPT API.';
$string['plugindesc']              = 'REST API plugin that allows external services to programmatically manage Moodle course content including courses, sections, and LTI 1.3 activities.';
$string['pluginname']              = 'MathGPT API';
$string['privacy:metadata']                    = 'The MathGPT API plugin does not store any personal data. It acts as a REST bridge that orchestrates Moodle-internal APIs; any personal data (including LTI custom parameters) is stored and managed by mod_lti.';
$string['privacy:metadata:lti_tool']           = 'The configured LTI 1.3 external tool receives data when activities are created or updated via the MathGPT API.';
$string['privacy:metadata:lti_tool:custom_params'] = 'Arbitrary key/value parameters supplied by the API caller and forwarded verbatim to the LTI tool as custom launch parameters. May include personal data such as user email addresses or identifiers.';
$string['privacy:metadata:lti_tool:name']      = 'The display name of the LTI activity.';
$string['resourcenotfound']        = 'Resource not found.';
$string['unexpectederror']         = 'An unexpected error occurred. Please contact the site administrator.';
$string['tokenexpired']            = 'Access token has expired.';
