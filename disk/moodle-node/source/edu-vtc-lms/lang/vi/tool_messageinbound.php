<?php
$string["classname"] = 'Tên lớp';
$string["component"] = 'Thành phần';
$string["configmessageinboundhost"] = 'Địa chỉ của máy chủ mà Moodle sẽ kiểm tra thư. ';
$string["defaultexpiration"] = 'Thời gian hết hạn của địa chỉ mặc định';
$string["defaultexpiration_help"] = 'Khi một địa chỉ email được tạo bởi trình xử lý, nó có thể được đặt thành tự động hết hạn sau một khoảng thời gian để không thể sử dụng được nữa. ';
$string["description"] = 'Sự miêu tả';
$string["domain"] = 'Tên miền email';
$string["edit"] = 'Biên tập';
$string["edithandler"] = 'Chỉnh sửa cài đặt cho trình xử lý {$a}';
$string["editinghandler"] = 'Đang chỉnh sửa {$a}';
$string["enabled"] = 'Đã bật';
$string["fixedvalidateaddress"] = 'Xác thực địa chỉ người gửi';
$string["fixedvalidateaddress_help"] = 'Bạn không thể thay đổi xác thực địa chỉ cho trình xử lý này. ';
$string["fixedenabled_help"] = 'Bạn không thể thay đổi trạng thái của trình xử lý này. ';
$string["handlerdisabled"] = 'Trình xử lý email mà bạn cố gắng liên hệ đã bị vô hiệu hóa. ';
$string["incomingmailconfiguration"] = 'Cấu hình thư đến';
$string["incomingmailserversettings"] = 'Cài đặt máy chủ thư đến';
$string["incomingmailserversettings_desc"] = 'Moodle có khả năng kết nối với các máy chủ IMAP được cấu hình phù hợp. ';
$string["invalid_recipient_handler"] = 'Nếu nhận được thư hợp lệ nhưng không thể xác thực người gửi, thư sẽ được lưu trữ trên máy chủ email và người dùng sẽ được liên hệ bằng địa chỉ email trong hồ sơ người dùng của họ. ';
$string["invalid_recipient_handler_name"] = 'Trình xử lý người gửi không hợp lệ';
$string["invalidrecipientdescription"] = 'Không thể xác thực thư \"{$a->subject}\" vì nó được gửi từ địa chỉ email khác với địa chỉ trong hồ sơ người dùng của bạn. ';
$string["invalidrecipientdescriptionhtml"] = 'Không thể xác thực thư \"{$a->subject}\" vì nó được gửi từ địa chỉ email khác với địa chỉ trong hồ sơ người dùng của bạn. ';
$string["invalidrecipientfinal"] = 'Thông báo \"{$a->subject}\" không thể được xác thực. ';
$string["mailbox"] = 'Tên hộp thư';
$string["mailboxconfiguration"] = 'Cấu hình hộp thư';
$string["mailboxdescription"] = '[hộp thư] địa chỉ con@[tên miền]';
$string["mailsettings"] = 'Cài đặt thư';
$string["message_handlers"] = 'Trình xử lý tin nhắn';
$string["messageprocessingerror"] = 'Gần đây bạn đã gửi email \"{$a->subject}\" nhưng rất tiếc nó không thể được xử lý.

Chi tiết về lỗi được hiển thị bên dưới.

{$ a->lỗi}';
$string["messageprocessingerrorhtml"] = '<p>Gần đây bạn đã gửi email \"{$a->subject}\" nhưng rất tiếc email này không thể được xử lý.</p>
<p>Các chi tiết của lỗi được hiển thị dưới đây.</p>
<p>{$a->lỗi}</p>';
$string["messageprocessingfailed"] = 'Không thể xử lý email \"{$a->subject}\". Lỗi như sau: \"{$a->message}\".';
$string["messageprocessingfailedunknown"] = 'Không thể xử lý email \"{$a->subject}\". Hãy liên hệ với quản trị viên của bạn để biết thêm thông tin.';
$string["messageprocessingsuccess"] = '{$a->plain}

