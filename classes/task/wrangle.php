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

use function tool_coursewrangler\get_course_metric as get_course_metric;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/admin/tool/coursewrangler/locallib.php');
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
        mtrace("Starting " . $this->get_name());
        // Working out the amount of time that should 
        //  pass before running each phase.
        $scheduledduration = time() - get_config('tool_coursewrangler', 'scheduledduration');
        $notifyduration = time() - get_config('tool_coursewrangler', 'notifyduration');
        $hiddenduration = time() - get_config('tool_coursewrangler', 'hiddenduration');
        $waitingduration = time() - get_config('tool_coursewrangler', 'waitingduration');

        // This will select scheduled actions that have 
        //  been added after a certain period of time.
        $scheduled_actions = $DB->get_records_sql(
            'SELECT * FROM {tool_coursewrangler_action} 
                WHERE action="delete" AND status="scheduled" 
                AND lastupdated < :lastupdated ;', 
            ['lastupdated' => $scheduledduration]
        );
        $notified_actions = $DB->get_records_sql(
            'SELECT * FROM {tool_coursewrangler_action} 
                WHERE action="delete" AND status="notified" 
                AND lastupdated < :lastupdated ;', 
            ['lastupdated' => $notifyduration]
        );
        $hidden_actions = $DB->get_records_sql(
            'SELECT * FROM {tool_coursewrangler_action} 
                WHERE action="delete" AND status="hidden" 
                AND lastupdated < :lastupdated ;', 
            ['lastupdated' => $hiddenduration]
        );
        $waiting_actions = $DB->get_records_sql(
            'SELECT * FROM {tool_coursewrangler_action} 
                WHERE action="delete" AND status="waiting" 
                AND lastupdated < :lastupdated ;', 
            ['lastupdated' => $waitingduration]
        );
        
        mtrace("Starting 'Schedule' Task.");
        /**
         * The first step of Wrangle is to workout who do we notify.
         * 
         * Some of the extra steps should be:
         *  - If setting to protect child courses is enabled, 
         *     skip parent course deletion?
         *  - If end date is set, and not yet over, protect it? (setting).
         *  - If course is not visible, do not notify? (setting).
         */
        $childprotection = get_config('tool_coursewrangler', 'childprotection') ?? false;
        $enddateprotection = get_config('tool_coursewrangler', 'enddateprotection') ?? false;
        $donotnotifyhidden = get_config('tool_coursewrangler', 'donotnotifyhidden') ?? false;
        $scheduled_mailinglist = [];
        $notifymode = get_config('tool_coursewrangler', 'notifymode') ?? false;
        $scheduled_actions_notify = [];
        // Processing the scheduled tasks.
        foreach ($scheduled_actions as $scheduled) {
            mtrace("Processing scheduled action for course id $scheduled->course_id:");
            // Here we must do all the extra checks.
            $metrics = get_course_metric($scheduled->course_id);

            $isparent = false;
            $isrunning = false;
            $ishidden = false;

            $isparent = $metrics->course_children != null;
            $isrunning = $metrics->course_enddate > time();
            $ishidden = $metrics->course_visible == 0;

            if ($childprotection && $isparent) {
                mtrace("Course $metrics->course_id is protected because is parent, skipped.");
                continue;                
            }
            if ($enddateprotection && $isrunning) {
                mtrace("Course $metrics->course_id is protected because is not over, skipped.");
                continue;
            }
            if (!$donotnotifyhidden && !$ishidden) {
                $scheduled_actions_notify[$scheduled->course_id] = $scheduled;
            }
            action_handler::update($scheduled->course_id, 'delete', 'notified');
        }
        if ($notifymode) {
            // Preparing mailing list.
            mtrace("Assembling mailing list.");
            $scheduled_mailinglist = action_handler::getmaillist($scheduled_actions_notify);
            mtrace("Emailing course managers and teachers for new scheduled tasks.");
            action_handler::notify_owners($scheduled_mailinglist);
        }
        // Processing the notified tasks.
        foreach ($notified_actions as $notified) {
            mtrace("Processing notified action for course id $notified->course_id:");
            $action = new action($notified->id);
            $action->hide_course();
            action_handler::update($notified->course_id, 'delete', 'hidden');
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