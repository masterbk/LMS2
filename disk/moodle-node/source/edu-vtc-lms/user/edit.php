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
 * Allows you to edit a users profile
 *
 * @copyright 1999 Martin Dougiamas  http://dougiamas.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package core_user
 */

require_once('../config.php');
require_once($CFG->libdir.'/gdlib.php');
require_once($CFG->dirroot.'/user/edit_form.php');
require_once($CFG->dirroot.'/user/editlib.php');
require_once($CFG->dirroot.'/user/profile/lib.php');
require_once($CFG->dirroot.'/user/lib.php');
require_once($CFG->dirroot.'/theme/moove/lib.php');

$userid = optional_param('id', $USER->id, PARAM_INT);    // User id.
$course = optional_param('course', SITEID, PARAM_INT);   // Course id (defaults to Site).
$returnto = optional_param('returnto', null, PARAM_ALPHA);  // Code determining where to return to after save.
$cancelemailchange = optional_param('cancelemailchange', 0, PARAM_INT);   // Course id (defaults to Site).

$PAGE->set_url('/user/edit.php', array('course' => $course, 'id' => $userid));

if (!$course = $DB->get_record('course', array('id' => $course))) {
    throw new \moodle_exception('invalidcourseid');
}

if ($course->id != SITEID) {
    require_login($course);
} else if (!isloggedin()) {
    if (empty($SESSION->wantsurl)) {
        $SESSION->wantsurl = $CFG->wwwroot.'/user/edit.php';
    }
    redirect(get_login_url());
} else {
    $PAGE->set_context(context_system::instance());
}

// Guest can not edit.
if (isguestuser()) {
    throw new \moodle_exception('guestnoeditprofile');
}

// The user profile we are editing.
if (!$user = $DB->get_record('user', array('id' => $userid))) {
    throw new \moodle_exception('invaliduserid');
}

// Guest can not be edited.
if (isguestuser($user)) {
    throw new \moodle_exception('guestnoeditprofile');
}

// User interests separated by commas.
$user->interests = core_tag_tag::get_item_tags_array('core', 'user', $user->id);

// Remote users cannot be edited. Note we have to perform the strict user_not_fully_set_up() check.
// Otherwise the remote user could end up in endless loop between user/view.php and here.
// Required custom fields are not supported in MNet environment anyway.
if (is_mnet_remote_user($user)) {
    if (user_not_fully_set_up($user, true)) {
        $hostwwwroot = $DB->get_field('mnet_host', 'wwwroot', array('id' => $user->mnethostid));
        throw new \moodle_exception('usernotfullysetup', 'mnet', '', $hostwwwroot);
    }
    redirect($CFG->wwwroot . "/user/view.php?course={$course->id}");
}

// Load the appropriate auth plugin.
$userauth = get_auth_plugin($user->auth);

if (!$userauth->can_edit_profile()) {
    throw new \moodle_exception('noprofileedit', 'auth');
}

if ($editurl = $userauth->edit_profile_url()) {
    // This internal script not used.
    redirect($editurl);
}

if ($course->id == SITEID) {
    $coursecontext = context_system::instance();   // SYSTEM context.
} else {
    $coursecontext = context_course::instance($course->id);   // Course context.
}
$systemcontext   = context_system::instance();
$personalcontext = context_user::instance($user->id);

// Check access control.
if ($user->id == $USER->id) {
    // Editing own profile - require_login() MUST NOT be used here, it would result in infinite loop!
    if (!has_capability('moodle/user:editownprofile', $systemcontext)) {
        throw new \moodle_exception('cannotedityourprofile');
    }

} else {
    // Teachers, parents, etc.
    require_capability('moodle/user:editprofile', $personalcontext);
    // No editing of guest user account.
    if (isguestuser($user->id)) {
        throw new \moodle_exception('guestnoeditprofileother');
    }
    // No editing of primary admin!
    if (is_siteadmin($user) and !is_siteadmin($USER)) {  // Only admins may edit other admins.
        throw new \moodle_exception('useradmineditadmin');
    }
}

if ($user->deleted) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('userdeleted'));
    echo $OUTPUT->footer();
    die;
}

$PAGE->set_pagelayout('admin');
$PAGE->add_body_class('limitedwidth');
$PAGE->set_context($personalcontext);
if ($USER->id != $user->id) {
    $PAGE->navigation->extend_for_user($user);
} else {
    if ($node = $PAGE->navigation->find('myprofile', navigation_node::TYPE_ROOTNODE)) {
        $node->force_open();
    }
}

// Process email change cancellation.
if ($cancelemailchange) {
    cancel_email_update($user->id);
}

// Load user preferences.
useredit_load_preferences($user);

// Load custom profile fields data.
profile_load_data($user);


