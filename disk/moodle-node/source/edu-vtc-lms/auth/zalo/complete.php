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
 * Complete user registeration.
 *
 * @package		auth_zalo
 * @copyright 	2023 Videa {@link https://videabiz.com}
 * @author		Brindley <brindley@videabiz.com>
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once "../../config.php";
require_once "{$CFG -> dirroot}/user/lib.php";
require_once "{$CFG -> dirroot}/user/profile/lib.php";

use auth_zalo\register_complete_form;

global $SESSION, $USER, $DB;

$action = required_param("action", PARAM_URL);

if (!user_not_fully_set_up($USER, true))
	redirect($action);

$context = context_system::instance();
$PAGE -> set_url("/auth/zalo/complete.php", Array( "action" => $action ));
$PAGE -> set_context($context);
$PAGE -> set_pagelayout("login");
$PAGE -> set_title(get_string("complete_registration", "auth_zalo"));

$form = new register_complete_form(null, Array( "action" => $action ));

if ($form -> is_cancelled()) {
	require_logout();
	redirect(get_login_url());
} else if ($data = $form -> get_data()) {
	foreach ($data as $key => $value) {
		// Make sure this form does not change any sensitive fields.
		if (in_array($key, [ "username", "password", "auth", "id", "action", "mnethostid", "idnumber" ]))
			continue;

		$USER -> {$key} = $value;
	}

	// Update user with new profile data.
	user_update_user($USER, false, false);

	// Update preferences.
	useredit_update_user_preference($USER);

	// Plugins can perform post sign up actions once data has been validated.
	core_login_post_signup_requests($USER);

	// Save custom profile fields data.
    profile_save_data($USER);

    // Trigger event.
    \core\event\user_updated::create_from_userid($USER -> id)
		-> trigger();

	redirect($action);
}

$form -> set_data($USER);

echo $OUTPUT -> header();
echo $form -> render();
echo $OUTPUT -> footer();
