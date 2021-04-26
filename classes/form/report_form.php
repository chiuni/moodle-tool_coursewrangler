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
 * @author    Hugo Soares <h.soares@chi.ac.uk>
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

        $mform = $this->_form;
        $customdata = $this->_customdata;

        // HEADER FILTER OPTIONS

        // Short string match for course idnumber and course short name.
        $mform->addElement(
            'text', 
            'matchstring_short', 
            get_string('report_form_matchstring_short', 'tool_coursewrangler')
        );        
        // Full string match for course full name.
        $mform->addElement(
            'text', 
            'matchstring_full', 
            get_string('report_form_matchstring_full', 'tool_coursewrangler')
        );
        $pagesize_options = [50=>50, 100=>100, 250=>250, 500=>500];
        $mform->addElement(
            'select', 
            'pagesize', 
            get_string('report_form_pagesize', 'tool_coursewrangler'), 
            $pagesize_options
        );

        // Autocomplete search box for categories.
        $categories = $DB->get_records('course_categories', ['parent' => 0], 'name ASC');
        $category_areanames = [];
        foreach ($categories as $id => $category) {
            $category->idnumber = $category->idnumber ?? '*no id number*'; // To do: missing get_string, improve this.
            $category_areanames[$id] = $category->name . ": $category->idnumber";
        }
        $category_options = [
                'multiple' => true,
                'noselectionstring' => get_string('report_form_filter_categories_noselectionstring', 'tool_coursewrangler'),
        ];
        $mform->addElement(
            'autocomplete', 
            'category_ids', 
            get_string('report_form_filter_categories', 'tool_coursewrangler'), 
            $category_areanames, 
            $category_options
        );

        $mform->addElement(
            'checkbox', 
            'display_action_data', 
            get_string('report_form_filter_display_action_data', 'tool_coursewrangler')
        );
        
        $actions_areanames = [
            'null' => get_string('report_form_filter_display_action_null', 'tool_coursewrangler'),
            'delete' => get_string('report_form_filter_display_action_delete', 'tool_coursewrangler'),
            'protect' => get_string('report_form_filter_display_action_protect', 'tool_coursewrangler')
        ];
        $actions_options = [
            'multiple' => true,
            'noselectionstring' => get_string('report_form_filter_action_data_noselectionstring', 'tool_coursewrangler'),
        ];

        $mform->addElement(
            'autocomplete', 
            'filter_action_data', 
            get_string('report_form_filter_action_data', 'tool_coursewrangler'), 
            $actions_areanames, 
            $actions_options
        );

        $mform->hideIf('filter_action_data', 'display_action_data');

        // HEADER DATE OPTIONS
        $mform->addElement(
            'header', 
            'header_date_options', 
            get_string('report_form_date_options', 
            'tool_coursewrangler')
        );
        $mform->setExpanded('header_date_options', false);
        if (
            isset($customdata['course_timecreated_after']) ||
            isset($customdata['course_timecreated_before']) ||
            isset($customdata['course_startdate_after']) ||
            isset($customdata['course_startdate_before']) ||
            isset($customdata['course_enddate_after']) ||
            isset($customdata['course_enddate_before']) ||
            isset($customdata['course_timeaccess_after']) ||
            isset($customdata['course_timeaccess_before'])
        ){
            $mform->setExpanded('header_date_options', true);
        }

        // COURSE_TIMECREATED
        $mform->addElement(
            'date_selector', 
            'course_timecreated_after', 
            get_string('report_form_filter_course_timecreated_after', 'tool_coursewrangler'), 
            ['optional' => true]
        );
        $mform->addElement(
            'date_selector', 
            'course_timecreated_before', 
            get_string('report_form_filter_course_timecreated_before', 'tool_coursewrangler'), 
            ['optional' => true]
        );

        // COURSE_STARTDATE
        $mform->addElement(
            'date_selector', 
            'course_startdate_after', 
            get_string('report_form_filter_course_startdate_after', 'tool_coursewrangler'), 
            ['optional' => true]
        );
        $mform->addElement(
            'date_selector', 
            'course_startdate_before', 
            get_string('report_form_filter_course_startdate_before', 'tool_coursewrangler'), 
            ['optional' => true]
        );

        // COURSE_ENDDATE
        $mform->addElement(
            'date_selector', 
            'course_enddate_after', 
            get_string('report_form_filter_course_enddate_after', 'tool_coursewrangler'), 
            ['optional' => true]
        );
        $mform->addElement(
            'date_selector', 
            'course_enddate_before', 
            get_string('report_form_filter_course_enddate_before', 'tool_coursewrangler'), 
            ['optional' => true]
        );

        // COURSE_TIMEACCESS
        $mform->addElement(
            'date_selector', 
            'course_timeaccess_after', 
            get_string('report_form_filter_course_timeaccess_after', 'tool_coursewrangler'), 
            ['optional' => true]
        );
        $mform->addElement(
            'date_selector', 
            'course_timeaccess_before', 
            get_string('report_form_filter_course_timeaccess_before', 'tool_coursewrangler'), 
            ['optional' => true]
        );

        // HEADER FLAG OPTIONS
        $mform->addElement(
            'header', 
            'header_flag_options', 
            get_string('report_form_flag_options', 
            'tool_coursewrangler')
        );
        $mform->setExpanded('header_flag_options', false);

        // COURSE_TIMECREATED_NOTSET
        $mform->addElement(
            'checkbox', 
            'course_timecreated_notset', 
            get_string('report_form_filter_course_timecreated_notset', 'tool_coursewrangler')
        );

        // COURSE_STARTDATE_NOTSET
        $mform->addElement(
            'checkbox', 
            'course_startdate_notset', 
            get_string('report_form_filter_course_startdate_notset', 'tool_coursewrangler')
        );

        // COURSE_ENDDATE_NOTSET
        $mform->addElement(
            'checkbox', 
            'course_enddate_notset', 
            get_string('report_form_filter_course_enddate_notset', 'tool_coursewrangler')
        );

        // COURSE_TIMEACCESS_NOTSET
        $mform->addElement(
            'checkbox', 
            'course_timeaccess_notset', 
            get_string('report_form_filter_course_timeaccess_notset', 'tool_coursewrangler')
        );

        // filter button
        $this->add_action_buttons(true, get_string('report_form_filter_results', 'tool_coursewrangler'));

    }
    //Custom validation should be added here.
    function validation($data, $files)
    {
        return array();
    }



}
