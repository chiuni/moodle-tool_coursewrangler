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
 * This file is a command line script example.
 *
 * @package   tool_coursewrangler
 * @author    Hugo Soares <h.soares@chi.ac.uk>
 * @copyright 2020 University of Chichester {@link www.chi.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// More info: https://docs.moodle.org/dev/Plugin_files#cli.2F

// >>> THIS FILE IS ONLY FOR DEVELOPMENT, NOT PART OF PLUGIN <<<

namespace tool_coursewrangler;

use context_system;
use stdClass;

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->libdir . '/adminlib.php');
require_once(__DIR__ . '/../locallib.php');
$context = context_system::instance();

$starttime = time();
$starttimeformatted = date('r', $starttime);
echo PHP_EOL .'tool_coursewrangler ::: Gather Course Data PHP Script' . PHP_EOL;
echo '=====================================================' . PHP_EOL;
echo '=============== Starting DB Queries =================' . PHP_EOL;
echo '=====================================================' . PHP_EOL;
\core_php_time_limit::raise();
echo "Start time: $starttimeformatted" . PHP_EOL . PHP_EOL;
$coursedata = find_relevant_coursedata_lite();
$metrics_data = $DB->get_records('tool_coursewrangler_metrics');
$dbendtime = time();
echo 'Queries took a total of: ' . ($dbendtime - $starttime) . ' seconds' . PHP_EOL;
echo 'Creating metrics data.' . PHP_EOL;
foreach ($coursedata as $data) {
    $fetchmetric = $DB->get_record('tool_coursewrangler_metrics', ['courseid' => $data->courseid]);
    $data->metricsupdated = time();
    if (!$fetchmetric) {
        $entry_id = $DB->insert_record('tool_coursewrangler_metrics', $data, true) ?? false;
        continue;
    }
    $data->id = $fetchmetric->id;
    $DB->update_record('tool_coursewrangler_metrics', $data);

}
$scriptendtime = time();
exit;
$score_handler = new deletion_score($coursedata);
$courses = $score_handler->get_courses();
echo 'End of script.' . PHP_EOL;
echo 'Triggering generate_score.php...' . PHP_EOL;
shell_exec("php7.4 ./generate_score.php");