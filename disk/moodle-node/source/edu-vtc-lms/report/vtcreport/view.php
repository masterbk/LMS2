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

$id = required_param('course',PARAM_INT);
$course = $DB->get_record('course',array('id'=>$id));
if (!$course) {
    throw new \moodle_exception('invalidcourseid');
}
$context = context_course::instance($course->id);
require_capability('report/vtcreport:view', $context);

$PAGE->set_course($course);
$PAGE->set_url(new moodle_url('/report/vtcreport/view.php',array('course'=>$course->id)));
$PAGE->set_context($context);
$PAGE->set_pagelayout('report');
$PAGE->set_title($SITE->shortname.": ".get_string('pluginname','report_vtcreport'));
$PAGE->set_heading($SITE->fullname);

validateCohortUserInCourse($course->id);
validateEnroledUserInCourse($course->id);
/* thống kê điểm */

$courseId = $course->id;
$chartdataob = new \report_vtcreport\chart\charbarcoursescorestats($courseId);
$chartdatas = $chartdataob->getChartData();
$scorestatistics = array();
foreach ($chartdatas as $key => $chartdata){
    $title = $chartdata['title'];
    $data = $chartdata['data'];


    $chart = new core\chart_bar();
    $series1 = new \core\chart_series($data['series']['tong_diem']['label'], $data['series']['tong_diem']['data']);
    $series1->set_yaxis(0);
    $yaxis1 = $chart->get_yaxis(0, true); // Select the second Y axis.
    $yaxis1->set_position(\core\chart_axis::POS_LEFT);
    $chart->set_yaxis($yaxis1,0);

    $series2 = new \core\chart_series($data['series']['diem_tb']['label'], $data['series']['diem_tb']['data']);
    $series2->set_yaxis(1);
    $yaxis2 = $chart->get_yaxis(1, true); // Select the second Y axis.
    $yaxis2->set_position(\core\chart_axis::POS_RIGHT);
    $yaxis2->set_min(0);
    $chart->set_yaxis($yaxis2,1);

    $chart->set_labels($data['labels']);
    $chart->add_series($series1);
    $chart->add_series($series2);
    $chart->set_title($title);
    $CFG->chart_colorset = ['#C893FD','#4A3AFF'];

    $scorestatistics[] = array(
        'charthtml' => $OUTPUT->render_chart($chart, false)
    );
}

// thống kê số lượng hoàn thành đủ điều kiện

$courseId = $course->id;
$chartdataob = new \report_vtcreport\chart\chartbarcoursecompletestas($courseId);
$chartdata = $chartdataob->getChartData();

$chart = new core\chart_bar();
$chart->set_stacked(true);
$series1 = new \core\chart_series($chartdata['data']['series']['hoan_thanh']['label'], $chartdata['data']['series']['hoan_thanh']['data']);
$series2 = new \core\chart_series($chartdata['data']['series']['khong_hoan_thanh']['label'], $chartdata['data']['series']['khong_hoan_thanh']['data']);
$chart->add_series($series1);
$chart->add_series($series2);
$chart->set_labels($chartdata['data']['labels']);
//$chart->set_title($title);
$CFG->chart_colorset = ['#C893FD','#4A3AFF'];
$coursecompletestatics =  $OUTPUT->render_chart($chart, false);
$coursecompletestaticslink = (new moodle_url('/report/vtcreport/coursecompletereport.php',['course'=>$courseId]))->out(false);

require_once(dirname(__FILE__) . '/classes/chart/completechart.php');

require_once(dirname(__FILE__) . '/classes/chart/ratechart.php');

$templatecontext = (object)[
    'course'=>$course,
    'pluginname' => get_string('pluginname','report_vtcreport'),
    'activity_select' =>$output->render_include_activity_select($url, $activitytypes, $activityinclude),
    'complete'=> $complete,
    'scorestatistics' => $scorestatistics,
    'coursecompletestatics' => $coursecompletestatics,
    'coursecompletestaticslink' => $coursecompletestaticslink,
    'rate' => $ratedata,
    'ratelink' => (new moodle_url('/admin/tool/courserating/index.php?id='.$id))->out(false),
    'star' => (new moodle_url('/report/vtcreport/pix'))->out(false),
];



echo $OUTPUT->header();
echo $OUTPUT->render_from_template('report_vtcreport/view',$templatecontext);
echo $OUTPUT->footer();
