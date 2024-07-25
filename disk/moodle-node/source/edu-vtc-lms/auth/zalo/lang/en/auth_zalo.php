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
 * Strings for component 'auth_zalo', language 'en'.
 *
 * @package		auth_zalo
 * @copyright 	2023 Videa {@link https://videabiz.com}
 * @author		Brindley <brindley@videabiz.com>
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string["pluginname"] = "Zalo";
$string["setting_help"] = "Here you can change the configuration of Zalo authentication plugin. You can find most of your information in the <code>settings</code> tab.";
$string["complete_registration"] = "Complete Registration";
$string["complete_registration_sub"] = "Please fill in missing information to continute to <b>{\$a}</b>";

$string["config_app_id"] = "Application ID";
$string["config_app_id_help"] = "Your application ID";
$string["config_secret_key"] = "Secret Key";
$string["config_secret_key_help"] = "Your application Secret key";
$string["config_api_base"] = "Authentication Endpoint";
$string["config_api_base_help"] = "The authorization endpoint, used to generate login URL and redirect user to it to perform login. You probally don't need to change this setting.";

$string["empty_appid"] = "Your Zalo application ID is empty!";
$string["no_active_session"] = "No Zalo OAuth2 session is currently in active!";
$string["curl_error"] = "Request to Zalo server generated an error!";
$string["auth_error"] = "Authentication to Zalo failed: {\$a->error_name}";
$string["info_error"] = "An error occured while fetching user's info: {\$a->error_name}";
