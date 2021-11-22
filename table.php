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

require_capability('tool/coursewrangler:manage', $context);

// Report_form_data_json is reponsible for allowing cross-form
// parameter sharing, to remember what the user options were.
$reportformdatajson = optional_param('reportformdatajson', null, PARAM_RAW);
$reportformdatajson =
    is_null($reportformdatajson) ? null : json_decode($reportformdatajson);

$action = optional_param('action', null, PARAM_RAW);
$rowsselected = optional_param('rowsselected', null, PARAM_RAW);
$rowsselected =
    is_array($rowsselected) ?
    $rowsselected : array_filter((array) explode(',', $rowsselected));

$categoryids = optional_param('categoryids', null, PARAM_RAW);
$categoryids =
    is_array($categoryids) ?
    $categoryids : array_filter((array) explode(',', $categoryids));

$categoryids = empty($categoryids) &&
    isset($reportformdatajson->categoryids) ?
    $reportformdatajson->categoryids :
    $categoryids;

$filteractiondata = optional_param('filteractiondata', null, PARAM_RAW);
$filteractiondata =
    is_array($filteractiondata) ?
    $filteractiondata :
    array_filter((array) explode(',', $filteractiondata));

$filteractiondata =
    empty($filteractiondata) &&
    isset($reportformdatajson->filteractiondata) ?
    $reportformdatajson->filteractiondata : $filteractiondata;

$filterbycourseids = optional_param('filterbycourseids', null, PARAM_RAW);
$filterbycourseids =
    is_array($filterbycourseids) ?
    $filterbycourseids :
    array_filter((array) explode(',', $filterbycourseids));

$filterbycourseids =
    empty($filterbycourseids) &&
    isset($reportformdatajson->filterbycourseids) ?
    $reportformdatajson->filterbycourseids :
    $filterbycourseids;

// Dates optional params.
$coursetimecreatedafter =
    optional_param('coursetimecreatedafter', null, PARAM_INT);
$coursetimecreatedafter =
    $coursetimecreatedafter ??
    $reportformdatajson->coursetimecreatedafter ??
    false;
$coursetimecreatedbefore =
    optional_param('coursetimecreatedbefore', null, PARAM_INT);
$coursetimecreatedbefore =
    $coursetimecreatedbefore ??
    $reportformdatajson->coursetimecreatedbefore ??
    false;
$coursestartdateafter =
    optional_param('coursestartdateafter', null, PARAM_INT);
$coursestartdateafter =
    $coursestartdateafter ??
    $reportformdatajson->coursestartdateafter ??
    false;
$coursestartdatebefore =
    optional_param('coursestartdatebefore', null, PARAM_INT);
$coursestartdatebefore =
    $coursestartdatebefore ??
    $reportformdatajson->coursestartdatebefore ??
    false;
$courseenddateafter =
    optional_param('courseenddateafter', null, PARAM_INT);
$courseenddateafter =
    $courseenddateafter ??
    $reportformdatajson->courseenddateafter ??
    false;
$courseenddatebefore =
    optional_param('courseenddatebefore', null, PARAM_INT);
$courseenddatebefore =
    $courseenddatebefore ??
    $reportformdatajson->courseenddatebefore ??
    false;
$coursetimeaccessafter =
    optional_param('coursetimeaccessafter', null, PARAM_INT);
$coursetimeaccessafter =
    $coursetimeaccessafter ??
    $reportformdatajson->coursetimeaccessafter ??
    false;
$coursetimeaccessbefore =
    optional_param('coursetimeaccessbefore', null, PARAM_INT);
$coursetimeaccessbefore =
    $coursetimeaccessbefore ??
    $reportformdatajson->coursetimeaccessbefore ??
    false;

// Turning dates into timestamps.
$coursetimecreatedafter =
    isset($coursetimecreatedafter['enabled']) &&
    $coursetimecreatedafter['enabled'] == 1 ?
    moodletime_to_unixtimestamp($coursetimecreatedafter) :
    $coursetimecreatedafter;
$coursetimecreatedbefore =
    isset($coursetimecreatedbefore['enabled']) &&
    $coursetimecreatedbefore['enabled'] == 1 ?
    moodletime_to_unixtimestamp($coursetimecreatedbefore) :
    $coursetimecreatedbefore;
$coursestartdateafter =
    isset($coursestartdateafter['enabled']) &&
    $coursestartdateafter['enabled'] == 1 ?
    moodletime_to_unixtimestamp($coursestartdateafter) :
    $coursestartdateafter;
$coursestartdatebefore =
    isset($coursestartdatebefore['enabled']) &&
    $coursestartdatebefore['enabled'] == 1 ?
    moodletime_to_unixtimestamp($coursestartdatebefore) :
    $coursestartdatebefore;
$courseenddateafter =
    isset($courseenddateafter['enabled']) &&
    $courseenddateafter['enabled'] == 1 ?
    moodletime_to_unixtimestamp($courseenddateafter) :
    $courseenddateafter;
