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
 * Frontpage layout for the moove theme.
 *
 * @package    theme_moove
 * @copyright  2022 Willian Mano {@link https://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
global $USER, $OUTPUT, $CFG, $PAGE, $SITE, $DB;

require_once($CFG->libdir . '/behat/lib.php');
require_once($CFG->dirroot . '/course/lib.php');


// Add block button in editing mode.
$addblockbutton = $OUTPUT->addblockbutton();

user_preference_allow_ajax_update('drawer-open-nav', PARAM_ALPHA);
user_preference_allow_ajax_update('drawer-open-index', PARAM_BOOL);
user_preference_allow_ajax_update('drawer-open-block', PARAM_BOOL);

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
$courseindex = core_course_drawer();
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
        // comment ko muá»‘n cÃ³ thanh quáº£n trá»‹ á»Ÿ trang home
        // $extraclasses[] = 'has-secondarynavigation';
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
$issiteadmin = is_siteadmin() ? (new moodle_url('/admin/search.php'))->out() : false;
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

];
$templatecontext = array_merge($templatecontext, defaultTemplateContext());
$themesettings = new \theme_moove\util\settings();

// count visitor
collectionVisitor();

// get data homepage
$cache = cache::make( 'theme_moove', 'homepage');
$homePage = $cache->get('homepagedata');
if(!$homePage){
    $homePage = new \theme_moove\layout\home();
    $cache->set('homepagedata',$homePage);
}

$templatecontextdata = [
    'countaccess'=>$homePage->countAccess,
    'countlearned'=>$homePage->countStudent,
    'countactivity'=>$homePage->countActivity,
    'countteacher'=>$homePage->countTeacher,
    'countVisitor'=>$homePage->countVisitor,
    'courses'    => [
        'list'=>$homePage->coursesData,
        'hiddenbuttonnext' => count($homePage->coursesData) > 3 ? true : false,
        'hiddenbuttonnext2' => count($homePage->coursesData) > 2 ? true : false,
        'hiddenbuttonnext3' => count($homePage->coursesData) > 1 ? true : false,
        'html'             => makeHtmlCarouselCourseOutstanding($homePage->coursesData, 3),
        'html2'            => makeHtmlCarouselCourseOutstanding($homePage->coursesData, 2),
        'html3'            => makeHtmlCarouselCourseOutstanding($homePage->coursesData, 1),
    ],
    'categories' => [
        'list'             => $homePage->categoriesData,
        'hiddenbuttonnext' => count($homePage->categoriesData) > 3 ? true : false,
        'hiddenbuttonnext2' => count($homePage->categoriesData) > 2 ? true : false,
        'hiddenbuttonnext3' => count($homePage->categoriesData) > 1 ? true : false,
        'html'             => makeHtmlCarouselCategory($homePage->categoriesData, 3),
        'html2'            => makeHtmlCarouselCategory($homePage->categoriesData,2),
        'html3'            => makeHtmlCarouselCategory($homePage->categoriesData,1),
    ]
];
$templatecontext = array_merge($templatecontext, $themesettings->footer(), $templatecontextdata);
$templatecontext = array_merge($templatecontext, $themesettings->frontpage());

if (isloggedin()) {
    echo $OUTPUT->render_from_template('theme_moove/home', $templatecontext);
} else {

    echo $OUTPUT->render_from_template('theme_moove/home', $templatecontext);
}
