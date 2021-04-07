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
 * This file defines observers needed by the tool.
 * 
 * @package   tool_coursewrangler
 * @author    Hugo Soares <h.soares@chi.ac.uk>
 * @copyright 2020 University of Chichester {@link www.chi.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_tool_coursewrangler_install() {
    // This install script generates the initial metrics load.
    if (!CLI_SCRIPT) {
        $eol = '<br>'; 
    }
    $format = "r";
    $start_time = time();
    $start_time_formatted = date($format, $start_time);
    mtrace('======================================================', $eol);
    mtrace('============ Installing Course Wrangler ==============', $eol);
    mtrace('======================================================', $eol);
    mtrace(">>> Start time: $start_time_formatted", $eol);
    mtrace('>>> Gathering course data...', $eol);
    $dbq_start_time = time();
    $course_data = tool_coursewrangler\find_relevant_course_data_lite();
    $dbq_end_time = time();
    mtrace('>>> Select queries took ' . ($dbq_end_time - $dbq_start_time) . ' seconds.', $eol);
    mtrace('>>> Inserting metrics data...', $eol);
    global $DB;
    $ins_start_time = time();
    foreach ($course_data as $data) {
        $data->metrics_updated = time();
        $DB->insert_record('tool_coursewrangler_metrics', $data, true) ?? false;
    }
    $ins_end_time = time();
    mtrace('>>> Insert queries took ' . ($ins_end_time - $ins_start_time) . ' seconds.', $eol);
    $end_time = time();
    mtrace('>>> Finished install.php, took ' . ($end_time - $start_time) . ' seconds.', $eol);
}