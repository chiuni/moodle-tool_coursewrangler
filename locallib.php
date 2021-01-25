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
 * @author    Mark Sharp <m.sharp@chi.ac.uk>
 * @copyright 2020 University of Chichester {@link www.chi.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_coursewrangler;

use stdClass;

defined('MOODLE_INTERNAL') || die();

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
            'sideid' => SITEID
        ]
    );
}

function find_relevant_course_data_lite()
{
    $course_query = find_activities_modified();
    $ula_query = find_course_last_access();
    $meta_query = find_meta_parents();
    $parent_course_ids = array_keys($meta_query);
    foreach ($course_query as $key => $result) {
        $result->course_timeaccess = $ula_query[$key]->timeaccess ?? 0;
        $result->course_isparent = in_array($result->course_id, $parent_course_ids) ? 1 : 0; // could we count this?
        $result->course_modulescount = count_course_modules($result->course_id)->course_modulescount ?? null;
        $result->course_lastenrolment = find_last_enrolment($result->course_id)->course_lastenrolment ?? null;
        $result->course_students = new stdClass;
        $result->course_students = find_course_students($result->course_id);
    }
    return $course_query;
}

function find_meta_parents()
{
    global $DB;
    return $DB->get_records_sql("SELECT customint1 AS parent_course_id FROM {enrol} WHERE enrol = 'meta';");
}

function find_last_enrolment($id)
{
    global $DB;
    return $DB->get_record_sql("SELECT courseid AS course_id, MAX(timecreated) AS course_lastenrolment FROM {enrol} WHERE courseid=:id;", ['id' => $id]);
}

function find_course_students($id)
{
    global $DB;
    $students = $DB->get_records_sql(
        "SELECT ue.id AS ue_id, 
                ue.userid AS userid, 
                r.archetype AS role_type, 
                ue.status AS enrol_status, 
                e.enrol AS enrol_type 
            FROM {user_enrolments} AS ue
            JOIN {enrol} AS e  ON ue.enrolid=e.id
            JOIN {role} AS r ON e.roleid=r.id
            WHERE r.archetype='student' AND e.courseid=:id;",
        ['id' => $id]
    );
    $course_students = new stdClass;
    $course_students->total_enrol_count = count($students) ?? 0;
    $course_students->active_enrol_count = 0;
    $course_students->self_enrol_count = 0;
    $course_students->manual_enrol_count = 0;
    $course_students->meta_enrol_count = 0;
    $course_students->other_enrol_count = 0;
    foreach ($students as $student) {
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
    $course_students->suspended_enrol_count = count($students) - $course_students->active_enrol_count ?? 0;
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
        "SELECT ula.courseid, 
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

function process_date($format, $timestamp)
{
    if ($timestamp < 1) {
        return '-';
    }
    if ($format != 'timeago') {
        return date($format, $timestamp);
    }
    return time_ago($timestamp);
}

function time_ago($timestamp)
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
