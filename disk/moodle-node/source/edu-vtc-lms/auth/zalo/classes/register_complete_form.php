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
 * Register completion form.
 *
 * @package		auth_zalo
 * @copyright 	2023 Videa {@link https://videabiz.com}
 * @author		Brindley <brindley@videabiz.com>
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_zalo;

use moodleform;
use core_user;

defined("MOODLE_INTERNAL") || die();

global $CFG;

require_once "{$CFG -> libdir}/formslib.php";
require_once "{$CFG -> dirroot}/login/lib.php";
require_once "{$CFG -> dirroot}/user/profile/lib.php";
require_once "{$CFG -> dirroot}/user/editlib.php";

/**
 * Register completion form.
 *
 * @package		auth_zalo
 * @copyright 	2023 Videa {@link https://videabiz.com}
 * @author		Brindley <brindley@videabiz.com>
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class register_complete_form extends moodleform {
	public function definition() {
		global $USER, $CFG;

		$mform = $this -> _form;
		$cdata = $this -> _customdata;

		if (!defined("VLOOM_INIT"))
			$mform -> addElement("header", "complete_registration", get_string("complete_registration", "auth_zalo"), "");

		$mform -> addElement("hidden", "action", $cdata["action"]);
		$mform -> setType("action", PARAM_URL);

		$mform -> addElement("text", "username", get_string("username"), 'maxlength="100" size="12" autocapitalize="none" disabled readonly');
		$mform -> setType("username", PARAM_RAW);

		$mform -> addElement("text", "email", get_string("email"), 'maxlength="100" size="25"');
		$mform -> setType("email", core_user::get_property_type("email"));
		$mform -> addRule("email", get_string("missingemail"), "required", null, "client");
		$mform -> setForceLtr("email");

		$mform -> addElement("text", "email2", get_string("emailagain"), 'maxlength="100" size="25"');
		$mform -> setType("email2", core_user::get_property_type("email"));
		$mform -> addRule("email2", get_string("missingemail"), "required", null, "client");
		$mform -> setForceLtr("email2");

		$namefields = useredit_get_required_name_fields();

		foreach ($namefields as $field) {
			$mform -> addElement("text", $field, get_string($field), 'maxlength="100" size="30"');
			$mform -> setType($field, core_user::get_property_type("firstname"));
			$stringid = "missing{$field}";

			if (!get_string_manager() -> string_exists($stringid, "moodle"))
				$stringid = "required";

			$mform -> addRule($field, get_string($stringid), "required", null, "client");
		}

		$mform -> addElement("text", "city", get_string("city"), 'maxlength="120" size="20"');
		$mform -> setType("city", core_user::get_property_type("city"));

		if (!empty($CFG -> defaultcity))
			$mform -> setDefault("city", $CFG -> defaultcity);

		$country = get_string_manager() -> get_list_of_countries();
		$default_country[""] = get_string("selectacountry");
		$country = array_merge($default_country, $country);
		$mform -> addElement("select", "country", get_string("country"), $country);

		if (!empty($CFG -> country)) {
			$mform -> setDefault("country", $CFG -> country);
		} else {
			$mform -> setDefault("country", "");
		}

		profile_signup_fields($mform);

		// Hook for plugins to extend form definition.
		core_login_extend_signup_form($mform);

		// Add "Agree to sitepolicy" controls. By default it is a link to the policy text and a checkbox but
		// it can be implemented differently in custom sitepolicy handlers.
		$manager = new \core_privacy\local\sitepolicy\manager();
		$manager -> signup_form($mform);

		// buttons
		$this -> set_display_vertical();
		$this -> add_action_buttons(false, get_string("complete_registration", "auth_zalo"));
	}

	function definition_after_data(){
        $mform = $this -> _form;
        $mform -> applyFilter("username", "trim");

        foreach (useredit_get_required_name_fields() as $field)
            $mform -> applyFilter($field, "trim");
    }

	/**
     * Validate user supplied data on the signup form.
     *
     * @param	array	$data	Array of ("fieldname" => value) of submitted data
     * @param	array	$files	Array of uploaded files "element_name" => tmp_file_path
     * @return	array	Array of "element_name"=>"error_description" if there are errors,
     * 					or an empty array if everything is OK (true allowed for backwards compatibility too).
     */
    public function validation($data, $files) {
		$errors = parent::validation($data, $files);

		if (strcasecmp($data["email"], $data["email2"]) !== 0)
			$errors["email2"] = get_string("invalidemail");

		return $errors;
    }
}
