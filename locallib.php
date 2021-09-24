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
 * @package   tool_coursewrangler
 * @author    Hugo Soares <h.soares@chi.ac.uk>
 * @copyright 2020 University of Chichester {@link www.chi.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_coursewrangler;

use stdClass;

defined('MOODLE_INTERNAL') || die();
/**
 * @deprecated use find_relevant_coursedata_lite instead
 */
function find_relevant_coursedata() {
    global $DB, $CFG;
    $modules = $DB->get_records_sql("SELECT id, name FROM {modules};");
    $unionsegments = [];
    foreach ($modules as $module) {
        $unionsegments[] = " SELECT act.id, act.course, act.name, act.timemodified FROM " . '{' . $module->name . '}' . " AS act ";
    }
    $unionstatement = implode(" UNION ", $unionsegments);
    return $DB->get_records_sql(
        "SELECT c.id AS courseid,
                cm.id AS coursemoduleid,
                act.id AS activity_id,
                c.fullname AS coursefullname,
                c.shortname AS courseshortname,
                c.idnumber AS courseidnumber,
                c.startdate AS coursestartdate,
                c.enddate AS courseenddate,
                c.timecreated AS coursetimecreated,
                c.timemodified AS coursetimemodified,
                c.visible AS coursevisible,
                m.name AS activitytype,
                MAX(act.timemodified) AS activity_last_modified
        FROM    {course} AS c
            JOIN {course_modules} AS cm ON cm.course=c.id
            JOIN {modules} AS m ON cm.module=m.id
            JOIN (  $unionstatement ) AS act ON act.course=c.id AND act.id=cm.instance
            JOIN (  SELECT ula.id,
                            ula.userid,
                            ula.courseid,
                            ula.timeaccess
                    FROM    {user_lastaccess} AS ula
                        INNER JOIN (SELECT  courseid,
                                            MAX(timeaccess) AS timeid
                                    FROM    {user_lastaccess}
                                    WHERE   userid!=:guestid
                                    AND     userid NOT IN (:siteadminids)
                                    GROUP BY courseid) AS groupedula
                        ON ula.courseid = groupedula.courseid
                        AND ula.timeaccess = groupedula.timeid) AS ula
        WHERE c.id!=:siteid
        GROUP BY c.id;",
        [
            'guestid' => 1,
            'siteadminids' => $CFG->siteadmins,
            'siteid' => SITEID
        ]
    );
}

/**
 * Returns an array with all the course data which can be fed to the metrics table.
 * Consists of:
 *      find_course_activity_data()->    To get activity data, like the last time
 *                                          an activity was modified.
 *      find_course_last_access()->     Finds the last access to a course, ignores
 *                                          guests and site admins.
 *      find_meta_parents()->           Finds meta parents and enrolments.
 */
function find_relevant_coursedata_lite(array $options = []) {
    $coursequery = find_course_activity_data();
    $ulaquery = find_course_last_access();
    $metaquery = find_meta_parents();
    $coursechildren = [];
    $courseparents = [];
    foreach ($metaquery as $value) {
        $courseparents[$value->courseid][] = $value->parentcourseid;
    }
    foreach ($metaquery as $value) {
        $coursechildren[$value->parentcourseid][] = $value->courseid;
    }
    foreach ($coursequery as $key => $result) {
        $result->coursetimeaccess = $ulaquery[$result->courseid]->timeaccess ?? 0;
        $result->courseparents = null;
        $result->coursechildren = null;
        if (array_key_exists($result->courseid, $coursechildren)) {
            $result->coursechildren = implode(',', $coursechildren[$result->courseid]);
        }
        if (array_key_exists($result->courseid, $courseparents)) {
            $result->courseparents = implode(',', $courseparents[$result->courseid]);
        }
        $result->coursemodulescount = count_course_modules($result->courseid)->coursemodulescount ?? 0;
        $result->courselastenrolment = find_last_enrolment($result->courseid)->courselastenrolment ?? 0;
        $coursestudents = find_coursestudents($result->courseid);
        foreach ($coursestudents as $key => $value) {
            $result->$key = $value;
        }
    }
    // Options processing.
    // We must always have $minimumage specified, if missing defaults to settings.
    // This enables us to bypass the settings if needed for minimum age.
    $minimumage = isset($options['minimumage']) ? $options['minimumage'] : get_config('tool_coursewrangler', 'minimumage');
    foreach ($coursequery as $id => $course) {
        if ($course->coursestartdate < 1) {
            continue;
        }
        $courseage = time() - $course->coursestartdate;
        $newcourse = false;
        if ($minimumage !== null && $minimumage > 0) {
            $newcourse = $minimumage > $courseage;
        }
        if ($newcourse) {
            unset($coursequery[$id]);
        }
    }
    return $coursequery;
}

/**
 * Simple function that should find all meta enrolments and therefore help
 *  identify meta parents.
 */
function find_meta_parents() {
    global $DB;
    return $DB->get_records_sql("SELECT id, courseid, customint1 AS parentcourseid FROM {enrol} WHERE enrol = 'meta';");
}

/**
 * Simple query to find the latest enrolment in a course by course id.
 */
function find_last_enrolment(int $courseid) {
    if ($courseid < 1) {
        return null;
    }
    global $DB;
    return $DB->get_record_sql(
        "SELECT id, courseid AS courseid,
                MAX(timecreated) AS courselastenrolment
            FROM {enrol} WHERE courseid=:courseid;",
        ['courseid' => $courseid]);
}

/**
 * The idea is that for a given course, we can find all of the students.
 * By finding students, we can figure out if the course is still in use or not,
 *  since courses without a single student are likely to be no longer needed.
 * @param int $id The course ID.
 * @return array $coursestudents List of all students for a course.
 */
function find_coursestudents(int $id) {
    global $DB;
    // By using the archetype we can firmly establish if they are students or not.
    $archetype = 'student';
    $coursecontext = \context_course::instance($id);
    // Then foreach result, depending on type of enrol ({enrol}.enrol), store that information
    // also remember to check for students only, we do not want any other archetypes for now.
    $allstudents = [];
    $sql = "SELECT  concat({role_assignments}.id, '-', {enrol}.id) as id,
                    {user_enrolments}.userid AS userid,
                    {role}.shortname,
                    {role_assignments}.component,
                    {user_enrolments}.timestart,
                    {user_enrolments}.timecreated,
                    {user_enrolments}.timemodified,
                    {role}.archetype AS role_type,
                    {user_enrolments}.status AS enrol_status,
                    {enrol}.enrol AS enrol_type
            FROM {role_assignments}
        LEFT JOIN {user_enrolments} ON {role_assignments}.userid = {user_enrolments}.userid
        LEFT JOIN {role} ON {role_assignments}.roleid = {role}.id
        LEFT JOIN {context} ON {context}.id = {role_assignments}.contextid
        LEFT JOIN {enrol} ON {enrol}.courseid = {context}.instanceid AND {user_enrolments}.enrolid = {enrol}.id
        WHERE {role}.archetype=:archetype AND {enrol}.courseid = :courseid;";
    $students = $DB->get_records_sql($sql, ['courseid' => $id, 'archetype' => $archetype]);
    $allstudents[] = $students;
    // This might need review, but the idea is that we create a total enrolment
    // count, and other enrolment counts to help admins make decisions when
    // deleting courses.
    $coursestudents = new stdClass;
    $coursestudents->totalenrolcount = 0;
    $coursestudents->activeenrolcount = 0;
    $coursestudents->selfenrolcount = 0;
    $coursestudents->manualenrolcount = 0;
    $coursestudents->metaenrolcount = 0;
    $coursestudents->otherenrolcount = 0;
    $coursestudents->suspendedenrolcount = 0;
    foreach ($allstudents as $students) {
        $coursestudents->totalenrolcount += count($students) ?? 0;
        foreach ($students as $student) {
            // This switch checks if enrolment status is active.
            switch ($student->enrol_status) {
                case 0:
                    $coursestudents->activeenrolcount += 1;
                    break;
                default:
                    break;
            }
            switch ($student->enrol_type) {
                case 'self':
                    $coursestudents->selfenrolcount += 1;
                    break;
                case 'manual':
                    $coursestudents->manualenrolcount += 1;
                    break;
                case 'meta':
                    $coursestudents->metaenrolcount += 1;
                    break;
                default:
                    $coursestudents->otherenrolcount += 1;
                    break;
            }
        }
        $coursestudents->suspendedenrolcount += $coursestudents->totalenrolcount - $coursestudents->activeenrolcount ?? 0;
    }
    return $coursestudents;
}
/**
 * Simple function to count modules in a course by course id.
 */
function count_course_modules(int $courseid) {
    if ($courseid < 1) {
        return null;
    }
    global $DB;
    return $DB->get_record_sql(
        "SELECT COUNT(id) AS coursemodulescount FROM {course_modules} WHERE course=:courseid;",
        ['courseid' => $courseid]
    );
}
/**
 * Large query that fetches the core course data plus activity data regarding that course.
 * Might be worth ignoring the activity data here: how valuable is it?
 * I cannot remember why it would be beneficial to use $where, maybe for a single course?
 */
function find_course_activity_data(string $where = '') {
    global $DB;
    $modules = $DB->get_records_sql("SELECT id, name FROM {modules};");
    $unionsegments = [];
    foreach ($modules as $module) {
        $mname = '{' . $module->name . '}';
        $unionsegments[] = " SELECT $mname.id, $mname.course, $mname.name, $mname.timemodified FROM $mname $where";
    }
    $unionstatement = implode(" UNION ", $unionsegments);
    return $DB->get_records_sql(
        "SELECT c.id AS courseid,
                cm.id AS coursemoduleid,
                act.id AS activity_id,
                c.fullname AS coursefullname,
                c.shortname AS courseshortname,
                c.idnumber AS courseidnumber,
                c.startdate AS coursestartdate,
                c.enddate AS courseenddate,
                c.timecreated AS coursetimecreated,
                c.timemodified AS coursetimemodified,
                c.visible AS coursevisible,
                m.name AS activitytype,
                MAX(act.timemodified) AS activitylastmodified
        FROM    {course} AS c
            LEFT JOIN {course_modules} AS cm ON cm.course=c.id
            LEFT JOIN {modules} AS m ON cm.module=m.id
            LEFT JOIN ( $unionstatement ) AS act
        ON act.course=c.id
        AND act.id=cm.instance
        WHERE c.id!=:siteid
        GROUP BY c.id;",
        ['siteid' => SITEID]
    );
}
/**
 * Finds course last access from user_last_access table.
 */
function find_course_last_access() {
    global $DB, $CFG;
    return $DB->get_records_sql(
        "SELECT  courseid,
            MAX(timeaccess) AS timeaccess
            FROM    {user_lastaccess}
            WHERE   userid!=:guestid
            AND     userid NOT IN (:siteadminids)
            GROUP BY courseid;",
        [
            'guestid' => 1,
            'siteadminids' => $CFG->siteadmins
        ]
    );
}
/**
 * Useful function to transform moodletime to unixtimestamp.
 * Could this be done differently?
 */
function moodletime_to_unixtimestamp(array $timearray) {
    $timestring = $timearray['day'] . '-' . $timearray['month'] . '-' . $timearray['year'];
    return (strtotime($timestring) ?? 0);
}

function get_course_metric(int $courseid) {
    if ($courseid <= 0) {
        return;
    }
    global $DB;
    return $DB->get_record('tool_coursewrangler_metrics', ['courseid' => $courseid], '*', MUST_EXIST);
}
function get_enrolments(int $courseid, string $archetype) {
    if ($courseid < 1) {
        return false;
    }
    global $DB;
    $sql = "SELECT concat({role_assignments}.id, '-', {enrol}.id) as id,
            {user_enrolments}.userid,
            {role}.shortname,
            {role}.archetype,
            {role_assignments}.component,
            {enrol}.enrol,
            {user_enrolments}.timestart,
            {user_enrolments}.timecreated,
            {user_enrolments}.timemodified
        FROM {role_assignments}
       LEFT JOIN {user_enrolments} ON {role_assignments}.userid = {user_enrolments}.userid
       LEFT JOIN {role} ON {role_assignments}.roleid = {role}.id
       LEFT JOIN {context} ON {context}.id = {role_assignments}.contextid
       LEFT JOIN {enrol} ON {enrol}.courseid = {context}.instanceid AND {user_enrolments}.enrolid = {enrol}.id
       WHERE {enrol}.courseid = :courseid
       AND {role}.archetype = :archetype;";
    return $DB->get_records_sql($sql, ['courseid' => $courseid, 'archetype' => $archetype]);
}

function find_owners(int $courseid, string $archetype = 'editingteacher') {
    $archetypes = get_role_archetypes();
    if (!in_array($archetype, $archetypes)) {
        // Archetype does not exist.
        return false;
    }
    $enrolments = get_enrolments($courseid, $archetype);
    if (!$enrolments) {
        return false;
    }
    // Trying something.
    return $enrolments;
    // We now create an object that has different roles
    // of editing teacher based on that course.
    $owners = new stdClass;
    foreach ($enrolments as $e) {
        $type = $e->shortname;
        $owners->{$type}[$e->userid] = $e;
    }
    return $owners;
}

function insert_cw_logentry(string $description, string $actor = null, int $metricsid = null) {
    $log = new stdClass();
    $log->timestamp = time();
    $log->description = $description;
    $log->actor = $metricsid ?? null;
    $log->actor = $actor ?? 'system';
    global $DB;
    return $DB->insert_record('tool_coursewrangler_log', $log);
}

/**
 * This is a test function to be removed asap.
 */
function test_sendmessage($subject, $messagebody, $user) {
    $message = new \core\message\message();
    $message->courseid = SITEID;
    $message->component = 'tool_coursewrangler'; // Your plugin's name
    $message->name = 'schedulednotification'; // Your notification name from message.php
    $message->userfrom = \core_user::get_noreply_user(); // If the message is 'from' a specific user you can set them here
    $message->userto = $user;
    $message->subject = $subject;
    $message->fullmessage = $messagebody;
    $message->fullmessageformat = FORMAT_MARKDOWN;
    $message->fullmessagehtml = html_to_text($message->fullmessage);
    $message->smallmessage = $messagebody;
    $message->notification = 1; // Because this is a notification generated from Moodle, not a user-to-user message
    $message->contexturl = (new \moodle_url('/course/'))->out(false); // A relevant URL for the notification
    $message->contexturlname = 'Course list'; // Link title explaining where users get to for the contexturl
    $content = [
        '*' =>
        ['header' => ' HEADER ', 'footer' => ' FOOTER ']
    ]; // Extra content for specific processor.
    $message->set_additional_content('email', $content);
    $messageid = message_send($message);
    return $messageid;
}

/**
 * Temporary function to help debug within html.
 */
function cwt_debugger($data, string $leadingtext = 'Debug') {
    if (!debugging()) {
        return false;
    }
    $id = 'coursewrangler_debug_' . random_int(100, 100000);
    echo "<p><button class=\"btn btn-dark\" type=\"button\" data-toggle=\"collapse\" data-target=\"#$id\">Debug data</button></p>";
    echo '<div class="collapse" id="'.$id.'"><pre>' . $leadingtext . ': <br>';
    if ($data === null) {
        echo "Data is null.";
    } else {
        print_object($data);
    }
    echo '</pre></div>';
    return true;
}