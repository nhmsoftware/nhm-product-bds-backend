# 06 — DATABASE SCHEMA (BDD)
# BDS APP — Thiết kế toàn bộ bảng dữ liệu

> **Tài liệu tham chiếu schema chuẩn. AI PHẢI đọc file này trước khi tạo Migration hoặc Model mới.**
> Mọi bảng mới phải tuân thủ quy tắc trong `05-DATABASE-RULES.md`.

---

## TỔNG QUAN — 17 BẢNG / 4 NHÓM

| Nhóm | Bảng |
|---|---|
| Core BDS | `users`, `broker_agent`, `properties`, `property_media`, `conversations`, `chat_messages` |
| Notification & Search | `notifications`, `saved_searches` |
| Khóa học | `courses`, `course_lessons`, `course_quizzes`, `course_enrollments`, `lesson_progress`, `quiz_attempts` |
| Chấm công & Bảng tin | `attendances`, `announcements`, `announcement_reads` |

---

## NHÓM 1 — CORE BDS

### Bảng: `users`

| Cột | Kiểu | Ghi chú |
|---|---|---|
| `id` | `uuid` | Primary key |
| `name` | `varchar(255)` | Họ tên |
| `email` | `varchar(255)` | Unique |
| `phone` | `varchar(20)` | Nullable, unique |
| `password` | `varchar(255)` | Bcrypt hash |
| `role` | `enum` | `admin` \| `agent` \| `broker` \| `buyer` |
| `avatar` | `varchar(255)` | Nullable, URL ảnh |
| `fcm_token` | `varchar(255)` | Nullable, Firebase push token |
| `is_active` | `boolean` | Default: `true` |
| `email_verified_at` | `timestamp` | Nullable |
| `created_at` | `timestamp` | Auto |
| `updated_at` | `timestamp` | Auto |
| `deleted_at` | `timestamp` | Soft delete |

---

### Bảng: `broker_agent`

| Cột | Kiểu | Ghi chú |
|---|---|---|
| `id` | `uuid` | Primary key |
| `broker_id` | `uuid` | FK → `users.id` |
| `agent_id` | `uuid` | FK → `users.id` |
| `joined_at` | `timestamp` | Nullable |
| `created_at` | `timestamp` | Auto |
| `updated_at` | `timestamp` | Auto |

> Unique constraint: `(broker_id, agent_id)`

---

### Bảng: `properties`

| Cột | Kiểu | Ghi chú |
|---|---|---|
| `id` | `uuid` | Primary key |
| `agent_id` | `uuid` | FK → `users.id` |
| `title` | `varchar(255)` | Tiêu đề listing |
| `description` | `text` | Nullable |
| `type` | `enum` | `apartment` \| `house` \| `land` \| `office` \| `shophouse` |
| `listing_type` | `enum` | `sale` \| `rent` |
| `status` | `enum` | `available` \| `reserved` \| `sold` — Default: `available` |
| `price` | `decimal(15,2)` | Giá (VND) |
| `area` | `decimal(10,2)` | Nullable, đơn vị m² |
| `address` | `varchar(255)` | Địa chỉ đầy đủ |
| `district` | `varchar(100)` | Nullable |
| `city` | `varchar(100)` | Nullable |
| `latitude` | `decimal(10,7)` | Nullable |
| `longitude` | `decimal(10,7)` | Nullable |
| `bedrooms` | `integer` | Nullable |
| `bathrooms` | `integer` | Nullable |
| `floors` | `integer` | Nullable |
| `is_featured` | `boolean` | Default: `false` |
| `is_approved` | `boolean` | Default: `false` |
| `approved_at` | `timestamp` | Nullable |
| `created_at` | `timestamp` | Auto |
| `updated_at` | `timestamp` | Auto |
| `deleted_at` | `timestamp` | Soft delete |

---

### Bảng: `property_media`

