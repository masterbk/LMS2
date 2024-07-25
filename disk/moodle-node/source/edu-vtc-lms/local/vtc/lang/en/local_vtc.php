<?php
// This file is part of the Contact Form plugin for Moodle - http://moodle.org/
//
// Contact Form is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Contact Form is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Contact Form.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This plugin for Moodle is used to send emails through a web form.
 *
 * @package    local_vtc
 * @copyright  Videa
 * @author     Bob Nguyen
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'VTC';
//Course
$string['additionalinfo'] = 'Thông tin khóa học';
$string['coursevideo'] = 'Video của khóa học (Link Youtube)';
$string['coursevideo_help'] = 'Nếu trường này không trống, nó sẽ được sử dụng để hiển thị banner video giới thiệu khóa học.';
$string['coursecontent'] = 'Nội dung khóa học';
$string['coursecontent_help'] = 'Nội dung khóa học sẽ được hiển thị tại tab "Tổng quan" bên trong course.';
$string['courseduration'] = 'Thời gian khóa học';
$string['courseduration_help'] = 'Thời gian dự kiến để hoàn thành khóa học. Ví dụ: 12 giờ';
$string['references'] = 'Tài liệu tham khảo';
$string['references_help'] = 'Tài liệu tham khảo là các tài liệu bên ngoài nội dung khóa học. Các tài liệu này sẽ được hiển thị ở mục tài liệu của khóa học. Có thể upload nhiều khóa học cùng lúc.';
$string['continuouslyupdate'] = 'Cập nhật kiến thức liên tục';
$string['continuouslyupdate_help'] = 'Nếu lựa chọn "Có" tại trường này, trong phần tổng quan khóa học sẽ có thêm một dòng text thể hiện khóa học cập nhật liên tục.';
$string['yes'] = 'Có';
$string['no'] = 'Không';
$string['vtclayoutenabled'] = 'Sử dụng VTC layout';
$string['vtclayoutenabled_help'] = 'Nếu lựa chọn "Có", giao diện của course sẽ được hiển thị theo cấu trúc khóa học của VTC.';
$string['course_not_found'] = 'Không tìm thấy khóa học.';
$string['markedascompleted'] = 'Đã đánh dấu hoàn thành khóa học này.';
$string['noselfcompletioncriteria'] = 'Khóa học chưa được thiết lập để học viên tự động đánh dấu hoàn thành.';