$courseenddatebefore =
    isset($courseenddatebefore['enabled']) &&
    $courseenddatebefore['enabled'] == 1 ?
    moodletime_to_unixtimestamp($courseenddatebefore) :
    $courseenddatebefore;
$coursetimeaccessafter =
    isset($coursetimeaccessafter['enabled']) &&
    $coursetimeaccessafter['enabled'] == 1 ?
    moodletime_to_unixtimestamp($coursetimeaccessafter) :
    $coursetimeaccessafter;
$coursetimeaccessbefore =
    isset($coursetimeaccessbefore['enabled']) &&
    $coursetimeaccessbefore['enabled'] == 1 ?
    moodletime_to_unixtimestamp($coursetimeaccessbefore) :
    $coursetimeaccessbefore;

// Validating timestamps.
$coursetimecreatedafter =
    $coursetimecreatedafter > 0 ? $coursetimecreatedafter : null;
$coursetimecreatedbefore =
    $coursetimecreatedbefore > 0 ? $coursetimecreatedbefore : null;
$coursestartdateafter =
    $coursestartdateafter > 0 ? $coursestartdateafter : null;
$coursestartdatebefore =
    $coursestartdatebefore > 0 ? $coursestartdatebefore : null;
$courseenddateafter =
    $courseenddateafter > 0 ? $courseenddateafter : null;
$courseenddatebefore =
    $courseenddatebefore > 0 ? $courseenddatebefore : null;
$coursetimeaccessafter =
    $coursetimeaccessafter > 0 ? $coursetimeaccessafter : null;
$coursetimeaccessbefore =
    $coursetimeaccessbefore > 0 ? $coursetimeaccessbefore : null;

// Flag parameters.
$coursetimecreatednotset =
    optional_param('coursetimecreatednotset', null, PARAM_BOOL);
$coursetimecreatednotset =
    $coursetimecreatednotset ??
    $reportformdatajson->coursetimecreatednotset ??
    false;
$coursestartdatenotset =
    optional_param('coursestartdatenotset', null, PARAM_BOOL);
$coursestartdatenotset =
    $coursestartdatenotset ??
    $reportformdatajson->coursestartdatenotset ??
    false;
$courseenddatenotset =
    optional_param('courseenddatenotset', null, PARAM_BOOL);
$courseenddatenotset =
    $courseenddatenotset ??
    $reportformdatajson->courseenddatenotset ??
    false;
$coursetimeaccessnotset =
    optional_param('coursetimeaccessnotset', null, PARAM_BOOL);
$coursetimeaccessnotset =
    $coursetimeaccessnotset ??
    $reportformdatajson->coursetimeaccessnotset ??
    false;

// Other settings parameters.
$displayactiondata =
    optional_param('displayactiondata', null, PARAM_BOOL);
$displayactiondata =
    $displayactiondata ?? $reportformdatajson->displayactiondata ?? false;
$hideshowmetachildren =
    optional_param('hideshowmetachildren', null, PARAM_TEXT);
$hideshowmetachildren =
    $hideshowmetachildren ??
    $reportformdatajson->hideshowmetachildren ??
    null;
$hideshowmetaparents =
    optional_param('hideshowmetaparents', null, PARAM_TEXT);
$hideshowmetaparents =
    $hideshowmetaparents ??
    $reportformdatajson->hideshowmetaparents ??
    null;
$hideshowhiddencourses =
    optional_param('hideshowhiddencourses', null, PARAM_TEXT);
$hideshowhiddencourses =
    $hideshowhiddencourses ??
    $reportformdatajson->hideshowhiddencourses ??
    null;
$pagesize =
    optional_param('pagesize', null, PARAM_INT);
$pagesize =
    $pagesize ??
    $reportformdatajson->pagesize ??
    0; // This resets it back to 50, two lines below.
$pagesize =
    ($pagesize > 5000) ? 5000 : $pagesize;
$pagesize =
    ($pagesize < 50) ? 50 : $pagesize;

$matchstringshort =
    optional_param('matchstringshort', null, PARAM_TEXT);
$matchstringshort =
    $matchstringshort ??
    $reportformdatajson->matchstringshort ??
    null;

$matchstringfull =
    optional_param('matchstringfull', null, PARAM_TEXT);
$matchstringfull =
    $matchstringfull ??
    $reportformdatajson->matchstringfull ??
    null;

$PAGE->set_context($context);
$PAGE->set_heading(get_string('pageheading', 'tool_coursewrangler'));
$PAGE->set_url(new moodle_url('/admin/tool/coursewrangler/table.php'));
$PAGE->set_title(get_string('pageheader', 'tool_coursewrangler'));
$PAGE->set_pagelayout('admin');
$PAGE->navbar->add(
    get_string('administrationsite'),
    new moodle_url('/admin/search.php')
);
$PAGE->navbar->add(
    get_string(
        'pluginname',
        'tool_coursewrangler'
    ),
    new moodle_url('/admin/tool/coursewrangler/index.php')
);
$PAGE->navbar->add(
    get_string(
        'table_tablename',
        'tool_coursewrangler'
    ),
    new moodle_url('/admin/tool/coursewrangler/table.php')
);

