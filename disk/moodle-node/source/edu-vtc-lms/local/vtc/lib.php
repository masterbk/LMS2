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
 * This file contains the moodle hooks for the assign module.
 *
 * It delegates most functions to the assignment class.
 *
 * @package   local_vtc
 * @copyright Videa
 * @author    Bob Nguyen
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

function local_vtc_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {

	// Make sure the filearea is one of those used by the plugin.
	if ($filearea !== 'references') {
		return false;
	}

	// Make sure the user is logged in and has access to the module (plugins that are not course modules should leave out the 'cm' part).
	require_login($course);

	// Leave this line out if you set the itemid to null in make_pluginfile_url (set $itemid to 0 instead).
	$itemid = array_shift($args); // The first item in the $args array.

	// Use the itemid to retrieve any relevant data records and perform any security checks to see if the
	// user really does have access to the file in question.

	// Extract the filename / filepath from the $args array.
	$filename = array_pop($args); // The last item in the $args array.
	if (!$args) {
		$filepath = '/'; // $args is empty => the path is '/'
	} else {
		$filepath = '/'.implode('/', $args).'/'; // $args contains elements of the filepath
	}

	// Retrieve the file from the Files API.
	$fs = get_file_storage();
	$file = $fs->get_file($context->id, 'local_vtc', $filearea, $itemid, $filepath, $filename);
	if (!$file) {
		return false; // The file does not exist.
	}

	// We can now send the file back to the browser - in this case with a cache lifetime of 1 day and no filtering.
	send_stored_file($file, 86400, 0, $forcedownload, $options);
}
