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
 * Open ID authentication.
 *
 * @package		auth_zalo
 * @copyright 	2023 Videa {@link https://videabiz.com}
 * @author		Brindley <brindley@videabiz.com>
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined("MOODLE_INTERNAL") || die();

require_once "{$CFG -> libdir}/authlib.php";

class auth_plugin_zalo extends \auth_plugin_base {
	const ICON_URL = "https://upload.wikimedia.org/wikipedia/commons/thumb/9/91/Icon_of_Zalo.svg/1024px-Icon_of_Zalo.svg.png";

	public function __construct() {
        $this -> authtype = "zalo";
        $this -> config = get_config("auth_zalo");
    }

	/**
     * Return a list of identity providers to display on the login page.
     *
     * @param	string|moodle_url	$wantsurl		The requested URL.
     * @return	array								List of arrays with keys url, iconurl and name.
     */
    public function loginpage_idp_list($wantsurl) {
		if (empty(get_config("auth_zalo", "app_id")) || empty(get_config("auth_zalo", "secret_key")))
			return [];

		return Array(
			Array(
				"url" => new moodle_url("/auth/zalo/login.php", Array( "sesskey" => sesskey() )),
				"iconurl" => static::ICON_URL,
				"name" => get_string("pluginname", "auth_zalo")
			)
		);
    }

	/**
     * Don't let user login with username and password
     *
     * @param	string	$username	The username
     * @param	string	$password	The password
     * @return	bool				Authentication success or failure.
     */
    function user_login($username, $password) {
        return false;
    }

	function can_change_password() {
		return false;
	}

	function can_signup() {
        return false;
    }

	function can_edit_profile() {
		return true;
	}

	function is_captcha_enabled() {
		return false;
	}

	function can_reset_password() {
		return false;
	}

	public function update_picture($user, $picture) {
		global $DB, $CFG;

		$context = context_user::instance($user -> id, MUST_EXIST);
		$hash = md5($picture);

		require_once "{$CFG -> libdir}/gdlib.php";
		$file = fopen($picture, "r");

		if ($file) {
			// Copy to temp file
			$temp = tempnam(sys_get_temp_dir(), "PIC_");
			file_put_contents($temp, $file);

			// Drop all images in area.
			$fs = get_file_storage();
			$fs -> delete_area_files($context -> id, "user", "icon");

			$newpicture = (int) process_new_icon($context, "user", "icon", 0, $temp);

			// Delete temporary file.
			@unlink($temp);

			$DB -> set_field("user", "picture", $newpicture, Array("id" => $user -> id));
		}
	}
}
