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

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Course Wrangler Tool';
$string['plugindesc'] = 'Course deletion tool for Moodle 3.8+';

$string['pageheader'] = 'The course wrangler tool page header.';
$string['pageheading'] = 'Course Wrangler Tool';

$string['privacy:metadata'] = 'The course wrangler tool plugin does not store any personal data.';

$string['table_tablename'] = 'CWT Table';
$string['table_row_select'] = 'Select';
$string['table_selectall'] = 'Select All';
$string['table_edit'] = 'Edit';
$string['table_action_delete'] = 'Delete';


$string['table_courseid'] = 'Course ID';
$string['table_coursemoduleid'] = 'Course Module ID';
$string['table_coursefullname'] = 'Name';
$string['table_courseshortname'] = 'Short name';
$string['table_courseidnumber'] = 'ID Number';
$string['table_coursestartdate'] = 'Start date';
$string['table_courseenddate'] = 'End date';
$string['table_coursetimecreated'] = 'Created';
$string['table_coursetimemodified'] = 'Last modified';
$string['table_coursevisible'] = 'Visible';
$string['table_activitytype'] = 'Activity type';
$string['table_activitylastmodified'] = 'Activities last modified';
$string['table_coursetimeaccess'] = 'Last access';
$string['table_courseparents'] = 'Parents (inherits meta enrolemnts from)';
$string['table_coursechildren'] = 'Children (uses this course for meta enrolments)';
$string['table_courseparents_none'] = 'No parents';
$string['table_coursechildren_none'] = 'No children';
$string['table_coursemodulescount'] = 'Total activities';
$string['table_courselastenrolment'] = 'Last enrolment';
$string['table_course_deletionscore'] = 'Deletion Score (%)';
$string['table_totalenrolcount'] = 'Total Enrolments';
$string['table_activeenrolcount'] = 'Active Enrolments';
$string['table_selfenrolcount'] = 'Self Enrolments';
$string['table_manualenrolcount'] = 'Manual Enrolments';
$string['table_metaenrolcount'] = 'Meta Enrolments';
$string['table_otherenrolcount'] = 'Other Enrolments';
$string['table_suspendedenrolcount'] = 'Suspended Enrolments';
$string['table_score_raw'] = 'Raw Score';
$string['table_score_rounded'] = 'Rounded Score';
$string['table_score_percentage'] = 'Percentage';
$string['table_percentage_notavailable'] = 'N/A';
$string['table_value_notavailable'] = 'N/A';
$string['table_visible_yes'] = 'Yes';
$string['table_visible_no'] = 'No';
$string['table_status_scheduled'] = 'Scheduled';
$string['table_status_notified'] = 'Owner Notified';
$string['table_status_hidden'] = 'Course Hidden';
$string['table_status_waiting'] = 'Waiting Time';
$string['table_displayingtotalrows'] = 'Query found {$a} courses in metrics data.';
$string['table_action_lastupdated'] = 'This course will be deleted after';

$string['table_course_action'] = 'Action';
$string['table_course_status'] = 'Status';
$string['table_action_protect'] = 'Protect';
$string['table_status_protect'] = 'Protect';

// Buttons.
$string['button_generatereport'] = 'Generate Report';

// Settings.
$string['settingspage_main'] = 'Main Settings';
$string['settingspage_dev'] = 'Developer Settings';
$string['settingspage_tasks'] = 'Scheduled Task Settings';
$string['settings_timeunit'] = 'Time Unit';
$string['settings_timeunit_desc'] = 'The unit of time used for calculating points, where one time unit equals one point.';
$string['settings_debugmode'] = 'Debug Mode';
$string['settings_debugmode_desc'] = 'Enables debug output for plugin-specific interactions. No need to use this if site-wide debug mode is enabled.';
$string['settings_courseparentweight'] = 'Course Parent Weight';
$string['settings_courseparentweight_desc'] = 'How important parent courses are when calculating score (meta enrolments).';
$string['settings_lowenrolmentsflag'] = 'Low Enrolments Flag';
$string['settings_lowenrolmentsflag_desc'] = 'The minimum number of enrolments before penalising a course in points.';
$string['settings_scorelimiter'] = 'Score Limiter';
$string['settings_scorelimiter_desc'] = 'The limiter used in calculations for percentages and score limiting (advanced).';
$string['settings_minimumage'] = 'Minimum Course Age';
$string['settings_minimumage_desc'] = 'The minimum age a course must be to be considered for deletion.';
$string['settings_scheduledduration'] = 'Scheduled Phase Task Length';
$string['settings_scheduledduration_desc'] = 'The time it takes for the "scheduled" task to be executed.';
$string['settings_notifyduration'] = 'Notification Phase Task Length';
$string['settings_notifyduration_desc'] = 'The time it takes for the "notified" task to be executed.';
$string['settings_hiddenduration'] = 'Hidden Phase Task Length';
$string['settings_hiddenduration_desc'] = 'The time it takes for the "hidden" task to be executed.';
$string['settings_waitingduration'] = 'Waiting Phase Task Length';
$string['settings_waitingduration_desc'] = 'The time it takes for the "waiting" task to be executed.';
$string['settings_notifymode'] = 'Email Mode';
$string['settings_notifymode_desc'] = 'Email mode for notifying managers and teachers pior to deletion of courses.';
$string['settings_childprotection'] = 'Child course protection.';
$string['settings_childprotection_desc'] = 'Protect child course by preventing parent deletion (meta enrolments).';
$string['settings_enddateprotection'] = 'End date protection.';
$string['settings_enddateprotection_desc'] = 'Protect courses that end date is set in the future from being deleted.';
$string['settings_donotnotifyhidden'] = 'Do not notify hidden courses.';
$string['settings_donotnotifyhidden_desc'] = 'Owners of courses that are not visible will not be notified.';

