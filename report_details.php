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

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once(__DIR__ . '/locallib.php');
require_once($CFG->libdir . '/tablelib.php');
$context = context_system::instance();

require_capability('moodle/site:configview', $context);

$course_id = required_param('course_id', PARAM_INT);

$return_link = optional_param('return_link', null, PARAM_URL);

$PAGE->set_context($context);
$PAGE->set_heading(get_string('pageheading', 'tool_coursewrangler'));
$PAGE->set_url(new moodle_url('/admin/tool/coursewrangler/report_details.php'));
$PAGE->set_title(get_string('report_details_pageheader', 'tool_coursewrangler'));
$PAGE->set_pagelayout('admin');

global $DB;
$course = $DB->get_record_sql('SELECT * FROM {tool_coursewrangler_metrics} WHERE course_id=:course_id', ['course_id' => $course_id]);
// creating course link
$course_url = new moodle_url('/course/view.php?id=' . $course->course_id, []);
$course->course_title_link = \html_writer::link($course_url, $course->course_shortname . ': ' . $course->course_fullname);
// Processing dates into human readable format
$course->course_timecreated = ($course->course_timecreated == 0) ?  '-' : userdate($course->course_timecreated);
$course->course_timemodified = ($course->course_timemodified == 0) ?  '-' : userdate($course->course_timemodified);
$course->course_startdate = ($course->course_startdate == 0) ?  '-' : userdate($course->course_startdate);
$course->course_enddate = ($course->course_enddate == 0) ?  '-' : userdate($course->course_enddate);
$course->course_timeaccess = ($course->course_timeaccess == 0) ?  '-' : userdate($course->course_timeaccess);
$course->course_lastenrolment = ($course->course_lastenrolment == 0) ?  '-' : userdate($course->course_lastenrolment);
$course->activity_lastmodified = ($course->activity_lastmodified == 0) ?  '-' : userdate($course->activity_lastmodified);
// Processing visible and parent
$course->course_visible = ($course->course_visible == 0) ? 'No' : 'Yes';
$course->course_isparent = ($course->course_isparent == 0) ? 'No' : 'Yes';
$course->score = $DB->get_record_sql('SELECT * FROM {tool_coursewrangler_score} WHERE metrics_id=:metrics_id ', ['metrics_id' => $course->id]);
if ($course->score->timemodified == 0) {
    $course->score = null;
} else {
    $course->score->timemodified = userdate($course->score->timemodified);
}

if ($course == false) {
    // throw not found error?
}

$course->links = [
    'return_link' => $return_link
];

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('report_details_coursedetailsfor', 'tool_coursewrangler'));

echo $OUTPUT->render_from_template('tool_coursewrangler/report_details', $course);

echo $OUTPUT->footer();
