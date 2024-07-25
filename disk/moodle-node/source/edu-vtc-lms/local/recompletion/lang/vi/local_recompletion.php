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
 * Strings for local_recompletion
 *
 * @package    local_recompletion
 * @copyright  2017 Dan Marsden
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Tái hoàn thành khóa học';
$string['recompletion'] = 'tái hoàn thành';
$string['editrecompletion'] = 'Chỉnh sửa cài đặt tái hoàn thành khóa học';
$string['enablerecompletion'] = 'Bật tái hoàn thành';
$string['enablerecompletion_help'] = 'Phần mềm tái hoàn thành cho phép thiết lập lại chi tiết hoàn thành khóa học sau một khoảng thời gian nhất định.';
$string['recompletionrange'] = 'Khoảng thời gian tái hoàn thành';
$string['recompletionrange_help'] = 'Thiết lập khoảng thời gian trước khi kết quả hoàn thành của người dùng được thiết lập lại.';
$string['recompletionsettingssaved'] = 'Lưu cài đặt tái hoàn thành';
$string['recompletion:manage'] = 'Cho phép thay đổi cài đặt tái hoàn thành khóa học';
$string['recompletion:resetmycompletion'] = 'Thiết lập lại hoàn thành của tôi';
$string['resetmycompletion'] = 'Thiết lập lại hoàn thành hoạt động của tôi';
$string['recompletiontask'] = 'Kiểm tra người dùng cần tái hoàn thành';
$string['completionnotenabled'] = 'Không bật hoàn thành trong khóa học này';
$string['recompletionnotenabled'] = 'Không bật tái hoàn thành trong khóa học này';
$string['recompletionemailenable'] = 'Gửi thông báo tái hoàn thành';
$string['recompletionemailenable_help'] = 'Bật thông báo qua email để thông báo cho người dùng biết rằng cần tái hoàn thành';
$string['recompletionemailsubject'] = 'Tiêu đề thông báo tái hoàn thành';
$string['recompletionemailsubject_help'] = 'Có thể thêm một tiêu đề email tái hoàn thành tùy chỉnh dưới dạng văn bản thường

Các chỗ giữ chỗ sau có thể được thêm vào thông báo:

* Tên khóa học {$a->coursename}
* Họ và tên người dùng {$a->fullname}';
$string['recompletionemaildefaultsubject'] = 'Khóa học {$a->coursename} yêu cầu tái hoàn thành';
$string['recompletionemailbody'] = 'Nội dung thông báo tái hoàn thành';
$string['recompletionemailbody_help'] = 'Có thể thêm nội dung email tái hoàn thành tùy chỉnh dưới dạng văn bản thường hoặc định dạng tự động của Moodle, bao gồm các thẻ HTML và thẻ đa ngôn ngữ

Các chỗ giữ chỗ sau có thể được thêm vào thông báo:

