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
 * @package   tool_coursewrangler
 * @author    Mark Sharp <m.sharp@chi.ac.uk>
 * @copyright 2020 University of Chichester {@link www.chi.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {

    $pluginname = get_string('pluginname', 'tool_coursewrangler');
    $url = $CFG->wwwroot . '/' . $CFG->admin . '/tool/coursewrangler/index.php';
    // $ADMIN->add('security', new admin_externalpage('toolcoursewrangler', $pluginname, $url, 'moodle/site:config', true));
    $ADMIN->add("parent_section", new admin_externalpage('toolcoursewrangler', $pluginname, $url, 'moodle/site:config', true));

    $coursewranglerurl = $CFG->wwwroot . '/' . $CFG->admin . '/tool/coursewrangler/index.php';
    // $ADMIN->locate('httpsecurity')->add(
    //     new admin_setting_heading(
    //         'tool_coursewranglerheader',
    //         new lang_string('pluginname', 'tool_coursewrangler'),
    //         new lang_string('toolintro', 'tool_coursewrangler', $coursewranglerurl)
    //     )
    // );
}
