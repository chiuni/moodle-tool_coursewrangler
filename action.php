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
 * @package   tool_coursewrangler
 * @author    Hugo Soares <h.soares@chi.ac.uk>
 * @copyright 2020 University of Chichester {@link www.chi.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_coursewrangler;

use context_system;
use moodle_url;

require_once(__DIR__ . '/../../../config.php');
require_login();
require_once($CFG->libdir . '/adminlib.php');
require_once(__DIR__ . '/locallib.php');
require_once($CFG->libdir . '/tablelib.php');
$context = context_system::instance();

require_capability('tool/coursewrangler:manage', $context);

$courseid = required_param('courseid', PARAM_INT);
$action = required_param('action', PARAM_ALPHA);
$returnlink = optional_param('returnlink', null, PARAM_URL);
$confirm = optional_param('confirm', false, PARAM_BOOL);

$returnlink_reportdetails = new moodle_url('/admin/tool/coursewrangler/report_details.php', ['courseid' => $courseid, 'returnlink' => $returnlink]);
$returnlink_table = $returnlink ?? false;

$PAGE->set_context($context);
$PAGE->set_heading(get_string('pageheading', 'tool_coursewrangler'));
$PAGE->set_url(new moodle_url('/admin/tool/coursewrangler/action.php', ['courseid' => $courseid, 'action' => $action]));
$PAGE->set_title(get_string('report_details_pageheader', 'tool_coursewrangler'));
$PAGE->set_pagelayout('admin');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('actionpage', 'tool_coursewrangler'));

if (!$confirm) {
    $confirmstring = get_string('actionpage_confirm_btn', 'tool_coursewrangler', ['action' => $action, 'courseid' => $courseid]);
    $confirmurl = new moodle_url(
            '/admin/tool/coursewrangler/action.php',
            ['courseid' => $courseid, 'action' => $action, 'confirm' => true, 'returnlink' => $returnlink]
        );
    echo $OUTPUT->confirm($confirmstring, $confirmurl, $returnlink_reportdetails);
}

if ($confirm) {
    $actionsuccess = action_handler::update($courseid, $action);
    if ($actionsuccess == true) {
        echo $OUTPUT->notification(get_string('actionpage_actionsuccess', 'tool_coursewrangler'), 'notifysuccess');
    } else {
        echo $OUTPUT->notification(get_string('actionpage_actionfail', 'tool_coursewrangler'), 'notifyerror');
    }
    echo $OUTPUT->single_button(
        $returnlink_reportdetails, get_string('actionpage_returntoreportdetails', 'tool_coursewrangler')
    );
    if ($returnlink_table != false) {
        echo $OUTPUT->single_button(
            $returnlink_table,
            get_string('actionpage_returntotablelink', 'tool_coursewrangler')
        );
    }
}

echo $OUTPUT->footer();
