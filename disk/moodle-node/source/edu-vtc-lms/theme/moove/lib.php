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
 * Theme functions.
 *
 * @package    theme_moove
 * @copyright 2017 Willian Mano - http://conecti.me
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
global $CFG;

use core_completion\progress;
use core_course\external\course_summary_exporter;
use tool_courserating\constants;
use tool_courserating\external\summary_exporter;
use tool_courserating\helper;
use tool_courserating\local\models\rating;
use tool_courserating\local\models\summary;
use tool_courserating\permission;

require_once($CFG->dirroot . '/course/lib.php');


/** collecttion lượt truy cập
 * @throws dml_exception
 */
function collectionVisitor(){
    global $DB;

    $table    = 'count_visitor';
    $field    = 'count_visitor';
    $criteria = array();
    $record   = $DB->get_record($table, $criteria, '*', '');

    $expirationTime = 3600; // 1 hour in seconds
    if ( ! isset($_SESSION['visited']) || (time() - $_SESSION['visited_time'] > $expirationTime)) {
        $_SESSION['visited'] = true;
        $_SESSION['visited_time'] = time();

        if ($record) {
            $record->$field++;
            $DB->update_record($table, $record);
        } else {
            // The record doesn't exist; create it
            $newRecord = new stdClass();
            $newRecord->{$field} = 1;
            // Insert the new record into the database
            $newRecord->id = $DB->insert_record($table, $newRecord, true);
        }
    }
}

/**
 * @param int $courseId
 * @param string $roleName
 * @param $type
 * @return int|string
 * @throws dml_exception
 */
function getTotalStudent(int $courseId, string $roleName, $type = 'short') {
    global $DB;

    $role = $DB->get_record('role', array('shortname' => $roleName));
    $context = context_course::instance($courseId);
    $listUsers = get_role_users($role->id, $context, 0, "u.*");
    $listUsers = count($listUsers);
    if ($type == 'short') {
        if ($listUsers > 1000) {
            $listUsers = floor($listUsers / 1000) . "N";
        }
    }

    return $listUsers;
}

function pagination($total, $array, $page, $limit, $url) {
    $totalPages = 0;
    $totalPages = $total == 0 ? 0 : ($limit == 0 ? 1 : ceil($total / $limit));
    $array = array_slice($array, ($page - 1) * $limit, $limit);
    if (!$array) {
        $totalPages = 0;
    }
    $paging = createPaginationHTML($totalPages, $page, 1, $url);
    return [$array, $paging];

}

function createPaginationHTML($pages, $page, $step, $url) {
    $str = '<ul class="mt-1 pagination demo d-flex flex-row" style="gap:10px">';
    $active = '';
    $pageCutLow = $page - $step <= 0 ? 1 : $page - $step;
    $pageCutHigh = $page + $step >= $pages ? $pages : $page + $step;
    if ($pages == 0) return '';
    // Show the Previous button only if you are on a page other than the first
    if ($page > 1) {
        $str .= '<li class="page-item previours" data-page="' . ($page - 1) . '">' .
            '<a href="' . $url . '&page=' . ($page - 1) . '" class="page-link" ><i class="fa-solid fa-chevron-left" style="font-size: 16px"></i><span class="sr-only">Previous</span></a></li>';
    }
    // Show all the pagination elements if there are less than 6 pages total
    if ($pages < 6) {
        for ($p = 1; $p <= $pages; $p++) {
            $active = $page === $p ? "active" : "no";
            $str .= '<li class="page-item ' . $active . '" data-page="' . $p . '">' .
                '<a href="' . $url . '&page=' . $p . '" class="page-link" >' . $p . '</a></li>';
        }
    } else {
        // Show the very first page followed by a "..." at the beginning of the
        // pagination section (after the Previous button)
        if ($page > $step + 1) {
            $str .= '<li class="no page-item" data-page="1">' .
                '<a href="' . $url . '&page=1" class="page-link">1</a></li>';
            if ($page > $step + 2) {
                $str .= '<li class="out-of-range">' .
                    '<a class="page-link">...</a></li>';
            }
        }
        // Determine how many pages to show before the current page index
        if ($page === $pages) {
            $pageCutLow -= 2;
        } else if ($page === $pages - 1) {
            $pageCutLow -= 1;
        }
        // Output the indexes for pages that fall inside the range of pageCutLow
        // and pageCutHigh
        for ($p = $pageCutLow; $p <= $pageCutHigh; $p++) {
            if ($p === 0) {
                $p += 1;

            }
            if ($p > $pages) {
                continue;
            }
            $active = $page === $p ? "active" : "no";
            $str .= '<li class="page-item ' . $active . '" data-page="' . $p . '">' .
                '<a href="' . $url . '&page=' . $p . '" class="page-link">' . $p . '</a></li>';
        }
        // Show the very last page preceded by a "..." at the end of the pagination
        // section (before the Next button)
        if ($page < $pages - $step) {
            if ($page < $pages - $step - 1) {
                $str .= '<li class="out-of-range">' .
                    '<a href="' . $url . '" class="page-link">...</a></li>';
            }
            $str .= '<li class="page-item no" data-page="' . $pages . '">' .
                ' <a href="' . $url . '&page=' . $pages . '" class="page-link">' . $pages . '</a></li>';
        }
    }
    // Show the Next button only if you are on a page other than the last
    if ($page < $pages) {
        $str .= '<li class="page-item next no" data-page="' . ($page + 1) . '">' .
            '<a href="' . $url . '&page=' . ($page + 1) . '" class="page-link"><i class="fa-solid fa-chevron-right" style="font-size: 16px"></i><span class="sr-only">Next</span></a></li>';
    }
    $str .= '</ul>';
    return $str;
    // Return the pagination string to be outputted in the pug templates
}

function themeMooveGetListTrainingFacilities() {
    $themesettings = new \theme_moove\util\settings();
    $trainingfacilities = $themesettings->getListTrainingFacilities();
    $listTrainingFacilities = [];
    if (!$trainingfacilities['trainingfacilities']) {
        return $listTrainingFacilities;
    };
    $trainingfacilities = explode("\r\n", $trainingfacilities['trainingfacilities']);

    foreach ($trainingfacilities as $trainingfacility) {
        $value = explode('|', $trainingfacility);
        $listTrainingFacilities[] = [
            'name'   => trim($value[0]),
            'url'    => trim($value[1]),
            'choose' => '',
        ];
    }
    return $listTrainingFacilities;
}

function themeMooveGetListCategory() {
    global $DB;
    $listCategory = [];
    $url = parse_url($_SERVER['REQUEST_URI']);
    if (isset($url['query'])) {
        parse_str($url['query'], $q);
    }
    $listCategory[] = [
        'name'   => get_string('allcategory', 'theme_moove'),
        'url'    => new moodle_url('/course/index.php'),
        'choose' => !isset($q['categoryid']) && strpos($_SERVER['SCRIPT_NAME'], 'course') == 1 ? 'active' : '',
    ];
    $categories = $DB->get_records('course_categories', array('parent' => 0,'visible'=>1), 'sortorder ASC');

    foreach ($categories as $category) {
        if (isset($q['categoryid']) && $q['categoryid'] == $category->id) {
            $category->choose = 'active';
        } else {
            $category->choose = false;
        }
        $category->url = (new moodle_url('/course/index.php?categoryid=' . $category->id))->out(false);
        if (getCustomField("context_coursecat", $category->id, "showinmenunavbar")['showinmenunavbar']) {
            $listCategory[] = $category;
        }
    }
    return $listCategory;
}

function getQuizSettingGrade($courseid, $quizid, $userid) {
    $gradinginfo = grade_get_grades($courseid, 'mod', 'quiz', $quizid, $userid);
    $settingGrade = false;
    if (!empty($gradinginfo->items)) {
        $settingGrade = $gradinginfo->items[0];
    }
    return $settingGrade;
}

function getHistoryAttemptsQuiz($attemptobj) {
    global $USER;
    $attempts = quiz_get_user_attempts($attemptobj->get_quizid(), $USER->id, 'finished', true);
    $currentAttempt = $attemptobj->get_attempt();
    $courseid = $attemptobj->get_courseid();
    $quiz = $attemptobj->get_quiz();
    $settingGrade = getQuizSettingGrade($courseid, $quiz->id, $USER->id);
    $listAttempts = [];
    if ($attempts) {
        $index = count($attempts);
        foreach ($attempts as $attempt) {
            $timetaken = "-";
            if ($timetaken = ($attempt->timefinish - $attempt->timestart)) {
                if ($attempt->timefinish == 0) {
                    $timetaken = 0;
                } else {
                    if ($quiz->timelimit && $timetaken > ($quiz->timelimit + 60)) {
                        $overtime = $timetaken - $quiz->timelimit;
                        $timetaken = format_time($overtime);
                    } else {
                        $timetaken = format_time($timetaken);
                    }
                }

            }
            if ($currentAttempt->id == $attempt->id) {
                $checkIsCurrentAttempt = true;
            } else {
                $checkIsCurrentAttempt = false;
            }
            $examStatusPassed = false;
            if ($settingGrade != false && $settingGrade->gradepass <= $attempt->sumgrades) {
                $examStatusPassed = true;
            }
            $listAttempts[] = [
                'index'                 => $index--,
                'starttime'             => date('H:i,d/m/Y', $attempt->timestart),
                'grade'                 => (int) $attempt->sumgrades . ' / ' . (int) $quiz->sumgrades,
                'dotime'                => $timetaken,
                'checkIsCurrentAttempt' => $checkIsCurrentAttempt,
                'examStatusPassed'      => $examStatusPassed
            ];
        }
    }
    return $listAttempts;
}

/**
 * @throws coding_exception
 * @throws dml_exception
 */
function getListUsersByRole($course, string $roleName) {
    global $DB, $OUTPUT, $PAGE;
    $courseUtil = new \core_course_list_element($course);
    $listUsers = $courseUtil->get_course_contacts();
    $users = [];
    $index = 1;
    foreach ($listUsers as $value) {
        $user  = $DB->get_record('user',array('id' =>$value['user']->id));
        $user->index = $index - 1;
        if ($index == 1) {
            $user->active = 'active';
            $index++;
        }
        $userCustomfieldAcademicRank = $DB->get_record('user_info_field', array('shortname' => 'academicrank'));
        if ($userCustomfieldAcademicRank) {
            $academicRank = $DB->get_record('user_info_data', array('fieldid' => $userCustomfieldAcademicRank->id, 'userid' => $user->id));
            if ($academicRank) {
                $user->academicrank = $academicRank->data;
            }
        }
        $avatar = static function ($user) use ($PAGE) {
            $userpicture = new user_picture($user);
            $userpicture->size = 2;
            $useravatar = $userpicture->get_url($PAGE)->out(false);
            if (!strpos($useravatar, 'rev')) {
                $useravatar = (new \moodle_url('/theme/moove/public/no-image-user.png'))->out();
            }

            return $useravatar;
        };

        $user->avatar = $avatar($user);
        $users[] = $user;

    }

    return $users;
}

/**
 * @param int $courseId
 * @return mixed
 */
function getTotalActivitiesCourse(int $courseId) {
    global $DB;
    $activities = $DB->get_records('course_modules', array('course' => $courseId, 'deletioninprogress' => 0));
    return count($activities);

}

/**
 * @param stdClass $course
 * @param int $userId
 * @return float|int|null
 */
function getCourseProgressPercentage(stdClass $course, int $userId) {
    return progress::get_course_progress_percentage($course, $userId);
}

/**
 * @param $name
 * @param $url
 * @return string
 */
function makeHtmlBreadcrumb($arrayUrl) {
    $html = [];
    foreach ($arrayUrl as $url) {
        if (count($url) == 2) {
            $html[] = '<a class="text-decoration-none" href="' . $url['url'] . '">' . $url['name'] . '</a>';
        } elseif (count($url) >= 3) {
            $subString = [];
            foreach ($url['sub'] as $sub) {
                $subString[] = $sub['param'] . '=' . $sub['value'];
            }
            $subString = implode('&', $subString);
            $urlString = $url['url'] . '?' . $subString;
            $html[] = '<a class="text-decoration-none" href="' . $urlString . '">' . $url['name'] . '</a>';
        }
    }
    $html = implode('/', $html);
    return $html;
}

