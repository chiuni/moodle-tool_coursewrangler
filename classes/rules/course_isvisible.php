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

class course_isvisible extends rule implements rule_interface
{
    public function evaluate_condition(): bool {
        // If this course has children, we do not evaluate
        // its visibility.
        if ($this->course->coursechildren != null) {
            return $this->state;
        }
        // This checks to make sure the values are valid.
        if ($this->course->coursevisible == 1 ||
            $this->course->coursevisible == 0) {
            $this->state = true;
        }
        return $this->state;
    }
    public function calculate_score(): float {
        // If state is not true, then cannot calculate score, return default.
        if (!$this->state) {
            return $this->score;
        }
        // If is course visible, reduce deletion score by 25.
        $this->score = -50;
        if ($this->course->coursevisible == 0) {
            // Else, add 50 to deletion score.
            $this->score = 100;
        }
        return $this->score;
    }
    public function set_params() {
        // The only param needed for this is coursevisible.
        $this->params = [];
        $this->params[] = 'coursevisible';
        // Course children can be null, so we don't use it here.
        // $this->params[] = 'coursechildren';
    }
}
