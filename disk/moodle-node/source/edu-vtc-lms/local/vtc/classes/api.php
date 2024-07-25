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
 * Accessibility API endpoints
 *
 * @package    local_vtc
 * @copyright  2023 Videa - https://videabiz.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_vtc;

use context_course;
use context_system;
use core_course\external\course_summary_exporter;
use core_course\external\helper_for_get_mods_by_courses;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use mod_customcert\certificate;
use mod_quiz_external;
use moodle_url;
use stdClass;
use tool_courserating\constants;
use tool_courserating\event\rating_created;
use tool_courserating\event\rating_updated;
use tool_courserating\external\summary_exporter;
use tool_courserating\helper;
use tool_courserating\local\models\rating;
use tool_courserating\local\models\summary;
use core_external\external_warnings;

defined("MOODLE_INTERNAL") || die;

global $CFG;

require_once "{$CFG->dirroot}/user/lib.php";
require_once "{$CFG->dirroot}/user/editlib.php";
require_once "{$CFG->dirroot}/user/profile/lib.php";
require_once "{$CFG->dirroot}/login/lib.php";
require_once "{$CFG->dirroot}/theme/moove/lib.php";

/**
 * Deleting user's endpoint.
 *
 * @package    local_vtc
 * @copyright  2023 Videa - https://videabiz.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class api extends external_api {
    public static function delete_account_parameters() {
        return new external_function_parameters(
            array(
                "password" => new external_value(PARAM_RAW, "Password for confirmation")
            )
        );
    }

    public static function delete_account_returns() {
        return new external_single_structure(
            array(
                "success" => new external_value(PARAM_BOOL, "Operation response"),
                "reason"  => new external_value(PARAM_TEXT, "Failure reason"),
                "message" => new external_value(PARAM_TEXT, "Message")
            )
        );
    }

    public static function delete_account($password) {
        global $DB, $USER;

        $params = self::validate_parameters(
            self::delete_account_parameters(),
            array(
                "password" => $password
            )
        );

        extract($params);
        $user = $DB->get_record("user", array("id" => $USER->id));

        if (empty($user)) {
            return array(
                "success" => false,
                "reason"  => "usernotfound",
                "message" => "No user found with ID {$USER->id}."
            );
        }

        $auth = get_auth_plugin($user->auth);

        if (!$auth->user_login($user->username, $password)) {
            return array(
                "success" => false,
                "reason"  => "wrongpassword",
                "message" => "Wrong password provided for user {$user->username}."
            );
        }

        \user_delete_user($user);

        return array(
            "success" => true,
            "reason"  => null,
            "message" => "User deleted"
        );
    }

    public static function create_account_parameters() {
        return new external_function_parameters(
            array(
                "username" => new external_value(PARAM_USERNAME, "Username"),
                "fullname" => new external_value(PARAM_TEXT, "User's full name"),
                "email"    => new external_value(PARAM_EMAIL, "User's email address"),
                "password" => new external_value(PARAM_RAW, "Password"),
                "address"  => new external_value(PARAM_TEXT, "User's address"),
            )
        );
    }

    public static function create_account_returns() {
        return new external_single_structure(
            array(
                "success" => new external_value(PARAM_BOOL, "Operation response"),
                "reason"  => new external_value(PARAM_TEXT, "Failure reason"),
                "message" => new external_value(PARAM_TEXT, "Message"),
                "errors"  => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            "field"   => new external_value(PARAM_TEXT),
                            "message" => new external_value(PARAM_RAW_TRIMMED)
                        )
                    ),
                    "Error messages",
                    VALUE_OPTIONAL
                ),
                "data"    => new external_single_structure(
                    array(
                        "id"        => new external_value(PARAM_INT, "User's ID", VALUE_OPTIONAL),
                        "confirmed" => new external_value(PARAM_BOOL, "User has been confirmed. If false, user need to confirm their email address by clicking verification link sent in email.", VALUE_OPTIONAL)
                    ),
                    "Data",
                    VALUE_OPTIONAL
                )
            )
        );
    }

    public static function create_account($username, $fullname, $email, $password, $address) {
        global $DB, $CFG;

        $params = self::validate_parameters(
            self::create_account_parameters(),
            array(
                "username" => $username,
                "fullname" => $fullname,
                "email"    => $email,
                "password" => $password,
                "address"  => $address
            )
        );

        extract($params);
        $auth = get_auth_plugin($CFG->registerauth);

        $nameParts = explode(" ", $fullname);
        $firstname = array_pop($nameParts);
        $lastname = implode(" ", $nameParts);

        $user = new \stdClass;
        $user->username = $username;
        $user->firstname = $firstname;
        $user->lastname = $lastname;
        $user->email = $email;
        $user->email2 = $email;
        $user->address = $address;
        $user->city = $address;
        $user->country = "VN";
        $user->password = $password;

        $errors = signup_validate_data((array) $user, []);

        if (!empty($errors)) {
            $e = array();
            foreach ($errors as $key => $value) {
                $e[] = array(
                    "field"   => $key,
                    "message" => $value
                );
            }

            return array(
                "success" => false,
                "reason"  => "validationfailed",
                "message" => "Data validation failed with errors",
                "errors"  => $e,
                "data"    => []
            );
        }

        $user->password = \hash_internal_user_password($password);
        signup_setup_new_user($user);
        core_login_post_signup_requests($user);
        $auth->user_signup($user, false);

        return array(
            "success" => true,
            "reason"  => null,
            "message" => "User created with ID {$user->id}",
            "errors"  => [],
            "data"    => $user
        );
    }

    public static function list_categories_parameters() {
        return new external_function_parameters(array());
    }

    public static function list_categories_returns() {
        return new external_single_structure(
            array(
                "success" => new external_value(PARAM_BOOL, "Operation response"),
                "reason"  => new external_value(PARAM_TEXT, "Failure reason"),
                "message" => new external_value(PARAM_TEXT, "Message"),
                "data"    => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            "id"           => new external_value(PARAM_INT),
                            "name"         => new external_value(PARAM_TEXT),
                            "description"  => new external_value(PARAM_RAW_TRIMMED),
                            "courses"      => new external_value(PARAM_INT),
                            "certificates" => new external_value(PARAM_INT)
                        )
                    ),
                    "Data",
                    VALUE_OPTIONAL
                )
            )
        );
    }

    public static function list_categories() {
        global $DB, $USER;

        $cats = $DB->get_records(
            "course_categories",
            array(
                "depth"   => 2,
                "visible" => true
            )
        );

        $data = array();

        foreach ($cats as &$cat) {
            $sql = "SELECT (c.id) FROM {course} c
					LEFT JOIN {enrol} e ON e.courseid = c.id
					LEFT JOIN {user_enrolments} ue ON ue.enrolid = e.id
					
					WHERE ue.userid = :userid AND c.category = :categoryid AND c.visible = 1 AND c.startdate <= :ctime1 AND (c.enddate = 0 OR c.enddate > :ctime2)";

            $courses = $DB->get_records_sql(
                $sql,
                array(
                    "userid"     => $USER->id,
                    "categoryid" => $cat->id,
                    "ctime1"     => time(),
                    "ctime2"     => time()
                )
            );
            $count = 0;
            foreach ($courses as $c) {
                $sc = \core_course_category::get($c->category);
                $progress = getCourseProgressPercentage($c, $USER->id);
                $c->scName = $sc->name;
                $coursecontext = context_course::instance($c->id);
                $moduleCertificateId = $DB->get_record('modules', array('name' => 'customcert'))->id;
                $countCourseActivitiesSql = "select * from {course_modules} where course = :course and module = :moduled ";
                $certificate = $DB->get_record_sql($countCourseActivitiesSql, array('course' => $c->id, 'moduled' => $moduleCertificateId));
                $customcert = $DB->get_record('customcert', array('course' => $c->id));
                if (is_enrolled($coursecontext, $USER->id) && $customcert && themeMooveCheckRoleUserInCourse($c->id, 'student') && $progress == 100) {
                    $template = $DB->get_record('customcert_templates', array('id' => $customcert->templateid));
                    if ($template) {
                        if ($certificate) {
                            $count++;
                        }
                    }
                }
            }
            $data[] = array(
                "id"           => $cat->id,
                "name"         => $cat->name,
                "description"  => $cat->description,
                "courses"      => count($courses),
                "certificates" => $count
            );
        }

        return array(
            "success" => true,
            "reason"  => null,
            "message" => "Categories fetched.",
            "data"    => $data
        );
    }

    public static function list_courses_parameters() {
        return new external_function_parameters(
            array(
                "catid"  => new external_value(PARAM_INT, "catid", VALUE_OPTIONAL),
                "filter" => new external_value(PARAM_TEXT, "filter", VALUE_OPTIONAL, "all"),
                "all"    => new external_value(PARAM_INT, "catid", VALUE_OPTIONAL)
            )
        );
    }

    public static function list_courses_returns() {
        return new external_single_structure(
            array(
                "success" => new external_value(PARAM_BOOL, "Operation response"),
                "reason"  => new external_value(PARAM_TEXT, "Failure reason"),
                "message" => new external_value(PARAM_TEXT, "Message"),
                "data"    => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            "id"          => new external_value(PARAM_INT),
                            "fullname"    => new external_value(PARAM_TEXT),
                            "shortname"   => new external_value(PARAM_TEXT),
                            "description" => new external_value(PARAM_TEXT),
                            "category"    => new external_single_structure(
                                array(
                                    "id"   => new external_value(PARAM_INT),
                                    "name" => new external_value(PARAM_TEXT)
                                )
                            ),
                            "teachers"    => new external_multiple_structure(
                                new external_single_structure(
                                    array(
                                        "id"           => new external_value(PARAM_INT),
                                        "name"         => new external_value(PARAM_TEXT),
                                        "image"        => new external_value(PARAM_URL),
                                        "academicRank" => new external_value(PARAM_TEXT)
                                    )
                                ),
                                "Teachers"
                            ),
                            "image"       => new external_value(PARAM_URL),
                            "progress"    => new external_value(PARAM_FLOAT),
                            "rating"      => new external_single_structure(
                                array(
                                    "value" => new external_value(PARAM_FLOAT),
                                    "total" => new external_value(PARAM_INT)
                                )
                            ),
                            "start"       => new external_value(PARAM_INT, "", VALUE_OPTIONAL),
                            "end"         => new external_value(PARAM_INT, "", VALUE_OPTIONAL),
                            "lastaccess"  => new external_value(PARAM_INT, "", VALUE_OPTIONAL),
                            "enrolled"    => new external_value(PARAM_BOOL, "", VALUE_OPTIONAL),
                            "label"       => new external_value(PARAM_TEXT, "", VALUE_OPTIONAL),
                            "time"        => new external_value(PARAM_TEXT, "", VALUE_OPTIONAL)
                        )
                    ),
                    "Data",
                    VALUE_OPTIONAL
                )
            )
        );
    }

    public static function list_courses($catid = null, $filter = "all", int $all = 0) {
        global $DB, $USER;

        $params = self::validate_parameters(
            self::list_courses_parameters(),
            array(
                "catid"  => $catid,
                "filter" => $filter,
                "all"    => $all
            )
        );

        extract($params);

        if (!empty($catid)) {
            $cat = \core_course_category::get($catid);

            $sql = "SELECT c.* FROM {course} c
					WHERE c.category = :categoryid AND c.visible = 1 AND c.startdate <= :ctime1 AND (c.enddate = 0 OR c.enddate > :ctime2)";

            if ($filter === "latest")
                $sql .= "\nORDER BY c.timecreated DESC";

            $courses = $DB->get_records_sql(
                $sql,
                array(
                    "userid"     => $USER->id,
                    "categoryid" => $cat->id,
                    "ctime1"     => time(),
                    "ctime2"     => time()
                )
            );
        } else {
            $sql = "SELECT c.* FROM {course} c
					LEFT JOIN {course_categories} cc ON c.category = cc.id
					WHERE cc.depth = 2 AND c.visible = 1 AND c.startdate <= :ctime1 AND (c.enddate = 0 OR c.enddate > :ctime2)";

            if ($filter === "latest")
                $sql .= "\nORDER BY c.timecreated DESC";

            $courses = $DB->get_records_sql(
                $sql,
                array(
                    "userid" => $USER->id,
                    "ctime1" => time(),
                    "ctime2" => time()
                )
            );
        }

        if (in_array($filter, ["outstanding", "latest"]))
            $courses = filterCourse($courses, $filter);

        $data = array();

        foreach ($courses as $course) {
            $ctx = \context_course::instance($course->id);
            $enrolled = (is_enrolled($ctx, $USER) && themeMooveCheckRoleUserInCourse($course->id, "student"));

            if ($all == 1 && !$enrolled) {
                continue;
            } else if ($all == -1 && $enrolled) {
                continue;
            }

            $image = course_summary_exporter::get_course_image($course);
            $progress = course_summary_exporter::get_course_progress($course);
            $rating = array("value" => 5, "total" => 0);

            if (\core_component::get_plugin_directory("tool", "courserating")) {
                $summary = $DB->get_record(
                    "tool_courserating_summary",
                    array(
                        "courseid"   => $course->id,
                        "ratingmode" => \tool_courserating\helper::get_course_rating_mode($course->id)
                    )
                );

                $rating["value"] = (float) $summary->avgrating;
                $rating["total"] = (int) $summary->cntall;
            }

            $label = getCustomField("context_course", $course->id, "label");
            $time = getCustomField("context_course", $course->id, "time");

            if (empty($catid))
                $cat = \core_course_category::get($course->category);

            // Get the logs data for the specific course and user.
            $log = $DB->get_records("logstore_standard_log", array(
                "courseid" => $course->id,
                "userid"   => $USER->id
            ), "timecreated DESC", "*", 0, 1);

            $log = reset($log);
            $records = getListUsersByRole($course, 'editingteacher');
            $teachers = array();

            foreach ($records as $record) {
                $teachers[] = array(
                    "id"           => $record->id,
                    "name"         => fullname($record),
                    "image"        => $record->avatar,
                    "academicRank" => $record->academicrank
                );
            }

            $data[] = array(
                "id"          => $course->id,
                "fullname"    => $course->fullname,
                "shortname"   => $course->shortname,
                "description" => $course->description,
                "category"    => array(
                    "id"   => $cat->id,
                    "name" => $cat->name
                ),
                "teachers"    => $teachers,
                "image"       => $image,
                "progress"    => $progress,
                "rating"      => $rating,
                "start"       => $course->startdate,
                "end"         => $course->enddate,
                "lastaccess"  => empty($log) ? null : $log->timecreated,
                "enrolled"    => $enrolled,
                "label"       => $label["label"],
                "time"        => $time["time0"] ? $time["time"] : null
            );
        }

        return array(
            "success" => true,
            "reason"  => null,
            "message" => "Courses fetched",
            "data"    => $data
        );
    }

    public static function get_user_information_parameters() {
        return new external_function_parameters(array());
    }

    public static function get_user_information() {
        global $USER, $DB, $PAGE;
        $userCustomfieldDateofbirth = $DB->get_record('user_info_field', array('shortname' => 'dateofbirth'));
        $userCustomfieldGender = $DB->get_record('user_info_field', array('shortname' => 'gender'));
        $userCustomfieldJob = $DB->get_record('user_info_field', array('shortname' => 'job'));

        $userDateofbirth = $DB->get_record('user_info_data', array('fieldid' => $userCustomfieldDateofbirth->id, 'userid' => $USER->id));
        if ($userDateofbirth) {
            $userDateofbirth = $userDateofbirth->data;
        }
        $userGender = $DB->get_record('user_info_data', array('fieldid' => $userCustomfieldGender->id, 'userid' => $USER->id));
        if ($userGender) {
            $userGender = $userGender->data;
        }
        $userjob = $DB->get_record('user_info_data', array('fieldid' => $userCustomfieldJob->id, 'userid' => $USER->id));
        if ($userjob) {
            $userjob = $userjob->data;
        }
        $user = $DB->get_record('user', array('id' => $USER->id));
        return [
            'username'    => $user->username,
            'fullname'    => $user->firstname . ' ' . $user->lastname,
            'email'       => $user->email,
            'phone1'      => $user->phone1,
            'gender'      => $userGender,
            'address'     => $user->address,
            'dateofbirth' => date('Y-m-d', $userDateofbirth),
            'job'         => $userjob,
            'city'        => $user->city,

        ];
    }

    public static function get_user_information_returns() {
        return new external_single_structure(
            array(
                "username"    => new external_value(PARAM_TEXT),
                "fullname"    => new external_value(PARAM_TEXT),
                "email"       => new external_value(PARAM_TEXT),
                "phone1"      => new external_value(PARAM_TEXT),
                "gender"      => new external_value(PARAM_TEXT),
                "address"     => new external_value(PARAM_TEXT),
                "dateofbirth" => new external_value(PARAM_TEXT),
                "job"         => new external_value(PARAM_TEXT),
                "city"        => new external_value(PARAM_TEXT),
            )
        );

    }

    public static function update_user_information_parameters() {
        return new external_function_parameters(
            array(
                "fullname"    => new external_value(PARAM_TEXT),
                "email"       => new external_value(PARAM_TEXT),
                "phone1"      => new external_value(PARAM_TEXT),
                "gender"      => new external_value(PARAM_TEXT),
                "address"     => new external_value(PARAM_TEXT),
                "dateofbirth" => new external_value(PARAM_TEXT),
                "job"         => new external_value(PARAM_TEXT),
                "city"        => new external_value(PARAM_TEXT),
            )
        );
    }

    public static function update_user_information($fullname, $email, $phone1, $gender, $address, $dateofbirth, $job, $city) {
        $params = self::validate_parameters(
            self::update_user_information_parameters(),
            array(
                "fullname"    => $fullname,
                "email"       => $email,
                "phone1"      => $phone1,
                "gender"      => $gender,
                "address"     => $address,
                "dateofbirth" => $dateofbirth,
                "job"         => $job,
                "city"        => $city,
            )
        );

        extract($params);
        global $DB, $USER, $CFG;
        $error = [];
        if (!$fullname) {
            $error['fullname'] = get_string("fullname_required", "theme_moove");
        }

//        if (!$phone1) {
//            $error['phone1'] = get_string("phonerequired", "theme_moove");
//
//        }
        if (!$email) {
            $error['email'] = get_string("email_required", "theme_moove");
        }
//        if (!$city) {
//            $error['city'] = get_string("city_required", "theme_moove");
//        }

        if ($error) {
            $error['statuserror'] = true;

            return [
                'success' => false,
                'reason'  => '',
                'message' => '',
                'data'    => json_encode($error)
            ];

        } else {
            $user = $DB->get_record('user', array('id' => $USER->id));
            $user->firstname = explode(' ', $fullname, 2)[0];
            $user->lastname = explode(' ', $fullname, 2)[1];
            $user->phone1 = $phone1;
            $user->email = $email;
            $user->address = $address;
            $DB->update_record('user', $user);

            $userCustomfieldDateofbirth = $DB->get_record('user_info_field', array('shortname' => 'dateofbirth'));
            if ($userCustomfieldDateofbirth) {
                $userDateofbirth = $DB->get_record('user_info_data', array('fieldid' => $userCustomfieldDateofbirth->id, 'userid' => $USER->id));
                if ($userDateofbirth) {
                    $userDateofbirth->data = strtotime($dateofbirth);
                    $DB->update_record('user_info_data', $userDateofbirth);

                } else {
                    $dateofbirthObj = new stdClass();
                    $dateofbirthObj->fieldid = $userCustomfieldDateofbirth->id;
                    $dateofbirthObj->userid = $USER->id;
                    $dateofbirthObj->data = strtotime($dateofbirth);
                    $DB->insert_record('user_info_data', $dateofbirth);
                }
            }
            $userCustomfieldGender = $DB->get_record('user_info_field', array('shortname' => 'gender'));
            if ($userCustomfieldGender) {
                $userGender = $DB->get_record('user_info_data', array('fieldid' => $userCustomfieldGender->id, 'userid' => $USER->id));
                if ($userGender) {
                    $userGender->data = $gender;
                    $DB->update_record('user_info_data', $userGender);
                } else {
                    $genderObj = new stdClass();
                    $genderObj->fieldid = $userCustomfieldGender->id;
                    $genderObj->userid = $USER->id;
                    $genderObj->data = $gender;
                    $DB->insert_record('user_info_data', $gender);
                }
            }
            $userCustomfieldJob = $DB->get_record('user_info_field', array('shortname' => 'job'));
            if ($userCustomfieldJob) {
                $userjob = $DB->get_record('user_info_data', array('fieldid' => $userCustomfieldJob->id, 'userid' => $USER->id));
                if ($userjob) {
                    $userjob->data = $job;
                    $DB->update_record('user_info_data', $userjob);
                } else {
                    $jobObj = new stdClass();
                    $jobObj->fieldid = $userCustomfieldJob->id;
                    $jobObj->userid = $USER->id;
                    $jobObj->data = $job;
                    $DB->insert_record('user_info_data', $job);
                }
            }
        }
        return [
            'success' => true,
            'reason'  => '',
            'message' => '',
            'data'    => json_encode([])
        ];
    }

    public static function update_user_information_returns() {
        return new external_single_structure(
            array(
                "success" => new external_value(PARAM_BOOL, "Operation response"),
                "reason"  => new external_value(PARAM_TEXT, "Failure reason"),
                "message" => new external_value(PARAM_TEXT, "Message"),
                "data"    => new external_value(PARAM_RAW, "Message"),
            )
        );
    }

    public static function get_list_parent_categories_parameters() {
        return new external_function_parameters(array());
    }

    public static function get_list_parent_categories() {
        global $DB;
        $sql = "SELECT * from {course_categories} where parent =0 and visible =1";
        $categories = $DB->get_records_sql($sql);
        date_default_timezone_set('Asia/Ho_Chi_Minh');
        $now = time();
        $listCategories = [];
        foreach ($categories as $c) {
            $sql = "SELECT * FROM {course_categories} where parent = :id and visible = 1";
            $subcategories = $DB->get_records_sql($sql, array('id' => $c->id));
            $listSubCategories = [];
            foreach ($subcategories as $sc) {
                $sql = 'SELECT * FROM {course} WHERE visible != 0 and (startdate < :time1 and ( enddate > :time2 or enddate =0 )) and category = :id order by sortorder ASC';
                $courses = $DB->get_records_sql($sql, array('time1' => $now, 'time2' => $now, 'id' => $sc->id));
                $listSubCategories[] = [
                    'id'           => $sc->id,
                    'name'         => $sc->name,
                    'numbercourse' => count($courses)
                ];
            }
            $listCategories[] = [
                'id'   => $c->id,
                'name' => $c->name,
                'sub'  => $listSubCategories
            ];
        }
        return array(
            "success" => true,
            "reason"  => null,
            "message" => "List categories and list sub category and number course",
            "data"    => $listCategories
        );
    }

    public static function get_list_parent_categories_returns() {
        return new external_single_structure(
            array(
                "success" => new external_value(PARAM_BOOL, "Operation response"),
                "reason"  => new external_value(PARAM_TEXT, "Failure reason"),
                "message" => new external_value(PARAM_TEXT, "Message"),
                "data"    => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            "id"   => new external_value(PARAM_INT),
                            "name" => new external_value(PARAM_TEXT),
                            "sub"  => new external_multiple_structure(
                                new external_single_structure(
                                    array(
                                        "id"           => new external_value(PARAM_INT),
                                        "name"         => new external_value(PARAM_TEXT),
                                        "numbercourse" => new external_value(PARAM_INT),
                                    )
                                )
                            ),

                        )
                    ),
                    "Data",
                    VALUE_OPTIONAL
                )
            )
        );
    }

    public static function get_list_courses_by_filter_parameters() {
        return new external_function_parameters(array(
            'cateid'    => new external_value(PARAM_INT),
            'subcateid' => new external_value(PARAM_INT),
            'filterid'  => new external_value(PARAM_INT),
        ));
    }

    public static function get_list_courses_by_filter($cateid, $subcateid, $filterid) {
        global $DB, $USER;
        $params = self::validate_parameters(
            self::get_list_courses_by_filter_parameters(),
            array(
                "cateid"    => $cateid,
                "subcateid" => $subcateid,
                "filterid"  => $filterid,
            )
        );

        extract($params);
        $cate = $cateid;
        $subCate = $subcateid;
        $filter = $filterid;
        date_default_timezone_set('Asia/Ho_Chi_Minh');
        $now = time();
        $listCourses = [];
        if ($cate) {
            $subCategories = $DB->get_records('course_categories', array("parent" => $cate));
            $listSubCate = [];
            foreach ($subCategories as $sc) {
                $listSubCate[] = [
                    'id'   => $sc->id,
                    'name' => $sc->name
                ];
            }
            if ($subCate) {
                $sql = 'SELECT * FROM {course} WHERE visible != 0 and (startdate < :time1 and ( enddate > :time2 or enddate =0 )) and category = :id order by sortorder ASC';
                $courses = $DB->get_records_sql($sql, array('time1' => $now, 'time2' => $now, 'id' => $subCate));
            } else {
                $sql = 'SELECT * FROM {course} WHERE visible != 0 and (startdate < :time1 and ( enddate > :time2 or enddate =0 )) and category IN (SELECT id FROM {course_categories} WHERE parent = :parent and depth = 2) order by sortorder ASC';
                $courses = $DB->get_records_sql($sql, array('time1' => $now, 'time2' => $now, 'parent' => $cate));
            }
            if ($filter == 1) {
                $courses = filterCourse($courses, 'latest');
            }
            if ($filter == 2) {
                $courses = filterCourse($courses, 'outstanding');
            }
            if ($filter == 3) {
                $courses = array_filter($courses, function ($c) use ($USER) {
                    $coursecontext = context_course::instance($c->id);
                    if (!is_enrolled($coursecontext, $USER->id)) {
                        return $c;
                    }
                    return '';
                });
            }
            if ($filter == 4) {
                $courses = array_filter($courses, function ($c) use ($USER) {
                    $coursecontext = context_course::instance($c->id);
                    if (is_enrolled($coursecontext, $USER->id) && themeMooveCheckRoleUserInCourse($c->id, 'student')) {
                        if (getCourseProgressPercentage($c, $USER->id) < 100)
                            return $c;
                    }
                    return '';
                });
            }
            if ($filter == 5) {
                $courses = array_filter($courses, function ($c) use ($USER) {
                    $coursecontext = context_course::instance($c->id);
                    if (is_enrolled($coursecontext, $USER->id) && themeMooveCheckRoleUserInCourse($c->id, 'student')) {
                        if (getCourseProgressPercentage($c, $USER->id) == 100)
                            return $c;
                    }
                    return '';
                });
            }


            $listSubCourseIn = [];
            foreach ($courses as $c) {
                if (!in_array($c->category, $listSubCourseIn)) {
                    $listSubCourseIn[] = $c->category;
                }

                $rating = [];
                if (\core_component::get_plugin_directory("tool", "courserating")) {
                    $summary = $DB->get_record("tool_courserating_summary",
                        array(
                            "courseid"   => $c->id,
                            "ratingmode" => \tool_courserating\helper::get_course_rating_mode($c->id)
                        )
                    );

                    $rating["value"] = (float) $summary->avgrating;
                    $rating["total"] = (int) $summary->cntall;
                }
                $subcate = $DB->get_record("course_categories", array('id' => $c->category));
                $records = getListUsersByRole($c, 'editingteacher');
                $teachers = array();

                foreach ($records as $record) {
                    $teachers[] = array(
                        "id"           => $record->id,
                        "name"         => fullname($record),
                        "image"        => $record->avatar,
                        "academicRank" => $record->academicrank
                    );
                }
                $coursecontext = context_course::instance($c->id);
                $listCourses[] = [
                    'id'          => $c->id,
                    'name'        => $c->fullname,
                    'subcatename' => $subcate->name,
                    'url'         => \course_get_url($c->id)->out(false),
                    'courseimage' => getThumbnailCourse($c),
                    'ratingvalue' => $rating["value"],
                    'ratingtotal' => $rating["total"],
                    'progress'    => getCourseProgressPercentage($c, $USER->id) ? round(getCourseProgressPercentage($c, $USER->id), 2) : 0,
                    'time'        => getCustomField('context_course', $c->id, 'time')['time'],
                    'teacher'     => $teachers,
                    'enrolled'    => is_enrolled($coursecontext, $USER->id)
                ];
            }
        }

        return array(
            "success"          => true,
            "reason"           => null,
            "message"          => "List categories and list sub category and number course",
            "data"             => $listCourses,
            "totalsubcategory" => count($listSubCourseIn),
            "totalcourse"      => count($listCourses),
            "subcategories"    => $listSubCate
        );
    }

    public static function get_list_courses_by_filter_returns() {
        return new external_single_structure(
            array(
                "success"          => new external_value(PARAM_BOOL, "Operation response"),
                "reason"           => new external_value(PARAM_TEXT, "Failure reason"),
                "message"          => new external_value(PARAM_TEXT, "Message"),
                "data"             => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            "id"          => new external_value(PARAM_INT),
                            "name"        => new external_value(PARAM_TEXT),
                            "subcatename" => new external_value(PARAM_TEXT),
                            "url"         => new external_value(PARAM_URL),
                            "courseimage" => new external_value(PARAM_TEXT),
                            "progress"    => new external_value(PARAM_FLOAT, "Progress"),
                            "time"        => new external_value(PARAM_TEXT, "Time"),
                            "ratingvalue" => new external_value(PARAM_FLOAT),
                            "ratingtotal" => new external_value(PARAM_INT),
                            'teacher'     => new external_multiple_structure(
                                new external_single_structure(
                                    array(
                                        "id"           => new external_value(PARAM_INT),
                                        "name"         => new external_value(PARAM_TEXT),
                                        "image"        => new external_value(PARAM_TEXT),
                                        "academicRank" => new external_value(PARAM_TEXT),
                                    )
                                )
                            ),
                            "enrolled"    => new external_value(PARAM_BOOL),
                        )
                    ),
                    "Data",
                    VALUE_OPTIONAL
                ),
                "totalsubcategory" => new external_value(PARAM_INT, "Failure reason"),
                "totalcourse"      => new external_value(PARAM_INT, "Failure reason"),
                "subcategories"    => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            "id"   => new external_value(PARAM_INT),
                            "name" => new external_value(PARAM_TEXT),
                        )
                    )
                ),
            )
        );
    }

    public static function user_update_password_parameters() {
        return new external_function_parameters(array(
            "oldPassword" => new external_value(PARAM_TEXT, "Old Password"),
            "newPassword" => new external_value(PARAM_TEXT, "New Password"),
        ));
    }

    public static function user_update_password_returns() {
        return new external_single_structure(
            array(
                "success" => new external_value(PARAM_BOOL, "Operation response"),
                "reason"  => new external_value(PARAM_TEXT, "Failure reason"),
                "message" => new external_value(PARAM_TEXT, "Message")
            )
        );
    }

    public static function user_update_password(string $oldPassword, string $newPassword) {
        global $DB, $USER;

        $params = self::validate_parameters(
            self::user_update_password_parameters(),
            array(
                "oldPassword" => $oldPassword,
                "newPassword" => $newPassword
            )
        );

        extract($params);
        $auth = get_auth_plugin($USER->auth);

        // Verify password
        if (!$auth->user_login($USER->username, $oldPassword)) {
            return array(
                "success" => false,
                "reason"  => "wrongpassword",
                "message" => "Old password is not valid."
            );
        }

        $auth->user_update_password($USER, $newPassword);

        return array(
            "success" => true,
            "reason"  => null,
            "message" => "Password changed."
        );
    }

    public static function get_list_certificate_by_filter_parameters() {
        return new external_function_parameters(array(
            'cateid'     => new external_value(PARAM_INT, 'Category Id'),
            'filterid'   => new external_value(PARAM_INT, 'Filter Id'),
            'coursename' => new external_value(PARAM_RAW, 'Course Name', VALUE_OPTIONAL)
        ));
    }

    public static function get_list_certificate_by_filter($cateid, $filterid, $coursename) {
        global $DB, $USER;
        $params = self::validate_parameters(
            self::get_list_certificate_by_filter_parameters(),
            array(
                "cateid"     => $cateid,
                "filterid"   => $filterid,
                "coursename" => $coursename,
            )
        );
        extract($params);
        $filter = $filterid;
        date_default_timezone_set('Asia/Ho_Chi_Minh');
        $certificates = [];
        if ($cateid != '') {
            $now = time();
            if ($cateid) {
                if ($coursename) {
                    $sql = 'SELECT * FROM {course} WHERE visible != 0 and (startdate < :time1 and ( enddate > :time2 or enddate =0 )) and fullname LIKE :fullname and category = :id order by sortorder ASC';
                    $courses = $DB->get_records_sql($sql, array('time1' => $now, 'time2' => $now, 'id' => $cateid, 'fullname' => '%' . $coursename . '%'));
                } else {
                    $sql = 'SELECT * FROM {course} WHERE visible != 0 and (startdate < :time1 and ( enddate > :time2 or enddate =0 )) and category = :id order by sortorder ASC';
                    $courses = $DB->get_records_sql($sql, array('time1' => $now, 'time2' => $now, 'id' => $cateid));
                }
            } elseif ($cateid == 0) {
                if ($coursename) {
                    $sql = 'SELECT * FROM {course} WHERE  visible != 0 and (startdate < :time1 and ( enddate > :time2 or enddate =0 )) and fullname LIKE :fullname and category IN (SELECT id FROM {course_categories})';
                    $courses = $DB->get_records_sql($sql, array('time1' => $now, 'time2' => $now, 'fullname' => '%' . $coursename . '%'));
                } else {
                    $sql = 'SELECT * FROM {course} WHERE visible != 0 and (startdate < :time1 and ( enddate > :time2 or enddate =0 )) and category IN (SELECT id FROM {course_categories})';
                    $courses = $DB->get_records_sql($sql, array('time1' => $now, 'time2' => $now));
                }
            } else {
                if ($coursename) {
                    $sql = 'SELECT * FROM {course} WHERE  visible != 0 and (startdate < :time1 and ( enddate > :time2 or enddate =0 )) and fullname LIKE :fullname and category IN (SELECT id FROM {course_categories} WHERE parent !=0) order by sortorder ASC';
                    $courses = $DB->get_records_sql($sql, array('time1' => $now, 'time2' => $now, 'fullname' => '%' . $coursename . '%'));
                } else {
                    $sql = 'SELECT * FROM {course} WHERE visible != 0 and (startdate < :time1 and ( enddate > :time2 or enddate =0 )) and category IN (SELECT id FROM {course_categories} WHERE parent !=0) order by sortorder ASC';
                    $courses = $DB->get_records_sql($sql, array('time1' => $now, 'time2' => $now));
                }
            }
            if ($filter == 1) {
                $courses = filterCourse($courses, 'latest');
            }
            if ($filter == 2) {
                $courses = filterCourse($courses, 'outstanding');
            }
            if ($filter == 3) {
                $courses = array_filter($courses, function ($c) use ($USER) {
                    $coursecontext = context_course::instance($c->id);
                    if (!is_enrolled($coursecontext, $USER->id)) {
                        return $c;
                    }
                    return '';
                });
            }
            if ($filter == 4) {
                $courses = array_filter($courses, function ($c) use ($USER) {
                    $coursecontext = context_course::instance($c->id);
                    if (is_enrolled($coursecontext, $USER->id) && themeMooveCheckRoleUserInCourse($c->id, 'student')) {
                        if (getCourseProgressPercentage($c, $USER->id) < 100)
                            return $c;
                    }
                    return '';
                });
            }
            if ($filter == 5) {
                $courses = array_filter($courses, function ($c) use ($USER) {
                    $coursecontext = context_course::instance($c->id);
                    if (is_enrolled($coursecontext, $USER->id) && themeMooveCheckRoleUserInCourse($c->id, 'student')) {
                        if (getCourseProgressPercentage($c, $USER->id) == 100)
                            return $c;
                    }
                    return '';
                });
            }
            $listSubCourseIn = [];
            foreach ($courses as $c) {
                $sc = \core_course_category::get($c->category);
                $progress = getCourseProgressPercentage($c, $USER->id);
                $c->scName = $sc->name;
                $coursecontext = context_course::instance($c->id);
                $moduleCertificateId = $DB->get_record('modules', array('name' => 'customcert'))->id;
                $countCourseActivitiesSql = "select * from {course_modules} where course = :course and module = :moduled ";
                $certificate = $DB->get_record_sql($countCourseActivitiesSql, array('course' => $c->id, 'moduled' => $moduleCertificateId));
                $customcert = $DB->get_record('customcert', array('course' => $c->id));
                if (is_enrolled($coursecontext, $USER->id) && $customcert && themeMooveCheckRoleUserInCourse($c->id, 'student') && $progress == 100) {
                    $template = $DB->get_record('customcert_templates', array('id' => $customcert->templateid));
                    if ($template) {
                        $page = $DB->get_record('customcert_pages', array('templateid' => $template->id));
                        $element = $DB->get_record('customcert_elements', array('pageid' => $page->id, 'element' => 'bgimage'));
                        if ($certificate) {
                            $template->viewurl = (new moodle_url("/mod/customcert/view.php?id=" . $certificate->id))->out(false);
                            $template->downloadurl = (new moodle_url("/mod/customcert/view.php?downloadown=1&id=" . $certificate->id))->out(false);
                        }
                        if ($element) {
                            // Get an instance of the element class.
                            if ($e = \mod_customcert\element_factory::get_element_instance($element)) {
                                $file = $e->get_file();
                                $element->gburl = \moodle_url::make_pluginfile_url($file->get_contextid(), 'mod_customcert', 'image', $file->get_itemid(),
                                    $file->get_filepath(), $file->get_filename())->out();
                            }
                        } else {
                            $element = new stdClass();
                            $element->gburl = getThumbnailCourse($c);
                            $element->gbclass = "cerfiticate-img-resize";
                        }
                        $certificates[] = [
                            "cid"           => $c->id,
                            "category"      => $c->category,
                            "sortorder"     => $c->sortorder,
                            "fullname"      => $c->fullname,
                            "shortname"     => $c->shortname,
                            "format"        => $c->format,
                            "ctimecreated"  => $c->id,
                            "ctimemodified" => $c->id,
                            "scName"        => $c->scName,
                            "templateid"    => $template->id,
                            "name"          => $template->name,
                            "contextid"     => $template->contextid,
                            "certimecreated"=> $template->timecreated,
                            "certimemodified"=> $template->timemodified,
                            "viewurl"       => $template->viewurl,
                            "downloadurl"   => $template->downloadurl,
                            "gburl"         => $element->gburl,
                            "gbclass"       => $element->gbclass,
                            "certificateid" => $certificate->id,
                        ];
                    }
                }
            }
        }

        return array(
            "success" => true,
            "reason"  => null,
            "message" => "List user certificate",
            "data"    => $certificates
        );
    }

    public static function get_list_certificate_by_filter_returns() {
        return new external_single_structure(
            array(
                "success" => new external_value(PARAM_BOOL, "Operation response"),
                "reason"  => new external_value(PARAM_TEXT, "Failure reason"),
                "message" => new external_value(PARAM_TEXT, "Message"),
                "data"    => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            "cid"             => new external_value(PARAM_INT),
                            "category"        => new external_value(PARAM_INT),
                            "sortorder"       => new external_value(PARAM_INT),
                            "fullname"        => new external_value(PARAM_TEXT),
                            "shortname"       => new external_value(PARAM_TEXT),
                            "format"          => new external_value(PARAM_TEXT),
                            "ctimecreated"    => new external_value(PARAM_INT),
                            "ctimemodified"   => new external_value(PARAM_INT),
                            "scName"          => new external_value(PARAM_TEXT),
                            "templateid"      => new external_value(PARAM_INT),
                            "name"            => new external_value(PARAM_TEXT),
                            "contextid"       => new external_value(PARAM_INT),
                            "certimecreated"  => new external_value(PARAM_INT),
                            "certimemodified" => new external_value(PARAM_INT),
                            "viewurl"         => new external_value(PARAM_URL),
                            "downloadurl"     => new external_value(PARAM_URL),
                            "gburl"           => new external_value(PARAM_URL),
                            "gbclass"         => new external_value(PARAM_TEXT),
                            "certificateid"   => new external_value(PARAM_INT),
                        )
                    ),
                    "Data",
                    VALUE_OPTIONAL
                )
            )
        );
    }

    public static function get_download_certificate_parameters() {
        return new external_function_parameters(array(
            'certificateid' => new external_value(PARAM_INT, 'Certificate Id'),
        ));
    }

    public static function get_download_certificate($certificateid) {
        global $CFG, $DB, $USER;
        $params = self::validate_parameters(
            self::get_download_certificate_parameters(),
            array(
                "certificateid" => $certificateid
            )
        );
        require_once($CFG->libdir . '/pdflib.php');
        require_once($CFG->dirroot . '/mod/customcert/lib.php');
        extract($params);
        $moduleCertificateId = $DB->get_record('modules', array('name' => 'customcert'))->id;
        $countCourseActivitiesSql = "select * from {course_modules} where id = :certificateid and module = :moduled ";
        $certificate = $DB->get_record_sql($countCourseActivitiesSql, array('certificateid' => $certificateid, 'moduled' => $moduleCertificateId));
        $cm = get_coursemodule_from_id('customcert', $certificate->id, 0, false);
        $customcert = $DB->get_record('customcert', array('id' => $cm->instance));
        $template = $DB->get_record('customcert_templates', array('id' => $customcert->templateid));
        // Get the pages for the template, there should always be at least one page for each template.
        if ($pages = $DB->get_records('customcert_pages', array('templateid' => $template->id), 'sequence ASC')) {
            // Create the pdf object.
            $pdf = new \pdf();

            $customcert = $DB->get_record('customcert', ['templateid' => $template->id]);

            // I want to have my digital diplomas without having to change my preferred language.
            $userlang = $USER->lang ?? current_language();

            // Check the $customcert exists as it is false when previewing from mod/customcert/manage_templates.php.
            if ($customcert) {
                $forcelang = mod_customcert_force_current_language($customcert->language);
                if (!empty($forcelang)) {
                    // This is a failsafe -- if an exception triggers during the template rendering, this should still execute.
                    // Preventing a user from getting trapped with the wrong language.
                    \core_shutdown_manager::register_function('force_current_language', [$userlang]);
                }
            }

            // If the template belongs to a certificate then we need to check what permissions we set for it.
            if (!empty($customcert->protection)) {
                $protection = explode(', ', $customcert->protection);
                $pdf->SetProtection($protection);
            }

            if (empty($customcert->deliveryoption)) {
                $deliveryoption = certificate::DELIVERY_OPTION_INLINE;
            } else {
                $deliveryoption = $customcert->deliveryoption;
            }

            // Remove full-stop at the end, if it exists, to avoid "..pdf" being created and being filtered by clean_filename.
            $filename = rtrim(format_string($template->name, true, ['context' => $template->contextid]), '.');

            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            $pdf->SetTitle($filename);
            $pdf->SetAutoPageBreak(true, 0);

            // This is the logic the TCPDF library uses when processing the name. This makes names
            // such as 'الشهادة' become empty, so set a default name in these cases.
            $filename = preg_replace('/[\s]+/', '_', $filename);
            $filename = preg_replace('/[^a-zA-Z0-9_\.-]/', '', $filename);

            if (empty($filename)) {
                $filename = get_string('certificate', 'customcert');
            }

            $filename = clean_filename($filename . '.pdf');
            // Loop through the pages and display their content.
            foreach ($pages as $page) {
                // Add the page to the PDF.
                if ($page->width > $page->height) {
                    $orientation = 'L';
                } else {
                    $orientation = 'P';
                }
                $pdf->AddPage($orientation, array($page->width, $page->height));
                $pdf->SetMargins($page->leftmargin, 0, $page->rightmargin);
                // Get the elements for the page.
                if ($elements = $DB->get_records('customcert_elements', array('pageid' => $page->id), 'sequence ASC')) {
                    // Loop through and display.
                    foreach ($elements as $element) {
                        // Get an instance of the element class.
                        if ($e = \mod_customcert\element_factory::get_element_instance($element)) {
                            $e->render($pdf, false, $USER);
                        }
                    }
                }
            }

            // Check the $customcert exists as it is false when previewing from mod/customcert/manage_templates.php.
            if ($customcert) {
                // We restore original language.
                if ($userlang != $customcert->language) {
                    mod_customcert_force_current_language($userlang);
                }
            }

            $pdf->Output($filename, $deliveryoption);
        }
        return array(
            "success" => true,
            "reason"  => null,
            "message" => "Download certificate as pdf"
        );
    }

    public static function get_download_certificate_returns() {
        return new external_single_structure(
            array(
                "success" => new external_value(PARAM_BOOL, "Operation response"),
                "reason"  => new external_value(PARAM_TEXT, "Failure reason"),
                "message" => new external_value(PARAM_TEXT, "Message"),
            )
        );
    }

    public static function update_rating_course_parameters() {
        return new external_function_parameters(array(
            'courseid' => new external_value(PARAM_INT, 'Course Id'),
            'rating'   => new external_value(PARAM_INT, 'Rating star'),
            'review'   => new external_value(PARAM_RAW, 'Review text', VALUE_OPTIONAL)
        ));
    }

    public static function update_rating_course($courseid, $rating, $review) {
        global $USER, $CFG;
        $params = self::validate_parameters(
            self::update_rating_course_parameters(),
            array(
                "courseid" => $courseid,
                "rating"   => $rating,
                "review"   => $review
            )
        );
        require_once($CFG->libdir . '/pdflib.php');
        extract($params);
        $ratingold = rating::get_record(['userid' => $USER->id, 'courseid' => $courseid]);
        if ($ratingold) {
            $oldrecord = $ratingold->to_record();
            $r = $ratingold;
            $r->set('rating', $rating);
            $r->set('review', $review);
            $r->save();
            $summary = summary::update_rating($courseid, $r, $oldrecord);
        } else {
            $r = new rating(0, (object) [
                'userid'   => $USER->id,
                'courseid' => $courseid,
                'rating'   => $rating,
            ]);
            $r->save();
            if ($review !== $r->get('review')) {
                $r->set('review', $review);
                $r->save();
            }
            $summary = summary::add_rating($courseid, $r);
        }

        if ($ratingold) {
            rating_updated::create_from_rating($r, $oldrecord)->trigger();
        } else {
            rating_created::create_from_rating($r)->trigger();
        }
        if ($ratingold) {
            rating_updated::create_from_rating($r, $oldrecord)->trigger();
        } else {
            rating_created::create_from_rating($r)->trigger();
        }
        $data = [

            [
                "id"           => $r->get('id'),
                "courseid"     => $r->get('courseid'),
                "userid"       => $r->get('userid'),
                "rating"       => $r->get('rating'),
                "review"       => $r->get('review'),
                "hasreview"    => $r->get('hasreview'),
                "timecreated"  => $r->get('timecreated'),
                "timemodified" => $r->get('timemodified'),
                "usermodified" => $r->get('usermodified'),
            ]
        ];
        return [
            "success" => true,
            "reason"  => null,
            "message" => "Update rating course",
            "data"    => $data,
        ];
    }

    public static function update_rating_course_returns() {
        return new external_single_structure(
            array(
                "success" => new external_value(PARAM_BOOL, "Operation response"),
                "reason"  => new external_value(PARAM_TEXT, "Failure reason"),
                "message" => new external_value(PARAM_TEXT, "Message"),
                "data"    => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            "id"           => new external_value(PARAM_INT),
                            "courseid"     => new external_value(PARAM_INT),
                            "userid"       => new external_value(PARAM_INT),
                            "rating"       => new external_value(PARAM_INT),
                            "review"       => new external_value(PARAM_TEXT),
                            "hasreview"    => new external_value(PARAM_INT),
                            "timecreated"  => new external_value(PARAM_INT),
                            "timemodified" => new external_value(PARAM_INT),
                            "usermodified" => new external_value(PARAM_INT),
                        )
                    ),
                    "Data",
                    VALUE_OPTIONAL
                )
            )
        );
    }

    public static function get_course_document_parameters() {
        return new external_function_parameters(array(
            'courseid' => new external_value(PARAM_INT, 'Course Id'),
        ));
    }

    public static function get_course_document($courseid) {
        $params = self::validate_parameters(
            self::get_course_document_parameters(),
            array(
                "courseid" => $courseid,
            )
        );
        extract($params);
        return array(
            "success" => true,
            "reason"  => null,
            "message" => "List documents of course",
            "data"=>themeMooveGetlistDocumentsCourse($courseid)
        );
    }

    public static function get_course_document_returns() {
        return new external_single_structure(
            array(
                "success" => new external_value(PARAM_BOOL, "Operation response"),
                "reason"  => new external_value(PARAM_TEXT, "Failure reason"),
                "message" => new external_value(PARAM_TEXT, "Message"),
                "data"    => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            "url"  => new external_value(PARAM_URL),
                            "name" => new external_value(PARAM_TEXT),
                            "externalurl"=> new external_value(PARAM_URL),
                        )
                    ),
                    "Data",
                    VALUE_OPTIONAL
                )
            )
        );
    }

    public static function get_rating_course_parameters() {
        return new external_function_parameters(array(
            'courseid'    => new external_value(PARAM_INT, 'Course Id'),
        ));
    }

    public static function get_rating_course($courseid) {
        global $USER, $DB;

        $params = self::validate_parameters(
            self::get_rating_course_parameters(),
            array(
                "courseid"  => $courseid
            )
        );
        extract($params);
        $rating = [];
        $sql = "select * from {tool_courserating_summary} tcs JOIN {tool_courserating_rating} tcr ON tcs.courseid = tcr.courseid where tcs.courseid = :courseid and tcr.userid = :userid ";
        if (\core_component::get_plugin_directory("tool", "courserating")) {
            $summary = $DB->get_record_sql($sql, array("courseid"=> $courseid, "userid"=> $USER->id));
            $rating = [
                ["id" => (int) $summary->id,
                "courseid" => (int) $summary->courseid,
                "avgrating" => (float) $summary->avgrating,
                "cntall" => (int) $summary->cntall,
                "sumrating" => (int) $summary->sumrating,
                "total" => (int) $summary->total,
                "cntreviews" => (int) $summary->cntreviews,
                "userid" => (int) $summary->userid,
                "rating" => (int) $summary->rating,
                "review" => (int) $summary->review,
                "timecreated" => (int) $summary->timecreated,
                "timemodified" => (int) $summary->timemodified,]
            ];
        }
        return [
            "success"          => true,
            "reason"           => null,
            "message"          => "Get rating course",
            "data"             => $rating,
        ];
    }

    public static function get_rating_course_returns() {
        return new external_single_structure(
            array(
                "success"          => new external_value(PARAM_BOOL, "Operation response"),
                "reason"           => new external_value(PARAM_TEXT, "Failure reason"),
                "message"          => new external_value(PARAM_TEXT, "Message"),
                "data"             => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            "id"          => new external_value(PARAM_INT),
                            "courseid"        => new external_value(PARAM_INT),
                            "avgrating"     => new external_value(PARAM_FLOAT),
                            "cntall"     => new external_value(PARAM_INT),
                            "userid"     => new external_value(PARAM_INT),
                            "total"     => new external_value(PARAM_INT),
                            "sumrating"     => new external_value(PARAM_INT),
                            "cntreviews"     => new external_value(PARAM_INT),
                            "rating"      => new external_value(PARAM_INT),
                            "review"      => new external_value(PARAM_RAW),
                            "timecreated"      => new external_value(PARAM_INT),
                            "timemodified"      => new external_value(PARAM_INT),
                        )
                    ),
                    "Data",
                    VALUE_OPTIONAL
                )
            )
        );
    }

    public static function get_course_quiz_info_parameters() {
        return new external_function_parameters(array(
            'courseid'    => new external_value(PARAM_INT, 'Course Id'),
        ));
    }

    public static function get_course_quiz_info($courseid) {
        global $USER, $DB;

        $params = self::validate_parameters(
            self::get_course_quiz_info_parameters(),
            array(
                "courseid"  => $courseid
            )
        );
        extract($params);
        $quiz = mod_quiz_external::get_quizzes_by_courses([$courseid]);
        return [
            "success"          => true,
            "reason"           => null,
            "message"          => "Get course quiz",
            "data"             => $quiz,
        ];
    }

    public static function get_course_quiz_info_returns() {
        return new external_single_structure(
            array(
                "success"          => new external_value(PARAM_BOOL, "Operation response"),
                "reason"           => new external_value(PARAM_TEXT, "Failure reason"),
                "message"          => new external_value(PARAM_TEXT, "Message"),
                "data"             =>
                    new external_single_structure(
                        [
                            'quizzes' => new external_multiple_structure(
                                new external_single_structure(array_merge(
                                    helper_for_get_mods_by_courses::standard_coursemodule_elements_returns(true),
                                    [
                                        'timeopen' => new external_value(PARAM_INT, 'The time when this quiz opens. (0 = no restriction.)',
                                            VALUE_OPTIONAL),
                                        'timeclose' => new external_value(PARAM_INT, 'The time when this quiz closes. (0 = no restriction.)',
                                            VALUE_OPTIONAL),
                                        'timelimit' => new external_value(PARAM_INT, 'The time limit for quiz attempts, in seconds.',
                                            VALUE_OPTIONAL),
                                        'overduehandling' => new external_value(PARAM_ALPHA, 'The method used to handle overdue attempts.
                                                                    \'autosubmit\', \'graceperiod\' or \'autoabandon\'.',
                                            VALUE_OPTIONAL),
                                        'graceperiod' => new external_value(PARAM_INT, 'The amount of time (in seconds) after the time limit
                                                                runs out during which attempts can still be submitted,
                                                                if overduehandling is set to allow it.', VALUE_OPTIONAL),
                                        'preferredbehaviour' => new external_value(PARAM_ALPHANUMEXT, 'The behaviour to ask questions to use.',
                                            VALUE_OPTIONAL),
                                        'canredoquestions' => new external_value(PARAM_INT, 'Allows students to redo any completed question
                                                                        within a quiz attempt.', VALUE_OPTIONAL),
                                        'attempts' => new external_value(PARAM_INT, 'The maximum number of attempts a student is allowed.',
                                            VALUE_OPTIONAL),
                                        'attemptonlast' => new external_value(PARAM_INT, 'Whether subsequent attempts start from the answer
                                                                    to the previous attempt (1) or start blank (0).',
                                            VALUE_OPTIONAL),
                                        'grademethod' => new external_value(PARAM_INT, 'One of the values QUIZ_GRADEHIGHEST, QUIZ_GRADEAVERAGE,
                                                                    QUIZ_ATTEMPTFIRST or QUIZ_ATTEMPTLAST.', VALUE_OPTIONAL),
                                        'decimalpoints' => new external_value(PARAM_INT, 'Number of decimal points to use when displaying
                                                                    grades.', VALUE_OPTIONAL),
                                        'questiondecimalpoints' => new external_value(PARAM_INT, 'Number of decimal points to use when
                                                                            displaying question grades.
                                                                            (-1 means use decimalpoints.)', VALUE_OPTIONAL),
                                        'reviewattempt' => new external_value(PARAM_INT, 'Whether users are allowed to review their quiz
                                                                    attempts at various times. This is a bit field, decoded by the
                                                                    \mod_quiz\question\display_options class. It is formed by ORing
                                                                    together the constants defined there.', VALUE_OPTIONAL),
                                        'reviewcorrectness' => new external_value(PARAM_INT, 'Whether users are allowed to review their quiz
                                                                        attempts at various times.
                                                                        A bit field, like reviewattempt.', VALUE_OPTIONAL),
                                        'reviewmarks' => new external_value(PARAM_INT, 'Whether users are allowed to review their quiz attempts
                                                                at various times. A bit field, like reviewattempt.',
                                            VALUE_OPTIONAL),
                                        'reviewspecificfeedback' => new external_value(PARAM_INT, 'Whether users are allowed to review their
                                                                            quiz attempts at various times. A bit field, like
                                                                            reviewattempt.', VALUE_OPTIONAL),
                                        'reviewgeneralfeedback' => new external_value(PARAM_INT, 'Whether users are allowed to review their
                                                                            quiz attempts at various times. A bit field, like
                                                                            reviewattempt.', VALUE_OPTIONAL),
                                        'reviewrightanswer' => new external_value(PARAM_INT, 'Whether users are allowed to review their quiz
                                                                        attempts at various times. A bit field, like
                                                                        reviewattempt.', VALUE_OPTIONAL),
                                        'reviewoverallfeedback' => new external_value(PARAM_INT, 'Whether users are allowed to review their quiz
                                                                            attempts at various times. A bit field, like
                                                                            reviewattempt.', VALUE_OPTIONAL),
                                        'questionsperpage' => new external_value(PARAM_INT, 'How often to insert a page break when editing
                                                                        the quiz, or when shuffling the question order.',
                                            VALUE_OPTIONAL),
                                        'navmethod' => new external_value(PARAM_ALPHA, 'Any constraints on how the user is allowed to navigate
                                                                around the quiz. Currently recognised values are
                                                                \'free\' and \'seq\'.', VALUE_OPTIONAL),
                                        'shuffleanswers' => new external_value(PARAM_INT, 'Whether the parts of the question should be shuffled,
                                                                    in those question types that support it.', VALUE_OPTIONAL),
                                        'sumgrades' => new external_value(PARAM_FLOAT, 'The total of all the question instance maxmarks.',
                                            VALUE_OPTIONAL),
                                        'grade' => new external_value(PARAM_FLOAT, 'The total that the quiz overall grade is scaled to be
                                                            out of.', VALUE_OPTIONAL),
                                        'timecreated' => new external_value(PARAM_INT, 'The time when the quiz was added to the course.',
                                            VALUE_OPTIONAL),
                                        'timemodified' => new external_value(PARAM_INT, 'Last modified time.',
                                            VALUE_OPTIONAL),
                                        'password' => new external_value(PARAM_RAW, 'A password that the student must enter before starting or
                                                                continuing a quiz attempt.', VALUE_OPTIONAL),
                                        'subnet' => new external_value(PARAM_RAW, 'Used to restrict the IP addresses from which this quiz can
                                                            be attempted. The format is as requried by the address_in_subnet
                                                            function.', VALUE_OPTIONAL),
                                        'browsersecurity' => new external_value(PARAM_ALPHANUMEXT, 'Restriciton on the browser the student must
                                                                    use. E.g. \'securewindow\'.', VALUE_OPTIONAL),
                                        'delay1' => new external_value(PARAM_INT, 'Delay that must be left between the first and second attempt,
                                                            in seconds.', VALUE_OPTIONAL),
                                        'delay2' => new external_value(PARAM_INT, 'Delay that must be left between the second and subsequent
                                                            attempt, in seconds.', VALUE_OPTIONAL),
                                        'showuserpicture' => new external_value(PARAM_INT, 'Option to show the user\'s picture during the
                                                                    attempt and on the review page.', VALUE_OPTIONAL),
                                        'showblocks' => new external_value(PARAM_INT, 'Whether blocks should be shown on the attempt.php and
                                                                review.php pages.', VALUE_OPTIONAL),
                                        'completionattemptsexhausted' => new external_value(PARAM_INT, 'Mark quiz complete when the student has
                                                                                exhausted the maximum number of attempts',
                                            VALUE_OPTIONAL),
                                        'completionpass' => new external_value(PARAM_INT, 'Whether to require passing grade', VALUE_OPTIONAL),
                                        'allowofflineattempts' => new external_value(PARAM_INT, 'Whether to allow the quiz to be attempted
                                                                            offline in the mobile app', VALUE_OPTIONAL),
                                        'autosaveperiod' => new external_value(PARAM_INT, 'Auto-save delay', VALUE_OPTIONAL),
                                        'hasfeedback' => new external_value(PARAM_INT, 'Whether the quiz has any non-blank feedback text',
                                            VALUE_OPTIONAL),
                                        'hasquestions' => new external_value(PARAM_INT, 'Whether the quiz has questions', VALUE_OPTIONAL),
                                    ]
                                ))
                            ),
                            'warnings' => new external_warnings(),
                        ]
                    )
            )
        );
    }

    public static function get_course_start_date_parameters(){
        return new external_function_parameters(array(
            'courseid'    => new external_value(PARAM_INT, 'Course Id'),
        ));
    }
    public static function get_course_start_date($courseid){
        global $USER, $DB;

        $params = self::validate_parameters(
            self::get_course_start_date_parameters(),
            array(
                "courseid"  => $courseid
            )
        );
        extract($params);
        $course = $DB->get_record('course', array('id'=>$courseid), '*', MUST_EXIST);
        return [
            "success"          => true,
            "reason"           => null,
            "message"          => "Get course start date",
            "data"             => $course->startdate,
        ];
    }
    public static function get_course_start_date_returns(){
        return new external_single_structure(
            array(
                "success"          => new external_value(PARAM_BOOL, "Operation response"),
                "reason"           => new external_value(PARAM_TEXT, "Failure reason"),
                "message"          => new external_value(PARAM_TEXT, "Message"),
                "data"             => new external_value(PARAM_TEXT, "Course start date"),
            )
        );
    }
}
