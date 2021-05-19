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

    static function update(int $course_id, string $task, string $status = '') {
        if ($course_id < 1) {
            return false;
        }
        if ($task == 'reset') {
            return action_handler::purge($course_id);
        }
        if ($task == 'delete' && $status == '') {
            $status = 'scheduled';
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
    static public function old_getmaillist(array $scheduled, array $relevantarchetypes = []) {
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
    
    /**
     * @param array $scheduled Array of ALL scheduled actions.
     * @param array $relevantarchetypes Array of archetypes to select.
     * @return array Array of user ids => Array of course ids
     */
    static public function getmaillist(array $scheduled) {
        // Could this be done using capabilities?
        $owners = [];
        foreach ($scheduled as $action) {
            // TODO Issue is only finds one owner when there might be more.
            $findowners = find_owners($action->course_id);
            if (empty($findowners)) {
                continue;
            }
            foreach($findowners as $owner) {
                // This preserves all enrolments whilst keeping the course_id
                //  as an array key so we can easily use that in the templates.
                $owners[$owner->userid][$action->course_id][] = $owner;
            }
        }
        return $owners;
    }

    static function send_schedulednotification(object $user, array $courseids) {
        global $OUTPUT;
        $courses = [];
        // Prepare course information for template.
        foreach ($courseids as $cid) {
            $course = get_course_metric($cid);
            $course->course_url = new \moodle_url('/course/view.php', ['id' => $course->course_id]);
            $courses[] = $course;
        }
        $messagebody = $OUTPUT->render_from_template(
            'tool_coursewrangler/scheduled_notification', 
            [
                'courses' => $courses,
                'user_table_url' => new \moodle_url('/admin/tool/coursewrangler/user_table.php')
            ]
        );
        $message = new \core\message\message();
        $message->courseid = SITEID;
        $message->component = 'tool_coursewrangler';
        $message->name = 'schedulednotification';
        $message->userfrom = \core_user::get_noreply_user();
        $message->userto = $user;
        $message->subject = get_string('message_deletesubject', 'tool_coursewrangler');
        $message->fullmessage = $messagebody;
        $message->fullmessageformat = FORMAT_MARKDOWN;
        $message->fullmessagehtml = $message->fullmessage;
        $message->smallmessage = html_to_text($messagebody);
        // Because this is a notification generated from Moodle, not a user-to-user message:
        $message->notification = 1;
        // A relevant URL for the notification.
        $message->contexturl = (new \moodle_url('/admin/tool/coursewrangler/user_table.php'))->out(false);
        // Link title explaining where users get to for the contexturl.
        $message->contexturlname = get_string('message_contexturlname', 'tool_coursewrangler');
        $messageid = message_send($message);
        return $messageid;
    }

    static function notify_owners(array $mailinglist) {
        foreach ($mailinglist as $userid => $owner) {
            $user = \core_user::get_user($userid);
            $courseids = array_keys($owner);
            action_handler::send_schedulednotification($user, $courseids);
        }
    }
}
