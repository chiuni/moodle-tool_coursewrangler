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
 * This file how to uninstall the plugin.
 * @package   tool_coursewrangler
 * @author    Hugo Soares <h.soares@chi.ac.uk>
 * @copyright 2020 University of Chichester {@link www.chi.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$tasks = [
    [
        'classname' => 'tool_coursewrangler\task\filldata',
        'blocking' => 0,
        'minute' => '00',
        'hour' => '2-5', // Between 2 and 5 AM.
        'day' => '*',
        'month' => '*',
        'dayofweek' => '0', // Every sunday.
    ],
    [
        'classname' => 'tool_coursewrangler\task\wrangle',
        'blocking' => 0,
        'minute' => '00',
        'hour' => '2-5', // Between 2 and 5 AM.
        'day' => '*',
        'month' => '*',
        'dayofweek' => '0', // Every sunday.
    ],
    [
        'classname' => 'tool_coursewrangler\task\score',
        'blocking' => 0,
        'minute' => '00',
        'hour' => '2-5', // Between 2 and 5 AM.
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*', // Every day.
    ],
];
