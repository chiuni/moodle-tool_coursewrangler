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
 * This file is an Action class for managing courses.
 *
 * @package   tool_coursewrangler
 * @author    Hugo Soares <h.soares@chi.ac.uk>
 * @copyright 2020 University of Chichester {@link www.chi.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_coursewrangler;

require_once($CFG->dirroot . '/course/lib.php');

class action {
    public function __construct(int $id = 0) {
        if ($id < 1) {
            $this->id_not_valid = true;
            return null;
        }
        global $DB;
        $actionclass = $DB->get_record('tool_coursewrangler_action', ['id' => $id], '*', MUST_EXIST);
        if (!$actionclass) {
            $this->action_not_found = true;
            return false;
        }
        foreach ($actionclass as $key => $value) {
            $this->$key = $value;
        }

        return true;
    }

    public static function find_action(int $courseid) {
        if ($courseid < 1) {
            return null;
        }
        global $DB;
        $action = $DB->get_record('tool_coursewrangler_action', ['courseid' => $courseid], '*');
        if ($action == false) {
            return false;
        }
        return new action($action->id);
    }

    public function delete_course() {
        if (!isset($this->courseid) || !is_integer((int) $this->courseid) || $this->courseid < 1) {
            return false;
        }
        // Double check course exits:
        $course = get_course($this->courseid, false);
        if ($course == false) {
            return false;
        }
        \core_php_time_limit::raise();
        // We do this here because it spits out feedback as it goes.
        $deletestatus = delete_course($this->courseid);
        if ($deletestatus) {
            global $DB;
            $DB->delete_record('tool_coursewrangler_action', ['courseid' => $this->courseid]);
            $DB->delete_record('tool_coursewrangler_metrics', ['courseid' => $this->courseid]);
            insert_cw_logentry("Course with ID: $this->courseid ", 'course_wrangler', $this->id);
        }
        return $deletestatus;
    }

    public function wait() {
        if ($this->status != 'hidden') {
            // Log here that it must be hidden before waiting.
            return false;
        }
        global $DB;
        $this->status = 'waiting';
        $DB->update_record('tool_coursewrangler_action', $this);
    }

    public static function hide_course(int $courseid) {
        global $DB;
        $course = $DB->get_record('course', ['id' => $courseid]);
        $metric = $DB->get_record('tool_coursewrangler_metrics', ['courseid' => $courseid]);
        $course->visible = 0;
        $metric->coursevisible = 0;
        $DB->update_record('tool_coursewrangler_metrics', $metric);
        return update_course($course);
    }
}