| Cột | Kiểu | Ghi chú |
|---|---|---|
| `id` | `uuid` | Primary key |
| `property_id` | `uuid` | FK → `properties.id` |
| `type` | `enum` | `image` \| `video` |
| `url` | `varchar(255)` | URL file |
| `thumbnail_url` | `varchar(255)` | Nullable |
| `sort_order` | `integer` | Default: `0` |
| `is_cover` | `boolean` | Default: `false` |
| `created_at` | `timestamp` | Auto |
| `updated_at` | `timestamp` | Auto |
| `deleted_at` | `timestamp` | Soft delete |

---

### Bảng: `conversations`

| Cột | Kiểu | Ghi chú |
|---|---|---|
| `id` | `uuid` | Primary key |
| `property_id` | `uuid` | Nullable, FK → `properties.id` |
| `buyer_id` | `uuid` | FK → `users.id` |
| `agent_id` | `uuid` | FK → `users.id` |
| `last_message_at` | `timestamp` | Nullable |
| `created_at` | `timestamp` | Auto |
| `updated_at` | `timestamp` | Auto |
| `deleted_at` | `timestamp` | Soft delete |

> Unique constraint: `(buyer_id, agent_id, property_id)`

---

### Bảng: `chat_messages`

| Cột | Kiểu | Ghi chú |
|---|---|---|
| `id` | `uuid` | Primary key |
| `conversation_id` | `uuid` | FK → `conversations.id` |
| `sender_id` | `uuid` | FK → `users.id` |
| `body` | `text` | Nội dung tin nhắn |
| `type` | `enum` | `text` \| `image` \| `file` — Default: `text` |
| `attachment_url` | `varchar(255)` | Nullable |
| `read_at` | `timestamp` | Nullable |
| `created_at` | `timestamp` | Auto |
| `updated_at` | `timestamp` | Auto |
| `deleted_at` | `timestamp` | Soft delete |

---

## NHÓM 2 — NOTIFICATION & SEARCH

### Bảng: `notifications`

| Cột | Kiểu | Ghi chú |
|---|---|---|
| `id` | `uuid` | Primary key |
| `user_id` | `uuid` | FK → `users.id` |
| `type` | `varchar(100)` | Vd: `PropertyApproved`, `NewMessage` |
| `title` | `varchar(255)` | Tiêu đề thông báo |
| `body` | `text` | Nội dung |
| `data` | `jsonb` | Nullable, payload linh hoạt |
| `channel` | `varchar(50)` | `fcm` \| `reverb` \| `both` — Default: `fcm` |
| `read_at` | `timestamp` | Nullable |
| `created_at` | `timestamp` | Auto |
| `updated_at` | `timestamp` | Auto |

> ⚠️ Bảng này **KHÔNG có `deleted_at`** — lịch sử notification phải giữ nguyên.

---

### Bảng: `saved_searches`

| Cột | Kiểu | Ghi chú |
|---|---|---|
| `id` | `uuid` | Primary key |
| `user_id` | `uuid` | FK → `users.id` |
| `name` | `varchar(100)` | Nullable, tên tìm kiếm |
| `criteria` | `jsonb` | Filter params lưu dạng JSON |
| `notify_enabled` | `boolean` | Default: `true` |
| `geofence_lat` | `decimal(10,7)` | Nullable |
| `geofence_lng` | `decimal(10,7)` | Nullable |
| `geofence_radius` | `integer` | Nullable, đơn vị: mét |
| `created_at` | `timestamp` | Auto |
| `updated_at` | `timestamp` | Auto |
| `deleted_at` | `timestamp` | Soft delete |

---

## NHÓM 3 — KHÓA HỌC

### Bảng: `courses`

