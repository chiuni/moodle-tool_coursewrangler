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

function find_relevant_course_data_lite(array $options = [])
{
    $course_query = find_activities_modified();
    $ula_query = find_course_last_access();
    $meta_query = find_meta_parents();
    $parent_course_ids = array_keys($meta_query);
    foreach ($course_query as $key => $result) {
        $result->course_timeaccess = $ula_query[$key]->timeaccess ?? 0;
        $result->course_isparent = in_array($result->course_id, $parent_course_ids) ? 1 : 0; // could we count this?
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
 * @deprecated
 */
function fetch_report_data()
{
    global $DB;
    $report_data = $DB->get_records('tool_coursewrangler_metrics');
    return $report_data;
    // Formatting data to match find_relevant_course_data_lite
    foreach ($report_data as $course) {
        $course->course_students = new stdClass;
        $course->course_students->total_enrol_count = $course->total_enrol_count;
        unset($course->total_enrol_count);
        $course->course_students->active_enrol_count = $course->active_enrol_count;
        unset($course->active_enrol_count);
        $course->course_students->self_enrol_count = $course->self_enrol_count;
        unset($course->self_enrol_count);
        $course->course_students->manual_enrol_count = $course->manual_enrol_count;
        unset($course->manual_enrol_count);
        $course->course_students->meta_enrol_count = $course->meta_enrol_count;
        unset($course->meta_enrol_count);
        $course->course_students->other_enrol_count = $course->other_enrol_count;
        unset($course->other_enrol_count);
        $course->course_students->other_enrol_count = $course->suspended_enrol_count;
        unset($course->suspended_enrol_count);
    }
    
}

function find_meta_parents()
{
    global $DB;
    return $DB->get_records_sql("SELECT id, customint1 AS parent_course_id FROM {enrol} WHERE enrol = 'meta';");
}

function find_last_enrolment(int $id)
{
    global $DB;
    return $DB->get_record_sql("SELECT id, courseid AS course_id, MAX(timecreated) AS course_lastenrolment FROM {enrol} WHERE courseid=:id;", ['id' => $id]);
}
/**
 * @param int $id Course ID
 */
function find_course_students(int $id)
{
    global $DB;
    $archetype = 'student';
    // To find course students, first select all enrol instances from mdl_enrol table
    // status=0 means the enrolment method is enabled for this course
    $enrol = $DB->get_records_sql("SELECT * FROM {enrol} AS e WHERE e.courseid=:id AND e.status=0;", ['id' => $id]);
    $coursecontext = \context_course::instance($id);
    // Then foreach result, depending on type of enrol (e.enrol), store that information
    // also remember to check for students only, we do not want any other archetypes for now
    $all_students = [];
    foreach ($enrol as $enrol_instance) {
        $students = $DB->get_records_sql(
            "SELECT ue.id AS ue_id, 
                ue.userid AS userid, 
                r.archetype AS role_type, 
                ue.status AS enrol_status,
                e.enrol AS enrol_type 
            FROM {user_enrolments} AS ue
            JOIN {enrol} AS e  ON ue.enrolid=e.id
            JOIN {role} AS r ON e.roleid=r.id
            WHERE r.archetype='$archetype' AND ue.enrolid=:enrolid;",
            ['enrolid' => $enrol_instance->id]
        );
        $all_students[] = $students;
    }

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
            if ($roles) {}
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

function count_course_modules(int $id)
{
    global $DB;
    if ($id < 1) {
        return null;
    }
    return $DB->get_record_sql("SELECT COUNT(id) AS course_modulescount FROM {course_modules} WHERE course=:id;", ['id' => $id]);
}
function find_activities_modified(string $where = '')
{
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
            JOIN {course_modules} AS cm ON cm.course=c.id 
            JOIN {modules} AS m ON cm.module=m.id 
            JOIN ( $union_statement ) AS act 
        ON act.course=c.id 
        AND act.id=cm.instance
        WHERE c.id!=:siteid
        GROUP BY c.id;",
        ['siteid' => SITEID]
    );
}

function find_course_last_access()
{
    global $DB, $CFG;
    return $DB->get_records_sql(
        "SELECT ula.id,
                ula.courseid, 
                ula.userid, 
                ula.timeaccess
        FROM    {user_lastaccess} AS ula
            INNER JOIN (SELECT  courseid, 
                                MAX(timeaccess) AS timeid
                        FROM    {user_lastaccess}
                        WHERE   userid!=:guestid 
                        AND     userid NOT IN (:siteadminids)
                        GROUP BY courseid) AS groupedula 
        ON ula.courseid = groupedula.courseid 
        AND ula.timeaccess = groupedula.timeid ORDER BY ula.courseid ASC;",
        [
            'guestid' => 1,
            'siteadminids' => $CFG->siteadmins
        ]
    );
}
/**
 * @deprecated
 */
function time_ago(int $timestamp)
{
    $string_map = [
        'y' => 'year',
        'm' => 'month',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
    ];
    $date = new \DateTime();
    $date->setTimestamp($timestamp);
    $interval = $date->diff(new \DateTime('now'));
    $date_string = '';
    foreach ($interval as $key => $ago) {
        $plural = '';
        if ($ago <= 0) {
            continue;
        } else if ($ago > 1) {
            $plural = 's';
        }
        $date_string .= $ago . ' ' . $string_map[$key] . $plural . ' ago';
        break;
    }
    return $date_string;
}

function moodletime_to_unixtimestamp(array $timearray)
{
    $timestring = $timearray['day'] . '-' . $timearray['month'] . '-' . $timearray['year'];
    return (strtotime($timestring) ?? 0);
}

/**
 * Temporary function
 */
function cwt_debugger($data, string $leadingtext = 'Debug') {
    $debugmode = get_config('tool_coursewrangler', 'debugmode');
    // To do: check if Moodle is in debug mode.
    if (!$debugmode){
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