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

require_login(null, false);

$userid = optional_param('userid', null, PARAM_INT);

if (isset($userid) && $userid != null && is_siteadmin($USER)) {
    $userid = $userid;
} else {
    $userid = $USER->id;
}

$user = \core_user::get_user($userid);

$PAGE->set_context($context);
$PAGE->set_heading(get_string('pageheading', 'tool_coursewrangler'));
$PAGE->set_url(new moodle_url('/admin/tool/coursewrangler/user_table.php'));
$PAGE->set_title(get_string('pageheader', 'tool_coursewrangler'));
$PAGE->set_pagelayout('admin');
$PAGE->navbar->add(get_string('administrationsite'), new moodle_url('/admin/search.php'));
$PAGE->navbar->add(get_string('pluginname', 'tool_coursewrangler'), new moodle_url('/admin/tool/coursewrangler/index.php'));
$PAGE->navbar->add(get_string('table_usertable_name', 'tool_coursewrangler'), new moodle_url('/admin/tool/coursewrangler/user_table.php'));

$enrolments = enrol_get_all_users_courses($userid);
$enrolids = array_keys($enrolments);
// Creating url params.
$base_url_str = '/admin/tool/coursewrangler/user_table.php';
// Parameter category_ids must be string.
$base_url = new moodle_url($base_url_str, []);

$table = new table\user_report_table(
    $base_url,
    ['courseids' => $enrolids]
);

echo $OUTPUT->header();
cwt_debugger($enrolids);

$usernamehtml = \html_writer::tag(
    'p',
    $user->firstname,
    ['class' => 'h5 mdl-right']
);
echo $usernamehtml;

$table->out(50, false);

echo $OUTPUT->footer();
