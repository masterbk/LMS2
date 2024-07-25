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
 * This file is used to call any registered externallib function in Moodle.
 *
 * It will process more than one request and return more than one response if required.
 * It is recommended to add webservice functions and re-use this script instead of
 * writing any new custom ajax scripts.
 *
 * @since Moodle 2.9
 * @package core
 * @copyright 2015 Damyon Wiese
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_course\external\course_summary_exporter;
use core_external\external_api;
use core_external\external_settings;

define('AJAX_SCRIPT', true);
// Services can declare 'readonlysession' in their config located in db/services.php, if not present will default to false.
define('READ_ONLY_SESSION', true);
if (!empty($_GET['nosessionupdate'])) {
    define('NO_SESSION_UPDATE', true);
}

require_once(__DIR__ . '/../../../../config.php');

define('PREFERRED_RENDERER_TARGET', RENDERER_TARGET_GENERAL);

try {
    $requests = $_REQUEST;
} catch (Exception $e) {
    $lasterror = json_last_error_msg();
    throw new coding_exception('Invalid json in request: ' . $lasterror);
}
$responses = array();
// Defines the external settings required for Ajax processing.
$settings = external_settings::get_instance();
$settings->set_file('pluginfile.php');
$settings->set_fileurl(true);
$settings->set_filter(true);
$settings->set_raw(false);