$optionsarray = [];
$optionsarray['categoryids'] =
    $categoryids ?? [];
$optionsarray['filteractiondata'] =
    $filteractiondata ?? [];
$optionsarray['filterbycourseids'] =
    $filterbycourseids ?? [];
$optionsarray['coursetimecreatedafter'] =
    $coursetimecreatedafter > 0 ? $coursetimecreatedafter : null;
$optionsarray['coursetimecreatedbefore'] =
    $coursetimecreatedbefore > 0 ? $coursetimecreatedbefore : null;
$optionsarray['coursestartdateafter'] =
    $coursestartdateafter > 0 ? $coursestartdateafter : null;
$optionsarray['coursestartdatebefore'] =
    $coursestartdatebefore > 0 ? $coursestartdatebefore : null;
$optionsarray['courseenddateafter'] =
    $courseenddateafter > 0 ? $courseenddateafter : null;
$optionsarray['courseenddatebefore'] =
    $courseenddatebefore > 0 ? $courseenddatebefore : null;
$optionsarray['coursetimeaccessafter'] =
    $coursetimeaccessafter > 0 ? $coursetimeaccessafter : null;
$optionsarray['coursetimeaccessbefore'] =
    $coursetimeaccessbefore > 0 ? $coursetimeaccessbefore : null;
$optionsarray['coursetimecreatednotset'] =
    $coursetimecreatednotset ?? false;
$optionsarray['coursestartdatenotset'] =
    $coursestartdatenotset ?? false;
$optionsarray['courseenddatenotset'] =
    $courseenddatenotset ?? false;
$optionsarray['coursetimeaccessnotset'] =
    $coursetimeaccessnotset ?? false;
$optionsarray['displayactiondata'] =
    $displayactiondata ?? false;
$optionsarray['hideshowmetachildren'] =
    $hideshowmetachildren ?? null;
$optionsarray['hideshowmetaparents'] =
    $hideshowmetaparents ?? null;
$optionsarray['hideshowhiddencourses'] =
    $hideshowhiddencourses ?? null;
$optionsarray['pagesize'] =
    $pagesize ?? 50;
$optionsarray['matchstringshort'] =
    $matchstringshort ?? null;
$optionsarray['matchstringfull'] =
    $matchstringfull ?? null;
$optionsarray = array_filter($optionsarray);

// Instantiate report_form .
$mform = new form\report_form(
    null,
    $optionsarray,
    'post'
);
// Set default data (if any) do not remove!
// This is for setting additional data so the form doesn't lose it.
$mform->set_data($optionsarray);

// Creating url params.
$baseurlstr = '/admin/tool/coursewrangler/table.php';
$urlparams = $optionsarray;
// Parameter categoryids must be string.
$urlparams['categoryids'] =
    is_array($categoryids) ? implode(',', $categoryids) : $categoryids;

$urlparams['filteractiondata'] =
    is_array($filteractiondata) ?
    implode(',', $filteractiondata) :
    $filteractiondata;

$urlparams['filterbycourseids'] =
    is_array($filterbycourseids) ?
    implode(',', $filterbycourseids) :
    $filterbycourseids;

$urlparams = array_filter($urlparams);
$baseurl = new moodle_url($baseurlstr, $urlparams);
$baseurlreset = new moodle_url($baseurlstr);

// Form processing and displaying is done here.
if ($mform->is_cancelled()) {
    // Handle form cancel operation, if cancel button is present on form.
    // This is the default way Moodle handles cancelled forms.
    redirect($baseurlreset);
    exit;
} elseif ($fromform = $mform->get_data()) {
    // In this case you process validated data. $mform->get_data()
    // returns data posted in form.
} else {
    // This branch is executed if the form is submitted but
    // the data doesn't validate and the form should be redisplayed
    // or on the first display of the form.
}

$table = new table\report_table(
    $baseurl,
    $optionsarray
);
$table->prepare_report_table($pagesize, false);
$totalrowstext = get_string(
    'table_displayingtotalrows',
    'tool_coursewrangler',
    $table->totalrows
);
$totalrowshtml = \html_writer::tag(
    'p',
    $totalrowstext,
    ['class' => 'h5 mdl-right']
);

if ($displayactiondata == true) {
    $aform = new form\action_form(
        null,
        ['reportformdatajson' => json_encode($optionsarray)]
    );
    if ($fromform = $aform->get_data()) {
        // In this case you process validated data. $mform->get_data()
        // returns data posted in form.
        if (isset($rowsselected) && isset($action)) {
            $formhandler = new action_handler();
            foreach ($rowsselected as $rowcourseid) {
                action_handler::update($rowcourseid, $action);
            }
            // [TODO]: Is this the best way of doing redirects in Moodle?
            redirect($baseurl,
                get_string(
                    'table_actionredirectmessage',
                    'tool_coursewrangler'
                ),
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

// Displays the big form.
$mform->display();
echo $totalrowshtml;

if ($displayactiondata == true) {
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
