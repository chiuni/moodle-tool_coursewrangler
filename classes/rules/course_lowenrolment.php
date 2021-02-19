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
namespace tool_coursewrangler\rules;

use tool_coursewrangler\interfaces\rule as rule_interface;
use tool_coursewrangler\traits\score_limit;

class course_lowenrolment implements rule_interface
{
    use score_limit;
    /**
     * @param int $param1
     * @param int $param2
     */
    function __construct(int $total_enrol_count, int $low_enrolments_flag)
    {
        $this->description  = 'Course Total Enrolments <= Low Enrolment Number';
        $this->state        = false;
        $this->score        = 0;

        $components = [
            'total_enrol_count' => $total_enrol_count
        ];
        $settings = [
            'low_enrolments_flag' => $low_enrolments_flag
        ];
        
        $this->state = $this->evaluate_condition($components, $settings) ?? false;
        $this->score = $this->calculate_score($components, $settings) ?? 0;
    }
    function evaluate_condition(array $components, array $settings = []): bool
    {
        return ($components['total_enrol_count'] <= $settings['low_enrolments_flag']);
    }
    function calculate_score(array $components, array $settings = []): float
    {
        return ($components['total_enrol_count'] > 0) ? ((1 / $components['total_enrol_count']) * 50) : 0;
    }
}
