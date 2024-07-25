<?php
$string["auth_ldap_ad_create_req"] = 'Không thể tạo tài khoản mới trong Active Directory. ';
$string["auth_ldap_attrcreators"] = 'Danh sách các nhóm hoặc bối cảnh mà các thành viên được phép tạo thuộc tính. ';
$string["auth_ldap_attrcreators_key"] = 'Người tạo thuộc tính';
$string["auth_ldap_auth_user_create_key"] = 'Tạo người dùng bên ngoài';
$string["auth_ldap_bind_dn_key"] = 'Tên đặc biệt';
$string["auth_ldap_bind_pw_key"] = 'Mật khẩu';
$string["auth_ldap_contexts_key"] = 'Bối cảnh';
$string["auth_ldap_create_context_key"] = 'Bối cảnh cho người dùng mới';
$string["auth_ldap_create_error"] = 'Lỗi tạo người dùng trong LDAP.';
$string["auth_ldap_expiration_key"] = 'Hết hạn';
$string["auth_ldap_expiration_warning_key"] = 'Cảnh báo hết hạn';
$string["auth_ldap_expireattr_key"] = 'Thuộc tính hết hạn';
$string["auth_ldap_gracelogin_key"] = 'Thuộc tính đăng nhập Grace';
$string["auth_ldap_gracelogins_key"] = 'Đăng nhập ân hạn';
$string["auth_ldap_groupecreators"] = 'Danh sách các nhóm hoặc bối cảnh mà các thành viên được phép tạo nhóm. ';
$string["auth_ldap_groupecreators_key"] = 'Người tạo nhóm';
$string["auth_ldap_host_url_key"] = 'URL máy chủ';
$string["auth_ldap_changepasswordurl_key"] = 'URL thay đổi mật khẩu';
$string["auth_ldap_ldap_encoding"] = 'Mã hóa được máy chủ LDAP sử dụng, rất có thể là utf-8. ';
$string["auth_ldap_ldap_encoding_key"] = 'mã hóa LDAP';
$string["auth_ldap_memberattribute_isdn"] = 'Ghi đè xử lý các giá trị thuộc tính thành viên';
$string["auth_ldap_memberattribute_isdn_key"] = 'Thuộc tính thành viên sử dụng dn';
$string["auth_ldap_memberattribute_key"] = 'Thuộc tính thành viên';
$string["auth_ldap_noconnect"] = 'Mô-đun LDAP không thể kết nối với máy chủ: {$a}';
$string["auth_ldap_noconnect_all"] = 'Mô-đun LDAP không thể kết nối với bất kỳ máy chủ nào: {$a}';
$string["auth_ldap_noextension"] = 'Mô-đun LDAP PHP dường như không có mặt. ';
$string["auth_ldap_no_mbstring"] = 'Bạn cần phần mở rộng mbstring để tạo người dùng trong Active Directory.';
$string["auth_ldapnotinstalled"] = 'Không thể sử dụng xác thực LDAP. ';
$string["auth_ldap_objectclass_key"] = 'Lớp đối tượng';
$string["auth_ldap_opt_deref_key"] = 'Bí danh tham chiếu';
$string["auth_ldap_passtype"] = 'Chỉ định định dạng của mật khẩu mới hoặc đã thay đổi trong máy chủ LDAP.';
$string["auth_ldap_passtype_key"] = 'Định dạng mật khẩu';
$string["auth_ldap_preventpassindb"] = 'Chọn có để ngăn mật khẩu được lưu trữ trong DB của Moodle.';
$string["auth_ldap_preventpassindb_key"] = 'Ngăn chặn việc lưu mật khẩu vào bộ nhớ đệm';
$string["auth_ldap_rolecontext"] = 'bối cảnh {$a->localname}';
$string["auth_ldap_rolecontext_help"] = 'Bối cảnh LDAP được sử dụng để chọn cho <i>{$a->tên địa phương}</i> lập bản đồ. ';
$string["auth_ldap_search_sub_key"] = 'Tìm kiếm ngữ cảnh phụ';
$string["auth_ldap_unsupportedusertype"] = 'auth: ldap user_create() không hỗ trợ kiểu người dùng đã chọn: {$a}';
$string["auth_ldap_user_attribute_key"] = 'Thuộc tính người dùng';
$string["auth_ldap_suspended_attribute"] = 'Tùy chọn: Khi được cung cấp, thuộc tính này sẽ được sử dụng để bật/tạm dừng tài khoản người dùng được tạo cục bộ.';
$string["auth_ldap_suspended_attribute_key"] = 'Thuộc tính bị treo';
$string["auth_ldap_user_exists"] = 'Tên người dùng LDAP đã tồn tại.';
$string["auth_ldap_user_type_key"] = 'Loại người dùng';
$string["auth_ldap_usertypeundefined"] = 'config.user_type không được xác định hoặc hàm ldap_expirationtime2unix không hỗ trợ loại đã chọn!';
$string["auth_ldap_usertypeundefined2"] = 'config.user_type không được xác định hoặc hàm ldap_unixi2expirationtime không hỗ trợ loại đã chọn!';
$string["auth_ldap_version_key"] = 'Phiên bản';
$string["auth_ntlmsso"] = 'NTLM SSO';
$string["auth_ntlmsso_enabled"] = 'Đặt thành có để thử Đăng nhập một lần bằng miền NTLM.  <a href=\"https://docs.moodle.org/en/NTLM_authentication\">Xác thực NTLM</a>.';
$string["auth_ntlmsso_enabled_key"] = 'Cho phép';
$string["auth_ntlmsso_ie_fastpath"] = 'Đặt để bật đường dẫn nhanh NTLM SSO (bỏ qua các bước nhất định nếu trình duyệt của khách hàng là MS Internet Explorer).';
$string["auth_ntlmsso_ie_fastpath_key"] = 'Đường dẫn nhanh của MS IE?';
$string["auth_ntlmsso_ie_fastpath_yesform"] = 'Có, tất cả các trình duyệt khác đều sử dụng hình thức đăng nhập tiêu chuẩn';
$string["auth_ntlmsso_ie_fastpath_yesattempt"] = 'Có, hãy thử NTLM trên các trình duyệt khác';
$string["auth_ntlmsso_ie_fastpath_attempt"] = 'Hãy thử NTLM với tất cả các trình duyệt';
$string["auth_ntlmsso_maybeinvalidformat"] = 'Không thể trích xuất tên người dùng từ tiêu đề REMOTE_USER. ';
$string["auth_ntlmsso_missing_username"] = 'Bạn cần chỉ định ít nhất %username% ở định dạng tên người dùng từ xa';
$string["auth_ntlmsso_remoteuserformat_key"] = 'Định dạng tên người dùng từ xa';
$string["auth_ntlmsso_remoteuserformat"] = 'Nếu bạn đã chọn \'NTLM\' trong \'Loại xác thực\', bạn có thể chỉ định định dạng tên người dùng từ xa tại đây.  <b>%lãnh địa%</b> giữ chỗ để chỉ định nơi tên miền xuất hiện và bắt buộc <b>%tên tài khoản%</b> giữ chỗ để chỉ định nơi tên người dùng xuất hiện. <br /><br />Một số định dạng được sử dụng rộng rãi là <tt>%tên miền%\\%tên người dùng%</tt> (MS Windows mặc định), <tt>%tên miền%/%tên người dùng%</tt>, <tt>%tên miền% %tên người dùng%</tt> và chỉ <tt>%tên tài khoản%</tt> (nếu không có phần miền).';
$string["auth_ntlmsso_subnet"] = 'Nếu được đặt, nó sẽ chỉ thử SSO với các máy khách trong mạng con này. ';
$string["auth_ntlmsso_subnet_key"] = 'Mạng con';
$string["auth_ntlmsso_type_key"] = 'Loại xác thực';
$string["auth_ntlmsso_type"] = 'Phương thức xác thực được định cấu hình trong máy chủ web để xác thực người dùng (nếu nghi ngờ, hãy chọn NTLM)';
$string["cannotmaprole"] = 'Không thể ánh xạ vai trò \"{$a->rolename}\" vì tên ngắn \"{$a->shortname}\" của nó quá dài và/hoặc chứa dấu gạch nối.  <a href=\"{$a->link}\">Chỉnh sửa vai trò</a>';
$string["connectingldap"] = 'Đang kết nối với máy chủ LDAP...
';
$string["connectingldapsuccess"] = 'Kết nối với máy chủ LDAP của bạn đã thành công';
$string["creatingtemptable"] = 'Tạo bảng tạm thời {$a}
';
$string["didntfindexpiretime"] = 'pass_expire() không tìm thấy thời gian hết hạn.';
$string["didntgetusersfromldap"] = 'Không nhận được bất kỳ người dùng nào từ LDAP -- lỗi? 
';
$string["gotcountrecordsfromldap"] = 'Đã nhận được bản ghi {$a} từ LDAP
';
$string["invalidusererrors"] = 'Cảnh báo: Đã bỏ qua việc tạo tài khoản người dùng {$a}.

