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

echo 'tool_coursewrangler ::: Generating score entries script' . PHP_EOL;

$data = $DB->get_records('tool_coursewrangler_metrics');
$scorekeeper = new deletion_score($data);
$courses = $scorekeeper->get_courses();

$scores = [];
foreach ($courses as $metrics) {
    $scoredata = [
        'metrics_id' => $metrics->id,
        'timemodified' => time(),
        'raw' => $metrics->score->raw,
        'rounded' => $metrics->score->rounded,
        'percentage' => $metrics->score->percentage,
    ];
    $DB->insert_record('tool_coursewrangler_score', $scoredata, true) ?? false;
}
$time = time();
// file_put_contents("./score_$time.json", json_encode($courses));
echo 'End of script.' . PHP_EOL;
