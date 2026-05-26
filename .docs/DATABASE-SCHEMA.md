# Database Schema (BDS App Backend)

> Tài liệu này được sinh tự động từ các file migration thực tế tại `database/migrations/`.  
> **Cập nhật lần cuối**: 2026-05-26

---

## Nhóm 1: Người dùng & Hồ sơ nhân viên

### `users`
| Cột | Kiểu | Mặc định | Ghi chú |
|-----|------|----------|---------|
| `id` | uuid | PK | |
| `staff_code` | string | nullable | unique |
| `name` | string | | |
| `email` | string | | unique |
| `phone` | string | nullable | unique |
| `email_verified_at` | timestamp | nullable | |
| `password` | string | | |
| `role` | tinyInteger | `4` | 1: Super Admin, 2: Admin, 3: Manager, 4: Employee |
| `avatar` | string | nullable | |
| `address` | string(255) | nullable | |
| `fcm_token` | string | nullable | FCM push notification token |
| `is_active` | boolean | `true` | |
| `department` | string | nullable | Phòng ban nhân viên |
| `job_position` | string | nullable | Vị trí công việc |
| `area` | string | nullable | Khu vực quản lý/làm việc |
| `remember_token` | string | nullable | |
| `created_at` | timestamp | | |
| `updated_at` | timestamp | | |
| `deleted_at` | timestamp | nullable | SoftDeletes |

---

### `employee_profiles`
| Cột | Kiểu | Mặc định | Ghi chú |
|-----|------|----------|---------|
| `id` | uuid | PK | |
| `user_id` | uuid | FK → users | unique |
| `employee_title` | string | nullable | Danh hiệu nhân viên (VD: Nhân viên xuất sắc) |
| `identity_card` | string | nullable | Số CCCD |
| `dob` | date | nullable | Ngày sinh |
| `bank_account_name` | string | nullable | Chủ tài khoản |
| `bank_account_number` | string | nullable | Số tài khoản |
| `bank_name` | string | nullable | Tên ngân hàng |
| `education` | text | nullable | Học vấn |
| `major` | string | nullable | Chuyên ngành |
| `experience` | text | nullable | Kinh nghiệm làm việc |
| `attachments` | jsonb | nullable | Danh sách tài liệu đính kèm `[{type, name, url}]` |
| `reward_points` | integer | `0` | Điểm thưởng tích lũy |
| `kpi_stars` | integer | `0` | Số sao KPI |
| `created_at` | timestamp | | |
| `updated_at` | timestamp | | |

---

### `reward_point_histories`
| Cột | Kiểu | Mặc định | Ghi chú |
|-----|------|----------|---------|
| `id` | uuid | PK | |
| `user_id` | uuid | FK → users | |
| `points_changed` | integer | | Số điểm thay đổi (dương: cộng, âm: trừ) |
| `stars_changed` | integer | `0` | Số sao thay đổi |
| `reason` | string | nullable | Lý do |
| `related_id` | uuid | nullable | ID giao dịch/yêu cầu liên quan |
| `created_at` | timestamp | | |
| `updated_at` | timestamp | | |

---

## Nhóm 2: Tin tức

### `news`
| Cột | Kiểu | Mặc định | Ghi chú |
|-----|------|----------|---------|
| `id` | uuid | PK | |
| `title` | string | | |
| `slug` | string | | unique |
| `summary` | text | nullable | |
| `content` | text | | |
| `thumbnail` | string | nullable | |
| `category` | string | | indexed |
| `author_id` | uuid | | FK → users (không có foreign key cứng) |
| `is_published` | boolean | `false` | |
| `is_featured` | boolean | `false` | |
| `likes_count` | integer | `0` | Bộ đếm like (denormalized) |
| `published_at` | timestamp | nullable | |
| `department` | string | nullable | Phòng ban đăng tin / được xem |
| `area` | string | nullable | Khu vực đăng tin / được xem |
| `created_at` | timestamp | | |
| `updated_at` | timestamp | | |
| `deleted_at` | timestamp | nullable | SoftDeletes |

