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
 * Format topics mutations.
 *
 * An instance of this class will be used to add custom mutations to the course editor.
 * To make sure the addMutations method find the proper functions, all functions must
 * be declared as class attributes, not a simple methods. The reason is because many
 * plugins can add extra mutations to the course editor.
 *
 * @module     format_vtc/mutations
 * @copyright  2022 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import $ from "jquery";

export const init = () => {
        let activityItemCustom = $('.activity-item-custom');
        let customActivityDetail = $('.tab-pane');
        let customActivityDetailLayout = $('.custom-activity-detail-layout');
        activityItemCustom.on('click', function() {
                customActivityDetailLayout.removeClass('hidden');
                activityItemCustom.each(function() {
                        $(this).removeClass('active');
                });
                customActivityDetail.each(function() {
                        $(this).addClass('hidden');
                        $(this).removeClass('d-block');
                });
                $(this).addClass('active');
                $('.' + $(this).data('id')).removeClass('hidden').addClass('d-block');
        });
};
