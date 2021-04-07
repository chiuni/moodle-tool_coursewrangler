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

// More Info: https://docs.moodle.org/dev/Coding_style#Namespaces
namespace tool_coursewrangler;

class action {
    function __construct(int $id = 0) {
        if ($id < 1) {
            $this->id_not_valid = true;
            return null;
        }
        global $DB;
        $action_class = $DB->get_record('tool_coursewrangler_action', ['id' => $id], '*', MUST_EXIST);
        if (!$action_class) {
            $this->action_not_found = true;
            return false;
        }
        foreach ($action_class as $key => $value) {
            $this->$key = $value;
        }

        return true;
    }

    static function find_action(int $course_id) {
        if ($course_id < 1) {
            return null;
        }
        global $DB;
        $action = $DB->get_record('tool_coursewrangler_action', ['course_id' => $course_id], '*');
        if ($action == false) {
            return false;
        }
        return new action($action->id);
    }

    function delete_course() { 
        if (!isset($this->course_id) || !is_integer((int) $this->course_id) || $this->course_id < 1) {
            return false;
        }
        // Double check course exits:
        $course = get_course($this->course_id, false);
        if ($course == false) {
            return false;
        }
        \core_php_time_limit::raise();
        // We do this here because it spits out feedback as it goes.
        $delete_status = delete_course($this->course_id);
        if ($delete_status) {
            global $DB;
            $this->status = 'deleted';
            $this->lastupdated = time();
            $DB->update_record('tool_coursewrangler_action', $this);
        }
        return $delete_status;
    }

    function wait() {
        if ($this->status != 'hidden') {
            // Log here that it must be hidden before waiting.
            return false;
        }
        global $DB;
        $this->status = 'waiting';
        $DB->update_record('tool_coursewrangler_action', $this);
    }

    function hide_course() {
        if (!isset($this->course_id) || !is_integer((int) $this->course_id) || $this->course_id < 1) {
            return false;
        }
        global $DB;

    }
}