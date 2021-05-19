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

require_login(null, false);

if (!is_siteadmin($USER)) {
    redirect('', 'Only site admins can access this page.');
    exit;
}

// Report_form_data_json is reponsible for allowing cross-form
// parameter sharing, to remember what the user options were.
$report_form_data_json = optional_param('report_form_data_json', null, PARAM_RAW);
$report_form_data_json = is_null($report_form_data_json) ? null : json_decode($report_form_data_json);

$action = optional_param('action', null, PARAM_RAW);
$rows_selected = optional_param('rows_selected', null, PARAM_RAW);
$rows_selected = is_array($rows_selected) ? $rows_selected : array_filter( (array) explode(',', $rows_selected) );

$category_ids = optional_param('category_ids', null, PARAM_RAW);
$category_ids = is_array($category_ids) ? $category_ids : array_filter( (array) explode(',', $category_ids) );
$category_ids = empty($category_ids) && isset($report_form_data_json->category_ids) 
                    ? $report_form_data_json->category_ids 
                    : $category_ids;

$filter_action_data = optional_param('filter_action_data', null, PARAM_RAW);
$filter_action_data = is_array($filter_action_data) ? $filter_action_data : array_filter( (array) explode(',', $filter_action_data) );
$filter_action_data = empty($filter_action_data) && isset($report_form_data_json->filter_action_data) 
                    ? $report_form_data_json->filter_action_data 
                    : $filter_action_data;

$filter_by_courseids = optional_param('filter_by_courseids', null, PARAM_RAW);
$filter_by_courseids = is_array($filter_by_courseids) ? $filter_by_courseids : array_filter( (array) explode(',', $filter_by_courseids) );
$filter_by_courseids = empty($filter_by_courseids) && isset($report_form_data_json->filter_by_courseids) 
                    ? $report_form_data_json->filter_by_courseids 
                    : $filter_by_courseids;

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
$course_timecreated_after = isset($course_timecreated_after['enabled']) 
                            && $course_timecreated_after['enabled'] == 1 
                                ? moodletime_to_unixtimestamp($course_timecreated_after) 
                                : $course_timecreated_after;
$course_timecreated_before = isset($course_timecreated_before['enabled']) 
                            && $course_timecreated_before['enabled'] == 1 
                                ? moodletime_to_unixtimestamp($course_timecreated_before) 
                                : $course_timecreated_before;
$course_startdate_after = isset($course_startdate_after['enabled']) 
                            && $course_startdate_after['enabled'] == 1 
                                ? moodletime_to_unixtimestamp($course_startdate_after) 
                                : $course_startdate_after;
$course_startdate_before = isset($course_startdate_before['enabled']) 
                            && $course_startdate_before['enabled'] == 1 
                                ? moodletime_to_unixtimestamp($course_startdate_before) 
                                : $course_startdate_before;
$course_enddate_after = isset($course_enddate_after['enabled']) 
                            && $course_enddate_after['enabled'] == 1 
                                ? moodletime_to_unixtimestamp($course_enddate_after) 
                                : $course_enddate_after;
$course_enddate_before = isset($course_enddate_before['enabled']) 
                            && $course_enddate_before['enabled'] == 1 
                                ? moodletime_to_unixtimestamp($course_enddate_before) 
                                : $course_enddate_before;
$course_timeaccess_after = isset($course_timeaccess_after['enabled']) 
                            && $course_timeaccess_after['enabled'] == 1 
                                ? moodletime_to_unixtimestamp($course_timeaccess_after) 
                                : $course_timeaccess_after;
$course_timeaccess_before = isset($course_timeaccess_before['enabled']) 
                            && $course_timeaccess_before['enabled'] == 1 
                                ? moodletime_to_unixtimestamp($course_timeaccess_before) 
                                : $course_timeaccess_before;

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
$hideshow_meta_children = optional_param('hideshow_meta_children', null, PARAM_TEXT);
$hideshow_meta_children = $hideshow_meta_children ?? $report_form_data_json->hideshow_meta_children ?? null;
$hideshow_meta_parents = optional_param('hideshow_meta_parents', null, PARAM_TEXT);
$hideshow_meta_parents = $hideshow_meta_parents ?? $report_form_data_json->hideshow_meta_parents ?? null;
$hideshow_hidden_courses = optional_param('hideshow_hidden_courses', null, PARAM_TEXT);
$hideshow_hidden_courses = $hideshow_hidden_courses ?? $report_form_data_json->hideshow_hidden_courses ?? null;
$pagesize = optional_param('pagesize', null, PARAM_INT);
$pagesize = $pagesize ?? $report_form_data_json->pagesize ?? 0; // This resets it back to 50, two lines below.
$pagesize = ($pagesize > 500) ? 500 : $pagesize;
$pagesize = ($pagesize < 50) ? 50 : $pagesize;