| Cột | Kiểu | Ghi chú |
|---|---|---|
| `id` | `uuid` | Primary key |
| `title` | `varchar(255)` | Tên khóa học |
| `description` | `text` | Nullable |
| `thumbnail` | `varchar(255)` | Nullable, URL ảnh cover |
| `is_required` | `boolean` | Default: `true` — Bắt buộc với nhân viên mới |
| `order` | `integer` | Thứ tự hiển thị |
| `is_active` | `boolean` | Default: `true` |
| `created_at` | `timestamp` | Auto |
| `updated_at` | `timestamp` | Auto |
| `deleted_at` | `timestamp` | Soft delete |

---

### Bảng: `course_lessons`

| Cột | Kiểu | Ghi chú |
|---|---|---|
| `id` | `uuid` | Primary key |
| `course_id` | `uuid` | FK → `courses.id` |
| `title` | `varchar(255)` | Tên bài học |
| `content` | `text` | Nullable, nội dung text/HTML |
| `video_url` | `varchar(255)` | Nullable, URL video |
| `duration_minutes` | `integer` | Nullable, thời lượng |
| `order` | `integer` | Thứ tự trong khóa |
| `created_at` | `timestamp` | Auto |
| `updated_at` | `timestamp` | Auto |
| `deleted_at` | `timestamp` | Soft delete |

---

### Bảng: `course_quizzes`

| Cột | Kiểu | Ghi chú |
|---|---|---|
| `id` | `uuid` | Primary key |
| `lesson_id` | `uuid` | FK → `course_lessons.id` |
| `question` | `text` | Câu hỏi |
| `options` | `jsonb` | Mảng các đáp án: `["A", "B", "C", "D"]` |
| `correct_option` | `integer` | Index đáp án đúng (0-based) |
| `created_at` | `timestamp` | Auto |
| `updated_at` | `timestamp` | Auto |
| `deleted_at` | `timestamp` | Soft delete |

---

### Bảng: `course_enrollments`

| Cột | Kiểu | Ghi chú |
|---|---|---|
| `id` | `uuid` | Primary key |
| `user_id` | `uuid` | FK → `users.id` |
| `course_id` | `uuid` | FK → `courses.id` |
| `status` | `enum` | `not_started` \| `in_progress` \| `completed` |
| `progress_percent` | `decimal(5,2)` | Default: `0.00` |
| `completed_at` | `timestamp` | Nullable |
| `created_at` | `timestamp` | Auto |
| `updated_at` | `timestamp` | Auto |

> Unique constraint: `(user_id, course_id)`

---

### Bảng: `lesson_progress`

| Cột | Kiểu | Ghi chú |
|---|---|---|
| `id` | `uuid` | Primary key |
| `enrollment_id` | `uuid` | FK → `course_enrollments.id` |
| `lesson_id` | `uuid` | FK → `course_lessons.id` |
| `is_completed` | `boolean` | Default: `false` |
| `completed_at` | `timestamp` | Nullable |
| `created_at` | `timestamp` | Auto |
| `updated_at` | `timestamp` | Auto |

> Unique constraint: `(enrollment_id, lesson_id)`

---

### Bảng: `quiz_attempts`

| Cột | Kiểu | Ghi chú |
|---|---|---|
| `id` | `uuid` | Primary key |
| `user_id` | `uuid` | FK → `users.id` |
| `quiz_id` | `uuid` | FK → `course_quizzes.id` |
| `selected_option` | `integer` | Index đáp án user chọn (0-based) |
| `is_correct` | `boolean` | Đúng hay sai |
| `created_at` | `timestamp` | Auto |
| `updated_at` | `timestamp` | Auto |

---

## NHÓM 4 — CHẤM CÔNG & BẢNG TIN

### Bảng: `attendances`

