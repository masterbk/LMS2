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

defined('MOODLE_INTERNAL') || die();

function local_bulkcustomcert_extend_navigation_course($navigation, $course, $context)
{
    global $DB;
    if (has_capability('mod/customcert:viewallcertificates', $context)) {
        $courseid = $course->id;

        $sql = "SELECT cci.userid
                FROM {customcert_issues} as cci
                INNER JOIN {customcert} as cc ON cci.customcertid = cc.id
                WHERE cc.course = {$courseid} LIMIT 1";
        $availablecerts = $DB->get_record_sql($sql);

        if ($availablecerts) {
            $url = new moodle_url('/local/bulkcustomcert/index.php', array('id' => $course->id));
            $name = get_string('pluginname', 'local_bulkcustomcert');
            $navigation->add($name, $url, navigation_node::TYPE_SETTING, null, null, new pix_icon('i/settings', ''));
        }
    }
}




