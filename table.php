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

// Report_form_data_json is reponsible for allowing cross-form
// parameter sharing, to remember what the user options were.
$report_form_data_json = optional_param('report_form_data_json', null, PARAM_RAW);
$report_form_data_json = is_null($report_form_data_json) ? null : json_decode($report_form_data_json);

$action = optional_param('action', null, PARAM_RAW);
$rows_selected = optional_param('rows_selected', null, PARAM_RAW);
$rows_selected = is_array($rows_selected) ? $rows_selected : array_filter( (array) explode(',', $rows_selected) );

$report_id = (int) optional_param('report_id', 0, PARAM_INT);
// $report_id = $report_id ?? $rfd['report_id'] ?? 0;
$category_ids = optional_param('category_ids', null, PARAM_RAW);
$category_ids = is_array($category_ids) ? $category_ids : (array) explode(',', $category_ids);
// $category_ids = $category_ids ?? $rfd['category_ids'] ?? null;

// Dates optional params.
$course_timecreated_after = optional_param('course_timecreated_after', null, PARAM_INT);
$course_timecreated_after = $course_timecreated_after ?? $report_form_data_json->course_timecreated_after ?? false;
$course_timecreated_before = optional_param('course_timecreated_before', null, PARAM_INT);
$course_timecreated_before = $course_timecreated_before ?? $report_form_data_json->course_timecreated_before ?? false;
$course_startdate_after = optional_param('course_startdate_after', null, PARAM_INT);
$course_startdate_after = $course_startdate_after ?? $report_form_data_json->course_startdate_after ?? false;
$course_startdate_before = optional_param('course_startdate_before', null, PARAM_INT);
$course_startdate_before = $course_startdate_before ?? $report_form_data_json->course_startdate_before ?? false;
$course_enddate_after = optional_param('course_enddate_after', null, PARAM_INT);
$course_enddate_after = $course_enddate_after ?? $report_form_data_json->course_enddate_after ?? false;
$course_enddate_before = optional_param('course_enddate_before', null, PARAM_INT);
$course_enddate_before = $course_enddate_before ?? $report_form_data_json->course_enddate_before ?? false;
$course_timeaccess_after = optional_param('course_timeaccess_after', null, PARAM_INT);
$course_timeaccess_after = $course_timeaccess_after ?? $report_form_data_json->course_timeaccess_after ?? false;
$course_timeaccess_before = optional_param('course_timeaccess_before', null, PARAM_INT);
$course_timeaccess_before = $course_timeaccess_before ?? $report_form_data_json->course_timeaccess_before ?? false;

// Turning dates into timestamps.
$course_timecreated_after = $course_timecreated_after['enabled'] == 1 ? moodletime_to_unixtimestamp($course_timecreated_after) : $course_timecreated_after;
$course_timecreated_before = $course_timecreated_before['enabled'] == 1 ? moodletime_to_unixtimestamp($course_timecreated_before) : $course_timecreated_before;
$course_startdate_after = $course_startdate_after['enabled'] == 1 ? moodletime_to_unixtimestamp($course_startdate_after) : $course_startdate_after;
$course_startdate_before = $course_startdate_before['enabled'] == 1 ? moodletime_to_unixtimestamp($course_startdate_before) : $course_startdate_before;
$course_enddate_after = $course_enddate_after['enabled'] == 1 ? moodletime_to_unixtimestamp($course_enddate_after) : $course_enddate_after;
$course_enddate_before = $course_enddate_before['enabled'] == 1 ? moodletime_to_unixtimestamp($course_enddate_before) : $course_enddate_before;
$course_timeaccess_after = $course_timeaccess_after['enabled'] == 1 ? moodletime_to_unixtimestamp($course_timeaccess_after) : $course_timeaccess_after;
$course_timeaccess_before = $course_timeaccess_before['enabled'] == 1 ? moodletime_to_unixtimestamp($course_timeaccess_before) : $course_timeaccess_before;

// Validating timestamps.
$course_timecreated_after = $course_timecreated_after > 0 ? $course_timecreated_after : null;
$course_timecreated_before = $course_timecreated_before > 0 ? $course_timecreated_before : null;
$course_startdate_after = $course_startdate_after > 0 ? $course_startdate_after : null;
$course_startdate_before = $course_startdate_before > 0 ? $course_startdate_before : null;
$course_enddate_after = $course_enddate_after > 0 ? $course_enddate_after : null;
$course_enddate_before = $course_enddate_before > 0 ? $course_enddate_before : null;
$course_timeaccess_after = $course_timeaccess_after > 0 ? $course_timeaccess_after : null;
$course_timeaccess_before = $course_timeaccess_before > 0 ? $course_timeaccess_before : null;

