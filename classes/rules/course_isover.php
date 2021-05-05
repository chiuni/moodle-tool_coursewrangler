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
 * This is a rule implementation class.
 * 
 * @package   tool_coursewrangler
 * @author    Hugo Soares <h.soares@chi.ac.uk>
 * @copyright 2020 University of Chichester {@link www.chi.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// More Info: https://docs.moodle.org/dev/Coding_style#Namespaces
namespace tool_coursewrangler\rules;

use tool_coursewrangler\interfaces\rule as rule_interface;
use tool_coursewrangler\rule;

class course_isover extends rule implements rule_interface
{
    function evaluate_condition(): bool
    {
        // If this course has children, do not apply this rule.
        // This is because this course is being used for meta enrolments.
        if ($this->course->course_children != null) {
            return $this->state;
        }
        // If course has open ended date, do not apply this rule.
        if ($this->course->course_enddate == 0) {
            return $this->state;
        }
        $this->state = true;
        return $this->state;
    }
    function calculate_score(): float
    {
        // If state is not true, then cannot calculate score, return default.
        if (!$this->state) {
            return $this->score;
        }
        // If course is not over, lower score by 50.
        $this->score = -50;
        // If is course date is older than current date, means course is over.
        if ($this->course->course_enddate < time()) {
            // Else, add 100 to deletion score.
            $this->score = 100;
            // And for each time-period past the course enddate, we add a score point.
            $time_period = $this->settings['time_unit'];
            $this->score += (time() - $this->course->course_enddate) / $time_period;
        }
        return $this->score;
    }
    
    function set_params()
    {
        // The only param needed for this is course_visible.
        $this->params = [];
        $this->params[] = 'course_enddate';
    }
}
