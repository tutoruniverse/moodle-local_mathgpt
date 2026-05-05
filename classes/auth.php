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

namespace local_mathgpt;

defined('MOODLE_INTERNAL') || die();

/**
 * Bearer token authentication helpers for local_mathgpt.
 *
 * @package   local_mathgpt
 * @copyright 2026 MathGPT <backend@gotitapp.co>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class auth {
    /**
     * Extract the raw token string from an Authorization header value.
     * Returns null if header is absent, uses a different scheme, or has no token.
     *
     * @param string $header The value of the Authorization header.
     * @return string|null The raw token, or null if not a valid Bearer header.
     */
    public static function extract_bearer_token(string $header): ?string {
        if (preg_match('/^Bearer\s+(\S+)$/i', trim($header), $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Validate a Bearer token against local_oauth2's access token store.
     * Returns the associated Moodle user ID on success.
     *
     * Table: local_oauth2_access_token
     * Columns: access_token, user_id, expires (Unix timestamp)
     *
     * @param string $token The raw Bearer token to validate.
     * @return int The Moodle user ID associated with the token.
     * @throws \moodle_exception On missing or expired token.
     */
    public static function validate_bearer_token(string $token): int {
        global $DB;

        $record = $DB->get_record('local_oauth2_access_token', ['access_token' => $token]);
        if (!$record) {
            throw new \moodle_exception('invalidtoken', 'local_mathgpt');
        }
        if ($record->expires < time()) {
            throw new \moodle_exception('tokenexpired', 'local_mathgpt');
        }
        return (int) $record->user_id;
    }
}