/**
 * @param $courseid
 * @return string
 */
function themeMooveCheckRoleUserInCourse($courseid, $rolename) {
    if (is_siteadmin()) {
        return false;
    }
    global $USER;
    $context = context_course::instance($courseid);
    $roles = get_user_roles($context, $USER->id, true);
    if ($roles) {
        foreach ($roles as $role) {
            if ($role->shortname == $rolename) {
                return true;
            }
        }
    }
    return false;

}

function themeMooveGetCourseCategoryPicture($categoryid) {
    global $DB, $CFG, $OUTPUT;
    $fs = get_file_storage();
    $context = context_coursecat::instance($categoryid);
    $files = $fs->get_area_files($context->id, 'course', 'category', 0, 'filename', false);
    if ($files) {
        $file = '';
        foreach ($files as $f) {
            $file = $f;
        }
        $url = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(), null, $file->get_filepath(), $file->get_filename(), false);
        $url = $url->out(false);
        return $url;
    }
    return $OUTPUT->get_generated_image_for_id($categoryid);
}

function themeMooveGetlistDocumentsCourse($courseid) {
    global $DB, $CFG, $OUTPUT;
    $fs = get_file_storage();
    $context = context_course::instance($courseid);
    $files = $fs->get_area_files($context->id, 'local_vtc', 'references', $courseid, 'filename', false);
    $listDocuments = array();
    if ($files) {
        $file = '';
        foreach ($files as $f) {
            $file = $f;
            $url = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $file->get_filename(), true);
            $url = $url->out(false);
            $externalUrl  = \moodle_url::make_webservice_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $file->get_filename(), true);
            $externalUrl = $externalUrl->out(false);
            $listDocuments[] = [
                'name' => $file->get_filename(),
                'url'  => $url,
                'externalurl'=>$externalUrl
            ];
        }
    }
    return $listDocuments;
}

function themeMooveTimeIntToString($time) {
    $string = '';
    if (!$time) {
        return $string;
    }
    $hour = floor($time / 60);
    $hour = $hour > 0 ? ($hour . ' ' . get_string('hour', 'theme_moove')) : '';
    $minute = $time % 60;
    $minute = $minute > 0 ? ($minute . ' ' . get_string('minute', 'theme_moove')) : '';

    if( $hour ) $string = $string.$hour;
    if( $hour && $minute ) $string = $string." ";
    if( $minute ) $string = $string.$minute;

    return $string;
}

function getListFormEnrol($courseId) {
    $enrols = enrol_get_plugins(true);
    $enrolinstances = enrol_get_instances($courseId, true);
    $forms = array();
    foreach ($enrolinstances as $instance) {
        if (!isset($enrols[$instance->enrol])) {
            continue;
        }
        $form = $enrols[$instance->enrol]->enrol_page_hook($instance);
        if ($form) {
            if ($instance->enrol == 'self') {
                $form = '<div class="custom-form-self-enrol">' . $form . '</div>';
                $form = str_replace('col-md-3', '', $form);
                $form = str_replace('col-md-9', '', $form);
            }
            $forms[] = ['header' => get_string('enrollselftcontent', 'theme_moove'), 'html' => $form];
        }
    }
    if (!$forms) {
        $forms[] = ['header' => get_string('enrollmanualcontent', 'theme_moove')];

    }
    return $forms;
}

/**
 * @param $c
 * @return false|string
 */
function getThumbnailCourse($c) {
    global $CFG,$OUTPUT;
    $courseimage = course_summary_exporter::get_course_image($c);

    if(!$courseimage){
        require_once( $CFG->libdir . '/filelib.php' );
        $url = '';
        $context = context_course::instance( $c->id );
        $fs = get_file_storage();
        $files = $fs->get_area_files( $context->id, 'course', 'overviewfiles', 0 );
        foreach ( $files as $f ) {
            if ( $f->is_valid_image() ) {
                $url = moodle_url::make_pluginfile_url( $f->get_contextid(), $f->get_component(), $f->get_filearea(), null, $f->get_filepath(), $f->get_filename(), false );
            }
        }
        return $url;
    }

    if (!$courseimage) {
        $courseimage = $OUTPUT->get_generated_image_for_id($c->id);
    }

    return $courseimage;
}

function vtcCustomFieldCategory($data, $contextid, $id) {
    global $DB;
    $cfCategory = $DB->get_record('customfield_category', array('area' => 'category'));
    if (!$cfCategory) {
        $cfCategory = new stdClass();
        $cfCategory->name = 'Other fields';
        $cfCategory->component = 'core_course';
        $cfCategory->area = 'category';
        $cfCategory->contextid = 1;
        $cfCategory->timecreated = time();
        $cfCategory->timemodified = time();
        $cfCategoryid = $DB->insert_record('customfield_category', $cfCategory);
        $cfCategory->id = $cfCategoryid;
    }
    $cf_field = $DB->get_record('customfield_field', array('categoryid' => $cfCategory->id, 'type' => 'checkbox', 'shortname' => 'showinmenunavbar'));
    if (!$cf_field) {
        $cf_field = new stdClass();
        $cf_field->shortname = 'showinmenunavbar';
        $cf_field->name = 'show in menun avbar';
        $cf_field->type = 'checkbox';
        $cf_field->descriptionformat = '1';
        $cf_field->sortorder = '0';
        $cf_field->categoryid = $cfCategory->id;
        $cf_field->timecreated = time();
        $cf_field->timemodified = time();
        $cf_field->configdata = '{"required":"0","uniquevalues":"0","checkbydefault":"0","locked":"0","visibility":"2"}';
        $cf_field_id = $DB->insert_record('customfield_field', $cf_field);
        $cf_field->id = $cf_field_id;
    }
    $cf_data = $DB->get_record('customfield_data', array('contextid' => $contextid, 'fieldid' => $cf_field->id));
    if (!$cf_data) {
        $cf_data = new stdClass();
        $cf_data->fieldid = $cf_field->id;
        $cf_data->instanceid = $id;
        $cf_data->intvalue = $data;
        $cf_data->value = $data;
        $cf_data->contextid = $contextid;
        $cf_data->valueformat = 0;
        $cf_data->timecreated = time();
        $cf_data->timemodified = time();
        $cf_data_id = $DB->insert_record('customfield_data', $cf_data);
        $cf_data->id = $cf_data_id;
    } else {
        $cf_data->intvalue = $data;
        $cf_data->value = $data;
        $DB->update_record('customfield_data', $cf_data);
    }
}

function vtcLoadListCity($data) {
    global $CFG;
    $MyArray = include($CFG->dirroot . '/theme/moove/lib/listcity.php');
    $listCity = [];
    foreach ($MyArray as $city) {
        if (isset($data['errors']['city']) && $city['city'] == $data['errors']['city']) {
            $city['choose'] = 'checked';
        }
        $listCity[] = $city;
    }
    return $listCity;
}

function getCustomField($context_type, $id, $shortname) {
    global $DB;
    $context = $context_type::instance($id);
    $customFieldData = $DB->get_records('customfield_data', array('contextid' => $context->id));
    $c = [];
    if ($shortname == 'label') {
        $c['label'] = false;
    } elseif ($shortname == 'time') {
        $c['time'] = 0;
        $c['time0'] = true;
    } elseif ($shortname == 'showhomepage') {
        $c['showhomepage'] = false;
    } elseif ($shortname == 'showinmenunavbar') {
        $c['showinmenunavbar'] = false;
    }
    $arrayLabel = [
        '0' => false,
        '1' => 'outstanding',
        '2' => 'latest'
    ];
    if ($customFieldData) {
        foreach ($customFieldData as $field) {
            $customFieldField = $DB->get_record('customfield_field', array('id' => $field->fieldid));
            if ($customFieldField->shortname == $shortname && $shortname == "label") {
                $c['label'] = $arrayLabel[$field->value];
                $c['labelvalue'] = $arrayLabel[$field->value] ? get_string($arrayLabel[$field->value], 'theme_moove') : '';
            } else if ($customFieldField->shortname == $shortname && $shortname == "time") {
                $c['time'] = themeMooveTimeIntToString($field->value);
                if ($field->value != null && $field->value != 0) {
                    $c['time0'] = false;
                }
            } else if ($customFieldField->shortname == $shortname && $shortname == "showhomepage") {
                $c['showhomepage'] = $field->value;
            } else if ($customFieldField->shortname == $shortname && $shortname == "showinmenunavbar") {
                $c['showinmenunavbar'] = $field->value;
            }

        }

    }
    return $c;
}


function getEnrollmentCourse($course) {
    $enrols = enrol_get_plugins(true);
    $enrolinstances = enrol_get_instances($course->id, true);

    $enrolName = 'manual';
    foreach ($enrolinstances as $instance) {
        if ($instance->enrol == 'self') {
            return 'self';
        }
    }
    return $enrolName;
}

function ratingCourse(int $courseid) {
    global $CFG, $USER, $OUTPUT;
    if (!permission::can_view_ratings($courseid)) {
        return '';
    }
    $summary = summary::get_for_course($courseid);
    $canrate = permission::can_add_rating($courseid);
    $data = (new summary_exporter(0, $summary, $canrate))->export($OUTPUT);
    $data->canrate = false;
    $data->hasrating = $canrate && rating::get_record(['userid' => $USER->id, 'courseid' => $courseid]);

    $branch = $CFG->branch ?? '';
    if ($parentcss = helper::get_setting(constants::SETTING_PARENTCSS)) {
        $data->parentelement = $parentcss;
    } else if ("{$branch}" === '311') {
        $data->parentelement = '#page-header .card-body, #page-header #course-header, #page-header';
    } else if ("{$branch}" >= '400') {
        $data->parentelement = '#page-header';
        $data->extraclasses = 'pb-2';
    }
    return $OUTPUT->render_from_template('tool_courserating/course_rating_block', $data);

}

function filterCourse($courses, $type) {
    global $DB;
    $arrayLabel = [
        '0' => '',
        '1' => 'outstanding',
        '2' => 'latest'
    ];
    $array = [];
    foreach ($courses as $c) {
        $context = context_course::instance($c->id);
        $customFieldData = $DB->get_records('customfield_data', array('contextid' => $context->id));
        foreach ($customFieldData as $field) {
            $customFieldField = $DB->get_record('customfield_field', array('id' => $field->fieldid));
            if ($customFieldField->shortname == 'label') {
                if ($arrayLabel[$field->value] == $type) {
                    $array[] = $c;
                }

            }
        }
    }
    return $array;
}

function VtcDefaultAddToUrl($url, $key, $value = null) {
    $query = parse_url($url, PHP_URL_QUERY);
    if ($query) {
        parse_str($query, $queryParams);
        $queryParams[$key] = $value;

        $url = str_replace("?$query", '?' . http_build_query($queryParams), $url);

    } else {
        $url .= '?' . urlencode($key) . '=' . urlencode($value);
    }
    return $url;
}

function appendUrlParam($params = null, $noparam = true, $defaultparam = '') {
    $url = parse_url($_SERVER['REQUEST_URI']);
    parse_str($url['query'], $q);
    unset($q['page']);
    if ($noparam && $defaultparam) {
        $q = array($defaultparam => $q[$defaultparam]);
    }
    if ($params != null) {
        foreach ($params as $k => $v) $q[$k] = $v;
    }
    $new_url = $url['path'] . '?' . http_build_query($q, '', '&');
    return $new_url;
}

function listSubCategories($subCategories, $sub = null, $paramDefault = 'sub', $defaultFirstParam = '') {
    $array = [];
    $array[] = array(
        'url'    => appendUrlParam(null, true, $defaultFirstParam),
        'choose' => $sub == null ? 'true' : 'false',
        'name'   => get_string('allsubcategory', 'theme_moove')
    );
    foreach ($subCategories as $subCategory) {
        $subCategory->url = appendUrlParam([$paramDefault => $subCategory->id]);
        $subCategory->choose = $subCategory->id == $sub ? 'true' : 'false';
        $array[] = $subCategory;
    }
    return $array;
}

