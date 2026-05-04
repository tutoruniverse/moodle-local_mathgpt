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

$string['pluginname']     = 'MathGPT API';
$string['ltitoolid']      = 'LTI Tool ID';
$string['ltitoolid_desc'] = 'The Moodle LTI 1.3 tool type ID for MathGPT activities.';
$string['notoolid']       = 'LTI tool ID is not configured. Set it in Site administration → Plugins → Local plugins → MathGPT API.';
$string['invalidtoken']   = 'Invalid access token.';
$string['tokenexpired']   = 'Access token has expired.';
$string['privacy:metadata'] = 'The MathGPT API plugin does not store any personal data. It acts as a REST bridge that orchestrates Moodle-internal APIs; any personal data (including LTI custom parameters) is stored and managed by mod_lti.';

// Capability.
$string['mathgpt:useapi'] = 'Use MathGPT API';

// API error responses.
$string['missingauthheader']       = 'Missing or malformed Authorization header.';
$string['invalidapitoken']         = 'Invalid or expired token.';
$string['invalidrequestbody']      = 'Request body must be JSON with a "function" key.';
$string['resourcenotfound']        = 'Resource not found.';
$string['insufficientcapabilities'] = 'Insufficient capabilities.';
