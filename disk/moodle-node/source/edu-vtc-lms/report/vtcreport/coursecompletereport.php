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
 * Print adminer in an iframe. It chooses the db driver by looking into the Moodle db configuration.
 *
 * @package    local_adminer
 * @author Andreas Grabs <moodle@grabs-edv.de>
 * @copyright  Andreas Grabs
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir.'/gradelib.php');
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->dirroot.'/report/vtcreport/locallib.php');

global $DB;

require_login();
$courseid = required_param('course',PARAM_INT);
$page = optional_param('page', 1, PARAM_INT);
$cohort = optional_param('cohort', null, PARAM_INT);
$username = optional_param('username', null, PARAM_TEXT);

$context = context_course::instance($courseid);
require_capability('report/vtcreport:coursecomplete', $context);

validateCohortUserInCourse($courseid);

$PAGE->set_url(new moodle_url('/report/vtcreport/coursecompletereport.php',['course'=>$courseid]));
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_title($SITE->shortname.": ".get_string('pluginname','report_vtcreport'));
$PAGE->set_heading($SITE->fullname);

$coursecompletereport = new \report_vtcreport\chart\coursecompletereport($courseid,$cohort,$username,$page);
$reportDatas = $coursecompletereport->getReportData();
$enroledCohorts = $reportDatas['enroledCohorts'];
$enrolers = $reportDatas['enrolers'];
$courseModules = $reportDatas['courseModules'];
$enrolerspagination = $reportDatas['enrolerspagination'];

$templatecontext = (object)[
    'pluginname' => get_string('pluginname','report_vtcreport'),
    'publicimage' => (new moodle_url('/theme/moove/public'))->out(false),
    'courseModules' => $courseModules,
    'enroledCohorts' => $enroledCohorts,
    'enrolers' => $enrolers,
    'enrolerspagination' => $enrolerspagination,
    'courseid' => $courseid,
    'username' => $username,
    'formsearchurl' => new moodle_url("/report/vtcreport/coursecompletereport.php")
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('report_vtcreport/coursecompletereport',$templatecontext);
echo $OUTPUT->footer();
