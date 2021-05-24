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
 * This file is the task that grabs data from Moodle into course wrangler.
 * @package   tool_coursewrangler
 * @author    Hugo Soares <h.soares@chi.ac.uk>
 * @copyright 2020 University of Chichester {@link www.chi.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace tool_coursewrangler\task;

use stdClass;

use function tool_coursewrangler\find_relevant_coursedata_lite;

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

        $starttime = time();
        $starttimeformatted = date('r', $starttime);
        mtrace('tool_coursewrangler ::: Gather Course Data PHP Script');
        mtrace('=====================================================');
        mtrace('=============== Starting DB Queries =================');
        mtrace('=====================================================');
        \core_php_time_limit::raise();
        mtrace("Start time: $starttimeformatted");
        $coursedata = find_relevant_coursedata_lite();
        $dbendtime = time();
        mtrace('Queries took a total of: ' . ($dbendtime - $starttime) . ' seconds');
        mtrace('Creating metrics data.');
        foreach ($coursedata as $data) {
            $fetchmetric = $DB->get_record('tool_coursewrangler_metrics', ['courseid' => $data->courseid]);
            if (!$fetchmetric) {
                // This is a new entry.
                $data->metricsupdated = time();
                $DB->insert_record('tool_coursewrangler_metrics', $data, true) ?? false;
                continue;
            }
            // Compare data to highlight changes.
            $comparedata = $data;
            $comparedata->id = $fetchmetric->id;
            unset($fetchmetric->metricsupdated);
            $changeddata = new stdClass;
            $diff = false;
            foreach ($fetchmetric as $key => $value) {
                if ($value != $comparedata->$key) {
                    mtrace("change detected: ". $key);
                    $changeddata->$key = $comparedata->$key;
                    $diff = true;
                }
            }
            if (!$diff) {
                continue;
            }
            $changeddata->id = $fetchmetric->id;
            $changeddata->metricsupdated = time();
            $DB->update_record('tool_coursewrangler_metrics', $changeddata);
        }
        $scriptendtime = time();

        mtrace('>>> Script took ' . ($scriptendtime - $starttime) . ' seconds.');
        mtrace(">>> Finishing " . $this->get_name() . '.');
    }
}