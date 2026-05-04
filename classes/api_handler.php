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

global $CFG;
require_once($CFG->dirroot . '/course/modlib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/local/mathgpt/classes/lti_manager.php');

class api_handler {

    public function dispatch(string $function, array $params): array {
        require_capability('local/mathgpt:useapi', \context_system::instance());

        switch ($function) {
            case 'get_courses':
                return $this->get_courses();
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

    private function get_courses(): array {
        global $USER;
        $courses = enrol_get_users_courses($USER->id, false, 'id,fullname,shortname,visible', 'fullname ASC');
        $result  = [];
        foreach ($courses as $course) {
            $result[] = [
                'id'        => (int)$course->id,
                'fullname'  => $course->fullname,
                'shortname' => $course->shortname,
                'visible'   => (int)$course->visible,
            ];
        }
        return $result;
    }

    private function get_course_contents(array $params): array {
        if (empty($params['courseid'])) {
            throw new \invalid_parameter_exception('Missing required param: courseid');
        }
        $course  = get_course((int)$params['courseid']); // throws dml_missing_record_exception if absent
        $modinfo = get_fast_modinfo($course);
        $result  = [];

        foreach ($modinfo->get_section_info_all() as $sectioninfo) {
            $modules = [];
            foreach ($modinfo->sections[$sectioninfo->section] ?? [] as $cmid) {
                $cm        = $modinfo->cms[$cmid];
                $modules[] = [
                    'id'      => (int)$cm->id,
                    'modname' => $cm->modname,
                    'name'    => $cm->name,
                    'visible' => (int)$cm->visible,
                ];
            }
            $result[] = [
                'id'      => (int)$sectioninfo->id,
                'name'    => get_section_name($course, $sectioninfo),
                'modules' => $modules,
            ];
        }
        return $result;
    }

    private function create_lti_activity(array $params): array {
        foreach (['courseid', 'sectionnum', 'name'] as $field) {
            if (!isset($params[$field]) || $params[$field] === '') {
                throw new \invalid_parameter_exception("Missing required param: {$field}");
            }
        }
        $custom_params = isset($params['custom_params']) && is_array($params['custom_params'])
            ? $params['custom_params']
            : [];
        return (new lti_manager())->create(
            (int)    $params['courseid'],
            (int)    $params['sectionnum'],
            (string) $params['name'],
            $custom_params
        );
    }

    private function update_lti_activity(array $params): array {
        if (empty($params['cmid'])) {
            throw new \invalid_parameter_exception('Missing required param: cmid');
        }
        $updates = array_intersect_key($params, array_flip(['name', 'visible']));
        if (isset($params['custom_params']) && is_array($params['custom_params'])) {
            $updates['custom_params'] = $params['custom_params'];
        }
        return (new lti_manager())->update((int) $params['cmid'], $updates);
    }

    private function delete_lti_activity(array $params): array {
        if (empty($params['cmid'])) {
            throw new \invalid_parameter_exception('Missing required param: cmid');
        }
        return (new lti_manager())->delete((int) $params['cmid']);
    }

    private function update_section(array $params): array {
        global $DB;
        if (empty($params['sectionid'])) {
            throw new \invalid_parameter_exception('Missing required param: sectionid');
        }
        $record  = $DB->get_record('course_sections', ['id' => (int) $params['sectionid']], '*', MUST_EXIST);
        $course  = get_course($record->course);
        $updates = array_intersect_key($params, array_flip(['name', 'visible', 'summary']));
        if (empty($updates)) {
            throw new \invalid_parameter_exception('No updatable fields provided (name, visible, summary)');
        }
        course_update_section($course, $record, $updates);

        // Re-fetch to return current state.
        $record = $DB->get_record('course_sections', ['id' => (int) $params['sectionid']], '*', MUST_EXIST);
        return [
            'id'      => (int) $record->id,
            'section' => (int) $record->section,
            'name'    => get_section_name($course, $record),
            'visible' => (int) $record->visible,
        ];
    }

    private function delete_section(array $params): array {
        global $DB;
        if (empty($params['sectionid'])) {
            throw new \invalid_parameter_exception('Missing required param: sectionid');
        }
        $record      = $DB->get_record('course_sections', ['id' => (int) $params['sectionid']], '*', MUST_EXIST);
        $course      = get_course($record->course);
        $modinfo     = get_fast_modinfo($course);
        $sectioninfo = $modinfo->get_section_info_by_id((int) $params['sectionid'], MUST_EXIST);
        $force       = !empty($params['force']);

        if (!course_delete_section($course, $sectioninfo, $force)) {
            throw new \moodle_exception('cannotdeletesection', 'error', '', null,
                'Section has content — pass force=true to delete anyway');
        }
        return ['success' => true];
    }

    private function create_section(array $params): array {
        if (empty($params['courseid'])) {
            throw new \invalid_parameter_exception('Missing required param: courseid');
        }
        $course   = get_course((int) $params['courseid']);
        $position = isset($params['position']) ? (int) $params['position'] : 0;
        $section  = course_create_section($course, $position);

        if (!empty($params['name'])) {
            course_update_section($course, $section, ['name' => (string) $params['name']]);
            $section->name = (string) $params['name'];
        }

        return [
            'id'      => (int) $section->id,
            'section' => (int) $section->section,
            'name'    => get_section_name($course, $section),
        ];
    }
}