function get_enrolled_courses($requests) {
    global $CFG, $PAGE, $USER, $DB, $OUTPUT;
    require_once($CFG->dirroot . '/course/lib.php');
    $context = context_course::instance($USER->id);
    $PAGE->set_context($context);

    $limit = isset($requests['limit']) ? $requests['limit'] : 0;
    $sort = isset($requests['sort']) ? $requests['sort'] : null;
    $offset = isset($requests['offset']) ? $requests['offset'] : 0;
    $classification = isset($requests['classification']) ? $requests['classification'] : 'all';
    $findByname = isset($requests['find']) ? $requests['find'] : null;
    $sortBycategory = isset($requests['category']) ? $requests['category'] : null;
    $params = array(
        'classification' => $classification,
        'limit' => $limit,
        'offset' => $offset,
        'sort' => $sort,
    );

    $classification = $params['classification'];
    $limit = $params['limit'];
    $offset = $params['offset'];
    $sort = $params['sort'];

    switch($classification) {
        case COURSE_TIMELINE_ALLINCLUDINGHIDDEN:
            break;
        case COURSE_TIMELINE_ALL:
            break;
        case COURSE_TIMELINE_PAST:
            break;
        case COURSE_TIMELINE_INPROGRESS:
            break;
        case COURSE_TIMELINE_FUTURE:
            break;
        case COURSE_FAVOURITES:
            break;
        case COURSE_TIMELINE_HIDDEN:
            break;
        case COURSE_TIMELINE_SEARCH:
            break;
        case COURSE_CUSTOMFIELD:
            break;
        default:
            throw new invalid_parameter_exception('Invalid classification');
    }

    $requiredproperties = course_summary_exporter::define_properties();
    $fields = join(',', array_keys($requiredproperties));
    $hiddencourses = get_hidden_courses_on_timeline();
    $courses = [];

    // If the timeline requires really all courses, get really all courses.
    if ($classification == COURSE_TIMELINE_ALLINCLUDINGHIDDEN) {
        $courses = course_get_enrolled_courses_for_logged_in_user(0, $offset, $sort, $fields, COURSE_DB_QUERY_LIMIT);

        // Otherwise if the timeline requires the hidden courses then restrict the result to only $hiddencourses.
    } else if ($classification == COURSE_TIMELINE_HIDDEN) {
        $courses = course_get_enrolled_courses_for_logged_in_user(0, $offset, $sort, $fields,
            COURSE_DB_QUERY_LIMIT, $hiddencourses);

        // Otherwise get the requested courses and exclude the hidden courses.
    } else if ($classification == COURSE_TIMELINE_SEARCH) {
        // Prepare the search API options.
        $options = ['idonly' => true];
        $courses = course_get_enrolled_courses_for_logged_in_user_from_search(
            0,
            $offset,
            $sort,
            $fields,
            COURSE_DB_QUERY_LIMIT,
            $options
        );
    } else {
        $courses = course_get_enrolled_courses_for_logged_in_user(0, $offset, $sort, $fields,
            COURSE_DB_QUERY_LIMIT, [], $hiddencourses);
    }

    $favouritecourseids = [];
    $ufservice = \core_favourites\service_factory::get_service_for_user_context(\context_user::instance($USER->id));
    $favourites = $ufservice->find_favourites_by_type('core_course', 'courses');

    if ($favourites) {
        $favouritecourseids = array_map(
            function($favourite) {
                return $favourite->itemid;
            }, $favourites);
    }

    if ($classification == COURSE_FAVOURITES) {
        list($filteredcourses, $processedcount) = course_filter_courses_by_favourites(
            $courses,
            $favouritecourseids,
            $limit
        );
    }else {
        list($filteredcourses, $processedcount) = course_filter_courses_by_timeline_classification(
            $courses,
            $classification,
            $limit
        );
    }

    $renderer = $PAGE->get_renderer('core');
    $formattedcourses = array_map(function($course) use ($renderer, $favouritecourseids) {
        if ($course == null) {
            return;
        }
        context_helper::preload_from_record($course);
        $context = context_course::instance($course->id);
        $isfavourite = false;
        if (in_array($course->id, $favouritecourseids)) {
            $isfavourite = true;
        }
        $exporter = new course_summary_exporter($course, ['context' => $context, 'isfavourite' => $isfavourite]);
        return $exporter->export($renderer);
    }, $filteredcourses);

    $formattedcourses = array_filter($formattedcourses, function($course) {
        if ($course != null) {
            return $course;
        }
    });
    $inProgressCoursesHtml = $completeCoursesHtml = '<div class="carousel-item active"><div class="d-flex" style="gap: 16px">';
    $activetiesIPCount = $coursesIPCount = $activetiesCPCount = $coursesCPCount = $activetiesSCCount = $coursesSCCount = 0;
    $html = '';
    $listCategoryId = array();
    $ajaxUrl = (new moodle_url("/course/view.php")) -> out(false);
    $moduleCertificateId = $DB->get_record('modules', array('name' => 'customcert'))->id;
    $countCourseActivitiesSql = "select COUNT(*) from {course_modules} where course = :course and module != :moduled ";

    // Generate html for my_courses
    foreach($formattedcourses as $course) {
        // Filter by course name
        if($findByname && !str_contains($course->fullname,$findByname)) {
            continue;
        }
        // Filter by category
        $categoryID = $DB->get_record('course', array('id' => $course->id),'category')->category;
        $listCategoryId[] = $categoryID;
        if($sortBycategory && $sortBycategory !=$categoryID) {
            continue;
        }
        // Get time courses
        $courseCustomfieldTime = $DB->get_record('customfield_field',array('shortname'=> 'tool_coursetime'));
        $courseTime = $DB->get_record('customfield_data',array('fieldid'=> $courseCustomfieldTime->id, 'instanceid' => $course->id))->value;
        $timeIPHTMl = '';
        if($courseTime) {
            $timeIPHTMl = '<div style="background: rgba(2,104,187,0.71);border-radius: 15px;top: 100px;left: 7px; height: 30px;display: flex;align-items: center;flex-direction: row;" class="my-profile-course-featured">
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="14" viewBox="0 0 13 14" fill="none">
                              <path d="M6.63219 0.833984C10.1204 0.833984 12.948 3.66156 12.948 7.14977C12.948 10.638 10.1204 13.4656 6.63219 13.4656C3.14398 13.4656 0.316406 10.638 0.316406 7.14977C0.316406 3.66156 3.14398 0.833984 6.63219 0.833984ZM6.04482 4.35883C5.92791 4.35883 5.81578 4.40527 5.7331 4.48795C5.65043 4.57062 5.60398 4.68275 5.60398 4.79967V8.32451C5.60398 8.5683 5.80103 8.76535 6.04482 8.76535H9.56966C9.62892 8.7676 9.68802 8.75786 9.74342 8.73674C9.79883 8.71561 9.8494 8.68352 9.89211 8.64239C9.93483 8.60126 9.96881 8.55194 9.99202 8.49738C10.0152 8.44281 10.0272 8.38412 10.0272 8.32483C10.0272 8.26553 10.0152 8.20684 9.99202 8.15228C9.96881 8.09771 9.93483 8.04839 9.89211 8.00726C9.8494 7.96613 9.79883 7.93404 9.74342 7.91292C9.68802 7.89179 9.62892 7.88206 9.56966 7.8843H6.48503V4.79967C6.48503 4.68286 6.43867 4.57082 6.35614 4.48817C6.2736 4.40551 6.16163 4.35899 6.04482 4.35883Z" fill="white"/>
                            </svg> &nbsp;
                            '.themeMooveTimeIntToString($courseTime).'
                          </div>';
        }
        // Get las time access
        $lastAccessTimeRecord = $DB->get_record('user_lastaccess', array('courseid' => $course->id, 'userid' => $USER->id));
        $lastTimeHuman = date('H:i m/d/Y',$lastAccessTimeRecord->timeaccess);
        if ($course->progress < 100){
            // Count all in progress activeties
            $activetiesIPCount += $DB->count_records_sql($countCourseActivitiesSql, array('course' => $course->id,'moduled'=>$moduleCertificateId));
            $coursesIPCount ++ ;
            // All slide if more than 3 items
            if($coursesIPCount > 3) {
                $inProgressCoursesHtml .= '</div></div><div class="carousel-item"><div class="d-flex">';
            }
            //In progress courses
            $inProgressCoursesHtml .= '<div class="card dashboard-card d-block" role="listitem" data-region="course-content" data-course-id='.$course->id.'>
              <a href="'.$course->viewurl.'" tabindex="-1">
                  <div class="card-img dashboard-card-img" style="background-image: url('.$course->courseimage.'); overflow: hidden;border-radius: 20px;border: 10px solid white;"">
                      <div style="background-color: #FF0500" class="my-profile-course-featured">   '.get_string('featured', 'theme_moove').'</div>
                      '.$timeIPHTMl.'
                  </div>
              </a>
              <div style="text-align: left; padding: 10px 12px 0 12px;background-color: white" class="card-body course-info-container" id="course-info-container-2-3">
                  <div class="d-flex align-items-start">
                      <div class="w-100">
                              <span id="favorite-icon-2-3" data-region="favourite-icon" data-course-id="2">
                                  <span class="text-primary hidden" data-region="is-favourite" aria-hidden="true">
                                      <i class="icon fa fa-star fa-fw " title="Starred course" role="img" aria-label="Starred course"></i>
                                  </span>
                              </span>
                              <div>
                              <div class="my-profile-notification-text multiline" style="font-size: 11px; font-weight: 700">'.$course->fullnamedisplay.'</div>
                              <div class="course-rating-star">'.ratingCourse($course->id).'</div>
                              </div>
                              <div style="padding-top: 5px">
                                  <div>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 11 11" fill="none">
                                      <path fill-rule="evenodd" clip-rule="evenodd" d="M5.50016 10.9168C2.51016 10.9168 0.0834961 8.49558 0.0834961 5.50016C0.0834961 2.51016 2.51016 0.0834961 5.50016 0.0834961C8.49558 0.0834961 10.9168 2.51016 10.9168 5.50016C10.9168 8.49558 8.49558 10.9168 5.50016 10.9168ZM7.22808 7.50975C7.29308 7.54766 7.3635 7.56933 7.43933 7.56933C7.57475 7.56933 7.71016 7.49891 7.786 7.36891C7.89975 7.17933 7.84016 6.93016 7.64516 6.811L5.71683 5.66266V3.16016C5.71683 2.93266 5.53266 2.75391 5.31058 2.75391C5.0885 2.75391 4.90433 2.93266 4.90433 3.16016V5.89558C4.90433 6.03641 4.98016 6.16641 5.10475 6.24225L7.22808 7.50975Z" fill="#2147A8"/>
                                    </svg>
                                    <span class="my-profile-notification-text" style="font-size: 11px; color: #6D6D6D">&nbsp;&nbsp;'.date('d/m/Y', $course->startdate).' - '.date('d/m/Y', $course->enddate).'</span></div>
                                  <div>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 13 13" fill="none">
                                      <path fill-rule="evenodd" clip-rule="evenodd" d="M3.8998 1.2998C3.72741 1.2998 3.56208 1.36829 3.44019 1.49019C3.31829 1.61208 3.2498 1.77741 3.2498 1.9498V2.5998H2.5998C2.25502 2.5998 1.92436 2.73677 1.68057 2.98057C1.43677 3.22436 1.2998 3.55502 1.2998 3.8998V10.3998C1.2998 10.7446 1.43677 11.0752 1.68057 11.319C1.92436 11.5628 2.25502 11.6998 2.5998 11.6998H10.3998C10.7446 11.6998 11.0752 11.5628 11.319 11.319C11.5628 11.0752 11.6998 10.7446 11.6998 10.3998V3.8998C11.6998 3.55502 11.5628 3.22436 11.319 2.98057C11.0752 2.73677 10.7446 2.5998 10.3998 2.5998H9.7498V1.9498C9.7498 1.77741 9.68132 1.61208 9.55942 1.49019C9.43752 1.36829 9.2722 1.2998 9.0998 1.2998C8.92741 1.2998 8.76208 1.36829 8.64019 1.49019C8.51829 1.61208 8.4498 1.77741 8.4498 1.9498V2.5998H4.5498V1.9498C4.5498 1.77741 4.48132 1.61208 4.35942 1.49019C4.23753 1.36829 4.0722 1.2998 3.8998 1.2998ZM3.8998 4.5498C3.72741 4.5498 3.56208 4.61829 3.44019 4.74019C3.31829 4.86208 3.2498 5.02741 3.2498 5.1998C3.2498 5.3722 3.31829 5.53753 3.44019 5.65942C3.56208 5.78132 3.72741 5.8498 3.8998 5.8498H9.0998C9.2722 5.8498 9.43752 5.78132 9.55942 5.65942C9.68132 5.53753 9.7498 5.3722 9.7498 5.1998C9.7498 5.02741 9.68132 4.86208 9.55942 4.74019C9.43752 4.61829 9.2722 4.5498 9.0998 4.5498H3.8998Z" fill="#2147A8"/>
                                    </svg>
                                  <span class="my-profile-notification-text" style="font-size: 11px; color: #6D6D6D">&nbsp;&nbsp;'.$course->coursecategory.'</span></div>
                                  <div><span class="my-profile-notification-text" style="font-size: 10px; color: #6D6D6D">'.get_string('lasttimeaccess', 'theme_moove',$lastTimeHuman).'</span></div>
                              </div>
                      </div>
                  </div> 
              </div>
              <div class="align-items-start">
                  <div class="card-footer dashboard-card-footer menu border-0 bg-white ml-auto">
                      <div style="width: 100%">
                        <div class="progress-container" style="width: 100%;height: 5px;background-color: #E2FBD7;border-radius: 10px;">
                          <div class="progress-bar" style="width: '.$course->progress.'%;height: 100%;background-color: #34B53A;border-radius: 10px;"></div>
                        </div>
                        <div style="padding-top: 8px">
                            <div class="my-profile-notification-text" style="width: 60%; display: inline-block; text-align: left; font-weight: 600">'.get_string('inprogress', 'theme_moove').'</div>
                            <div class="my-profile-notification-text" style="width: 38%; display: inline-block; text-align: right;font-weight: 500;color: #34B53A">'.$course->progress.'%</div>
                        </div>
                      </div>
                  </div>
              </div>
          </div>';
        }
        else {
            // Count all complete activeties
            $activetiesCPCount = $DB->count_records_sql($countCourseActivitiesSql, array('course' => $course->id,'moduled'=>$moduleCertificateId));
            $coursesCPCount ++ ;
            if($coursesCPCount > 3) {
                $completeCoursesHtml .= '</div></div><div class="carousel-item"><div class="d-flex">';
            }
            // Get time courses
            $courseCustomfieldTime = $DB->get_record('customfield_field',array('shortname'=> 'tool_coursetime'));
            $courseTime = $DB->get_record('customfield_data',array('fieldid'=> $courseCustomfieldTime->id, 'instanceid' => $course->id))->value;
            $timeCPHTMl = '';
            if($courseTime) {
                $timeCPHTMl = '<div style="background: rgba(2,104,187,0.71);border-radius: 15px;top: 100px;left: 7px; height: 30px;display: flex;align-items: center;flex-direction: row;" class="my-profile-course-featured">
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="14" viewBox="0 0 13 14" fill="none">
                              <path d="M6.63219 0.833984C10.1204 0.833984 12.948 3.66156 12.948 7.14977C12.948 10.638 10.1204 13.4656 6.63219 13.4656C3.14398 13.4656 0.316406 10.638 0.316406 7.14977C0.316406 3.66156 3.14398 0.833984 6.63219 0.833984ZM6.04482 4.35883C5.92791 4.35883 5.81578 4.40527 5.7331 4.48795C5.65043 4.57062 5.60398 4.68275 5.60398 4.79967V8.32451C5.60398 8.5683 5.80103 8.76535 6.04482 8.76535H9.56966C9.62892 8.7676 9.68802 8.75786 9.74342 8.73674C9.79883 8.71561 9.8494 8.68352 9.89211 8.64239C9.93483 8.60126 9.96881 8.55194 9.99202 8.49738C10.0152 8.44281 10.0272 8.38412 10.0272 8.32483C10.0272 8.26553 10.0152 8.20684 9.99202 8.15228C9.96881 8.09771 9.93483 8.04839 9.89211 8.00726C9.8494 7.96613 9.79883 7.93404 9.74342 7.91292C9.68802 7.89179 9.62892 7.88206 9.56966 7.8843H6.48503V4.79967C6.48503 4.68286 6.43867 4.57082 6.35614 4.48817C6.2736 4.40551 6.16163 4.35899 6.04482 4.35883Z" fill="white"/>
                            </svg> &nbsp;
                            '.themeMooveTimeIntToString($courseTime).'
                          </div>';
            }
            // Complete courses
            $completeCoursesHtml .= '<div class="card dashboard-card d-block" role="listitem" data-region="course-content" data-course-id='.$course->id.'>
              <a href="'.$course->viewurl.'" tabindex="-1">
                  <div class="card-img dashboard-card-img" style="background-image: url('.$course->courseimage.'); overflow: hidden;border-radius: 20px;border: 10px solid white;">
                      <div style="background-color: #FF0500" class="my-profile-course-featured">   '.get_string('featured', 'theme_moove').'</div>
                      '.$timeCPHTMl.'
                  </div>
              </a>
              <div style="text-align: left; padding: 10px 12px 0 12px;background-color: white" class="card-body course-info-container" id="course-info-container-2-3">
                  <div class="d-flex align-items-start">
                      <div class="w-100">
                              <span id="favorite-icon-2-3" data-region="favourite-icon" data-course-id="2">
                                  <span class="text-primary hidden" data-region="is-favourite" aria-hidden="true">
                                      <i class="icon fa fa-star fa-fw " title="Starred course" role="img" aria-label="Starred course"></i>
                                  </span>
                              </span>
                              <div>
                                  <div class="my-profile-notification-text multiline" style="font-size: 11px; font-weight: 700">'.$course->fullnamedisplay.'</div>
                                  <div class="course-rating-star">'.ratingCourse($course->id).'</div>
                              </div>
                              <div style="padding-top: 5px">
                                  <div>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 11 11" fill="none">
                                      <path fill-rule="evenodd" clip-rule="evenodd" d="M5.50016 10.9168C2.51016 10.9168 0.0834961 8.49558 0.0834961 5.50016C0.0834961 2.51016 2.51016 0.0834961 5.50016 0.0834961C8.49558 0.0834961 10.9168 2.51016 10.9168 5.50016C10.9168 8.49558 8.49558 10.9168 5.50016 10.9168ZM7.22808 7.50975C7.29308 7.54766 7.3635 7.56933 7.43933 7.56933C7.57475 7.56933 7.71016 7.49891 7.786 7.36891C7.89975 7.17933 7.84016 6.93016 7.64516 6.811L5.71683 5.66266V3.16016C5.71683 2.93266 5.53266 2.75391 5.31058 2.75391C5.0885 2.75391 4.90433 2.93266 4.90433 3.16016V5.89558C4.90433 6.03641 4.98016 6.16641 5.10475 6.24225L7.22808 7.50975Z" fill="#2147A8"/>
                                    </svg>
                                  <span class="my-profile-notification-text" style="font-size: 11px; color: #6D6D6D">&nbsp;&nbsp;'.date('d/m/Y', $course->startdate).' - '.date('d/m/Y', $course->enddate).'</span></div>
                                  <div>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 13 13" fill="none">
                                      <path fill-rule="evenodd" clip-rule="evenodd" d="M3.8998 1.2998C3.72741 1.2998 3.56208 1.36829 3.44019 1.49019C3.31829 1.61208 3.2498 1.77741 3.2498 1.9498V2.5998H2.5998C2.25502 2.5998 1.92436 2.73677 1.68057 2.98057C1.43677 3.22436 1.2998 3.55502 1.2998 3.8998V10.3998C1.2998 10.7446 1.43677 11.0752 1.68057 11.319C1.92436 11.5628 2.25502 11.6998 2.5998 11.6998H10.3998C10.7446 11.6998 11.0752 11.5628 11.319 11.319C11.5628 11.0752 11.6998 10.7446 11.6998 10.3998V3.8998C11.6998 3.55502 11.5628 3.22436 11.319 2.98057C11.0752 2.73677 10.7446 2.5998 10.3998 2.5998H9.7498V1.9498C9.7498 1.77741 9.68132 1.61208 9.55942 1.49019C9.43752 1.36829 9.2722 1.2998 9.0998 1.2998C8.92741 1.2998 8.76208 1.36829 8.64019 1.49019C8.51829 1.61208 8.4498 1.77741 8.4498 1.9498V2.5998H4.5498V1.9498C4.5498 1.77741 4.48132 1.61208 4.35942 1.49019C4.23753 1.36829 4.0722 1.2998 3.8998 1.2998ZM3.8998 4.5498C3.72741 4.5498 3.56208 4.61829 3.44019 4.74019C3.31829 4.86208 3.2498 5.02741 3.2498 5.1998C3.2498 5.3722 3.31829 5.53753 3.44019 5.65942C3.56208 5.78132 3.72741 5.8498 3.8998 5.8498H9.0998C9.2722 5.8498 9.43752 5.78132 9.55942 5.65942C9.68132 5.53753 9.7498 5.3722 9.7498 5.1998C9.7498 5.02741 9.68132 4.86208 9.55942 4.74019C9.43752 4.61829 9.2722 4.5498 9.0998 4.5498H3.8998Z" fill="#2147A8"/>
                                    </svg>
                                    <span class="my-profile-notification-text" style="font-size: 11px; color: #6D6D6D">&nbsp;&nbsp;'.$course->coursecategory.'</span>
                                  </div>
                                  <div><span class="my-profile-notification-text" style="font-size: 10px; color: #6D6D6D">'.get_string('lasttimeaccess', 'theme_moove',$lastTimeHuman).'</span></div>
                              </div>
                      </div>
                  </div> 
              </div>
              <div class="align-items-start">
                  <div class="card-footer dashboard-card-footer menu border-0 bg-white ml-auto">
                      <div style="width: 100%">
                        <div class="progress-container" style="width: 100%;height: 5px;background-color: #E2FBD7;border-radius: 10px;">
                          <div class="progress-bar" style="width: '.$course->progress.'%;height: 100%;background-color: #34B53A;border-radius: 10px;"></div>
                        </div>
                        <div style="padding-top: 8px">
                            <div class="my-profile-notification-text" style="width: 60%; display: inline-block; text-align: left; font-weight: 600">'.get_string('completed', 'theme_moove').'</div>
                            <div class="my-profile-notification-text" style="width: 38%; display: inline-block; text-align: right;font-weight: 500;color: #34B53A">'.$course->progress.'%</div>
                        </div>
                      </div>
                  </div>
              </div>
          </div>';
        }
    }
    // Generate list option for searchba and sort by category
    $optionHtml = '';
    foreach(array_unique($listCategoryId) as $cateId) {
        // Find parent for all categories
        $parentCategories = $DB->get_record('course_categories',array('id' => $cateId,'parent' => 0));
        $childrenCategories = $DB->get_records('course_categories', array('parent' => $parentCategories->id), 'sortorder ASC');
        // Generate list option
        foreach ($childrenCategories as $record) {
            if($sortBycategory == $record->id) {
                $optionHtml .= '<option selected value="'.$record->id.'">'.$record->name.'</option>';
            } else {
                $optionHtml .= '<option value="'.$record->id.'">'.$record->name.'</option>';
            }
        }
    }
    $suggestCoursesHtml = '';
    $count = (count($listCategoryId) < 3) ? count($listCategoryId) : 3;
    // Get random 3 category and list category
    foreach(array_rand(array_unique($listCategoryId),$count) as $cateId){
        $category = core_course_category::get($cateId);
        foreach ($category->get_courses(array('offset' => 0, 'limit' => $count)) as $course) {
            // CHeck if enroll will pass
            $context = \context_course::instance($course->id);
            if (is_enrolled($context, $USER->id)) {
                continue;
            }
            $activetiesSCCount += $DB->count_records_sql($countCourseActivitiesSql, array('course' => $course->id,'moduled'=>$moduleCertificateId));
            $coursesSCCount ++ ;
            // Get time courses
            $courseCustomfieldTime = $DB->get_record('customfield_field',array('shortname'=> 'tool_coursetime'));
            $courseTime = $DB->get_record('customfield_data',array('fieldid'=> $courseCustomfieldTime->id, 'instanceid' => $course->id))->value;
            $timeSCHTMl = '';
            if($courseTime) {
                $timeSCHTMl = '<div style="background: rgba(2,104,187,0.71);border-radius: 15px;top: 100px;left: 7px; height: 30px;display: flex;align-items: center;flex-direction: row;" class="my-profile-course-featured">
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="14" viewBox="0 0 13 14" fill="none">
                              <path d="M6.63219 0.833984C10.1204 0.833984 12.948 3.66156 12.948 7.14977C12.948 10.638 10.1204 13.4656 6.63219 13.4656C3.14398 13.4656 0.316406 10.638 0.316406 7.14977C0.316406 3.66156 3.14398 0.833984 6.63219 0.833984ZM6.04482 4.35883C5.92791 4.35883 5.81578 4.40527 5.7331 4.48795C5.65043 4.57062 5.60398 4.68275 5.60398 4.79967V8.32451C5.60398 8.5683 5.80103 8.76535 6.04482 8.76535H9.56966C9.62892 8.7676 9.68802 8.75786 9.74342 8.73674C9.79883 8.71561 9.8494 8.68352 9.89211 8.64239C9.93483 8.60126 9.96881 8.55194 9.99202 8.49738C10.0152 8.44281 10.0272 8.38412 10.0272 8.32483C10.0272 8.26553 10.0152 8.20684 9.99202 8.15228C9.96881 8.09771 9.93483 8.04839 9.89211 8.00726C9.8494 7.96613 9.79883 7.93404 9.74342 7.91292C9.68802 7.89179 9.62892 7.88206 9.56966 7.8843H6.48503V4.79967C6.48503 4.68286 6.43867 4.57082 6.35614 4.48817C6.2736 4.40551 6.16163 4.35899 6.04482 4.35883Z" fill="white"/>
                            </svg> &nbsp;
                            '.themeMooveTimeIntToString($courseTime).'
                          </div>';
            }
            $course->courseimage = getThumbnailCourse($course);
            $suggestCoursesHtml .= '<div class="card dashboard-card d-block" role="listitem" data-region="course-content" data-course-id='.$course->id.'>
              <a href="'.$ajaxUrl.'?id='.$course->id.'" tabindex="-1">
                  <div class="card-img dashboard-card-img" style="background-image: url('.getThumbnailCourse($course).'); overflow: hidden;border-radius: 20px;border: 10px solid white;"">
                      <div style="background-color: #FF0500" class="my-profile-course-featured">   '.get_string('featured', 'theme_moove').'</div>
                      '.$timeSCHTMl.'
                  </div>
              </a>
              <div style="text-align: left; padding: 10px 12px 0 12px;background-color: white" class="card-body course-info-container" id="course-info-container-2-3">
                  <div class="d-flex align-items-start">
                      <div class="w-100">
                              <span id="favorite-icon-2-3" data-region="favourite-icon" data-course-id="2">
                                  <span class="text-primary hidden" data-region="is-favourite" aria-hidden="true">
                                      <i class="icon fa fa-star fa-fw " title="Starred course" role="img" aria-label="Starred course"></i>
                                  </span>
                              </span>
                              <div>
                                  <div class="my-profile-notification-text multiline" style="">'.$course->fullname.'</div>
                                  <div class="course-rating-star">'.ratingCourse($course->id).'</div>
                              </div>
                              <div style="padding-top: 5px">
                                  <div>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 11 11" fill="none">
                                      <path fill-rule="evenodd" clip-rule="evenodd" d="M5.50016 10.9168C2.51016 10.9168 0.0834961 8.49558 0.0834961 5.50016C0.0834961 2.51016 2.51016 0.0834961 5.50016 0.0834961C8.49558 0.0834961 10.9168 2.51016 10.9168 5.50016C10.9168 8.49558 8.49558 10.9168 5.50016 10.9168ZM7.22808 7.50975C7.29308 7.54766 7.3635 7.56933 7.43933 7.56933C7.57475 7.56933 7.71016 7.49891 7.786 7.36891C7.89975 7.17933 7.84016 6.93016 7.64516 6.811L5.71683 5.66266V3.16016C5.71683 2.93266 5.53266 2.75391 5.31058 2.75391C5.0885 2.75391 4.90433 2.93266 4.90433 3.16016V5.89558C4.90433 6.03641 4.98016 6.16641 5.10475 6.24225L7.22808 7.50975Z" fill="#2147A8"/>
                                    </svg>
                                    <span class="my-profile-notification-text" style="font-size: 11px; color: #6D6D6D">&nbsp;&nbsp;'.date('d/m/Y', $course->startdate).' - '.date('d/m/Y', $course->enddate).'</span></div>
                                  <div>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 13 13" fill="none">
                                      <path fill-rule="evenodd" clip-rule="evenodd" d="M3.8998 1.2998C3.72741 1.2998 3.56208 1.36829 3.44019 1.49019C3.31829 1.61208 3.2498 1.77741 3.2498 1.9498V2.5998H2.5998C2.25502 2.5998 1.92436 2.73677 1.68057 2.98057C1.43677 3.22436 1.2998 3.55502 1.2998 3.8998V10.3998C1.2998 10.7446 1.43677 11.0752 1.68057 11.319C1.92436 11.5628 2.25502 11.6998 2.5998 11.6998H10.3998C10.7446 11.6998 11.0752 11.5628 11.319 11.319C11.5628 11.0752 11.6998 10.7446 11.6998 10.3998V3.8998C11.6998 3.55502 11.5628 3.22436 11.319 2.98057C11.0752 2.73677 10.7446 2.5998 10.3998 2.5998H9.7498V1.9498C9.7498 1.77741 9.68132 1.61208 9.55942 1.49019C9.43752 1.36829 9.2722 1.2998 9.0998 1.2998C8.92741 1.2998 8.76208 1.36829 8.64019 1.49019C8.51829 1.61208 8.4498 1.77741 8.4498 1.9498V2.5998H4.5498V1.9498C4.5498 1.77741 4.48132 1.61208 4.35942 1.49019C4.23753 1.36829 4.0722 1.2998 3.8998 1.2998ZM3.8998 4.5498C3.72741 4.5498 3.56208 4.61829 3.44019 4.74019C3.31829 4.86208 3.2498 5.02741 3.2498 5.1998C3.2498 5.3722 3.31829 5.53753 3.44019 5.65942C3.56208 5.78132 3.72741 5.8498 3.8998 5.8498H9.0998C9.2722 5.8498 9.43752 5.78132 9.55942 5.65942C9.68132 5.53753 9.7498 5.3722 9.7498 5.1998C9.7498 5.02741 9.68132 4.86208 9.55942 4.74019C9.43752 4.61829 9.2722 4.5498 9.0998 4.5498H3.8998Z" fill="#2147A8"/>
                                    </svg>
                                    <span class="my-profile-notification-text" style="font-size: 11px; color: #6D6D6D">&nbsp;&nbsp;'.$category->name.'</span>
                                  </div>
                              </div>
                      </div>
                  </div> 
              </div>
              <div class="align-items-start">
                  <div class="card-footer dashboard-card-footer menu border-0 bg-white ml-auto">
                      <div style="width: 100%">
                        <div>
                            <div class="my-profile-notification-text" style="width: 44%; display: inline-block; text-align: left; font-weight: 600"></div>
                            <div class="my-profile-notification-text" style="width: 54%; display: inline-block; text-align: right;font-weight: 500;color: #34B53A">
                                <a href="'.$ajaxUrl.'?id='.$course->id.'" >
                                    <button class="btn d-flex align-items-center justify-content-between text-center-col-vtc" style="border-radius: 15px;background-color: rgba(2,104,187,.1);">
                                        <div style="font-size: 15px;font-weight: 500;color: #0060AD;">'.get_string('register_now', 'theme_moove').'</div>
                                    </button>
                                </a>
                            </div>
                        </div>
                      </div>
                  </div>
              </div>
          </div>';
        }
    }

    // Search bar and list category
    $html .= '<div class="w-100" style="padding: 15px 20px" data-region="card-deck" role="list">
                <div class="d-inline text-center-col-vtc" style="width: 49%;float: left;font-weight: 500">
                    <div style="margin: 5px; width: 19%;display: inline">'.get_string('category', 'theme_moove').'</div>
                    <select style="width: 80%;float: right;display: inline" class="form-control" onchange="getMyCourses(event,`courses`,`sort`,this)">
                        <option selected disabled hidden>'.get_string('all_category','theme_moove').'</option>
                        '.$optionHtml.'
                    </select>
                </div>
                <div class="d-inline" style="width: 49%;float: right">
                    <div class="input-group">
                        <div class="form-outline" style="width: 100%">
                            <input style="width: 100%" placeholder="'.get_string('find_courses', 'theme_moove').'" type="search" class="form-control find-my-courses"/>
                        </div>
                        <button type="button" onclick="getMyCourses(event,`courses`,`find`)" class="btn btn-primary btn-search-absolute">
                            <i class="fas fa-search" style="color: black"></i>
                        </button>
                    </div>
                </div>
            </div>';
    if($coursesIPCount != 0) {
        $preNextBtn = '';
        if($coursesIPCount > 3) {
            $preNextBtn = '<div class="carousel-controls">
                               <button class="carousel-control-prev" href="#carouselInprogressControls" role="button" data-slide="prev">
                                    &#10094;
                               </button>
                               <button class="carousel-control-next" href="#carouselInprogressControls" role="button" data-slide="next">
                                    &#10095;
                               </button>
                           </div>';
        }
        $html .= '<div  style="position: relative;" class="section-courses courses-inprogress">'.$preNextBtn.'
                    <div class="vtc-blue-color my-profile-notification-text" style="width: 49%; display: inline-block; text-align: left;font-size: 16px; padding-left: 10px">'.get_string('inprogress_courses', 'theme_moove').'</div>
                    <div class="my-profile-notification-text" style="width: 49%; display: inline-block; text-align: right;font-size: 16px;line-height: 30px">
                        '.$coursesIPCount.' '.get_string('courses', 'theme_moove').' | '.$activetiesIPCount.' '.get_string('activities', 'theme_moove').'
                    </div>
                    <div style="width: 92%;margin: auto" id="carouselInprogressControls" data-interval="false" class="carousel slide" data-ride="carousel"><div class="carousel-inner">'.$inProgressCoursesHtml.'</div></div></div></div></div>
                <div style="width: 100%; padding-top: 45px"></div>';
    }
    if($coursesCPCount != 0){
        $preNextBtn = '';
        if($coursesCPCount > 3) {
            $preNextBtn = '<div class="carousel-controls">
                               <button class="carousel-control-prev" href="#carouselInprogressControls" role="button" data-slide="prev">
                                    &#10094;
                               </button>
                               <button class="carousel-control-next" href="#carouselInprogressControls" role="button" data-slide="next">
                                    &#10095;
                               </button>
                           </div>';
        }
        $html .= '<div  style="position: relative;" class="section-courses courses-complete">'.$preNextBtn.'
                     <div class="vtc-blue-color my-profile-notification-text" style="width: 49%; display: inline-block; text-align: left;font-size: 16px; padding-left: 10px">'.get_string('complete_courses', 'theme_moove').'</div>
                     <div class="my-profile-notification-text" style="width: 49%; display: inline-block; text-align: right;font-size: 16px;line-height: 30px"">
                        '.$coursesCPCount.' '.get_string('courses', 'theme_moove').' | '.$activetiesCPCount.'  '.get_string('activities', 'theme_moove').'
                     </div>
                     <div style="width: 92%; margin: auto" id="carouselInprogressControls" data-interval="false" class="carousel slide" data-ride="carousel"><div class="carousel-inner">'.$completeCoursesHtml.' </div></div></div></div></div>
            <div style="width: 100%; padding-top: 45px"></div>';
     }
    if($coursesSCCount != 0) {
        $preNextBtn = '';
        if ($coursesSCCount > 3) {
            $preNextBtn = '<div class="carousel-controls">
                               <button class="carousel-control-prev" href="#carouselSuggestControls" role="button" data-slide="prev">
                                    &#10094;
                               </button>
                               <button class="carousel-control-next" href="#carouselSuggestControls" role="button" data-slide="next">
                                    &#10095;
                               </button>
                           </div>';
        }
        $html .= '<div  style="position: relative;" class="section-courses courses-complete">' . $preNextBtn . '
                     <div class="vtc-blue-color my-profile-notification-text" style="width: 49%; display: inline-block; text-align: left;font-size: 16px; padding-left: 10px">' . get_string('suggest_courses', 'theme_moove') . '</div>
                     <div class="my-profile-notification-text" style="width: 49%; display: inline-block; text-align: right;font-size: 16px;line-height: 30px"">
                        ' . $coursesSCCount . ' ' . get_string('courses', 'theme_moove') . ' | ' . $activetiesSCCount . '  ' . get_string('activities', 'theme_moove') . '
                     </div>
                     <div style="width: 92%; margin: auto" id="carouselSuggestControls" class="carousel slide" data-interval="false" data-ride="carousel"><div class="carousel-inner">' . $suggestCoursesHtml . ' </div></div></div></div></div>
            <div style="width: 100%; padding-top: 45px"></div>';
    }
    return [
        'html'=> $html
    ];
}

