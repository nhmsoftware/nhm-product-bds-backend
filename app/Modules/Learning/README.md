# Module Learning (LMS)

Module này chịu trách nhiệm quản lý hoạt động học tập, các khóa học bắt buộc và bài học của nhân viên (Employee) được phân bổ theo cơ cấu tổ chức (phòng ban hoặc vị trí công việc).

## Tính năng / Use Cases
- **UC-053: View Mandatory Course**: Cho phép nhân viên xem danh sách các khóa học bắt buộc được giao cho họ dựa trên phòng ban (department) hoặc vị trí công việc (job_position). Nhân viên cũng có thể chọn khóa học để xem danh sách bài học và tiến độ tương ứng.
- **UC-054: Lesson details**: Cho phép nhân viên xem chi tiết bài học bao gồm video, mô tả, trạng thái bài học, tài liệu đính kèm và thông báo điều kiện mở khóa bài tiếp theo.
- **UC-055: Watch Training Video**: Cho phép nhân viên xem video đào tạo, tự động lưu tiến độ xem hiện tại và tự động đánh giá hoàn thành bài học, mở khóa bài tiếp theo khi xem đủ thời lượng yêu cầu.
- **UC-056: Take Quiz**: Cho phép nhân viên làm bài kiểm tra trắc nghiệm sau bài học, chấm điểm tự động và lưu lịch sử bài làm.
- **UC-057: Complete Course**: Ghi nhận nhân viên hoàn thành khóa học khi đã hoàn thành toàn bộ bài học và đạt điểm yêu cầu của bài quiz cuối khóa (nếu có).
- **UC-058: View Certificate**: Cho phép nhân viên xem thông tin chứng nhận (dữ liệu JSON) và tải chứng nhận hoàn thành khóa học (.txt) về thiết bị.
- **UC-059: Save draft**: Cho phép nhân viên lưu tạm tiến trình làm bài kiểm tra trắc nghiệm (lưu bản nháp) để tiếp tục làm sau.

## Database Schema (Group 3: Khóa Học)
1. **courses**: Lưu thông tin khóa học (Tiêu đề, mô tả, ảnh cover, phân bổ phòng ban/vị trí, trạng thái hoạt động, hỗ trợ cấp chứng nhận hay không `has_certificate`).
2. **course_lessons**: Danh sách bài học của mỗi khóa học (tên bài học, thời lượng video, link video, thứ tự, tài liệu đính kèm).
3. **course_quizzes**: Câu hỏi trắc nghiệm đính kèm mỗi bài học.
4. **course_enrollments**: Bản ghi ghi nhận lượt tham gia học và tiến độ của nhân viên đối với khóa học.
5. **lesson_progress**: Chi tiết trạng thái hoàn thành từng bài học trong khóa học của nhân viên (bao gồm thời lượng đã xem video).
6. **quiz_attempts**: Lịch sử trả lời các câu hỏi quiz (hỗ trợ trạng thái lưu nháp `is_draft`).

## API Endpoints
- `GET /api/v1/learning/courses` - Tải danh sách khóa học bắt buộc được phân bổ cho nhân viên hiện tại.
- `GET /api/v1/learning/courses/{id}` - Tải thông tin chi tiết của một khóa học bắt buộc và danh sách bài học kèm trạng thái tương ứng (`learning`, `completed`, `locked`).
- `POST /api/v1/learning/courses/{id}/complete` - Ghi nhận nhân viên hoàn thành khóa học nếu đủ điều kiện hoàn thành toàn bộ bài học và đạt điểm quiz cuối khóa.
- `GET /api/v1/learning/courses/{id}/certificate` - Tải thông tin chứng nhận hoàn thành khóa học (UC-058).
- `GET /api/v1/learning/courses/{id}/certificate/download` - Tải file chứng nhận hoàn thành dưới dạng file .txt (UC-058).
- `GET /api/v1/learning/lessons/{id}` - Tải thông tin chi tiết của bài học (video, mô tả, trạng thái, tài liệu đính kèm, tiến độ xem đã lưu, điều kiện mở khóa bài tiếp theo).
- `POST /api/v1/learning/lessons/{id}/progress` - Cập nhật tiến độ xem video bài học, đánh giá hoàn thành và xác định bài tiếp theo để mở khóa.
- `GET /api/v1/learning/lessons/{id}/quiz` - Lấy danh sách câu hỏi kiểm tra (không chứa đáp án đúng) để nhân viên làm bài (kèm câu trả lời nháp đã lưu nếu có).
- `POST /api/v1/learning/lessons/{id}/quiz/submit` - Nộp bài làm trắc nghiệm, tính toán điểm số và cập nhật kết quả.
- `POST /api/v1/learning/lessons/{id}/quiz/draft` - Lưu nháp câu trả lời quiz (UC-059).
