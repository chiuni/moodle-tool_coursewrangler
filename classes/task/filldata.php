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
 * This file is a ...
 * 
 * @package   tool_coursewrangler
 * @author    Hugo Soares <h.soares@chi.ac.uk>
 * @copyright 2020 University of Chichester {@link www.chi.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace tool_coursewrangler\task;

use stdClass;

use function tool_coursewrangler\find_relevant_course_data_lite;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->dirroot/admin/tool/coursewrangler/locallib.php");

class filldata extends \core\task\scheduled_task {

    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name() {
        return get_string('task_filldata', 'tool_coursewrangler');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        global $DB;
        mtrace(">>> Starting " . $this->get_name() . '.');
        mtrace('>>> Calculating score...');
        
        $start_time = time();
        $start_time_formatted = date('r', $start_time);
        mtrace('tool_coursewrangler ::: Gather Course Data PHP Script');
        mtrace('=====================================================');
        mtrace('=============== Starting DB Queries =================');
        mtrace('=====================================================');
        \core_php_time_limit::raise();
        mtrace("Start time: $start_time_formatted");
        $course_data = find_relevant_course_data_lite();
        $db_end_time = time();
        mtrace('Queries took a total of: ' . ($db_end_time - $start_time) . ' seconds');
        mtrace('Creating metrics data.');
        foreach ($course_data as $data) {
            $fetch_metric = $DB->get_record('tool_coursewrangler_metrics', ['course_id' => $data->course_id]);
            if (!$fetch_metric) {
                // This is a new entry.
                $data->metrics_updated = time();
                $DB->insert_record('tool_coursewrangler_metrics', $data, true) ?? false;
                continue;
            }
            // Compare data to highlight changes.
            $compare_data = $data;
            $compare_data->id = $fetch_metric->id;
            unset($fetch_metric->metrics_updated);
            $changed_data = new stdClass;
            $diff = false;
            foreach ($fetch_metric as $key => $value) {
                if ($value != $compare_data->$key) {
                    mtrace("change detected: ". $key);
                    $changed_data->$key = $compare_data->$key;
                    $diff = true;
                }
            }
            if (!$diff) {
                continue;
            }
            $changed_data->id = $fetch_metric->id;
            $changed_data->metrics_updated = time();
            $DB->update_record('tool_coursewrangler_metrics', $changed_data);
        }
        $script_end_time = time();

        mtrace('>>> Script took ' . ($script_end_time - $start_time) . ' seconds.');
        mtrace(">>> Finishing " . $this->get_name() . '.');
    }
}