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
 * @author    Mark Sharp <m.sharp@chi.ac.uk>
 * @copyright 2020 University of Chichester {@link www.chi.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace tool_coursewrangler\table;

use html_writer;
use moodle_url;
use table_sql;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/tablelib.php');

/**
 * Table that lists all report data.
 */
class report_table extends table_sql
{

    /**
     * Sets up the table.
     */
    public function __construct($baseurl, int $report_id, int $category_id = 0)
    {
        parent::__construct('tool_coursewrangler-report');
        $this->context = \context_system::instance();
        // This object should not be used without the right permissions. TODO: THIS ->
        // require_capability('moodle/badges:manageglobalsettings', $this->context);

        // Define columns in the table.
        $this->define_table_columns();

        // Define configs.
        $this->define_table_configs();

        $this->define_baseurl($baseurl);
        $this->define_table_sql($report_id, $category_id);
    }

    /**
     * Setup the headers for the table.
     */
    protected function define_table_columns()
    {
        $cols = [
            'course_id' => get_string('table_course_id', 'tool_coursewrangler'),
            // 'course_module_id' => get_string('table_course_module_id', 'tool_coursewrangler'),
            'course_shortname' => get_string('table_course_shortname', 'tool_coursewrangler'),
            'course_fullname' => get_string('table_course_fullname', 'tool_coursewrangler'),
            // 'course_idnumber' => get_string('table_course_idnumber', 'tool_coursewrangler'),
            'course_timecreated' => get_string('table_course_timecreated', 'tool_coursewrangler'),
            // 'course_timemodified' => get_string('table_course_timemodified', 'tool_coursewrangler'),
            'course_startdate' => get_string('table_course_startdate', 'tool_coursewrangler'),
            'course_enddate' => get_string('table_course_enddate', 'tool_coursewrangler'),
            'course_visible' => get_string('table_course_visible', 'tool_coursewrangler'),
            // 'course_isparent' => get_string('table_course_isparent', 'tool_coursewrangler'),
            // 'course_modulescount' => get_string('table_course_modulescount', 'tool_coursewrangler'),
            'course_timeaccess' => get_string('table_course_timeaccess', 'tool_coursewrangler'),
            // 'course_lastenrolment' => get_string('table_course_lastenrolment', 'tool_coursewrangler'),
            // 'activity_type' => get_string('table_activity_type', 'tool_coursewrangler'),
            // 'activity_lastmodified' => get_string('table_activity_lastmodified', 'tool_coursewrangler'),
            'percentage' => get_string('table_course_deletionscore', 'tool_coursewrangler')
        ];
        $this->define_columns(array_keys($cols));
        $this->define_headers(array_values($cols));
    }

    /**
     * Define table configs.
     */
    protected function define_table_configs()
    {
        $this->collapsible(true);
        $this->sortable(true, 'course_id', SORT_ASC);
        $this->pageable(true);
    }

    /**
     * Define table SQL
     */
    protected function define_table_sql($report_id, $category_id)
    {
        global $DB;
        $join_score = ' JOIN {tool_coursewrangler_score} AS cws ON cwc.id=cws.coursemt_id ';
        // check score has been calculated
        $score_check = $DB->get_records_sql("SELECT * FROM {tool_coursewrangler_coursemt} AS cwc $join_score WHERE report_id=:report_id", ['report_id' => $report_id]);
        if (count($score_check) < 1) {
            // if score not found, change query
            $join_score = '';
        }
        // check category exists
        if ($category_id > 0) {
            $category = $DB->get_record_sql("SELECT * FROM {course_categories} WHERE id = :id;", ['id' => $category_id]);
            if ($category != false) {
                // Category found, exists
                // TODO use record_exists instead
                $id_courses = $DB->get_records_sql("SELECT c.id FROM {course} AS c JOIN {course_categories} AS cc ON c.category=cc.id WHERE cc.id=:id;", ['id' => $category_id]);
                $id_courses_array = [];
                foreach ($id_courses as $course) {
                    $id_courses_array[] = $course->id;
                }
                $ids_string = implode(',', $id_courses_array);
                $this->set_sql("*", "{tool_coursewrangler_coursemt} AS cwc $join_score", "report_id=$report_id AND course_id IN ($ids_string)");
                return true;
            }
            $this->set_sql("*", "{tool_coursewrangler_coursemt} AS cwc $join_score", "report_id=$report_id");
            return false;
        }

        $this->set_sql("*", "{tool_coursewrangler_coursemt} AS cwc $join_score", "report_id=$report_id");
    }

    /**
     * Processing dates for table
     */
    function col_course_timecreated($values)
    {
        return ($values->course_timecreated == 0) ?  '-' : userdate($values->course_timecreated);
    }
    function col_course_timemodified($values)
    {
        return ($values->course_timemodified == 0) ?  '-' : userdate($values->course_timemodified);
    }
    function col_course_startdate($values)
    {
        return ($values->course_startdate == 0) ?  '-' : userdate($values->course_startdate);
    }
    function col_course_enddate($values)
    {
        return ($values->course_enddate == 0) ?  '-' : userdate($values->course_enddate);
    }
    function col_course_timeaccess($values)
    {
        return ($values->course_timeaccess == 0) ?  '-' : userdate($values->course_timeaccess);
    }
    function col_course_lastenrolment($values)
    {
        return ($values->course_lastenrolment == 0) ?  '-' : userdate($values->course_lastenrolment);
    }
    function col_activity_lastmodified($values)
    {
        return ($values->activity_lastmodified == 0) ?  '-' : userdate($values->activity_lastmodified);
    }
    /**
     * Processing visible and parent cols
     */
    function col_course_visible($values)
    {
        return ($values->course_visible ? 'Yes' : 'No');
    }
    function col_course_isparent($values)
    {
        return ($values->course_isparent ? 'Yes' : 'No');
    }
    /**
     * Turning course name into link for details area
     * TODO: Improve this into a link that goes to a details page within coursewrangler
     */
    function col_course_fullname($values)
    {
        $url = new moodle_url('/admin/tool/coursewrangler/report_details.php?course_id=' . $values->course_id . '&report_id=' . $values->report_id, []);
        $link = html_writer::link($url, $values->course_fullname);
        return $link;
    }
    /**
     * Creating the score when required
     */
    function col_percentage($values)
    {
        $display_value = $values->percentage ? $values->percentage . '%' : 'Not Available';
        return ($display_value);
    }
}
