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

global $DB, $USER, $OUTPUT, $SITE, $PAGE, $SESSION, $CFG;
require_once($CFG->libdir . '/gdlib.php');
// Get the profile userid.
$courseid = optional_param('course', 1, PARAM_INT);
$tab = optional_param('tab', 'profile', PARAM_RAW);

$userid = optional_param('id', 0, PARAM_INT);

if($userid){
    $userid = $userid ? $userid : $USER->id;

    $user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);

    $primary = new core\navigation\output\primary($PAGE);
    $renderer = $PAGE->get_renderer('core');
    $primarymenu = $primary->export_for_template($renderer);
    $buildregionmainsettings = !$PAGE->include_region_main_settings_in_header_actions() && !$PAGE->has_secondary_navigation();
// If the settings menu will be included in the header then don't add it here.
    $regionmainsettingsmenu = $buildregionmainsettings ? $OUTPUT->region_main_settings_menu() : false;

    $header = $PAGE->activityheader;
    $headercontent = $header->export_for_template($renderer);

    $userimg = new \user_picture($user);
    $userimg->size = 100;

    $context = \core\context\course::instance(SITEID);

    $extraclasses = [];
    $secondarynavigation = false;
    $overflow = '';
    if ($PAGE->has_secondary_navigation()) {
        $secondary = $PAGE->secondarynav;

        if ($secondary->get_children_key_list()) {
            $tablistnav = $PAGE->has_tablist_secondary_navigation();
            $moremenu = new \core\navigation\output\more_menu($PAGE->secondarynav, 'nav-tabs', true, $tablistnav);
            $secondarynavigation = $moremenu->export_for_template($OUTPUT);
            $extraclasses[] = 'has-secondarynavigation';
        }

        $overflowdata = $PAGE->secondarynav->get_overflow_menu_data();
        if (!is_null($overflowdata)) {
            $overflow = $overflowdata->export_for_template($OUTPUT);
        }
    }

    $bodyattributes = $OUTPUT->body_attributes($extraclasses);

    $templatecontext = [
        'sitename' => format_string($SITE->shortname, true, ['context' => \core\context\course::instance(SITEID), "escape" => false]),
        'output' => $OUTPUT,
        'bodyattributes' => $bodyattributes,
        'primarymoremenu' => $primarymenu['moremenu'],
        'secondarymoremenu' => $secondarynavigation ?: false,
        'mobileprimarynav' => $primarymenu['mobileprimarynav'],
        'usermenu' => $primarymenu['user'],
        'langmenu' => $primarymenu['lang'],
        'regionmainsettingsmenu' => $regionmainsettingsmenu,
        'hasregionmainsettingsmenu' => !empty($regionmainsettingsmenu),
        'userpicture' => $userimg->get_url($PAGE),
        'userfullname' => fullname($user),
        'headerbuttons' => \theme_moove\util\extras::get_mypublic_headerbuttons($context, $user),
        'editprofileurl' => \theme_moove\util\extras::get_mypublic_editprofile_url($user, $courseid),
        'userdescription' => format_text($user->description, $user->descriptionformat, ['overflowdiv' => true]),
    ];

    $templatecontext = array_merge($templatecontext, defaultTemplateContext());

    $themesettings = new \theme_moove\util\settings();
    $templatecontext = array_merge($templatecontext, $themesettings->footer());

    echo $OUTPUT->render_from_template('theme_moove/mypublic_old', $templatecontext);
}
else{
    $userid = $userid ? $userid : $USER->id;
    if (!empty($_FILES['useravatar'])) {
        $user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);

        $file = $_FILES['useravatar']['tmp_name'];
        $contextUser = context_user::instance($user->id);
        $fs = get_file_storage();
        $fs->delete_area_files($contextUser->id, 'user', 'icon');
        $new_avatar = (int) process_new_icon($contextUser, 'user', 'icon', 0, $file);
        $DB->set_field('user', 'picture', $new_avatar, ['id' => $user->id]);
    }

    $user = $USER;
    $primary = new core\navigation\output\primary($PAGE);
    $renderer = $PAGE->get_renderer('core');
    $primarymenu = $primary->export_for_template($renderer);
    $buildregionmainsettings = !$PAGE->include_region_main_settings_in_header_actions() && !$PAGE->has_secondary_navigation();