* Tên khóa học {$a->coursename}
* Liên kết đến khóa học {$a->link}
* Liên kết đến trang hồ sơ người dùng {$a->profileurl}
* Email người dùng {$a->email}
* Họ và tên người dùng {$a->fullname}';
$string['recompletionemaildefaultbody'] = 'Xin chào, hãy tái hoàn thành khóa học {$a->coursename} {$a->link}';
$string['advancedrecompletiontitle'] = 'Nâng cao';
$string['deletegradedata'] = 'Xóa tất cả điểm cho người dùng';
$string['deletegradedata_help'] = 'Xóa dữ liệu hoàn thành điểm hiện tại từ bảng grade_grades. Dữ liệu tái hoàn thành điểm sẽ bị xóa vĩnh viễn nhưng dữ liệu được giữ lại trong bảng lịch sử grade.';
$string['archivecompletiondata'] = 'Lưu trữ dữ liệu hoàn thành';
$string['archivecompletiondata_help'] = 'Ghi dữ liệu hoàn thành vào các bảng local_recompletion_cc, local_recompletion_cc_cc và local_recompletion_cmc. Dữ liệu hoàn thành sẽ bị xóa vĩnh viễn nếu lựa chọn này không được chọn.';
$string['forcearchivecompletiondata'] = 'Bắt buộc lưu trữ dữ liệu hoàn thành';
$string['forcearchivecompletiondata_help'] = 'Nếu được kích hoạt, quá trình lưu trữ dữ liệu hoàn thành sẽ bắt buộc đối với tất cả các tái hoàn thành khóa học. Điều này có thể ngăn chặn việc mất dữ liệu không mong muốn.';
$string['emailrecompletiontitle'] = 'Cài đặt thông báo tái hoàn thành tùy chỉnh';
$string['eventrecompletion'] = 'Tái hoàn thành khóa học';
$string['assignattempts'] = 'Gán số lần thử';
$string['assignattempts_help'] = 'Cách xử lý số lần thử trong khóa học.
Nếu chọn \'Cập nhật khi điểm thay đổi\', khi giáo viên cập nhật điểm trong hoạt động giao bài và người dùng đã hoàn thành khóa học, ngày hoàn thành khóa học của họ sẽ được cập nhật để sử dụng ngày thay đổi điểm bài tập.';
$string['extraattempt'] = 'Cho sinh viên cơ hội thêm';
$string['quizattempts'] = 'Số lần thử bài kiểm tra';
$string['quizattempts_help'] = 'Làm gì với số lần thử bài kiểm tra hiện tại. Nếu chọn xóa và lưu trữ, các lần thử bài kiểm tra cũ sẽ được lưu trữ trong các bảng local_recompletion,
 nếu chọn thêm lần thử mới, điều này sẽ thêm một lựa chọn bài kiểm tra để cho phép người dùng có số lần thử tối đa được thiết lập.';
