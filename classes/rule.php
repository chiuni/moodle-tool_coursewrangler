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
 * The parent Rule class.
 * @package   tool_coursewrangler
 * @author    Hugo Soares <h.soares@chi.ac.uk>
 * @copyright 2020 University of Chichester {@link www.chi.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// More Info: https://docs.moodle.org/dev/Coding_style#Namespaces
namespace tool_coursewrangler;

use stdClass;
use tool_coursewrangler\traits\score_limit;

abstract class rule {
    use score_limit;

    protected stdClass $course;
    protected array $settings;
    protected array $params;
    protected bool $state;
    protected bool $has_param;
    public float $score;

    public function __construct(
        stdClass $course,
        array $settings = []
    ) {
        $this->state        = false;
        $this->score        = 0.0;
        $this->settings     = $settings;
        $this->course       = $course;
        $this->set_params();
        $this->has_params   = $this->has_params();
        if (!$this->has_params) {
            return false;
        }
        $this->evaluate_condition();
        $this->calculate_score();
        unset($this->course);
    }

    public abstract function evaluate_condition();
    public abstract function calculate_score();
    public abstract function set_params();

    public function has_params(): bool {
        if (!isset($this->params)) {
            return false;
        }
        foreach ($this->params as $param) {
            if (!isset($this->course->$param)) {
                return false;
            }
        }
        return true;
    }
}