// If the settings menu will be included in the header then don't add it here.
    $regionmainsettingsmenu = $buildregionmainsettings ? $OUTPUT->region_main_settings_menu() : false;

    $header = $PAGE->activityheader;
    $headercontent = $header->export_for_template($renderer);

    $userimg = new \user_picture($USER);


    $context = \core\context\course::instance(SITEID);

    $extraclasses = [];
    $secondarynavigation = false;
    $overflow = '';
    if ($PAGE->has_secondary_navigation()) {
        $secondary = $PAGE->secondarynav;

        if ($secondary->get_children_key_list()) {
            $tablistnav = $PAGE->has_tablist_secondary_navigation();
            $moremenu = new \core\navigation\output\more_menu($PAGE->secondarynav, 'nav-tabs', true, $tablistnav);
            $secondarynavigation = $moremenu->export_for_template($OUTPUT);
            $extraclasses[] = 'has-secondarynavigation';
        }

        $overflowdata = $PAGE->secondarynav->get_overflow_menu_data();
        if (!is_null($overflowdata)) {
            $overflow = $overflowdata->export_for_template($OUTPUT);
        }
    }

    $bodyattributes = $OUTPUT->body_attributes($extraclasses);

    $userpicture = vtcGetUserAvatar();

    $templatecontext = [
        'sitename'                  => format_string($SITE->shortname, true, ['context' => \core\context\course::instance(SITEID), "escape" => false]),
        'output'                    => $OUTPUT,
        'bodyattributes'            => $bodyattributes,
        'primarymoremenu'           => $primarymenu['moremenu'],
        'secondarymoremenu'         => $secondarynavigation ? : false,
        'mobileprimarynav'          => $primarymenu['mobileprimarynav'],
        'usermenu'                  => $primarymenu['user'],
        'langmenu'                  => $primarymenu['lang'],
        'regionmainsettingsmenu'    => $regionmainsettingsmenu,
        'hasregionmainsettingsmenu' => !empty($regionmainsettingsmenu),
        'headerbuttons'             => \theme_moove\util\extras::get_mypublic_headerbuttons($context, $user),
        'editprofileurl'            => \theme_moove\util\extras::get_mypublic_editprofile_url($user, $courseid),
        'updateurl'                 => (new moodle_url("/theme/moove/classes/edituser.php"))->out(false),
        'ajaxurl'                   => (new moodle_url("/theme/moove/lib/ajax/service.php"))->out(false),
        'logouturl'                 => (new moodle_url('/login/logout.php?sesskey=' . sesskey()))->out(),
        'url'                       => new moodle_url('/user/profile.php?tab'),
        'currenturl'                => (new moodle_url($_SERVER['REQUEST_URI']))->out(false),
        'userpicture'               => $userpicture,
        'userfullname'              => $USER->firstname . ' ' . $USER->lastname,
        'username'                  => $USER->username,
        'useremail'                 => $USER->email,
        'cannoteditswitch'          => true
    ];
    $content = [];
    if ($tab == 'profile' && $userid) {
        $error = '';
        if (isset($_POST['fullname'])) {
            $error = updateUserProfile(data_submitted());
        }
        $profile = getProfileUser();
        $content = [
            'profile'      => $profile,
            'error'        => $error,
            'inputdatemax' => date('Y-m-d')
        ];
    } elseif ($tab == 'courses') {

        $subCate = optional_param('subcategoryid', '', PARAM_INT);
        $search = optional_param('search', '', PARAM_TEXT);
        $filterCourseType = optional_param('coursetype', '', PARAM_INT);
        $course = getUserCourses($subCate, $search, $filterCourseType);
        $content = [
            'course' => true,
        ];

        $content = array_merge($course, $content);
    } elseif ($tab == 'certificate') {
        $subCate = optional_param('subcategoryid', '', PARAM_INT);
        $search = optional_param('search', '', PARAM_TEXT);
        $data = getCertification($subCate, $search);
        $content = [
            'certificates' => $data
        ];
    } elseif ($tab == 'study') {
        $subCate = optional_param('subcategoryid', '', PARAM_INT);
        $search = optional_param('search', '', PARAM_TEXT);
        $data = getStudy($subCate, $search);
        $content = [
            'data'  => $data,
            'study' => true,
        ];
    } elseif ($tab == 'notification') {
        list($notification, $unread) = getNotification([]);
        $content = [
            'notifications'       => $notification,
            'unread'              => $unread,
            'notificationsstatus' => true,
        ];
    } elseif ($tab == 'changepassword') {
        $error = '';
        if (isset($_POST['old_pass'])) {
            $error = updatePassword(data_submitted());
        }
        $changepassword = getNotification([]);
        $content = [
            'changepassword' => [
                'changepassimgurl' => (new moodle_url("/theme/moove/pix/changepassword.png"))->out(false),
            ],
            'error'          => $error,
        ];
    }

    $templatecontext = array_merge($templatecontext, $content);

    $templatecontext = array_merge($templatecontext, defaultTemplateContext());

    $themesettings = new \theme_moove\util\settings();
    $templatecontext = array_merge($templatecontext, $themesettings->footer());
    echo $OUTPUT->render_from_template('theme_moove/mypublic', $templatecontext);
}
