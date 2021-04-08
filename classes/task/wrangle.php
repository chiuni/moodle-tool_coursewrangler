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
 * This file is a ...
 * 
 * @package   tool_coursewrangler
 * @author    Hugo Soares <h.soares@chi.ac.uk>
 * @copyright 2020 University of Chichester {@link www.chi.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace tool_coursewrangler\task;

use tool_coursewrangler\action;

defined('MOODLE_INTERNAL') || die();

class wrangle extends \core\task\scheduled_task {

    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name() {
        return get_string('task_wrangle', 'tool_coursewrangler');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        global $DB;
        mtrace("Starting tool_coursewrangler wrangle task");
        $scheduledduration = time() - get_config('tool_coursewrangler', 'scheduledduration');
        $emailedduration = time() - get_config('tool_coursewrangler', 'emailedduration');
        $hiddenduration = time() - get_config('tool_coursewrangler', 'hiddenduration');
        $waitingduration = time() - get_config('tool_coursewrangler', 'waitingduration');

        $scheduled_actions = $DB->get_records_sql('SELECT * FROM {tool_coursewrangler_action} WHERE action="delete" AND status="scheduled" AND lastupdated < :lastupdated ;', ['lastupdated' => $scheduledduration]);
        $emailed_actions = $DB->get_records('SELECT * FROM {tool_coursewrangler_action} WHERE action="delete" AND status="emailed" AND lastupdated < :lastupdated ;', ['lastupdated' => $emailedduration]);
        $hidden_actions = $DB->get_records('SELECT * FROM {tool_coursewrangler_action} WHERE action="delete" AND status="hidden" AND lastupdated < :lastupdated ;', ['lastupdated' => $hiddenduration]);
        $waiting_actions = $DB->get_records('SELECT * FROM {tool_coursewrangler_action} WHERE action="delete" AND status="waiting" AND lastupdated < :lastupdated ;', ['lastupdated' => $waitingduration]);
        foreach ($scheduled_actions as $scheduled) {
            mtrace("Processing scheduled action for course id $scheduled->course_id:");

        }
        foreach ($emailed_actions as $emailed) {
            mtrace("Action: " . $emailed->course_id);
        }
        foreach ($hidden_actions as $hidden) {
            mtrace("Action: " . $hidden->course_id);
        }
        foreach ($waiting_actions as $waiting) {
            mtrace("Action: " . $waiting->course_id);
        }
        // foreach ($waiting_actions as $action) {
        //     mtrace("Processing waiting action for $action->id:");
        //     mtrace("Deleting course $action->course_id:");
        //     $action_object = new action($action->id);
        //     $delete_status = $action_object->delete_course();
        //     if ($delete_status) {
        //         // Log in database the success?
        //         mtrace("Delete successfull.");
        //     } else {
        //         // Log in database the failure.
        //     }
        // }
        // foreach($hidden_actions as $action) {
        //     mtrace("Processing hidden action for $action->id:");
        //     mtrace("Switching to waiting...");
        //     $action_object = new action($action->id);
        //     $wait_status = $action_object->wait();
        //     if ($wait_status) {
        //         // Log in database the success?
        //         mtrace("Delete successfull.");
        //     } else {
        //         // Log in database the failure.
        //     }
        // }
        mtrace("Finished tool_coursewrangler wrangle task");
    }
}