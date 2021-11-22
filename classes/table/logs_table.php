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
class logs_table extends table_sql implements renderable
{
    /**
     * Sets up the table.
     */
    public function __construct(\moodle_url $baseurl, array $params = []) {
        parent::__construct('tool_coursewrangler-report');
        $this->context = \context_system::instance();
        // This object should not be used without the right permissions.
        // [TODO] THIS:
        // require_capability(
        //     'moodle/badges:manageglobalsettings',
        //     $this->context
        // );

        // Define columns and headers in the table.
        $this->define_table_columns();

        // Define configs.
        $this->define_table_configs();

        $this->define_baseurl($baseurl);
        $this->define_table_sql();
    }
    /**
     * Setup the headers for the table.
     */
    protected function define_table_columns() {
        $cols = [
            'id' => get_string(
                'table_log_id',
                'tool_coursewrangler'
            ),
            'actor' => get_string(
                'table_log_actor',
                'tool_coursewrangler'
            ),
            'description' => get_string(
                'table_log_description',
                'tool_coursewrangler'
            ),
            'timestamp' => get_string(
                'table_log_timestamp',
                'tool_coursewrangler'
            ),
            'metrics_id' => get_string(
                'table_log_metrics_id',
                'tool_coursewrangler'
            )
        ];

        $this->define_columns(array_keys($cols));
        $this->define_headers(array_values($cols));
    }
    /**
     * Define table configs.
     */
    protected function define_table_configs() {
        $this->sortable(true, 'id', SORT_DESC);
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
        $sqlwhat = [
            '{tool_coursewrangler_log}.id',
            '{tool_coursewrangler_log}.actor',
            '{tool_coursewrangler_log}.description',
            '{tool_coursewrangler_log}.timestamp',
            '{tool_coursewrangler_log}.metrics_id'
        ];
        $sqlfrom = [
            '{tool_coursewrangler_log}'
        ];
        $params = [];
        $conditions = ['id > 0'];
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

    public function col_timestamp($values) : string {
        return ($values->timestamp == 0) ? '-' : userdate($values->timestamp);
    }

    public function col_metrics_id($values) : string {
        return ($values->metrics_id == 0) ? '-' : $values->metrics_id;
    }
}
