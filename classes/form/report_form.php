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
 * This file is a class example.
 * 
 * @package   tool_coursewrangler
 * @author    Mark Sharp <m.sharp@chi.ac.uk>
 * @copyright 2020 University of Chichester {@link www.chi.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// More Info: https://docs.moodle.org/dev/Coding_style#Namespaces

namespace tool_coursewrangler\form;

use moodleform;

defined('MOODLE_INTERNAL') || die();

//moodleform is defined in formslib.php
require_once("$CFG->libdir/formslib.php");


class report_form extends moodleform
{
    //Add elements to form
    public function definition()
    {
        global $DB;

        $mform = $this->_form; // Don't forget the underscore!
        $customdata = $this->_customdata;

        // autocomplete search box for reports
        $reports = $DB->get_records('tool_coursewrangler_report', [], 'timecreated DESC');
        $report_areanames = array();
        foreach ($reports as $id => $report) {
            $report->timecreated = userdate($report->timecreated);
            $report_areanames[$id] = $report->id . ' - ' . $report->timecreated . ' - ' . ucfirst($report->type) ;
        }
        $report_options = array(
            'multiple' => false,
            'noselectionstring' => get_string('report_form_filter_reports_noselectionstring', 'tool_coursewrangler'),
        );
        // HEADER FILTER OPTIONS
        $mform->addElement('header', 'filter', get_string('report_form_filter_options', 'tool_coursewrangler'));

        $mform->addElement('autocomplete', 'report_id', get_string('report_form_filter_reports', 'tool_coursewrangler'), $report_areanames, $report_options);

        // autocomplete search box for categories
        $categories = $DB->get_records('course_categories', ['parent' => 0], 'name ASC');
        $category_areanames = array();
        foreach ($categories as $id => $category) {
            $category->idnumber = $category->idnumber ?? '*no id number*';
            $category_areanames[$id] = $category->name . ": $category->idnumber";
        }
        $category_options = array(
                'multiple' => true,
                'noselectionstring' => get_string('report_form_filter_categories_noselectionstring', 'tool_coursewrangler'),
            );
        $mform->addElement('autocomplete', 'category_ids', get_string('report_form_filter_categories', 'tool_coursewrangler'), $category_areanames, $category_options);

        // HEADER DATE OPTIONS
        $mform->addElement('header', 'filter', get_string('report_form_date_options', 'tool_coursewrangler'));

        // COURSE_TIMECREATED
        $mform->addElement('date_selector', 'course_timecreated_after', get_string('report_form_filter_course_timecreated_after', 'tool_coursewrangler'), ['optional' => true]);
        $mform->addElement('date_selector', 'course_timecreated_before', get_string('report_form_filter_course_timecreated_before', 'tool_coursewrangler'), ['optional' => true]);

        // COURSE_STARTDATE

        $mform->addElement('date_selector', 'course_startdate_after', get_string('report_form_filter_course_startdate_after', 'tool_coursewrangler'), ['optional' => true]);
        $mform->addElement('date_selector', 'course_startdate_before', get_string('report_form_filter_course_startdate_before', 'tool_coursewrangler'), ['optional' => true]);

        // COURSE_ENDDATE

        $mform->addElement('date_selector', 'course_enddate_after', get_string('report_form_filter_course_enddate_after', 'tool_coursewrangler'), ['optional' => true]);
        $mform->addElement('date_selector', 'course_enddate_before', get_string('report_form_filter_course_enddate_before', 'tool_coursewrangler'), ['optional' => true]);

        // HEADER FLAG OPTIONS
        $mform->addElement('header', 'filter', get_string('report_form_flag_options', 'tool_coursewrangler'));

        // filter button
        $this->add_action_buttons(null, get_string('report_form_filter_results', 'tool_coursewrangler'));

    }
    //Custom validation should be added here
    function validation($data, $files)
    {
        return array();
    }



}
