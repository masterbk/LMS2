<?php
// This file is part of the local_bulkcustomcert plugin for Moodle - http://moodle.org/
//
// local_bulkcustomcert is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// local_bulkcustomcert is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Version information for local_bulkcustomcert.
 *
 * @package    local_bulkcustomcert
 * @author     Gonzalo Romero
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// defined('MOODLE_INTERNAL') || die();

require_once('../../config.php');
require_once($CFG->dirroot.'/local/bulkcustomcert/locallib.php');
global $DB, $CFG;

$courseid = optional_param('id', null, PARAM_INT);
$page = optional_param('page', 1, PARAM_INT);
$download = optional_param('download', '', PARAM_ALPHAEXT);

require_login();
$context = context_course::instance($courseid);
require_capability('mod/customcert:viewallcertificates', $context);


$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
if (!$course) {
    throw new \moodle_exception('invalidcourseid');
}
$seturl = new moodle_url('/local/bulkcustomcert/index.php',array('id'=>$courseid));
$PAGE->set_url($seturl);
$PAGE->set_course($course);

$sqlcustomcertIssues = "SELECT COUNT(cci.id) AS countcer
                FROM {customcert_issues} as cci
                INNER JOIN {customcert} as cc ON cci.customcertid = cc.id
                WHERE cc.course = {$course->id}";
$customcertIssues = $DB->get_record_sql($sqlcustomcertIssues);
if($customcertIssues->countcer<=0){
    throw new \moodle_exception('Rất tiếc không có chứng chỉ nào để tải về');
}

// tổng số page
$pageLimit = 10;
$pages = ceil($customcertIssues->countcer / $pageLimit);
$limitfrom = ( $page - 1 ) * $pageLimit;
if($pages < $page){
    throw new \moodle_exception('invalid page');
}

assign_process_group_deleted_in_course_custom_v2($course->id,$limitfrom);








