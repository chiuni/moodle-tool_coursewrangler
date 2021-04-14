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
require_once(__DIR__ . '/locallib.php');
$context = context_system::instance();

$PAGE->set_context($context);
$PAGE->set_heading(get_string('pageheading', 'tool_coursewrangler'));
$PAGE->set_url(new moodle_url('/admin/tool/coursewrangler/table.php'));
$PAGE->set_title(get_string('pageheader', 'tool_coursewrangler'));
$PAGE->set_pagelayout('admin');
$PAGE->navbar->add(get_string('user_table', 'tool_coursewrangler'), new moodle_url('/admin/tool/coursewrangler/user_table.php'));

echo $OUTPUT->header();

$enrolments = enrol_get_all_users_courses($USER->id);
$enrolids = array_keys($enrolments);
cwt_debugger($enrolids);
// Creating url params.
$base_url_str = '/admin/tool/coursewrangler/user_table.php';
// Parameter category_ids must be string.
$base_url = new moodle_url($base_url_str, []);

$table = new table\user_report_table(
    $base_url,
    ['courseids' => $enrolids]
);
$table->out(50, false);

echo $OUTPUT->footer();
