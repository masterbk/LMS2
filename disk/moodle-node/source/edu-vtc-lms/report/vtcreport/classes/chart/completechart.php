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
use core\report_helper;
use \report_progress\local\helper;

require_once(dirname(__FILE__) . '/../../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/completionlib.php');


global $DB;


$id = required_param('course',PARAM_INT);
$course = $DB->get_record('course',array('id'=>$id));
if (!$course) {
    throw new \moodle_exception('invalidcourseid');
}
$context = context_course::instance($course->id);



$PAGE->set_url(new moodle_url('/report/vtcreport/view.php'));
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_title($SITE->shortname.": ".get_string('pluginname','report_vtcreport'));
$PAGE->set_heading($SITE->fullname);


// số người hoàn thành bài học
$groupid = optional_param('group', 0, PARAM_INT);
$activityinclude = optional_param('activityinclude', 'all', PARAM_TEXT);
$activityorder = optional_param('activityorder', 'orderincourse', PARAM_TEXT);
$activitysection = optional_param('activitysection', -1, PARAM_INT);


$url = new moodle_url('/report/vtcreport/view.php', array('course'=>$id));

if ($activityinclude !== '') {
    $url->param('activityinclude', $activityinclude);
}
if ($activityorder !== '') {
    $url->param('activityorder', $activityorder);
}
if ($activitysection !== '') {
    $url->param('activitysection', $activitysection);
}
$PAGE->set_url($url);


$group = groups_get_course_group($course,true); // Supposed to verify group
if ($group===0 && $course->groupmode==SEPARATEGROUPS) {
    require_capability('moodle/site:accessallgroups',$context);
}


$completion = new completion_info($course);
list($activitytypes, $activities) = helper::get_activities_to_show($completion, $activityinclude, $activityorder, $activitysection);

 
// Generate where clause
$where = array();
$where_params = array();
// Get user match count
$total = $completion->get_num_tracked_users(implode(' AND ', $where), $where_params, $group);

// Total user count
$grandtotal = $completion->get_num_tracked_users('', array(), $group);

// Get user data
$progress = array();

if ($total) {
    $progress = $completion->get_progress_all(
        implode(' AND ', $where),
        $where_params,
        $group,
        'u.firstname ASC, u.lastname ASC',
        0,
        0,
        $context
    );
}



$output = $PAGE->get_renderer('report_progress'); 

$CFG->chart_colorset = ['#E5EAFC', '#4A3AFF', '#F012BE', '#85144b', '#B10DC9'];
$chartar = [];



foreach($activities as $activity) {

	$done = 0;
	$notdone = 0;    

	foreach($progress as $user) { 

	    if (array_key_exists($activity->id, $user->progress)) {
            $thisprogress = $user->progress[$activity->id];
            $state = $thisprogress->completionstate;          
           
        } else {
            $state = COMPLETION_INCOMPLETE;            
        }

        // Work out how it corresponds to an icon
        switch($state) {
            case COMPLETION_INCOMPLETE :
                $notdone++;
                break;
            case COMPLETION_COMPLETE :
                $done++;
                break;
            case COMPLETION_COMPLETE_PASS :
                $done++; 
                break;
            case COMPLETION_COMPLETE_FAIL :
                $notdone++;
                break;
        }
       
	} 

	$chart = new \core\chart_pie();
	$chartdata = new core\chart_series('Học viên', [$notdone, $done]);
	$chart->add_series($chartdata);
    $chart->set_labels(["Hoàn thành",'Chưa hoàn thành']);
    $chart->set_legend_options([
        'display' => true,
        'position' => 'left'
    ]);

    //$chart->set_legend_options(['display' => false, 'width' => '700px','height' => '300px']);

	$c = [
            'chart' => $OUTPUT->render_chart($chart, false),
            'name' => format_string($activity->name, true, array('context' => $activity->context))
        ];                
    $chartar[] = $c;               
          
}


$complete['chart'] = $chartar;