### `news_likes`
| Cột | Kiểu | Mặc định | Ghi chú |
|-----|------|----------|---------|
| `id` | uuid | PK | |
| `user_id` | uuid | FK → users | unique(user_id, news_id) |
| `news_id` | uuid | FK → news | |
| `created_at` | timestamp | | |
| `updated_at` | timestamp | | |

### `news_comments`
| Cột | Kiểu | Mặc định | Ghi chú |
|-----|------|----------|---------|
| `id` | uuid | PK | |
| `user_id` | uuid | FK → users | |
| `news_id` | uuid | FK → news | |
| `content` | text | | |
| `created_at` | timestamp | | |
| `updated_at` | timestamp | | |
| `deleted_at` | timestamp | nullable | SoftDeletes |

---

## Nhóm 3: Bất động sản (Dự án, Phân khu, Lô đất)

### `projects`
| Cột | Kiểu | Mặc định | Ghi chú |
|-----|------|----------|---------|
| `id` | uuid | PK | |
| `name` | string | | |
| `location` | string | | |
| `google_maps_url` | string | nullable | |
| `image` | string | nullable | |
| `banner` | string | nullable | |
| `price` | decimal(20,2) | `0` | |
| `status` | tinyInteger | `1` | 1: opening, 2: coming_soon, 3: sold_out |
| `is_public` | boolean | `true` | |
| `description` | text | nullable | |
| `amenities` | jsonb | nullable | Tiện ích |
| `floor_plans` | jsonb | nullable | Mặt bằng |
| `legal_info` | jsonb | nullable | Thông tin pháp lý |
| `brochure` | string | nullable | |
| `contact_info` | jsonb | nullable | |
| `planning_info` | jsonb | nullable | |
| `keywords` | string | nullable | Từ khóa |
| `type` | string | nullable | Loại dự án |
| `supervisor_id` | uuid | nullable | FK → users |
| `sales_manager_id` | uuid | nullable | FK → users |
| `marketing_manager_id` | uuid | nullable | FK → users |
| `created_at` | timestamp | | |
| `updated_at` | timestamp | | |
| `deleted_at` | timestamp | nullable | SoftDeletes |

### `areas`
| Cột | Kiểu | Mặc định | Ghi chú |
|-----|------|----------|---------|
| `id` | uuid | PK | |
| `project_id` | uuid | nullable | FK → projects |
| `name` | string | | |
| `sales_board_image` | string | nullable | Ảnh bảng hàng |
| `total_lots` | integer | `0` | Tổng số lô |
| `remaining_lots` | integer | `0` | Số lô còn lại |
| `is_featured` | boolean | `false` | |
| `created_at` | timestamp | | |
| `updated_at` | timestamp | | |
| `deleted_at` | timestamp | nullable | SoftDeletes |

### `area_assignments`
| Cột | Kiểu | Mặc định | Ghi chú |
|-----|------|----------|---------|
| `id` | uuid | PK | |
| `area_id` | uuid | FK → areas | unique(user_id, area_id) |
| `user_id` | uuid | FK → users | |
| `created_at` | timestamp | | |
| `updated_at` | timestamp | | |
| `deleted_at` | timestamp | nullable | SoftDeletes |

### `project_assignments`
| Cột | Kiểu | Mặc định | Ghi chú |
|-----|------|----------|---------|
| `id` | uuid | PK | |
| `project_id` | uuid | FK → projects | |
| `assignable_id` | uuid | | ID user hoặc department |
| `assignable_type` | string | | `'user'` hoặc `'department'` |
| `permissions` | json | nullable | |
| `created_at` | timestamp | | |
| `updated_at` | timestamp | | |
| `deleted_at` | timestamp | nullable | SoftDeletes |

