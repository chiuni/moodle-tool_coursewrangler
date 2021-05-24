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
 * @package   tool_coursewrangler
 * @author    Hugo Soares <h.soares@chi.ac.uk>
 * @copyright 2020 University of Chichester {@link www.chi.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_coursewrangler\rules;

use tool_coursewrangler\interfaces\rule as rule_interface;
use tool_coursewrangler\rule;

class course_neverused extends rule implements rule_interface
{
    public function evaluate_condition(): bool {
        // If this course has children, do not apply this rule.
        // This is because this course is being used for meta enrolments.
        if ($this->course->coursechildren != null) {
            return $this->state;
        }
        // If it has been accessed, do not apply rule.
        if ($this->course->coursetimeaccess != 0) {
            return $this->state;
        }
        // If course has at least 2 activities, do not apply rule.
        if ($this->course->coursemodulescount > 1) {
            return $this->state;
        }
        $this->state = true;
        return $this->state;
    }
    public function calculate_score(): float {
        // If state is not true, then cannot calculate score, return default.
        if (!$this->state) {
            return $this->score;
        }
        // Simply raise the score by 100 if state is true.
        $this->score = 100;
        return $this->score;
    }
    public function set_params() {
        // The only param needed for this is coursevisible.
        $this->params = [];
        $this->params[] = 'totalenrolcount';
        $this->params[] = 'coursetimeaccess';
        $this->params[] = 'coursemodulescount';
    }
}