function getUserCourses($subCate, $search, $filterCourseType) {
    global $DB, $USER;
    list($courses, $inactivesubcategory) = getCoursesBySCandS($subCate, $search);
    $coursesLearning = [];
    $coursesLearned = [];
    $countCourseLearning = 0;
    $coursesLearning = array_filter($courses, function ($c) use ($USER, $DB) {
        $coursecontext = context_course::instance($c->id);
        if (is_enrolled($coursecontext, $USER->id) && themeMooveCheckRoleUserInCourse($c->id, 'student')) {
            if (getCourseProgressPercentage($c, $USER->id) < 100) {
                $c->url = \course_get_url($c->id)->out(false);
                $c->courseimage = getThumbnailCourse($c);
                if (\core_component::get_plugin_directory('tool', 'courserating')) {
                    $c->rating = ratingCourse($c->id);
                }
                $progress = getCourseProgressPercentage($c, $USER->id);
                $customLabel = getCustomField('context_course', $c->id, 'label');
                $customTime = getCustomField('context_course', $c->id, 'time');
                $c->customfield = array_merge($customLabel, $customTime);
                $c->progress = $progress ? ceil($progress) : 0;
                $c->startenddate = date('d/m/Y', $c->startdate);
                if($c->enddate) $c->startenddate .= ' - ' . date('d/m/Y', $c->enddate);
                $lastAccessTimeRecord = $DB->get_record('user_lastaccess', array('courseid' => $c->id, 'userid' => $USER->id));
                $lastTimeHuman = date('H:i m/d/Y', $lastAccessTimeRecord->timeaccess);
                $c->lastTimeHuman = $lastTimeHuman;
                $c->totalActivity = $DB->count_records('course_modules', array('course' => $c->id));
                $category = $DB->get_record('course_categories', array('id' => $c->category));
                $c->categoryname = $category->name;
                return $c;
            }
        }
        return '';
    });
    $totalActivityCourseLearning = 0;
    foreach ($coursesLearning as $course) {
        $totalActivityCourseLearning += $course->totalActivity;
    }
    $coursesLearned = array_filter($courses, function ($c) use ($USER, $DB) {
        $coursecontext = context_course::instance($c->id);
        if (is_enrolled($coursecontext, $USER->id) && themeMooveCheckRoleUserInCourse($c->id, 'student')) {
            if (getCourseProgressPercentage($c, $USER->id) == 100) {
                $c->url = \course_get_url($c->id)->out(false);
                $c->courseimage = getThumbnailCourse($c);
                if (\core_component::get_plugin_directory('tool', 'courserating')) {
                    $c->rating = ratingCourse($c->id);
                }
                $progress = 100;
                $customLabel = getCustomField('context_course', $c->id, 'label');
                $customTime = getCustomField('context_course', $c->id, 'time');
                $c->customfield = array_merge($customLabel, $customTime);
                $c->progress = $progress;
                $c->startenddate = date('d/m/Y', $c->startdate);
                if($c->enddate) $c->startenddate .= ' - ' . date('d/m/Y', $c->enddate);
                $lastAccessTimeRecord = $DB->get_record('user_lastaccess', array('courseid' => $c->id, 'userid' => $USER->id));
                $lastTimeHuman = date('H:i m/d/Y', $lastAccessTimeRecord->timeaccess);
                $c->lastTimeHuman = $lastTimeHuman;
                $c->totalActivity = $DB->count_records('course_modules', array('course' => $c->id));
                $category = $DB->get_record('course_categories', array('id' => $c->category));
                $c->categoryname = $category->name;
                return $c;

            }
        }
        return '';
    });
    $totalActivityCourseLearned = 0;
    foreach ($coursesLearned as $course) {
        $totalActivityCourseLearned += $course->totalActivity;
    }
    $sql = 'SELECT * FROM {course} WHERE category IN (SELECT id FROM {course_categories} where parent !=0 and depth = 2) order by sortorder ASC';
    $interestCourse = $DB->get_records_sql($sql);
    $interestCourse = array_filter($interestCourse, function ($c) use ($DB, $USER) {
        $coursecontext = context_course::instance($c->id);
        if (!is_enrolled($coursecontext, $USER->id)) {
            $c->url = \course_get_url($c->id)->out(false);
            $c->courseimage = getThumbnailCourse($c);
            if (\core_component::get_plugin_directory('tool', 'courserating')) {
                $c->rating = ratingCourse($c->id);
            }
            $customLabel = getCustomField('context_course', $c->id, 'label');
            $customTime = getCustomField('context_course', $c->id, 'time');
            $c->customfield = array_merge($customLabel, $customTime);
            $c->startenddate = date('d/m/Y', $c->startdate);
            if($c->enddate) $c->startenddate .= ' - ' . date('d/m/Y', $c->enddate);
            $c->totalActivity = $DB->count_records('course_modules', array('course' => $c->id));
            $category = $DB->get_record('course_categories', array('id' => $c->category));
            $c->categoryname = $category->name;
            return $c;
        }
        return '';
    });
    $totalActivityInterestCourse = 0;
    foreach ($interestCourse as $course) {
        $totalActivityInterestCourse += $course->totalActivity;
    }
    $sql = 'select * from {course_categories} where parent !=0 and visible =1 and depth = 2 order by sortorder ASC';
    $subCategories = $DB->get_records_sql($sql);
    $paramurl = [];
    if (isset(parse_url($_SERVER['REQUEST_URI'])['query'])) {
        parse_str(parse_url($_SERVER['REQUEST_URI'])['query'], $params);
        foreach ($params as $key => $param) {
            if ($key != 'search' && $key != 'page') {
                $paramurl[] = [
                    'key'   => $key,
                    'value' => $param
                ];
            }

        }
    }
    $countSubCategory = 0;
    if ($filterCourseType == 1) {
        $allCourse = $coursesLearning;
        $listSub = [];
        foreach ($coursesLearning as $course) {
            if (!in_array($course->category, $listSub)) {
                $listSub[] = $course->category;
            }
        }
        $countSubCategory = count($listSub);
    } elseif ($filterCourseType == 2) {
        $allCourse = $coursesLearned;
        $listSub = [];
        foreach ($coursesLearned as $course) {
            if (!in_array($course->category, $listSub)) {
                $listSub[] = $course->category;
            }
        }
        $countSubCategory = count($listSub);

    } else {
        $allCourse = array_merge($coursesLearning, $coursesLearned);
        $listSubCategory = [];
        foreach ($allCourse as $course) {
            if (!in_array($course->category, $listSubCategory)) {
                $listSubCategory[] = $course->category;
            }
        }
        $countSubCategory = count($listSubCategory);

    }
    $countCourse = count($allCourse);
    if ($filterCourseType == 1) {
        $inactivecoursetype = get_string('inprogress_courses', 'theme_moove');
    } elseif ($filterCourseType == 2) {
        $inactivecoursetype = get_string('complete_courses', 'theme_moove');

    } else {
        $inactivecoursetype = get_string('allcourse', 'theme_moove');

    }

    return [
        'search'               => $search,
        'paramurl'             => $paramurl,
        'inactivesubcategory'  => $inactivesubcategory,
        'inactivecoursetype'   => $inactivecoursetype,
        'subcategories'        => listSubCategories($subCategories, $subCate, 'subcategoryid', 'tab'),
        'listfiltercoursetype' => listFilterInMyCourse($_SERVER['REQUEST_URI']),
        'allcourse'            => array_values($allCourse),
        'countcourse'          => $countCourse,
        'countsubcategory'     => $countSubCategory,
        'listCoursesLearning'  => [
            'course'                      => $coursesLearning,
            'countCourseLearning'         => count($coursesLearning),
            'totalActivityCourseLearning' => $totalActivityCourseLearning,
            'hiddenbuttonnext'            => count($coursesLearning) > 3 ? true : false,
            'html'                        => makeHtmlCarouselCourse($coursesLearning, get_string('learning', 'theme_moove'), 3),
            'html2'                       => makeHtmlCarouselCourse($coursesLearning, get_string('learning', 'theme_moove'), 2),
        ],
        'listCoursesLearned'   => [
            'course'                     => $coursesLearned,
            'countCourseLearned'         => count($coursesLearned),
            'totalActivityCourseLearned' => $totalActivityCourseLearned,
            'hiddenbuttonnext'           => count($coursesLearned) > 3 ? true : false,
            'html'                       => makeHtmlCarouselCourse($coursesLearned, get_string('complete', 'theme_moove'), 3),
            'html2'                      => makeHtmlCarouselCourse($coursesLearned, get_string('learning', 'theme_moove'), 2),

        ],
        'listInterestCourse'   => [
            'course'                      => $interestCourse,
            'countInterestCourse'         => count($interestCourse),
            'totalActivityInterestCourse' => $totalActivityInterestCourse,
            'hiddenbuttonnext'            => count($interestCourse) > 3 ? true : false,
            'html'                        => makeHtmlCarouselCourse($interestCourse, get_string('complete', 'theme_moove'), 3),
            'html2'                       => makeHtmlCarouselCourse($interestCourse, get_string('learning', 'theme_moove'), 2),

        ],
    ];
}

function makeHtmlCarouselCourse($course, $string, $number) {
    $listCourse = [];
    foreach ($course as $c) {
        $listCourse[] = (array) $c;
    }
    $html = '';
    $url = (new moodle_url('/course/view.php?id'))->out(false);
    $count = 0;
    for ($i = 0; $i < count($listCourse); $i += $number) {
        if ($count == 0) {
            $html .= ' <div class="carousel-item active">
                     <div class="d-flex" style="padding:10px;gap: 13px">';
            $count++;
        } else {
            $html .= ' <div class="carousel-item">
                     <div class="d-flex" style="padding:10px;gap: 13px">';
        }
        for ($y = 0; $y < $number; ++$y) {
            if (isset($listCourse[$i + $y])) {
                $html .= templateCourseCard($url, $listCourse[$i + $y], $string);
            }
        }
        $html .= '   </div>
                </div>';
    }
    return $html;
}