// Report_details page.
$string['report_details_pageheader'] = 'Report Details';
$string['report_details_coursedetailsfor'] = 'Course details for: ';
$string['report_details_report_date'] = 'Last updated on';
$string['report_details_enrolmentinformation'] = 'Enrolment Information';
$string['report_details_scoreinformation'] = 'Deletion Score';
$string['report_details_scorecreated'] = 'Score created on';
$string['report_details_action_delete'] = 'Schedule Deletion';
$string['report_details_return'] = 'Return to Table';
$string['report_details_action_reset'] = 'Reset Action';
$string['report_details_action_logs'] = 'Action History Log';
$string['report_details_action_protect'] = 'Protect Course';
$string['report_details_actionstatus'] = 'Action Status';
$string['report_details_actionstatus_scheduled'] = 'Scheduled for deletion';

// Form stuff.
$string['report_form_filter_results'] = 'Filter Results';
$string['report_form_pagesize'] = 'Entries per page';
$string['report_form_matchstringshort'] = 'Search by short name (and ID number)';
$string['report_form_matchstringfull'] = 'Search by full name';
$string['report_form_filter_categories'] = 'Filter by category';
$string['report_form_filter_categories_noselectionstring'] = 'Select a category';
$string['report_form_filter_reports'] = 'Select a report';
$string['report_form_filter_reports_noselectionstring'] = 'Showing latest report';
$string['report_form_filter_options'] = 'Filter Options';
$string['report_form_date_options'] = 'Date Options';
$string['report_form_flag_options'] = 'Flag Options';
$string['report_form_filter_coursetimecreatedafter'] = 'Created After';
$string['report_form_filter_coursetimecreatedbefore'] = 'Created Before';
$string['report_form_filter_coursestartdateafter'] = 'Start Date After';
$string['report_form_filter_coursestartdatebefore'] = 'Start Date Before';
$string['report_form_filter_courseenddateafter'] = 'End Date After';
$string['report_form_filter_courseenddatebefore'] = 'End Date Before';
$string['report_form_filter_coursetimeaccessafter'] = 'Last Access After';
$string['report_form_filter_coursetimeaccessbefore'] = 'Last Access Before';
$string['report_form_filter_coursetimecreatednotset'] = 'Time Created Not Set (or equals 0)';
$string['report_form_filter_coursestartdatenotset'] = 'Start Date Not Set (or equals 0)';
$string['report_form_filter_courseenddatenotset'] = 'End Date Not Set (or equals 0)';
$string['report_form_filter_coursetimeaccessnotset'] = 'Last Access Not Set (or equals 0)';
$string['report_form_filter_displayactiondata'] = 'Display Action Data';
$string['report_form_filterbycourseids'] = 'Filter by course ids';
$string['report_form_filterbycourseids_noselectionstring'] = 'No courses filtered';
$string['report_form_filteractiondata'] = 'Filter by action';
$string['report_form_filteractiondata_noselectionstring'] = 'Select an action';
$string['report_form_filter_display_action_null'] = 'Courses not set';
$string['report_form_filter_display_action_delete'] = 'Courses to delete';
$string['report_form_filter_display_action_protect'] = 'Protected courses';
$string['report_form_filter_hideshowmetachildren'] = 'Filter by child status';
$string['report_form_filter_hideshowmetaparents'] = 'Filter by parent status';
$string['report_form_filter_hideshowhiddencourses'] = 'Filter by course visibility status';
$string['hideshowmetachildren_hideonly'] = 'Hide child courses';
$string['hideshowmetachildren_showonly'] = 'Show only child courses';
$string['hideshowmetaparents_hideonly'] = 'Hide parent courses';
$string['hideshowmetaparents_showonly'] = 'Show only parent courses';
$string['hideshowhiddencourses_hideonly'] = 'Hide visible courses';
$string['hideshowhiddencourses_showonly'] = 'Show only visible courses';


$string['action_form_chooseaction'] = 'missing string!';

// Action_form.
$string['action_form_withselected'] = 'With selected...';
$string['action_form_scheduledelete'] = 'Schedule Deletion';
$string['action_form_protectcourse'] = 'Protect Course';
$string['action_form_resetaction'] = 'Reset Action';

// Tasks.
$string['task_wrangle'] = 'Wrangle Task';
$string['task_score'] = 'Score Task';
$string['task_filldata'] = 'Fill Data Task';

// Messages.
$string['message_deletesubject'] = 'Course Deletion Notice';
$string['message_contexturlname'] = 'Deletion course list';
$string['message_scheduleddeletion_h1'] = 'Course Deletion Notice';
$string['message_scheduleddeletion_h2'] = 'List of courses:';
$string['message_scheduleddeletion_p1'] = 'Some courses that you are enroled in have been identified as old and are marked for deletion.';
$string['message_scheduleddeletion_p2'] = 'Please make sure any important resources in these courses have been backed up, if not then make sure to do so as soon as possible.';
$string['message_scheduleddeletion_p3'] = 'If you believe this is a mistake, please contact <a href="#">someone@chi.ac.uk</a> but please note that due to our retention policies, old courses have to be deleted after they are no longer in use.';
$string['message_scheduleddeletion_p4'] = 'Thank you,';
$string['message_scheduleddeletion_p5'] = 'Admin Team at <a href="https://moodle.chi.ac.uk/">moodle.chi.ac.uk';
$string['message_scheduleddeletion_accesslistofallcourses'] = 'Access list of all courses on the deletion list.';

// Other.
$string['select_an_option'] = 'Select an option';
