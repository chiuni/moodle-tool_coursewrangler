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
 * This file is the Wrangle task that resolves courses to delete.
 * @package   tool_coursewrangler
 * @author    Hugo Soares <h.soares@chi.ac.uk>
 * @copyright 2020 University of Chichester {@link www.chi.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace tool_coursewrangler\task;

use moodle_url;
use tool_coursewrangler\action;
use tool_coursewrangler\action_handler;

use function tool_coursewrangler\get_course_metric as get_course_metric;
use function tool_coursewrangler\insert_cw_logentry as insert_cw_logentry;

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
        // pass before running each phase.
        $scheduledduration =
            time() - get_config('tool_coursewrangler', 'scheduledduration');
        $notifyduration =
            time() - get_config('tool_coursewrangler', 'notifyduration');
        $hiddenduration =
            time() - get_config('tool_coursewrangler', 'hiddenduration');
        $waitingduration =
            time() - get_config('tool_coursewrangler', 'waitingduration');

        // This will select scheduled actions that have
        // been added after a certain period of time.
        $scheduledactions = $DB->get_records_sql(
            'SELECT * FROM {tool_coursewrangler_action}
                WHERE action="delete" AND status="scheduled"
                AND lastupdated < :lastupdated ;',
            ['lastupdated' => $scheduledduration]
        );
        $notifiedactions = $DB->get_records_sql(
            'SELECT * FROM {tool_coursewrangler_action}
                WHERE action="delete" AND status="notified"
                AND lastupdated < :lastupdated ;',
            ['lastupdated' => $notifyduration]
        );
        $hiddenactions = $DB->get_records_sql(
            'SELECT * FROM {tool_coursewrangler_action}
                WHERE action="delete" AND status="hidden"
                AND lastupdated < :lastupdated ;',
            ['lastupdated' => $hiddenduration]
        );
        $waitingactions = $DB->get_records_sql(
            'SELECT * FROM {tool_coursewrangler_action}
                WHERE action="delete" AND status="waiting"
                AND lastupdated < :lastupdated ;',
            ['lastupdated' => $waitingduration]
        );
        mtrace("Starting 'Schedule' Task.");

        // The first step of Wrangle is to workout who do we notify.
        // Some of the extra steps should be:
        // - If setting to protect child courses is enabled,
        // skip parent course deletion?
        // - If end date is set, and not yet over, protect it? (setting).
        // - If course is not visible, do not notify? (setting).

        $childprotection =
            get_config('tool_coursewrangler', 'childprotection') ?? false;
        $enddateprotection =
            get_config('tool_coursewrangler', 'enddateprotection') ?? false;
        $donotnotifyhidden =
            get_config('tool_coursewrangler', 'donotnotifyhidden') ?? false;
        $notifysiteadmins =
            get_config('tool_coursewrangler', 'notifysiteadmins') ?? false;
        $scheduledmailinglist = [];
        $notifymode =
            get_config('tool_coursewrangler', 'notifymode') ?? false;
        $scheduledactionsnotify = [];
        // Processing the scheduled tasks.
        foreach ($scheduledactions as $scheduled) {
            mtrace(
                "Processing scheduled action
                 for course id $scheduled->courseid:"
            );
            // Here we must do all the extra checks.
            $metrics = get_course_metric($scheduled->courseid);

            $isparent = $metrics->coursechildren != null ?? false;
            $isrunning = $metrics->courseenddate > time() ?? false;
            $ishidden = $metrics->coursevisible == 0 ?? false;

            if ($childprotection && $isparent) {
                mtrace(
                    "Course $metrics->courseid is
                     protected because is parent, skipped."
                );
                continue;
            }
            if ($enddateprotection && $isrunning) {
                mtrace(
                    "Course $metrics->courseid is protected
                     because is not over, skipped."
                    );
                continue;
            }
            if ($donotnotifyhidden && $ishidden) {
                mtrace(
                    "Not sending course notification
                     because is already hidden."
                    );
            } else {
                $scheduledactionsnotify[$scheduled->courseid] = $scheduled;
            }
            action_handler::update($scheduled->courseid, 'delete', 'notified');
        }
        if ($notifymode) {
            // Preparing mailing list.
            mtrace("Assembling mailing list.");
            $scheduledmailinglist =
                action_handler::getmaillist($scheduledactionsnotify);
            mtrace(
                "Emailing course managers and teachers
                 for new scheduled tasks."
                );
            action_handler::notify_owners($scheduledmailinglist);
            mtrace("Done notifying owners.");
        }
        // Processing the notified tasks.
        foreach ($notifiedactions as $notified) {
            mtrace(
                "Processing notified action for
                 course id $notified->courseid:"
            );
            $action = new action($notified->id);
            $action->hide_course($notified->courseid);
            action_handler::update($notified->courseid, 'delete', 'hidden');
        }
        foreach ($hiddenactions as $hidden) {
            mtrace(
                "Processing hidden action for
                 course id $hidden->courseid:"
            );
            action_handler::update($hidden->courseid, 'delete', 'waiting');
        }
        $deletestarttime = time();
        $maxdeletetime =
            get_config('tool_coursewrangler', 'maxdeleteexecutiontime') ?? 1800;
        foreach ($waitingactions as $waiting) {
            // We introduce this check to make sure we don't spend
            // than 30 minutes deleting courses.
            if (time() > ($deletestarttime + $maxdeletetime)) {
                break;
            }
            mtrace(
                "Processing waiting action for
                 course id $waiting->courseid:"
                );
            mtrace("ATTENTION! Deleting course $waiting->courseid.");
            $actionobject = new action($waiting->id);
            $deletestatus = $actionobject->delete_course();
            $logcourseidlink =
                new moodle_url('/course/view.php?id=' . $waiting->courseid);
            if ($deletestatus) {
                // Log in database the success?
                insert_cw_logentry(
                    "Course with ID: $waiting->courseid deleted successfully.",
                    'wrangle_task'
                );
                mtrace("Delete successful ID-$waiting->courseid.");
            } else {
                $metric =
                    $DB->get_record(
                        'tool_coursewrangler_metrics',
                        ['courseid' => $waiting->courseid]
                    );
                // Log in database the failure.
                $failedtodeletestring =
                    "Course: <a href=\"$logcourseidlink\">
                    $waiting->courseid</a>failed to delete.";
                insert_cw_logentry(
                    "$failedtodeletestring",
                    'wrangle_task',
                    $metric->id
                );
            }
        }
        mtrace("Finished tool_coursewrangler wrangle task");
    }
}