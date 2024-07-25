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

require_once('../../../config.php');
require_once($CFG->libdir.'/gdlib.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/user/editadvanced_form.php');
require_once($CFG->dirroot.'/user/editlib.php');
require_once($CFG->dirroot.'/user/profile/lib.php');
require_once($CFG->dirroot.'/user/lib.php');
require_once($CFG->dirroot.'/webservice/lib.php');
global $SESSION;
defined('MOODLE_INTERNAL') || die();
$id     = optional_param('id', $USER->id, PARAM_INT);    // User id; -1 if creating new user.
$course = optional_param('course', SITEID, PARAM_INT);   // Course id (defaults to Site).
$returnto = optional_param('returnto', null, PARAM_ALPHA);  // Code determining where to return to after save.
$tab = optional_param('tab', 'profile', PARAM_RAW);

$PAGE->set_url('/user/editadvanced.php', array('course' => $course, 'id' => $id));

$course = $DB->get_record('course', array('id' => $course), '*', MUST_EXIST);
if ($course->id == SITEID) {
    require_login();
    $PAGE->set_context(context_system::instance());
} else {
    require_login($course);
}
$PAGE->set_pagelayout('admin');
$PAGE->add_body_class('limitedwidth');

$coursecontext = context_system::instance();   // SYSTEM context.
// Editing existing user.
require_capability('moodle/user:update', $coursecontext);
$user = $DB->get_record('user', array('id' => $id), '*', MUST_EXIST);
$PAGE->set_context(context_user::instance($user->id));
$PAGE->navbar->includesettingsbase = true;
if ($node = $PAGE->navigation->find('profile', navigation_node::TYPE_ROOTNODE)) {
    $node->force_open();
}
// Load user preferences.
useredit_load_preferences($user);

// Load custom profile fields data.
profile_load_data($user);

// User interests.
$user->interests = core_tag_tag::get_item_tags_array('core', 'user', $id);
$usercontext = context_user::instance($user->id);
$editoroptions = array(
    'maxfiles'   => EDITOR_UNLIMITED_FILES,
    'maxbytes'   => $CFG->maxbytes,
    'trusttext'  => false,
    'forcehttps' => false,
    'context'    => $usercontext
);

$user = file_prepare_standard_editor($user, 'description', $editoroptions, $usercontext, 'user', 'profile', 0);

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
$userform = new user_editadvanced_form(new moodle_url($PAGE->url, array('returnto' => $returnto)), array(
    'editoroptions' => $editoroptions,
    'filemanageroptions' => $filemanageroptions,
    'user' => $user));

