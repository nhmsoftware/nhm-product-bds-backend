# 05 — DATABASE RULES & STANDARDS
# BDS APP — Quy tắc Database chuẩn cho PostgreSQL

> **AI PHẢI đọc file này trước khi tạo bất kỳ Migration, Model, hoặc Query nào.**
> Schema chi tiết của từng bảng xem tại: `06-DATABASE-SCHEMA.md`

---

## PHẦN 1 — NGUYÊN TẮC CỐT LÕI

| Quy tắc | Giá trị |
|---|---|
| Database | PostgreSQL |
| Primary Key | UUID (`uuid` type, không dùng auto-increment) |
| Soft Delete | BẮT BUỘC dùng `deleted_at` cho mọi bảng nghiệp vụ |
| Timestamps | `created_at`, `updated_at` BẮT BUỘC có trên mọi bảng |
| Tên bảng | `snake_case`, số nhiều (vd: `properties`, `chat_messages`) |
| Tên cột | `snake_case` (vd: `property_type`, `agent_id`) |
| Foreign Key | Luôn có index, đặt tên: `fk_[bảng]_[cột]` |
| Index | Đặt tên: `idx_[bảng]_[cột]` |
| Encoding | UTF-8 |
| Timezone | Lưu tất cả datetime theo UTC |
| JSON | Dùng `jsonb` thay vì `json` (PostgreSQL) |

---

## PHẦN 2 — LUẬT KHI TẠO MIGRATION

### 2.1 — Template chuẩn cho mọi bảng

```php
Schema::create('table_name', function (Blueprint $table) {
    $table->uuid('id')->primary();          // ✅ UUID — không dùng $table->id()

    // --- Các cột nghiệp vụ ---
    $table->string('title');
    $table->text('description')->nullable();

    // --- Foreign Keys ---
    $table->uuid('user_id');
    $table->foreign('user_id', 'fk_table_name_user_id')
          ->references('id')->on('users')->onDelete('cascade');

    // --- Indexes ---
    $table->index('status', 'idx_table_name_status');

    // --- Timestamps (BẮT BUỘC) ---
    $table->timestamps();                   // ✅ created_at + updated_at
    $table->softDeletes();                  // ✅ deleted_at
});
```

### 2.2 — Đặt tên index & foreign key

```php
// Foreign key — fk_[tên_bảng]_[tên_cột]
$table->foreign('agent_id', 'fk_properties_agent_id')
      ->references('id')->on('users');

// Index đơn — idx_[tên_bảng]_[tên_cột]
$table->index('status', 'idx_properties_status');

// Composite index — idx_[tên_bảng]_[cột1]_[cột2]
$table->index(['status', 'type'], 'idx_properties_status_type');

// Unique constraint — uq_[tên_bảng]_[cột]
$table->unique(['buyer_id', 'agent_id'], 'uq_conversations_buyer_agent');
```

### 2.3 — Kiểu dữ liệu chuẩn

| Loại dữ liệu | Kiểu dùng |
|---|---|
| Giá tiền | `decimal(15, 2)` |
| Tọa độ GPS | `decimal(10, 7)` |
| Phần trăm | `decimal(5, 2)` |
| Diện tích (m²) | `decimal(10, 2)` |
| Dữ liệu JSON | `jsonb` |
| Trạng thái có/không | `boolean` |
| Ngày làm việc | `date` |
| Thời điểm cụ thể | `timestamp` |

### 2.4 — Ngoại lệ Soft Delete

Các bảng sau **KHÔNG dùng `softDeletes()`** vì lịch sử phải giữ nguyên:
- `notifications`
- `quiz_attempts`
- `announcement_reads`
- `lesson_progress`

---

## PHẦN 3 — LUẬT KHI TẠO MODEL

### 3.1 — Template Model chuẩn

```php
<?php

namespace App\Modules\[Module]\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ModelName extends Model
{
    use HasFactory, SoftDeletes, HasUuids;  // ✅ HasUuids BẮT BUỘC

    protected $fillable = [                 // ✅ $fillable — không dùng $guarded = []
        'column_one',
        'column_two',
    ];

    protected $casts = [                    // ✅ $casts cho cột đặc biệt
        'price'      => 'decimal:2',
        'is_active'  => 'boolean',
        'data'       => 'array',            // jsonb → array
        'created_at' => 'datetime',
    ];
}
```

### 3.2 — Traits bắt buộc

| Trait | Khi nào dùng |
|---|---|
| `HasUuids` | Mọi Model — BẮT BUỘC |
| `SoftDeletes` | Mọi Model trừ các ngoại lệ ở Phần 2.4 |
| `HasFactory` | Mọi Model |

### 3.3 — Quy tắc $casts