### `lots`
| Cột | Kiểu | Mặc định | Ghi chú |
|-----|------|----------|---------|
| `id` | uuid | PK | |
| `area_id` | uuid | FK → areas | unique(area_id, code) |
| `code` | string | | Mã lô |
| `status` | integer | | 1: available, 2: sold, 3: reserved, 4: unavailable |
| `image_url` | string | nullable | |
| `area_size` | decimal(10,2) | nullable | Diện tích (m²) |
| `frontage` | decimal(8,2) | nullable | Mặt tiền (m) |
| `direction` | string | nullable | Hướng |
| `legal` | string | nullable | Tình trạng pháp lý |
| `description` | text | nullable | |
| `planning_id` | uuid | nullable | FK → plannings |
| `price` | bigInteger | nullable | Giá bán |
| `unit_price` | bigInteger | nullable | Đơn giá |
| `coordinate_x` | integer | nullable | |
| `coordinate_y` | integer | nullable | |
| `width` | integer | nullable | |
| `height` | integer | nullable | |
| `is_locked` | boolean | `false` | |
| `created_at` | timestamp | | |
| `updated_at` | timestamp | | |
| `deleted_at` | timestamp | nullable | SoftDeletes |

### `lot_comments`
| Cột | Kiểu | Mặc định | Ghi chú |
|-----|------|----------|---------|
| `id` | uuid | PK | |
| `lot_id` | uuid | FK → lots | |
| `user_id` | uuid | FK → users | |
| `content` | text | | |
| `created_at` | timestamp | | |
| `updated_at` | timestamp | | |
| `deleted_at` | timestamp | nullable | SoftDeletes |

### `lot_lock_requests`
| Cột | Kiểu | Mặc định | Ghi chú |
|-----|------|----------|---------|
| `id` | uuid | PK | |
| `lot_id` | uuid | FK → lots | |
| `user_id` | uuid | FK → users | |
| `reason` | text | nullable | |
| `created_at` | timestamp | | |
| `updated_at` | timestamp | | |
| `deleted_at` | timestamp | nullable | SoftDeletes |

### `lot_deposit_requests`
| Cột | Kiểu | Mặc định | Ghi chú |
|-----|------|----------|---------|
| `id` | uuid | PK | |
| `lot_id` | uuid | FK → lots | |
| `user_id` | uuid | FK → users | |
| `status` | integer | `1` | 1: PENDING, 2: APPROVED, 3: REJECTED |
| `reason` | text | nullable | Lý do từ chối |
| `reject_reason` | text | nullable | |
| `created_at` | timestamp | | |
| `updated_at` | timestamp | | |
| `deleted_at` | timestamp | nullable | SoftDeletes |

---

## Nhóm 4: Quy hoạch

### `plannings`
| Cột | Kiểu | Mặc định | Ghi chú |
|-----|------|----------|---------|
| `id` | uuid | PK | |
| `title` | string | | |
| `map_image` | string | | |
| `status` | tinyInteger | | 1: approved, 2: pending, 3: cancelled |
| `updated_year` | integer | | |
| `description` | text | | |
| `city` | string | | |
| `district` | string | nullable | |
| `content` | longText | nullable | |
| `investor` | string | nullable | |
| `area_size` | string | nullable | |
| `project_purpose` | string | nullable | |
| `created_at` | timestamp | | |
| `updated_at` | timestamp | | |
| `deleted_at` | timestamp | nullable | SoftDeletes |

---

## Nhóm 5: Khóa học (LMS - Learning)

### `courses`
| Cột | Kiểu | Mặc định | Ghi chú |
|-----|------|----------|---------|
| `id` | uuid | PK | |
| `title` | string(255) | | |
| `description` | text | nullable | |
| `thumbnail` | string(255) | nullable | URL ảnh cover |
| `is_required` | boolean | `true` | |
| `department` | string(100) | nullable | Phân bổ theo phòng ban |
| `job_position` | string(100) | nullable | Phân bổ theo vị trí |
| `order` | integer | `0` | Thứ tự hiển thị |
| `is_active` | boolean | `true` | |
| `has_certificate` | boolean | `true` | Có cấp chứng chỉ không |
| `created_at` | timestamp | | |
| `updated_at` | timestamp | | |
| `deleted_at` | timestamp | nullable | SoftDeletes |

