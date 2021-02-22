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


$table = new table\report_table(
    new moodle_url(
        '/admin/tool/coursewrangler/table.php?report_id=' . $report_id . '&category_ids=' . implode(',', $category_ids)
    ),
    $report_id,
    $category_ids
);
$table->out(50, false);

echo $OUTPUT->footer();
