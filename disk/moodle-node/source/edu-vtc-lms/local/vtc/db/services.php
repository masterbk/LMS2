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
    "vtc_create_account"             => array(
        "classname"   => 'local_vtc\api',
        "classpath"   => "local_vtc/classes/api.php",
        "methodname"  => "create_account",
        "description" => "Create an account.",
        "type"        => "write",
        "ajax"        => true,
        "services"    => [MOODLE_OFFICIAL_MOBILE_SERVICE]
    ),

    "vtc_delete_account"             => array(
        "classname"   => 'local_vtc\api',
        "classpath"   => "local_vtc/classes/api.php",
        "methodname"  => "delete_account",
        "description" => "Delete an account.",
        "type"        => "write",
        "ajax"        => true,
        "services"    => [MOODLE_OFFICIAL_MOBILE_SERVICE]
    ),

    "vtc_list_categories"            => array(
        "classname"   => 'local_vtc\api',
        "classpath"   => "local_vtc/classes/api.php",
        "methodname"  => "list_categories",
        "description" => "List categories.",
        "type"        => "read",
        "ajax"        => true,
        "services"    => [MOODLE_OFFICIAL_MOBILE_SERVICE]
    ),

    "vtc_list_courses"               => array(
        "classname"   => 'local_vtc\api',
        "classpath"   => "local_vtc/classes/api.php",
        "methodname"  => "list_courses",
        "description" => "List courses.",
        "type"        => "read",
        "ajax"        => true,
        "services"    => [MOODLE_OFFICIAL_MOBILE_SERVICE]
    ),
    "vtc_get_user_information"       => array(
        "classname"   => 'local_vtc\api',
        "classpath"   => "local_vtc/classes/api.php",
        "methodname"  => "get_user_information",
        "description" => "Get user information.",
        "type"        => "read",
        "ajax"        => true,
        "services"    => [MOODLE_OFFICIAL_MOBILE_SERVICE]
    ),
    "vtc_update_user_information"    => array(
        "classname"   => 'local_vtc\api',
        "classpath"   => "local_vtc/classes/api.php",
        "methodname"  => "update_user_information",
        "description" => "Update user information.",
        "type"        => "read",
        "ajax"        => true,
        "services"    => [MOODLE_OFFICIAL_MOBILE_SERVICE]
    ),
    "vtc_get_list_parent_categories" => array(
        "classname"   => 'local_vtc\api',
        "classpath"   => "local_vtc/classes/api.php",
        "methodname"  => "get_list_parent_categories",
        "description" => "get List parent categories ",
        "type"        => "read",
        "ajax"        => true,
        "services"    => [MOODLE_OFFICIAL_MOBILE_SERVICE]
    ),
    "vtc_get_list_courses_by_filter" => array(
        "classname"   => 'local_vtc\api',
        "classpath"   => "local_vtc/classes/api.php",
        "methodname"  => "get_list_courses_by_filter",
        "description" => "list courses by filter",
        "type"        => "read",
        "ajax"        => true,
        "services"    => [MOODLE_OFFICIAL_MOBILE_SERVICE]
    ),
    "vtc_user_update_password"       => array(
        "classname"   => 'local_vtc\api',
        "classpath"   => "local_vtc/classes/api.php",
        "methodname"  => "user_update_password",
        "description" => "change user password",
        "type"        => "write",
        "ajax"        => true,
        "services"    => [MOODLE_OFFICIAL_MOBILE_SERVICE]
    ),
    "vtc_get_list_certificate_by_filter"       => array(
        "classname"   => 'local_vtc\api',
        "classpath"   => "local_vtc/classes/api.php",
        "methodname"  => "get_list_certificate_by_filter",
        "description" => "get list certificate by filter",
        "type"        => "write",
        "ajax"        => true,
        "services"    => [MOODLE_OFFICIAL_MOBILE_SERVICE]
    ),
    "vtc_get_download_certificate"       => array(
        "classname"   => 'local_vtc\api',
        "classpath"   => "local_vtc/classes/api.php",
        "methodname"  => "get_download_certificate",
        "description" => "generate certificate pdf download certificate",
        "type"        => "read",
        "ajax"        => true,
        "services"    => [MOODLE_OFFICIAL_MOBILE_SERVICE]
    ),
    "vtc_update_rating_course" => array(
        "classname"   => 'local_vtc\api',
        "classpath"   => "local_vtc/classes/api.php",
        "methodname"  => "update_rating_course",
        "description" => "update rating course",
        "type"        => "write",
        "ajax"        => true,
        "services"    => [MOODLE_OFFICIAL_MOBILE_SERVICE]
    ),
    "vtc_get_rating_course"       => array(
        "classname"   => 'local_vtc\api',
        "classpath"   => "local_vtc/classes/api.php",
        "methodname"  => "get_rating_course",
        "description" => "get rating course",
        "type"        => "read",
        "ajax"        => true,
        "services"    => [MOODLE_OFFICIAL_MOBILE_SERVICE]
    ),
    "vtc_get_course_document" => array(
        "classname"   => 'local_vtc\api',
        "classpath"   => "local_vtc/classes/api.php",
        "methodname"  => "get_course_document",
        "description" => "get course document ",
        "type"        => "read",
        "ajax"        => true,
        "services"    => [MOODLE_OFFICIAL_MOBILE_SERVICE]
    ),
    "vtc_get_course_quiz_info" => array(
        "classname"   => 'local_vtc\api',
        "classpath"   => "local_vtc/classes/api.php",
        "methodname"  => "get_course_quiz_info",
        "description" => "get course quiz info",
        "type"        => "read",
        "ajax"        => true,
        "services"    => [MOODLE_OFFICIAL_MOBILE_SERVICE]
    ),
    "vtc_get_course_start_date" => array(
        "classname"   => 'local_vtc\api',
        "classpath"   => "local_vtc/classes/api.php",
        "methodname"  => "get_course_start_date",
        "description" => "get_course_start_date",
        "type"        => "read",
        "ajax"        => true,
        "services"    => [MOODLE_OFFICIAL_MOBILE_SERVICE]
    ),
);