### `course_lessons`
| Cột | Kiểu | Mặc định | Ghi chú |
|-----|------|----------|---------|
| `id` | uuid | PK | |
| `course_id` | uuid | FK → courses | cascade |
| `title` | string(255) | | |
| `content` | text | nullable | Nội dung text/HTML |
| `video_url` | string(255) | nullable | URL video bài học |
| `duration_minutes` | integer | nullable | Thời lượng video (phút) |
| `order` | integer | `0` | Thứ tự trong khóa học |
| `is_active` | boolean | `true` | |
| `attachments` | jsonb | nullable | `[{type, url, name}]` |
| `created_at` | timestamp | | |
| `updated_at` | timestamp | | |
| `deleted_at` | timestamp | nullable | SoftDeletes |

### `course_quizzes`
| Cột | Kiểu | Mặc định | Ghi chú |
|-----|------|----------|---------|
| `id` | uuid | PK | |
| `lesson_id` | uuid | FK → course_lessons | cascade |
| `question` | text | | Nội dung câu hỏi |
| `options` | jsonb | | Danh sách đáp án (mảng string) |
| `correct_option` | integer | | Index đáp án đúng (0-based) |
| `created_at` | timestamp | | |
| `updated_at` | timestamp | | |
| `deleted_at` | timestamp | nullable | SoftDeletes |

### `course_enrollments`
| Cột | Kiểu | Mặc định | Ghi chú |
|-----|------|----------|---------|
| `id` | uuid | PK | |
| `user_id` | uuid | FK → users | unique(user_id, course_id) |
| `course_id` | uuid | FK → courses | |
| `status` | tinyInteger | `1` | 1: not_started, 2: in_progress, 3: completed |
| `progress_percent` | decimal(5,2) | `0.00` | Tiến độ hoàn thành (%) |
| `completed_at` | timestamp | nullable | |
| `created_at` | timestamp | | |
| `updated_at` | timestamp | | |

### `lesson_progress`
| Cột | Kiểu | Mặc định | Ghi chú |
|-----|------|----------|---------|
| `id` | uuid | PK | |
| `enrollment_id` | uuid | FK → course_enrollments | unique(enrollment_id, lesson_id) |
| `lesson_id` | uuid | FK → course_lessons | |
| `is_completed` | boolean | `false` | |
| `completed_at` | timestamp | nullable | |
| `current_watch_seconds` | integer | `0` | Thời lượng video đã xem hiện tại (giây) |
| `created_at` | timestamp | | |
| `updated_at` | timestamp | | |

### `quiz_attempts`
| Cột | Kiểu | Mặc định | Ghi chú |
|-----|------|----------|---------|
| `id` | uuid | PK | |
| `user_id` | uuid | FK → users | |
| `quiz_id` | uuid | FK → course_quizzes | |
| `selected_option` | integer | nullable | Index đáp án đã chọn (0-based) |
| `is_correct` | boolean | nullable | |
| `is_draft` | boolean | `false` | Lưu nháp hay đã nộp |
| `created_at` | timestamp | | |
| `updated_at` | timestamp | | |

---

## Nhóm 6: Hoạt động nhân viên

### `attendances`
| Cột | Kiểu | Mặc định | Ghi chú |
|-----|------|----------|---------|
| `id` | uuid | PK | |
| `user_id` | uuid | FK → users | unique(user_id, work_date) |
| `work_date` | date | | |
| `check_in_at` | timestamp | nullable | |
| `check_in_lat` | decimal(10,7) | nullable | |
| `check_in_lng` | decimal(10,7) | nullable | |
| `check_in_method` | enum | nullable | `gps`, `wifi`, `qr` |
| `check_in_wifi_ssid` | string | nullable | |
| `check_in_device_name` | string | nullable | |
| `check_out_at` | timestamp | nullable | |
| `check_out_lat` | decimal(10,7) | nullable | |
| `check_out_lng` | decimal(10,7) | nullable | |
| `check_out_method` | enum | nullable | `gps`, `wifi`, `qr` |
| `check_out_wifi_ssid` | string | nullable | |
| `check_out_device_name` | string | nullable | |
| `status` | tinyInteger | `1` | 1: on_time, 2: late, 3: absent |
| `note` | text | nullable | |
| `created_at` | timestamp | | |
| `updated_at` | timestamp | | |
| `deleted_at` | timestamp | nullable | SoftDeletes |

