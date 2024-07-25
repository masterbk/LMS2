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
 * Contains the default content output class.
 *
 * @package   format_vtc
 * @copyright 2020 Ferran Recio <ferran@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_vtc\output\courseformat;

use core_courseformat\output\local\content as content_base;
use renderer_base;

/**
 * Base class to render a course content.
 *
 * @package   format_vtc
 * @copyright 2020 Ferran Recio <ferran@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class content extends content_base {

    /**
     * @var bool Topic format has add section after each topic.
     *
     * The responsible for the buttons is core_courseformat\output\local\content\section.
     */
    protected $hasaddsection = false;

    /**
     * Export this data so it can be used as the context for a mustache template (core/inplace_editable).
     *
     * @param renderer_base $output typically, the renderer that's calling this function
     */
    public function export_for_template(renderer_base $output) {
        global $PAGE, $USER;
        $PAGE->requires->js_call_amd('format_vtc/mutations', 'init');
        $PAGE->requires->js_call_amd('format_vtc/section', 'init');
        $PAGE->requires->js_call_amd('format_vtc/tabs', 'init');
        $data = parent::export_for_template($output);
        $htmlCustom = '<div class="tab-content w-100" >';
        if (isset($data->initialsection->cmlist->hascms) && $data->initialsection->cmlist->hascms == true) {
            foreach ($data->initialsection->cmlist->cms as $value) {
                $completeactivity = get_string('notcompleteactivity', 'theme_moove');

                $btnValue = getClassifyModuleName($value->cmitem->module,1);
                $progress = checkActivityProgressByUser($this->format->get_course(), $USER->id, $value->cmitem);
                if ($progress == 100) {
                    $completeactivity = get_string('completeactivity', 'theme_moove');
                    $btnValue = getClassifyModuleName($value->cmitem->module,2);

                }
                $progressHtml = $progress != 0 ? $progress . '%' : '';

                $customTime = getActivityCustomField('context_module', $value->cmitem->id, 'time');

                $htmlCustomTime = '<div></div>';
                $htmlProgressBar = '<div class="d-flex justify-content-between">
                                          <span>' . $completeactivity . '</span>
                                          <span></span>
                                          
                                    </div>';
                $padding = 'my-2';
                if ($value->cmitem->module == 'scorm') {
                    $htmlCustomTime = '<div class="custom-course-time cutom-p-no-margin font-weight-normal" ><i class="fa fa-clock" style="margin-right: 10px"></i>' . formatVtcTimeIntToString($customTime['time']) . '</div>';
                    $htmlProgressBar = ' <div class="progress">
                                      <div class="progress-bar" style="width:'.$progress.'%;background-color:#34B53A" role="progressbar" aria-valuenow="' . $progress . '" aria-valuemin="0" aria-valuemax="100"></div>
                                       </div>
                                        <div class="d-flex justify-content-end">
                                          <span style="color: #34B53A;font-size: 16px;font-weight: 600;">' . $progress . '%' . '</span>
                                          
                                        </div>';
                    $padding = 'my-3';
                    $value->cmitem->progress = $progress;
                }else{
                    if($progress == 100){
                        $value->cmitem->progressnotscorm = 1;
                    }
                }
                $value->cmitem->thumbnail = getCustomFieldPictureActivity($value->cmitem->id);

//                $value->cmitem->progress = 1;

                $value->cmitem->customtime = formatVtcTimeIntToString($customTime['time'], 1);
                $value->cmitem->playimage = getClassifyModuleName($value->cmitem->module,3);
                $htmlCustom .= '<div class="tab-pane custom-activity-detail ' . $value->cmitem->cmformattabid . '" id="' . $value->cmitem->cmformattabid . '" role="tabpanel">
                                    <p class="font-weight-bolder activity-detail-title">' . $value->cmitem->cmformat->activityname . '</p>
                                   ' . $htmlProgressBar . '
                                    <div class="d-flex justify-content-between ' . $padding . '"> 
                                        ' . $htmlCustomTime . '
                                    </div>
                                    <div style="overflow: auto; max-height: 300px">
                                        <div style="font-size: 16px;color:#5D5A6F ">' . $value->cmitem->inforcmitem->intro . '</div>
                                    </div>
                                    
                                    <a class="mt-3 activity-button" href="' . $value->cmitem->cmformat->url . '">' . $btnValue . '</a>
                                </div>';
            }
        }
        $htmlCustom .= '</div>';
        $data->htmlCustom = $htmlCustom;
        return $data;
    }

}
