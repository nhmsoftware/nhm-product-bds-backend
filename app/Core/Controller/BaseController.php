<?php

namespace App\Core\Controller;

use App\Core\Traits\HandleApi;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    description: 'Tài liệu API cho hệ thống nền tảng bất động sản NHM. Bao gồm toàn bộ API dành cho khách hàng công khai, nhân viên bán hàng, quản lý và quản trị viên.',
    title: 'NHM BDS Platform — API Documentation'
)]
#[OA\Server(
    url: '',
    description: 'Máy chủ API chính'
)]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'apiKey',
    description: "Nhập 'Bearer ' theo sau là token xác thực của bạn (ví dụ: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...)",
    name: 'Authorization',
    in: 'header'
)]
// ─── Khai báo nhóm Tag toàn cục cho Swagger UI ────────────────────────────
#[OA\Tag(
    name: 'Auth',
    description: 'Xác thực và quản lý tài khoản người dùng: đăng ký, đăng nhập, đăng xuất, đổi mật khẩu, cập nhật hồ sơ cá nhân và tải tài liệu nhân viên.'
)]
#[OA\Tag(
    name: 'Dashboard',
    description: 'Trang chủ hệ thống: tải dữ liệu tổng quan (KPI, tin tức, module điều hướng) dựa theo vai trò và quyền hạn của người dùng đang đăng nhập.'
)]
#[OA\Tag(
    name: 'Activity Evidence',
    description: 'Minh chứng hoạt động sale: cho phép nhân viên tải ảnh minh chứng thực tế (chụp tại hiện trường) lên hệ thống để ghi nhận hoạt động bán hàng.'
)]
#[OA\Tag(
    name: 'Attendance',
    description: 'Chấm công và điểm danh: nhân viên thực hiện check-in/check-out ca làm việc bằng GPS hoặc WiFi văn phòng, xem lịch sử chấm công và thống kê của bản thân.'
)]
#[OA\Tag(
    name: 'Public Consultation',
    description: 'Tư vấn công khai dành cho khách hàng: xem thông tin liên hệ hỗ trợ (hotline, yêu cầu gọi lại), gửi tin nhắn tư vấn và theo dõi lịch sử tư vấn.'
)]
#[OA\Tag(
    name: 'Customer Meeting',
    description: 'Gặp gỡ khách hàng: nhân viên sale check-in hoạt động gặp khách tại dự án bằng GPS và ảnh minh chứng, xem lịch sử các lượt gặp khách.'
)]
#[OA\Tag(
    name: 'Department Transfers',
    description: 'Yêu cầu chuyển phòng ban: nhân viên gửi yêu cầu, Director xem danh sách, phê duyệt hoặc từ chối yêu cầu chuyển phòng ban.'
)]
#[OA\Tag(
    name: 'Leave',
    description: 'Quản lý nghỉ phép: nhân viên gửi đơn xin nghỉ phép và xem lịch sử; Team Leader/Admin xem danh sách, phê duyệt hoặc từ chối đơn nghỉ phép.'
)]
#[OA\Tag(
    name: 'LegalVideo',
    description: 'Thư viện video pháp lý: xem và tìm kiếm các video kiến thức pháp lý bất động sản theo danh mục (pháp lý dự án, hợp đồng, quy hoạch, quy trình giao dịch).'
)]
#[OA\Tag(
    name: 'Learning',
    description: 'Học tập và đào tạo bắt buộc của nhân viên: xem danh sách khóa học, học bài, làm quiz, nộp bài kiểm tra và nhận chứng nhận hoàn thành.'
)]
#[OA\Tag(
    name: 'Learning Admin',
    description: 'Quản lý LMS dành cho Super Admin: tạo/sửa/xóa khóa học, bài học, quiz; theo dõi tiến độ onboarding nhân viên và xác nhận hoàn thành.'
)]
#[OA\Tag(
    name: 'News',
    description: 'Tin tức và bảng tin nội bộ: xem/tìm kiếm tin tức công khai; đọc, đăng, chỉnh sửa, bình luận và tương tác bài viết bảng tin nội bộ theo phòng ban.'
)]
#[OA\Tag(
    name: 'Manage Comment',
    description: 'Quản lý bình luận (Admin): xem danh sách toàn bộ bình luận trên hệ thống và xóa bình luận vi phạm theo loại nguồn (lô đất, tin tức công khai, bảng tin nội bộ).'
)]
#[OA\Tag(
    name: 'Public Planning',
    description: 'Thông tin quy hoạch công khai: khách hàng và người dùng chưa đăng nhập có thể xem danh sách, chi tiết và tìm kiếm thông tin quy hoạch bất động sản theo khu vực.'
)]
#[OA\Tag(
    name: 'Planning',
    description: 'Quy hoạch có yêu cầu đăng nhập: khách hàng tải PDF hồ sơ quy hoạch; Admin mô phỏng kiểm tra quy hoạch lô đất qua API bên thứ ba.'
)]
#[OA\Tag(
    name: 'Public Project',
    description: 'Dự án bất động sản công khai: khách hàng và người dùng chưa đăng nhập xem danh sách, chi tiết, tìm kiếm dự án, tải brochure và lấy số hotline tư vấn.'
)]
#[OA\Tag(
    name: 'Admin Project',
    description: 'Quản lý dự án bất động sản (Admin): Super Admin và General Director tạo, cập nhật, khóa/mở khóa dự án; tạo dự án hàng loạt kèm bảng hàng và lô đất.'
)]
#[OA\Tag(
    name: 'Admin Project Assignments',
    description: 'Phân quyền truy cập dự án/bảng hàng (Admin): cấp hoặc thu hồi quyền xem/thao tác inventory cho nhân viên hoặc phòng ban cụ thể.'
)]
#[OA\Tag(
    name: 'Admin Area & Lots',
    description: 'Quản lý khu đất và lô đất bất động sản (Admin): khóa/mở khóa lô đất để kiểm soát trạng thái bán hàng.'
)]
#[OA\Tag(
    name: 'Area',
    description: 'Khu đất và bảng hàng dành cho nhân viên: xem danh sách khu đất, chi tiết bảng hàng, trạng thái lô đất theo dự án được phân quyền.'
)]
#[OA\Tag(
    name: 'Site Tour',
    description: 'Dẫn khách tham quan thực tế: nhân viên check-in hoạt động dẫn khách tại dự án bằng GPS và ảnh minh chứng; xem lịch sử và các lượt dẫn khách gần đây.'
)]
abstract class BaseController
{
    use AuthorizesRequests, HandleApi, ValidatesRequests;
}