function templateCourseCard($url, $data, $string) {
    $checkLabel = '';
    $color = 'unset';
    if ($data['customfield']['labelvalue'] == '') {
        $checkLabel = 'hidden';
    } else {
        if ($data['customfield']['label'] == 'outstanding') {
            $color = '#FF0500';
        } elseif ($data['customfield']['label'] == 'latest') {
            $color = '#1890FF';
        }
    }
    $checkLastTimeHuman = false;
    if (isset($data['lastTimeHuman'])) {
        $checkLastTimeHuman = '<div>
                                    <span class="my-profile-notification-text" style="font-size: 10px; color: #6D6D6D">' . get_string('lasttimeaccess', 'theme_moove', $data['lastTimeHuman']) . '</span>
                                </div>
                                <div style="width: 100%" class="d-flex flex-column">
                                <div class="progress-container" style="width: 100%;height: 5px;background-color: #E2FBD7;border-radius: 10px;">
                                    <div class="progress-bar" style="height:100%;width: ' . ceil((int) $data['progress']) . '%;background-color: #34B53A;border-radius: 10px;"></div>
                                </div>
                                
                                <div class="d-flex justify-content-between mt-2">
                                    <div class="my-profile-notification-text" style="color:#000;font-size: 9.971px; text-align: left; font-weight: 600">' . $string . '</div>
                                    <div class="my-profile-notification-text" style="color: #34B53A;font-size: 9.971px;font-weight: 500; text-align: right;font-weight: 500;color: #34B53A">' . ceil((int) $data['progress']) . '%</div>
                                </div>
                            </div>';
    } else {
        $checkLastTimeHuman = '<div class="course-custom-footer d-flex flex-row justify-content-end">
                                    <a href="' . $url . '=' . $data['id'] . '">
                                        <button class="btn d-flex align-items-center justify-content-between" style="border-radius: 15px;background: rgba(2, 104, 187, 0.10);color: #0060AD;font-size: 10px;font-weight: 500;gap:10px;padding:4px 13px">
                                            <span>' . get_string('learnnow', 'theme_moove') . '</span>
                                            <i class="fa-solid fa-angle-right"></i>
                                        </button>
                                    </a>
                                </div>';
    }
    return ' <div class="card dashboard-card d-block" role="listitem" data-region="course-content" data-course-id="3" style="margin: 0;padding: 10px">
                <div class="card-img dashboard-card-img" style="background-image: url(' . $data['courseimage'] . '); overflow: hidden;border-radius: 10px;">
                    <div style="background-color: ' . $color . '" class="my-profile-course-featured' . $checkLabel . '">' . $data['customfield']['labelvalue'] . '</div>
                    <div style="background: rgba(2,104,187,0.71);border-radius: 15px; padding: 6px 10px;position: absolute;bottom: 6%;left: 4%;font-size: 10px;font-weight: 500;color: #fff;gap:5px" class="d-flex align-items-center" id="yui_3_18_1_1_1694021536093_27">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="14" viewBox="0 0 13 14" fill="none">
                            <path d="M6.63219 0.833984C10.1204 0.833984 12.948 3.66156 12.948 7.14977C12.948 10.638 10.1204 13.4656 6.63219 13.4656C3.14398 13.4656 0.316406 10.638 0.316406 7.14977C0.316406 3.66156 3.14398 0.833984 6.63219 0.833984ZM6.04482 4.35883C5.92791 4.35883 5.81578 4.40527 5.7331 4.48795C5.65043 4.57062 5.60398 4.68275 5.60398 4.79967V8.32451C5.60398 8.5683 5.80103 8.76535 6.04482 8.76535H9.56966C9.62892 8.7676 9.68802 8.75786 9.74342 8.73674C9.79883 8.71561 9.8494 8.68352 9.89211 8.64239C9.93483 8.60126 9.96881 8.55194 9.99202 8.49738C10.0152 8.44281 10.0272 8.38412 10.0272 8.32483C10.0272 8.26553 10.0152 8.20684 9.99202 8.15228C9.96881 8.09771 9.93483 8.04839 9.89211 8.00726C9.8494 7.96613 9.79883 7.93404 9.74342 7.91292C9.68802 7.89179 9.62892 7.88206 9.56966 7.8843H6.48503V4.79967C6.48503 4.68286 6.43867 4.57082 6.35614 4.48817C6.2736 4.40551 6.16163 4.35899 6.04482 4.35883Z" fill="white"></path>
                        </svg>
                        ' . $data['customfield']['time'] . '
                    </div>
                </div>
                <div class="d-flex flex-column mt-2" style="gap: 5px">
                 <a href="' . $url . '=' . $data['id'] . '" tabindex="-1">
                    <div class="my-profile-notification-text multiline" style="font-size: 12px; font-weight: 700">' . $data['fullname'] . '</div>
                     </a>
                    <div class="course-rating-star">
                        ' . $data['rating'] . '
                    </div>
                    <div class="d-flex align-items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 11 11" fill="none">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M5.50016 10.9168C2.51016 10.9168 0.0834961 8.49558 0.0834961 5.50016C0.0834961 2.51016 2.51016 0.0834961 5.50016 0.0834961C8.49558 0.0834961 10.9168 2.51016 10.9168 5.50016C10.9168 8.49558 8.49558 10.9168 5.50016 10.9168ZM7.22808 7.50975C7.29308 7.54766 7.3635 7.56933 7.43933 7.56933C7.57475 7.56933 7.71016 7.49891 7.786 7.36891C7.89975 7.17933 7.84016 6.93016 7.64516 6.811L5.71683 5.66266V3.16016C5.71683 2.93266 5.53266 2.75391 5.31058 2.75391C5.0885 2.75391 4.90433 2.93266 4.90433 3.16016V5.89558C4.90433 6.03641 4.98016 6.16641 5.10475 6.24225L7.22808 7.50975Z" fill="#2147A8"></path>
                        </svg>
                        <span class="ml-2 my-profile-notification-text" style="font-size: 11px; color: #6D6D6D">' . $data['startenddate'] . '</span>
                    </div>
                    <div class="d-flex align-items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 13 13" fill="none">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M3.8998 1.2998C3.72741 1.2998 3.56208 1.36829 3.44019 1.49019C3.31829 1.61208 3.2498 1.77741 3.2498 1.9498V2.5998H2.5998C2.25502 2.5998 1.92436 2.73677 1.68057 2.98057C1.43677 3.22436 1.2998 3.55502 1.2998 3.8998V10.3998C1.2998 10.7446 1.43677 11.0752 1.68057 11.319C1.92436 11.5628 2.25502 11.6998 2.5998 11.6998H10.3998C10.7446 11.6998 11.0752 11.5628 11.319 11.319C11.5628 11.0752 11.6998 10.7446 11.6998 10.3998V3.8998C11.6998 3.55502 11.5628 3.22436 11.319 2.98057C11.0752 2.73677 10.7446 2.5998 10.3998 2.5998H9.7498V1.9498C9.7498 1.77741 9.68132 1.61208 9.55942 1.49019C9.43752 1.36829 9.2722 1.2998 9.0998 1.2998C8.92741 1.2998 8.76208 1.36829 8.64019 1.49019C8.51829 1.61208 8.4498 1.77741 8.4498 1.9498V2.5998H4.5498V1.9498C4.5498 1.77741 4.48132 1.61208 4.35942 1.49019C4.23753 1.36829 4.0722 1.2998 3.8998 1.2998ZM3.8998 4.5498C3.72741 4.5498 3.56208 4.61829 3.44019 4.74019C3.31829 4.86208 3.2498 5.02741 3.2498 5.1998C3.2498 5.3722 3.31829 5.53753 3.44019 5.65942C3.56208 5.78132 3.72741 5.8498 3.8998 5.8498H9.0998C9.2722 5.8498 9.43752 5.78132 9.55942 5.65942C9.68132 5.53753 9.7498 5.3722 9.7498 5.1998C9.7498 5.02741 9.68132 4.86208 9.55942 4.74019C9.43752 4.61829 9.2722 4.5498 9.0998 4.5498H3.8998Z" fill="#2147A8"></path>
                        </svg>
                        <span class="ml-2 my-profile-notification-text" style="font-size: 11px; color: #6D6D6D">' . $data['categoryname'] . '</span>
                    </div>
                    ' . $checkLastTimeHuman . '
                    
                    
                </div>
            </div>';
}

function makeHtmlCarouselCourseOutstanding($course, $number) {
    $listCourse = [];
    foreach ($course as $c) {
        $listCourse[] = (array) $c;
    }
    $html = '';
    $url = (new moodle_url('/course/view.php?id'))->out(false);
    $count = 0;
    for ($i = 0; $i < count($listCourse); $i += $number) {
        if ($count == 0) {
            $html .= ' <div class="carousel-item active">
                     <div class="d-flex" style="padding:10px;gap: 16px">';
            $count++;
        } else {
            $html .= ' <div class="carousel-item">
                     <div class="d-flex" style="padding:10px;gap: 16px">';
        }
        for ($y = 0; $y < $number; ++$y) {
            if (isset($listCourse[$i + $y])) {
                $html .= templateCourseCardOutstanding($url, $listCourse[$i + $y]);
            }
        }
        $html .= '   </div>
                </div>';
    }
    return $html;
}

function templateCourseCardOutstanding($url, $data) {
    $checkLabel = '';
    if ($data['customfield']['labelvalue'] == '') {
        $checkLabel = 'hidden';
    }
    $coursetimehtml = "";
    if($data['customfield']['time']){
        $coursetimehtml = '<div class="d-flex align-items-center card-course-outstanding" id="yui_3_18_1_1_1694021536093_27">
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="14" viewBox="0 0 13 14" fill="none">
                                <path d="M6.63219 0.833984C10.1204 0.833984 12.948 3.66156 12.948 7.14977C12.948 10.638 10.1204 13.4656 6.63219 13.4656C3.14398 13.4656 0.316406 10.638 0.316406 7.14977C0.316406 3.66156 3.14398 0.833984 6.63219 0.833984ZM6.04482 4.35883C5.92791 4.35883 5.81578 4.40527 5.7331 4.48795C5.65043 4.57062 5.60398 4.68275 5.60398 4.79967V8.32451C5.60398 8.5683 5.80103 8.76535 6.04482 8.76535H9.56966C9.62892 8.7676 9.68802 8.75786 9.74342 8.73674C9.79883 8.71561 9.8494 8.68352 9.89211 8.64239C9.93483 8.60126 9.96881 8.55194 9.99202 8.49738C10.0152 8.44281 10.0272 8.38412 10.0272 8.32483C10.0272 8.26553 10.0152 8.20684 9.99202 8.15228C9.96881 8.09771 9.93483 8.04839 9.89211 8.00726C9.8494 7.96613 9.79883 7.93404 9.74342 7.91292C9.68802 7.89179 9.62892 7.88206 9.56966 7.8843H6.48503V4.79967C6.48503 4.68286 6.43867 4.57082 6.35614 4.48817C6.2736 4.40551 6.16163 4.35899 6.04482 4.35883Z" fill="white"></path>
                            </svg>
                            ' . $data['customfield']['time'] . '
                            </div>';
    }
    return ' <div class="card dashboard-card d-block" role="listitem" data-region="course-content" data-course-id="3" style="margin: 0;padding: 10px;border-radius: 16px;box-shadow: 0 0 20px 0 rgba(0, 96, 173, 0.08);">
                <a href="' . $url . '=' . $data['id'] . '">
                    <div class="card-img dashboard-card-img" style="background-image: url(' . $data['courseimage'] . '); overflow: hidden;border-radius: 10px;">
                        <div class="card-course-outstanding-label my-profile-course-featured' . $checkLabel . '">' . $data['customfield']['labelvalue'] . '</div>
                        '.$coursetimehtml.'
                    </div>
                </a>
                <div class="d-flex flex-column mt-2" style="gap: 8px !important;">
                 <a href="' . $url . '=' . $data['id'] . '" tabindex="-1">
                    <div class="my-profile-notification-text multiline" style="font-size: 20px; font-weight: 700">' . $data['fullname'] . '</div>
                     </a>
                    <div class="course-rating-star">
                        ' . $data['rating'] . '
                    </div>
                    <div class="course-custom-summary mb-3" style="height: 97px;display: -webkit-box;-webkit-line-clamp: 4;-webkit-box-orient: vertical;overflow: hidden;font-weight: 400;font-size: 16px;color: #6D6D6D;">
                    ' . $data['summary'] . '
                    </div>
                    <div class="course-custom-footer d-flex flex-row justify-content-end" >
                        <a href="' . $url . '=' . $data['id'] . '">
                            <button class="btn d-flex align-items-center justify-content-between" style="    border-radius: 24px;background-color: rgba(2,104,187,.1);gap: 10px;padding: 4px 13px;color: #1890FF;font-size: 16px;font-weight: 500;">
                                <span style="color: #0060AD;font-size: 16px;font-weight: 500;">Học ngay</span>
                                <i class="fa-solid fa-angle-right"></i>
                            </button>
                        </a>
                    </div>
                </div>
            </div>';
}

function makeHtmlCarouselCategory($course, $number) {
    $listCourse = [];
    foreach ($course as $c) {
        $listCourse[] = (array) $c;
    }
    $html = '';
    $url = (new moodle_url('/course/index.php?categoryid'))->out(false);
    $count = 0;
    for ($i = 0; $i < count($listCourse); $i += $number) {
        if ($count == 0) {
            $html .= ' <div class="carousel-item active">
                     <div class="d-flex" style="padding:10px;gap: 24px">';
            $count++;
        } else {
            $html .= ' <div class="carousel-item">
                     <div class="d-flex" style="padding:10px;gap: 24px">';
        }
        for ($y = 0; $y < $number; ++$y) {
            if (isset($listCourse[$i + $y])) {
                $html .= templateCategoryCard($url, $listCourse[$i + $y]);
            }
        }
        $html .= '   </div>
                </div>';
    }
    return $html;
}

function templateCategoryCard($url, $data) {
    return ' <div class="card dashboard-card d-block" role="listitem" data-region="course-content" data-course-id="3" style="margin: 0;padding: 10px;border-radius: 16px;background: #FFF;box-shadow: 0 0 16.9542px 0 rgba(0, 96, 173, 0.25);}">
                <a href="' . $url . '=' . $data['id'] . '">
                <div class="card-img dashboard-card-img" style="background-image: url(' . $data['image'] . '); overflow: hidden;border-radius: 10px;">
                    <div style="background: #1890FF;" class="my-profile-course-featured">' . $data['countcourse'] . ' ' . get_string('course', 'theme_moove') . '</div>
                </div>
                </a>
                <div class="d-flex flex-column text-center" style="gap: 5px;margin:18px auto 15px auto">
                 <a href="' . $url . '=' . $data['id'] . '" tabindex="-1">
                    <span class="my-profile-notification-text multiline" style="color: #1890FF;font-size: 20px;font-weight: 700;">' . $data['name'] . '</span>
                 </a>


                </div>
            </div>';
}

