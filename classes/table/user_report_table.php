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
            'course_idnumber' => get_string('table_course_idnumber', 'tool_coursewrangler'),
            'course_shortname' => get_string('table_course_shortname', 'tool_coursewrangler'),
            'course_fullname' => get_string('table_course_fullname', 'tool_coursewrangler'),
            'course_timecreated' => get_string('table_course_timecreated', 'tool_coursewrangler'),
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
    protected function build_query() {
        global $DB;
        $sqlwhat = [
            'metrics.course_id', 
            'metrics.id', 
            'metrics.course_shortname', 
            'metrics.course_fullname', 
            'metrics.course_idnumber', 
            'metrics.course_timecreated', 
            'act.id',
            'act.action',
            'act.status',
            'act.lastupdated'
        ];
        $sqlfrom = [
            '{tool_coursewrangler_metrics} AS metrics',
            'LEFT JOIN {tool_coursewrangler_action} AS act ON metrics.course_id=act.course_id'
        ];
        $params = [];
        $conditions = [];
        // We must filter the user's course ids from their enrolments.
        list($cids_sql, $cids_params) = $DB->get_in_or_equal($this->courseids, SQL_PARAMS_NAMED, 'cids');
        $params += $cids_params;
        $conditions[] = "metrics.course_id $cids_sql";
        // Now we only want to see the ones that have been marked for deletion
        //  and the user has at least been notified, scheduled ones might not
        //  be confirmed for deletion yet.
        $params['scheduled'] = 'scheduled';
        $conditions[] = "act.status != :scheduled";
        $params['delete'] = 'delete';
        $conditions[] = "act.action = :delete";
        return [$sqlwhat, $sqlfrom, $conditions, $params];
    }
    /**
     * Define table SQL.
     */
    protected function define_table_sql() {
        list($sqlwhat, $sqlfrom, $conditions, $params) = $this->build_query();
        $sqlwhat = join(', ', $sqlwhat);
        $sqlfrom = join(' ', $sqlfrom);
        $conditions = join(' AND ', $conditions);
        $this->set_sql($sqlwhat, $sqlfrom, $conditions, $params);
    }

    function col_course_timecreated($values) : string {
        return ($values->course_timecreated == 0) ? '-' : userdate($values->course_timecreated);
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
    function col_status($values) : string {
        $display_value = (isset($values->status) && $values->status != '')
                            ? "table_status_$values->status"
                            : 'table_value_notavailable';
        $display_value_string = get_string($display_value, 'tool_coursewrangler');
        return ($display_value_string);
    }
}
