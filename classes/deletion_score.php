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

namespace tool_coursewrangler;

use stdClass;

class deletion_score
{
    protected int $courseparentweight = 10;
    protected int $lowenrolmentsflag = 10;
    protected int $timeunit = 86400;
    protected int $scorelimiterpositive = 400;
    protected int $ratiolimit;
    protected stdClass $scores;
    protected array $courses;

    public function __construct(array $courses = []) {
        // Initialising settings.
        // This makes parent courses more or less important.
        $this->courseparentweight =
            (int) get_config('tool_coursewrangler', 'courseparentweight') ?? 10;
        // This triggers a low score for courses with less enrolments than n enrolments.
        $this->lowenrolmentsflag =
            (int) get_config('tool_coursewrangler', 'lowenrolmentsflag') ?? 10;
        // This makes each time unit = 1 score point.
        $this->timeunit =
            (int) get_config('tool_coursewrangler', 'timeunit') ?? 86400;
        // This is the value used for limiting each score to a upper/lower limit.
        $this->score_limiter =
            (int) get_config('tool_coursewrangler', 'scorelimiter') ?? 400;
        // Preventing zeros, they cause division by zero errors.
        $this->courseparentweight =
            $this->courseparentweight > 0 ? $this->courseparentweight : 10;
        $this->lowenrolmentsflag =
            $this->lowenrolmentsflag > 0 ? $this->lowenrolmentsflag : 10;
        $this->timeunit =
            $this->timeunit > 0 ? $this->timeunit : 86400;
        $this->score_limiter =
            $this->score_limiter > 0 ? $this->score_limiter : 400;
        if (empty($courses)) {
            return;
        }
        foreach ($courses as $course) {
            $course = $this->apply_rules($course);
            $course = $this->make_score($course);
        }
        $this->courses = $courses;
    }

    public function get_courses() : array {
        return $this->courses ?? [];
    }

    public function apply_rules(stdClass $course) : stdClass {
        $rules = [];
        $settings = [
            'timeunit' => $this->timeunit
        ];
        $rules['course_lastaccess'] =
            new rules\course_lastaccess($course, $settings);
        $rules['course_haschildren'] =
            new rules\course_haschildren($course);
        $rules['course_isvisible'] =
            new rules\course_isvisible($course);
        $rules['course_noenrol'] =
            new rules\course_noenrol($course);
        $rules['course_isover'] =
            new rules\course_isover($course, $settings);
        $rules['course_neverused'] =
            new rules\course_neverused($course);
        $course->rules = $rules;
        return $course;
    }

    public function make_score(stdClass $course) : stdClass {
        $score = new stdClass;
        $score->raw = 0;
        $score->rounded = 0;
        $score->percentage = 0;
        if (!isset($course->rules)) {
            return false;
        }
        $ratiolimit = count($course->rules) * $this->score_limiter;
        foreach ($course->rules as $rule) {
            // Setting score in different forms.
            $score->raw += $rule->get_limit_score($this->score_limiter) ?? 0;
        }
        $score->rounded = round($score->raw, 2) ?? 0;
        $score->percentage = round(($score->raw / $ratiolimit) * 100, 2) ?? 0;
        $course->score = $score;
        return $course;
    }
}
