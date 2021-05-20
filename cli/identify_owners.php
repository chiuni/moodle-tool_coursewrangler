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

// >>> THIS FILE IS ONLY FOR DEVELOPMENT, NOT PART OF PLUGIN <<<

namespace tool_coursewrangler;

use context_system;
use stdClass;
use tool_coursewrangler\action_handler;

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
$courses = get_courses();;

// foreach ($courses as $c) {
//     print_object(find_owner($c->id));
//     break;
// }

// print_object(find_owners(70248));

$scheduledduration = time() - get_config('tool_coursewrangler', 'scheduledduration');
$scheduled_actions = $DB->get_records_sql(
            'SELECT * FROM {tool_coursewrangler_action} 
                WHERE action="delete" AND status="scheduled" 
                AND lastupdated < :lastupdated ;', 
            ['lastupdated' => $scheduledduration]
        );
$maillist = action_handler::getmaillist($scheduled_actions);
print_object($maillist);

foreach ($maillist as $userid => $owner) {
    $user = \core_user::get_user($userid);
    $courseids = array_keys($owner);
    action_handler::send_schedulednotification($user, $courseids);
}

//########################################################

$elapsed = time() - $starttime;
echo "Finished script in $elapsed seconds." . PHP_EOL;

//--------------------------------------------------------