$matchstring_short = optional_param('matchstring_short', null, PARAM_TEXT);
$matchstring_short = $matchstring_short ?? $report_form_data_json->matchstring_short ?? null;
$matchstring_full = optional_param('matchstring_full', null, PARAM_TEXT);
$matchstring_full = $matchstring_full ?? $report_form_data_json->matchstring_full ?? null;

$PAGE->set_context($context);
$PAGE->set_heading(get_string('pageheading', 'tool_coursewrangler'));
$PAGE->set_url(new moodle_url('/admin/tool/coursewrangler/table.php'));
$PAGE->set_title(get_string('pageheader', 'tool_coursewrangler'));
$PAGE->set_pagelayout('admin');
$PAGE->navbar->add(get_string('administrationsite'), new moodle_url('/admin/search.php'));
$PAGE->navbar->add(get_string('pluginname', 'tool_coursewrangler'), new moodle_url('/admin/tool/coursewrangler/index.php'));
$PAGE->navbar->add(get_string('table_tablename', 'tool_coursewrangler'), new moodle_url('/admin/tool/coursewrangler/table.php'));

$options_array = [];
$options_array['category_ids'] = $category_ids ?? [];
$options_array['filter_action_data'] = $filter_action_data ?? [];
$options_array['filter_by_courseids'] = $filter_by_courseids ?? [];
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
$options_array['course_enddate_notset'] = $course_enddate_notset ?? false;
$options_array['course_timeaccess_notset'] = $course_timeaccess_notset ?? false;
$options_array['display_action_data'] = $display_action_data ?? false;
$options_array['hideshow_meta_children'] = $hideshow_meta_children ?? null;
$options_array['hideshow_meta_parents'] = $hideshow_meta_parents ?? null;
$options_array['hideshow_hidden_courses'] = $hideshow_hidden_courses ?? null;
$options_array['pagesize'] = $pagesize ?? 50;
$options_array['matchstring_short'] = $matchstring_short ?? null;
$options_array['matchstring_full'] = $matchstring_full ?? null;
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

// Creating url params.
$base_url_str = '/admin/tool/coursewrangler/table.php';
$url_params = $options_array;
// Parameter category_ids must be string.
$url_params['category_ids'] = is_array($category_ids) ? implode(',', $category_ids) : $category_ids;
$url_params['filter_action_data'] = is_array($filter_action_data) ? implode(',', $filter_action_data) : $filter_action_data;
$url_params['filter_by_courseids'] = is_array($filter_by_courseids) ? implode(',', $filter_by_courseids) : $filter_by_courseids;
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
    //In this case you process validated data. $mform->get_data() returns data posted in form.
} else {
    // This branch is executed if the form is submitted but the data doesn't validate and the form should be redisplayed
    // or on the first display of the form.
}

$table = new table\report_table(
    $base_url,
    $options_array
);
$table->prepare_report_table($pagesize, false);
$totalrowstext = get_string('table_displayingtotalrows', 'tool_coursewrangler', $table->totalrows);
$totalrowshtml = \html_writer::tag(
    'p',
    $totalrowstext,
    ['class' => 'h5 mdl-right']
);

if ($display_action_data == true) {
    $aform = new form\action_form(null, ['report_form_data_json' => json_encode($options_array)]);
    if ($fromform = $aform->get_data()) {
        //In this case you process validated data. $mform->get_data() returns data posted in form.
        if (isset($rows_selected) && isset($action)) {
            $form_handler = new action_handler();
            foreach ($rows_selected as $row_course_id) {
                action_handler::update($row_course_id, $action);
            }
            // TODO: Is this the best way of doing redirects in Moodle?
            redirect($base_url, 
            get_string('table_actionredirectmessage', 'tool_coursewrangler'),
            0
            );
            exit;
        }
    } else {
        // This branch is executed if the form is submitted but the data 
        // doesn't validate and the form should be redisplayed
        // or on the first display of the form.
    }
}
// OUTPUT begins here.
// We do this so that redirects can happen prior.
echo $OUTPUT->header();

//Displays the big form.
$mform->display();
echo $totalrowshtml;

if ($display_action_data == true) {
    // We only display this one if required.
    $aform->display();
}

$table->finish_report_table();

static $initialised = false;
if (!$initialised) {
    $PAGE->requires->js_call_amd('tool_coursewrangler/table', 'init');
    $initialised = true;
}

echo $OUTPUT->footer();
