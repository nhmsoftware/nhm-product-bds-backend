# Module Leave

## Tổng quan
Module này đóng gói toàn bộ nghiệp vụ liên quan đến xin nghỉ phép (Leave Request) của nhân sự trong BDS App.

## Tính năng
- **UC-043: Request Leave** - Nhân viên gửi đơn xin nghỉ phép (nghỉ phép năm, nghỉ không lương, nghỉ cá nhân, thai sản, công tác, nghỉ bù).

## Cấu trúc thư mục
- `DTO/`: Data Transfer Objects (CreateLeaveDTO).
- `Http/`: Controllers và FormRequests (LeaveController, CreateLeaveRequest).
- `Interfaces/`: Định nghĩa Service và Repository Interfaces.
- `Models/`: Eloquent Model (LeaveRequest).
- `Enums/`: PHP Enums (LeaveType).
- `Events/`: Sự kiện Domain Event (LeaveRequestCreated).
- `Repositories/`: Tương tác CSDL.
- `Services/`: Logic nghiệp vụ.
- `Routes/`: Khai báo API routes.
