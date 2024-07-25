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
 * A drawer based layout for the moove theme.
 *
 * @package    theme_moove
 * @copyright  2022 Willian Mano {@link https://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

global $CFG, $OUTPUT, $PAGE, $SITE;
require_once($CFG->libdir . '/behat/lib.php');
require_once(__DIR__ . '/../lib.php');
global $DB, $USER;
// Add block button in editing mode.
$addblockbutton = $OUTPUT->addblockbutton();
$PAGE->set_title(get_string('courses','theme_moove'));
user_preference_allow_ajax_update('drawer-open-nav', PARAM_ALPHA);
user_preference_allow_ajax_update('drawer-open-index', PARAM_BOOL);
user_preference_allow_ajax_update('drawer-open-block', PARAM_BOOL);

$cate = optional_param('categoryid', null, PARAM_INT);    // Turn editing on and off
$subCate = optional_param('sub', null, PARAM_INT);
$search = optional_param('search', null, PARAM_TEXT);
$filter = optional_param('filter', null, PARAM_INT);
$page = optional_param('page', null, PARAM_INT);
$activeTab = optional_param('tab', null, PARAM_INT);


$limit = 6;

$paramurl = [];
if (isset(parse_url($_SERVER['REQUEST_URI'])['query'])) {
    parse_str(parse_url($_SERVER['REQUEST_URI'])['query'], $params);
    foreach ($params as $key => $param) {
        if ($key != 'search' && $key != 'filter' && $key != 'page') {
            $paramurl[] = [
                'key'   => $key,
                'value' => $param
            ];
        }

    }
}

if (isloggedin()) {
    $courseindexopen = (get_user_preferences('drawer-open-index', true) == true);
    $blockdraweropen = (get_user_preferences('drawer-open-block') == true);
} else {
    $courseindexopen = false;
    $blockdraweropen = false;
}

if (defined('BEHAT_SITE_RUNNING')) {
    $blockdraweropen = true;
}

$extraclasses = ['uses-drawers'];
if ($courseindexopen) {
    $extraclasses[] = 'drawer-open-index';
}

$blockshtml = $OUTPUT->blocks('side-pre');
$hasblocks = (strpos($blockshtml, 'data-block=') !== false || !empty($addblockbutton));
if (!$hasblocks) {
    $blockdraweropen = false;
}

$themesettings = new \theme_moove\util\settings();

if (!$themesettings->enablecourseindex) {
    $courseindex = '';
} else {
    $courseindex = core_course_drawer();
}

if (!$courseindex) {
    $courseindexopen = false;
}

$forceblockdraweropen = $OUTPUT->firstview_fakeblocks();

$secondarynavigation = false;
$overflow = '';
if ($PAGE->has_secondary_navigation()) {
    $secondary = $PAGE->secondarynav;

    if ($secondary->get_children_key_list()) {
        $tablistnav = $PAGE->has_tablist_secondary_navigation();
        $moremenu = new \core\navigation\output\more_menu($PAGE->secondarynav, 'nav-tabs', true, $tablistnav);
        $secondarynavigation = $moremenu->export_for_template($OUTPUT);
//        $extraclasses[] = 'has-secondarynavigation';
    }

    $overflowdata = $PAGE->secondarynav->get_overflow_menu_data();
    if (!is_null($overflowdata)) {
        $overflow = $overflowdata->export_for_template($OUTPUT);
    }
}

$primary = new core\navigation\output\primary($PAGE);
$renderer = $PAGE->get_renderer('core');
$primarymenu = $primary->export_for_template($renderer);
$buildregionmainsettings = !$PAGE->include_region_main_settings_in_header_actions() && !$PAGE->has_secondary_navigation();
// If the settings menu will be included in the header then don't add it here.
$regionmainsettingsmenu = $buildregionmainsettings ? $OUTPUT->region_main_settings_menu() : false;

$header = $PAGE->activityheader;
$headercontent = $header->export_for_template($renderer);

$bodyattributes = $OUTPUT->body_attributes($extraclasses);
$categories = $DB->get_records("course_categories", array("parent" => 0),'sortorder ASC');
date_default_timezone_set('Asia/Ho_Chi_Minh');
$now = time();
$list_category = [];
foreach ($categories as $category) {
    $subcategory = $DB->get_records("course_categories", array("parent" => $category->id),'sortorder ASC');
    $countCourses = 0;
    $totalActivity = 0;
    $isLearning = 0;
    foreach ($subcategory as $sc) {
        $sql = 'SELECT * FROM {course} WHERE visible != 0 and (startdate < :time1 and ( enddate > :time2 or enddate =0 )) and category = :category order by sortorder ASC';
        $courses = $DB->get_records_sql($sql, array('time1'=>$now,'time2'=>$now,'id' => $subCate, 'category' => $sc->id));
        $countCourses += count($courses);
        foreach ($courses as $course) {
            if (themeMooveCheckRoleUserInCourse($course->id,'student') && $isLearning == 0) {
                $isLearning = 1;
            }
            $totalActivity += $DB->count_records('course_modules', array('course' => $course->id));
        }
    }
    $category->countcourse = $countCourses;
    $category->totalactivity = $totalActivity;
    $category->isLearning = $isLearning;
    $category->image = themeMooveGetCourseCategoryPicture($category->id);
    $category->description = $category->description ? strip_tags($category->description, '<b>') : $category->description;
    $category->url = new moodle_url('/course/index.php?categoryid=' . $category->id);
    $list_category [] = $category;
}
$templatecontext = [
    'sitename'                  => format_string($SITE->shortname, true, ['context' => \core\context\course::instance(SITEID), "escape" => false]),
    'output'                    => $OUTPUT,
    'sidepreblocks'             => $blockshtml,
    'hasblocks'                 => $hasblocks,
    'bodyattributes'            => $bodyattributes,
    'courseindexopen'           => $courseindexopen,
    'blockdraweropen'           => $blockdraweropen,
    'courseindex'               => $courseindex,
    'primarymoremenu'           => $primarymenu['moremenu'],
    //    'secondarymoremenu'         => $secondarynavigation ? : false,
    'secondarymoremenu'         => false,
    'mobileprimarynav'          => $primarymenu['mobileprimarynav'],
    'usermenu'                  => $primarymenu['user'],
    'langmenu'                  => $primarymenu['lang'],
    'forceblockdraweropen'      => $forceblockdraweropen,
    'regionmainsettingsmenu'    => $regionmainsettingsmenu,
    'hasregionmainsettingsmenu' => !empty($regionmainsettingsmenu),
    'overflow'                  => $overflow,
    'headercontent'             => $headercontent,
    'addblockbutton'            => $addblockbutton,
    'enablecourseindex'         => $themesettings->enablecourseindex,
    'url'                       => (new moodle_url($_SERVER['REQUEST_URI']))->out(false),
    'paramurl'                  => $paramurl,
];
$templatecontext = array_merge($templatecontext, defaultTemplateContext());

$category = [];
if ($cate) {
    $courseCategory = $DB->get_record("course_categories", array("id" => $cate));
    $subCategories = $DB->get_records('course_categories', array("parent" => $cate,'visible'=>1),'sortorder ASC');
    $totalSubCategory = count($subCategories);
    $category['inactivesubcategory'] = get_string('allsubcategory', 'theme_moove');
    $category['inactivelistfilter'] = get_string('all', 'theme_moove');
    $category['listfilter'] = listFilterInCourseCategory($_SERVER['REQUEST_URI']);
    $category['inactivefilter'] = $filter ? $category['listfilter'][$filter]['name'] : $category['listfilter'][0]['name'];
    if (isloggedin()) {
        if (!isset($filter)) {
            $category['inactivefilterinlogin'] = get_string('studyfilter', 'theme_moove');
            $category['inactivefilterinloginstatus'] = true;
        } else {
            if ($filter == 1 || $filter == 2) {
                $category['inactivefilterinlogin'] = get_string('studyfilter', 'theme_moove');
                $category['inactivefilterinloginstatus'] = false;
            } else {
                $category['inactivefilterinlogin'] = $category['listfilter'][$filter]['name'];
                $category['inactivefilterinloginstatus'] = true;
            }
        }
        $category['inactivefilterinloginScreenMD'] = !isset($filter) ? get_string('filter','theme_moove') : $category['listfilter'][$filter]['name'];
    } else {
        $category['inactivefilterinlogin'] = !isset($filter) ? $category['listfilter'][0]['name'] : $category['listfilter'][$filter]['name'];
        $category['inactivefilterinloginScreenMD'] = !isset($filter) ? $category['listfilter'][0]['name'] : $category['listfilter'][$filter]['name'];
    }

    $category['category'] = $courseCategory;
    if ($subCate) {
        if ($search) {
            $sql = 'SELECT * FROM {course} WHERE visible != 0 and (startdate < :time1 and ( enddate > :time2 or enddate =0 )) and fullname LIKE :fullname and category = :id order by sortorder ASC';
            $courses = $DB->get_records_sql($sql, array('time1'=>$now,'time2'=>$now,'id' => $subCate, 'fullname' => '%' . $search . '%'));
        } else {
            $sql = 'SELECT * FROM {course} WHERE visible != 0 and (startdate < :time1 and ( enddate > :time2 or enddate =0 )) and category = :id order by sortorder ASC';
            $courses = $DB->get_records_sql($sql, array('time1'=>$now,'time2'=>$now,'id' => $subCate));
        }
        $totalSubCategory = 0;
        $subCategory = $DB->get_record('course_categories', array('id' => $subCate));
        $category['inactivesubcategory'] = $subCategory->name;

    } else {
        if ($search) {
            $sql = 'SELECT * FROM {course} WHERE  visible != 0 and (startdate < :time1 and ( enddate > :time2 or enddate =0 )) and fullname LIKE :fullname and category IN (SELECT id FROM {course_categories} WHERE parent = :parent and depth = 2) order by sortorder ASC';
            $courses = $DB->get_records_sql($sql, array('time1'=>$now,'time2'=>$now,'parent' => $cate, 'fullname' => '%' . $search . '%'));
        } else {
            $sql = 'SELECT * FROM {course} WHERE visible != 0 and (startdate < :time1 and ( enddate > :time2 or enddate =0 )) and category IN (SELECT id FROM {course_categories} WHERE parent = :parent and depth = 2) order by sortorder ASC';
            $courses = $DB->get_records_sql($sql, array('time1'=>$now,'time2'=>$now,'parent' => $cate));
        }
    }
    if ($filter == 1) {
        $courses = filterCourse($courses, 'latest');
    }
    if ($filter == 2) {
        $courses = filterCourse($courses, 'outstanding');
    }
    if (isloggedin()) {
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
                if (is_enrolled($coursecontext, $USER->id) && themeMooveCheckRoleUserInCourse($c->id,'student')) {
                    if (getCourseProgressPercentage($c, $USER->id) < 100)
                        return $c;
                }
                return '';
            });
        }
        if ($filter == 5) {
            $courses = array_filter($courses, function ($c) use ($USER) {
                $coursecontext = context_course::instance($c->id);
                if (is_enrolled($coursecontext, $USER->id) && themeMooveCheckRoleUserInCourse($c->id,'student')) {
                    if (getCourseProgressPercentage($c, $USER->id) == 100)
                        return $c;
                }
                return '';
            });

        }
    }

    $category['subcategories'] = listSubCategories($subCategories, $subCate, 'sub', 'categoryid');
    $totalCourse = count($courses);
    $q = [];
    foreach ($paramurl as $pu) {
        $q[$pu['key']] = $pu['value'];
    }
    if($search && $search != ''){
        $q['search'] = $search;
    }
    $url = new moodle_url(parse_url($_SERVER['REQUEST_URI'])['path'] . '?' . http_build_query($q, '', '&'));
    if (!isset($page)) {
        $page = 1;
    }
    $totalActivity = 0;
    foreach ($courses as $c) {
        $totalActivity += $DB->count_records('course_modules', array('course' => $c->id));
    }
    list($courses, $paging) = pagination($totalCourse, $courses, $page, $limit, $url);
    $category['paging'] = $paging;

    $listCourses = [];
    foreach ($courses as $c) {
        $c->summary = strip_tags($c->summary, '<b>');
        $c->student = getTotalStudent($c->id, 'student');
        $c->url = \course_get_url($c->id)->out(false);
        $c->courseimage = getThumbnailCourse($c);
        if (\core_component::get_plugin_directory('tool', 'courserating')) {
            $c->rating = ratingCourse($c->id);
        }
        if (isloggedin()) {
            $progress = getCourseProgressPercentage($c, $USER->id);
            $coursecontext = context_course::instance($c->id);
            if (is_enrolled($coursecontext, $USER->id) && themeMooveCheckRoleUserInCourse($c->id,'student')) {
                $progress = $progress ? ceil($progress) : 0;
                $radius = 20;
                $c->progress = [
                    'number'     => $progress,
                    'text'       => $progress . '%',
                    'dasharray'  => M_PI * 2 * $radius,
                    'dashoffset' => (M_PI * 2 * $radius) * (1 - $progress / 100),
                    'radius'     => $radius
                ];;
            }


        }
        $customLabel = getCustomField('context_course', $c->id, 'label');
        $customTime = getCustomField('context_course', $c->id, 'time');

        $c->customfield = array_merge($customLabel, $customTime);
        $listCourses[] = $c;
    }
    $category['courses'] = $listCourses;
    if ($search) {
        $category['search'] = $search;
    }
    $category['breadcrumbs'] = array(
        'htmlCategory'      => makeHtmlBreadcrumb([
            [
                'url'  => (new moodle_url('/'))->out(),
                'name' => get_string('home', 'theme_moove'),
            ],
            [
                'url'  => (new moodle_url('/course'))->out(),
                'name' => get_string('learn', 'theme_moove'),
            ],
            [
                'url'  => (new moodle_url('/course'))->out(),
                'name' => $courseCategory->name,
                'sub'  => [
                    [
                        'param' => 'categoryid',
                        'value' => $courseCategory->id
                    ],
                ]
            ],
        ]),
        'htmlCategoryRight' => $totalCourse . ' ' . get_string('course', 'theme_moove') . ' | ' . $totalActivity . ' ' . get_string('lession', 'theme_moove')

    );


}else{
    if($search){
        $sql = 'SELECT * FROM {course} WHERE  visible != 0 and (startdate < :time1 and ( enddate > :time2 or enddate =0 )) and fullname LIKE :fullname and category IN (SELECT id FROM {course_categories} WHERE parent !=0 and depth =2) order by sortorder ASC';
        $courses = $DB->get_records_sql($sql, array('time1'=>$now,'time2'=>$now,'parent' => $cate, 'fullname' => '%' . $search . '%'));
        $totalCourse = count($courses);
        $q = [];
        foreach ($paramurl as $pu) {
            $q[$pu['key']] = $pu['value'];
        }
        if($search && $search != ''){
            $q['search'] = $search;
        }

        $url = new moodle_url(parse_url($_SERVER['REQUEST_URI'])['path'] . '?' . http_build_query($q, '', '&'));
        if (!isset($page)) {
            $page = 1;
        }
        $totalActivity = 0;
        foreach ($courses as $c) {
            $totalActivity += $DB->count_records('course_modules', array('course' => $c->id));
        }
        list($courses, $paging) = pagination($totalCourse, $courses, $page, $limit, $url);
        $category['paging'] = $paging;

        $listCourses = [];
        foreach ($courses as $c) {
            $c->summary = strip_tags($c->summary, '<b>');
            $c->student = getTotalStudent($c->id, 'student');
            $c->url = \course_get_url($c->id)->out(false);
            $c->courseimage = getThumbnailCourse($c);
            if (\core_component::get_plugin_directory('tool', 'courserating')) {
                $c->rating = ratingCourse($c->id);
            }
            if (isloggedin()) {
                $progress = getCourseProgressPercentage($c, $USER->id);
                $coursecontext = context_course::instance($c->id);
                if (is_enrolled($coursecontext, $USER->id) && themeMooveCheckRoleUserInCourse($c->id,'student')) {
                    $progress = $progress ? ceil($progress) : 0;
                    $radius = 20;
                    $c->progress = [
                        'number'     => $progress,
                        'text'       => $progress . '%',
                        'dasharray'  => M_PI * 2 * $radius,
                        'dashoffset' => (M_PI * 2 * $radius) * (1 - $progress / 100),
                        'radius'     => $radius
                    ];;
                }


            }
            $customLabel = getCustomField('context_course', $c->id, 'label');
            $customTime = getCustomField('context_course', $c->id, 'time');

            $c->customfield = array_merge($customLabel, $customTime);
            $listCourses[] = $c;
        }
        $category['courses'] = $listCourses;
        if ($search) {
            $category['search'] = $search;
        }
    }
}

if ($cate) {
    $templatecontext = array_merge($templatecontext, ['category' => $category]);

} else {
    if($search){
        $templatecontext = array_merge($templatecontext, ['listcourse' => $category]);

    }else{
        $templatecontext = array_merge($templatecontext, ['listcategories' => ['categories' => $list_category]]);

    }

}

$templatecontext = array_merge($templatecontext, $themesettings->footer());

echo $OUTPUT->render_from_template('theme_moove/coursecategory', $templatecontext);
