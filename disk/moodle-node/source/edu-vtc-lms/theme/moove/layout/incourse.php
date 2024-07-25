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

use mod_quiz\output\navigation_panel_attempt;

defined('MOODLE_INTERNAL') || die();

global $DB, $CFG, $OUTPUT, $SITE, $PAGE, $USER, $COURSE;


require_once($CFG->libdir . '/behat/lib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->libdir . '/gradelib.php');

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

$courseid = optional_param('id', null, PARAM_INT); // Turn editing on and off


$course = null;
$modQuizAttempt = null;
$modScormPlayer = null;

if ($PAGE->pagetype == 'enrol-index') {
    $course = $DB->get_record('course', array('id' => $courseid));
    $context = context_course::instance($course->id);
    $customFieldData = $DB->get_records('customfield_data', array('contextid' => $context->id));
    $course->time = getCustomField('context_course', $course->id, 'time')['time'];
    $course->student = getTotalStudent($course->id, 'student', 'full');
    $course->totalactivity = getTotalActivitiesCourse($course->id);
    if (isloggedin()) {
        $progress = getCourseProgressPercentage($course, $USER->id);
        if ($progress == 0) {
            $course->progress = get_string('notlearn', 'theme_moove');
        } elseif ($progress > 0 && $progress < 100) {
            $course->progress = get_string('learning', 'theme_moove') . " : " . $progress;
        } else {

            $course->progress = get_string('complete', 'theme_moove');
        }
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
                    'param' => 'categoryid',
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
                    'param' => 'categoryid',
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
    $course->breadcrumbs = makeHtmlBreadcrumb($arrayUrl);
    $course->category = $category;
    $course->thumbnail = getThumbnailCourse($course);
    $course->formsenrol = getListFormEnrol($course->id);
    if (getEnrollmentCourse($course) == 'self') {
        $course->enrollheader = get_string('enrollselftcontent', 'theme_moove');
        $enrollID = $DB->get_record('enrol', array('courseid' => $course->id, 'enrol' => 'self'));
        $course->enroll = '<form autocomplete="off" action="/enrol/index.php" method="post" accept-charset="utf-8" class="mform" data-boost-form-errors-enhanced="1">
                           <div style="display: none;">
                                <input name="id" type="hidden" value="' . $course->id . '">
                                <input name="instance" type="hidden" value="' . $enrollID->id . '">
                                <input name="sesskey" type="hidden" value="' . sesskey() . '">
                                <input name="_qf__' . $enrollID->id . '_enrol_self_enrol_form" type="hidden" value="1">
                                <input name="mform_isexpanded_id_selfheader" type="hidden" value="1">
                           </div>
                            <input type="submit" class="btn" style="display:block;margin:auto;border-radius: 48px;background-color:#1890FF;font-size: 16px;font-weight: 600;color: #FFFFFF" name="submitbutton" id="id_submitbutton" value="' . get_string('enrolltocourse', 'theme_moove') . '">
                       </form>';

    } else {
        $course->enrollheader = get_string('enrollmanualcontent', 'theme_moove');
    }
} else if ($PAGE->pagetype == 'mod-quiz-attempt' || $PAGE->pagetype == 'mod-quiz-review') {
    $attemptid = required_param('attempt', PARAM_INT);
    $page = optional_param('page', 0, PARAM_INT);
    $cmid = optional_param('cmid', null, PARAM_INT);

    $attemptobj = quiz_create_attempt_handling_errors($attemptid, $cmid);
    $output = '';
    $navbc = $attemptobj->get_navigation_panel($PAGE->get_renderer('mod_quiz'), navigation_panel_attempt::class, $page);
    $summaryLink = $attemptobj->get_cmid();
    $navbc = $navbc->content . '<div>
                    <a class="endtestlink aalink mb-3 btn" id="btn-submit" style="display: block;margin: auto" href="' . (new moodle_url('/mod/quiz/view.php', ['attempt' => $attemptobj->get_attemptid(), 'cmid' => $attemptobj->get_cmid()]))->out() . '">' . get_string('submit', 'theme_moove') . '</a>
                  </div>';
    $modQuizAttempt = [
        'content' => $navbc
    ];
    $review = [];
    if ($PAGE->pagetype == 'mod-quiz-review') {
        $attempt = $attemptobj->get_attempt();
        $courseid = $attemptobj->get_courseid();
        $quiz = $attemptobj->get_quiz();
        $settingGrade = getQuizSettingGrade($courseid, $quiz->id, $USER->id);
        $totalQuesetion = count($attemptobj->get_slots());
        $correctAnswer = 0;
        foreach ($attemptobj->get_slots() as $slot) {
            if ($attemptobj->get_question_attempt($slot)->get_fraction() > 0) {
                $correctAnswer++;
            }
        }
        $timetaken = '-';
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
        $examStatusPassed = false;
        $examStatusNotPass = true;
        if ($settingGrade != false && $settingGrade->gradepass <= $attempt->sumgrades) {
            $examStatusPassed = true;
            $examStatusNotPass = false;
        }
        $grade = (int) $attempt->sumgrades . '/' . (int) $quiz->sumgrades . ' (' . $attempt->sumgrades * 100 / $quiz->sumgrades . '%)';
        $navbc = $attemptobj->get_navigation_panel($PAGE->get_renderer('mod_quiz'), navigation_panel_attempt::class, $page);
        $quizCm = $attemptobj->get_cm();
        $navbc = $navbc->content . '<div>
                    <a class="aalink mb-3 btn" style="width:80%;border-radius: 8px;background: #1890FF;color:#fff;display: block;margin: auto" href="' . (new moodle_url('/mod/quiz/view.php', ['id' => $quizCm->id]))->out() . '">' . get_string('finishreview', 'theme_moove') . '</a>
                  </div>';
        $review = [
            'review'            => $navbc,
            'examstatuspassed'  => $examStatusPassed,
            'examstatusnotpass' => $examStatusNotPass,
            'numberrightanswer' => $correctAnswer,
            'dotime'            => $timetaken,
            'grade'             => $grade,
            'starttime'         => date('H:i,d/m/Y', $attempt->timestart),
            'history'           => getHistoryAttemptsQuiz($attemptobj),
        ];
    }
    $modQuizAttempt = array_merge($modQuizAttempt, $review);

}
else if ($PAGE->pagetype == 'mod-scorm-player') {
    $modScormPlayer = true;
}
//echo "<pre>";
//print_r($PAGE->pagetype);
//echo "</pre>";
//exit();
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
    'course'                    => $course,
    'modquizattempt'            => $modQuizAttempt,
    'modscormplayer'            => $modScormPlayer,
    'publicimage'               => (new moodle_url('/theme/moove/public'))->out(false),
];
$templatecontext = array_merge($templatecontext, defaultTemplateContext());

$templatecontext = array_merge($templatecontext, $themesettings->footer());

echo $OUTPUT->render_from_template('theme_moove/incourse', $templatecontext);
