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
namespace tool_coursewrangler;

use stdClass;

class deletion_score
{
    protected int $course_parent_weight = 10;
    protected int $low_enrolments_flag = 10;
    protected int $time_unit = 86400;
    protected int $score_limiter_positive = 400;
    protected int $score_limiter_negative = -400;
    protected int $ratio_limit;
    protected stdClass $scores;
    protected array $courses;

    function __construct(array $courses = [])
    {
        // initialising settings
        $this->course_parent_weight = (int) get_config('tool_coursewrangler', 'courseparentweight') ?? 10; // this makes parent courses more or less important
        $this->low_enrolments_flag = (int) get_config('tool_coursewrangler', 'lowenrolmentsflag') ?? 10; // this triggers a low score for courses with less enrolments than n enrolments
        $this->time_unit = (int) get_config('tool_coursewrangler', 'timeunit') ?? 86400; // this makes each time unit = 1 score point
        $this->score_limiter_positive = (int) get_config('tool_coursewrangler', 'scorelimiter') ?? 400; // this is the value used for limiting each score to a upper/lower limit
        $this->score_limiter_negative = (int) ($this->score_limiter_positive * -1);
        if (empty($courses)) {
            return;
        }
        foreach ($courses as $course) {
            $course = $this->make_rules($course) ?? $course;
            $course = $this->make_score($course) ?? $course;
        }
        $this->courses = $courses;
    }

    public function get_courses()
    {
        return $this->courses ?? [];
    }

    protected function make_rules(stdClass $course)
    {
        $rules = [];

        /**
         * #R1
         * Course End Date Rule
         * The information we have:
         *      The assigned end date of the course, could be 0 if not set.
         */
        $rules['course_enddate'] = [
            'statement_desc' => 'Course End Date != 0',
            'statement_bool' => (int) ($course->course_enddate != 0),
            'statement_score' => ((time() - $course->course_enddate) / $this->time_unit)
        ];

        /** 
         * #R2
         * Course Last Access Rule
         * The information we have:
         *      The last access by anyone enroled to the course, could be 0 if not accessed.
         *      The time the course was created
         */
        // TODO: Consider courses that havent yet started, should we ignore them?
        $rules['course_lastaccess'] = [
            'statement_desc' => 'Course Time Access > Couter Time Created',
            'statement_bool' => (int) ($course->course_timeaccess > $course->course_timecreated),
            'statement_score' => ((time() - $course->course_timeaccess) / $this->time_unit)
        ];

        /**
         * #R3
         * Course Settings Time Modified Rule
         * The information we have:
         *      The last time someone edited course settings (not including activies/resources on course page)
         *      The time the course was created
         */
        $rules['course_settings_timemodified'] = [
            'statement_desc' => 'Course Settings Time Modified != Couter Time Created',
            'statement_bool' => (int) ($course->course_timemodified != $course->course_timecreated),
            'statement_score' => (($course->course_timecreated - $course->course_timemodified) / $this->time_unit)
        ];

        /**
         * #R4
         * Activity Recently Modified Rule
         * The information we have:
         *      The last time an activity was changed
         *      The time the course was created
         */
        $rules['activity_last_modified'] = [
            'statement_desc' => 'Course Activity Last Modified != Couter Time Created',
            'statement_bool' => (int) ($course->activity_lastmodified != $course->course_timecreated),
            'statement_score' => (($course->course_timecreated - $course->activity_lastmodified) / $this->time_unit)
        ];

        /**
         * #R5
         * Course Is Parent Rule
         * The information we have:
         *      If the course is parent of other courses (meta enrolments count)
         */
        $rules['course_isparent'] = [
            'statement_desc' => 'Course is Parent != 0',
            'statement_bool' => (int) ($course->course_isparent != 0),
            'statement_score' => (0 - ($course->course_isparent * $this->course_parent_weight))
        ];

        /**
         * #R6
         * Course Last Enrolment Rule
         * The information we have:
         *      The date the last enrolment was created // TODO: should we make this student only role (architype) enrolment?
         */
        $rules['course_lastenrolment'] = [
            'statement_desc' => 'Course Last Enrolment > 0',
            'statement_bool' => (int) ($course->course_lastenrolment > 0),
            'statement_score' => ((time() - $course->course_lastenrolment) / $this->time_unit)
        ];

        /**
         * #R7
         * Course Low Enrolment Rule
         * The information we have:
         *      The number of enrolments and type of enrolments per course
         */
        $rules['course_lowenrolment'] = [
            'statement_desc' => 'Course Total Enrolments <= Low Enrolment Number',
            'statement_bool' => (int) ($course->course_students->total_enrol_count <= $this->low_enrolments_flag),
            'statement_score' => ((1 / $course->course_students->total_enrol_count) * 50)
        ];

        /** 
         * #R8
         * Course Is Visible Rule
         * The information we have:
         *      Whether the course is visible or not
         */
        $rules['course_isvisible'] = [
            'statement_desc' => 'Course Is Visible',
            'statement_bool' => (int) ($course->course_visible),
            'statement_score' => ($course->course_visible ? -25 : 50)
        ];

        // Resetting score where bool is 0
        foreach ($rules as $key => $rule) {
            if ($rule['statement_bool']) {
                // applying score limits
                // TODO: make easier to read
                $statement_score_limited = $rules[$key]['statement_score'];
                if ($rules[$key]['statement_score'] > $this->score_limiter_positive) {
                    $statement_score_limited = $this->score_limiter_positive;
                } else if ($rules[$key]['statement_score'] < $this->score_limiter_negative) {
                    $statement_score_limited = $this->score_limiter_negative;
                }
                if ($statement_score_limited != $rules[$key]['statement_score']) {
                    $rules[$key]['statement_score_limited'] = $statement_score_limited;
                }
                continue;
            }
            $rules[$key]['statement_score'] = 0;
        }
        $course->rules = $rules;
        return $course;
    }

    protected function make_score(stdClass $course)
    {
        $score = new stdClass;
        $score->raw = 0;
        $score->rounded = 0;
        $score->percentage = 0;
        if (!isset($course->rules)) {
            return false;
        }
        $ratio_limit = count($course->rules) * $this->score_limiter_positive;
        foreach ($course->rules as $rule) {
            // setting score in different forms
            $score->raw += $rule['statement_score_limited'] ?? $rule['statement_score'] ?? 0;
        }
        $score->rounded = round($score->raw, 2) ?? 0;
        $score->percentage = round(($score->raw / $ratio_limit) * 100, 2) ?? 0;
        $course->score = $score;
        return $course;
    }
}
