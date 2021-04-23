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
 * This file is a command line script example.
 *
 * @package   tool_coursewrangler
 * @author    Hugo Soares <h.soares@chi.ac.uk>
 * @copyright 2020 University of Chichester {@link www.chi.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// More info: https://docs.moodle.org/dev/Plugin_files#cli.2F

namespace tool_coursewrangler;

use context_system;
use stdClass;

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->libdir . '/adminlib.php');
require_once(__DIR__ . '/../locallib.php');
$context = context_system::instance();
$starttime = time();

//########################################################

echo 'identify_owners.php.......' . PHP_EOL;

global $DB;
$input = 69951;
while ($input > 0) {
    print_r(find_owner($input));
    $handle = fopen("php://stdin", "r");
    $input = fgets($handle);
    fclose($handle);
}

//########################################################

$elapsed = time() - $starttime;
echo "Finished script in $elapsed seconds." . PHP_EOL;

//--------------------------------------------------------
function get_enrolments(int $course_id) {
    if ($course_id < 1) {
        return false;
    }
    global $DB;
    $sql = "SELECT concat(ra.id, '-', e.id) as id, ue.userid, r.shortname, ra.component, e.enrol, ue.timestart, ue.timecreated, ue.timemodified
        FROM {role_assignments} AS ra 
       LEFT JOIN {user_enrolments} AS ue ON ra.userid = ue.userid 
       LEFT JOIN {role} AS r ON ra.roleid = r.id 
       LEFT JOIN {context} AS c ON c.id = ra.contextid 
       LEFT JOIN {enrol} AS e ON e.courseid = c.instanceid AND ue.enrolid = e.id 
       WHERE e.courseid = :course_id;";
    return $DB->get_records_sql($sql, ['course_id' => $course_id]);
}

function find_owner(int $course_id) {
    $enrolments = get_enrolments($course_id);
    if (!$enrolments) {
        return false;
    }
    $owners = new stdClass;
    foreach ($enrolments as $e) {
        // Checking sits enrolments.
        // if ($e->enrol == 'sits') {
            $type = $e->shortname . 's';
            $owners->$type[$e->userid] = $e;
        // }
    }
    if (!empty($owners->coordinators)) {
        return $owners->coordinators;
    }
    if (!empty($owners->lecturers)) {
        return $owners->lecturers;
    }
    return false;
}