// Prepare the editor and create form.
$editoroptions = array(
    'maxfiles'   => EDITOR_UNLIMITED_FILES,
    'maxbytes'   => $CFG->maxbytes,
    'trusttext'  => false,
    'forcehttps' => false,
    'context'    => $personalcontext
);

$user = file_prepare_standard_editor($user, 'description', $editoroptions, $personalcontext, 'user', 'profile', 0);
// Prepare filemanager draft area.
$draftitemid = 0;
$filemanagercontext = $editoroptions['context'];
$filemanageroptions = array('maxbytes'       => $CFG->maxbytes,
                             'subdirs'        => 0,
                             'maxfiles'       => 1,
                             'accepted_types' => 'optimised_image');
file_prepare_draft_area($draftitemid, $filemanagercontext->id, 'user', 'newicon', 0, $filemanageroptions);
$user->imagefile = $draftitemid;
// Create form.
$userform = new user_edit_form(new moodle_url($PAGE->url, array('returnto' => $returnto)), array(
    'editoroptions' => $editoroptions,
    'filemanageroptions' => $filemanageroptions,
    'user' => $user));

$emailchanged = false;

// Deciding where to send the user back in most cases.
if ($returnto === 'profile') {
    if ($course->id != SITEID) {
        $returnurl = new moodle_url('/user/view.php', array('id' => $user->id, 'course' => $course->id));
    } else {
        $returnurl = new moodle_url('/user/profile.php', array('id' => $user->id));
    }
} else {
    $returnurl = new moodle_url('/user/preferences.php', array('userid' => $user->id));
}

