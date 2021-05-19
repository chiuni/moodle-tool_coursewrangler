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
        $this->filter_action_data = $params['filter_action_data'] ?? [];
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
        $this->matchstring_short = $params['matchstring_short'] ?? null;
        $this->matchstring_full = $params['matchstring_full'] ?? null;
        $this->hideshow_meta_children = $params['hideshow_meta_children'] ?? null;
        $this->hideshow_meta_parents = $params['hideshow_meta_parents'] ?? null;
        $this->hideshow_hidden_courses = $params['hideshow_hidden_courses'] ?? null;

        // Preparing data for building urls in edit col.
        $params['category_ids'] = isset(($params['category_ids'])) ? implode(',', $params['category_ids']) : null;
        $params['filter_action_data'] = isset(($params['filter_action_data'])) ? implode(',', $params['filter_action_data']) : null;
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
        $cols = [];
        if (isset($this->display_action_data) && $this->display_action_data) {
            $cols['row_select'] = get_string('table_row_select', 'tool_coursewrangler') . $selecthtml;
        }
        $cols['course_id'] = get_string('table_course_id', 'tool_coursewrangler');
        $cols['course_shortname'] = get_string('table_course_shortname', 'tool_coursewrangler');
        $cols['course_fullname'] = get_string('table_course_fullname', 'tool_coursewrangler');
        $cols['course_timecreated'] = get_string('table_course_timecreated', 'tool_coursewrangler');
        $cols['course_startdate'] = get_string('table_course_startdate', 'tool_coursewrangler');
        $cols['course_enddate'] = get_string('table_course_enddate', 'tool_coursewrangler');
        $cols['course_visible'] = get_string('table_course_visible', 'tool_coursewrangler');
        $cols['total_enrol_count'] = get_string('table_total_enrol_count', 'tool_coursewrangler');
        $cols['course_modulescount'] = get_string('table_course_modulescount', 'tool_coursewrangler');
        $cols['course_timeaccess'] = get_string('table_course_timeaccess', 'tool_coursewrangler');
        $cols['percentage'] = get_string('table_course_deletionscore', 'tool_coursewrangler');

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
     * Define table SQL query.
     */
    protected function build_query() {
        global $DB;
        $sqlwhat = [
            'metrics.course_id', 
            'metrics.id', 
            'metrics.course_module_id', 
            'metrics.course_shortname', 
            'metrics.course_fullname', 
            'metrics.course_idnumber', 
            'metrics.course_timecreated', 
            'metrics.course_timemodified', 
            'metrics.course_startdate', 
            'metrics.course_enddate', 
            'metrics.course_visible', 
            'metrics.course_parents', 
            'metrics.course_modulescount', 
            'metrics.course_timeaccess', 
            'metrics.course_lastenrolment', 
            'metrics.activity_type', 
            'metrics.activity_lastmodified', 
            'metrics.total_enrol_count', 
            'metrics.active_enrol_count', 
            'metrics.self_enrol_count', 
            'metrics.manual_enrol_count', 
            'metrics.meta_enrol_count', 
            'metrics.other_enrol_count', 
            'metrics.suspended_enrol_count', 
            'metrics.metrics_updated',
            'score.id', 
            'score.metrics_id', 
            'score.timemodified', 
            'score.raw', 
            'score.rounded', 
            'score.percentage'
        ];
        $sqlfrom = [
            '{tool_coursewrangler_metrics} AS metrics',
            'LEFT JOIN {tool_coursewrangler_score} AS score ON metrics.id=score.metrics_id'
        ];
        $params = [];
        $conditions = [];

        /**
         * We add this join always, so that we can filter out protected courses.
         */
        $sqlfrom[] = 'LEFT JOIN {tool_coursewrangler_action} AS act ON metrics.course_id=act.course_id';
        // Make sure not to double select course_id here, otherwise ambiguous error appears.
        $sqlwhat[] = 'act.id';
        $sqlwhat[] = 'act.action';
        $sqlwhat[] = 'act.status';
        $sqlwhat[] = 'act.lastupdated';

        /**
         * Filter out protected courses by default!
         * 
         * If filter_action_data is empty, the table will
         *  output all courses which aren't protected, else it will
         *  filter through courses.
         */
        if (empty($this->filter_action_data)) {
            $conditions[] = "(act.action != 'protect' OR act.action IS NULL)";
        } else {
            // Filter action data prefix.
            $fad_prefix = 'fad';
            $fad_conditions = [];
            foreach ($this->filter_action_data as $value) {
                if ($value == '_qf__force_multiselect_submission') {
                    // We do this here as well, so that when users select action data,
                    //  but haven't yet selected filters, we by default hide protected
                    //  courses. This way if the user wants to see protected courses
                    //  at the same time as other courses, they have to select all 
                    //  the appropriate filters.
                    $conditions[] = "(act.action != 'protect' OR act.action IS NULL)";
                    continue;
                }
                // To get courses without action we must do it differently
                //  by asking the database for action IS NULL.
                if ($value == 'null') {
                    $fad_conditions[] = "act.action IS NULL";
                    continue;
                }
                $fad_conditions[] = "act.action = :$fad_prefix"."$value";
                $params[$fad_prefix . $value] = $value;
            }
            if (!empty($fad_conditions)) {
                $conditions[] = '(' . implode(' OR ', $fad_conditions) . ')';
            }
        }


        /**
         * Filtering 
         */

        /**
         * Date SQL options.
         * 
         * Course time created filters.
         */
        if ($this->course_timecreated_notset) {
            // If 'notset' option for 'course_timecreated' is set, filter all missing time created.
            $conditions[] = "metrics.course_timecreated = 0";
        } else {
            if (isset($this->course_timecreated_after)) {
                // Option where 'course_timecreated' is AFTER specified date.
                $conditions[] = "metrics.course_timecreated > :coursetimecreatedafter";
                $params['coursetimecreatedafter'] = $this->course_timecreated_after;
            }
            if (isset($this->course_timecreated_before)) {
                // Option where 'course_timecreated' is BEFORE specified date.
                $conditions[] = "metrics.course_timecreated < :coursetimecreatedbefore";
                $params['coursetimecreatedbefore'] = $this->course_timecreated_before;
            }
        }
        /**
         * Course start date filters.
         */
        if ($this->course_startdate_notset) {
            // If 'notset' option for 'course_startdate' is set, filter all missing time created.
            $conditions[] = "metrics.course_startdate = 0";
        } else {
            if (isset($this->course_startdate_after)) {
                // Option where 'course_startdate' is AFTER specified date.
                $conditions[] = "metrics.course_startdate > :coursestartdateafter";
                $params['coursestartdateafter'] = $this->course_startdate_after;
            }
            if (isset($this->course_startdate_before)) {
                // Option where 'course_startdate' is BEFORE specified date.
                $conditions[] = "metrics.course_startdate < :coursestartdatebefore";
                $params['coursestartdatebefore'] = $this->course_startdate_before;
            }
        }
        /**
         * Course end date filters.
         */
        if ($this->course_enddate_notset) {
            // If 'notset' option for 'course_enddate' is set, filter all missing time created.
            $conditions[] = "metrics.course_enddate = 0";
        } else {
            if (isset($this->course_enddate_after)) {
                // Option where 'course_enddate' is AFTER specified date.
                $conditions[] = "metrics.course_enddate > :courseenddateafter";
                $params['courseenddateafter'] = $this->course_enddate_after;
            }
            if (isset($this->course_enddate_before)) {
                // Option where 'course_enddate' is BEFORE specified date.
                $conditions[] = "metrics.course_enddate < :courseenddatebefore";
                $params['courseenddatebefore'] = $this->course_enddate_before;
            }
        }
        /**
         * Course last time access filters.
         */
        if ($this->course_timeaccess_notset) {
            // IF 'notset' option for 'course_timeaccess' is set, filter all missing time created.
            $conditions[] = "metrics.course_timeaccess = 0";
        } else {
            if (isset($this->course_timeaccess_after)) {
                // Option where 'course_timeaccess' is AFTER specified date.
                $conditions[] = "metrics.course_timeaccess > :coursetimeaccessafter";
                $params['coursetimeaccessafter'] = $this->course_timeaccess_after;
            }
            if (isset($this->course_timeaccess_before)) {
                // Option where 'course_timeaccess' is BEFORE specified date.
                $conditions[] = "metrics.course_timeaccess < :coursetimeaccessbefore";
                $params['coursetimeaccessbefore'] = $this->course_timeaccess_before;
            }
        }
        /**
         * String search for course short name and id number.
         */
        if (isset($this->matchstring_short)){
            $query = [];
            $query[] = $DB->sql_like(
                'metrics.course_shortname', 
                ':matchstring_shortname', 
                false
            );
            $query[] = $DB->sql_like(
                'metrics.course_idnumber', 
                ':matchstring_idnumber', 
                false
            );
            $conditions[] = '(' . join(' OR ', $query) . ')';
            $params['matchstring_shortname'] = "%$this->matchstring_short%";
            $params['matchstring_idnumber'] = "%$this->matchstring_short%";

        }
        /**
         * String search for course full name.
         */
        if (isset($this->matchstring_full)){
            $conditions[] = $DB->sql_like(
                'metrics.course_fullname', 
                ':matchstringfull', 
                false
            );
            $params['matchstringfull'] = "%$this->matchstring_full%";
        }

        /**
         * Category filter.
         */
        if (count($this->category_ids) > 0) {
            $categories = [];
            foreach ($this->category_ids as $category_id) {
                if ($category_id <= 0) {
                    continue;
                }
                if ($DB->record_exists("course_categories", ['id' => $category_id])) {
                    $categories[] = $category_id;
                }
            }
            if (count($categories) > 0) {
                $courseids = [];
                foreach ($categories as $id) {
                    $categorycourses = $DB->get_records_sql(
                        "SELECT c.id 
                            FROM {course} AS c  
                            WHERE c.category=:catid;", 
                        ['catid' => $id]
                    );
                    foreach ($categorycourses as $course) {
                        $courseids[] = $course->id;
                    }
                }
                if (count($courseids) > 0) {
                    list($catsql, $catparams) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'coursecatids');
                    $params += $catparams;
                    $conditions[] = "metrics.course_id $catsql";
                } else {
                    // We make conditions 1=0 because it's the best way to display no results 
                    //  without breaking the table.
                    $conditions[] = '1=0';
                }
            }
        }

        /**
         * Hide parent courses.
         */
        if (isset($this->hideshow_meta_parents)) {
            switch($this->hideshow_meta_parents) {
                // Show only parent courses.
                case 'show':
                    $conditions[] = "metrics.course_children IS NOT NULL";
                    break;
                // Hide all parent courses.
                case 'hide':
                    $conditions[] = "metrics.course_children IS NULL";
                    break;
                // Do nothing.
                default:
                    break;
            }
        }

        /**
         * Hide child courses.
         */
        if (isset($this->hideshow_meta_children)) {
            switch($this->hideshow_meta_children) {
                // Show only child courses.
                case 'show':
                    $conditions[] = "metrics.course_parents IS NOT NULL";
                    break;
                // Hide all child courses.
                case 'hide':
                    $conditions[] = "metrics.course_parents IS NULL";
                    break;
                // Do nothing.
                default:
                    break;
            }
        }

        /**
         * Hide visible courses.
         */
        if (isset($this->hideshow_hidden_courses)) {
            switch($this->hideshow_hidden_courses) {
                // Show only visible courses.
                case 'show':
                    $conditions[] = "metrics.course_visible = 1";
                    break;
                // Hide all visible courses.
                case 'hide':
                    $conditions[] = "metrics.course_visible = 0";
                    break;
                // Do nothing.
                default:
                    break;
            }
        }

        // This is required to prevent SQL errors on empty conditions.
        if (empty($conditions)) {
            $conditions[] = '1=1';
        }
        return [$sqlwhat, $sqlfrom, $conditions, $params];
    }
    /**
     * Define table SQL query.
     */
    protected function define_table_sql() {
        list($sqlwhat, $sqlfrom, $conditions, $params) = $this->build_query();
        $sqlwhat = join(', ', $sqlwhat);
        $sqlfrom = join(' ', $sqlfrom);
        $conditions = join(' AND ', $conditions);
        $this->set_sql($sqlwhat, $sqlfrom, $conditions, $params);
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
        $course_visible = $values->course_visible ? 'yes' : 'no';
        $display_value = isset($course_visible) ? "table_visible_$course_visible" : 'table_value_notavailable';
        $display_value_string = get_string($display_value, 'tool_coursewrangler');
        return ($display_value_string);
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
        $display_value = ($values->percentage != null) ? $values->percentage . '%' : get_string('table_percentage_notavailable', 'tool_coursewrangler');
        return ($display_value);
    }
    /**
     * Creating the action col.
     */
    function col_action($values) : string {
        $display_value = isset($values->action) ? "table_action_$values->action" : 'table_value_notavailable';
        $display_value_string = get_string($display_value, 'tool_coursewrangler');
        return ($display_value_string);
    }
    /**
     * Creating the action status col.
     */
    function col_status($values) : string {
        $display_value = (isset($values->status) && $values->status != '')
                            ? "table_status_$values->status"
                            : 'table_value_notavailable';
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
    /**
     * Preparing the report table gives us access to the total records
     *  before outputting table on scree. This was taken from ->out()
     *  function.
     */
    function prepare_report_table($pagesize, $useinitialsbar) {
        global $DB;
        if (!$this->columns) {
            $onerow = $DB->get_record_sql("SELECT {$this->sql->fields} FROM {$this->sql->from} WHERE {$this->sql->where}",
                $this->sql->params, IGNORE_MULTIPLE);
            // If columns is not set then define columns as the keys of the rows returned
            // from the db.
            $this->define_columns(array_keys((array)$onerow));
            $this->define_headers(array_keys((array)$onerow));
        }
        $this->setup();
        $this->query_db($pagesize, $useinitialsbar);
    }
    /**
     * After preparing table, now we can output on screen.
     * Must check totalrows has been set.
     */
    function finish_report_table() {
        if (!isset($this->totalrows) || $this->totalrows < 1) {
            return false;
        }
        $this->build_table();
        $this->close_recordset();
        $this->finish_output();
    }
}
