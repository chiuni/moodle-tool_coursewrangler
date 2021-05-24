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
 * This file is the task that generates the score form metrics data.
 *
 * @package   tool_coursewrangler
 * @author    Hugo Soares <h.soares@chi.ac.uk>
 * @copyright 2020 University of Chichester {@link www.chi.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace tool_coursewrangler\task;

use tool_coursewrangler\deletion_score;

defined('MOODLE_INTERNAL') || die();

class score extends \core\task\scheduled_task {

    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name() {
        return get_string('task_score', 'tool_coursewrangler');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        global $DB;
        mtrace(">>> Starting " . $this->get_name() . '.');
        mtrace('>>> Calculating score...');
        $scrstarttime = time();
        $data = $DB->get_records('tool_coursewrangler_metrics');
        $scorekeeper = new deletion_score($data);
        $courses = $scorekeeper->get_courses();
        $insert = [];
        foreach ($courses as $metrics) {
            $currentscore = $DB->get_record('tool_coursewrangler_score', ['metrics_id' => $metrics->id]) ?? false;
            $scoredata = [
                'metrics_id' => $metrics->id,
                'timemodified' => $scrstarttime,
                'raw' => $metrics->score->raw,
                'rounded' => $metrics->score->rounded,
                'percentage' => (float) $metrics->score->percentage,
            ];
            if ($currentscore === false) {
                // If record does not exist, create new one.
                $insert[] = $scoredata;
                continue;
            }
            $scoredata['id'] = $currentscore->id;
            // Update record does not support bulk queries.
            $DB->update_record('tool_coursewrangler_score', $scoredata, true);
        }
        if (!empty($insert)) {
            $DB->insert_records('tool_coursewrangler_score', $insert, true, true);
        }
        $screndtime = time();
        mtrace('>>> Calculating score took ' . ($screndtime - $scrstarttime) . ' seconds.');
        mtrace(">>> Finishing " . $this->get_name() . '.');
    }
}