// Flag parameters.
$course_timecreated_notset = optional_param('course_timecreated_notset', null, PARAM_BOOL);
$course_timecreated_notset = $course_timecreated_notset ?? $report_form_data_json->course_timecreated_notset ?? false;
$course_startdate_notset = optional_param('course_startdate_notset', null, PARAM_BOOL);
$course_startdate_notset = $course_startdate_notset ?? $report_form_data_json->course_startdate_notset ?? false;
$course_enddate_notset = optional_param('course_enddate_notset', null, PARAM_BOOL);
$course_enddate_notset = $course_enddate_notset ?? $report_form_data_json->course_enddate_notset ?? false;
$course_timeaccess_notset = optional_param('course_timeaccess_notset', null, PARAM_BOOL);
$course_timeaccess_notset = $course_timeaccess_notset ?? $report_form_data_json->course_timeaccess_notset ?? false;

// Other settings parameters.
$display_action_data = optional_param('display_action_data', null, PARAM_BOOL);
$display_action_data = $display_action_data ?? $report_form_data_json->display_action_data ?? false;

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
$PAGE->navbar->add(get_string('table_tablename', 'tool_coursewrangler'), new moodle_url('/admin/tool/coursewrangler/table.php'));

// Print the page header.
// $PAGE->navbar->add('Testing table class', new moodle_url('/admin/tool/coursewrangler/table.php'));
echo $OUTPUT->header();

$options_array = [];
$options_array['report_id'] = $report_id;
$options_array['category_ids'] = $category_ids ?? [];
$options_array['course_timecreated_after'] = $course_timecreated_after > 0 ? $course_timecreated_after : null;
$options_array['course_timecreated_before'] = $course_timecreated_before > 0 ? $course_timecreated_before : null;
$options_array['course_startdate_after'] = $course_startdate_after > 0 ? $course_startdate_after : null;
$options_array['course_startdate_before'] = $course_startdate_before > 0 ? $course_startdate_before : null;
$options_array['course_enddate_after'] = $course_enddate_after > 0 ? $course_enddate_after : null;
$options_array['course_enddate_before'] = $course_enddate_before > 0 ? $course_enddate_before : null;
$options_array['course_timeaccess_after'] = $course_timeaccess_after > 0 ? $course_timeaccess_after : null;
$options_array['course_timeaccess_before'] = $course_timeaccess_before > 0 ? $course_timeaccess_before : null;
$options_array['course_timecreated_notset'] = $course_timecreated_notset ?? false;
$options_array['course_startdate_notset'] = $course_startdate_notset ?? false;
$options_array['course_endate_notset'] = $course_endate_notset ?? false;
$options_array['course_timeaccess_notset'] = $course_timeaccess_notset ?? false;
$options_array['display_action_data'] = $display_action_data ?? false;

//Instantiate report_form .
$mform = new form\report_form(
    null,
    $options_array,
    'post'
);
//Set default data (if any).
$mform->set_data($options_array);

//Displays the form.
$mform->display();

//Form processing and displaying is done here.
if ($mform->is_cancelled()) {
    //Handle form cancel operation, if cancel button is present on form.
} else if ($fromform = $mform->get_data()) {
    print_r($fromform);
    $report_id = $fromform->report_id ?? $report_id;
    //In this case you process validated data. $mform->get_data() returns data posted in form.
} else {
    echo 'else';
    // This branch is executed if the form is submitted but the data doesn't validate and the form should be redisplayed
    // or on the first display of the form.
}

// Creating url params.
$base_url_str = '/admin/tool/coursewrangler/table.php';
$url_params = $options_array;
// Parameter category_ids must be string.
$url_params['category_ids'] = implode(',', $category_ids) ?? null;
$url_params = array_filter($url_params);
$base_url = new moodle_url($base_url_str, $url_params);

$table = new table\report_table(
    $base_url,
    $options_array
);
$table->out(50, false);

$aform = new form\action_form(null, ['report_form_data_json' => json_encode($options_array)]);
$aform->display();

//Form processing and displaying is done here.
if ($aform->is_cancelled()) {
    echo 'cancelled';
    //Handle form cancel operation, if cancel button is present on form.
} elseif ($fromform = $aform->get_data()) {
    print_r($fromform);
    $report_id = $fromform->report_id ?? $report_id;
//In this case you process validated data. $mform->get_data() returns data posted in form.
} else {
    echo 'else';
    // This branch is executed if the form is submitted but the data doesn't validate and the form should be redisplayed
    // or on the first display of the form.
}
print_r($rows_selected);
print_r($report_form_data_json);

static $initialised = false;
if (!$initialised) {
    $PAGE->requires->js_call_amd('tool_coursewrangler/table', 'init');
    $initialised = true;
}

echo $OUTPUT->footer();