| Cột | Kiểu | Ghi chú |
|---|---|---|
| `id` | `uuid` | Primary key |
| `user_id` | `uuid` | FK → `users.id` |
| `work_date` | `date` | Ngày làm việc |
| `check_in_at` | `timestamp` | Nullable |
| `check_in_lat` | `decimal(10,7)` | Nullable, tọa độ GPS |
| `check_in_lng` | `decimal(10,7)` | Nullable, tọa độ GPS |
| `check_in_qr_code` | `varchar(255)` | Nullable, mã QR đã quét |
| `check_in_method` | `enum` | Nullable, `gps` \| `qr` |
| `check_out_at` | `timestamp` | Nullable |
| `check_out_lat` | `decimal(10,7)` | Nullable |
| `check_out_lng` | `decimal(10,7)` | Nullable |
| `check_out_qr_code` | `varchar(255)` | Nullable |
| `check_out_method` | `enum` | Nullable, `gps` \| `qr` |
| `status` | `enum` | `present` \| `late` \| `absent` \| `half_day` |
| `note` | `text` | Nullable, ghi chú |
| `created_at` | `timestamp` | Auto |
| `updated_at` | `timestamp` | Auto |
| `deleted_at` | `timestamp` | Soft delete |

> Unique constraint: `(user_id, work_date)`

---

### Bảng: `announcements`

| Cột | Kiểu | Ghi chú |
|---|---|---|
| `id` | `uuid` | Primary key |
| `author_id` | `uuid` | FK → `users.id` (admin hoặc manager) |
| `title` | `varchar(255)` | Tiêu đề bài đăng |
| `body` | `text` | Nội dung (HTML/Markdown) |
| `attachments` | `jsonb` | Nullable, mảng file đính kèm: `[{type, url, name}]` |
| `visibility` | `enum` | `all` \| `admin` \| `agent` \| `broker` \| `buyer` |
| `is_pinned` | `boolean` | Default: `false` — Ghim lên đầu |
| `published_at` | `timestamp` | Nullable — null = nháp |
| `created_at` | `timestamp` | Auto |
| `updated_at` | `timestamp` | Auto |
| `deleted_at` | `timestamp` | Soft delete |

> `attachments` JSON structure:
> ```json
> [
>   { "type": "image", "url": "...", "name": "photo.jpg" },
>   { "type": "pdf",   "url": "...", "name": "report.pdf" },
>   { "type": "video", "url": "...", "name": "clip.mp4" }
> ]
> ```

---

### Bảng: `announcement_reads`

| Cột | Kiểu | Ghi chú |
|---|---|---|
| `id` | `uuid` | Primary key |
| `announcement_id` | `uuid` | FK → `announcements.id` |
| `user_id` | `uuid` | FK → `users.id` |
| `read_at` | `timestamp` | Thời điểm đọc |
| `created_at` | `timestamp` | Auto |
| `updated_at` | `timestamp` | Auto |

> Unique constraint: `(announcement_id, user_id)`

---

## QUAN HỆ TỔNG HỢP

```
users ──< broker_agent >── users
users ──< properties
properties ──< property_media
properties ──< conversations
users ──< conversations (buyer)
users ──< conversations (agent)
conversations ──< chat_messages
users ──< notifications
users ──< saved_searches

courses ──< course_lessons
course_lessons ──< course_quizzes
users ──< course_enrollments >── courses
course_enrollments ──< lesson_progress >── course_lessons
users ──< quiz_attempts >── course_quizzes

users ──< attendances
users ──< announcements (author)
announcements ──< announcement_reads >── users
```

---

## ENUM TỔNG HỢP

| Enum | Giá trị |
|---|---|
| `users.role` | `admin`, `agent`, `broker`, `buyer` |
| `properties.type` | `apartment`, `house`, `land`, `office`, `shophouse` |
| `properties.listing_type` | `sale`, `rent` |
| `properties.status` | `available`, `reserved`, `sold` |
| `property_media.type` | `image`, `video` |
| `chat_messages.type` | `text`, `image`, `file` |
| `course_enrollments.status` | `not_started`, `in_progress`, `completed` |
| `attendances.check_in_method` | `gps`, `qr` |
| `attendances.check_out_method` | `gps`, `qr` |
| `attendances.status` | `present`, `late`, `absent`, `half_day` |
| `announcements.visibility` | `all`, `admin`, `agent`, `broker`, `buyer` |
