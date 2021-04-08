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
    function __construct(?stdClass $action = null) {
        if ($action === null) {
            return;
        }
        foreach ($action as $attribute => $value) {
            $this->$attribute = $value;
        }
    }

    static function schedule(int $course_id, string $task = 'delete') {
        if ($course_id < 1) {
            return false;
        }
        $exists = action::find_action($course_id);
        $action = new stdClass();
        $action->course_id = $course_id;
        $action->action = $task;
        $action->status = 'scheduled';
        $action->lastupdated = time();
        global $DB;
        if ($exists != false) {
            // If exists, overwrite.
            $action->id = $exists->id;
            return $DB->update_record('tool_coursewrangler_action', $action);
        }
        return $DB->insert_record('tool_coursewrangler_action', $action);
    }

    static function purge(int $course_id) {
        if ($course_id < 1) {
            return false;
        }
        global $DB;
        $action = $DB->get_record('tool_coursewrangler_action', ['course_id' => $course_id], 'id', IGNORE_MISSING);
        if ($action == true) {
            return $DB->delete_records('tool_coursewrangler_action', ['id' => $action->id]);
        }
        return null;
    }

    static public function getmaillist($scheduled) {
        global $DB;
        /**
         * In the format:
         * [ USERID => [COURSEID_1, COURSEID_2] ]
         */
        $responsibleuserids = [];
        // This is bad, redo all.
        foreach ($scheduled as $action) {
            echo "\n". $action->course_id;
            $enrol = $DB->get_records_sql("SELECT * FROM {enrol} AS e WHERE e.courseid=:id AND e.status=0;", ['id' => $action->course_id]);
            // Then foreach result, depending on type of enrol (e.enrol), store that information
            // also remember to ignore students and maybe other archetypes
            // Comma separated achetypes.
            $archetypes = 'student';
            $users = [];
            foreach ($enrol as $enrol_instance) {
                $users[] = $DB->get_records_sql(
                    "SELECT ue.id AS ue_id, 
                        ue.userid AS userid, 
                        r.archetype AS role_type, 
                        ue.status AS enrol_status,
                        e.enrol AS enrol_type 
                    FROM {user_enrolments} AS ue
                    JOIN {enrol} AS e  ON ue.enrolid=e.id
                    JOIN {role} AS r ON e.roleid=r.id
                    WHERE ue.enrolid=:enrolid;",
                    ['enrolid' => $enrol_instance->id]
                );
            }
            foreach($users as $user) {
                print_r($users);
                $responsibleuserids[$user->userid][] = $action->course_id;
            }
        }
    }
}
