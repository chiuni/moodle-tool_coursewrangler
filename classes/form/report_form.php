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
 * @package   tool_coursewrangler
 * @author    Hugo Soares <h.soares@chi.ac.uk>
 * @copyright 2020 University of Chichester {@link www.chi.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_coursewrangler\form;

use moodleform;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

class report_form extends moodleform {
    // Add elements to form.
    public function definition() {
        global $DB;

        $mform = $this->_form;
        $customdata = $this->_customdata;

        // Header filter options.
        $bycourseidsareanames = [];
        $allmetrics = $DB->get_records('tool_coursewrangler_metrics');
        foreach ($allmetrics as $course) {
            $bycourseidsareanames[$course->courseid] =
                $course->courseidnumber ?? $course->courseshortname ?? $course->coursefullname ?? $course->courseid;
        }
        $bycourseidsoptions = [
            'multiple' => true,
            'showsuggestions' => false,
            'tags' => true,
            'noselectionstring' => get_string('report_form_filterbycourseids_noselectionstring', 'tool_coursewrangler'),
        ];

        $mform->addElement(
            'autocomplete',
            'filterbycourseids',
            get_string('report_form_filterbycourseids', 'tool_coursewrangler'),
            $bycourseidsareanames,
            $bycourseidsoptions
        );
        // Short string match for course idnumber and course short name.
        $mform->addElement(
            'text',
            'matchstringshort',
            get_string('report_form_matchstringshort', 'tool_coursewrangler')
        );
        // Full string match for course full name.
        $mform->addElement(
            'text',
            'matchstringfull',
            get_string('report_form_matchstringfull', 'tool_coursewrangler')
        );
        $pagesizeoptions = [50 => 50, 100 => 100, 250 => 250, 500 => 500];
        $mform->addElement(
            'select',
            'pagesize',
            get_string('report_form_pagesize', 'tool_coursewrangler'),
            $pagesizeoptions
        );
        // Autocomplete search box for categories.
        $categories = $DB->get_records('course_categories', ['parent' => 0], 'name ASC');
        $categoryareanames = [];
        foreach ($categories as $id => $category) {
            $category->idnumber = $category->idnumber ?? '*no id number*'; // To do: missing get_string, improve this.
            $categoryareanames[$id] = $category->name . ": $category->idnumber";
        }
        $categoryoptions = [
                'multiple' => true,
                'noselectionstring' => get_string('report_form_filter_categories_noselectionstring', 'tool_coursewrangler'),
        ];
        $mform->addElement(
            'autocomplete',
            'categoryids',
            get_string('report_form_filter_categories', 'tool_coursewrangler'),
            $categoryareanames,
            $categoryoptions
        );
        $mform->addElement(
            'checkbox',
            'displayactiondata',
            get_string('report_form_filter_displayactiondata', 'tool_coursewrangler')
        );
        $actionsareanames = [
            'null' => get_string('report_form_filter_display_action_null', 'tool_coursewrangler'),
            'delete' => get_string('report_form_filter_display_action_delete', 'tool_coursewrangler'),
            'protect' => get_string('report_form_filter_display_action_protect', 'tool_coursewrangler')
        ];
        $actionsoptions = [
            'multiple' => true,
            'noselectionstring' => get_string('report_form_filteractiondata_noselectionstring', 'tool_coursewrangler'),
        ];

        $mform->addElement(
            'autocomplete',
            'filteractiondata',
            get_string('report_form_filteractiondata', 'tool_coursewrangler'),
            $actionsareanames,
            $actionsoptions
        );

        $mform->hideIf('filteractiondata', 'displayactiondata');

        // Header date options.
        $mform->addElement(
            'header',
            'header_date_options',
            get_string('report_form_date_options',
            'tool_coursewrangler')
        );
        $mform->setExpanded('header_date_options', false);
        if (
            isset($customdata['coursetimecreatedafter']) ||
            isset($customdata['coursetimecreatedbefore']) ||
            isset($customdata['coursetimecreatednotset']) ||
            isset($customdata['coursestartdateafter']) ||
            isset($customdata['coursestartdatebefore']) ||
            isset($customdata['coursestartdatenotset']) ||
            isset($customdata['courseenddateafter']) ||
            isset($customdata['courseenddatebefore']) ||
            isset($customdata['courseenddatenotset']) ||
            isset($customdata['coursetimeaccessafter']) ||
            isset($customdata['coursetimeaccessbefore']) ||
            isset($customdata['coursetimeaccessnotset'])
        ) {
            $mform->setExpanded('header_date_options', true);
        }

        // COURSE_TIMECREATED.
        $mform->addElement(
            'date_selector',
            'coursetimecreatedafter',
            get_string('report_form_filter_coursetimecreatedafter', 'tool_coursewrangler'),
            ['optional' => true]
        );
        $mform->addElement(
            'date_selector',
            'coursetimecreatedbefore',
            get_string('report_form_filter_coursetimecreatedbefore', 'tool_coursewrangler'),
            ['optional' => true]
        );
        // COURSE_TIMECREATED_NOTSET.
        $mform->addElement(
            'checkbox',
            'coursetimecreatednotset',
            get_string('report_form_filter_coursetimecreatednotset', 'tool_coursewrangler')
        );

        // COURSE_STARTDATE.
        $mform->addElement(
            'date_selector',
            'coursestartdateafter',
            get_string('report_form_filter_coursestartdateafter', 'tool_coursewrangler'),
            ['optional' => true]
        );
        $mform->addElement(
            'date_selector',
            'coursestartdatebefore',
            get_string('report_form_filter_coursestartdatebefore', 'tool_coursewrangler'),
            ['optional' => true]
        );
        // COURSE_STARTDATE_NOTSET.
        $mform->addElement(
            'checkbox',
            'coursestartdatenotset',
            get_string('report_form_filter_coursestartdatenotset', 'tool_coursewrangler')
        );

        // COURSE_ENDDATE.
        $mform->addElement(
            'date_selector',
            'courseenddateafter',
            get_string('report_form_filter_courseenddateafter', 'tool_coursewrangler'),
            ['optional' => true]
        );
        $mform->addElement(
            'date_selector',
            'courseenddatebefore',
            get_string('report_form_filter_courseenddatebefore', 'tool_coursewrangler'),
            ['optional' => true]
        );
        // COURSE_ENDDATE_NOTSET.
        $mform->addElement(
            'checkbox',
            'courseenddatenotset',
            get_string('report_form_filter_courseenddatenotset', 'tool_coursewrangler')
        );

        // COURSE_TIMEACCESS.
        $mform->addElement(
            'date_selector',
            'coursetimeaccessafter',
            get_string('report_form_filter_coursetimeaccessafter', 'tool_coursewrangler'),
            ['optional' => true]
        );
        $mform->addElement(
            'date_selector',
            'coursetimeaccessbefore',
            get_string('report_form_filter_coursetimeaccessbefore', 'tool_coursewrangler'),
            ['optional' => true]
        );
        // COURSE_TIMEACCESS_NOTSET.
        $mform->addElement(
            'checkbox',
            'coursetimeaccessnotset',
            get_string('report_form_filter_coursetimeaccessnotset', 'tool_coursewrangler')
        );

        // HEADER FLAG OPTIONS.
        $mform->addElement(
            'header',
            'header_flag_options',
            get_string('report_form_flag_options',
            'tool_coursewrangler')
        );
        $mform->setExpanded('header_flag_options', false);

        if (
            (isset($customdata['hideshowmetaparents']) && $customdata['hideshowmetaparents'] != 'default') ||
            (isset($customdata['hideshowhiddencourses']) && $customdata['hideshowhiddencourses'] != 'default')
        ) {
            $mform->setExpanded('header_flag_options', true);
        }
        // Hideshow_meta_children.
        $metachildrenoptions = [
            'default' => get_string('select_an_option', 'tool_coursewrangler'),
            'hide' => get_string('hideshowmetachildren_hideonly', 'tool_coursewrangler'),
            'show' => get_string('hideshowmetachildren_showonly', 'tool_coursewrangler')
        ];
        $mform->addElement(
            'select',
            'hideshowmetachildren',
            get_string('report_form_filter_hideshowmetachildren', 'tool_coursewrangler'),
            $metachildrenoptions
        );
        // Hideshowmetaparents.
        $metaparentsoptions = [
            'default' => get_string('select_an_option', 'tool_coursewrangler'),
            'hide' => get_string('hideshowmetaparents_hideonly', 'tool_coursewrangler'),
            'show' => get_string('hideshowmetaparents_showonly', 'tool_coursewrangler')
        ];
        $mform->addElement(
            'select',
            'hideshowmetaparents',
            get_string('report_form_filter_hideshowmetaparents', 'tool_coursewrangler'),
            $metaparentsoptions
        );
        // Hideshowhiddencourses.
        $metaparentsoptions = [
            'default' => get_string('select_an_option', 'tool_coursewrangler'),
            'hide' => get_string('hideshowhiddencourses_hideonly', 'tool_coursewrangler'),
            'show' => get_string('hideshowhiddencourses_showonly', 'tool_coursewrangler')
        ];
        $mform->addElement(
            'select',
            'hideshowhiddencourses',
            get_string('report_form_filter_hideshowhiddencourses', 'tool_coursewrangler'),
            $metaparentsoptions
        );
        // Filter button.
        $this->add_action_buttons(true, get_string('report_form_filter_results', 'tool_coursewrangler'));

    }
    // Custom validation should be added here.
    public function validation($data, $files) {
        return array();
    }
}
