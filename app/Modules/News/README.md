# Module News (Bảng Tin)

Module này chịu trách nhiệm quản lý hoạt động tin tức công khai và bảng tin nội bộ của công ty.

## Tính năng / Use Cases
- **UC-08: View News**: Xem danh sách tin tức công khai (tin nổi bật và tin mới nhất phân trang).
- **UC-09: Search News**: Tìm kiếm tin tức công khai theo từ khóa.
- **UC-11: View News Details**: Xem chi tiết bài viết công khai kèm tin liên quan.
- **UC-12: Like News**: Thích hoặc bỏ thích bài viết tin tức.
- **UC-060: View Internal News Feed**: Xem bảng tin nội bộ được phân quyền theo cơ cấu tổ chức:
  - **Employee (agent)**: Chỉ xem bài viết thuộc phòng ban (department) của mình.
  - **Team Leader (broker)**: Chỉ xem bài viết thuộc phòng ban mình quản lý.
  - **Director (admin)**: Xem bài viết của tất cả phòng ban thuộc khu vực (area) mình quản lý.

## Database Schema
1. **news**: Lưu thông tin bài viết (Tiêu đề, slug, tóm tắt, nội dung, ảnh thumbnail, danh mục, phòng ban `department`, khu vực `area`, lượt thích, trạng thái xuất bản).
2. **news_likes**: Lưu thông tin lượt thích bài viết của người dùng.

## API Endpoints
- `GET /api/v1/news` - Xem danh sách tin tức công khai.
- `GET /api/v1/news/search` - Tìm kiếm tin tức công khai.
- `GET /api/v1/news/internal` - Xem bảng tin nội bộ dựa trên phòng ban hoặc khu vực quản lý của người dùng hiện tại (UC-060).
- `GET /api/v1/news/{idOrSlug}` - Xem chi tiết bài viết tin tức.
- `POST /api/v1/news/{id}/like` - Thích hoặc bỏ thích bài viết tin tức.