Nếu bạn không muốn nhận những thông báo này trong tương lai, bạn có thể chỉnh sửa tùy chọn nhắn tin cá nhân của mình bằng cách mở {$a->messagepreferencesurl} trong trình duyệt của mình.';
$string["messageprocessingsuccesshtml"] = '{$a->html}
<p>Nếu bạn không muốn nhận những thông báo này trong tương lai, bạn có thể <a href=\"{$a->messagepreferencesurl}\">chỉnh sửa tùy chọn nhắn tin cá nhân của bạn</a>.</p>';
$string["messageinbound"] = 'Tin nhắn gửi đến';
$string["messageinboundenabled"] = 'Bật xử lý thư đến';
$string["messageinboundenabled_desc"] = 'Việc xử lý thư đến phải được bật để gửi thư với thông tin thích hợp.';
$string["messageinboundgeneralconfiguration"] = 'Cấu hình chung';
$string["messageinboundgeneralconfiguration_desc"] = 'Xử lý thư đến cho phép bạn nhận và xử lý email trong Moodle. ';
$string["messageinboundhost"] = 'Máy chủ thư đến';
$string["messageinboundhostoauth_help"] = 'Dịch vụ OAuth 2 được sử dụng để truy cập máy chủ IMAP, sử dụng xác thực XOAUTH2. ';
$string["messageinboundhostpass"] = 'Mật khẩu';
$string["messageinboundhostpass_desc"] = 'Đây là mật khẩu mà nhà cung cấp dịch vụ của bạn sẽ cung cấp để đăng nhập vào tài khoản email của bạn.';
$string["messageinboundhostssl"] = 'Sử dụng SSL';
$string["messageinboundhostssl_desc"] = 'Một số máy chủ thư hỗ trợ mức độ bảo mật bổ sung bằng cách mã hóa thông tin liên lạc giữa Moodle và máy chủ của bạn. ';
$string["messageinboundhosttype"] = 'Loại máy chủ';
$string["messageinboundhostuser"] = 'tên tài khoản';
$string["messageinboundhostuser_desc"] = 'Đây là tên người dùng mà nhà cung cấp dịch vụ của bạn sẽ cung cấp để đăng nhập vào tài khoản email của bạn.';
$string["messageinboundmailboxconfiguration_desc"] = 'Khi tin nhắn được gửi đi, chúng sẽ có định dạng địa chỉ data@example.com. ';
$string["messageprovider:invalidrecipienthandler"] = 'Tin nhắn để xác nhận rằng tin nhắn gửi đến đến từ bạn';
$string["messageprovider:messageprocessingerror"] = 'Cảnh báo khi không thể xử lý tin nhắn gửi đến';
$string["messageprovider:messageprocessingsuccess"] = 'Xác nhận tin nhắn đã được xử lý thành công';
$string["noencryption"] = 'Tắt - Không mã hóa';
$string["noexpiry"] = 'Không hết hạn';
$string["oldmessagenotfound"] = 'Bạn đã cố gắng xác thực thư theo cách thủ công nhưng không thể tìm thấy thư. ';
$string["oneday"] = 'Một ngày';
$string["onehour"] = 'Một giờ';
$string["oneweek"] = 'Một tuần';
$string["oneyear"] = 'Một năm';
$string["pluginname"] = 'Cấu hình tin nhắn gửi đến';
$string["privacy:metadata:coreuserkey"] = 'Khóa của người dùng để xác thực email đã nhận';
$string["privacy:metadata:messagelist"] = 'Danh sách các số nhận dạng tin nhắn không được xác thực và cần được ủy quyền thêm';
$string["privacy:metadata:messagelist:address"] = 'Địa chỉ nơi email được gửi';
$string["privacy:metadata:messagelist:messageid"] = 'ID tin nhắn';
$string["privacy:metadata:messagelist:timecreated"] = 'Thời điểm lập kỷ lục';
$string["privacy:metadata:messagelist:userid"] = 'ID của người dùng cần phê duyệt tin nhắn';
$string["replysubjectprefix"] = 'Re:';
$string["requirevalidation"] = 'Xác thực địa chỉ người gửi';
$string["name"] = 'Tên';
$string["ssl"] = 'SSL (Phiên bản SSL tự động phát hiện)';
$string["sslv2"] = 'SSLv2 (Bắt buộc phiên bản SSL 2)';
$string["sslv3"] = 'SSLv3 (Buộc SSL Phiên bản 3)';
$string["taskcleanup"] = 'Dọn dẹp email đến chưa được xác minh';
$string["taskpickup"] = 'Nhận email đến';
$string["tls"] = 'TLS (TLS; bắt đầu thông qua đàm phán cấp giao thức trên kênh không được mã hóa; cách KHUYẾN NGHỊ để bắt đầu kết nối an toàn)';
$string["tlsv1"] = 'TLSv1 (kết nối trực tiếp tới máy chủ TLS phiên bản 1.x)';
$string["validateaddress"] = 'Xác thực địa chỉ email người gửi';
$string["validateaddress_help"] = 'Khi nhận được thư từ người dùng, Moodle cố gắng xác thực thư bằng cách so sánh địa chỉ email của người gửi với địa chỉ email trong hồ sơ người dùng của họ.

';
