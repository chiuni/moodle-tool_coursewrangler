<?php

/**
 * Simple file test.php to drop into root of Moodle installation.
 * This is the skeleton code to print a downloadable, paged, sorted table of
 * data from a sql query.
 */

namespace tool_coursewrangler;

use moodle_url;
use context_system;

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/tablelib.php');
require_once(__DIR__ . '/locallib.php');
$context = context_system::instance();

$report_id = (int) optional_param('report_id', 0, PARAM_INT);
$category_ids = optional_param('category_ids', null, PARAM_RAW);
$category_ids = is_array($category_ids) ? $category_ids : (array) explode(',', $category_ids);

// dates optional params
$course_timecreated_after = optional_param('course_timecreated_after', -1, PARAM_INT);
$course_timecreated_before = optional_param('course_timecreated_before', -1, PARAM_INT);
$course_startdate_after = optional_param('course_startdate_after', -1, PARAM_INT);
$course_startdate_before = optional_param('course_startdate_before', -1, PARAM_INT);
$course_enddate_after = optional_param('course_enddate_after', -1, PARAM_INT);
$course_enddate_before = optional_param('course_enddate_before', -1, PARAM_INT);

// turning dates into timestamps
$course_timecreated_after = $course_timecreated_after['enabled'] == 1 ? moodletime_to_unixtimestamp($course_timecreated_after) : $course_timecreated_after;
$course_timecreated_before = $course_timecreated_before['enabled'] == 1 ? moodletime_to_unixtimestamp($course_timecreated_before) : $course_timecreated_before;
$course_startdate_after = $course_startdate_after['enabled'] == 1 ? moodletime_to_unixtimestamp($course_startdate_after) : $course_startdate_after;
$course_startdate_before = $course_startdate_before['enabled'] == 1 ? moodletime_to_unixtimestamp($course_startdate_before) : $course_startdate_before;
$course_enddate_after = $course_enddate_after['enabled'] == 1 ? moodletime_to_unixtimestamp($course_enddate_after) : $course_enddate_after;
$course_enddate_before = $course_enddate_before['enabled'] == 1 ? moodletime_to_unixtimestamp($course_enddate_before) : $course_enddate_before;

// TODO OPTIMISE THIS
if ($report_id == 0) {
    $report = $DB->get_records_sql("SELECT * FROM {tool_coursewrangler_report} ORDER BY timecreated DESC");
    foreach ($report as $first) {
        $report_id = $first->id;
        break;
    }
}

// require_capability('moodle/course:manageactivities', $coursecontext);

$PAGE->set_context($context);
$PAGE->set_heading(get_string('pageheading', 'tool_coursewrangler'));
$PAGE->set_url(new moodle_url('/admin/tool/coursewrangler/table.php'));
$PAGE->set_title(get_string('pageheader', 'tool_coursewrangler'));
$PAGE->set_pagelayout('admin');
$PAGE->navbar->add(get_string('administrationsite'), new moodle_url('/admin/search.php'));
$PAGE->navbar->add(get_string('pluginname', 'tool_coursewrangler'), new moodle_url('/admin/tool/coursewrangler/index.php'));
$PAGE->navbar->add(get_string('table', 'tool_coursewrangler'), new moodle_url('/admin/tool/coursewrangler/table.php'));

// Print the page header
// $PAGE->navbar->add('Testing table class', new moodle_url('/admin/tool/coursewrangler/table.php'));
echo $OUTPUT->header();

//Instantiate simplehtml_form 
$mform = new form\report_form(null, ['report_id' => $report_id, 'category_ids' => $category_ids], 'get');

//Set default data (if any)
$mform->set_data(['report_id' => $report_id, 'category_ids' => $category_ids]);
//displays the form
$mform->display();

//Form processing and displaying is done here
if ($mform->is_cancelled()) {
    //Handle form cancel operation, if cancel button is present on form
} else if ($fromform = $mform->get_data()) {
    $report_id = $fromform->report_id ?? $report_id;
    //In this case you process validated data. $mform->get_data() returns data posted in form.
} else {
    // this branch is executed if the form is submitted but the data doesn't validate and the form should be redisplayed
    // or on the first display of the form.
}

$table_options = [];
$table_options['category_ids'] = $category_ids ?? [];
$table_options['course_timecreated_after'] = $course_timecreated_after > 0 ? $course_timecreated_after : null;
$table_options['course_timecreated_before'] = $course_timecreated_before > 0 ? $course_timecreated_before : null;
$table_options['course_startdate_after'] = $course_startdate_after > 0 ? $course_startdate_after : null;
$table_options['course_startdate_before'] = $course_startdate_before > 0 ? $course_startdate_before : null;
$table_options['course_enddate_after'] = $course_enddate_after > 0 ? $course_enddate_after : null;
$table_options['course_enddate_before'] = $course_enddate_before > 0 ? $course_enddate_before : null;

$table = new table\report_table(
    new moodle_url(
        '/admin/tool/coursewrangler/table.php?report_id=' . $report_id . '&category_ids=' . implode(',', $category_ids)
    ),
    $report_id,
    $table_options
);
$table->out(50, false);

echo $OUTPUT->footer();
