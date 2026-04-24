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
define('NO_MOODLE_COOKIES', true);

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/mathgpt/classes/auth.php');
require_once($CFG->dirroot . '/local/mathgpt/classes/api_handler.php');

use local_mathgpt\auth;
use local_mathgpt\api_handler;

header('Content-Type: application/json');

// 1. Extract Bearer token
$authheader = $_SERVER['HTTP_AUTHORIZATION']
    ?? (function_exists('apache_request_headers') ? (apache_request_headers()['Authorization'] ?? '') : '');
$token = auth::extract_bearer_token($authheader);

if ($token === null) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Missing or malformed Authorization header']);
    exit;
}

// 2. Validate token and bootstrap Moodle session as service account user
try {
    $userid = auth::validate_bearer_token($token);
} catch (\moodle_exception $e) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Invalid or expired token']);
    exit;
}

$PAGE->set_context(\context_system::instance());
\core\session\manager::set_user(core_user::get_user($userid, '*', MUST_EXIST));

// 3. Parse request body
$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body) || !isset($body['function'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Request body must be JSON with a "function" key']);
    exit;
}

$function = (string) $body['function'];
$params   = is_array($body['params'] ?? null) ? $body['params'] : [];

// 4. Dispatch
try {
    $data = (new api_handler())->dispatch($function, $params);
    echo json_encode(['success' => true, 'data' => $data]);

} catch (\invalid_parameter_exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);

} catch (\dml_missing_record_exception $e) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Resource not found']);

} catch (\required_capability_exception $e) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Insufficient capabilities']);

} catch (\moodle_exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