function get_notifications($requests) {
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
    $notifiContent = '';
    $unRead = 0;
    foreach ($records as $record) {
        // Check unread notifications
        $style = 'style="background-color: white"';
        if($record->timeread == null) {
            $unRead++;
            $style = 'style="background-color: #e6f0f7"';
        }
        // Converse timestamp to text
        $timeDifference = time() - $record->timecreated;
        $formattedTime = '';
        if (time() - $record->timecreated < (24 * 60 * 60) ) { // Check if time is less than 24 hours (in seconds)
            $hours = floor($timeDifference / 3600); // Convert seconds to hours
            $minutes = round(($timeDifference % 3600) / 60); // Convert remaining seconds to minutes
            if ($hours > 0) {
                $formattedTime =  $hours . " hours";
                if ($minutes > 0) {
                    $formattedTime .= " and " . $minutes . " minutes";
                }
                $formattedTime .= " ago";
            } elseif ($minutes > 0) {
                $formattedTime .= $minutes. " minutes ago";
            } else {
                $formattedTime =  "just now";
            }
        } else {
            $formattedTime = date("H:i d-m-Y", $record->timecreated);
        }
        $notifiContent .= '<a onclick="readedNotification(event,'.$record-> id.')"><div '.$style.' class="content-item-container notification" data-region="notification-content-item-container" data-id="'.$record->id.'" role="listitem">
                    <div tabindex="0" aria-label="hello" class="row">
                        <div class="col-9">
                            <div class="content-item-body">
                                    <div class="my-profile-notification-text my-profile-notification-header">  '.$record->subject.'</div>
                            </div>
                                <div class="content-item-footer">
                                    <div class="my-profile-notification-text my-profile-notification-message">  '.$record->fullmessage.'</div>
                                </div>
                        </div>
                        <div class="col-3">
                            <div class="my-profile-notification-text my-profile-notification-timestamp">'.$formattedTime.'</div>
                        </div>
                    </div>
                </div></a>';
    }
    $html = '<div style="width: 100%;display: inline-block;padding: 20px 10px 0 10px">
                <div class="left align-block my-profile-notification-text" style="float: left;text-align: left;font-weight: 500">'.get_string('unreadnotification', 'theme_moove', $unRead).'</div>
                <a onclick="readedNotification(event)"><h6 class="right vtc-blue-color align-block" style="float: right;text-align: right">'.get_string('markasread', 'theme_moove', $unRead).' <i class="fa-regular fa-circle-check"></i></h6></a>
            </div> 
                '. $notifiContent;
    $records->close();
    return $html;
}

