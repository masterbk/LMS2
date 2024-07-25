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
 * Handle login callback.
 *
 * @package		auth_zalo
 * @copyright 	2023 Videa {@link https://videabiz.com}
 * @author		Brindley <brindley@videabiz.com>
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once "../../config.php";
require_once "{$CFG -> libdir}/authlib.php";
require_once "{$CFG -> libdir}/filelib.php";
require_once "{$CFG -> dirroot}/user/lib.php";
require_once "{$CFG -> dirroot}/user/profile/lib.php";
require_once "auth.php";

define("ZALO_GRAPHQL_BASE", "https://graph.zalo.me/v2.0");

global $SESSION, $USER;

$code = required_param("code", PARAM_RAW);
$client = optional_param("client", "web", PARAM_RAW);

$url = new moodle_url("/auth/zalo/callback.php", Array(
	"code" => $code,
	"client" => $client
));

if ($client === "mobileapp") {
	// It is required for mobile app to provide a passport.
	$passport = required_param("passport", PARAM_RAW);
	$urlscheme = optional_param("urlscheme", "moodlemobile", PARAM_NOTAGS);

	$url -> params(Array(
		"passport" => $passport,
		"urlscheme" => $urlscheme
	));
}

if (!isloggedin()) {
	if (empty($SESSION -> zaloCodeVerifier))
		throw new moodle_exception("no_active_session", "auth_zalo");

	$appId = get_config("auth_zalo", "app_id");
	$secretKey = get_config("auth_zalo", "secret_key");

	$loginUrl = new moodle_url("/login/index.php");

	// Verify token
	$request = new curl();
	$authPlugin = new auth_plugin_zalo();

	$data = http_build_query(Array(
		"code" => $code,
		"app_id" => $appId,
		"grant_type" => "authorization_code",
		"code_verifier" => $SESSION -> zaloCodeVerifier
	), "", "&");

	$auth = $request -> post(
		get_config("auth_zalo", "api_base") . "/access_token",
		$data,
		Array(
			"CURLOPT_RETURNTRANSFER" => true,
			"CURLOPT_MAXREDIRS" => 10,
			"CURLOPT_TIMEOUT" => 60,
			"CURLOPT_HTTP_VERSION" => CURL_HTTP_VERSION_1_1,
			"CURLOPT_HTTPHEADER" => Array(
				"Content-Type: application/x-www-form-urlencoded",
				"secret_key: {$secretKey}"
			),
		)
	);

	if ($request -> errno || !$request)
		throw new moodle_exception("curl_error", "auth_zalo", $request -> error);

	$auth = json_decode($auth);
	unset($SESSION -> zaloCodeVerifier);

	if (!empty($auth -> error_name))
		throw new moodle_exception("auth_error", "auth_zalo", $loginUrl, $auth, $auth -> error_description);

	if (empty($auth -> access_token))
		throw new moodle_exception("empty_token", "auth_zalo", $loginUrl, $auth, $auth -> error_description);

	$token = $auth -> access_token;

	$info = $request -> get(
		ZALO_GRAPHQL_BASE . "/me",
		Array( "fields" => "id,name,picture" ),
		Array(
			"CURLOPT_RETURNTRANSFER" => true,
			"CURLOPT_MAXREDIRS" => 10,
			"CURLOPT_TIMEOUT" => 60,
			"CURLOPT_HTTP_VERSION" => CURL_HTTP_VERSION_1_1,
			"CURLOPT_HTTPHEADER" => Array(
				"access_token: {$token}"
			),
		)
	);

	if ($request -> errno || !$request)
		throw new moodle_exception("curl_error", "auth_zalo", $request -> error);

	$info = json_decode($info);

	if (!empty($info -> error_name))
		throw new moodle_exception("info_error", "auth_zalo", $loginUrl, $info, $info -> error_description);

	$user = $DB -> get_record("user", Array( "username" => $info -> id ));

	if (empty($user)) {
		$parts = explode(" ", $info -> name);

		if (count($parts) <= 2) {
			$firstname = implode(" ", $parts);
			$lastname = array_shift($parts);
		} else {
			$firstname = implode(" ", array_splice($parts, -2));
			$lastname = implode(" ", $parts);
		}

		$user = new stdClass;
		$user -> auth = "zalo";
		$user -> username = $info -> id;
		$user -> firstname = $firstname;
		$user -> lastname = $lastname;
		$user -> password = "";
		$user -> confirmed = 1;
		$user -> mnethostid = 1;
		$user -> country = "VN";
		$user -> lang = "vi";
		$user -> timezone = "Asia/Ho_Chi_Minh";
		$user -> timecreated = time();
		$user -> timemodified = time();
		$user -> currentlogin = 0;
		$user -> lastlogin = 0;

		$user -> id = user_create_user($user, false);
		$user = $DB -> get_record("user", Array( "id" => $user -> id ));
		profile_save_data($user);
	}

	complete_user_login($user);
	\core\session\manager::apply_concurrent_login_limit($user -> id, session_id());
	$authPlugin -> update_picture($user, $info -> picture -> data -> url);
}

// Additional step required before redirecting user.
if (user_not_fully_set_up($USER, true)) {
	$completeurl = new moodle_url("/auth/zalo/complete.php", Array( "action" => $url ));
	redirect($completeurl);
}

// Make sure user is properly logged in.
require_login(0, false);

if ($client === "mobileapp") {
	$serviceshortname = "moodle_mobile_app";

	// Check if the service exists and is enabled.
	$service = $DB -> get_record("external_services", Array(
		"shortname" => $serviceshortname,
		"enabled" => 1
	));

	if (empty($service))
		throw new moodle_exception("servicenotavailable", "webservice");

	// Get an existing token or create a new one.
	$timenow = time();
	$token = \core_external\util::generate_token_for_current_user($service);
	$privatetoken = $token -> privatetoken;
	\core_external\util::log_token_request($token);

	// Don't return the private token if the user didn"t just log in and a new token wasn't created.
	if (empty($SESSION -> justloggedin) && $token -> timecreated < $timenow)
		$privatetoken = null;

	$siteadmin = has_capability("moodle/site:config", context_system::instance(), $USER -> id);

	// Passport is generated in the mobile app, so the app opening can be validated using that variable.
	// Passports are valid only one time, it's deleted in the app once used.
	$siteid = md5($CFG -> wwwroot . $passport);
	$apptoken = "{$siteid}:::{$token -> token}";

	if ($privatetoken and is_https() and !$siteadmin)
		$apptoken .= ":::{$privatetoken}";

	$apptoken = base64_encode($apptoken);

	// Redirect using the custom URL scheme checking first if a URL scheme is forced in the site settings.
	$forcedurlscheme = get_config("tool_mobile", "forcedurlscheme");

	if (!empty($forcedurlscheme))
		$urlscheme = $forcedurlscheme;

	$location = "$urlscheme://token=$apptoken";

	if (core_useragent::is_ios()) {
		// For iOS 10 onwards, we have to simulate a user click.
		// If we come from the confirmation page, we should display a nicer page.

		$PAGE -> set_context(context_system::instance());
		$PAGE -> set_heading($COURSE -> fullname);
		$PAGE -> set_url($url);

		echo $OUTPUT -> header();

		$confirmedstr = get_string("confirmed");
		$PAGE -> navbar -> add($confirmedstr);
		$PAGE -> set_title($confirmedstr);
		echo $OUTPUT -> notification($confirmedstr, \core\output\notification::NOTIFY_SUCCESS);
		echo $OUTPUT -> box_start("generalbox centerpara boxwidthnormal boxaligncenter");
		echo $OUTPUT -> single_button(new moodle_url("/course/"), get_string("courses"));
		echo $OUTPUT -> box_end();

		$notice = get_string("clickheretolaunchtheapp", "tool_mobile");

		echo html_writer::link($location, $notice, array("id" => "launchapp"));
		echo html_writer::script(
			"window.onload = function() {
				document.getElementById('launchapp').click();
			};"
		);

		echo $OUTPUT -> footer();
		die;
	} else {
		// For Android a http redirect will do fine.
		header("Location: $location");
		die;
	}
}

redirect("/");
