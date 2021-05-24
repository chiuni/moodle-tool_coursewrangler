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
 * @package   tool_coursewrangler
 * @author    Hugo Soares <h.soares@chi.ac.uk>
 * @copyright 2020 University of Chichester {@link www.chi.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace tool_coursewrangler\table;

use html_writer;
use moodle_url;
use table_sql;
use renderable;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/tablelib.php');

/**
 * Table that lists all report data.
 */
class report_table extends table_sql implements renderable
{
    /**
     * Sets up the table.
     */
    public function __construct(\moodle_url $baseurl, array $params = []) {
        parent::__construct('tool_coursewrangler-report');
        $this->context = \context_system::instance();
        // This object should not be used without the right permissions. TODO: THIS ->
        // require_capability('moodle/badges:manageglobalsettings', $this->context);

        // Action table data trigger.
        $this->displayactiondata = $params['displayactiondata'] ?? false;

        // Define columns and headers in the table.
        $this->define_table_columns();

        // Define configs.
        $this->define_table_configs();

        // Optional params setting.
        $this->categoryids = $params['categoryids'] ?? [];
        $this->filteractiondata = $params['filteractiondata'] ?? [];
        $this->filterbycourseids = $params['filterbycourseids'] ?? [];
        $this->coursetimecreatedafter = $params['coursetimecreatedafter'] ?? null;
        $this->coursetimecreatedbefore = $params['coursetimecreatedbefore'] ?? null;
        $this->coursestartdateafter = $params['coursestartdateafter'] ?? null;
        $this->coursestartdatebefore = $params['coursestartdatebefore'] ?? null;
        $this->courseenddateafter = $params['courseenddateafter'] ?? null;
        $this->courseenddatebefore = $params['courseenddatebefore'] ?? null;
        $this->coursetimeaccessafter = $params['coursetimeaccessafter'] ?? null;
        $this->coursetimeaccessbefore = $params['coursetimeaccessbefore'] ?? null;
        $this->coursetimecreatednotset = $params['coursetimecreatednotset'] ?? false;
        $this->coursestartdatenotset = $params['coursestartdatenotset'] ?? false;
        $this->courseenddatenotset = $params['courseenddatenotset'] ?? false;
        $this->coursetimeaccessnotset = $params['coursetimeaccessnotset'] ?? false;
        $this->matchstringshort = $params['matchstringshort'] ?? null;
        $this->matchstringfull = $params['matchstringfull'] ?? null;
        $this->hideshowmetachildren = $params['hideshowmetachildren'] ?? null;
        $this->hideshowmetaparents = $params['hideshowmetaparents'] ?? null;
        $this->hideshowhiddencourses = $params['hideshowhiddencourses'] ?? null;

        // Preparing data for building urls in edit col.
        $params['categoryids'] = isset(($params['categoryids'])) ? implode(',', $params['categoryids']) : null;
        $params['filteractiondata'] = isset(($params['filteractiondata'])) ? implode(',', $params['filteractiondata']) : null;
        $params['filterbycourseids'] = isset(($params['filterbycourseids'])) ? implode(',', $params['filterbycourseids']) : null;
        $this->urlparams = $params;
        $this->returnlink = $baseurl->out();
        $this->define_baseurl($baseurl);
        $this->define_table_sql();
    }
    /**
     * Setup the headers for the table.
     */
    protected function define_table_columns() {
        $selectlabel = \html_writer::tag(
            'label',
            get_string('table_selectall', 'tool_coursewrangler'),
            ['class' => 'accesshide', 'for' => "selectall"]
        );
        $selectcheckbox = \html_writer::tag(
            'input',
            '',
            ['type' => 'checkbox',
            'id' => "selectall",
            'name' => 'selectall',
            'title' => get_string('table_selectall', 'tool_coursewrangler')]
        );
        $selecthtml = \html_writer::tag(
            'div',
            $selectlabel . $selectcheckbox,
            ['class' => 'selectall']
        );
        $cols = [];
        if (isset($this->displayactiondata) && $this->displayactiondata) {
            $cols['row_select'] = get_string('table_row_select', 'tool_coursewrangler') . $selecthtml;
        }
        $cols['courseid'] = get_string('table_courseid', 'tool_coursewrangler');
        $cols['courseshortname'] = get_string('table_courseshortname', 'tool_coursewrangler');
        $cols['coursefullname'] = get_string('table_coursefullname', 'tool_coursewrangler');
        $cols['coursetimecreated'] = get_string('table_coursetimecreated', 'tool_coursewrangler');
        $cols['coursestartdate'] = get_string('table_coursestartdate', 'tool_coursewrangler');
        $cols['courseenddate'] = get_string('table_courseenddate', 'tool_coursewrangler');
        $cols['coursevisible'] = get_string('table_coursevisible', 'tool_coursewrangler');
        $cols['totalenrolcount'] = get_string('table_totalenrolcount', 'tool_coursewrangler');
        $cols['coursemodulescount'] = get_string('table_coursemodulescount', 'tool_coursewrangler');
        $cols['coursetimeaccess'] = get_string('table_coursetimeaccess', 'tool_coursewrangler');
        $cols['percentage'] = get_string('table_course_deletionscore', 'tool_coursewrangler');

        // Prepare to display table with action data.
        if ($this->displayactiondata) {
            $cols['action'] = 'Action';
            $cols['status'] = 'Action Status';
            // Unsetting some data as no space on screen.
            unset($cols['coursetimecreated']);
            unset($cols['coursestartdate']);
            unset($cols['courseenddate']);
        }

        $this->define_columns(array_keys($cols));
        $this->define_headers(array_values($cols));
    }
    /**
     * Define table configs.
     */
    protected function define_table_configs() {
        $this->sortable(true, 'courseid', SORT_ASC);
        $this->no_sorting('row_select');
        $this->pageable(true);
    }
    /**
     * Override the table show_hide_link to not show for select column.
     * Taken from 'assign/gradingtable.php' but slightly modified.
     *
     * @param string $column the column name, index into various names.
     * @param int $index numerical index of the column.
     * @return string HTML fragment.
     */
    protected function show_hide_link($column, $index) {
        if ($column != 'row_select' && $column != 'courseid') {
            return parent::show_hide_link($column, $index);
        }
        return '';
    }
    /**
     * Define table SQL query.
     */
    protected function build_query() {
        global $DB;
        $sqlwhat = [
            'metrics.courseid',
            'metrics.id',
            'metrics.coursemoduleid',
            'metrics.courseshortname',
            'metrics.coursefullname',
            'metrics.courseidnumber',
            'metrics.coursetimecreated',
            'metrics.coursetimemodified',
            'metrics.coursestartdate',
            'metrics.courseenddate',
            'metrics.coursevisible',
            'metrics.courseparents',
            'metrics.coursemodulescount',
            'metrics.coursetimeaccess',
            'metrics.courselastenrolment',
            'metrics.activitytype',
            'metrics.activitylastmodified',
            'metrics.totalenrolcount',
            'metrics.activeenrolcount',
            'metrics.selfenrolcount',
            'metrics.manualenrolcount',
            'metrics.metaenrolcount',
            'metrics.otherenrolcount',
            'metrics.suspendedenrolcount',
            'metrics.metricsupdated',
            'score.id',
            'score.metrics_id',
            'score.timemodified',
            'score.raw',
            'score.rounded',
            'score.percentage'
        ];
        $sqlfrom = [
            '{tool_coursewrangler_metrics} AS metrics',
            'LEFT JOIN {tool_coursewrangler_score} AS score ON metrics.id=score.metrics_id'
        ];
        $params = [];
        $conditions = [];

        // We add this join always, so that we can filter out protected courses.
        $sqlfrom[] = 'LEFT JOIN {tool_coursewrangler_action} AS act ON metrics.courseid=act.courseid';
        // Make sure not to double select courseid here, otherwise ambiguous error appears.
        $sqlwhat[] = 'act.id';
        $sqlwhat[] = 'act.action';
        $sqlwhat[] = 'act.status';
        $sqlwhat[] = 'act.lastupdated';

        // Filter out protected courses by default!
        // If filteractiondata is empty, the table will
        // output all courses which aren't protected, else it will
        // filter through courses.
        if (empty($this->filteractiondata)) {
            $conditions[] = "(act.action != 'protect' OR act.action IS NULL)";
        } else {
            // Filter action data prefix.
            $fadprefix = 'fad';
            $fadconditions = [];
            foreach ($this->filteractiondata as $value) {
                if ($value == '_qf__force_multiselect_submission') {
                    // We do this here as well, so that when users select action data,
                    // but haven't yet selected filters, we by default hide protected
                    // courses. This way if the user wants to see protected courses
                    // at the same time as other courses, they have to select all
                    // the appropriate filters.
                    $conditions[] = "(act.action != 'protect' OR act.action IS NULL)";
                    continue;
                }
                // To get courses without action we must do it differently
                // by asking the database for action IS NULL.
                if ($value == 'null') {
                    $fadconditions[] = "act.action IS NULL";
                    continue;
                }
                $fadconditions[] = "act.action = :$fadprefix"."$value";
                $params[$fadprefix . $value] = $value;
            }
            if (!empty($fadconditions)) {
                $conditions[] = '(' . implode(' OR ', $fadconditions) . ')';
            }
        }
        // This is the course id(s) filter.
        // Allows the user to filter which courses to see based on course ids.
        if (!empty($this->filterbycourseids)) {
            foreach ($this->filterbycourseids as $value) {
                if ($value == '_qf__force_multiselect_submission') {
                    // We can skip this one, as this is same as not set.
                    continue;
                }
                $fbcsparams[] = $value;
            }
            if (!empty($fbcsparams)) {
                list($fbcssql, $fbcsparams) = $DB->get_in_or_equal($fbcsparams, SQL_PARAMS_NAMED, 'fbcs');
                $params += $fbcsparams;
                $conditions[] = "metrics.courseid $fbcssql";
            }
        }
        // Filtering.
        // Date SQL options.
        // Course time created filters.
        if ($this->coursetimecreatednotset) {
            // If 'notset' option for 'coursetimecreated' is set, filter all missing time created.
            $conditions[] = "metrics.coursetimecreated = 0";
        } else {
            if (isset($this->coursetimecreatedafter)) {
                // Option where 'coursetimecreated' is AFTER specified date.
                $conditions[] = "metrics.coursetimecreated > :coursetimecreatedafter";
                $params['coursetimecreatedafter'] = $this->coursetimecreatedafter;
            }
            if (isset($this->coursetimecreatedbefore)) {
                // Option where 'coursetimecreated' is BEFORE specified date.
                $conditions[] = "metrics.coursetimecreated < :coursetimecreatedbefore";
                $params['coursetimecreatedbefore'] = $this->coursetimecreatedbefore;
            }
        }
        // Course start date filters.
        if ($this->coursestartdatenotset) {
            // If 'notset' option for 'coursestartdate' is set, filter all missing time created.
            $conditions[] = "metrics.coursestartdate = 0";
        } else {
            if (isset($this->coursestartdateafter)) {
                // Option where 'coursestartdate' is AFTER specified date.
                $conditions[] = "metrics.coursestartdate > :coursestartdateafter";
                $params['coursestartdateafter'] = $this->coursestartdateafter;
            }
            if (isset($this->coursestartdatebefore)) {
                // Option where 'coursestartdate' is BEFORE specified date.
                $conditions[] = "metrics.coursestartdate < :coursestartdatebefore";
                $params['coursestartdatebefore'] = $this->coursestartdatebefore;
            }
        }
        // Course end date filters.
        if ($this->courseenddatenotset) {
            // If 'notset' option for 'courseenddate' is set, filter all missing time created.
            $conditions[] = "metrics.courseenddate = 0";
        } else {
            if (isset($this->courseenddateafter)) {
                // Option where 'courseenddate' is AFTER specified date.
                $conditions[] = "metrics.courseenddate > :courseenddateafter";
                $params['courseenddateafter'] = $this->courseenddateafter;
            }
            if (isset($this->courseenddatebefore)) {
                // Option where 'courseenddate' is BEFORE specified date.
                $conditions[] = "metrics.courseenddate < :courseenddatebefore";
                $params['courseenddatebefore'] = $this->courseenddatebefore;
            }
        }
        // Course last time access filters.
        if ($this->coursetimeaccessnotset) {
            // IF 'notset' option for 'coursetimeaccess' is set, filter all missing time created.
            $conditions[] = "metrics.coursetimeaccess = 0";
        } else {
            if (isset($this->coursetimeaccessafter)) {
                // Option where 'coursetimeaccess' is AFTER specified date.
                $conditions[] = "metrics.coursetimeaccess > :coursetimeaccessafter";
                $params['coursetimeaccessafter'] = $this->coursetimeaccessafter;
            }
            if (isset($this->coursetimeaccessbefore)) {
                // Option where 'coursetimeaccess' is BEFORE specified date.
                $conditions[] = "metrics.coursetimeaccess < :coursetimeaccessbefore";
                $params['coursetimeaccessbefore'] = $this->coursetimeaccessbefore;
            }
        }
        // String search for course short name and id number.
        if (isset($this->matchstringshort)) {
            $query = [];
            $query[] = $DB->sql_like(
                'metrics.courseshortname',
                ':matchstringshortname',
                false
            );
            $query[] = $DB->sql_like(
                'metrics.courseidnumber',
                ':matchstring_idnumber',
                false
            );
            $conditions[] = '(' . join(' OR ', $query) . ')';
            $params['matchstringshortname'] = "%$this->matchstringshort%";
            $params['matchstring_idnumber'] = "%$this->matchstringshort%";

        }
        // String search for course full name.
        if (isset($this->matchstringfull)) {
            $conditions[] = $DB->sql_like(
                'metrics.coursefullname',
                ':matchstringfull',
                false
            );
            $params['matchstringfull'] = "%$this->matchstringfull%";
        }
        // Category filter.
        if (count($this->categoryids) > 0) {
            $categories = [];
            foreach ($this->categoryids as $categoryid) {
                if ($categoryid <= 0) {
                    continue;
                }
                if ($DB->record_exists("course_categories", ['id' => $categoryid])) {
                    $categories[] = $categoryid;
                }
            }
            if (count($categories) > 0) {
                $courseids = [];
                foreach ($categories as $id) {
                    $categorycourses = $DB->get_records_sql(
                        "SELECT c.id
                            FROM {course} AS c
                            WHERE c.category=:catid;",
                        ['catid' => $id]
                    );
                    foreach ($categorycourses as $course) {
                        $courseids[] = $course->id;
                    }
                }
                if (count($courseids) > 0) {
                    list($catsql, $catparams) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'coursecatids');
                    $params += $catparams;
                    $conditions[] = "metrics.courseid $catsql";
                } else {
                    // We make conditions 1=0 because it's the best way to display no results
                    // without breaking the table.
                    $conditions[] = '1=0';
                }
            }
        }
        // Hide parent courses.
        if (isset($this->hideshowmetaparents)) {
            switch($this->hideshowmetaparents) {
                // Show only parent courses.
                case 'show':
                    $conditions[] = "metrics.coursechildren IS NOT NULL";
                    break;
                // Hide all parent courses.
                case 'hide':
                    $conditions[] = "metrics.coursechildren IS NULL";
                    break;
                // Do nothing.
                default:
                    break;
            }
        }
        // Hide child courses.
        if (isset($this->hideshowmetachildren)) {
            switch($this->hideshowmetachildren) {
                // Show only child courses.
                case 'show':
                    $conditions[] = "metrics.courseparents IS NOT NULL";
                    break;
                // Hide all child courses.
                case 'hide':
                    $conditions[] = "metrics.courseparents IS NULL";
                    break;
                // Do nothing.
                default:
                    break;
            }
        }
        // Hide visible courses.
        if (isset($this->hideshowhiddencourses)) {
            switch($this->hideshowhiddencourses) {
                // Show only visible courses.
                case 'show':
                    $conditions[] = "metrics.coursevisible = 1";
                    break;
                // Hide all visible courses.
                case 'hide':
                    $conditions[] = "metrics.coursevisible = 0";
                    break;
                // Do nothing.
                default:
                    break;
            }
        }
        // This is required to prevent SQL errors on empty conditions.
        if (empty($conditions)) {
            $conditions[] = '1=1';
        }
        return [$sqlwhat, $sqlfrom, $conditions, $params];
    }
    /**
     * Define table SQL query.
     */
    protected function define_table_sql() {
        list($sqlwhat, $sqlfrom, $conditions, $params) = $this->build_query();
        $sqlwhat = join(', ', $sqlwhat);
        $sqlfrom = join(' ', $sqlfrom);
        $conditions = join(' AND ', $conditions);
        $this->set_sql($sqlwhat, $sqlfrom, $conditions, $params);
    }
    /**
     * Processing dates for table.
     */
    public function col_coursetimecreated($values) : string {
        return ($values->coursetimecreated == 0) ? '-' : userdate($values->coursetimecreated);
    }
    public function col_coursetimemodified($values) : string {
        return ($values->coursetimemodified == 0) ? '-' : userdate($values->coursetimemodified);
    }
    public function col_coursestartdate($values) : string {
        return ($values->coursestartdate == 0) ? '-' : userdate($values->coursestartdate);
    }
    public function col_courseenddate($values) : string {
        return ($values->courseenddate == 0) ? '-' : userdate($values->courseenddate);
    }
    public function col_coursetimeaccess($values) : string {
        return ($values->coursetimeaccess == 0) ? '-' : userdate($values->coursetimeaccess);
    }
    public function col_courselastenrolment($values) : string {
        return ($values->courselastenrolment == 0) ? '-' : userdate($values->courselastenrolment);
    }
    public function col_activitylastmodified($values) : string {
        return ($values->activitylastmodified == 0) ? '-' : userdate($values->activitylastmodified);
    }
    /**
     * Processing visible and parent cols.
     */
    public function col_coursevisible($values) : string {
        $coursevisible = $values->coursevisible ? 'yes' : 'no';
        $displayvalue = isset($coursevisible) ? "table_visible_$coursevisible" : 'table_value_notavailable';
        $displayvaluestring = get_string($displayvalue, 'tool_coursewrangler');
        return ($displayvaluestring);
    }
    /**
     * Turning course name into link for details area.
     * TODO: Improve this into a link that goes to a details page within coursewrangler?
     */
    public function col_coursefullname($values) : string {
        $urlparams = [
            'courseid' => $values->courseid,
            'returnlink' => $this->returnlink
        ];
        $url = new moodle_url('/admin/tool/coursewrangler/report_details.php', $urlparams);
        $link = html_writer::link($url, $values->coursefullname);
        return $link;
    }
    /**
     * Creating the score when required.
     */
    public function col_percentage($values) : string {
        $displayvalue = ($values->percentage != null)
            ? $values->percentage . '%'
            : get_string('table_percentage_notavailable', 'tool_coursewrangler');
        return ($displayvalue);
    }
    /**
     * Creating the action col.
     */
    public function col_action($values) : string {
        $displayvalue = isset($values->action) ? "table_action_$values->action" : 'table_value_notavailable';
        $displayvaluestring = get_string($displayvalue, 'tool_coursewrangler');
        return ($displayvaluestring);
    }
    /**
     * Creating the action status col.
     */
    public function col_status($values) : string {
        $displayvalue = (isset($values->status) && $values->status != '')
                            ? "table_status_$values->status"
                            : 'table_value_notavailable';
        $displayvaluestring = get_string($displayvalue, 'tool_coursewrangler');
        return ($displayvaluestring);
    }
    /**
     * Creating the select col.
     */
    public function col_row_select($values) : string {
        $courseid = $values->courseid;
        $labelcontent = get_string('table_row_select', 'tool_coursewrangler') . " $values->coursefullname";
        $label = \html_writer::tag(
            'label',
            $labelcontent,
            ['class' => 'accesshide', 'for' => "selectcourse_$courseid"]
        );
        $checkbox = \html_writer::tag(
            'input',
            '',
            ['type' => "checkbox",
            'id' => "selectcourse_$courseid",
            'class' => "selectcourses",
            'name' => "selectedcourseids",
            'value' => $courseid]
        );
        return $label . $checkbox;
    }
    /**
     * Preparing the report table gives us access to the total records
     *  before outputting table on scree. This was taken from ->out()
     *  function.
     */
    public function prepare_report_table($pagesize, $useinitialsbar) {
        global $DB;
        if (!$this->columns) {
            $onerow = $DB->get_record_sql("SELECT {$this->sql->fields} FROM {$this->sql->from} WHERE {$this->sql->where}",
                $this->sql->params, IGNORE_MULTIPLE);
            // If columns is not set then define columns as the keys of the rows returned
            // from the db.
            $this->define_columns(array_keys((array)$onerow));
            $this->define_headers(array_keys((array)$onerow));
        }
        $this->setup();
        $this->query_db($pagesize, $useinitialsbar);
    }
    /**
     * After preparing table, now we can output on screen.
     * Must check totalrows has been set.
     */
    public function finish_report_table() {
        if (!isset($this->totalrows) || $this->totalrows < 1) {
            return false;
        }
        $this->build_table();
        $this->close_recordset();
        $this->finish_output();
    }
}
