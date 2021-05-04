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
 * @author    Hugo Soares <h.soares@chi.ac.uk>
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
        DAYSECS,
        86400
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

    $main_page->add(new admin_setting_configduration(
        'tool_coursewrangler/minimumage',
        get_string('settings_minimumage', 'tool_coursewrangler'),
        get_string('settings_minimumage_desc', 'tool_coursewrangler'),
        52 * WEEKSECS, 
        WEEKSECS
    ));

    $settings->add($main_page);
    
    $tasks_page = new admin_settingpage('tool_coursewrangler_tasks', get_string('settingspage_tasks', 'tool_coursewrangler'));

    $tasks_page->add(new admin_setting_configduration(
        'tool_coursewrangler/scheduledduration',
        get_string('settings_scheduledduration', 'tool_coursewrangler'),
        get_string('settings_scheduledduration_desc', 'tool_coursewrangler'),
        7 * DAYSECS, 
        DAYSECS
    ));

    $tasks_page->add(new admin_setting_configduration(
        'tool_coursewrangler/emailedduration',
        get_string('settings_emailedduration', 'tool_coursewrangler'),
        get_string('settings_emailedduration_desc', 'tool_coursewrangler'),
        7 * DAYSECS, 
        DAYSECS
    ));

    $tasks_page->add(new admin_setting_configduration(
        'tool_coursewrangler/hiddenduration',
        get_string('settings_hiddenduration', 'tool_coursewrangler'),
        get_string('settings_hiddenduration_desc', 'tool_coursewrangler'),
        7 * DAYSECS, 
        DAYSECS
    ));

    $tasks_page->add(new admin_setting_configduration(
        'tool_coursewrangler/waitingduration',
        get_string('settings_waitingduration', 'tool_coursewrangler'),
        get_string('settings_waitingduration_desc', 'tool_coursewrangler'),
        7 * DAYSECS, 
        DAYSECS
    ));

    $tasks_page->add(new admin_setting_configcheckbox(
        'tool_coursewrangler/emailmode',
        get_string('settings_emailmode', 'tool_coursewrangler'),
        get_string('settings_emailmode_desc', 'tool_coursewrangler'), 
        0
    ));
    
    $settings->add($tasks_page);

}