### `leave_requests`
| Cột | Kiểu | Mặc định | Ghi chú |
|-----|------|----------|---------|
| `id` | uuid | PK | |
| `user_id` | uuid | FK → users | |
| `leave_type` | string | | annual, unpaid, personal, maternity, business, compensatory |
| `start_date` | date | | |
| `end_date` | date | | |
| `reason` | text | | |
| `status` | tinyInteger | `1` | 1: pending, 2: approved, 3: rejected |
| `rejection_reason` | text | nullable | |
| `created_at` | timestamp | | |
| `updated_at` | timestamp | | |
| `deleted_at` | timestamp | nullable | SoftDeletes |

### `department_transfer_requests`
| Cột | Kiểu | Mặc định | Ghi chú |
|-----|------|----------|---------|
| `id` | uuid | PK | |
| `user_id` | uuid | FK → users | |
| `current_department` | string | | Phòng ban hiện tại |
| `target_department` | string | | Phòng ban muốn chuyển |
| `reason` | text | | |
| `desired_transfer_date` | date | | |
| `status` | tinyInteger | `1` | 1: pending, 2: approved, 3: rejected |
| `rejection_reason` | text | nullable | |
| `created_at` | timestamp | | |
| `updated_at` | timestamp | | |
| `deleted_at` | timestamp | nullable | SoftDeletes |

### `customer_meetings`
| Cột | Kiểu | Mặc định | Ghi chú |
|-----|------|----------|---------|
| `id` | uuid | PK | |
| `user_id` | uuid | FK → users | |
| `project_id` | uuid | FK → projects | |
| `customer_name` | string | | |
| `customer_phone` | string | | |
| `image_path` | string | | Ảnh minh chứng |
| `latitude` | decimal(10,7) | | |
| `longitude` | decimal(10,7) | | |
| `created_at` | timestamp | | |
| `updated_at` | timestamp | | |
| `deleted_at` | timestamp | nullable | SoftDeletes |

### `site_tours`
| Cột | Kiểu | Mặc định | Ghi chú |
|-----|------|----------|---------|
| `id` | uuid | PK | |
| `user_id` | uuid | FK → users | |
| `project_id` | uuid | FK → projects | |
| `unit_code` | string | | Mã căn hộ/lô tham quan |
| `customer_name` | string | | |
| `image_path` | string | | Ảnh minh chứng |
| `latitude` | decimal(10,7) | | |
| `longitude` | decimal(10,7) | | |
| `created_at` | timestamp | | |
| `updated_at` | timestamp | | |
| `deleted_at` | timestamp | nullable | SoftDeletes |

---

## Nhóm 7: Tư vấn

### `consultation_settings`
| Cột | Kiểu | Mặc định | Ghi chú |
|-----|------|----------|---------|
| `id` | uuid | PK | |
| `hotline` | string(20) | | |
| `email` | string(100) | nullable | |
| `address` | string(255) | nullable | |
| `is_callback_enabled` | boolean | `true` | |
| `is_message_form_enabled` | boolean | `true` | |
| `working_hours` | string(255) | nullable | |
| `is_active` | boolean | `true` | |
| `created_at` | timestamp | | |
| `updated_at` | timestamp | | |
| `deleted_at` | timestamp | nullable | SoftDeletes |

