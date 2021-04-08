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
 * This file is a class example.
 * 
 * @package   tool_coursewrangler
 * @author    Hugo Soares <h.soares@chi.ac.uk>
 * @copyright 2020 University of Chichester {@link www.chi.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// More Info: https://docs.moodle.org/dev/Coding_style#Namespaces
namespace tool_coursewrangler;

use stdClass;

class action_handler
{
    function __construct() {
        global $DB;
        $this->actions = $DB->get_records('tool_coursewrangler_action', []);
    }

    public function schedule_delete(int $course_id) {
        if ($course_id < 1) {
            return false;
        }
        global $DB;
        // It does not actually matter if course is already deleted, since we are only scheduling.
        // $course = $DB->get_record('courses', ['id' => $course_id], '*', MUST_EXIST);
        // if ($course === false) {
        //     // Course was not found.
        //     return false;
        // }
        $action = action::find_action($course_id);
        if ($action == false) {
            $action = null;
            $action = new stdClass();
            $action->course_id = $course_id;
            $metrics = $DB->get_record('tool_coursewrangler_metrics', ['course_id' => $course_id], '*', MUST_EXIST);
            $action->metrics_id = $metrics->id;
            $action->action = 'delete';
            $action->status = 'scheduled';
            $action->lastupdated = time();
            $new_action_id = $DB->insert_record('tool_coursewrangler_action', $action);
            return $new_action_id;
        }
        $action->action = 'delete';
        $action->status = 'scheduled';
        $action->updated = time();
        $upated_action = $DB->update_record('tool_coursewrangler_action', $action);
        return $upated_action;
    }
    public function hard_reset(int $course_id) {
        if ($course_id < 1) {
            return false;
        }
        global $DB;
        $action = $DB->get_record('tool_coursewrangler_action', ['course_id' => $course_id], '*');
        if ($action == true) {
            $DB->delete_records('tool_coursewrangler_action', ['id' => $action->id]);
        }
    }
}
