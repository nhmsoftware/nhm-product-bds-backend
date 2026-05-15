# TÀI LIỆU HƯỚNG DẪN KIẾN TRÚC REALTIME (SOCKET.IO)
> Dành cho người sau: Đọc kỹ tài liệu này trước khi muốn sửa đổi logic Realtime.

## 1. Mục Tiêu Thiết Kế
Dự án này sử dụng kiến trúc **Laravel Broadcasting** kết hợp với **Node.js + Socket.io** làm Adapter, thay cho các thư viện phức tạp như Laravel Echo Server hoặc Reverb.

- **Laravel** là nơi CHỨA DUY NHẤT Business Logic.
- **Node.js (socket/server.js)** CHỈ đóng vai trò làm "Người đưa thư" (Universal Adapter). Nó không chứa bất kỳ logic nào về việc phân chia room phức tạp, tạo token, hay xác thực DB.

## 2. Luồng Hoạt Động (Data Flow)
1. Có sự kiện trong **Laravel Service** (VD: duyệt dự án, có tin nhắn mới).
2. Laravel gọi `event(new MyCustomEvent($data))`.
3. Event này được config `implements ShouldBroadcastNow`. Laravel tự động gói payload (kèm tên channel, tên event) và ném vào **Redis** qua driver `phpredis`.
4. Server **Node.js (Socket.io)** đang listen bằng `redis.psubscribe('*')`. Nó bắt được gói tin này ngay lập tức.
5. Node.js giải mã (JSON.parse) gói tin, và dùng lệnh `io.emit(eventName, data)` để phát thẳng xuống tất cả các WebSocket Client đang kết nối.

## 3. Cách Sử Dụng (Dành cho Developer mới)
**BẠN KHÔNG CẦN SỬA CODE BÊN TRONG THƯ MỤC `socket/`!**

Khi cần thêm tính năng realtime mới, bạn chỉ cần làm bên Laravel:
1. Tạo 1 Event (`php artisan make:event`).
2. Cho class Event đó implements `ShouldBroadcastNow` (hoặc `ShouldBroadcast`).
3. Viết data vào property `public array $data;`.
4. Define tên Event bằng hàm `broadcastAs()` (ví dụ return `'property.approved'`).
5. Fire event: `event(new PropertyApproved($data))`.

Ở phía Frontend / Mobile App, kết nối trực tiếp vào Socket.io (Port 3000):
```javascript
import { io } from "socket.io-client";
const socket = io("http://localhost:3000");

// Lắng nghe đúng tên Event mà Laravel định nghĩa trong broadcastAs()
socket.on("property.approved", (data) => {
    console.log("Dự án đã được duyệt:", data);
});
```

## 4. Tại Sao Lại Thiết Kế Như Này?
- Tránh phân mảnh logic (Logic phải kiểm tra Auth, role, data hợp lệ... nằm hoàn toàn ở Laravel - theo đúng tôn chỉ Modular DDD).
- Đảm bảo Node.js server chạy rất nhẹ, không cần kết nối tới Database Postgres, không cần load configs phức tạp, không sập.
- Hiệu năng cực cao nhờ thông qua `Redis Pub/Sub`.
