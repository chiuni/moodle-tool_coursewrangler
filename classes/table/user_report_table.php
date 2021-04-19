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

namespace tool_coursewrangler\table;

use html_writer;
use moodle_url;
use table_sql;
use renderable;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/tablelib.php');

/**
 * Table that lists all report data.
 */
class user_report_table extends table_sql implements renderable
{
    /**
     * Sets up the table.
     */
    public function __construct(\moodle_url $baseurl, array $params = [])
    {
        parent::__construct('tool_coursewrangler-report');
        $this->context = \context_system::instance();
        // This object should not be used without the right permissions. TODO: THIS ->
        // require_capability('moodle/badges:manageglobalsettings', $this->context);

        // Define columns and headers in the table.
        $this->define_table_columns();

        // Define configs.
        $this->define_table_configs();

        // Optional params setting.
        $this->courseids = $params['courseids'] ?? 0;
        if ($this->courseids == 0) {
            global $USER;
            $enrolments = enrol_get_all_users_courses($USER->id);
            $this->courseids = array_keys($enrolments);
        }

        $this->define_baseurl($baseurl);
        $this->define_table_sql();
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
            // 'course_timecreated' => get_string('table_course_timecreated', 'tool_coursewrangler'),
            // 'course_timemodified' => get_string('table_course_timemodified', 'tool_coursewrangler'),
            // 'course_startdate' => get_string('table_course_startdate', 'tool_coursewrangler'),
            'course_enddate' => get_string('table_course_enddate', 'tool_coursewrangler'),
            'course_visible' => get_string('table_course_visible', 'tool_coursewrangler'),
            // 'course_isparent' => get_string('table_course_isparent', 'tool_coursewrangler'),
            // 'course_modulescount' => get_string('table_course_modulescount', 'tool_coursewrangler'),
            'course_timeaccess' => get_string('table_course_timeaccess', 'tool_coursewrangler'),
            // 'course_lastenrolment' => get_string('table_course_lastenrolment', 'tool_coursewrangler'),
            // 'activity_type' => get_string('table_activity_type', 'tool_coursewrangler'),
            // 'activity_lastmodified' => get_string('table_activity_lastmodified', 'tool_coursewrangler'),
            'action' => get_string('table_course_action', 'tool_coursewrangler'),
            'status' => get_string('table_course_status', 'tool_coursewrangler')
        ];

        $this->define_columns(array_keys($cols));
        $this->define_headers(array_values($cols));
    }
    /**
     * Define table configs.
     */
    protected function define_table_configs()
    {
        $this->sortable(true, 'course_id', SORT_ASC);
        $this->pageable(true);
    }
    /**
     * Override the table show_hide_link to not show for select column.
     * Taken from 'assign/gradingtable.php' but slightly modified.
     *
     * @param string $column the column name, index into various names.
     * @param int $index numerical index of the column.
     * @return string HTML fragment.
     */
    protected function show_hide_link($column, $index)
    {
        if ($column != 'course_id') {
            return parent::show_hide_link($column, $index);
        }
        return '';
    }
    /**
     * Define table SQL.
     */
    protected function define_table_sql()
    {
        // Make sure that metrics.course_id is ALWAYS first item in fields section of the query.
        $what_metrics_sql = "metrics.course_id, metrics.id, metrics.course_module_id, metrics.course_shortname, metrics.course_fullname, metrics.course_idnumber, metrics.course_timecreated, metrics.course_timemodified, metrics.course_startdate, metrics.course_enddate, metrics.course_visible, metrics.course_isparent, metrics.course_modulescount, metrics.course_timeaccess, metrics.course_lastenrolment, metrics.activity_type, metrics.activity_lastmodified, metrics.total_enrol_count, metrics.active_enrol_count, metrics.self_enrol_count, metrics.manual_enrol_count, metrics.meta_enrol_count, metrics.other_enrol_count, metrics.suspended_enrol_count, metrics.metrics_updated";
        $what_score_sql = "score.id, score.metrics_id, score.timemodified, score.raw, score.rounded, score.percentage";
        $what_sql = "$what_metrics_sql, $what_score_sql";
        // Default where statement should at least have one statement,
        // so we use true as the initial statement to avoid Moodle
        // errors and other undesired behaviour.
        $courseidslist = implode(',', $this->courseids);
        $where_sql = "metrics.course_id IN($courseidslist) AND act.id";
        $from_sql = "{tool_coursewrangler_metrics} AS metrics ";
        $join_score_sql = " LEFT JOIN {tool_coursewrangler_score} AS score ON metrics.id=score.metrics_id ";

        $join_action_data = '';

        $join_action_data = " LEFT JOIN {tool_coursewrangler_action} AS act ON metrics.course_id=act.course_id ";
        // Make sure not to double select course_id here, otherwise ambiguous error appears.
        $what_sql .= ", act.id, act.action, act.status, act.lastupdated";

        $full_join_score_sql = $join_score_sql . $join_action_data;

        $this->set_sql($what_sql, "$from_sql $full_join_score_sql", $where_sql);
    }
    /**
     * Processing dates for table.
     */

    public function col_course_enddate($values): string
    {
        return ($values->course_enddate == 0) ? '-' : userdate($values->course_enddate);
    }
    public function col_course_timeaccess($values): string
    {
        return ($values->course_timeaccess == 0) ? '-' : userdate($values->course_timeaccess);
    }
    /**
     * Processing visible col.
     */
    public function col_course_visible($values): string
    {
        $course_visible = $values->course_visible ? 'yes' : 'no';
        $display_value = isset($course_visible) ? "table_visible_$course_visible" : 'table_value_notavailable';
        $display_value_string = get_string($display_value, 'tool_coursewrangler');
        return ($display_value_string);
    }
    /**
     * Turning course name into link for details area.
     */
    public function col_course_fullname($values): string
    {
        $url = new moodle_url('/course/view.php?id=' . $values->course_id);
        $link = html_writer::link($url, $values->course_fullname);
        return $link;
    }
    /**
     * Creating the score when required.
     */
    public function col_percentage($values): string
    {
        $display_value = $values->percentage ? $values->percentage . '%' : get_string('table_percentage_notavailable', 'tool_coursewrangler');
        return ($display_value);
    }
    /**
     * Creating the action col.
     */
    public function col_action($values): string
    {
        $display_value = isset($values->action) ? "table_action_$values->action" : 'table_value_notavailable';
        $display_value_string = get_string($display_value, 'tool_coursewrangler');
        return ($display_value_string);
    }
    /**
     * Creating the action status col.
     */
    public function col_status($values): string
    {
        $display_value = isset($values->status) ? "table_status_$values->status" : 'table_value_notavailable';
        $display_value_string = get_string($display_value, 'tool_coursewrangler');
        return ($display_value_string);
    }
}
