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

$string['table_course_id'] = 'Course ID';
$string['table_course_module_id'] = 'Course Module ID';
$string['table_course_fullname'] = 'Name';
$string['table_course_shortname'] = 'Short name';
$string['table_course_idnumber'] = 'ID Number';
$string['table_course_startdate'] = 'Start date';
$string['table_course_enddate'] = 'End date';
$string['table_course_timecreated'] = 'Created';
$string['table_course_timemodified'] = 'Last modified';
$string['table_course_visible'] = 'Visible';
$string['table_activity_type'] = 'Activity type';
$string['table_activity_lastmodified'] = 'Activities last modified';
$string['table_course_timeaccess'] = 'Last access';
$string['table_course_isparent'] = 'Parent';
$string['table_course_modulescount'] = 'Total activities';
$string['table_course_lastenrolment'] = 'Last enrolment';
$string['table_course_deletionscore'] = 'Deletion Score';
$string['table_total_enrol_count'] = 'Total Enrolments';
$string['table_active_enrol_count'] = 'Active Enrolments';
$string['table_self_enrol_count'] = 'Self Enrolments';
$string['table_manual_enrol_count'] = 'Manual Enrolments';
$string['table_meta_enrol_count'] = 'Meta Enrolments';
$string['table_other_enrol_count'] = 'Other Enrolments';
$string['table_suspended_enrol_count'] = 'Suspended Enrolments';
$string['table_score_raw'] = 'Raw Score';
$string['table_score_rounded'] = 'Rounded Score';
$string['table_score_percentage'] = 'Percentage';
$string['table_percentage_notavailable'] = 'Not Available';

// buttons
$string['button_generatereport'] = 'Generate Report';

// settings
$string['settingspage_main'] = 'Main Settings';
$string['settings_timeunit'] = 'Time Unit';
$string['settings_timeunit_desc'] = 'The unit of time used for calculating points, where one time unit equals one point.';
$string['settings_courseparentweight'] = 'Course Parent Weight';
$string['settings_courseparentweight_desc'] = 'How important parent courses are when calculating score (meta enrolments).';
$string['settings_lowenrolmentsflag'] = 'Low Enrolments Flag';
$string['settings_lowenrolmentsflag_desc'] = 'The minimum number of enrolments before penalising a course in points.';
$string['settings_scorelimiter'] = 'Score Limiter';
$string['settings_scorelimiter_desc'] = 'The limiter used in calculations for percentages and score limiting (advanced).';

// report_details page
$string['report_details_pageheader'] = 'Report Details';
$string['report_details_coursedetailsfor'] = 'Course details for: ';
$string['report_details_report_date'] = 'Report created on';
$string['report_details_enrolmentinformation'] = 'Enrolment Information';
$string['report_details_scoreinformation'] = 'Deletion Score';
$string['report_details_scorecreated'] = 'Score created on';

// form stuff
$string['report_form_filter_results'] = 'Filter Results';
$string['report_form_filter_categories'] = 'Filter by category';
$string['report_form_filter_categories_noselectionstring'] = 'Select a category';
$string['report_form_filter_reports'] = 'Select a report';
$string['report_form_filter_reports_noselectionstring'] = 'Showing latest report';
$string['report_form_filter_options'] = 'Filter Options';
$string['report_form_date_options'] = 'Date Options';
$string['report_form_filter_course_startdate_after'] = 'Start Date After';
$string['report_form_filter_course_startdate_before'] = 'Start Date Before';