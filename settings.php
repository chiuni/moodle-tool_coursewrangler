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
    // Courses tab navigation
    $courses = new admin_externalpage(
        'coursewrangler',
        get_string('pluginname', 'tool_coursewrangler'),
        new moodle_url('/admin/tool/coursewrangler/index.php')
    );
    $ADMIN->add('courses', $courses);
    // Settings tab navigation
    $settings = new theme_boost_admin_settingspage_tabs(
        'tool_coursewrangler',
        get_string('pluginname', 'tool_coursewrangler')
    );
    $ADMIN->add('tools', $settings);

    $numbers = array();
    for ($i = 20; $i > 1; $i--) {
        $numbers[$i] = $i;
    }

    $large_numbers = array();
    for ($i = 800; $i > 1; $i--) {
        if ($i % 50 != 0) {
            continue;
        }
        $large_numbers[$i] = $i;
    }

    $main_page = new admin_settingpage('tool_coursewrangler_main', get_string('settingspage_main', 'tool_coursewrangler'));

    $main_page->add(new admin_setting_configduration(
        'tool_coursewrangler/timeunit',
        get_string('settings_timeunit', 'tool_coursewrangler'),
        get_string('settings_timeunit_desc', 'tool_coursewrangler'),
        DAYSECS
    ));

    $main_page->add(new admin_setting_configselect(
        'tool_coursewrangler/courseparentweight',
        get_string('settings_courseparentweight', 'tool_coursewrangler'),
        get_string('settings_courseparentweight_desc', 'tool_coursewrangler'),
        10,
        $numbers
    ));

    $main_page->add(new admin_setting_configselect(
        'tool_coursewrangler/lowenrolmentsflag',
        get_string('settings_lowenrolmentsflag', 'tool_coursewrangler'),
        get_string('settings_lowenrolmentsflag_desc', 'tool_coursewrangler'),
        10,
        $numbers
    ));

    $main_page->add(new admin_setting_configselect(
        'tool_coursewrangler/scorelimiter',
        get_string('settings_scorelimiter', 'tool_coursewrangler'),
        get_string('settings_scorelimiter_desc', 'tool_coursewrangler'),
        400,
        $large_numbers
    ));

    $settings->add($main_page);
}
