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

        $mform->addElement('text', 'email', get_string('missingstring', 'tool_coursewrangler')); // Add elements to your form
        $mform->setType('email', PARAM_NOTAGS); //Set type of element
        $mform->setDefault('email', 'Something here'); //Default value

        // autocomplete search box for categories
        $categories = $DB->get_records('course_categories', ['parent' => 0], 'name ASC');
        $areanames = array();
        foreach ($categories as $id => $category) {
            $category->idnumber = $category->idnumber ?? '*no id number*';
            $areanames[$id] = $category->name . ": $category->idnumber";
        }
        $options = array(
                'multiple' => true,
                'noselectionstring' => get_string('missingstring', 'tool_coursewrangler'),
            );
        $mform->addElement('autocomplete', 'categoryids', get_string('missingstring', 'tool_coursewrangler'), $areanames, $options);


        // show score button
        $mform->addElement('button', 'show_scores', get_string('missingstring', 'tool_coursewrangler'));
    }
    //Custom validation should be added here
    function validation($data, $files)
    {
        return array();
    }
}
