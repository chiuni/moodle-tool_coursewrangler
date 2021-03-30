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
    function __construct(int $report_id) {
        if ($report_id < 1) {
            return null;
        }
        global $DB;
        $this->report =$DB->get_record('tool_coursewrangler_report', ['id' => $report_id], '*', MUST_EXIST);
        if ($this->report === false) {
            return null;
        }
        $this->report_id = $report_id;
        $this->actions = $DB->get_records('tool_coursewrangler_action', ['report_id' => $this->report_id]);
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
        $action = $DB->get_record('tool_coursewrangler_action', ['course_id' => $course_id, 'report_id' => $this->report_id], '*');
        if ($action == false) {
            $action = null;
            $action = new stdClass();
            $action->report_id = $this->report_id;
            $action->course_id = $course_id;
            $coursemt = $DB->get_record('tool_coursewrangler_coursemt', ['course_id' => $course_id, 'report_id' => $this->report_id], '*', MUST_EXIST);
            $action->coursemt_id = $coursemt->id;
            $action->action = 'delete';
            $action->status = 'scheduled';
            $new_action_id = $DB->insert_record('tool_coursewrangler_action', $action);
            return $new_action_id;
        }
        $action->action = 'delete';
        $action->status = 'scheduled';
        $upated_action = $DB->update_record('tool_coursewrangler_action', $action);
        return $upated_action;
    }
    public function hard_reset(int $course_id) {
        if ($course_id < 1) {
            return false;
        }
        global $DB;
        $action = $DB->get_record('tool_coursewrangler_action', ['course_id' => $course_id, 'report_id' => $this->report_id], '*');
        if ($action == true) {
            $DB->delete_records('tool_coursewrangler_action', ['id' => $action->id]);
        }
    }
}
