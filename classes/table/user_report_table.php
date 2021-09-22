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
    public function __construct(\moodle_url $baseurl, array $params = []) {
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
        if ($this->courseids == 0 || empty($this->courseids)) {
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
    protected function define_table_columns() {
        $cols = [
            'courseid' => get_string('table_courseid', 'tool_coursewrangler'),
            'courseidnumber' => get_string('table_courseidnumber', 'tool_coursewrangler'),
            'courseshortname' => get_string('table_courseshortname', 'tool_coursewrangler'),
            'coursefullname' => get_string('table_coursefullname', 'tool_coursewrangler'),
            'coursetimecreated' => get_string('table_coursetimecreated', 'tool_coursewrangler'),
            'action' => get_string('table_course_action', 'tool_coursewrangler'),
            'status' => get_string('table_course_status', 'tool_coursewrangler'),
            'lastupdated' => get_string('table_action_lastupdated', 'tool_coursewrangler')
        ];

        $this->define_columns(array_keys($cols));
        $this->define_headers(array_values($cols));
    }
    /**
     * Define table configs.
     */
    protected function define_table_configs() {
        $this->sortable(true, 'courseid', SORT_ASC);
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
        if ($column != 'courseid') {
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
            '{tool_coursewrangler_metrics}.courseid',
            '{tool_coursewrangler_metrics}.id',
            '{tool_coursewrangler_metrics}.courseshortname',
            '{tool_coursewrangler_metrics}.coursefullname',
            '{tool_coursewrangler_metrics}.courseidnumber',
            '{tool_coursewrangler_metrics}.coursetimecreated',
            '{tool_coursewrangler_action}.id',
            '{tool_coursewrangler_action}.action',
            '{tool_coursewrangler_action}.status',
            '{tool_coursewrangler_action}.lastupdated'
        ];
        $sqlfrom = [
            '{tool_coursewrangler_metrics}',
            'LEFT JOIN {tool_coursewrangler_action} ON {tool_coursewrangler_metrics}.courseid={tool_coursewrangler_action}.courseid'
        ];
        $params = [];
        $conditions = [];
        $cidssql = '';
        $cidsparams = [];
        // We must filter the user's course ids from their enrolments.
        if (!empty($this->courseids)) {
            list($cidssql, $cidsparams) = $DB->get_in_or_equal($this->courseids, SQL_PARAMS_NAMED, 'cids');
        }
        if (!empty($cidsparams)) {
            $params += $cidsparams;
        }
        if ($cidssql != '') {
            $conditions[] = "{tool_coursewrangler_metrics}.courseid $cidssql";
        }
        // Now we only want to see the ones that have been marked for deletion
        // and the user has at least been notified, scheduled ones might not
        // be confirmed for deletion yet.
        $params['status'] = 'notified';
        $conditions[] = "{tool_coursewrangler_action}.status = :status";
        $params['delete'] = 'delete';
        $conditions[] = "{tool_coursewrangler_action}.action = :delete";
        // We do not want to confuse users by showing them hidden courses.
        // Could we do something to check this against real data, instead of metrics?
        $params['visible'] = '1';
        $conditions[] = "{tool_coursewrangler_metrics}.coursevisible = :visible";
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

    public function col_coursetimecreated($values) : string {
        return ($values->coursetimecreated == 0) ? '-' : userdate($values->coursetimecreated);
    }
    /**
     * Turning course name into link for details area.
     */
    public function col_coursefullname($values): string {
        $url = new moodle_url('/course/view.php?id=' . $values->courseid);
        $link = html_writer::link($url, $values->coursefullname);
        return $link;
    }
    /**
     * Creating the action col.
     */
    public function col_action($values): string {
        $displayvalue = isset($values->action) ? "table_action_$values->action" : 'table_value_notavailable';
        $displayvaluestring = get_string($displayvalue, 'tool_coursewrangler');
        return ($displayvaluestring);
    }
    /**
     * Creating the action status col.
     */
    public function col_status($values) : string {
        $displayvalue = (isset($values->status) && $values->status != '')
                            ? "table_status_$values->status"
                            : 'table_value_notavailable';
        $displayvaluestring = get_string($displayvalue, 'tool_coursewrangler');
        return ($displayvaluestring);
    }

    /**
     * Creating the action status col.
     */
    public function col_lastupdated($values) : string {
        $lastupdated = $values->lastupdated;
        $scheduledperiod = get_config('tool_coursewrangler', 'scheduledduration');
        $toberun = $scheduledperiod + $lastupdated;
        $displayvaluestring = userdate($toberun);
        return ($displayvaluestring);
    }
}
