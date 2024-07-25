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

require_once "../../config.php";
require_once "{$CFG -> libdir}/authlib.php";

global $SESSION;

$client = optional_param("client", "web", PARAM_RAW);

$params = Array( "client" => $client );

if ($client === "mobileapp") {
	// It is required for mobile app to provide a passport.
	$passport = required_param("passport", PARAM_RAW);
	$urlscheme = optional_param("urlscheme", "moodlemobile", PARAM_NOTAGS);

	$params["passport"] = $passport;
	$params["urlscheme"] = $urlscheme;
} else {
	// Do a simple session check on web client.
	require_sesskey();
}

$codeVerifier = bin2hex(random_bytes(64));
$codeChallenge = strtr(rtrim(base64_encode(hash('sha256', $codeVerifier, true)), '='), '+/', '-_');

$state = bin2hex(random_bytes(16));
$appId = get_config("auth_zalo", "app_id");
$params["state"] = $state;
$redirect = new moodle_url("/auth/zalo/callback.php", $params);

if (empty($appId))
	throw new moodle_exception("empty_appid", "auth_zalo");

$SESSION -> zaloCodeVerifier = $codeVerifier;

$url = new moodle_url(get_config("auth_zalo", "api_base") . "/permission", Array(
	"app_id" => $appId,
	"redirect_uri" => $redirect -> out(false),
	"state" => $state,
	"code_challenge" => $codeChallenge
));

redirect($url);
