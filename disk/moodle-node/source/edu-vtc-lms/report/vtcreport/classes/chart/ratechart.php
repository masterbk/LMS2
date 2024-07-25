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
  
$rating = $DB->get_record('tool_courserating_summary', array('courseid' => $course->id), '*');

$ratedata['rate'] = $rating->avgrating? $rating->avgrating : 4.5;
$ratedata['count'] = $rating->cntall;
if($ratedata['count']>0){
    $ratedata['five'] = $rating->cnt05;
    $ratedata['fivepc'] = number_format(100* ($rating->cnt05 / $rating->cntall) , 2,'.','');
    $ratedata['four'] = $rating->cnt04;
    $ratedata['fourpc'] = number_format(100* ($rating->cnt04 / $rating->cntall) , 2, '.', '');
    $ratedata['three'] = $rating->cnt03;
    $ratedata['threepc'] = number_format(100* ($rating->cnt03 / $rating->cntall) , 2, '.', '');
    $ratedata['two'] = $rating->cnt02;
    $ratedata['twopc'] = number_format(100* ($rating->cnt02 / $rating->cntall) , 2, '.','');
    $ratedata['one'] = $rating->cnt01;
    $ratedata['onepc'] = number_format(100* ($rating->cnt01 / $rating->cntall) , 2, '.','');
}else{
    $ratedata['five'] = $rating->cnt05;
    $ratedata['fivepc'] = 0;
    $ratedata['four'] = $rating->cnt04;
    $ratedata['fourpc'] = 0;
    $ratedata['three'] = $rating->cnt03;
    $ratedata['threepc'] = 0;
    $ratedata['two'] = $rating->cnt02;
    $ratedata['twopc'] = 0;
    $ratedata['one'] = $rating->cnt01;
    $ratedata['onepc'] = 0;
}

$ratedata['stars'] = [];
for ($i=0; $i < round($ratedata['rate']); $i++) { 
    $ratedata['stars'][] = 1;
}