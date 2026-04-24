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
 * LTI 1.3 activity CRUD helpers for local_mathgpt.
 *
 * @package   local_mathgpt
 * @copyright 2026 MathGPT <backend@gotitapp.co>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_mathgpt;

global $CFG;
require_once($CFG->dirroot . '/course/modlib.php');
require_once($CFG->dirroot . '/course/lib.php');

class lti_manager {

    /**
     * Create a new mod_lti activity in the given course section.
     *
     * @param int    $courseid   Target course ID
     * @param int    $sectionnum Section number (0 = top)
     * @param string $name       Display name shown in the course
     * @param string $module_item_id MathGPT content UUID stored as LTI custom param
     * @return array { cmid: int, coursemodule_url: string }
     * @throws \moodle_exception if LTI tool ID is not configured in plugin settings
     */
    public function create(int $courseid, int $sectionnum, string $name, string $module_item_id, string $educator_uid): array {
        $toolid = (int) get_config('local_mathgpt', 'ltitoolid');
        if (!$toolid) {
            throw new \moodle_exception('notoolid', 'local_mathgpt');
        }

        get_course($courseid); // throws dml_missing_record_exception if not found

        $moduleinfo                             = new \stdClass();
        $moduleinfo->modulename                 = 'lti';
        $moduleinfo->course                     = $courseid;
        $moduleinfo->section                    = $sectionnum;
        $moduleinfo->typeid                     = $toolid;
        $moduleinfo->name                       = $name;
        $moduleinfo->instructorcustomparameters = 'module_item_id=' . $module_item_id . "\neducator_uid=" . $educator_uid;
        $moduleinfo->visible                    = 1;
        $moduleinfo->introeditor                = ['text' => '', 'format' => FORMAT_HTML, 'itemid' => 0];
        $moduleinfo->grade                      = 0;
        $moduleinfo->cmidnumber                 = '';

        $moduleinfo = create_module($moduleinfo);

        $url = new \moodle_url('/mod/lti/view.php', ['id' => $moduleinfo->coursemodule]);

        return [
            'cmid'             => (int) $moduleinfo->coursemodule,
            'coursemodule_url' => $url->out(false),
        ];
    }

    /**
     * Delete a course module by cmid.
     *
     * @param int $cmid Course module ID to delete
     * @return array { success: true }
     */
    public function delete(int $cmid): array {
        course_delete_module($cmid);
        return ['success' => true];
    }

    /**
     * Update an existing LTI activity. Only keys present in $updates are changed.
     *
     * @param int   $cmid    Course module ID of the LTI activity
     * @param array $updates Keys: name (string), module_item_id (string), visible (bool/int)
     * @return array { cmid: int }
     * @throws \dml_missing_record_exception if cmid does not exist
     */
    public function update(int $cmid, array $updates): array {
        global $DB;

        $cm     = get_coursemodule_from_id('lti', $cmid, 0, false, MUST_EXIST);
        $course = get_course($cm->course);
        $lti    = $DB->get_record('lti', ['id' => $cm->instance], '*', MUST_EXIST);

        $moduleinfo                             = new \stdClass();
        $moduleinfo->coursemodule               = $cmid;
        $moduleinfo->modulename                 = 'lti';
        $moduleinfo->course                     = (int) $cm->course;
        $moduleinfo->instance                   = (int) $cm->instance;
        $moduleinfo->section                    = (int) $cm->sectionnum;
        $moduleinfo->typeid                     = (int) $lti->typeid;
        $moduleinfo->name                       = $updates['name']    ?? $lti->name;
        $moduleinfo->visible                    = isset($updates['visible'])
            ? (int) $updates['visible']
            : (int) $cm->visible;
        if (isset($updates['module_item_id'])) {
            // Rebuild params, preserving educator_uid from the existing record.
            $existing = [];
            foreach (explode("\n", $lti->instructorcustomparameters ?? '') as $line) {
                [$k, $v] = array_pad(explode('=', $line, 2), 2, '');
                if ($k !== '') {
                    $existing[trim($k)] = trim($v);
                }
            }
            $new_params = 'module_item_id=' . $updates['module_item_id'];
            if (!empty($existing['educator_uid'])) {
                $new_params .= "\neducator_uid=" . $existing['educator_uid'];
            }
            $moduleinfo->instructorcustomparameters = $new_params;
        } else {
            $moduleinfo->instructorcustomparameters = $lti->instructorcustomparameters ?? '';
        }
        $moduleinfo->introeditor                = ['text' => $lti->intro ?? '', 'format' => (int) ($lti->introformat ?? FORMAT_HTML), 'itemid' => 0];
        $moduleinfo->grade                      = $lti->grade      ?? 0;
        $moduleinfo->cmidnumber                 = $cm->idnumber    ?? '';

        update_module($moduleinfo);

        return ['cmid' => $cmid];
    }
}