$string['questionnaireattempts'] = 'Số lần thử bảng câu hỏi';
$string['questionnaireattempts_help'] = 'Làm gì với số lần thử bảng câu hỏi hiện tại. Nếu chọn xóa và lưu trữ, các lần thử câu hỏi cũ sẽ được lưu trữ trong các bảng local_recompletion.';
$string['scormattempts'] = 'Số lần thử SCORM';
$string['scormattempts_help'] = 'Có nên xóa số lần thử SCORM hiện tại hay không - nếu chọn lưu trữ, các lần thử SCORM cũ sẽ được lưu trữ trong bảng local_recompletion_sst.';
$string['archive'] = 'Lưu trữ số lần thử cũ';
$string['delete'] = 'Xóa các lần thử hiện tại';
$string['donothing'] = 'Không làm gì cả';
$string['resetcompletionconfirm'] = 'Bạn có chắc chắn muốn thiết lập lại tất cả dữ liệu hoàn thành trong khóa học này cho {$a}? Cảnh báo - điều này có thể xóa vĩnh viễn một số nội dung đã được nộp.';
$string['privacy:metadata:local_recompletion_cc'] = 'Lưu trữ các lần hoàn thành khóa học trước đó.';
$string['privacy:metadata:local_recompletion_cmc'] = 'Lưu trữ các lần hoàn thành mô-đun khóa học trước đó.';
$string['privacy:metadata:local_recompletion_cc_cc'] = 'Lưu trữ các lần hoàn thành course_completion_crit_compl trước đó.';
$string['privacy:metadata:local_recompletion_cha'] = 'Lưu trữ câu trả lời lựa chọn trước đó';
$string['privacy:metadata:local_recompletion_cha:choiceid'] = 'ID của lựa chọn trong lưu trữ câu trả lời lựa chọn';
$string['privacy:metadata:local_recompletion_cha:optionid'] = 'ID của lựa chọn trong lưu trữ câu trả lời lựa chọn';
$string['privacy:metadata:local_recompletion_ltia'] = 'Nhật ký truy cập người dùng và dữ liệu gradeback.';
$string['privacy:metadata:local_recompletion_ltia:toolid'] = 'ID của công cụ của phương pháp đăng ký LTI.';
$string['privacy:metadata:local_recompletion_ltia:userid'] = 'ID của người dùng.';
$string['privacy:metadata:local_recompletion_ltia:lastgrade'] = 'Điểm cuối cùng mà người dùng được ghi nhận.';
$string['privacy:metadata:local_recompletion_ltia:lastaccess'] = 'Thời gian người dùng cuối cùng truy cập khóa học.';
$string['privacy:metadata:local_recompletion_ltia:timecreated'] = 'Thời gian người dùng được đăng ký.';
$string['privacy:metadata:userid'] = 'ID của người dùng liên kết với bảng này.';
$string['privacy:metadata:course'] = 'ID của khóa học liên kết với bảng này.';
$string['privacy:metadata:timecompleted'] = 'Thời gian khóa học được hoàn thành.';
$string['privacy:metadata:timeenrolled'] = 'Thời gian người dùng được đăng ký vào khóa học';
$string['privacy:metadata:timemodified'] = 'Thời gian bản ghi được sửa đổi';
$string['privacy:metadata:timestarted'] = 'Thời gian khóa học được bắt đầu.';
$string['privacy:metadata:coursesummary'] = 'Lưu trữ dữ liệu hoàn thành khóa học cho người dùng.';
$string['privacy:metadata:gradefinal'] = 'Điểm cuối cùng nhận được cho hoàn thành khóa học';
$string['privacy:metadata:overrideby'] = 'ID người dùng của người đã ghi đè lên hoàn thành hoạt động';
$string['privacy:metadata:reaggregate'] = 'Nếu hoàn thành khóa học đã được tổng hợp lại.';
$string['privacy:metadata:unenroled'] = 'Nếu người dùng đã bị hủy đăng ký khóa học';
$string['privacy:metadata:quiz_attempts'] = 'Chi tiết lưu trữ về mỗi lần thử bài kiểm tra.';
$string['privacy:metadata:quiz_attempts:attempt'] = 'Số lần thử.';
$string['privacy:metadata:quiz_attempts:currentpage'] = 'Trang hiện tại người dùng đang ở.';
$string['privacy:metadata:quiz_attempts:preview'] = 'Cho biết đây có phải là xem trước bài kiểm tra không.';
$string['privacy:metadata:quiz_attempts:state'] = 'Trạng thái hiện tại của lần thử.';
$string['privacy:metadata:quiz_attempts:sumgrades'] = 'Tổng điểm của các lần thử.';
$string['privacy:metadata:quiz_attempts:timecheckstate'] = 'Thời gian kiểm tra trạng thái.';
$string['privacy:metadata:quiz_attempts:timefinish'] = 'Thời gian lần thử hoàn thành.';
$string['privacy:metadata:quiz_attempts:timemodified'] = 'Thời gian lần thử được cập nhật.';
$string['privacy:metadata:quiz_attempts:timemodifiedoffline'] = 'Thời gian lần thử được cập nhật thông qua cập nhật ngoại tuyến.';
$string['privacy:metadata:quiz_attempts:timestart'] = 'Thời gian lần thử bắt đầu.';
$string['privacy:metadata:quiz_grades'] = 'Chi tiết lưu trữ về điểm số tổng cộng cho các lần thử bài kiểm tra trước đó.';
$string['privacy:metadata:quiz_grades:grade'] = 'Điểm tổng cộng cho bài kiểm tra này.';
$string['privacy:metadata:quiz_grades:quiz'] = 'Bài kiểm tra đã được đánh giá.';
$string['privacy:metadata:quiz_grades:timemodified'] = 'Thời gian điểm số được sửa đổi.';
$string['privacy:metadata:quiz_grades:userid'] = 'Người dùng đã được đánh giá.';
$string['privacy:metadata:scoes_track:element'] = 'Tên của phần tử cần theo dõi';
$string['privacy:metadata:scoes_track:value'] = 'Giá trị của phần tử được đưa ra';
$string['privacy:metadata:coursemoduleid'] = 'ID của hoạt động.';
$string['privacy:metadata:completionstate'] = 'Nếu hoạt động đã được hoàn thành.';
$string['privacy:metadata:viewed'] = 'Nếu hoạt động đã được xem.';
$string['privacy:metadata:attempt'] = 'Số lần thử.';
$string['privacy:metadata:scorm_scoes_track'] = 'Lưu trữ dữ liệu theo dõi của SCOes thuộc hoạt động.';
$string['privacy:metadata:local_recompletion_qr:questionnaireid'] = 'ID của bảng câu hỏi.';
$string['privacy:metadata:local_recompletion_qr:submitted'] = 'Đã nộp.';
$string['privacy:metadata:local_recompletion_qr:complete'] = 'Hoàn thành.';
$string['privacy:metadata:local_recompletion_qr:grade'] = 'Điểm số.';
$string['privacy:metadata:local_recompletion_qr'] = 'Bảng phản hồi câu hỏi lại.';
$string['noassigngradepermission'] = 'Hoàn thành của bạn đã được đặt lại, nhưng khóa học này chứa một bài tập không thể đặt lại, vui lòng yêu cầu giáo viên thực hiện nếu cần.';
$string['editcompletion'] = 'Chỉnh sửa ngày hoàn thành khóa học';
$string['editcompletion_desc'] = 'Sửa đổi ngày hoàn thành khóa học cho các người dùng sau:';
$string['coursecompletiondate'] = 'Ngày hoàn thành khóa học mới';
$string['completionupdated'] = 'Ngày hoàn thành khóa học đã được cập nhật';
$string['bulkchangedate'] = 'Thay đổi ngày hoàn thành cho người dùng đã chọn';
$string['nousersselected'] = 'Không có người dùng nào được chọn';
$string['resetallcompletion'] = 'Đặt lại tất cả hoàn thành';
$string['bulkresetallcompletion'] = 'Đặt lại tất cả hoàn thành cho người dùng đã chọn';
$string['resetcompletionfor'] = 'Đặt lại hoàn thành cho {$a}';
$string['completionresetuser'] = 'Hoàn thành cho {$a} trong khóa học này đã được đặt lại.';
$string['completionreset'] = 'Hoàn thành cho những sinh viên đã chọn trong khóa học này đã được đặt lại.';
$string['modifycompletiondates'] = 'Sửa đổi ngày hoàn thành khóa học';
$string['assignevent'] = 'Cập nhật hoàn thành khóa học khi điểm thay đổi';
$string['defaultsettings'] = 'Cài đặt mặc định cho tái hoàn thành';
$string['archivequiz'] = 'Lưu trữ cũ các lần thử bài kiểm tra';
$string['archivequestionnaire'] = 'Lưu trữ cũ các lần thử bảng câu hỏi';
$string['archivescorm'] = 'Lưu trữ cũ các lần thử SCORM';
$string['resetlti'] = 'Đặt lại điểm LTI';
$string['resetltis'] = 'Điểm LTI';
$string['resetltis_help'] = 'Cách xử lý điểm LTI trong khóa học.
Nếu chọn \'Đặt lại điểm LTI\', tất cả điểm kết quả LTI sẽ được đặt lại về 0.
Khi người dùng đạt được hoàn thành mới trong khóa học, điểm khóa học đã được cập nhật sẽ được gửi lại cho nhà cung cấp LTI.';
$string['pulsenotifications'] = 'Thông báo Pulse';
$string['pulsenotifications_help'] = 'Có nên đặt lại thông báo Pulse đã được gửi không?';
$string['pulseresetnotifications'] = 'Đặt lại thông báo';
$string['choiceattempts'] = "Số lần thử lựa chọn";
$string['archivechoice'] = "Lưu trữ cũ các lần thử lựa chọn";
$string['choiceattempts_help'] = 'Có nên xóa các lần thử lựa chọn hiện tại không - nếu chọn lưu trữ, các lần thử lựa chọn cũ sẽ được lưu trữ trong bảng local_recompletion_cha.';
$string['customcertcertificates'] = 'Chứng chỉ tùy chỉnh';
$string['customcertcertificates_help'] = 'Có nên xóa chứng chỉ tùy chỉnh đã được cấp không?';
$string['customcertresetcertificates'] = 'Xóa chứng chỉ đã cấp';
$string['customcertresetcertificatesverifywarn'] = 'Chú ý: Xóa các chứng chỉ đã cấp, ngay cả nếu bạn lưu trữ chúng trước khi xóa, sẽ dẫn đến việc không thể xác minh chứng chỉ đã cấp nữa trong Moodle. Hãy chỉ xóa chứng chỉ nếu điều này chấp nhận được đối với bạn.';
$string['archivecustomcertcertificates'] = 'Lưu trữ chứng chỉ đã cấp';
$string['archivecustomcertcertificates_help'] = 'Có nên lưu trữ chứng chỉ đã cấp không?';
