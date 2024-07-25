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
 * Print adminer in an iframe. It chooses the db driver by looking into the Moodle db configuration.
 *
 * @package    local_adminer
 * @author Andreas Grabs <moodle@grabs-edv.de>
 * @copyright  Andreas Grabs
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
global $DB;
require_login();
//require_capability('local/adminer:useadminer', context_system::instance());

//$myconfig = get_config('local_adminer');

admin_externalpage_setup('local_adminer', '', null);

$PAGE->set_heading('Organizations');
//$PAGE->set_title($SITE->fullname . ': ' . get_string('pluginname', 'local_adminer'));


if ($_SERVER["REQUEST_METHOD"] == "POST") {
 
	$ins = new stdClass();
	$ins->name = $_POST['name'];
	$ins->status = 1;
	$ins->timecreated = time();
	$ins->id = $DB->insert_record('organizations', $ins);
}
           

$templatecontext = [
    'data' => $data,
    'listurl' => (new moodle_url('/local/organization'))->out(false),
];


echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_organization/addorganization', $templatecontext);
echo $OUTPUT->footer();
