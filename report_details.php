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

$returnlink = optional_param('returnlink', null, PARAM_URL);

$PAGE->set_context($context);
$PAGE->set_heading(get_string('pageheading', 'tool_coursewrangler'));
$PAGE->set_url(new moodle_url('/admin/tool/coursewrangler/report_details.php'));
$PAGE->set_title(get_string('report_details_pageheader', 'tool_coursewrangler'));
$PAGE->set_pagelayout('admin');

global $DB;
$course = $DB->get_record_sql(
    'SELECT *
     FROM {tool_coursewrangler_metrics}
     WHERE courseid=:courseid',
    ['courseid' => $courseid]
    );

if (!isset($course->id)) {
    $course->courseid = $courseid;
    $course->links = [];
    $course->links['returnlink'] = $returnlink;
    $course->links['courselink'] =
        new moodle_url('/course/view.php?id=' . $courseid);
    // Course was not found for whatever reason, display default template.
    echo $OUTPUT->header();
    echo $OUTPUT->heading(
        get_string(
            'report_details_notfound',
            'tool_coursewrangler'
        )
    );
    echo $OUTPUT->render_from_template(
        'tool_coursewrangler/report_details_notfound',
        $course
    );
    echo $OUTPUT->footer();
    exit;
}

// Creating course link.
$courseurl = new moodle_url('/course/view.php?id=' . $course->courseid, []);
$course->course_title_link =
    \html_writer::link(
        $courseurl,
        $course->courseshortname . ': ' . $course->coursefullname
    );
// Processing dates into human readable format.
$course->coursetimecreated =
    ($course->coursetimecreated == 0) ?
    '-' : userdate($course->coursetimecreated);
$course->coursetimemodified =
    ($course->coursetimemodified == 0) ?
    '-' : userdate($course->coursetimemodified);
$course->coursestartdate =
    ($course->coursestartdate == 0) ?
    '-' : userdate($course->coursestartdate);
$course->courseenddate =
    ($course->courseenddate == 0) ?
    '-' : userdate($course->courseenddate);
$course->coursetimeaccess =
    ($course->coursetimeaccess == 0) ?
    '-' : userdate($course->coursetimeaccess);
$course->courselastenrolment =
    ($course->courselastenrolment == 0) ?
    '-' : userdate($course->courselastenrolment);
$course->activitylastmodified =
    ($course->activitylastmodified == 0) ?
    '-' : userdate($course->activitylastmodified);
$course->metricsupdated =
    ($course->metricsupdated == 0) ?
    '-' : userdate($course->metricsupdated);
// Processing visible and parent.
// [TODO] To do: Convert to languange strings.
$course->coursevisible =
    ($course->coursevisible == 0) ?
    'No' : 'Yes';

if ($course->courseparents != null) {
    $courseparents = explode(',', $course->courseparents);
    $course->courseparents = [];
    foreach ($courseparents as $parentcourseid) {
        $course->courseparents[] = [
            'courseid' => $parentcourseid,
            'course_link' => new moodle_url(
                '/course/view.php?id=' . $parentcourseid,
                []
            )
        ];
    }
} else {
    $course->courseparents = false;
}
if ($course->coursechildren != null) {
    $coursechildren = explode(',', $course->coursechildren);
    $course->coursechildren = [];
    foreach ($coursechildren as $childcourseid) {
        $course->coursechildren[] = [
            'courseid' => $childcourseid,
            'course_link' => new moodle_url(
                '/course/view.php?id=' . $childcourseid,
                []
            )
        ];
    }
} else {
    $course->coursechildren = false;
}
$course->score = $DB->get_record_sql(
    'SELECT * FROM {tool_coursewrangler_score} WHERE metrics_id=:metrics_id ',
    ['metrics_id' => $course->id]
);
if ($course->score->timemodified == 0) {
    $course->score = null;
} else {
    $course->score->timemodified = userdate($course->score->timemodified);
}

if ($course == false) {
    // Throw not found error?
    echo 'not found';
    exit;
}
$course->links = ['returnlink' => $returnlink];

$actiondata = $DB->get_record(
    'tool_coursewrangler_action',
    ['courseid' => $course->courseid]
);

$actionlink = $CFG->wwwroot . '/admin/tool/coursewrangler/action.php';
$actionlinkparams = [];
$actionlinkparams['courseid'] = $course->courseid;
$actionlinkparams['returnlink'] = $returnlink;
if ($actiondata != false) {
    $actiondata->status =
        ($actiondata->status == '') ?
        $actiondata->action : $actiondata->status;
    $actiondata->status = get_string(
        'report_details_actionstatus_'.$actiondata->status,
        'tool_coursewrangler'
    );
    $course->actionstatus = $actiondata->status;
    $course->actionstatus_date = userdate($actiondata->lastupdated);
    $resetlinkparams = $actionlinkparams;
    $resetlinkparams['action'] = 'reset';
    $course->links['action_reset_link'] = new moodle_url(
        $actionlink,
        $resetlinkparams
    );
} else {
    $deletelinkparams = $actionlinkparams;
    $deletelinkparams['action'] = 'delete';
    $course->links['action_delete_link'] = new moodle_url(
        $actionlink,
        $deletelinkparams
    );
    $protectlinkparams = $actionlinkparams;
    $protectlinkparams['action'] = 'protect';
    $course->links['action_protect_link'] = new moodle_url(
        $actionlink,
        $protectlinkparams
    );
}

echo $OUTPUT->header();
echo $OUTPUT->heading(
    get_string(
        'report_details_coursedetailsfor',
        'tool_coursewrangler'
    )
);
echo $OUTPUT->render_from_template(
    'tool_coursewrangler/report_details',
    $course
);
echo $OUTPUT->footer();