function markReadedNotification($requests){
    global $CFG, $DB, $USER;
    $notificationid = $requests['notificationId'];
    $timeread = time();
    if($notificationid) {
        // Validate params.
        $params = array(
            'notificationid' => $notificationid,
            'timeread' => $timeread
        );
        $notification = $DB->get_record('notifications', ['id' => $params['notificationid']], '*', MUST_EXIST);
        if ($notification->timeread) {
            return [];
        }
        if ($notification->useridto != $USER->id) {
            throw new invalid_parameter_exception('Invalid notificationid, you don\'t have permissions to mark this ' .
                'notification as read');
        }
        \core_message\api::mark_notification_as_read($notification, $timeread);
        $results = array(
            'notificationid' => $notification->id,
        );
        return $results;
    } else {
        $notifications = $DB->get_records('notifications', array('useridto' => $USER->id, 'timeread' => null));
        foreach($notifications as $notification) {
            \core_message\api::mark_notification_as_read($notification, $timeread);
        }
    }
}

function get_certification($requests) {
    global $DB, $USER, $CFG;
    require_once($CFG->dirroot . '/course/lib.php');

// Create the div that represents the PDF.
    $requiredproperties = course_summary_exporter::define_properties();
    $fields = join(',', array_keys($requiredproperties));
    $hiddencourses = get_hidden_courses_on_timeline();
    $courses = course_get_enrolled_courses_for_logged_in_user(0, 0, null, $fields,
        COURSE_DB_QUERY_LIMIT, [], $hiddencourses);
    list($filteredcourses, $processedcount) = course_filter_courses_by_timeline_classification(
        $courses, 'all', 0);

    $html = '';
    foreach($filteredcourses as $course) {
        $listContextCourse  = $DB->get_records('context',array('instanceid' => $course->id));
        foreach ($listContextCourse as $contextCourse) {
            $customcert  = $DB->get_record('customcert_templates',array('contextid' => $contextCourse->id));
            if($customcert) {
                $page = $DB->get_record('customcert_pages', array('id' => $customcert->id), '*', MUST_EXIST);
                $template = $DB->get_record('customcert_templates', array('id' => $page->templateid), '*', MUST_EXIST);
                $elements = $DB->get_records('customcert_elements', array('pageid' => $customcert->id), 'sequence');

                $style = 'height: ' . $page->height . 'px; line-height: normal; width: ' . $page->width . 'px;';
                $marginstyle = 'height: ' . $page->height . 'px; width:1px; float:left; position:relative;';
                $html .= "http://localhost/edu-vtc-lms/mod/customcert/rearrange.php?pid={$customcert->id}";
//                $html .= html_writer::start_tag('div', array(
//                        'data-templateid' => $template->id,
//                        'data-contextid' => $template->contextid,
//                        'id' => 'pdf',
//                        'style' => $style)
//                );
//                if ($page->leftmargin) {
//                    $position = 'left:' . $page->leftmargin . 'px;';
//                    $html .= "<div id='leftmargin' style='$position $marginstyle'></div>";
//                }
//                if ($elements) {
//                    foreach ($elements as $element) {
//                        // Get an instance of the element class.
//                        if ($e = \mod_customcert\element_factory::get_element_instance($element)) {
//                            switch ($element->refpoint) {
//                                case \mod_customcert\element_helper::CUSTOMCERT_REF_POINT_TOPRIGHT:
//                                    $class = 'element refpoint-right';
//                                    break;
//                                case \mod_customcert\element_helper::CUSTOMCERT_REF_POINT_TOPCENTER:
//                                    $class = 'element refpoint-center';
//                                    break;
//                                case \mod_customcert\element_helper::CUSTOMCERT_REF_POINT_TOPLEFT:
//                                default:
//                                    $class = 'element refpoint-left';
//                            }
//                            switch ($element->alignment) {
//                                case \mod_customcert\element::ALIGN_CENTER:
//                                    $class .= ' align-center';
//                                    break;
//                                case \mod_customcert\element::ALIGN_RIGHT:
//                                    $class .= ' align-right';
//                                    break;
//                                case \mod_customcert\element::ALIGN_LEFT:
//                                default:
//                                    $class .= ' align-left';
//                                    break;
//                            }
//                            $html .= html_writer::tag('div', $e->render_html(), array('class' => $class,
//                                'data-refpoint' => $element->refpoint, 'id' => 'element-' . $element->id));
//                        }
//                    }
//                }
//                if ($page->rightmargin) {
//                    $position = 'left:' . ($page->width - $page->rightmargin) . 'px;';
//                    $html .= "<div id='rightmargin' style='$position $marginstyle'></div>";
//                }
//                $html .= html_writer::end_tag('div');
            }
        }
    }

    return [
        'html' => $html
    ];
}
// Go to function
$methodname = $requests['methodname'];
$responses = $methodname($requests);
echo json_encode($responses);
