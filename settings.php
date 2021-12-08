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
    // Courses tab navigation.
    $courses = new admin_externalpage(
        'coursewrangler',
        get_string('pluginname', 'tool_coursewrangler'),
        new moodle_url('/admin/tool/coursewrangler/index.php')
    );
    $ADMIN->add('courses', $courses);
    // Settings tab navigation.
    $settings = new theme_boost_admin_settingspage_tabs(
        'tool_coursewrangler',
        get_string('pluginname', 'tool_coursewrangler')
    );
    $ADMIN->add('tools', $settings);

    $numbers = array();
    for ($i = 20; $i > 1; $i--) {
        $numbers[$i] = $i;
    }

    $largenumbers = array();
    for ($i = 800; $i > 1; $i--) {
        if ($i % 50 != 0) {
            continue;
        }
        $largenumbers[$i] = $i;
    }
    $mainpage = new admin_settingpage(
        'tool_coursewrangler_main',
        get_string('settingspage_main', 'tool_coursewrangler')
    );

    $mainpage->add(new admin_setting_configduration(
        'tool_coursewrangler/timeunit',
        get_string('settings_timeunit', 'tool_coursewrangler'),
        get_string('settings_timeunit_desc', 'tool_coursewrangler'),
        DAYSECS,
        86400
    ));

    $mainpage->add(new admin_setting_configselect(
        'tool_coursewrangler/courseparentweight',
        get_string('settings_courseparentweight', 'tool_coursewrangler'),
        get_string('settings_courseparentweight_desc', 'tool_coursewrangler'),
        10,
        $numbers
    ));

    $mainpage->add(new admin_setting_configselect(
        'tool_coursewrangler/lowenrolmentsflag',
        get_string('settings_lowenrolmentsflag', 'tool_coursewrangler'),
        get_string('settings_lowenrolmentsflag_desc', 'tool_coursewrangler'),
        10,
        $numbers
    ));

    $mainpage->add(new admin_setting_configselect(
        'tool_coursewrangler/scorelimiter',
        get_string('settings_scorelimiter', 'tool_coursewrangler'),
        get_string('settings_scorelimiter_desc', 'tool_coursewrangler'),
        400,
        $largenumbers
    ));

    $mainpage->add(new admin_setting_configduration(
        'tool_coursewrangler/minimumage',
        get_string('settings_minimumage', 'tool_coursewrangler'),
        get_string('settings_minimumage_desc', 'tool_coursewrangler'),
        52 * WEEKSECS,
        WEEKSECS
    ));

    $settings->add($mainpage);

    $taskspage = new admin_settingpage(
        'tool_coursewrangler_tasks',
        get_string('settingspage_tasks', 'tool_coursewrangler')
    );

    $taskspage->add(new admin_setting_configduration(
        'tool_coursewrangler/scheduledduration',
        get_string('settings_scheduledduration', 'tool_coursewrangler'),
        get_string('settings_scheduledduration_desc', 'tool_coursewrangler'),
        7 * DAYSECS,
        DAYSECS
    ));

    $taskspage->add(new admin_setting_configduration(
        'tool_coursewrangler/notifyduration',
        get_string('settings_notifyduration', 'tool_coursewrangler'),
        get_string('settings_notifyduration_desc', 'tool_coursewrangler'),
        7 * DAYSECS,
        DAYSECS
    ));

    $taskspage->add(new admin_setting_configduration(
        'tool_coursewrangler/hiddenduration',
        get_string('settings_hiddenduration', 'tool_coursewrangler'),
        get_string('settings_hiddenduration_desc', 'tool_coursewrangler'),
        7 * DAYSECS,
        DAYSECS
    ));

    $taskspage->add(new admin_setting_configduration(
        'tool_coursewrangler/waitingduration',
        get_string('settings_waitingduration', 'tool_coursewrangler'),
        get_string('settings_waitingduration_desc', 'tool_coursewrangler'),
        7 * DAYSECS,
        DAYSECS
    ));

    $taskspage->add(new admin_setting_configcheckbox(
        'tool_coursewrangler/notifymode',
        get_string('settings_notifymode', 'tool_coursewrangler'),
        get_string('settings_notifymode_desc', 'tool_coursewrangler'),
        0
    ));

    $taskspage->add(new admin_setting_configcheckbox(
        'tool_coursewrangler/childprotection',
        get_string('settings_childprotection', 'tool_coursewrangler'),
        get_string('settings_childprotection_desc', 'tool_coursewrangler'),
        0
    ));

    $taskspage->add(new admin_setting_configcheckbox(
        'tool_coursewrangler/enddateprotection',
        get_string('settings_enddateprotection', 'tool_coursewrangler'),
        get_string('settings_enddateprotection_desc', 'tool_coursewrangler'),
        0
    ));

    $taskspage->add(new admin_setting_configcheckbox(
        'tool_coursewrangler/donotnotifyhidden',
        get_string('settings_donotnotifyhidden', 'tool_coursewrangler'),
        get_string('settings_donotnotifyhidden_desc', 'tool_coursewrangler'),
        0
    ));

    $taskspage->add(new admin_setting_configcheckbox(
        'tool_coursewrangler/notifysiteadmins',
        get_string('settings_notifysiteadmins', 'tool_coursewrangler'),
        get_string('settings_notifysiteadmins_desc', 'tool_coursewrangler'),
        0
    ));

    $taskspage->add(new admin_setting_configduration(
        'tool_coursewrangler/maxdeleteexecutiontime',
        get_string('settings_maxdeleteexecutiontime', 'tool_coursewrangler'),
        get_string('settings_maxdeleteexecutiontime_desc', 'tool_coursewrangler'),
        30 * MINSECS,
        MINSECS
    ));

    $settings->add($taskspage);
}
