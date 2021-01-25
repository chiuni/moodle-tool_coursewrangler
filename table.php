<?php // $Id$
/**
 * Simple file test.php to drop into root of Moodle installation.
 * This is the skeleton code to print a downloadable, paged, sorted table of
 * data from a sql query.
 */

namespace tool_coursewrangler;

use moodle_url;
use flexible_table;
use context_system;

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/tablelib.php');
require_once(__DIR__ . '/locallib.php');
$context = context_system::instance();

// require_capability('moodle/course:manageactivities', $coursecontext);

$PAGE->set_context($context);
$PAGE->set_heading(get_string('pageheading', 'tool_coursewrangler'));
$PAGE->set_url(new moodle_url('/admin/tool/coursewrangler/table.php'));
$PAGE->set_title(get_string('pageheader', 'tool_coursewrangler'));
$PAGE->set_pagelayout('admin');

// Print the page header
// $PAGE->navbar->add('Testing table class', new moodle_url('/admin/tool/coursewrangler/table.php'));
echo $OUTPUT->header();
$table = new flexible_table('uniqueid');
$table->define_baseurl(new moodle_url('/admin/tool/coursewrangler/table.php'));
$table->define_columns([
    'course_id',
    'course_fullname',
    'course_shortname',
    'course_startdate',
    'course_enddate',
    'course_timecreated',
    'course_timemodified',
    'course_visible',
    'activity_type',
    'activity_lastmodified',
    'course_timeaccess',
    'course_isparent',
    'course_modulescount',
    'course_lastenrolment',
    'course_deletionscore'
]);
$table->define_headers([
    get_string('table_course_id', 'tool_coursewrangler'),
    get_string('table_course_fullname', 'tool_coursewrangler'),
    get_string('table_course_shortname', 'tool_coursewrangler'),
    get_string('table_course_startdate', 'tool_coursewrangler'),
    get_string('table_course_enddate', 'tool_coursewrangler'),
    get_string('table_course_timecreated', 'tool_coursewrangler'),
    get_string('table_course_timemodified', 'tool_coursewrangler'),
    get_string('table_course_visible', 'tool_coursewrangler'),
    get_string('table_activity_type', 'tool_coursewrangler'),
    get_string('table_activity_lastmodified', 'tool_coursewrangler'),
    get_string('table_course_timeaccess', 'tool_coursewrangler'),
    get_string('table_course_isparent', 'tool_coursewrangler'),
    get_string('table_course_modulescount', 'tool_coursewrangler'),
    get_string('table_course_lastenrolment', 'tool_coursewrangler'),
    get_string('table_course_deletionscore', 'tool_coursewrangler'),
]);
$table->sortable(1);
$table->setup();
$date_format = $_GET['date_format'] ?? 'd/m/Y G:i:s';
foreach (find_relevant_course_data_lite() as $data) {
    $data->course_visible = $data->course_visible ? 'Yes' : 'No';
    $data->course_isparent = $data->course_isparent ? 'Yes' : 'No';
    $table->add_data([
        $data->course_id,
        $data->course_fullname,
        $data->course_shortname,
        process_date($date_format, $data->course_startdate),
        process_date($date_format, $data->course_enddate),
        process_date($date_format, $data->course_timecreated),
        process_date($date_format, $data->course_timemodified),
        $data->course_visible,
        $data->activity_type,
        process_date($date_format, $data->activity_lastmodified),
        process_date($date_format, $data->course_timeaccess),
        $data->course_isparent,
        $data->course_modulescount,
        process_date($date_format, $data->course_lastenrolment),
        get_course_deletion_score($data, true, true) . '%' ,
    ]);
}
echo $table->finish_output();
echo $OUTPUT->footer();
