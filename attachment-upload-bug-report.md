# Báo cáo: Bug "nuốt file" trong Attachment Repeater

## Tóm tắt

File upload bị "nuốt" (disappear) sau khi đạt 100% do **hai nguyên nhân chồng chéo** liên quan đến
cách Filament v3 gọi `afterStateHydrated` và cách FilePond xử lý temp file URL.

---

## Cách Filament gọi `afterStateHydrated`

```php
// HasState.php (Filament source)
public function callAfterStateHydrated(): static
{
    if ($callback = $this->afterStateHydrated) {
        $this->evaluate($callback);   // ← inject $state = $this->getState()
    }
    return $this;
}
```

`$this->evaluate($callback)` inject `$state` bằng `$this->getState()` → đọc từ Livewire data.

**Quan trọng:** `afterStateHydrated(?Closure $callback)` ghi đè (REPLACE) callback trước đó, KHÔNG append.
Code schema của chúng ta gọi `->afterStateHydrated(...)` → override hoàn toàn BaseFileUpload's default.

`callAfterStateHydrated()` được gọi trong `hydrateState()`, có thể trigger nhiều lần:
- Lần 1: Initial form mount (EditRecord load trang)
- Lần 2+: Mỗi khi Repeater re-hydrate item — ví dụ khi `type` dropdown thay đổi (live())

---

## Nguyên nhân 1: `getContainer()->getRawState()` không trả về `url`

```php
// Code cũ của chúng ta
$itemState = $component->getContainer()->getRawState();
$url = $itemState['url'] ?? null;
```

`getRawState()` trong HasState.php:
```php
public function getRawState(): array | Arrayable
{
    return data_get($this->getLivewire(), $this->getStatePath()) ?? [];
}
```

`data_get($livewire, $this->getStatePath())` — `getStatePath()` của Container (Schema)
trả về path như `data.attachments.{uuid}`. Kết quả phụ thuộc vào thời điểm gọi:

- Nếu gọi VÀO LÚC hydration đang diễn ra → dữ liệu chưa fill đầy đủ → `url = null`
- Nếu `Hidden::make('url')` chưa hydrate xong → không có trong raw state

**Hệ quả**: `url = null` → fall through → `$component->state([])` → FilePond hiển thị trống.
File hiện có không bao giờ được show trong edit form.

---

## Nguyên nhân 2: FilePond cố fetch temp file qua Public disk → 404

Khi user upload file mới:
1. Livewire upload → file đi vào `storage/app/livewire-tmp/xxx.pdf` (LOCAL disk)
2. `afterStateHydrated` nhận `$state = ['uuid' => 'livewire-tmp/xxx.pdf']`
3. `is_array && !empty` = TRUE → gọi `$component->state($state)` → PRESERVE ✓
4. Filament render lại component → FilePond nhận state với path `livewire-tmp/xxx.pdf`
5. FilePond cố fetch URL: `https://domain.vn/storage/livewire-tmp/xxx.pdf` → **404**
   (temp file ở LOCAL disk, không phải PUBLIC disk)
6. FilePond báo lỗi tải file → xóa khỏi preview → **file "biến mất"**

Đây là "nuốt file" mà user thấy: upload đến 100% → hiện briefly → sau đó biến mất.

---

## Nguyên nhân 3: `afterStateHydrated` re-run khi type thay đổi, xóa upload state

Khi user upload file XONG rồi thay đổi `type` dropdown:
1. `type` Select có `->live()` → trigger Livewire update
2. Repeater re-hydrate item → `callAfterStateHydrated()` gọi lại cho `_file_upload`
3. `$state` = giá trị từ Livewire data = `['uuid' => 'livewire-tmp/xxx.pdf']`
4. `is_array && !empty` = TRUE → preserve ✓ (lý thuyết đúng)
5. NHƯNG FilePond sau khi re-render lại cố fetch URL → 404 → remove file

Nếu `$state` là `null` (form re-fill từ record):
3b. `is_array(null)` = FALSE → đọc `url` sibling (cũng không lấy được, Nguyên nhân 1)
4b. `$component->state([])` → XÓAH upload state
5b. File bị "nuốt" hoàn toàn

---

## Root Cause của BaseFileUpload

BaseFileUpload's default `afterStateHydrated`:
```php
$this->afterStateHydrated(static function (BaseFileUpload $component, string|array|null $state): void {
    // ...
    ->filter(fn ($file) => $component->getDisk()->exists($file))  // ← Kiểm tra PUBLIC disk!
    // ...
});
```

Temp file ở LOCAL disk → `getDisk()->exists('livewire-tmp/xxx.pdf')` trên PUBLIC disk → **FALSE**
→ file bị filter out → state = `[]`. Default behavior CŨNG bị bug với temp file trong Repeater!

Điểm khác biệt: trong flow BÌNH THƯỜNG (không Repeater), FilePond quản lý temp file
hoàn toàn client-side — không cần server-side state để display. Nhưng khi Repeater
re-render và gọi lại `afterStateHydrated`, server cố "inject" state vào FilePond và fail.

---

## Fix được áp dụng

### 1. `afterStateHydrated` — đọc từ Livewire data TRỰC TIẾP

```php
->afterStateHydrated(function (Forms\Components\FileUpload $component, mixed $state): void {
    $livewire = $component->getContainer()->getLivewire();
    // data_get($livewire, 'data.attachments.uuid._file_upload') — bypass $state param
    $currentState = data_get($livewire, $component->getStatePath());
    
    if (is_array($currentState) && !empty(array_filter($currentState))) {
        $component->state($currentState);  // Pending upload → preserve
        return;
    }
    $component->state([]);  // No pending upload → empty FilePond
})
```

Khác với code cũ dùng `$state` param (có thể stale từ record), đọc TRỰC TIẾP từ
`$livewire->data` đảm bảo luôn lấy được state thực tế hiện tại.

### 2. `helperText` — hiển thị file hiện có dưới dạng text (không dùng FilePond load)

```php
->helperText(function (Forms\Components\FileUpload $component): ?string {
    $livewire = $component->getContainer()->getLivewire();
    $itemPath = Str::beforeLast($component->getStatePath(), '._file_upload');
    $url = data_get($livewire, $itemPath . '.url');
    if (!is_string($url) || blank($url)) return null;
    return 'File hiện tại: ' . basename($url) . ' — Tải lên file mới để thay thế.';
})
```

Không để FilePond fetch existing file (tránh 404 / spinner). Giống pattern AdminUploads::video().

### 3. `_link_input::afterStateHydrated` — đọc từ Livewire data trực tiếp

```php
->afterStateHydrated(function (Forms\Components\TextInput $component): void {
    $livewire = $component->getContainer()->getLivewire();
    $itemPath = Str::beforeLast($component->getStatePath(), '._link_input');
    $url = data_get($livewire, $itemPath . '.url');
    if (is_string($url) && str_starts_with($url, 'http')) {
        $component->state($url);
    }
})
```

---

## Tại sao file hiện có không show trong edit (screenshot)

Các attachment (PDF, Word) được upload với code CŨ (`dehydrated:false` + `afterStateUpdated`
set url = temp path). Kết quả:
- Temp file không được move sang `learning/attachments/`
- `url` trong DB = `/storage/livewire-tmp/xxx.pdf` (hoặc path sai)
- Livewire đã cleanup temp file → file không còn tồn tại trên disk
- FilePond/server không tìm thấy file → hiển thị trống

**Giải pháp cho data cũ**: Các attachment này cần được re-upload lại.
Code mới sẽ đảm bảo file upload mới được move đúng chỗ.
