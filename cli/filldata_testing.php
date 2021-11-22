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

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->libdir . '/adminlib.php');
require_once(__DIR__ . '/../locallib.php');
$context = context_system::instance();
$starttime = time();

echo 'Starting tool_coursewrangler filldata test script.' . PHP_EOL;

global $DB;
mtrace(">>> Starting " . '.');
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
mtrace("Printing object");
print_object($coursedata[88533]);
exit;
$dbendtime = time();
mtrace('Queries took a total of: ' . ($dbendtime - $starttime) . ' seconds');
mtrace('Creating metrics data.');
foreach ($coursedata as $data) {
    $fetchmetric = $DB->get_record(
        'tool_coursewrangler_metrics',
        ['courseid' => $data->courseid]
    );
    if (!$fetchmetric) {
        // This is a new entry.
        $data->metricsupdated = time();
        $DB->insert_record(
            'tool_coursewrangler_metrics',
            $data,
            true
        ) ?? false;
        continue;
    }
    // Compare data to highlight changes.
    $comparedata = $data;
    $comparedata->id = $fetchmetric->id;
    unset($fetchmetric->metricsupdated);
    $changeddata = new \stdClass();
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
mtrace(">>> Finishing ");

$elapsed = time() - $starttime;
echo "Finished script in $elapsed seconds." . PHP_EOL;