### `consultation_messages`
| Cột | Kiểu | Mặc định | Ghi chú |
|-----|------|----------|---------|
| `id` | uuid | PK | |
| `full_name` | string(255) | | |
| `phone` | string(20) | | |
| `email` | string(100) | nullable | |
| `project_id` | uuid | nullable | FK → projects (set null on delete) |
| `project_name` | string(255) | nullable | |
| `content` | text | nullable | |
| `status` | tinyInteger | `1` | 1: pending, 2: processing, 3: done, 4: cancelled |
| `created_at` | timestamp | | |
| `updated_at` | timestamp | | |
| `deleted_at` | timestamp | nullable | SoftDeletes |

---

## Nhóm 8: Video Pháp lý

### `legal_videos`
| Cột | Kiểu | Mặc định | Ghi chú |
|-----|------|----------|---------|
| `id` | uuid | PK | |
| `title` | string | | |
| `slug` | string | | unique |
| `short_description` | string(500) | nullable | |
| `description` | text | nullable | |
| `video_url` | string | | URL video |
| `thumbnail_url` | string | nullable | |
| `duration_seconds` | integer | nullable | |
| `category` | string | | indexed |
| `is_active` | boolean | `true` | indexed |
| `published_at` | timestamp | nullable | indexed |
| `created_at` | timestamp | | |
| `updated_at` | timestamp | | |
| `deleted_at` | timestamp | nullable | SoftDeletes |

---

## Nhóm 9: Giới thiệu (Referral)

### `referral_histories`
| Cột | Kiểu | Mặc định | Ghi chú |
|-----|------|----------|---------|
| `id` | uuid | PK | |
| `name` | string | | Họ tên người quét QR |
| `phone` | string | | |
| `referral_type` | smallInteger | | 1: Tuyển dụng, 2: Giới thiệu khách hàng |
| `status` | smallInteger | `1` | 1: Chưa hoàn tất, 2: Đã đăng ký |
| `scanned_at` | timestamp | | Thời gian quét |
| `registered_at` | timestamp | nullable | Thời gian hoàn tất đăng ký |
| `referrer_id` | uuid | FK → users | Người giới thiệu |
| `referee_id` | uuid | nullable → users | Người được giới thiệu (sau khi đăng ký) |
| `created_at` | timestamp | | |
| `updated_at` | timestamp | | |
| `deleted_at` | timestamp | nullable | SoftDeletes |

### `referral_commissions`
| Cột | Kiểu | Mặc định | Ghi chú |
|-----|------|----------|---------|
| `id` | uuid | PK | |
| `referrer_id` | uuid | FK → users | |
| `referral_history_id` | uuid | FK → referral_histories | |
| `amount` | bigInteger | `0` | Số tiền hoa hồng (VNĐ) |
| `status` | smallInteger | `1` | 1: Chờ thanh toán, 2: Đã thanh toán |
| `created_at` | timestamp | | |
| `updated_at` | timestamp | | |
| `deleted_at` | timestamp | nullable | SoftDeletes |

---

## Ghi chú về Kiểu dữ liệu Status (Integer Enum)

Toàn bộ các cột `status` trong dự án đều được chuyển sang **integer** (không dùng string enum) để tối ưu hiệu năng DB:

| Bảng | Giá trị status |
|------|----------------|
| `course_enrollments` | 1: not_started, 2: in_progress, 3: completed |
| `attendances` | 1: on_time, 2: late, 3: absent |
| `leave_requests` | 1: pending, 2: approved, 3: rejected |
| `department_transfer_requests` | 1: pending, 2: approved, 3: rejected |
| `lot_deposit_requests` | 1: PENDING, 2: APPROVED, 3: REJECTED |
| `lots` | 1: available, 2: sold, 3: reserved, 4: unavailable |
| `consultation_messages` | 1: pending, 2: processing, 3: done, 4: cancelled |
| `plannings` | 1: approved, 2: pending, 3: cancelled |
| `projects` | 1: opening, 2: coming_soon, 3: sold_out |
| `referral_histories` | 1: Chưa hoàn tất, 2: Đã đăng ký |
| `referral_commissions` | 1: Chờ thanh toán, 2: Đã thanh toán |