function updateUserProfile($data) {
    global $DB, $USER;
    $error = [];
    if (!$data->fullname) {
        $error['fullname'] = get_string("fullname_required", "theme_moove");
    }

    if (!$data->phone1) {
        $error['phone1'] = get_string("phonerequired", "theme_moove");

    }
    if (!$data->email) {
        $error['email'] = get_string("email_required", "theme_moove");
    }

    if ($error) {
        $error['fullnametext'] = $data->fullname;
        $error['dateofbirthtext'] = $data->profile_field_user_dateofbirth;
        $error['gendertext'] = $data->profile_field_user_gender;
        $error['phone1text'] = $data->phone1;
        $error['emailtext'] = $data->email;
        $error['jobtext'] = $data->profile_field_user_job;
        $error['addresstext'] = $data->address;
        $error['statuserror'] = true;

        return $error;
    } else {
        $user = $DB->get_record('user', array('id' => $USER->id));
        $user->firstname = explode(' ', $data->fullname, 2)[0];
        $user->lastname = explode(' ', $data->fullname, 2)[1];
        $user->phone1 = $data->phone1;
        $user->email = $data->email;
        $user->address = $data->address;
        $DB->update_record('user', $user);

        $userCustomfieldDateofbirth = $DB->get_record('user_info_field', array('shortname' => 'dateofbirth'));

        if ($userCustomfieldDateofbirth) {
            $userDateofbirth = $DB->get_record('user_info_data', array('fieldid' => $userCustomfieldDateofbirth->id, 'userid' => $USER->id));
            if ($userDateofbirth) {
                $userDateofbirth->data = strtotime($data->profile_field_user_dateofbirth);
                $DB->update_record('user_info_data', $userDateofbirth);

            } else {
                $dateofbirth = new stdClass();
                $dateofbirth->fieldid = $userCustomfieldDateofbirth->id;
                $dateofbirth->userid = $USER->id;
                $dateofbirth->data = strtotime($data->profile_field_user_dateofbirth);
                $DB->insert_record('user_info_data', $dateofbirth);
            }
        }
        $userCustomfieldGender = $DB->get_record('user_info_field', array('shortname' => 'gender'));
        if ($userCustomfieldGender) {
            $userGender = $DB->get_record('user_info_data', array('fieldid' => $userCustomfieldGender->id, 'userid' => $USER->id));
            $data->profile_field_user_gender = $data->profile_field_user_gender ? $data->profile_field_user_gender : 0;
            if ($userGender) {
                $userGender->data = $data->profile_field_user_gender;
                $DB->update_record('user_info_data', $userGender);
            } else {
                $gender = new stdClass();
                $gender->fieldid = $userCustomfieldGender->id;
                $gender->userid = $USER->id;
                $gender->data = $data->profile_field_user_gender ? $data->profile_field_user_gender : 0;

                $DB->insert_record('user_info_data', $gender);
            }
        }
        $userCustomfieldJob = $DB->get_record('user_info_field', array('shortname' => 'job'));
        if ($userCustomfieldJob) {
            $userjob = $DB->get_record('user_info_data', array('fieldid' => $userCustomfieldJob->id, 'userid' => $USER->id));
            if ($userjob) {
                $userjob->data = $data->profile_field_user_job;
                $DB->update_record('user_info_data', $userjob);
            } else {
                $job = new stdClass();
                $job->fieldid = $userCustomfieldJob->id;
                $job->userid = $USER->id;
                $job->data = $data->profile_field_user_job;
                $DB->insert_record('user_info_data', $job);
            }
        }
    }
    return [
        'successupdateprofile' => true
    ];
}

function vtcIsPasswordValid($password) {
    // Biểu thức chính quy kiểm tra
    $pattern = '/^(?=.*[A-Z])(?=.*[!@#$%^&*()_+{}[\]:;<>,.?~\\-]).{8,20}$/';

    // Sử dụng preg_match để kiểm tra
    if (preg_match($pattern, $password)) {
        return true; // Mật khẩu hợp lệ
    } else {
        return false; // Mật khẩu không hợp lệ
    }
}

function updatePassword($data) {
    global $DB, $USER;
    $error = [];
    if (!$data->old_pass) {
        $error['oldpass'] = get_string("password_required", "theme_moove");
    }
    if (!$data->new_pass) {
        $error['newpass'] = get_string("new_password_required", "theme_moove");
    }
    if (!$data->confirm_new_pass) {
        $error['confirmnewpass'] = get_string("confirm_new_password_required", "theme_moove");
    }
    if ($data->old_pass && $data->new_pass && $data->confirm_new_pass) {

        if ($data->new_pass != $data->confirm_new_pass) {
            $error['confirmnewpass'] = get_string("new_password_wrong_confirm_new_password", "theme_moove");
        } else {
            if (!vtcIsPasswordValid($data->new_pass)) {
                $error['newpass'] = get_string("passwordregex", "theme_moove");
            }
            $authplugin = get_auth_plugin('manual');

            // On auth fail fall through to the next plugin.
            if (!$authplugin->user_login($USER->username, $data->old_pass)) {
                $error['oldpass'] = get_string("password_wrong", "theme_moove");
                $error['oldpasstext'] = $data->old_pass;
            }
        }

    }

    if ($error) {
        $error['oldpasstext'] = $data->old_pass;
        $error['newpasstext'] = $data->new_pass;
        $error['confirmnewpasstext'] = $data->confirm_new_pass;
        $error['statuserror'] = true;


        return $error;
    } else {
        $authplugin->user_update_password($USER, $data->new_pass);
    }
    return [
        'successupdatepassword' => true
    ];

}

function getProfileUser() {
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
        'username'        => $user->username,
        'userfullname'    => $user->firstname . ' ' . $user->lastname,
        'useremail'       => $user->email,
        'userphone1'      => $user->phone1,
        'usergender'      => $userGender,
        'useraddress'     => $user->address,
        'userdateofbirth' => $userDateofbirth ? date('Y-m-d', $userDateofbirth) : false,
        'userjob'         => $userjob,

    ];
}

function getNotification($requests) {
    global $DB, $USER;

    $useridto = isset($requests['useridto']) ? $requests['useridto'] : 0;
    $sort = isset($requests['sort']) ? $requests['sort'] : 'DESC';
    $limit = isset($requests['limit']) ? $requests['limit'] : 0;
    $offset = isset($requests['offset']) ? $requests['offset'] : 0;
    $sort = strtoupper($sort);
    if ($sort != 'DESC' && $sort != 'ASC') {
        throw new \moodle_exception('invalid parameter: sort: must be "DESC" or "ASC"');
    }

    if (empty($useridto)) {
        $useridto = $USER->id;
    }

    // Is notification enabled ?
    if ($useridto == $USER->id) {
        $disabled = $USER->emailstop;
    } else {
        $user = \core_user::get_user($useridto, "emailstop", MUST_EXIST);
        $disabled = $user->emailstop;
    }
    if ($disabled) {
        // Notifications are disabled.
        return array();
    }

    $sql = "SELECT n.id, n.useridfrom, n.useridto,
                       n.subject, n.fullmessage, n.fullmessageformat,
                       n.fullmessagehtml, n.smallmessage, n.contexturl,
                       n.contexturlname, n.timecreated, n.component,
                       n.eventtype, n.timeread, n.customdata
                  FROM {notifications} n
                 WHERE n.id IN (SELECT notificationid FROM {message_popup_notifications})
                   AND n.useridto = ?
              ORDER BY timecreated $sort, timeread $sort, id $sort";

    $records = $DB->get_recordset_sql($sql, [$useridto], $offset, $limit);
    $unRead = 0;
    $notifications = [];
    foreach ($records as $record) {
        // Check unread notifications
        $style = 'style="background-color: white"';
        if ($record->timeread == null) {
            $unRead++;
            $style = 'style="background-color: #e6f0f7"';
        }
        // Converse timestamp to text
        $timeDifference = time() - $record->timecreated;
        $formattedTime = '';
        if (time() - $record->timecreated < (24 * 60 * 60)) { // Check if time is less than 24 hours (in seconds)
            $hours = floor($timeDifference / 3600); // Convert seconds to hours
            $minutes = round(($timeDifference % 3600) / 60); // Convert remaining seconds to minutes
            if ($hours > 0) {
                $formattedTime = $hours . get_string('notihours', 'theme_moove');
                if ($minutes > 0) {
                    $formattedTime .= " " . $minutes . get_string('notiminutes', 'theme_moove');
                }
                $formattedTime .= get_string('notiago', 'theme_moove');
            } elseif ($minutes > 0) {
                $formattedTime .= $minutes . get_string('notiminutes', 'theme_moove') . get_string('notiago', 'theme_moove');
            } else {
                $formattedTime = "just now";
            }
        } else {
            $formattedTime = date("H:i d-m-Y", $record->timecreated);
        }
        $notifications[] = [
            'id'            => $record->id,
            'style'         => $style,
            'subject'       => $record->subject,
            'fullmessage'   => $record->smallmessage,
            'formattedTime' => $formattedTime,
        ];
    }

    return [$notifications, $unRead];
}

function getCertification($subCate, $search) {
    global $DB, $USER, $CFG;
    require_once($CFG->dirroot . '/course/lib.php');
    list($courses, $inactivesubcategory) = getCoursesBySCandS($subCate, $search);
    $certificates = [];
    foreach ($courses as $c) {
        $progress = getCourseProgressPercentage($c, $USER->id);
        $sc = \core_course_category::get($c->category);
        $c->scName = $sc->name;
        $c->viewUrl = (new moodle_url("/course/view.php?id=" . $c->id))->out(false);
        $certificate = getCertificateByCourse($c);
        $cm = get_coursemodule_from_id('customcert', $certificate->id, 0, false);
        $coursecontext = context_course::instance($c->id);
        $customcert = $DB->get_record('customcert', array('id' => $cm->instance));
        if (is_enrolled($coursecontext, $USER->id) && $customcert && themeMooveCheckRoleUserInCourse($c->id, 'student') && $progress == 100) {
            $template = $DB->get_record('customcert_templates', array('id' => $customcert->templateid));
            if ($template) {
                $page = $DB->get_record('customcert_pages', array('templateid' => $template->id));
                $element = $DB->get_record('customcert_elements', array('pageid' => $page->id, 'element' => 'bgimage'));
                if ($certificate) {
                    $template->viewurl = (new moodle_url("/mod/customcert/view.php?id=" . $certificate->id))->out(false);
                    $template->downloadurl = (new moodle_url("/mod/customcert/view.php?downloadown=1&id=" . $certificate->id))->out(false);
                }
                if ($element && $e = \mod_customcert\element_factory::get_element_instance($element)) {
                    // Get an instance of the element class.
                    $file = $e->get_file();
                    $element->gburl = \moodle_url::make_pluginfile_url($file->get_contextid(), 'mod_customcert', 'image', $file->get_itemid(),
                        $file->get_filepath(), $file->get_filename())->out();
                } else {
                    $element = new stdClass();
                    $element->gburl = getThumbnailCourse($c);
                    $element->gbclass = "cerfiticate-img-resize";
                }
                $certificates[] = [
                    "courseinfo" => $c,
                    "template"   => $template,
                    "elements"   => $element,
                ];
            }
        }
    }
    $sql = 'select * from {course_categories} where parent !=0 and visible =1 and depth = 2 order by sortorder ASC';
    $subCategories = $DB->get_records_sql($sql);
    $data = [
        'text'                => get_string("certificateincate", "theme_moove", count($certificates)) . get_string("insubcate", "theme_moove", count($subCategories)),
        'inactivesubcategory' => $inactivesubcategory,
        'search'              => $search,
        'data'                => $certificates,
        'subcategories'       => listSubCategories($subCategories, $subCate, 'subcategoryid', 'tab')
    ];
    return $data;
}

function getCertificateByCourse($course) {
    global $DB;

    $moduleCertificateId = $DB->get_record('modules', array('name' => 'customcert'))->id;
    $countCourseActivitiesSql = "select * from {course_modules} where course = :course and module = :moduled ";
    $certificate = $DB->get_record_sql($countCourseActivitiesSql, array('course' => $course->id, 'moduled' => $moduleCertificateId));
    return $certificate;
}

