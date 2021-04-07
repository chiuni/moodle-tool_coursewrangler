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

$category_ids = optional_param('category_ids', null, PARAM_RAW);
$category_ids = is_array($category_ids) ? $category_ids : array_filter( (array) explode(',', $category_ids) );
$category_ids = empty($category_ids) ? $report_form_data_json->category_ids : $category_ids;

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

$PAGE->set_context($context);
$PAGE->set_heading(get_string('pageheading', 'tool_coursewrangler'));
$PAGE->set_url(new moodle_url('/admin/tool/coursewrangler/table.php'));
$PAGE->set_title(get_string('pageheader', 'tool_coursewrangler'));
$PAGE->set_pagelayout('admin');
$PAGE->navbar->add(get_string('administrationsite'), new moodle_url('/admin/search.php'));
$PAGE->navbar->add(get_string('pluginname', 'tool_coursewrangler'), new moodle_url('/admin/tool/coursewrangler/index.php'));
$PAGE->navbar->add(get_string('table_tablename', 'tool_coursewrangler'), new moodle_url('/admin/tool/coursewrangler/table.php'));

echo $OUTPUT->header();

$options_array = [];
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
$options_array = array_filter($options_array);

// Instantiate report_form .
$mform = new form\report_form(
    null,
    $options_array,
    'post'
);
// Set default data (if any) do not remove!
// This is for setting additional data so the form doesn't lose it.
$mform->set_data($options_array);

//Displays the form.
$mform->display();

// Creating url params.
$base_url_str = '/admin/tool/coursewrangler/table.php';
$url_params = $options_array;
// Parameter category_ids must be string.
$url_params['category_ids'] = is_array($category_ids) ? implode(',', $category_ids) : $category_ids;
$url_params = array_filter($url_params);
$base_url = new moodle_url($base_url_str, $url_params);
$base_url_reset = new moodle_url($base_url_str);

//Form processing and displaying is done here.
if ($mform->is_cancelled()) {
    //Handle form cancel operation, if cancel button is present on form.
    // This is the default way Moodle handles cancelled forms.
    redirect($base_url_reset);
    exit;
} elseif ($fromform = $mform->get_data()) {
    cwt_debugger($fromform, 'From form');
    //In this case you process validated data. $mform->get_data() returns data posted in form.
} else {
    cwt_debugger(null, 'Else');
    // This branch is executed if the form is submitted but the data doesn't validate and the form should be redisplayed
    // or on the first display of the form.
}
cwt_debugger($report_form_data_json, 'Report form data');
cwt_debugger($url_params, 'url_params');

$table = new table\report_table(
    $base_url,
    $options_array
);
$table->out(50, false);

cwt_debugger($table->sql, 'Table');

if ($display_action_data == true) {
    $aform = new form\action_form(null, ['report_form_data_json' => json_encode($options_array)]);
    $aform->display();
    if ($fromform = $aform->get_data()) {
        cwt_debugger($fromform, 'From second form');
        //In this case you process validated data. $mform->get_data() returns data posted in form.
        echo 'submitted?<br>';
        if (isset($rows_selected) && isset($action)) {
            echo 'is set';
            $form_handler = new action_handler();
            switch ($action) {
                case 'delete':
                    foreach ($rows_selected as $row_course_id) {
                        echo($row_course_id);
                        $form_handler->schedule_delete($row_course_id);
                    }
                    break;
                case 'reset':
                    foreach ($rows_selected as $row_course_id) {
                        $form_handler->hard_reset($row_course_id);
                    }
                    break;
                default:
                    break;
            }
            redirect($base_url);
            exit;
        }
    } else {
        cwt_debugger(null, 'Else');
        // This branch is executed if the form is submitted but the data doesn't validate and the form should be redisplayed
        // or on the first display of the form.
    }
}
cwt_debugger($rows_selected, 'Rows selected');

static $initialised = false;
if (!$initialised) {
    $PAGE->requires->js_call_amd('tool_coursewrangler/table', 'init');
    $initialised = true;
}

echo $OUTPUT->footer();
