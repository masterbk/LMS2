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

$context = context_system::instance();

$PAGE->set_url(new moodle_url('/report/vtcreport/view-test.php'));
$PAGE->set_context($context);
$PAGE->set_pagelayout('report');
$PAGE->set_title($SITE->shortname.": ".get_string('pluginname','report_vtcreport'));
$PAGE->set_heading($SITE->fullname);

$CFG->chart_colorset = ['#C893FD','#4A3AFF'];
$chart = new core\chart_bar();

$series1 = new \core\chart_series("nhomA", [50,100]);
$series1->set_yaxis(0);
$chart->add_series($series1);


$series2 = new \core\chart_series("nhomb", [500,1000]);
$series2->set_yaxis(1);
$chart->add_series($series2);

$chart->set_labels(['label1','label2']);



$yaxis1 = $chart->get_yaxis(0, true); // Select the second Y axis.
$yaxis1->set_position(\core\chart_axis::POS_LEFT);
$chart->set_yaxis($yaxis1,0);

$yaxis2 = $chart->get_yaxis(1, true); // Select the second Y axis.
$yaxis2->set_position(\core\chart_axis::POS_RIGHT);
$yaxis2->set_min(0);
$chart->set_yaxis($yaxis2,1);

$chartrender =  $OUTPUT->render_chart($chart, false);


$chart = new \core\chart_pie();
$chartdata = new core\chart_series('bài học', [30, 70]);
$chart->add_series($chartdata);
$chart->set_labels(["nhon1",'nhom2']);
$chartrender2 =  $OUTPUT->render_chart($chart, false);

$templatecontext = (object)[
    'chartrender'=>$chartrender,
    'chartrender2'=>$chartrender2,
];



echo $OUTPUT->header();
echo $OUTPUT->render_from_template('report_vtcreport/view-test',$templatecontext);
echo $OUTPUT->footer();