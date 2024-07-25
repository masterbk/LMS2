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
 * Strings for component 'auth_zalo', language 'vi'.
 *
 * @package		auth_zalo
 * @copyright 	2023 Videa {@link https://videabiz.com}
 * @author		Brindley <brindley@videabiz.com>
 * @license		http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string["setting_help"] = "Tại đây bạn có thể cập nhật thông tin về ứng dụng Zalo của bạn. Toàn bộ thông tin cần thiết đều có thể tìm thấy trong trang <code>Cài đặt</code> của ứng dụng của bạn.";
$string["complete_registration"] = "Hoàn Thành Đăng Kí";
$string["complete_registration_sub"] = "Hãy điền vào các thông tin còn thiếu để tiếp tục tới <b>{\$a}</b>";

$string["config_app_id"] = "ID ứng dụng";
$string["config_app_id_help"] = "Mã định danh ứng dụng Zalo của bạn";
$string["config_secret_key"] = "Khóa bí mật của ứng dụng";
$string["config_secret_key_help"] = "Khóa bí mật của ứng dụng Zalo của bạn. Được sử dụng để thực hiện giao tiếp với máy chủ Zalo";
$string["config_api_base"] = "Đường dẫn đăng nhập";
$string["config_api_base_help"] = "URL sử dụng để tạo liên kết đăng nhập và xác minh thông tin tài khoản của người dùng. Bạn không cần phải thay đổi giá trị cho trường này.";

$string["empty_appid"] = "ID ứng dụng hiện đang trống!";
$string["no_active_session"] = "Không có phiên đăng nhập Zalo OAuth2 nào đang được thực hiện!";
$string["curl_error"] = "Yêu cầu tới máy chủ Zalo đã sinh ra lỗi!";
$string["auth_error"] = "Lỗi đăng nhập vào máy chủ Zalo: {\$a->error_name}";
$string["info_error"] = "Lỗi đã xảy ra khi lấy thông tin người dùng: {\$a->error_name}";
