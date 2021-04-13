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
class report_table extends table_sql implements renderable
{
    /**
     * Sets up the table.
     */
    public function __construct(\moodle_url $baseurl, array $params = []) {
        parent::__construct('tool_coursewrangler-report');
        $this->context = \context_system::instance();
        // This object should not be used without the right permissions. TODO: THIS ->
        // require_capability('moodle/badges:manageglobalsettings', $this->context);

        // Action table data trigger.
        $this->display_action_data = $params['display_action_data'] ?? false;

        // Define columns and headers in the table.
        $this->define_table_columns();

        // Define configs.
        $this->define_table_configs();

        // Optional params setting.
        $this->category_ids = $params['category_ids'] ?? [];
        $this->course_timecreated_after = $params['course_timecreated_after'] ?? null;
        $this->course_timecreated_before = $params['course_timecreated_before'] ?? null;
        $this->course_startdate_after = $params['course_startdate_after'] ?? null;
        $this->course_startdate_before = $params['course_startdate_before'] ?? null;
        $this->course_enddate_after = $params['course_enddate_after'] ?? null;
        $this->course_enddate_before = $params['course_enddate_before'] ?? null;
        $this->course_timeaccess_after = $params['course_timeaccess_after'] ?? null;
        $this->course_timeaccess_before = $params['course_timeaccess_before'] ?? null;
        $this->course_timecreated_notset = $params['course_timecreated_notset'] ?? false;
        $this->course_startdate_notset = $params['course_startdate_notset'] ?? false;
        $this->course_enddate_notset = $params['course_enddate_notset'] ?? false;
        $this->course_timeaccess_notset = $params['course_timeaccess_notset'] ?? false;

        // Preparing data for building urls in edit col.
        $params['category_ids'] = isset(($params['category_ids'])) ? implode(',', $params['category_ids']) : null;
        $this->url_params = $params;
        $this->return_link = $baseurl->out();
        $this->define_baseurl($baseurl);
        $this->define_table_sql();
    }
    /**
     * Setup the headers for the table.
     */
    protected function define_table_columns() {
        $selectlabel = \html_writer::tag(
            'label',
            get_string('table_selectall', 'tool_coursewrangler'),
            ['class' => 'accesshide', 'for' => "selectall"]
        );
        $selectcheckbox = \html_writer::tag(
            'input',
            '',
            ['type' => 'checkbox',
            'id' => "selectall",
            'name' => 'selectall',
            'title' => get_string('table_selectall', 'tool_coursewrangler')]
        );
        $selecthtml = \html_writer::tag(
            'div',
            $selectlabel . $selectcheckbox,
            ['class' => 'selectall']
        );
        $cols = [
            'row_select' => get_string('table_row_select', 'tool_coursewrangler') . $selecthtml,
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

        // Prepare to display table with action data.
        if ($this->display_action_data) {
            $cols['action'] = 'Action';
            $cols['status'] = 'Action Status';
            // Unsetting some data as no space on screen.
            unset($cols['course_timecreated']);
            unset($cols['course_startdate']);
            unset($cols['course_enddate']);
        }

        $this->define_columns(array_keys($cols));
        $this->define_headers(array_values($cols));
    }
    /**
     * Define table configs.
     */
    protected function define_table_configs() {
        $this->sortable(true, 'course_id', SORT_ASC);
        $this->no_sorting('row_select');
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
    protected function show_hide_link($column, $index) {
        if ($column != 'row_select' && $column != 'course_id') {
            return parent::show_hide_link($column, $index);
        }
        return '';
    }
    /**
     * Define table SQL.
     */
    protected function define_table_sql() {
        global $DB;
        // Make sure that metrics.course_id is ALWAYS first item in fields section of the query.
        $what_metrics_sql = "metrics.course_id, metrics.id, metrics.course_module_id, metrics.course_shortname, metrics.course_fullname, metrics.course_idnumber, metrics.course_timecreated, metrics.course_timemodified, metrics.course_startdate, metrics.course_enddate, metrics.course_visible, metrics.course_isparent, metrics.course_modulescount, metrics.course_timeaccess, metrics.course_lastenrolment, metrics.activity_type, metrics.activity_lastmodified, metrics.total_enrol_count, metrics.active_enrol_count, metrics.self_enrol_count, metrics.manual_enrol_count, metrics.meta_enrol_count, metrics.other_enrol_count, metrics.suspended_enrol_count, metrics.metrics_updated";
        $what_score_sql = "score.id, score.metrics_id, score.timemodified, score.raw, score.rounded, score.percentage";
        $what_sql = "$what_metrics_sql, $what_score_sql";
        // Default where statement should at least have one statement,
        // so we use true as the initial statement to avoid Moodle
        // errors and other undesired behaviour.
        $where_sql = "true";
        $from_sql = "{tool_coursewrangler_metrics} AS metrics";
        $join_score_sql = " LEFT JOIN {tool_coursewrangler_score} AS score ON metrics.id=score.metrics_id ";

        $join_action_data = '';

        if ($this->display_action_data) {
            $join_action_data = " LEFT JOIN {tool_coursewrangler_action} AS act ON metrics.course_id=act.course_id ";
            // Make sure not to double select course_id here, otherwise ambiguous error appears.
            $what_sql .= ", act.id, act.action, act.status, act.lastupdated";
        }

        $full_join_score_sql = $join_score_sql . $join_action_data;

        // Date SQL options.
        if ($this->course_timecreated_notset) {
            // IF NOTSET option for COURSE_TIMECREATED is set, filter all missing time created.
            $where_sql .= " AND metrics.course_timecreated = 0";
        } else {
            if (isset($this->course_timecreated_after)) {
                // Option where COURSE_TIMECREATED is AFTER specified date.
                $where_sql .= " AND metrics.course_timecreated > $this->course_timecreated_after";
            }
            if (isset($this->course_timecreated_before)) {
                // Option where COURSE_TIMECREATED is BEFORE specified date.
                $where_sql .= " AND metrics.course_timecreated < $this->course_timecreated_before";
            }
        }
        if ($this->course_startdate_notset) {
            // IF NOTSET option for COURSE_STARTDATE is set, filter all missing time created.
            $where_sql .= " AND metrics.course_startdate = 0";
        } else {
            if (isset($this->course_startdate_after)) {
                // Option where COURSE_STARTDATE is AFTER specified date.
                $where_sql .= " AND metrics.course_startdate > $this->course_startdate_after";
            }
            if (isset($this->course_startdate_before)) {
                // Option where COURSE_STARTDATE is BEFORE specified date.
                $where_sql .= " AND metrics.course_startdate < $this->course_startdate_before";
            }
        }
        if ($this->course_enddate_notset) {
            // IF NOTSET option for COURSE_ENDDATE is set, filter all missing time created.
            $where_sql .= " AND metrics.course_enddate = 0";
        } else {
            if (isset($this->course_enddate_after)) {
                // Option where COURSE_ENDDATE is AFTER specified date.
                $where_sql .= " AND metrics.course_enddate > $this->course_enddate_after";
            }
            if (isset($this->course_enddate_before)) {
                // Option where COURSE_ENDDATE is BEFORE specified date.
                $where_sql .= " AND metrics.course_enddate < $this->course_enddate_before";
            }
        }
        if ($this->course_timeaccess_notset) {
            // IF NOTSET option for COURSE_TIMEACCESS is set, filter all missing time created.
            $where_sql .= " AND metrics.course_timeaccess = 0";
        } else {
            if (isset($this->course_timeaccess_after)) {
                // Option where COURSE_TIMEACCESS is AFTER specified date.
                $where_sql .= " AND metrics.course_timeaccess > $this->course_timeaccess_after";
            }
            if (isset($this->course_timeaccess_before)) {
                // Option where COURSE_TIMEACCESS is BEFORE specified date.
                $where_sql .= " AND metrics.course_timeaccess < $this->course_timeaccess_before";
            }
        }
        // Check categories exists.
        if (count($this->category_ids) > 0) {
            $categories = [];
            foreach ($this->category_ids as $key => $category_id) {
                if ($category_id <= 0) {
                    continue;
                }
                // TODO use record_exists instead?
                $categories[$category_id] = $DB->get_record_sql("SELECT * FROM {course_categories} WHERE id = :id;", ['id' => $category_id]);
            }
            if (count($categories) > 0) {
                $id_courses_array = [];
                foreach ($categories as $id => $category) {
                    if ($category != false) {
                        // Category found, exists.
                        $id_courses = $DB->get_records_sql("SELECT c.id FROM {course} AS c JOIN {course_categories} AS cc ON c.category=cc.id WHERE cc.id=:id;", ['id' => $id]);
                        foreach ($id_courses as $course) {
                            $id_courses_array[] = $course->id;
                        }
                    }
                }
                $ids_string = implode(',', $id_courses_array);
                $and_categories_sql = "AND metrics.course_id IN ($ids_string)";
                if (strlen($ids_string) < 1) {
                    $and_categories_sql = '';
                }
                $this->debug_sql = "$what_sql $from_sql $full_join_score_sql $where_sql $and_categories_sql";
                $where_sql = "$where_sql $and_categories_sql";
            }
        }
        $this->set_sql($what_sql, "$from_sql $full_join_score_sql", $where_sql);
    }
    /**
     * Processing dates for table.
     */
    function col_course_timecreated($values) : string {
        return ($values->course_timecreated == 0) ? '-' : userdate($values->course_timecreated);
    }
    function col_course_timemodified($values) : string {
        return ($values->course_timemodified == 0) ? '-' : userdate($values->course_timemodified);
    }
    function col_course_startdate($values) : string {
        return ($values->course_startdate == 0) ? '-' : userdate($values->course_startdate);
    }
    function col_course_enddate($values) : string {
        return ($values->course_enddate == 0) ? '-' : userdate($values->course_enddate);
    }
    function col_course_timeaccess($values) : string {
        return ($values->course_timeaccess == 0) ? '-' : userdate($values->course_timeaccess);
    }
    function col_course_lastenrolment($values) : string {
        return ($values->course_lastenrolment == 0) ? '-' : userdate($values->course_lastenrolment);
    }
    function col_activity_lastmodified($values) : string {
        return ($values->activity_lastmodified == 0) ? '-' : userdate($values->activity_lastmodified);
    }
    /**
     * Processing visible and parent cols.
     */
    function col_course_visible($values) : string {
        return ($values->course_visible ? 'Yes' : 'No');
    }
    function col_course_isparent($values) : string {
        return ($values->course_isparent ? 'Yes' : 'No');
    }
    /**
     * Turning course name into link for details area.
     * TODO: Improve this into a link that goes to a details page within coursewrangler?
     */
    function col_course_fullname($values) : string {
        $url_params = [
            'course_id' => $values->course_id,
            'return_link' => $this->return_link
        ];
        $url = new moodle_url('/admin/tool/coursewrangler/report_details.php', $url_params);
        $link = html_writer::link($url, $values->course_fullname);
        return $link;
    }
    /**
     * Creating the score when required.
     */
    function col_percentage($values) : string {
        $display_value = $values->percentage ? $values->percentage . '%' : get_string('table_percentage_notavailable', 'tool_coursewrangler');
        return ($display_value);
    }
    /**
     * Creating the action col.
     */
    function col_action($values) : string {
        $display_value = "table_action_$values->action" ?? 'table_value_notavailable';
        $display_value_string = get_string($display_value, 'tool_coursewrangler');
        return ($display_value_string);
    }
    /**
     * Creating the action status col.
     */
    function col_status($values) : string {
        $display_value = "table_status_$values->status" ?? 'table_value_notavailable';
        $display_value_string = get_string($display_value, 'tool_coursewrangler');
        return ($display_value_string);
    }
    /**
     * Creating the select col.
     */
    function col_row_select($values) : string {
        $course_id = $values->course_id;
        $labelcontent = get_string('table_row_select', 'tool_coursewrangler') . " $values->course_fullname";
        $label = \html_writer::tag(
            'label',
            $labelcontent,
            ['class' => 'accesshide', 'for' => "selectcourse_$course_id"]
        );
        $checkbox = \html_writer::tag(
            'input',
            '',
            ['type' => "checkbox",
            'id' => "selectcourse_$course_id",
            'class' => "selectcourses",
            'name' => "selectedcourseids",
            'value' => $course_id]
        );
        return $label . $checkbox;
    }
}
