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
 * This file contains the moodle hooks for the assign module.
 *
 * It delegates most functions to the assignment class.
 *
 * @package   local_vtc
 * @copyright Videa
 * @author    Bob Nguyen
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

function report_vtcreport_extend_navigation_course($navigation, $course, $context) {
    if (has_capability('report/vtcreport:view', $context)) {
        $url = new moodle_url('/report/vtcreport/view.php', array('course'=>$course->id));
        $navigation->add(get_string('pluginname', 'report_vtcreport'), $url, navigation_node::TYPE_SETTING, null, null, new pix_icon('i/report', ''));
    }
}