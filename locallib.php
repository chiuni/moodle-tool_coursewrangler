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
 * @deprecated use find_relevant_course_data_lite instead
 */
function find_relevant_course_data()
{
    global $DB, $CFG;
    $modules = $DB->get_records_sql("SELECT id, name FROM {modules};");
    $union_segments = [];
    foreach ($modules as $module) {
        $union_segments[] =    " SELECT act.id, act.course, act.name, act.timemodified FROM " . '{' . $module->name . '}' . " AS act ";
    }
    $union_statement = implode(" UNION ", $union_segments);
    return $DB->get_records_sql(
        "SELECT c.id AS course_id,
                cm.id AS course_module_id, 
                act.id AS activity_id,
                c.fullname AS course_fullname, 
                c.shortname AS course_shortname, 
                c.idnumber AS course_idnumber, 
                c.startdate AS course_startdate, 
                c.enddate AS course_enddate, 
                c.timecreated AS course_timecreated, 
                c.timemodified AS course_timemodified, 
                c.visible AS course_visible, 
                m.name AS activity_type, 
                MAX(act.timemodified) AS activity_last_modified
        FROM    {course} AS c 
            JOIN {course_modules} AS cm ON cm.course=c.id 
            JOIN {modules} AS m ON cm.module=m.id 
            JOIN (  $union_statement ) AS act ON act.course=c.id AND act.id=cm.instance
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
function find_relevant_course_data_lite(array $options = [])
{
    $course_query = find_course_activity_data();
    $ula_query = find_course_last_access();
    $meta_query = find_meta_parents();
    $course_children = [];
    $course_parents = [];
    foreach ($meta_query as $value) {
        $course_parents[$value->courseid][] = $value->parent_course_id;
    }
    foreach ($meta_query as $value) {
        $course_children[$value->parent_course_id][] = $value->courseid;
    }
    foreach ($course_query as $key => $result) {
        $result->course_timeaccess = $ula_query[$result->course_id]->timeaccess ?? 0;
        $result->course_parents = null;
        $result->course_children = null;
        if (array_key_exists($result->course_id, $course_children)) {
            $result->course_children = implode(',', $course_children[$result->course_id]);
        }
        if (array_key_exists($result->course_id, $course_parents)) {
            $result->course_parents = implode(',', $course_parents[$result->course_id]);
        }
        $result->course_modulescount = count_course_modules($result->course_id)->course_modulescount ?? 0;
        $result->course_lastenrolment = find_last_enrolment($result->course_id)->course_lastenrolment ?? 0;
        $course_students = find_course_students($result->course_id);
        foreach ($course_students as $key => $value) {
            $result->$key = $value;
        }
    }
    // Options processing.
    // We must always have $minimumage specified, if missing defaults to settings.
    // This enables us to bypass the settings if needed for minimum age.
    $minimumage = isset($options['minimumage']) ? $options['minimumage'] : get_config('tool_coursewrangler', 'minimumage');
    foreach ($course_query as $id => $course) {
        if ($course->course_startdate < 1) {
            continue;
        }
        $course_age = time() - $course->course_startdate;
        $new_course = false;
        if ($minimumage !== null && $minimumage > 0) {
            $new_course = $minimumage > $course_age;
        }
        if ($new_course) {
            unset($course_query[$id]);
        }
    }
    return $course_query;
}

/**
 * Simple function that should find all meta enrolments and therefore help
 *  identify meta parents.
 */
function find_meta_parents() {
    global $DB;
    return $DB->get_records_sql("SELECT id, courseid, customint1 AS parent_course_id FROM {enrol} WHERE enrol = 'meta';");
}

/** 
 * Simple query to find the latest enrolment in a course by course id.
 */
function find_last_enrolment(int $course_id) {
    if ($course_id < 1) {
        return null;
    }
    global $DB;
   return $DB->get_record_sql(
        "SELECT id, courseid AS course_id, 
                MAX(timecreated) AS course_lastenrolment 
            FROM {enrol} WHERE courseid=:course_id;",
        ['course_id' => $course_id]);
}

/**
 * The idea is that for a given course, we can find all of the students.
 * By finding students, we can figure out if the course is still in use or not,
 *  since courses without a single student are likely to be no longer needed.
 * 
 * @param int $id The course ID.
 * @return array $course_students List of all students for a course.
 */
function find_course_students(int $id) {
    global $DB;
    // By using the archetype we can firmly establish if they are students or not.
    $archetype = 'student';
    $coursecontext = \context_course::instance($id);
    // Then foreach result, depending on type of enrol (e.enrol), store that information
    // also remember to check for students only, we do not want any other archetypes for now.
    $all_students = [];
    $sql = "SELECT  concat(ra.id, '-', e.id) as id, 
                    ue.userid AS userid, 
                    r.shortname, 
                    ra.component, 
                    ue.timestart, 
                    ue.timecreated, 
                    ue.timemodified,
                    r.archetype AS role_type,
                    ue.status AS enrol_status,
                    e.enrol AS enrol_type 
            FROM {role_assignments} AS ra 
        LEFT JOIN {user_enrolments} AS ue ON ra.userid = ue.userid 
        LEFT JOIN {role} AS r ON ra.roleid = r.id 
        LEFT JOIN {context} AS c ON c.id = ra.contextid 
        LEFT JOIN {enrol} AS e ON e.courseid = c.instanceid AND ue.enrolid = e.id 
        WHERE r.archetype=:archetype AND e.courseid = :course_id;";
    $students = $DB->get_records_sql($sql,['course_id' => $id, 'archetype' => $archetype]);
    $all_students[] = $students;
    // This might need review, but the idea is that we create a total enrolment
    //  count, and other enrolment counts to help admins make decisions when
    //  deleting courses.
    $course_students = new stdClass;
    $course_students->total_enrol_count = 0;
    $course_students->active_enrol_count = 0;
    $course_students->self_enrol_count = 0;
    $course_students->manual_enrol_count = 0;
    $course_students->meta_enrol_count = 0;
    $course_students->other_enrol_count = 0;
    $course_students->suspended_enrol_count = 0;
    foreach ($all_students as $students) {
        $course_students->total_enrol_count += count($students) ?? 0;
        foreach ($students as $student) {
            $roles = get_user_roles($coursecontext, $student->userid);
            if ($roles) {
                // I don't know what I was going to do here.
            }
            // This switch checks if enrolment status is active.
            switch ($student->enrol_status) {
                case 0:
                    $course_students->active_enrol_count += 1;
                    break;
                default:
                    break;
            }
            switch ($student->enrol_type) {
                case 'self':
                    $course_students->self_enrol_count += 1;
                    break;
                case 'manual':
                    $course_students->manual_enrol_count += 1;
                    break;
                case 'meta':
                    $course_students->meta_enrol_count += 1;
                    break;
                default:
                    $course_students->other_enrol_count += 1;
                    break;
            }
        }
        $course_students->suspended_enrol_count += $course_students->total_enrol_count - $course_students->active_enrol_count ?? 0;
    }

    return $course_students;
}

/**
 * Simple function to count modules in a course by course id.
 */
function count_course_modules(int $course_id) {
    if ($course_id < 1) {
        return null;
    }
    global $DB;
    return $DB->get_record_sql("SELECT COUNT(id) AS course_modulescount FROM {course_modules} WHERE course=:course_id;", ['course_id' => $course_id]);
}

/**
 * Large query that fetches the core course data plus activity data regarding that course.
 * 
 * Might be worth ignoring the activity data here: how valuable is it?
 * 
 * I cannot remember why it would be beneficial to use $where, maybe for a single course?
 */
function find_course_activity_data(string $where = '') {
    global $DB;
    $modules = $DB->get_records_sql("SELECT id, name FROM {modules};");
    $union_segments = [];
    foreach ($modules as $module) {
        $union_segments[] =    " SELECT act.id, act.course, act.name, act.timemodified FROM " . '{' . $module->name . '}' . " AS act $where";
    }
    $union_statement = implode(" UNION ", $union_segments);
    return $DB->get_records_sql(
        "SELECT c.id AS course_id,
                cm.id AS course_module_id, 
                act.id AS activity_id,
                c.fullname AS course_fullname, 
                c.shortname AS course_shortname, 
                c.idnumber AS course_idnumber, 
                c.startdate AS course_startdate, 
                c.enddate AS course_enddate, 
                c.timecreated AS course_timecreated, 
                c.timemodified AS course_timemodified, 
                c.visible AS course_visible, 
                m.name AS activity_type, 
                MAX(act.timemodified) AS activity_lastmodified
        FROM    {course} AS c 
            LEFT JOIN {course_modules} AS cm ON cm.course=c.id 
            LEFT JOIN {modules} AS m ON cm.module=m.id 
            LEFT JOIN ( $union_statement ) AS act 
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
    return $DB->get_record('tool_coursewrangler_metrics', ['course_id' => $courseid], '*', MUST_EXIST);
}

function get_enrolments(int $course_id, string $archetype) {
    if ($course_id < 1) {
        return false;
    }
    global $DB;
    $sql = "SELECT concat(ra.id, '-', e.id) as id, 
            ue.userid, 
            r.shortname, 
            r.archetype,
            ra.component,
            e.enrol,
            ue.timestart,
            ue.timecreated,
            ue.timemodified
        FROM {role_assignments} AS ra 
       LEFT JOIN {user_enrolments} AS ue ON ra.userid = ue.userid 
       LEFT JOIN {role} AS r ON ra.roleid = r.id 
       LEFT JOIN {context} AS c ON c.id = ra.contextid 
       LEFT JOIN {enrol} AS e ON e.courseid = c.instanceid AND ue.enrolid = e.id 
       WHERE e.courseid = :course_id
       AND r.archetype = :archetype;";
    return $DB->get_records_sql($sql, ['course_id' => $course_id, 'archetype' => $archetype]);
}

function find_owners(int $course_id, string $archetype = 'editingteacher') {
    $archetypes = get_role_archetypes();
    if (!in_array($archetype, $archetypes)) {
        // Archetype does not exist.
        return false;
    }
    $enrolments = get_enrolments($course_id, $archetype);
    if (!$enrolments) {
        return false;
    }
    // Trying something.
    return $enrolments;
    /**
     * We now create an object that has different roles
     *  of editing teacher based on that course.
     */
    $owners = new stdClass;
    foreach ($enrolments as $e) {
        $type = $e->shortname;
        $owners->$type[$e->userid] = $e;
    }
    return $owners;
}

function insert_cw_logentry(string $description, string $actor = null, int $metricsid = null)
{
    $log = new stdClass();
    $log->timestamp = time();
    $log->description = $description;
    $log->actor = $actor ?? 'system';
    $log->actor = $metricsid ?? null;
    global $DB;
    return $DB->insert('tool_coursewrangler_log', $log);
}

/**
 * Temporary function to help debug within html.
 */
function cwt_debugger($data, string $leadingtext = 'Debug') {
    if (!debugging()){
        return false;
    }
    $id = 'coursewrangler_debug_' . random_int(100, 100000);
    echo "<p><button class=\"btn btn-dark\" type=\"button\" data-toggle=\"collapse\" data-target=\"#$id\">Debug data</button></p>"; 
    echo '<div class="collapse" id="'.$id.'"><pre>' . $leadingtext . ': <br>';
    if ($data === null) {
        echo "Data is null.";
    } else {
        print_r($data);
    }
    echo '</pre></div>';
    return true;
}