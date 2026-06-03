require('dotenv').config();
const express = require('express');
const http = require('http');
const { Server } = require('socket.io');
const Redis = require('ioredis');

const app = express();
const server = http.createServer(app);

// Cấu hình Socket.io với CORS
const io = new Server(server, {
  cors: {
    origin: '*', // Trong môi trường production, hãy thay bằng domain thực tế
    methods: ['GET', 'POST']
  }
});

// Cấu hình Redis client để lắng nghe event từ Laravel
const redisHost = process.env.REDIS_HOST || 'bds_redis'; // Kết nối tới container Redis
const redisPort = process.env.REDIS_PORT || 6379;
const redis = new Redis({
  host: redisHost,
  port: redisPort
});

console.log(`Đang kết nối Redis tới ${redisHost}:${redisPort}...`);

// Lắng nghe tất cả các kênh Redis (dùng psubscribe)
redis.psubscribe('*', (err, count) => {
  if (err) {
    console.error('Lỗi khi subscribe Redis:', err);
  } else {
    console.log(`Đã subscribe ${count} pattern(s) trên Redis.`);
  }
});

// Xử lý message nhận được từ Redis (do Laravel broadcast)
redis.on('pmessage', (pattern, channel, message) => {
  console.log(`[Redis] Channel: ${channel} | Pattern: ${pattern}`);
  try {
    const parsedMessage = JSON.parse(message);
    console.log('Payload:', parsedMessage);
    
    // Cấu trúc event từ Laravel thường có dạng { event: 'TênEvent', data: { ... } }
    if (parsedMessage.event && parsedMessage.data) {
      const eventName = parsedMessage.event;
      const data = parsedMessage.data;
      
      // Nếu có user_id hoặc notifiable_id, emit vào user-specific room
      // FE phải join room trước với format: user.{id}
      if (data.user_id || data.notifiable_id) {
        const userId = data.user_id || data.notifiable_id;
        const roomName = `user.${userId}`;
        console.log(`[Emit] Event: ${eventName} -> Room: ${roomName}`);
        io.to(roomName).emit(eventName, data);
      } else {
        // Nếu không có user_id, broadcast tới toàn bộ clients
        console.log(`[Emit] Event: ${eventName} -> All clients`);
        io.emit(eventName, data);
      }
    } else if (parsedMessage.event) {
       io.emit(parsedMessage.event, parsedMessage.data || {});
    } else {
       io.emit(channel, parsedMessage);
    }
  } catch (error) {
    console.error('Lỗi khi xử lý message từ Redis:', error);
    console.error('Raw message:', message);
  }
});

// Xử lý sự kiện khi có client kết nối tới Socket.io
io.on('connection', (socket) => {
  console.log(`[Socket.io] Client mới kết nối: ${socket.id}`);

  // Client có thể gửi event 'join' để vào các room cụ thể (ví dụ: private_user_1)
  socket.on('join', (room) => {
    socket.join(room);
    console.log(`[Socket.io] Client ${socket.id} tham gia phòng: ${room}`);
  });

  socket.on('disconnect', () => {
    console.log(`[Socket.io] Client ngắt kết nối: ${socket.id}`);
  });
});

const PORT = process.env.PORT || 3000;
server.listen(PORT, () => {
  console.log(`🚀 BDS App Realtime Server (Socket.io) đang chạy tại port ${PORT}`);
});
