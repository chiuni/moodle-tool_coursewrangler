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
use tool_coursewrangler\action_handler;

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
        mtrace("Starting tool_coursewrangler Wrangle task");
        $scheduledduration = time() - get_config('tool_coursewrangler', 'scheduledduration');
        $emailedduration = time() - get_config('tool_coursewrangler', 'emailedduration');
        $hiddenduration = time() - get_config('tool_coursewrangler', 'hiddenduration');
        $waitingduration = time() - get_config('tool_coursewrangler', 'waitingduration');

        $scheduled_actions = $DB->get_records_sql('SELECT * FROM {tool_coursewrangler_action} WHERE action="delete" AND status="scheduled" AND lastupdated < :lastupdated ;', ['lastupdated' => $scheduledduration]);
        $emailed_actions = $DB->get_records_sql('SELECT * FROM {tool_coursewrangler_action} WHERE action="delete" AND status="emailed" AND lastupdated < :lastupdated ;', ['lastupdated' => $emailedduration]);
        $hidden_actions = $DB->get_records_sql('SELECT * FROM {tool_coursewrangler_action} WHERE action="delete" AND status="hidden" AND lastupdated < :lastupdated ;', ['lastupdated' => $hiddenduration]);
        $waiting_actions = $DB->get_records_sql('SELECT * FROM {tool_coursewrangler_action} WHERE action="delete" AND status="waiting" AND lastupdated < :lastupdated ;', ['lastupdated' => $waitingduration]);
        
        mtrace("Starting 'Schedule' Task.");
        $scheduled_mailinglist = [];
        $emailmode = get_config('tool_coursewrangler', 'emailmode') ?? false;
        if ($emailmode) {
            mtrace("Assembling mailing list.");
            $scheduled_mailinglist = action_handler::getmaillist($scheduled_actions);
            mtrace("Emailing course managers and teachers for new scheduled tasks.");
            action_handler::email($scheduled_mailinglist);
        }
        foreach ($scheduled_actions as $scheduled) {
            mtrace("Processing scheduled action for course id $scheduled->course_id:");
            action_handler::update($scheduled->course_id, 'delete', 'emailed');
        }
        foreach ($emailed_actions as $emailed) {
            mtrace("Processing emailed action for course id $emailed->course_id:");
            $action = new action($emailed->id);
            $action->hide_course();
            action_handler::update($emailed->course_id, 'delete', 'hidden');
        }
        foreach ($hidden_actions as $hidden) {
            mtrace("Processing hidden action for course id $hidden->course_id:");
            action_handler::update($hidden->course_id, 'delete', 'waiting');
        }
        foreach ($waiting_actions as $waiting) {
            mtrace("Processing waiting action for course id $waiting->course_id:");
            mtrace("ATTENTION! Deleting course $waiting->course_id.");
            continue;
            $action_object = new action($waiting->id);
            $delete_status = $action_object->delete_course();
            if ($delete_status) {
                // Log in database the success?
                // $DB->insert_record_sql();
                mtrace("Delete successfull.");
            } else {
                // Log in database the failure.
            }
        }
        mtrace("Finished tool_coursewrangler wrangle task");
    }
}