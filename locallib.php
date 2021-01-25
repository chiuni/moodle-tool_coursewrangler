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

function find_last_enrolment(int $id)
{
    global $DB;
    return $DB->get_record_sql("SELECT courseid AS course_id, MAX(timecreated) AS course_lastenrolment FROM {enrol} WHERE courseid=:id;", ['id' => $id]);
}

function find_course_students(int $id)
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

function process_date(string $format, int $timestamp)
{
    if ($timestamp < 1) {
        return '-';
    }
    if ($format != 'timeago') {
        return date($format, $timestamp);
    }
    return time_ago($timestamp);
}

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

function get_course_deletion_score(stdClass $course, bool $simplify = false, bool $ratio = false)
{
    $course_score = [];

    $course_parent_weight = 10; // this makes parent courses more or less important
    $low_enrolments_flag = 10; // this triggers a low score for courses with less enrolments than
    $time_unit = 86400; // this makes each time unit = 1 score point
    $ratio_limit = 200; // this is the score used for 100%

    /**
     * Course End Date score
     * The information we have:
     *      The assigned end date of the course, could be 0 if not set.
     */
    if ($course->course_enddate != 0) {
        // how many time units have been from course end date to now = 1 score point
        $course_enddate_score = (time() - $course->course_enddate) / $time_unit;
    }

    /**
     * Course Last Access score
     * The information we have:
     *      The last access by anyone enroled to the course, could be 0 if not accessed.
     *      The time the course was created
     */
    if ($course->course_timeaccess > $course->course_timecreated) {
        // how many time units have been from course last access to now = 1 score point
        $course_timeaccess_score = (time() - $course->course_timeaccess) / $time_unit;
    }

    /**
     * Course Settings Time Modified score
     * The information we have:
     *      The last time someone edited course settings
     *      The time the course was created
     */
    if ($course->course_timemodified != $course->course_timecreated) {
        // how many time units have been from time created to course settings modified = 1 score point
        $course_timemodified_score = ($course->course_timecreated - $course->course_timemodified) / $time_unit;
    }

    /**
     * Activity Recently Modified score
     * The information we have:
     *      The last time an activity was changed
     *      The time the course was created
     */
    if ($course->activity_lastmodified != $course->course_timecreated) {
        // how many time units have been from time created to last activity modified = 1 score point
        $activity_lastmodified_score = ($course->course_timecreated - $course->activity_lastmodified) / $time_unit;
    }

    /**
     * Course Is Parent Score
     * The information we have:
     *      If the course is parent of other courses (meta enrolments count)
     */
    if ($course->course_isparent != 0) {
        // if a course is parent to other courses, add negative weight to score
        $course_isparent_score = 0 - ($course->course_isparent * $course_parent_weight);
    }

    /**
     * Course Last Enrolment Score
     * The information we have:
     *      The date the last enrolment was created // TODO: should we make this student only role enrolment?
     */
    if ($course->course_lastenrolment > 0) {
        // how many time units have been from last enrolment to now = 1 score point
        $course_lastenrolment_score = (time() - $course->course_lastenrolment) / $time_unit;
    }
    /**
     * Course Very Low Enrolment checker score
     * The information we have:
     *      The number of enrolments and type of enrolments per course
     */
    if ($course->course_students->total_enrol_count <= $low_enrolments_flag) {
        // how many time units have been from last enrolment to now = 1 score point
        $course_total_enrol_count_score = 50;
    }

    $course_visible_score = $course->course_visible ? -25 : 50;
    $course_enddate_score = (int) ($course_enddate_score ?? 0);
    $course_timeaccess_score = (int) ($course_timeaccess_score ?? 0);
    $course_timemodified_score = (int) ($course_timemodified_score ?? 0);
    $course_isparent_score = (int) ($course_isparent_score ?? 0);
    $course_lastenrolment_score = (int) ($course_lastenrolment_score ?? 0);
    $course_total_enrol_count_score = (int) ($course_total_enrol_count_score ?? 0);
    $activity_lastmodified_score = (int) ($activity_lastmodified_score ?? 0);
    
    $course_score = [
        'course_visible_score' => $course_visible_score,
        'course_timeaccess_score' => $course_timeaccess_score,
        'course_enddate_score' => $course_enddate_score,
        'course_timemodified_score' => $course_timemodified_score,
        'course_isparent_score' => $course_isparent_score,
        'course_lastenrolment_score' => $course_lastenrolment_score,
        'course_total_enrol_count_score' => $course_total_enrol_count_score,
        'activity_lastmodified_score' => $activity_lastmodified_score,
    ];
    // sum scores
    $final_score = array_sum($course_score);
    // do ratio
    $final_score = $ratio ? ((int) (($final_score / $ratio_limit) * 100)) : $final_score;
    // simplify return
    $score = $simplify ? $final_score : $course_score;
    return $score;
}
