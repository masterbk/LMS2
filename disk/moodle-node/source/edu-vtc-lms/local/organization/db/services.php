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
 * VTC extended functions and service definitions.
 *
 * @package    local_vtc
 * @copyright  2023 Videa - https://videabiz.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined("MOODLE_INTERNAL") || die();

$functions = array(
    "org_search_course" => array(
        "classname"   => 'local_organization\api',
        "classpath"   => "local_organization/classes/api.php",
        "methodname"  => "org_search_course",
        "description" => "org_search_course.",
        "type"        => "read",
        "ajax"        => true,
        "services"    => [MOODLE_OFFICIAL_MOBILE_SERVICE]
    ),
    'org_create_courses' => array(
        "classname"   => 'local_organization\api',
        "classpath"   => "local_organization/classes/api.php",
        'methodname'  => 'org_create_courses',        
        'description' => 'Create new courses',
        'type' => 'write',
        "services"    => [MOODLE_OFFICIAL_MOBILE_SERVICE]
        //'capabilities' => 'moodle/course:create, moodle/course:visibility',
    ),

  
);
