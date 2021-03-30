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
 * This is a tool for managing retiring old courses, and categories.
 * 
 * @package   tool_coursewrangler
 * @author    Hugo Soares <h.soares@chi.ac.uk>
 * @copyright 2020 University of Chichester {@link www.chi.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_coursewrangler;

use context_system;
use moodle_url;
use flexible_table;
use stdClass;

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once(__DIR__ . '/locallib.php');
require_once($CFG->libdir . '/tablelib.php');
$context = context_system::instance();

// require_capability('moodle/course:manageactivities', $coursecontext);

$PAGE->set_context($context);
$PAGE->set_heading(get_string('pageheading', 'tool_coursewrangler'));
$PAGE->set_url(new moodle_url('/admin/tool/coursewrangler/dev.php'));
$PAGE->set_title(get_string('pageheader', 'tool_coursewrangler'));
$PAGE->set_pagelayout('admin');

echo $OUTPUT->header();
// OUTPUT HERE
$score_handler = new deletion_score(find_relevant_course_data_lite());
$courses = $score_handler->get_courses();
// Sorts descending by raw score
usort($courses, function ($item1, $item2) {
    return $item2->score->raw <=> $item1->score->raw;
});
$report = new stdClass;
$report->timecreated = time();
$report->type = 'test';
$report_id = $DB->insert_record('tool_coursewrangler_report', $report, true);
echo $report_id . '<br>';
foreach ($courses as $data) {
    $data->report_id = $report_id;
    $data->enrolmt_id = $DB->insert_record('tool_coursewrangler_enrolmt', $data->course_students, true) ?? false;
    if (!isset($data->enrolmt_id) || $data->enrolmt_id == false || $data->enrolmt_id <= 0) {
        continue;
    }
    unset($data->rules);
    unset($data->score);
    $entry_id = $DB->insert_record('tool_coursewrangler_coursemt', $data, true) ?? false;
    echo $data->course_fullname . PHP_EOL;
    echo $entry_id . PHP_EOL;
    echo '<br>';
}
echo 'done';
echo $OUTPUT->footer();
