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
 * @author    Mark Sharp <m.sharp@chi.ac.uk>
 * @copyright 2020 University of Chichester {@link www.chi.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_coursewrangler;

use context_system;
use moodle_url;

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once(__DIR__ . '/locallib.php');
$context = context_system::instance();

// require_capability('moodle/course:manageactivities', $coursecontext);

$PAGE->set_context($context);
$PAGE->set_heading(get_string('pageheading', 'tool_coursewrangler'));
$PAGE->set_url(new moodle_url('/admin/tool/coursewrangler/index.php'));
$PAGE->set_title(get_string('pageheader', 'tool_coursewrangler'));
$PAGE->set_pagelayout('admin');

echo $OUTPUT->header();

// OUTPUT HERE
echo "let's get some db queries going<br> ";
global $DB;
// months ago
$timegap12 = time() - (86400 * 30 * 12);
$timegap24 = time() - (86400 * 30 * 24);
$timegap70 = time() - (86400 * 30 * 70);
echo "time12=" . $timegap12 . "<br>";
echo "time24=" . $timegap24;
$query = find_relevant_course_data_lite();

// $query = find_last_enrolment(44); 
echo '<pre>:::<br>';
echo 'activities query:<br>';
print_r($query, 0);
// print_r($la_query, 0);
echo 'la query:<br>';
// print_r($la_query, 0);
echo '</pre>';

echo $OUTPUT->footer();
