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
 * Contains the default activity list from a section.
 *
 * @package   core_courseformat
 * @copyright 2020 Ferran Recio <ferran@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_vtc\output\courseformat\content\section;


use core_courseformat\output\local\content\section\cmitem as core_cmitem;

/**
 * Base class to render a section activity list.
 *
 * @package   core_courseformat
 * @copyright 2020 Ferran Recio <ferran@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cmitem extends core_cmitem{

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param   \renderer_base  $output typically, the renderer that's calling this function
     * @return  \stdClass       data context for a mustache template
     */
    public function export_for_template(\renderer_base $output): \stdClass {
        global $USER, $PAGE,$DB,$COURSE;
        $inforCmitem = $DB->get_record($this->mod->modname,array('id'=>$this->mod->instance));
        $data = parent::export_for_template($output);
        $data->cmformattabid = $data->module.'-'.$data->id;
        $data->inforcmitem = $inforCmitem;

        return $data;
    }
}