// Deciding where to send the user back in most cases.
if ($course->id != SITEID) {
    $returnurl = new moodle_url('/user/view.php', array('id' => $user->id, 'course' => $course->id));
} else {
    $returnurl = new moodle_url('/user/profile.php', array('tab' => $tab));
}
$authplugin = get_auth_plugin($user->auth);
$usercreated = false;
$SESSION->paramsubmit = $frm = data_submitted();
$frm->id = $id;
if($tab == 'profile') {
    // User editing self.
    unset($frm->auth); // Can not change/remove.
    $errormsg = '';
    $errorcode = 0;
    $fullname = $frm->fullname;
    $frm->lastname = (strpos($fullname, ' ') === false) ? '' : preg_replace('#.*\s([\w-]*)$#', '$1', $fullname);
    $frm->firstname = trim( preg_replace('#'.preg_quote($frm->lastname,'#').'#', '', $fullname ) );

    if (!$frm->fullname) {
        $errormsg = get_string("fullname_required", "theme_moove");
        $errorcode = 1;
    } elseif (!$frm->email) {
        $errormsg = get_string("email_required", "theme_moove");
        $errorcode = 3;
    } elseif (!filter_var($frm->email, FILTER_VALIDATE_EMAIL)) {
        $errormsg = get_string("email_wrong_regex", "theme_moove");
        $errorcode = 3;
    }
    if ($errormsg) {
        // Validate user form and return if not match
        $SESSION->errormsg = $errormsg;
        $SESSION->errorcode = $errorcode;
        redirect($returnurl);
    }
    $frm->timemodified = time();
    $usercontext = context_user::instance($frm->id);
    // Update preferences.
    useredit_update_user_preference($frm);
    // Update tags.
    if (empty($USER->newadminuser) && isset($frm->interests)) {
        useredit_update_interests($frm, $frm->interests);
    }
    // Update user picture.
    if (empty($USER->newadminuser)) {
        core_user::update_picture($frm, $filemanageroptions);
    }
    // Update mail bounces.
    useredit_update_bounces($user, $frm);
    // Update forum track preference.
    useredit_update_trackforums($user, $frm);
    // Save custom profile fields data.
    profile_save_data($frm);

// Reload from db.
    $usernew = $DB->get_record('user', array('id' => $frm->id));

// Trigger update/create event, after all fields are stored.
    \core\event\user_updated::create_from_userid($usernew->id)->trigger();

    if ($success) {
        // Override old $USER session variable.
        foreach ((array)$usernew as $variable => $value) {
            if ($variable === 'description' or $variable === 'password') {
                // These are not set for security nad perf reasons.
                continue;
            }
            $USER->$variable = $value;
        }
        // Preload custom fields.
        profile_load_custom_fields($USER);

        if (!empty($USER->newadminuser)) {
            unset($USER->newadminuser);
            // Apply defaults again - some of them might depend on admin user info, backup, roles, etc.
            admin_apply_default_settings(null, false);
            // Admin account is fully configured - set flag here in case the redirect does not work.
            unset_config('adminsetuppending');
            // Redirect to admin/ to continue with installation.
            redirect("$CFG->wwwroot/$CFG->admin/");
        } else if (empty($SITE->fullname)) {
            // Somebody double clicked when editing admin user during install.
            redirect("$CFG->wwwroot/$CFG->admin/");
        } else {
            redirect($returnurl, get_string('changessaved'), null, \core\output\notification::NOTIFY_SUCCESS);
        }
    } else {
        redirect($returnurl, get_string('contactdpoviaprivacypolicy', 'tool_dataprivacy'), 0, \core\output\notification::NOTIFY_ERROR);
    }

} elseif($tab == 'changepassword') {
    // Change password
    $user = false;
    $errormsg = '';
    $errorcode = 0;

    if (!$frm->old_pass) {
        $errormsg = get_string("password_required", "theme_moove");
        $errorcode = 1;
    } elseif (!$frm->new_pass) {
        $errormsg = get_string("new_password_required", "theme_moove");
        $errorcode = 2;
    } elseif (!$frm->confirm_new_pass) {
        $errormsg = get_string("confirm_new_password_required", "theme_moove");
        $errorcode = 3;
    } elseif ($frm->new_pass != $frm->confirm_new_pass) {
        $errormsg = get_string("new_password_wrong_confirm_new_password", "theme_moove");
        $errorcode = 3;
    } elseif (!preg_match("/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{8,20}$/", $frm->new_pass)) {
        $errormsg = get_string("password_wrong_regex", "theme_moove");
        $errorcode = 2;
    }
    if ($errormsg) {
        // Validate 3 field password and return if not match
        $SESSION->errormsg = $errormsg;
        $SESSION->errorcode = $errorcode;
        redirect($returnurl);
    }
    $logintoken = isset($frm->logintoken) ? $frm->logintoken : '';

    if (!empty($SESSION->has_timed_out)) {
        $session_has_timed_out = true;
        unset($SESSION->has_timed_out);
    } else {
        $session_has_timed_out = false;
    }
    $user = authenticate_user_login($frm->username, $frm->old_pass, false, $errorcode, $logintoken);
    if($user) {
        $userupdatepass = new stdClass();
        $userupdatepass->id = $id;
        $frm->password = $frm->new_pass;
        unset($frm->tab);
        unset($frm->returnto);
        unset($frm->old_pass);
        unset($frm->new_pass);
        unset($frm->confirm_new_pass);
        user_update_user($frm, true, false);
        if (!empty($CFG->passwordchangelogout)) {
            // We can use SID of other user safely here because they are unique,
            // the problem here is we do not want to logout admin here when changing own password.
            \core\session\manager::kill_user_sessions($frm->id, session_id());
        }
        if (!empty($frm->signoutofotherservices)) {
            webservice::delete_user_ws_tokens($frm->id);
        }
        if ($user->id == $USER->id) {
            // Override old $USER session variable.
            foreach ((array)$frm as $variable => $value) {
                if ($variable === 'description' or $variable === 'password') {
                    // These are not set for security nad perf reasons.
                    continue;
                }
                $USER->$variable = $value;
            }
            // Preload custom fields.
            profile_load_custom_fields($USER);

            if (!empty($USER->newadminuser)) {
                unset($USER->newadminuser);
                // Apply defaults again - some of them might depend on admin user info, backup, roles, etc.
                admin_apply_default_settings(null, false);
                // Admin account is fully configured - set flag here in case the redirect does not work.
                unset_config('adminsetuppending');
                // Redirect to admin/ to continue with installation.
                redirect("$CFG->wwwroot/$CFG->admin/");
            } else if (empty($SITE->fullname)) {
                // Somebody double clicked when editing admin user during install.
                redirect("$CFG->wwwroot/$CFG->admin/");
            } else {
                redirect($returnurl, get_string('changessaved'), null, \core\output\notification::NOTIFY_SUCCESS);
            }
        }
    } else {
        if ($errorcode == AUTH_LOGIN_UNAUTHORISED) {
            $authenerrormsg = get_string("unauthorisedlogin", "", $frm->username);
            $errorcode = 1;
        } elseif($errorcode == 3) {
            $authenerrormsg = get_string("password_wrong", "theme_moove");
            $errorcode = 1;
        }
    }
    if ($session_has_timed_out and !data_submitted()) {
        $authenerrormsg = get_string('sessionerroruser', 'error');
        $errorcode = 1;
    }
    if ($authenerrormsg) {
        $SESSION->authenerrormsg = $authenerrormsg;
        $SESSION->authenerrorcode = $errorcode;
    }
    redirect($returnurl);
}