';
$string["invaliduserexception"] = '
Lỗi: Không thể tạo tài khoản người dùng mới. 

';
$string["ldapnotconfigured"] = 'Url máy chủ LDAP hiện chưa được định cấu hình';
$string["morethanoneuser"] = 'Đã tìm thấy nhiều bản ghi người dùng trong LDAP. ';
$string["needbcmath"] = 'Bạn cần tiện ích mở rộng BCMath để sử dụng tính năng kiểm tra mật khẩu đã hết hạn bằng Active Directory.';
$string["needmbstring"] = 'Bạn cần phần mở rộng mbstring để thay đổi mật khẩu trong Active Directory';
$string["nodnforusername"] = 'Lỗi trong user_update_password(). ';
$string["noemail"] = 'Đã thử gửi email cho bạn nhưng không thành công!';
$string["notcalledfromserver"] = 'Không nên gọi từ máy chủ web!';
$string["noupdatestobedone"] = 'Không có cập nhật nào được thực hiện
';
$string["nouserentriestoremove"] = 'Không có mục nhập của người dùng nào bị xóa
';
$string["nouserentriestorevive"] = 'Không có mục nhập của người dùng nào được hồi sinh
';
$string["nouserstobeadded"] = 'Không có mục nhập người dùng nào được thêm vào';
$string["ntlmsso_attempting"] = 'Đang cố gắng đăng nhập một lần qua NTLM...';
$string["ntlmsso_failed"] = 'Tự động đăng nhập không thành công, hãy thử trang đăng nhập bình thường...';
$string["ntlmsso_isdisabled"] = 'NTLM SSO bị vô hiệu hóa.';
$string["ntlmsso_unknowntype"] = 'Loại ntlmsso không xác định!';
$string["pagedresultsnotsupp"] = 'Kết quả phân trang LDAP không được hỗ trợ (hoặc phiên bản PHP của bạn thiếu hỗ trợ, bạn đã định cấu hình Moodle để sử dụng giao thức LDAP phiên bản 2 hoặc Moodle không thể liên hệ với máy chủ LDAP của bạn để xem liệu có hỗ trợ phân trang hay không.)';
$string["pagesize"] = 'Đảm bảo giá trị này nhỏ hơn giới hạn kích thước tập hợp kết quả máy chủ LDAP của bạn (số lượng mục nhập tối đa có thể được trả về trong một truy vấn)';
$string["pagesize_key"] = 'Kích thước trang';
$string["pluginnotenabled"] = 'Plugin chưa được kích hoạt!';
$string["renamingnotallowed"] = 'Đổi tên người dùng không được phép trong LDAP';
$string["rootdseerror"] = 'Lỗi truy vấn rootDSE cho Active Directory';
$string["syncroles"] = 'Đồng bộ hóa vai trò hệ thống từ LDAP';
$string["synctask"] = 'Công việc đồng bộ hóa người dùng LDAP';
$string["systemrolemapping"] = 'Ánh xạ vai trò hệ thống';
$string["start_tls"] = 'Sử dụng dịch vụ LDAP thông thường (cổng 389) với mã hóa TLS';
$string["start_tls_key"] = 'Sử dụng TLS';
$string["updateremfail"] = 'Lỗi cập nhật bản ghi LDAP. <br/>Khóa ({$a->key}) - giá trị moodle cũ: \'{$a->ouvalue}\' giá trị mới: \'{$a->nuvalue}\'';
$string["updateremfailamb"] = 'Không thể cập nhật LDAP với trường không rõ ràng {$a->key}; ';
$string["updatepasserror"] = 'Lỗi trong user_update_password(). ';
$string["updatepasserrorexpire"] = 'Lỗi user_update_password() khi đọc thời gian hết hạn của mật khẩu. ';
$string["updatepasserrorexpiregrace"] = 'Lỗi trong user_update_password() khi sửa đổi thời gian hết hạn và/hoặc thông tin đăng nhập gia hạn. ';
$string["updateusernotfound"] = 'Không thể tìm thấy người dùng trong khi cập nhật bên ngoài. ';
$string["user_activatenotsupportusertype"] = 'auth: ldap user_activate() không hỗ trợ loại người dùng đã chọn: {$a}';
$string["user_disablenotsupportusertype"] = 'auth: ldap user_disable() không hỗ trợ loại người dùng đã chọn: {$a}';
$string["userentriestoadd"] = 'Mục nhập của người dùng sẽ được thêm: {$a}
';
$string["userentriestoremove"] = 'Các mục của người dùng cần xóa: {$a}
';
$string["userentriestorevive"] = 'Các mục nhập của người dùng sẽ được hồi sinh: {$a}
';
$string["userentriestoupdate"] = 'Các mục nhập của người dùng sẽ được cập nhật: {$a}
';
$string["usernotfound"] = 'Không tìm thấy người dùng trong LDAP';
$string["useracctctrlerror"] = 'Lỗi khi tải userAccountControl cho {$a}';
$string["diag_genericerror"] = 'Lỗi LDAP {$a->code} đọc {$a->subject}: {$a->message}.';
$string["diag_toooldversion"] = 'Rất khó có khả năng máy chủ LDAP hiện đại sử dụng giao thức LDAPv2. ';
$string["diag_emptycontext"] = 'Đã tìm thấy bối cảnh trống.';
$string["diag_contextnotfound"] = 'Ngữ cảnh {$a} không tồn tại hoặc không thể đọc được bằng bind DN.';
$string["diag_rolegroupnotfound"] = 'Nhóm {$a->group} cho vai trò {$a->localname} không tồn tại hoặc không thể đọc được bằng bind DN.';
$string["privacy:metadata"] = 'Plugin xác thực máy chủ LDAP không lưu trữ bất kỳ dữ liệu cá nhân nào.';
