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
 * Admin settings and defaults.
 *
 * @package		auth_zalo
 * @copyright 	2023 Videa {@link https://videabiz.com}
 * @author		Brindley <brindley@videabiz.com>
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined("MOODLE_INTERNAL") || die;

if ($ADMIN -> fulltree) {
	/** @var admin_settingpage $settings */

    $settings -> add(new admin_setting_heading("auth_zalo/pluginname", get_string("pluginname", "auth_zalo"), get_string("setting_help", "auth_zalo")));

	$settings -> add(new admin_setting_configtext(
		"auth_zalo/app_id",
		get_string("config_app_id", "auth_zalo"),
		get_string("config_app_id_help", "auth_zalo"),
		""
	));

	$settings -> add(new admin_setting_configtext(
		"auth_zalo/secret_key",
		get_string("config_secret_key", "auth_zalo"),
		get_string("config_secret_key_help", "auth_zalo"),
		""
	));

	$settings -> add(new admin_setting_configtext(
		"auth_zalo/api_base",
		get_string("config_api_base", "auth_zalo"),
		get_string("config_api_base_help", "auth_zalo"),
		"https://oauth.zaloapp.com/v4"
	));
}
