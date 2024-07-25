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

global $DB, $CFG, $OUTPUT, $SITE, $PAGE, $USER,$COURSE;

require_once($CFG->libdir . '/behat/lib.php');
require_once($CFG->dirroot . '/course/lib.php');

// Add block button in editing mode.
$addblockbutton = $OUTPUT->addblockbutton();

user_preference_allow_ajax_update('drawer-open-nav', PARAM_ALPHA);
user_preference_allow_ajax_update('drawer-open-index', PARAM_BOOL);
user_preference_allow_ajax_update('drawer-open-block', PARAM_BOOL);

$courseid = optional_param('id', null, PARAM_INT); // Turn editing on and off


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

$addcontentblockbutton = $OUTPUT->addblockbutton('content');
$contentblocks = $OUTPUT->custom_block_region('content');

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

        if(!themeMooveCheckRoleUserInCourse($COURSE->id, 'student')){
            $extraclasses[] = 'has-secondarynavigation';
        }
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
$course = $DB->get_record('course', array('id' => $courseid));
$context = context_course::instance($course->id);

$course->student = getTotalStudent($course->id, 'student', 'full');
$course->totalactivity = getTotalActivitiesCourse($course->id);
if (isloggedin()) {
    $progress = getCourseProgressPercentage($course, $USER->id);
    if ($progress >= 0 && $progress < 100) {
        $course->progress = get_string('learning', 'theme_moove') . " : " . ceil($progress ? $progress : 0) . '%';
    } else {
        $course->progress = get_string('complete', 'theme_moove');
    }
    $course->progressnumber = $progress ? ceil($progress) : 0;
    $course->progressnumbervalue = $progress > 0 ? ceil($progress) . '%' : '';
}

$category = $DB->get_record('course_categories', array('id' => $course->category));

$arrayUrl = [
    [
        'url'  => (new moodle_url('/'))->out(),
        'name' => get_string('home', 'theme_moove'),
    ],
];
if ($category->parent == 0) {
    $arrayUrl[] = [
        'url'  => (new moodle_url('/course'))->out(),
        'name' => $category->name,
        'sub'  => [
            [
                'param' => 'category',
                'value' => $category->id
            ],
        ]
    ];

} else {
    $parentCategory = $DB->get_record('course_categories', array('id' => $category->parent));
    $arrayUrl[] = [
        'url'  => (new moodle_url('/course'))->out(),
        'name' => $parentCategory->name,
        'sub'  => [
            [
                'param' => 'category',
                'value' => $parentCategory->id
            ],
        ]
    ];
    $arrayUrl[] = [
        'url'  => (new moodle_url('/course'))->out(),
        'name' => $category->name,
        'sub'  => [
            [
                'param' => 'sub',
                'value' => $category->id
            ],
        ]
    ];
}
$htmlCategory = makeHtmlBreadcrumb($arrayUrl);
$course->breadcrumbs = $htmlCategory;
$course->category = $category;
$course->thumbnail = getThumbnailCourse($course);
$course->time = getCustomField('context_course', $course->id, 'time')['time'];
$course->listdocument = themeMooveGetlistDocumentsCourse($course->id);
$course->showlistdocument = count($course->listdocument) > 0 ? true : false;
$course->numberlistdocument = count(themeMooveGetlistDocumentsCourse($course->id));
$course->teacher = getListUsersByRole($course, 'editingteacher');
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
    'secondarymoremenu'         => themeMooveCheckRoleUserInCourse($COURSE->id, 'student') ? false : ($secondarynavigation ?: false),
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
    'addcontentblockbutton'     => $addcontentblockbutton,
    'contentblocks'             => $contentblocks,
    'course'                    => $course,
];
$templatecontext = array_merge($templatecontext, defaultTemplateContext());

$templatecontext = array_merge($templatecontext, $themesettings->footer());
echo $OUTPUT->render_from_template('theme_moove/course', $templatecontext);