function getStudy($subCate, $search) {
    global $DB, $USER;
    list($courses, $inactivesubcategory) = getCoursesBySCandS($subCate, $search);
    $tempStudy = [];
    foreach ($courses as $c) {
        if (isloggedin()) {
            $progress = getCourseProgressPercentage($c, $USER->id);
            $coursecontext = context_course::instance($c->id);
            if (is_enrolled($coursecontext, $USER->id) && themeMooveCheckRoleUserInCourse($c->id, 'student')) {
                $enrolTime = $DB->get_field_sql('SELECT ra.timemodified FROM {role_assignments} ra
                      JOIN {context} co ON ra.contextid = co.id WHERE co.instanceid = ?
                       AND ra.userid = ?', array($c->id, $USER->id));
                // If enrol time = 0 , that mean user create course, we skip that
                if (!$enrolTime && $enrolTime == 0) {
                    continue;
                }
                $c->viewUrl = (new moodle_url("/course/view.php?id=" . $c->id))->out(false);
                $c->enrollDate = date('d-m-Y', $enrolTime);
                $c->enrollHour = date('H:i', $enrolTime);
                $c->url = \course_get_url($c->id)->out(false);
                $sc = \core_course_category::get($c->category);
                $c->scName = $sc->name;
                $certificate = getCertificateByCourse($c);
                if ($progress == 0) {
                    $c->buttonText = get_string("learnnow", "theme_moove");
                } else if ($progress == 100) {
                    $c->certificateUrl = (new moodle_url("/mod/customcert/view.php?id=" . $certificate->id))->out(false);
                    $c->buttonText = get_string("relearn", "theme_moove");
                } else {
                    $c->buttonText = get_string("continuetolearn", "theme_moove");
                }
                $progress = $progress ? ceil($progress) : 0;
                $radius = 20;
                $c->progress = [
                    'number'     => $progress,
                    'text'       => $progress . '%',
                    'dasharray'  => M_PI * 2 * $radius,
                    'dashoffset' => (M_PI * 2 * $radius) * (1 - $progress / 100),
                    'radius'     => $radius
                ];
                $tempStudy[$enrolTime] = $c;
            }
        }
    }
    krsort($tempStudy, SORT_NUMERIC);
    $study = [];
    foreach ($tempStudy as $time => $record) {
        $study[] = $record;
    }
    $sql = 'select * from {course_categories} where parent !=0 and visible =1 and depth = 2 order by sortorder ASC';
    $subCategories = $DB->get_records_sql($sql);
    $data = [
        'study'               => $study,
        'search'              => $search,
        'inactivesubcategory' => $inactivesubcategory,
        'subcategories'       => listSubCategories($subCategories, $subCate, 'subcategoryid', 'tab')
    ];
    return $data;
}

function getCoursesBySCandS($subCate, $search) {
    global $DB, $USER;
    date_default_timezone_set('Asia/Ho_Chi_Minh');
    $now = time();

    if ($subCate) {
        $subCategory = $DB->get_record("course_categories", array("id" => $subCate));
        if ($search) {
            $sql = 'SELECT * FROM {course} WHERE visible != 0 and (startdate < :time1 and ( enddate > :time2 or enddate =0 )) and fullname LIKE :fullname and category = :id order by sortorder ASC';
            $courses = $DB->get_records_sql($sql, array('time1' => $now, 'time2' => $now, 'id' => $subCate, 'fullname' => '%' . $search . '%'));
        } else {
            $sql = 'SELECT * FROM {course} WHERE visible != 0 and (startdate < :time1 and ( enddate > :time2 or enddate =0 )) and category = :id order by sortorder ASC';
            $courses = $DB->get_records_sql($sql, array('time1' => $now, 'time2' => $now, 'id' => $subCate));
        }
        $inactivesubcategory = $subCategory->name;
    } else {
        if ($search) {
            $sql = 'SELECT * FROM {course} WHERE  visible != 0 and (startdate < :time1 and ( enddate > :time2 or enddate =0 )) and fullname LIKE :fullname and category IN (SELECT id FROM {course_categories} WHERE parent !=0 and depth = 2) order by sortorder ASC';
            $courses = $DB->get_records_sql($sql, array('time1' => $now, 'time2' => $now, 'fullname' => '%' . $search . '%'));
        } else {
            $sql = 'SELECT * FROM {course} WHERE visible != 0 and (startdate < :time1 and ( enddate > :time2 or enddate =0 )) and category IN (SELECT id FROM {course_categories} WHERE parent !=0 and depth = 2) order by sortorder ASC';
            $courses = $DB->get_records_sql($sql, array('time1' => $now, 'time2' => $now));
        }
        $inactivesubcategory = get_string('allsubcategory', 'theme_moove');

    }
    return [$courses, $inactivesubcategory];
}

function vtcGetUserAvatar() {
    global $DB, $USER, $PAGE;
    $useravatar = false;
    if (isloggedin()) {
        $user = $DB->get_record('user', ['id' => $USER->id], '*', MUST_EXIST);
        $userpicture = new user_picture($user);
        $userpicture->size = 2;
        $useravatar = $userpicture->get_url($PAGE)->out(false);
        if (!strpos($useravatar, 'rev')) {
            $useravatar = (new \moodle_url('/theme/moove/public/no-image-user.png'))->out();
        }
    }
    return $useravatar;
}

function defaultTemplateContext() {
    global $CFG, $OUTPUT, $USER, $DB, $PAGE;
    $menunavbar = [
        [
            'name'   => get_string('home', 'theme_moove'),
            'url'    => (new moodle_url('/'))->out(false),
            'choose' => strpos($_SERVER['SCRIPT_NAME'], 'home') == 1 || $_SERVER['REQUEST_URI'] == '/' ? 'active' : '',
        ],
        [
            'name'   => get_string('introduction', 'theme_moove'),
            'url'    => (new moodle_url('/introduction.php'))->out(false),
            'choose' => strpos($_SERVER['SCRIPT_NAME'], 'introduction') == 1 ? 'active' : '',
        ],
        [
            'name'    => get_string('learn', 'theme_moove'),
            'url'     => '',
            'choose'  => strpos($_SERVER['SCRIPT_NAME'], 'course') == 1 ? 'active' : '',
            'submenu' => ['menu' => themeMooveGetListCategory()],
            'index'   => 2
        ],
        [
            'name'    => get_string('trainingfacilities', 'theme_moove'),
            'url'     => 'asdfasdfasdf',
            'choose'  => '',
            'submenu' => ['menu' => themeMooveGetListTrainingFacilities()],
            'index'   => 3
        ],
    ];
    $useravatar = vtcGetUserAvatar();
    $publicImage = (new moodle_url('/theme/moove/public'))->out(false);
    $coursecat = \core_course_category::user_top();
    $categorytocreate = \core_course_category::get_nearest_editable_subcategory($coursecat, ['create']);
    $userMenu = [
        [
            'url'    => $categorytocreate != null && $categorytocreate->id !=0 ? (new \moodle_url('/course/edit.php', ['category' => $categorytocreate->id]))->out() : (new moodle_url('/admin/search.php'))->out(),
            'pix'    => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-folder" viewBox="0 0 16 16"> <path d="M.54 3.87.5 3a2 2 0 0 1 2-2h3.672a2 2 0 0 1 1.414.586l.828.828A2 2 0 0 0 9.828 3h3.982a2 2 0 0 1 1.992 2.181l-.637 7A2 2 0 0 1 13.174 14H2.826a2 2 0 0 1-1.991-1.819l-.637-7a1.99 1.99 0 0 1 .342-1.31zM2.19 4a1 1 0 0 0-.996 1.09l.637 7a1 1 0 0 0 .995.91h10.348a1 1 0 0 0 .995-.91l.637-7A1 1 0 0 0 13.81 4H2.19zm4.69-1.707A1 1 0 0 0 6.172 2H2.5a1 1 0 0 0-1 .981l.006.139C1.72 3.042 1.95 3 2.19 3h5.396l-.707-.707z"/> </svg>',
            'name'   => $categorytocreate != null && $categorytocreate->id !=0 ? get_string('createcourse','theme_moove'): get_string('administrationsite'),
            'choose' => '',
        ],
        [
            'url'    => (new moodle_url('/user/profile.php?tab=profile'))->out(),
            'pix'    => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-person" viewBox="0 0 16 16"> <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0zm4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4zm-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10c-2.29 0-3.516.68-4.168 1.332-.678.678-.83 1.418-.832 1.664h10z"/> </svg>',
            'name'   => get_string('userprofile', 'theme_moove'),
            'choose' => '',
        ],
        [
            'url'    => (new moodle_url('/user/profile.php?tab=courses'))->out(),
            'pix'    => ' <svg xmlns="http://www.w3.org/2000/svg" width="19" height="16" viewBox="0 0 19 16" fill="none">
                          <path d="M17.083 0.5H12.083C11.5979 0.5 11.1194 0.612953 10.6855 0.829915C10.2515 1.04688 9.87409 1.36189 9.58301 1.75C9.29192 1.36189 8.91447 1.04688 8.48055 0.829915C8.04663 0.612953 7.56815 0.5 7.08301 0.5H2.08301C1.75149 0.5 1.43354 0.631696 1.19912 0.866116C0.964704 1.10054 0.833008 1.41848 0.833008 1.75V11.75C0.833008 12.0815 0.964704 12.3995 1.19912 12.6339C1.43354 12.8683 1.75149 13 2.08301 13H7.08301C7.58029 13 8.0572 13.1975 8.40883 13.5492C8.76046 13.9008 8.95801 14.3777 8.95801 14.875C8.95801 15.0408 9.02386 15.1997 9.14107 15.3169C9.25828 15.4342 9.41725 15.5 9.58301 15.5C9.74877 15.5 9.90774 15.4342 10.025 15.3169C10.1422 15.1997 10.208 15.0408 10.208 14.875C10.208 14.3777 10.4056 13.9008 10.7572 13.5492C11.1088 13.1975 11.5857 13 12.083 13H17.083C17.4145 13 17.7325 12.8683 17.9669 12.6339C18.2013 12.3995 18.333 12.0815 18.333 11.75V1.75C18.333 1.41848 18.2013 1.10054 17.9669 0.866116C17.7325 0.631696 17.4145 0.5 17.083 0.5ZM7.08301 11.75H2.08301V1.75H7.08301C7.58029 1.75 8.0572 1.94754 8.40883 2.29917C8.76046 2.65081 8.95801 3.12772 8.95801 3.625V12.375C8.41759 11.9683 7.75934 11.7489 7.08301 11.75ZM17.083 11.75H12.083C11.4067 11.7489 10.7484 11.9683 10.208 12.375V3.625C10.208 3.12772 10.4056 2.65081 10.7572 2.29917C11.1088 1.94754 11.5857 1.75 12.083 1.75H17.083V11.75ZM12.083 3.625H15.208C15.3738 3.625 15.5327 3.69085 15.65 3.80806C15.7672 3.92527 15.833 4.08424 15.833 4.25C15.833 4.41576 15.7672 4.57473 15.65 4.69194C15.5327 4.80915 15.3738 4.875 15.208 4.875H12.083C11.9172 4.875 11.7583 4.80915 11.6411 4.69194C11.5239 4.57473 11.458 4.41576 11.458 4.25C11.458 4.08424 11.5239 3.92527 11.6411 3.80806C11.7583 3.69085 11.9172 3.625 12.083 3.625ZM15.833 6.75C15.833 6.91576 15.7672 7.07473 15.65 7.19194C15.5327 7.30915 15.3738 7.375 15.208 7.375H12.083C11.9172 7.375 11.7583 7.30915 11.6411 7.19194C11.5239 7.07473 11.458 6.91576 11.458 6.75C11.458 6.58424 11.5239 6.42527 11.6411 6.30806C11.7583 6.19085 11.9172 6.125 12.083 6.125H15.208C15.3738 6.125 15.5327 6.19085 15.65 6.30806C15.7672 6.42527 15.833 6.58424 15.833 6.75ZM15.833 9.25C15.833 9.41576 15.7672 9.57473 15.65 9.69194C15.5327 9.80915 15.3738 9.875 15.208 9.875H12.083C11.9172 9.875 11.7583 9.80915 11.6411 9.69194C11.5239 9.57473 11.458 9.41576 11.458 9.25C11.458 9.08424 11.5239 8.92527 11.6411 8.80806C11.7583 8.69085 11.9172 8.625 12.083 8.625H15.208C15.3738 8.625 15.5327 8.69085 15.65 8.80806C15.7672 8.92527 15.833 9.08424 15.833 9.25Z" fill="black"/>
                        </svg>',
            'name'   => get_string('mycourse', 'theme_moove'),
            'choose' => '',

        ],
        [
            'url'    => (new moodle_url('/user/profile.php?tab=certificate'))->out(),
            'pix'    => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="19" viewBox="0 0 20 19" fill="none">
                          <mask id="mask0_2458_1686" style="mask-type:alpha" maskUnits="userSpaceOnUse" x="0" y="0" width="20" height="19">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M0 0.000488281H20V18.1665H0V0.000488281Z" fill="white"/>
                          </mask>
                          <g mask="url(#mask0_2458_1686)">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M1.5 11.9795V14.0245C1.5 14.7305 1.774 15.3935 2.273 15.8925C2.772 16.3915 3.436 16.6665 4.141 16.6665H15.857C17.313 16.6665 18.499 15.4825 18.5 14.0265V11.9785C17.214 11.6455 16.261 10.4745 16.26 9.08549C16.26 7.69649 17.213 6.52549 18.499 6.19149L18.5 4.14649C18.501 2.68849 17.318 1.50149 15.861 1.50049H4.144C2.687 1.50049 1.501 2.68549 1.5 4.14249V6.25949C1.986 6.37549 2.437 6.61449 2.812 6.96449C3.381 7.49549 3.709 8.21549 3.737 8.99349C3.74 10.4595 2.787 11.6435 1.5 11.9795ZM15.857 18.1665H4.142C3.035 18.1665 1.995 17.7365 1.212 16.9535C0.43 16.1705 0 15.1305 0 14.0245V11.3245C0 10.9105 0.336 10.5745 0.75 10.5745C1.574 10.5735 2.24 9.90549 2.239 9.08549C2.225 8.66749 2.065 8.31949 1.789 8.06149C1.514 7.80349 1.158 7.66249 0.776 7.68349C0.569 7.68549 0.375 7.61449 0.229 7.47349C0.083 7.33249 0 7.13649 0 6.93349V4.14349C0.001 1.85849 1.86 0.000488281 4.144 0.000488281H15.856C18.146 0.00148828 20.002 1.86349 20 4.14749V6.84649C20 7.26049 19.664 7.59649 19.25 7.59649C18.429 7.59649 17.76 8.26449 17.76 9.08449C17.761 9.90649 18.429 10.5745 19.25 10.5745C19.664 10.5745 20 10.9105 20 11.3245V14.0245C19.999 16.3085 18.14 18.1665 15.857 18.1665Z" fill="black"/>
                          </g>
                          <path fill-rule="evenodd" clip-rule="evenodd" d="M8.10383 8.38152L8.61283 8.87752C8.88883 9.14852 9.01383 9.53452 8.94783 9.91152L8.82783 10.6095L9.45683 10.2785C9.79583 10.0975 10.2028 10.0985 10.5448 10.2805L11.1698 10.6085L11.0498 9.90952C10.9868 9.52652 11.1128 9.14352 11.3858 8.87752L11.8948 8.38152L11.1898 8.27952C10.8118 8.22452 10.4838 7.98652 10.3148 7.64152L9.99983 7.00452L9.68483 7.64252C9.51583 7.98652 9.18783 8.22452 8.80783 8.27952L8.10383 8.38152ZM11.6178 12.3875C11.4318 12.3875 11.2458 12.3425 11.0738 12.2515L9.99983 11.6885L8.92483 12.2525C8.52883 12.4595 8.05783 12.4265 7.69783 12.1645C7.33683 11.9015 7.15983 11.4655 7.23483 11.0255L7.43983 9.82952L6.57083 8.98252C6.25183 8.67152 6.13783 8.21452 6.27483 7.79052C6.41283 7.36552 6.77283 7.06052 7.21383 6.99652L8.41783 6.82052L8.95483 5.73152C9.15183 5.33152 9.55183 5.08252 9.99983 5.08252C10.4458 5.08252 10.8468 5.33152 11.0438 5.73252L11.5818 6.82052L12.7838 6.99652C13.2258 7.06052 13.5868 7.36552 13.7238 7.79052C13.8608 8.21452 13.7478 8.67152 13.4278 8.98352L12.5578 9.83052L12.7638 11.0255C12.8388 11.4665 12.6608 11.9035 12.2988 12.1655C12.0948 12.3125 11.8568 12.3875 11.6178 12.3875Z" fill="black"/>
                        </svg>',
            'name'   => get_string('certificate', 'theme_moove'),
            'choose' => '',
        ],
        [
            'url'    => (new moodle_url('/user/profile.php?tab=study'))->out(),
            'pix'    => '<svg xmlns="http://www.w3.org/2000/svg" width="21" height="22" viewBox="0 0 21 22" fill="none">
                          <path fill-rule="evenodd" clip-rule="evenodd" d="M6.982 5.3035C6.95 5.3035 6.918 5.3075 6.886 5.3155C3.916 6.0465 1.5 9.2645 1.5 12.4885C1.5 16.5445 4.8 19.8445 8.857 19.8445C12.514 19.8445 15.57 17.2395 16.124 13.6495C16.128 13.6195 16.142 13.5325 16.06 13.4355C15.982 13.3445 15.858 13.2905 15.728 13.2905C14.317 13.2905 13.24 13.3225 12.399 13.3465C10.363 13.4075 9.521 13.4305 8.689 12.8135C7.435 11.8845 7.329 10.2885 7.329 5.5945C7.329 5.5105 7.293 5.4405 7.221 5.3845C7.154 5.3315 7.069 5.3035 6.982 5.3035ZM8.857 21.3445C3.973 21.3445 0 17.3715 0 12.4885C0 8.6165 2.928 4.7445 6.527 3.8585C7.089 3.7215 7.693 3.8495 8.147 4.2035C8.58 4.5435 8.829 5.0505 8.829 5.5945C8.829 9.9795 8.977 11.1595 9.582 11.6085C9.979 11.9015 10.523 11.8985 12.356 11.8475C13.208 11.8225 14.299 11.7905 15.728 11.7905C16.298 11.7905 16.835 12.0345 17.199 12.4595C17.537 12.8545 17.685 13.3705 17.607 13.8785C16.939 18.2035 13.259 21.3445 8.857 21.3445Z" fill="black"/>
                          <mask id="mask0_2458_1722" style="mask-type:alpha" maskUnits="userSpaceOnUse" x="10" y="0" width="11" height="11">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M10.4736 0.000488281H20.8926V10.2898H10.4736V0.000488281Z" fill="white"/>
                          </mask>
                          <g mask="url(#mask0_2458_1722)">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M12.0104 1.50174C11.9004 4.02074 12.0634 7.27474 12.1394 8.56374C12.1434 8.64074 12.1994 8.69674 12.2754 8.70074C13.3034 8.75974 16.8454 8.92374 19.3924 8.54874C19.3984 7.14474 18.4374 5.24074 16.9904 3.79474C15.5064 2.31274 13.7504 1.50174 12.0324 1.50174H12.0104ZM15.3144 10.2897C14.0044 10.2897 12.8514 10.2367 12.1884 10.1987C11.3544 10.1497 10.6904 9.48474 10.6424 8.65074C10.5644 7.32874 10.3954 3.96974 10.5154 1.37174C10.5484 0.616742 11.1584 0.0157416 11.9044 0.00274156C14.0414 -0.0592584 16.2464 0.932742 18.0504 2.73374C19.8084 4.49074 20.9244 6.79974 20.8924 8.61674C20.8804 9.32574 20.3654 9.91774 19.6694 10.0227C18.3114 10.2277 16.7214 10.2897 15.3144 10.2897Z" fill="black"/>
                          </g>
                        </svg>',
            'name'   => get_string('studyprocress', 'theme_moove'),
            'choose' => '',
        ],
        [
            'url'    => (new moodle_url('/user/profile.php?tab=changepassword'))->out(),
            'pix'    => '<svg xmlns="http://www.w3.org/2000/svg" width="17" height="21" viewBox="0 0 17 21" fill="none">
                          <path fill-rule="evenodd" clip-rule="evenodd" d="M12.9228 8.19977C12.5088 8.19977 12.1728 7.86377 12.1728 7.44977V5.30277C12.1728 3.20677 10.4678 1.50177 8.37176 1.50177H8.35576C7.34276 1.50177 6.39376 1.89177 5.67676 2.60277C4.95476 3.31677 4.55576 4.26977 4.55176 5.28577V7.44977C4.55176 7.86377 4.21576 8.19977 3.80176 8.19977C3.38776 8.19977 3.05176 7.86377 3.05176 7.44977V5.30277C3.05776 3.86277 3.61476 2.53377 4.61976 1.53777C5.62576 0.540767 6.95376 -0.0362332 8.37476 0.00176677C11.2948 0.00176677 13.6728 2.37977 13.6728 5.30277V7.44977C13.6728 7.86377 13.3368 8.19977 12.9228 8.19977Z" fill="black"/>
                          <path fill-rule="evenodd" clip-rule="evenodd" d="M4.542 8.12842C2.864 8.12842 1.5 9.49242 1.5 11.1704V15.4594C1.5 17.1374 2.864 18.5014 4.542 18.5014H12.183C13.86 18.5014 15.225 17.1374 15.225 15.4594V11.1704C15.225 9.49242 13.86 8.12842 12.183 8.12842H4.542ZM12.183 20.0014H4.542C2.037 20.0014 0 17.9644 0 15.4594V11.1704C0 8.66542 2.037 6.62842 4.542 6.62842H12.183C14.688 6.62842 16.725 8.66542 16.725 11.1704V15.4594C16.725 17.9644 14.688 20.0014 12.183 20.0014Z" fill="black"/>
                          <path fill-rule="evenodd" clip-rule="evenodd" d="M8.3623 15.1756C7.9483 15.1756 7.6123 14.8396 7.6123 14.4256V12.2046C7.6123 11.7906 7.9483 11.4546 8.3623 11.4546C8.7763 11.4546 9.1123 11.7906 9.1123 12.2046V14.4256C9.1123 14.8396 8.7763 15.1756 8.3623 15.1756Z" fill="black"/>
                        </svg>',
            'name'   => get_string('changepassword', 'theme_moove'),
            'choose' => '',
        ],
        [
            'url'    => (new moodle_url('/login/logout.php?sesskey=' . sesskey()))->out(),
            'pix'    => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
                          <path d="M4.16667 4.16667H9.16667C9.625 4.16667 10 3.79167 10 3.33333C10 2.875 9.625 2.5 9.16667 2.5H4.16667C3.25 2.5 2.5 3.25 2.5 4.16667V15.8333C2.5 16.75 3.25 17.5 4.16667 17.5H9.16667C9.625 17.5 10 17.125 10 16.6667C10 16.2083 9.625 15.8333 9.16667 15.8333H4.16667V4.16667Z" fill="black"/>
                          <path d="M17.2083 9.70812L14.8833 7.38312C14.8254 7.32358 14.751 7.28269 14.6696 7.26568C14.5883 7.24867 14.5037 7.25631 14.4268 7.28763C14.3498 7.31896 14.284 7.37253 14.2376 7.4415C14.1913 7.51047 14.1666 7.5917 14.1667 7.67479V9.16646H8.33333C7.875 9.16646 7.5 9.54146 7.5 9.99979C7.5 10.4581 7.875 10.8331 8.33333 10.8331H14.1667V12.3248C14.1667 12.6998 14.6167 12.8831 14.875 12.6165L17.2 10.2915C17.3667 10.1331 17.3667 9.86646 17.2083 9.70812Z" fill="black"/>
                        </svg>',
            'name'   => get_string('logout', 'theme_moove'),
            'choose' => '',
        ],
    ];

    if (!is_siteadmin() && !$categorytocreate) {
        unset($userMenu['0']);
    }
    $userMenu = array_values($userMenu);
    $languageen = false;
    $languagevi = false;
    if (current_language() == 'vi') {
        $languagevi = 'active';
    } else {
        $languageen = 'active';

    }
    return [
        'publicimage'         => $publicImage,
        //        'issiteadmin'         => is_siteadmin() ? (new moodle_url('/admin/search.php'))->out() : false,
        'isloginin'           => isloggedin(),
        'userinformation'     => isloggedin() ? ['name' => $USER->firstname . ' ' . $USER->lastname, 'email' => $USER->email] : false,
        'showlangmenu'        => empty($CFG->langmenu) ? false : true,
        'urlchangelanguagevi' => (new moodle_url(VtcDefaultAddToUrl($_SERVER['REQUEST_URI'], 'lang', 'vi')))->out(false),
        'urlchangelanguageen' => (new moodle_url(VtcDefaultAddToUrl($_SERVER['REQUEST_URI'], 'lang', 'en')))->out(false),
        'currentlanguage'     => current_language(),
        'languagevi'          => $languagevi,
        'languageen'          => $languageen,
        'menunavbar'          => $menunavbar,
        'useravatar'          => $useravatar,
        'loginurl'            => (new moodle_url('/login/index.php'))->out(false),
        'signupurl'           => (new moodle_url('/login/signup.php'))->out(false),
        'customlistusermenu'  => $userMenu,
    ];
}

function listFilterInMyCourse($url) {
    $url = parse_url($_SERVER['REQUEST_URI']);
    parse_str($url['query'], $q);

    $array = [];
    $array[] = array(
        'url'    => appendUrlParam([], true, 'tab'),
        'choose' => !isset($q['coursetype']) ? 'true' : 'false',
        'name'   => get_string('all', 'theme_moove')
    );
    $array[] = array(
        'url'    => isset($q['coursetype']) && $q['coursetype'] == 1 ? appendUrlParam() : appendUrlParam(['coursetype' => 1]),
        'choose' => isset($q['coursetype']) && $q['coursetype'] == 1 ? 'true' : 'false',
        'name'   => get_string('inprogress_courses', 'theme_moove')
    );
    $array[] = array(
        'url'    => isset($q['coursetype']) && $q['coursetype'] == 2 ? appendUrlParam() : appendUrlParam(['coursetype' => 2]),
        'choose' => isset($q['coursetype']) && $q['coursetype'] == 2 ? 'true' : 'false',
        'name'   => get_string('complete_courses', 'theme_moove')
    );

    return $array;
}


function listFilterInCourseCategory($url) {
    $url = parse_url($_SERVER['REQUEST_URI']);
    parse_str($url['query'], $q);

    $array = [];
    $array[] = array(
        'url'    => appendUrlParam(null, true, 'categoryid'),
        'choose' => !isset($q['filter']) ? 'true' : 'false',
        'name'   => get_string('all', 'theme_moove')
    );
    $array[] = array(
        'url'    => isset($q['filter']) && $q['filter'] == 1 ? appendUrlParam() : appendUrlParam(['filter' => 1]),
        'choose' => isset($q['filter']) && $q['filter'] == 1 ? 'true' : 'false',
        'name'   => get_string('latest', 'theme_moove')
    );
    $array[] = array(
        'url'    => isset($q['filter']) && $q['filter'] == 2 ? appendUrlParam() : appendUrlParam(['filter' => 2]),
        'choose' => isset($q['filter']) && $q['filter'] == 2 ? 'true' : 'false',
        'name'   => get_string('outstandingwithicon', 'theme_moove')
    );
    if (isloggedin()) {
        $array[] = array(
            'url'    => isset($q['filter']) && $q['filter'] == 3 ? appendUrlParam() : appendUrlParam(['filter' => 3]),
            'choose' => isset($q['filter']) && $q['filter'] == 3 ? 'true' : 'false',
            'name'   => get_string('notlearn', 'theme_moove')
        );
        $array[] = array(
            'url'    => isset($q['filter']) && $q['filter'] == 4 ? appendUrlParam() : appendUrlParam(['filter' => 4]),
            'choose' => isset($q['filter']) && $q['filter'] == 4 ? 'true' : 'false',
            'name'   => get_string('learning', 'theme_moove')
        );
        $array[] = array(
            'url'    => isset($q['filter']) && $q['filter'] == 5 ? appendUrlParam() : appendUrlParam(['filter' => 5]),
            'choose' => isset($q['filter']) && $q['filter'] == 5 ? 'true' : 'false',
            'name'   => get_string('learned', 'theme_moove')
        );
    }
    //    foreach ($subCategories as $subCategory) {
//        $subCategory->url = appendUrlParam(['sub' => $subCategory->id]);
//        $subCategory->choose = $subCategory->id == $sub ? 'true' : 'false';
//        $array[] = $subCategory;
//    }
    return $array;
}

/**
 * Returns the main SCSS content.
 *
 * @param theme_config $theme The theme config object.
 * @return string
 */
function theme_moove_get_main_scss_content($theme) {
    global $CFG;

    $scss = '';
    $filename = !empty($theme->settings->preset) ? $theme->settings->preset : null;
    $fs = get_file_storage();

    $context = \core\context\system::instance();
    if ($filename == 'default.scss') {
        $scss .= file_get_contents($CFG->dirroot . '/theme/boost/scss/preset/default.scss');
    } else if ($filename == 'plain.scss') {
        $scss .= file_get_contents($CFG->dirroot . '/theme/boost/scss/preset/plain.scss');
    } else if ($filename && ($presetfile = $fs->get_file($context->id, 'theme_moove', 'preset', 0, '/', $filename))) {
        $scss .= $presetfile->get_content();
    } else {
        // Safety fallback - maybe new installs etc.
        $scss .= file_get_contents($CFG->dirroot . '/theme/boost/scss/preset/default.scss');
    }

    // Moove scss.
    $moovevariables = file_get_contents($CFG->dirroot . '/theme/moove/scss/moove/_variables.scss');
    $moove = file_get_contents($CFG->dirroot . '/theme/moove/scss/default.scss');
    $security = file_get_contents($CFG->dirroot . '/theme/moove/scss/moove/_security.scss');

    // Combine them together.
    $allscss = $moovevariables . "\n" . $scss . "\n" . $moove . "\n" . $security;

    return $allscss;
}

/**
 * Inject additional SCSS.
 *
 * @param theme_config $theme The theme config object.
 * @return string
 */
function theme_moove_get_extra_scss($theme) {
    $content = '';

    // Sets the login background image.
    $loginbgimgurl = $theme->setting_file_url('loginbgimg', 'loginbgimg');
    if (empty($loginbgimgurl)) {
        $loginbgimgurl = new \moodle_url('/theme/moove/pix/loginbg.png');
        $loginbgimgurl->out();
    }

    $loginbgimgurl = (new \moodle_url('/theme/moove/public/bg-loginpage.png'))->out();

    $content .= 'body.pagelayout-login #page { ';
    $content .= "background-image: url('$loginbgimgurl'); background-size: cover;";
    $content .= ' }';

    // Always return the background image with the scss when we have it.
    return !empty($theme->settings->scss) ? $theme->settings->scss . ' ' . $content : $content;
}

/**
 * Get SCSS to prepend.
 *
 * @param theme_config $theme The theme config object.
 * @return string
 */
function theme_moove_get_pre_scss($theme) {
    $scss = '';
    $configurable = [
        // Config key => [variableName, ...].
        'brandcolor'         => ['brand-primary'],
        'secondarymenucolor' => 'secondary-menu-color',
        'fontsite'           => 'font-family-sans-serif'
    ];

    // Prepend variables first.
    foreach ($configurable as $configkey => $targets) {
        $value = isset($theme->settings->{$configkey}) ? $theme->settings->{$configkey} : null;

        if (empty($value)) {
            continue;
        }

        if ($configkey == 'fontsite' && $value == 'Moodle') {
            continue;
        }

        array_map(function ($target) use (&$scss, $value) {
            if ($target == 'fontsite') {
                $scss .= '$' . $target . ': "' . $value . '", sans-serif !default' . ";\n";
            } else {
                $scss .= '$' . $target . ': ' . $value . ";\n";
            }
        }, (array) $targets);
    }

    // Prepend pre-scss.
    if (!empty($theme->settings->scsspre)) {
        $scss .= $theme->settings->scsspre;
    }

    return $scss;
}

/**
 * Get compiled css.
 *
 * @return string compiled css
 */
function theme_moove_get_precompiled_css() {
    global $CFG;

    return file_get_contents($CFG->dirroot . '/theme/moove/style/moodle.css');
}

/**
 * Serves any files associated with the theme settings.
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return mixed
 */
function theme_moove_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {
    $theme = theme_config::load('moove');

    if ($context->contextlevel == CONTEXT_SYSTEM &&
        ($filearea === 'logo' || $filearea === 'loginbgimg' || $filearea == 'favicon')) {
        $theme = theme_config::load('moove');
        // By default, theme files must be cache-able by both browsers and proxies.
        if (!array_key_exists('cacheability', $options)) {
            $options['cacheability'] = 'public';
        }
        return $theme->setting_file_serve($filearea, $args, $forcedownload, $options);
    }

    if ($filearea === 'hvp') {
        return theme_moove_serve_hvp_css($args[1], $theme);
    }

    if ($context->contextlevel == CONTEXT_SYSTEM && preg_match("/^sliderimage[1-9][0-9]?$/", $filearea) !== false) {
        return $theme->setting_file_serve($filearea, $args, $forcedownload, $options);
    }

    if ($context->contextlevel == CONTEXT_SYSTEM && $filearea === 'marketing1icon') {
        return $theme->setting_file_serve('marketing1icon', $args, $forcedownload, $options);
    }

    if ($context->contextlevel == CONTEXT_SYSTEM && $filearea === 'marketing2icon') {
        return $theme->setting_file_serve('marketing2icon', $args, $forcedownload, $options);
    }

    if ($context->contextlevel == CONTEXT_SYSTEM && $filearea === 'marketing3icon') {
        return $theme->setting_file_serve('marketing3icon', $args, $forcedownload, $options);
    }

    if ($context->contextlevel == CONTEXT_SYSTEM && $filearea === 'marketing4icon') {
        return $theme->setting_file_serve('marketing4icon', $args, $forcedownload, $options);
    }

    send_file_not_found();
}

/**
 * Update key callback.
 *
 * @param $keyname
 *
 * @return void
 */
function theme_moove_update_license_key($keyname) {
    $license = new \theme_moove\util\license();

    $license->validate_license($_REQUEST[$keyname]);
}

/**
 * Serves the H5P Custom CSS.
 *
 * @param string $filename The filename.
 * @param theme_config $theme The theme config object.
 *
 * @throws dml_exception
 */
function theme_moove_serve_hvp_css($filename, $theme) {
    global $CFG, $PAGE;

    require_once($CFG->dirroot . '/lib/configonlylib.php'); // For min_enable_zlib_compression().

    $PAGE->set_context(\core\context\system::instance());
    $themename = $theme->name;

    $settings = new \theme_moove\util\settings();
    $content = $settings->hvpcss;

    $md5content = md5($content);
    $md5stored = get_config('theme_moove', 'hvpccssmd5');
    if ((empty($md5stored)) || ($md5stored != $md5content)) {
        // Content changed, so the last modified time needs to change.
        set_config('hvpccssmd5', $md5content, $themename);
        $lastmodified = time();
        set_config('hvpccsslm', $lastmodified, $themename);
    } else {
        $lastmodified = get_config($themename, 'hvpccsslm');
        if (empty($lastmodified)) {
            $lastmodified = time();
        }
    }

    // Sixty days only - the revision may get incremented quite often.
    $lifetime = 60 * 60 * 24 * 60;

    header('HTTP/1.1 200 OK');

    header('Etag: "' . $md5content . '"');
    header('Content-Disposition: inline; filename="' . $filename . '"');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $lastmodified) . ' GMT');
    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $lifetime) . ' GMT');
    header('Pragma: ');
    header('Cache-Control: public, max-age=' . $lifetime);
    header('Accept-Ranges: none');
    header('Content-Type: text/css; charset=utf-8');
    if (!min_enable_zlib_compression()) {
        header('Content-Length: ' . strlen($content));
    }

    echo $content;

    die;
}
