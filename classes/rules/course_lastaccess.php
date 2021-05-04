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

class course_lastaccess extends rule implements rule_interface
{
    function evaluate_condition(): bool
    {
        // We lack the data to be able to make an educated assumption, 
        //  therefore we won't.
        if ($this->course->course_timeaccess == 0) {
            return $this->state;
        }
        if ($this->course->course_timeaccess > $this->course->course_timecreated) {
            $this->state = true;
        }
        return $this->state;
    }
    function calculate_score(): float
    {
        if (!$this->state) {
            return $this->score;
        }
        // Given the time unit set in settings, score becomes how many time
        //  units have passed since last access. Can only be positive. Time
        //  units are configurable in settings.
        $this->score = (time() - $this->course->course_timeaccess) / $this->settings['time_unit'];
        return $this->score;
    }
    function set_params()
    {
        $this->params = [];
        $this->params[] = 'course_timeaccess';
        $this->params[] = 'course_timecreated';
    }
}
