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
        $this->score_limiter = (int) get_config('tool_coursewrangler', 'scorelimiter') ?? 400; // this is the value used for limiting each score to a upper/lower limit
        // $this->score_limiter_negative = (int) ($this->score_limiter_positive * -1);
        if (empty($courses)) {
            return;
        }
        foreach ($courses as $course) {
            $course = $this->apply_rules($course);
            $course = $this->make_score($course);
        }
        $this->courses = $courses;
    }

    public function get_courses() : array
    {
        return $this->courses ?? [];
    }

    public function apply_rules(stdClass $course) : stdClass
    {
        $rules = [];
        $settings = [
            'time_unit' => $this->time_unit
        ];
        /**
         * #R1
         * Course End Date Rule
         * The information we have:
         *      The assigned end date of the course, could be 0 if not set.
         */
        // $rules['course_enddate'] = new rules\course_enddate($course->course_enddate, $this->time_unit);

        /** 
         * #R2
         * Course Last Access Rule
         * The information we have:
         *      The last access by anyone enroled to the course, could be 0 if not accessed.
         *      The time the course was created
         */
        // To do: Consider courses that havent yet started, should we ignore them?
        // $rules['course_lastaccess'] = new rules\course_lastaccess($course, $settings);

        /**
         * #R3
         * Course Settings Time Modified Rule
         * The information we have:
         *      The last time someone edited course settings (not including activies/resources on course page)
         *      The time the course was created
         */
        // $rules['course_settings_timemodified'] = new rules\course_settings_timemodified($course->course_timemodified, $course->course_timecreated, $this->time_unit);
        
        /**
         * #R4
         * Activity Recently Modified Rule
         * The information we have:
         *      The last time an activity was changed
         *      The time the course was created
         */
        // $rules['activity_last_modified'] = new rules\activity_last_modified($course->activity_lastmodified, $course->course_timecreated, $this->time_unit);
       
        /**
         * #R5 
         * Course Has Children Rule
         * The information we have:
         *      
         */
        // $rules['course_haschildren'] = new rules\course_haschildren($course);

        /**
         * #R6
         * Course Last Enrolment Rule
         * The information we have:
         *      The date the last enrolment was created // TODO: should we make this student only role (architype) enrolment?
         */
        // $lastenrolment = $course->course_lastenrolment ?? 0;
        // $rules['course_lastenrolment'] = new rules\course_lastenrolment($lastenrolment, $this->time_unit);

        /**
         * #R7
         * Course Low Enrolment Rule
         */
        // $rules['course_lowenrolment'] = new rules\course_lowenrolment($course->total_enrol_count, $this->low_enrolments_flag);

        /** 
         * #R8
         * Course Is Visible Rule
         * The information we have:
         *      Whether the course is visible or not
         */
        $rules['course_isvisible'] = new rules\course_isvisible($course);
        
        $course->rules = $rules;
        return $course;
    }

    public function make_score(stdClass $course) : stdClass
    {
        $score = new stdClass;
        $score->raw = 0;
        $score->rounded = 0;
        $score->percentage = 0;
        if (!isset($course->rules)) {
            return false;
        }
        $ratio_limit = count($course->rules) * $this->score_limiter;
        foreach ($course->rules as $rule) {
            // setting score in different forms
            $score->raw += $rule->get_limit_score($this->score_limiter) ?? 0;
        }
        $score->rounded = round($score->raw, 2) ?? 0;
        $score->percentage = round(($score->raw / $ratio_limit) * 100, 2) ?? 0;
        $course->score = $score;
        return $course;
    }
}
