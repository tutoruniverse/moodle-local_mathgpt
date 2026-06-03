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
 * API dispatcher for local_mathgpt.
 *
 * @package   local_mathgpt
 * @copyright 2026 MathGPT <backend@gotitapp.co>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_mathgpt;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/course/modlib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/local/mathgpt/classes/lti_manager.php');

/**
 * Dispatches API requests for the local_mathgpt plugin.
 *
 * @package   local_mathgpt
 * @copyright 2026 MathGPT <backend@gotitapp.co>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class api_handler {
    /**
     * Route an API function call to its handler.
     *
     * @param string $function Name of the API function to call.
     * @param array  $params   Request parameters.
     * @return array Handler result.
     * @throws \invalid_parameter_exception For unknown function names.
     */
    public function dispatch(string $function, array $params): ?array {
        switch ($function) {
            case 'get_courses':
                return $this->get_courses();
            case 'get_course':
                return $this->get_course($params);
            case 'get_course_contents':
                return $this->get_course_contents($params);
            case 'create_lti_activity':
                return $this->create_lti_activity($params);
            case 'update_lti_activity':
                return $this->update_lti_activity($params);
            case 'delete_lti_activity':
                return $this->delete_lti_activity($params);
            case 'create_section':
                return $this->create_section($params);
            case 'update_section':
                return $this->update_section($params);
            case 'delete_section':
                return $this->delete_section($params);
            default:
                throw new \invalid_parameter_exception("Unknown function: {$function}");
        }
    }

    /**
     * List all courses the current user is enrolled in.
     *
     * @return array Course records with id, fullname, shortname, visible, startdate, enddate, timecreated, summary.
     */
    private function get_courses(): array {
        global $USER;
        $courses = enrol_get_users_courses(
            $USER->id,
            false,
            'id,fullname,shortname,visible,startdate,enddate,timecreated,summary',
            'fullname ASC'
        );
        $result = [];
        foreach ($courses as $course) {
            $result[] = [
                'id'          => (int)$course->id,
                'fullname'    => $course->fullname,
                'shortname'   => $course->shortname,
                'visible'     => (int)$course->visible,
                'startdate'   => (int)$course->startdate,
                'enddate'     => (int)$course->enddate,
                'timecreated' => (int)$course->timecreated,
                'summary'     => $course->summary ?? '',
            ];
        }
        return $result;
    }

    /**
     * Return details for a single course by ID.
     *
     * @param array $params Must contain 'courseid'.
     * @return array Course record with id, fullname, shortname, visible, startdate, enddate, timecreated, summary.
     * @throws \invalid_parameter_exception If courseid is missing.
     */
    private function get_course(array $params): ?array {
        global $USER;
        if (empty($params['courseid'])) {
            throw new \invalid_parameter_exception('Missing required param: courseid');
        }
        $courseid = validate_param($params['courseid'], PARAM_INT);
        if ($courseid <= 0) {
            throw new \invalid_parameter_exception('courseid must be a positive integer');
        }
        try {
            $course = get_course($courseid);
        } catch (\dml_missing_record_exception $e) {
            return null;
        }
        $context = \context_course::instance($course->id);
        if (!is_enrolled($context, $USER->id)) {
            return null;
        }
        return [
            'id'          => (int) $course->id,
            'fullname'    => $course->fullname,
            'shortname'   => $course->shortname,
            'visible'     => (int) $course->visible,
            'startdate'   => (int) $course->startdate,
            'enddate'     => (int) $course->enddate,
            'timecreated' => (int) $course->timecreated,
            'summary'     => $course->summary ?? '',
        ];
    }

    /**
     * Return sections and modules for a course.
     *
     * @param array $params Must contain 'courseid'.
     * @return array Sections, each with a nested modules array.
     * @throws \invalid_parameter_exception If courseid is missing.
     */
    private function get_course_contents(array $params): array {
        global $DB;

        if (empty($params['courseid'])) {
            throw new \invalid_parameter_exception('Missing required param: courseid');
        }
        $courseid = validate_param($params['courseid'], PARAM_INT);
        if ($courseid <= 0) {
            throw new \invalid_parameter_exception('courseid must be a positive integer');
        }
        $course  = get_course($courseid); // Throws dml_missing_record_exception if absent.
        $modinfo = get_fast_modinfo($course);

        // Batch-load custom params for all LTI activities in this course.
        $ltiparams = [];
        $rows = $DB->get_records_sql(
            'SELECT cm.id AS cmid, l.instructorcustomparameters
               FROM {course_modules} cm
               JOIN {lti} l ON l.id = cm.instance
              WHERE cm.course = :courseid',
            ['courseid' => $courseid]
        );
        foreach ($rows as $row) {
            $decoded = [];
            foreach (explode("\n", $row->instructorcustomparameters ?? '') as $line) {
                [$k, $v] = array_pad(explode('=', $line, 2), 2, '');
                if (trim($k) !== '') {
                    $decoded[trim($k)] = trim($v);
                }
            }
            $ltiparams[(int)$row->cmid] = $decoded;
        }

        $result = [];
        foreach ($modinfo->get_section_info_all() as $sectioninfo) {
            $modules = [];
            foreach ($modinfo->sections[$sectioninfo->section] ?? [] as $cmid) {
                $cm = $modinfo->cms[$cmid];
                // 1. Check if the module is marked for deletion (The "Ghost" fix)
                if ($cm->deletioninprogress) {
                    continue;
                }
                // 2. Check if the current user (token holder) can actually see it.
                if (!$cm->uservisible) {
                    continue;
                }
                $module = [
                    'id'      => (int)$cm->id,
                    'modname' => $cm->modname,
                    'name'    => $cm->name,
                    'visible' => (int)$cm->visible,
                ];
                if ($cm->modname === 'lti' && !empty($ltiparams[(int)$cm->id])) {
                    $module['custom_params'] = $ltiparams[(int)$cm->id];
                }
                $modules[] = $module;
            }
            $result[] = [
                'id'        => (int)$sectioninfo->id,
                'name'      => get_section_name($course, $sectioninfo),
                'modules'   => $modules,
                'component' => isset($sectioninfo->component) ? (string)$sectioninfo->component : '',
            ];
        }
        return $result;
    }

    /**
     * Create an LTI 1.3 activity in a course section.
     *
     * @param array $params Must contain 'courseid', 'sectionnum', 'name'. Optional: 'custom_params'.
     * @return array Created activity data.
     * @throws \invalid_parameter_exception If a required param is missing.
     */
    private function create_lti_activity(array $params): array {
        foreach (['courseid', 'sectionnum', 'name'] as $field) {
            if (!isset($params[$field]) || $params[$field] === '') {
                throw new \invalid_parameter_exception("Missing required param: {$field}");
            }
        }
        $courseid   = validate_param($params['courseid'],   PARAM_INT);
        $sectionnum = validate_param($params['sectionnum'], PARAM_INT);
        $name       = clean_param(   $params['name'],       PARAM_TEXT);
        if ($courseid <= 0) {
            throw new \invalid_parameter_exception('courseid must be a positive integer');
        }
        if ($sectionnum < 0) {
            throw new \invalid_parameter_exception('sectionnum must be a non-negative integer');
        }
        if ($name === '') {
            throw new \invalid_parameter_exception('name cannot be empty');
        }
        $customparams = [];
        if (isset($params['custom_params']) && is_array($params['custom_params'])) {
            foreach ($params['custom_params'] as $k => $v) {
                $cleankey = validate_param((string) $k, PARAM_ALPHANUMEXT);
                $customparams[$cleankey] = clean_param((string) $v, PARAM_TEXT);
            }
        }
        return (new lti_manager())->create($courseid, $sectionnum, $name, $customparams);
    }

    /**
     * Update an existing LTI activity.
     *
     * @param array $params Must contain 'cmid'. Optional: 'name', 'visible', 'custom_params'.
     * @return array Updated activity data.
     * @throws \invalid_parameter_exception If cmid is missing.
     */
    private function update_lti_activity(array $params): array {
        if (empty($params['cmid'])) {
            throw new \invalid_parameter_exception('Missing required param: cmid');
        }
        $cmid = validate_param($params['cmid'], PARAM_INT);
        if ($cmid <= 0) {
            throw new \invalid_parameter_exception('cmid must be a positive integer');
        }
        $updates = [];
        if (isset($params['name'])) {
            $updates['name'] = clean_param($params['name'], PARAM_TEXT);
            if ($updates['name'] === '') {
                throw new \invalid_parameter_exception('name cannot be empty');
            }
        }
        if (isset($params['visible'])) {
            $visible = validate_param($params['visible'], PARAM_INT);
            if ($visible !== 0 && $visible !== 1) {
                throw new \invalid_parameter_exception('visible must be 0 or 1');
            }
            $updates['visible'] = $visible;
        }
        if (isset($params['custom_params']) && is_array($params['custom_params'])) {
            $updates['custom_params'] = [];
            foreach ($params['custom_params'] as $k => $v) {
                $cleankey = validate_param((string) $k, PARAM_ALPHANUMEXT);
                $updates['custom_params'][$cleankey] = clean_param((string) $v, PARAM_TEXT);
            }
        }
        return (new lti_manager())->update($cmid, $updates);
    }

    /**
     * Delete an LTI activity by course module ID.
     *
     * @param array $params Must contain 'cmid'.
     * @return array Success indicator.
     * @throws \invalid_parameter_exception If cmid is missing.
     */
    private function delete_lti_activity(array $params): array {
        if (empty($params['cmid'])) {
            throw new \invalid_parameter_exception('Missing required param: cmid');
        }
        $cmid = validate_param($params['cmid'], PARAM_INT);
        if ($cmid <= 0) {
            throw new \invalid_parameter_exception('cmid must be a positive integer');
        }
        return (new lti_manager())->delete($cmid);
    }

    /**
     * Update a course section's name, visibility, or summary.
     *
     * @param array $params Must contain 'sectionid'. Optional: 'name', 'visible', 'summary'.
     * @return array Updated section data.
     * @throws \invalid_parameter_exception If sectionid is missing or no updatable fields provided.
     */
    private function update_section(array $params): array {
        global $DB;
        if (empty($params['sectionid'])) {
            throw new \invalid_parameter_exception('Missing required param: sectionid');
        }
        $sectionid = validate_param($params['sectionid'], PARAM_INT);
        if ($sectionid <= 0) {
            throw new \invalid_parameter_exception('sectionid must be a positive integer');
        }
        $updates = [];
        if (isset($params['name'])) {
            $updates['name'] = clean_param($params['name'], PARAM_TEXT);
        }
        if (isset($params['visible'])) {
            $visible = validate_param($params['visible'], PARAM_INT);
            if ($visible !== 0 && $visible !== 1) {
                throw new \invalid_parameter_exception('visible must be 0 or 1');
            }
            $updates['visible'] = $visible;
        }
        if (isset($params['summary'])) {
            $updates['summary'] = clean_param($params['summary'], PARAM_CLEANHTML);
        }
        if (empty($updates)) {
            throw new \invalid_parameter_exception('No updatable fields provided (name, visible, summary)');
        }
        $record  = $DB->get_record('course_sections', ['id' => $sectionid], '*', MUST_EXIST);
        $course  = get_course($record->course);
        course_update_section($course, $record, $updates);

        // Re-fetch to return current state.
        $record = $DB->get_record('course_sections', ['id' => $sectionid], '*', MUST_EXIST);
        return [
            'id'      => (int) $record->id,
            'section' => (int) $record->section,
            'name'    => get_section_name($course, $record),
            'visible' => (int) $record->visible,
        ];
    }

    /**
     * Delete a course section.
     *
     * @param array $params Must contain 'sectionid'. Optional: 'force' to delete non-empty sections.
     * @return array Success indicator.
     * @throws \invalid_parameter_exception If sectionid is missing.
     * @throws \moodle_exception If the section cannot be deleted.
     */
    private function delete_section(array $params): array {
        global $DB;
        if (empty($params['sectionid'])) {
            throw new \invalid_parameter_exception('Missing required param: sectionid');
        }
        $sectionid = validate_param($params['sectionid'], PARAM_INT);
        if ($sectionid <= 0) {
            throw new \invalid_parameter_exception('sectionid must be a positive integer');
        }
        $record      = $DB->get_record('course_sections', ['id' => $sectionid], '*', MUST_EXIST);
        $course      = get_course($record->course);
        $modinfo     = get_fast_modinfo($course);
        $sectioninfo = $modinfo->get_section_info_by_id($sectionid, MUST_EXIST);
        $force       = !empty($params['force']);

        if (!course_delete_section($course, $sectioninfo, $force)) {
            throw new \moodle_exception('cannotdeletesection', 'local_mathgpt');
        }
        return ['success' => true];
    }

    /**
     * Create a new section in a course.
     *
     * @param array $params Must contain 'courseid'. Optional: 'position', 'name'.
     * @return array Created section data.
     * @throws \invalid_parameter_exception If courseid is missing.
     */
    private function create_section(array $params): array {
        if (empty($params['courseid'])) {
            throw new \invalid_parameter_exception('Missing required param: courseid');
        }
        $courseid = validate_param($params['courseid'], PARAM_INT);
        if ($courseid <= 0) {
            throw new \invalid_parameter_exception('courseid must be a positive integer');
        }
        $course   = get_course($courseid);
        $position = 0;
        if (isset($params['position'])) {
            $position = validate_param($params['position'], PARAM_INT);
            if ($position < 0) {
                throw new \invalid_parameter_exception('position must be a non-negative integer');
            }
        }
        $section  = course_create_section($course, $position);

        if (!empty($params['name'])) {
            $name = clean_param($params['name'], PARAM_TEXT);
            if ($name !== '') {
                course_update_section($course, $section, ['name' => $name]);
                $section->name = $name;
            }
        }

        return [
            'id'      => (int) $section->id,
            'section' => (int) $section->section,
            'name'    => get_section_name($course, $section),
        ];
    }
}
