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

    static function update(int $course_id, string $task = 'delete', string $status = 'scheduled') {
        if ($course_id < 1) {
            return false;
        }
        $exists = action::find_action($course_id);
        $action = new stdClass();
        $action->course_id = $course_id;
        $action->action = $task;
        $action->status = $status;
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

    /**
     * @param array $scheduled Array of ALL scheduled actions.
     * @param array $relevantarchetypes Array of archetypes to select.
     * @return array Array of user ids => Array of course ids
     */
    static public function getmaillist(array $scheduled, array $relevantarchetypes = []) {
        // Could this be done using capabilities?
        $responsibleuserids = [];
        foreach ($scheduled as $action) {
            // Getting user roles for course by course ID.
            $coursecontext = \context_course::instance($action->course_id);
            $userroles = get_users_roles($coursecontext, [], false);
            
            // Validate archetypes.
            $allarchetypes = get_role_archetypes();
            $validarchetypes = $allarchetypes;
            if (!empty($relevantarchetypes)) {
                $validarchetypes = array_intersect($allarchetypes, $relevantarchetypes);
            }
            
            // Getting all roles and selecting based on archetype.
            $roles = get_all_roles($coursecontext);
            foreach ($roles as $key => $role) {
                if (!in_array($role->archetype, $validarchetypes)) {
                    unset($roles[$key]);
                }
            }
            $roles = array_keys($roles);
            foreach ($userroles as $userid => $enrolmentarray) {
                $roledata = reset($enrolmentarray);
                if (in_array($roledata->roleid, $roles)) {
                    $responsibleuserids[$userid][] = $action->course_id;
                }
            }
        }
        return $responsibleuserids;
    }

    static function email(array $mailinglist) {
        return $mailinglist;
    }
}
