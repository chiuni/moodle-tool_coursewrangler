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
namespace tool_coursewrangler\rules;

use tool_coursewrangler\interfaces\rule as rule_interface;
use tool_coursewrangler\traits\score_limit;

class course_haschildren implements rule_interface
{
    use score_limit;
    function __construct(\stdClass $course, array $settings = [])
    {
        $this->description  = 'Course Has Children';
        $this->state        = false;
        $this->score        = 0;
        $this->settings     = $settings;

        if (!isset($course->course_children)) {
            return false;
        }
        
        $this->evaluate_condition($course);
        $this->calculate_score($course);
    }
    function evaluate_condition(\stdClass $course): bool
    {
        $children = explode(',', $course->course_children);
        if (count($children) > 0) {
            $this->state = true;
        }
        return $this->state;
    }
    function calculate_score(\stdClass $course): float
    {
        $children = explode(',', $course->course_children);
        $count = count($children);
        // For each children course, reduce score by 100 points.
        $this->score = $count * -100;
        return $this->score;
    }
}