```php
protected $casts = [
    // Số thực
    'price'            => 'decimal:2',
    'latitude'         => 'decimal:7',
    'progress_percent' => 'decimal:2',

    // Boolean
    'is_active'    => 'boolean',
    'is_approved'  => 'boolean',
    'is_completed' => 'boolean',

    // JSON (jsonb → array)
    'data'        => 'array',
    'attachments' => 'array',
    'criteria'    => 'array',
    'options'     => 'array',

    // Datetime
    'approved_at'  => 'datetime',
    'published_at' => 'datetime',
    'read_at'      => 'datetime',
];
```

---

## PHẦN 4 — LUẬT KHI VIẾT QUERY

### 4.1 — Query PHẢI nằm trong Repository

```php
// ❌ KHÔNG viết query trong Controller hay Service
Property::where('status', 'available')->get();

// ✅ ĐÚNG — viết trong Repository
public function getFiltered(array $filters): LengthAwarePaginator
{
    return Property::query()
        ->when($filters['status'] ?? null, fn($q, $v) => $q->where('status', $v))
        ->when($filters['min_price'] ?? null, fn($q, $v) => $q->where('price', '>=', $v))
        ->when($filters['max_price'] ?? null, fn($q, $v) => $q->where('price', '<=', $v))
        ->with(['agent', 'media'])
        ->orderBy('created_at', 'desc')
        ->paginate($filters['per_page'] ?? 15);
}
```

### 4.2 — Geofencing (tìm theo bán kính GPS)

```php
// Dùng công thức Haversine — KHÔNG dùng HTTP polling
public function findNearby(float $lat, float $lng, int $radiusKm = 5): Collection
{
    return Property::query()
        ->selectRaw("*, (6371 * acos(
            cos(radians(?)) * cos(radians(latitude))
            * cos(radians(longitude) - radians(?))
            + sin(radians(?)) * sin(radians(latitude))
        )) AS distance", [$lat, $lng, $lat])
        ->whereNotNull('latitude')
        ->whereNotNull('longitude')
        ->having('distance', '<=', $radiusKm)
        ->orderBy('distance')
        ->get();
}
```

### 4.3 — Soft Delete

```php
Property::withTrashed()->find($id);                    // Lấy kể cả đã xóa
Property::onlyTrashed()->get();                        // Chỉ lấy đã xóa
Property::withTrashed()->find($id)->restore();         // Khôi phục
Property::withTrashed()->find($id)->forceDelete();     // Xóa vĩnh viễn — CHỈ Admin
```

---

## PHẦN 5 — ENUM CHUẨN

> Tham chiếu khi viết validation, migration, và Swagger.

```php
enum UserRole: string {
    case Admin  = 'admin';
    case Agent  = 'agent';
    case Broker = 'broker';
    case Buyer  = 'buyer';
}

enum PropertyStatus: string {
    case Available = 'available';
    case Reserved  = 'reserved';
    case Sold      = 'sold';
}

enum PropertyType: string {
    case Apartment = 'apartment';
    case House     = 'house';
    case Land      = 'land';
    case Office    = 'office';
    case Shophouse = 'shophouse';
}

enum ListingType: string {
    case Sale = 'sale';
    case Rent = 'rent';
}

enum MessageType: string {
    case Text  = 'text';
    case Image = 'image';
    case File  = 'file';
}

enum EnrollmentStatus: string {
    case NotStarted = 'not_started';
    case InProgress = 'in_progress';
    case Completed  = 'completed';
}

enum AttendanceMethod: string {
    case GPS = 'gps';
    case QR  = 'qr';
}

enum AttendanceStatus: string {
    case Present = 'present';
    case Late    = 'late';
    case Absent  = 'absent';
    case HalfDay = 'half_day';
}

enum AnnouncementVisibility: string {
    case All    = 'all';
    case Admin  = 'admin';
    case Agent  = 'agent';
    case Broker = 'broker';
    case Buyer  = 'buyer';
}
```

---

## PHẦN 6 — LUẬT CẤM TUYỆT ĐỐI

| ❌ Cấm | ✅ Thay bằng |
|---|---|
| `$table->id()` | `$table->uuid('id')->primary()` |
| `$guarded = []` trong Model | `$fillable = [...]` |
| Thiếu `softDeletes()` | Luôn thêm (trừ ngoại lệ Phần 2.4) |
| Thiếu `HasUuids` trong Model | Luôn thêm trait `HasUuids` |
| Thiếu `timestamps()` | Luôn có `created_at` + `updated_at` |
| Foreign key không đặt tên | Luôn đặt tên `fk_[bảng]_[cột]` |
| Foreign key không có index | Luôn index foreign key |
| Dùng `json` thay `jsonb` | Dùng `jsonb` (PostgreSQL) |
| Lưu giá tiền dạng `float` | `decimal(15, 2)` |
| Lưu tọa độ dạng `string` | `decimal(10, 7)` |
| Tên bảng số ít hoặc camelCase | `snake_case` số nhiều |
| Query trong Controller/Service | Query trong Repository |
| HTTP polling cho GPS | Haversine query + FCM Geofencing |
| Lưu datetime local timezone | Luôn UTC |