if ($userform->is_cancelled()) {
    redirect($returnurl);
} else if ($usernew = $userform->get_data()) {

    $emailchangedhtml = '';

    if ($CFG->emailchangeconfirmation) {
        // Users with 'moodle/user:update' can change their email address immediately.
        // Other users require a confirmation email.
        if (isset($usernew->email) and $user->email != $usernew->email && !has_capability('moodle/user:update', $systemcontext)) {
            $a = new stdClass();
            $emailchangedkey = random_string(20);
            set_user_preference('newemail', $usernew->email, $user->id);
            set_user_preference('newemailkey', $emailchangedkey, $user->id);
            set_user_preference('newemailattemptsleft', 3, $user->id);

            $a->newemail = $emailchanged = $usernew->email;
            $a->oldemail = $usernew->email = $user->email;

            $emailchangedhtml = $OUTPUT->box(get_string('auth_changingemailaddress', 'auth', $a), 'generalbox', 'notice');
            $emailchangedhtml .= $OUTPUT->continue_button($returnurl);
        }
    }

    $authplugin = get_auth_plugin($user->auth);

    $usernew->timemodified = time();

    // Description editor element may not exist!
    if (isset($usernew->description_editor) && isset($usernew->description_editor['format'])) {
        $usernew = file_postupdate_standard_editor($usernew, 'description', $editoroptions, $personalcontext, 'user', 'profile', 0);
    }

    // Pass a true old $user here.
    if (!$authplugin->user_update($user, $usernew)) {
        // Auth update failed.
        throw new \moodle_exception('cannotupdateprofile');
    }

    // Update user with new profile data.
    user_update_user($usernew, false, false);

    // Update preferences.
    useredit_update_user_preference($usernew);

    // Update interests.
    if (isset($usernew->interests)) {
        useredit_update_interests($usernew, $usernew->interests);
    }

    // Update user picture.
    if (empty($CFG->disableuserimages)) {
        core_user::update_picture($usernew, $filemanageroptions);
    }

    // Update mail bounces.
    useredit_update_bounces($user, $usernew);

    // Update forum track preference.
    useredit_update_trackforums($user, $usernew);

    // Save custom profile fields data.
    profile_save_data($usernew);

    // Trigger event.
    \core\event\user_updated::create_from_userid($user->id)->trigger();

    // If email was changed and confirmation is required, send confirmation email now to the new address.
    if ($emailchanged !== false && $CFG->emailchangeconfirmation) {
        $tempuser = $DB->get_record('user', array('id' => $user->id), '*', MUST_EXIST);
        $tempuser->email = $emailchanged;

        $a = new stdClass();
        $a->url = $CFG->wwwroot . '/user/emailupdate.php?key=' . $emailchangedkey . '&id=' . $user->id;
        $a->site = format_string($SITE->fullname, true, array('context' => context_course::instance(SITEID)));
        $a->fullname = fullname($tempuser, true);
        $a->supportemail = $OUTPUT->supportemail();

        $emailupdatemessage = get_string('emailupdatemessage', 'auth', $a);
        $emailupdatetitle = get_string('emailupdatetitle', 'auth', $a);

        // Email confirmation directly rather than using messaging so they will definitely get an email.
        $noreplyuser = core_user::get_noreply_user();
        if (!$mailresults = email_to_user($tempuser, $noreplyuser, $emailupdatetitle, $emailupdatemessage)) {
            die("could not send email!");
        }
    }

    // Reload from db, we need new full name on this page if we do not redirect.
    $user = $DB->get_record('user', array('id' => $user->id), '*', MUST_EXIST);

    if ($USER->id == $user->id) {
        // Override old $USER session variable if needed.
        foreach ((array)$user as $variable => $value) {
            if ($variable === 'description' or $variable === 'password') {
                // These are not set for security nad perf reasons.
                continue;
            }
            $USER->$variable = $value;
        }
        // Preload custom fields.
        profile_load_custom_fields($USER);
    }

    if (is_siteadmin() and empty($SITE->shortname)) {
        // Fresh cli install - we need to finish site settings.
        redirect(new moodle_url('/admin/index.php'));
    }

    if (!$emailchanged || !$CFG->emailchangeconfirmation) {
        redirect(new moodle_url('/'));
//        redirect($returnurl, get_string('changessaved'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
}


// Display page header.
$streditmyprofile = get_string('editmyprofile');
$strparticipants  = get_string('participants');
$userfullname     = fullname($user, true);

$PAGE->set_title("$course->shortname: $streditmyprofile");
$PAGE->set_heading($userfullname);

echo $OUTPUT->header();
echo $OUTPUT->heading($userfullname);

if ($emailchanged) {
    echo $emailchangedhtml;
} else {
    // Finally display THE form.
    $userform->display();
}
$listCity = vtcLoadListCity([]);
echo ' <div class="modal fade custom-signup-city" tabindex="-1" aria-labelledby="myLargeModalLabel"  aria-hidden="true" style="display: none;">
    <div class="modal-dialog">
        <div class="modal-content" style="border:unset">
            <div class="modal-header d-flex justify-content-between align-items-center" style="background: #1890FF;padding-right: 30px">
                <div class="input-group">
                    <input style="padding:20px; border-color: white" type="text" class="form-control custom-input-search" placeholder="'. get_string('citydesc','theme_moove').'" aria-label="'. get_string('citydesc','theme_moove').'" aria-describedby="basic-addon2">
                    <div class="input-group-append">
                        <button style="border-color: white;color: white" class="btn" type="button" data-dismiss="modal" aria-label="Close">Chọn</button>
                    </div>
                </div>
            </div>
            <ul style="display:grid;grid-template-columns: repeat(2, minmax(0, 1fr));padding:25px;gap:20px;max-height: 400px;overflow-y: auto;">';
                foreach ($listCity as $city){
                    $check ='';
                    if($city['city'] == $user->city){
                        $check='checked';
                    }
                    echo '<li class="custom-option-city" style="list-style-type: none">
                                    <div class="d-flex flex-row align-items-center justify-content-between" style="border-bottom: 1px solid #D9D9D9;padding-bottom: 14px">
                                        <div class="name-city" style="color: #000;font-size: 16px;font-weight: 400;">
                                           '. $city['city'].'
                                        </div>
                                        <label class="custom-label-radiobutton">
                                            <input type="radio" name="city_radio" '.$check.' style="display: flex;width: 24px;height: 24px;padding: 2px;justify-content: center;align-items: center;flex-shrink: 0;" />
                                            <span class="checkmark"></span>
                                        </label>
                                    </div>
                                </li> ';
                }
                echo '</ul>
                        </div>
                        <!-- Modal Content: ends -->
                    </div>
                </div>';
echo '<script>
    $(document).ready(function(){
        $("input").on("click",function(){
            $(this).removeClass("error-input");
            $(this).parent().parent().children(".error").addClass("hidden");
        });
        $customoptioncity = $(".custom-option-city");
        $(".custom-signup-city .custom-input-search").on("keyup", function (e) {
            if (e.which === 13) {
            } else {
                var text = $(this).val().toUpperCase().trim();
                if ($(this).val().length === 0) {
                    $customoptioncity.each(function () {
                        $(this).show();
                    });
                } else {
                    $customoptioncity.each(function () {
                        console.log($(this).children().children(".name-city").html().trim(),text);
                        if ($(this).children().children(".name-city").html().trim().toUpperCase().indexOf(text) > -1) {
                            $(this).removeClass("hidden");
                        } else {
                            $(this).addClass("hidden");
                        }
                    });
                }
            }
        });
        $(".checkmark").on("click", function (e) {
            $("input[name=\"city\"]").val($(this).parent().parent().children(".name-city").html().trim())
        })
    });
    
    var show  = $(".form-control-showpassword");
    var inputpassword =$(".password");
    show.on("click",function(){
        if(inputpassword.attr("type") === "text"){
            inputpassword.attr("type", "password");
            show.removeClass("fa-eye");
            show.addClass("fa-eye-slash");
        }else{
            show.addClass("fa-eye");
            show.removeClass("fa-eye-slash");
            inputpassword.attr("type", "text");
        }
    })
   
</script>';

// And proper footer.
echo $OUTPUT->footer();
