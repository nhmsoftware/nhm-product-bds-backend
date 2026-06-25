<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeProfileResource\Pages;
use App\Filament\Support\AdminImageColumn;
use App\Filament\Support\AdminUploads;
use App\Modules\Auth\Models\Department;
use App\Modules\Auth\Models\EmployeeProfile;
use App\Modules\Auth\Models\Enums\UserRole;
use App\Modules\Auth\Models\User;
use App\Modules\Branch\Models\Branch;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EmployeeProfileResource extends Resource
{
    protected static ?string $model = EmployeeProfile::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationGroup = 'Nhân sự';

    protected static ?string $modelLabel = 'Hồ sơ nhân sự';

    protected static ?string $pluralModelLabel = 'Hồ sơ nhân sự';

    public static function form(Form $form): Form
    {
        return $form->schema([

            // ─── Tài khoản nhân viên ──────────────────────────────────────────
            Forms\Components\Section::make('Tài khoản nhân viên')
                ->schema([
                    Forms\Components\Select::make('user_id')
                        ->label('Nhân viên')
                        ->relationship('user', 'name', function (\Illuminate\Database\Eloquent\Builder $query) {
                            $currentUser = auth()->user();
                            if (!$currentUser) return $query;

                            // Chỉ cho phép EMPLOYEE, MANAGER, DIRECTOR
                            $query->where('id', '!=', $currentUser->id)
                                ->whereIn('role', [
                                    UserRole::EMPLOYEE->value,
                                    UserRole::MANAGER->value,
                                    UserRole::DIRECTOR->value,
                                ]);

                            // Không cho phép quản lý tài khoản cấp cao hơn mình
                            if ($currentUser->role !== UserRole::SUPER_ADMIN) {
                                $query->where('role', '<=', $currentUser->role->value);
                            }

                            // Giám đốc chỉ quản lý trong chi nhánh của mình
                            if ($currentUser->role === UserRole::DIRECTOR && $currentUser->branch_id) {
                                $query->where('branch_id', $currentUser->branch_id);
                            }

                            // Trưởng phòng chỉ quản lý trong phòng ban của mình
                            if ($currentUser->role === UserRole::MANAGER && $currentUser->department_id) {
                                $query->where('department_id', $currentUser->department_id);
                            }

                            return $query;
                        })
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, ?string $state) {
                            if ($state) {
                                $user = User::find($state);
                                if ($user) {
                                    $set('user_name', $user->name);
                                    $set('phone', $user->phone);
                                    $set('email', $user->email);
                                    $set('address', $user->address);
                                    $set('avatar', $user->avatar);
                                }
                            }
                        }),

                    AdminUploads::image('avatar', 'Ảnh đại diện', 'avatars')
                        ->avatar()
                        ->columnSpanFull(),
                ])
                ->columns(2),

            // ─── Thông tin liên hệ (lưu vào User model) ──────────────────────
            Forms\Components\Section::make('Thông tin liên hệ')
                ->schema([
                    Forms\Components\TextInput::make('user_name')
                        ->label('Họ và tên')
                        ->required()
                        ->maxLength(255)
                        ->validationMessages([
                            'required' => 'Vui lòng nhập họ và tên.',
                        ]),

                    Forms\Components\TextInput::make('phone')
                        ->label('Số điện thoại')
                        ->tel()
                        ->required()
                        ->regex('/^(03|05|07|08|09)\d{8}$/')
                        ->validationMessages([
                            'required' => 'Vui lòng nhập số điện thoại.',
                            'regex'    => 'Số điện thoại không hợp lệ.',
                        ]),

                    Forms\Components\TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->required()
                        ->maxLength(255)
                        ->validationMessages([
                            'required' => 'Vui lòng nhập email.',
                            'email'    => 'Email không hợp lệ.',
                        ]),

                    Forms\Components\TextInput::make('address')
                        ->label('Địa chỉ thường trú')
                        ->maxLength(255)
                        ->columnSpanFull(),
                ])
                ->columns(2),

            // ─── Thông tin hồ sơ ─────────────────────────────────────────────
            Forms\Components\Section::make('Thông tin hồ sơ')
                ->schema([
                    Forms\Components\TextInput::make('employee_title')
                        ->label('Danh hiệu nhân viên')
                        ->maxLength(255),

                    Forms\Components\TextInput::make('identity_card')
                        ->label('Số CCCD')
                        ->maxLength(20),

                    Forms\Components\DatePicker::make('dob')
                        ->label('Ngày sinh')
                        ->maxDate(now()->subDay())
                        ->native(false)
                        ->displayFormat('d/m/Y'),
                ])
                ->columns(2),

            // ─── Thông tin ngân hàng ──────────────────────────────────────────
            Forms\Components\Section::make('Thông tin ngân hàng')
                ->schema([
                    Forms\Components\TextInput::make('bank_account_name')
                        ->label('Chủ tài khoản')
                        ->maxLength(255),

                    Forms\Components\TextInput::make('bank_account_number')
                        ->label('Số tài khoản')
                        ->maxLength(20)
                        ->regex('/^\d{6,20}$/')
                        ->nullable()
                        ->validationMessages([
                            'regex' => 'Số tài khoản ngân hàng không hợp lệ.',
                        ]),

                    Forms\Components\TextInput::make('bank_name')
                        ->label('Ngân hàng')
                        ->maxLength(255),
                ])
                ->columns(2),

            // ─── Học vấn & Kinh nghiệm ───────────────────────────────────────
            Forms\Components\Section::make('Học vấn & Kinh nghiệm')
                ->schema([
                    Forms\Components\TextInput::make('education')
                        ->label('Học vấn')
                        ->maxLength(255),

                    Forms\Components\TextInput::make('major')
                        ->label('Chuyên ngành')
                        ->maxLength(255),

                    Forms\Components\Textarea::make('experience')
                        ->label('Kinh nghiệm làm việc')
                        ->rows(4)
                        ->columnSpanFull(),
                ])
                ->columns(2),

            // ─── Tài liệu cá nhân (UC-035) ───────────────────────────────────
            Forms\Components\Section::make('Tài liệu cá nhân')
                ->schema([
                    Forms\Components\Repeater::make('attachments')
                        ->label('')
                        ->schema([
                            Forms\Components\Select::make('type')
                                ->label('Loại tài liệu')
                                ->options([
                                    'Hợp đồng lao động' => 'Hợp đồng lao động',
                                    'Bằng cấp'          => 'Bằng cấp',
                                    'Chứng chỉ'         => 'Chứng chỉ',
                                    'CCCD/CMND'         => 'CCCD/CMND',
                                    'Tài liệu khác'     => 'Tài liệu khác',
                                ])
                                ->required()
                                ->validationMessages([
                                    'required' => 'Vui lòng chọn loại tài liệu.',
                                ]),

                            Forms\Components\TextInput::make('name')
                                ->label('Tên tài liệu')
                                ->maxLength(255),

                            Forms\Components\Hidden::make('url'),

                            Forms\Components\FileUpload::make('_file_upload')
                                ->label('File tài liệu')
                                ->disk('public')
                                ->directory('employee_documents')
                                ->acceptedFileTypes([
                                    'application/pdf',
                                    'application/msword',
                                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                    'image/jpeg',
                                    'image/png',
                                ])
                                ->maxSize(10240) // 10 MB
                                ->downloadable()
                                ->openable()
                                ->afterStateHydrated(function (Forms\Components\FileUpload $component): void {
                                    $livewire = $component->getContainer()->getLivewire();
                                    $currentState = data_get($livewire, $component->getStatePath());
                                    if (is_array($currentState) && !empty(array_filter($currentState))) {
                                        $component->state($currentState);
                                        return;
                                    }
                                    $component->state([]);
                                })
                                ->helperText(function (Forms\Components\FileUpload $component): \Illuminate\Support\HtmlString {
                                    $livewire = $component->getContainer()->getLivewire();
                                    $itemPath = \Illuminate\Support\Str::beforeLast($component->getStatePath(), '._file_upload');
                                    $storedUrl = data_get($livewire, $itemPath . '.url');
                                    if (!is_string($storedUrl) || blank($storedUrl)) {
                                        return new \Illuminate\Support\HtmlString('');
                                    }
                                    
                                    $absUrl = str_starts_with($storedUrl, 'http')
                                        ? $storedUrl
                                        : request()->getSchemeAndHttpHost() . '/' . ltrim($storedUrl, '/');
                                        
                                    $filename = e(basename($storedUrl));
                                    $ext = strtolower(pathinfo(parse_url($storedUrl, PHP_URL_PATH), PATHINFO_EXTENSION));
                                    $icons = [
                                        'pdf'   => ['label' => 'PDF', 'color' => '#ef4444', 'bg' => '#fef2f2'],
                                        'doc'   => ['label' => 'DOC', 'color' => '#3b82f6', 'bg' => '#eff6ff'],
                                        'docx'  => ['label' => 'DOCX', 'color' => '#3b82f6', 'bg' => '#eff6ff'],
                                        'jpg'   => ['label' => 'IMG', 'color' => '#10b981', 'bg' => '#f0fdf4'],
                                        'jpeg'  => ['label' => 'IMG', 'color' => '#10b981', 'bg' => '#f0fdf4'],
                                        'png'   => ['label' => 'IMG', 'color' => '#10b981', 'bg' => '#f0fdf4'],
                                    ];
                                    $icon = $icons[$ext] ?? ['label' => 'FILE', 'color' => '#6b7280', 'bg' => '#f3f4f6'];
                                    
                                    $btnBase = "display:inline-flex;align-items:center;gap:4px;padding:6px 10px;"
                                        . "border-radius:6px;border:1px solid #e5e7eb;background:#fff;"
                                        . "font-size:12px;font-weight:500;color:#374151;text-decoration:none;";
                                    $btnHover = "onmouseover=\"this.style.background='#f3f4f6';this.style.borderColor='#d1d5db'\" "
                                        . "onmouseout=\"this.style.background='#fff';this.style.borderColor='#e5e7eb'\"";
                                    $dlIcon = "<svg width='14' height='14' fill='none' stroke='currentColor' viewBox='0 0 24 24'>"
                                        . "<path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' "
                                        . "d='M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4'/></svg>";

                                    return new \Illuminate\Support\HtmlString(
                                        "<div style='display:flex;align-items:center;gap:12px;padding:12px 16px;"
                                        . "border:1px solid #e5e7eb;border-radius:8px;background:#fff;margin-top:8px'>"
                                        . "<div style='width:40px;height:40px;border-radius:8px;"
                                        . "background:{$icon['bg']};display:flex;align-items:center;"
                                        . "justify-content:center;font-weight:700;font-size:11px;color:{$icon['color']};flex-shrink:0'>"
                                        . $icon['label']
                                        . "</div>"
                                        . "<div style='flex:1;min-width:0'>"
                                        . "<p style='margin:0;font-size:0.875em;font-weight:500;color:#111827;"
                                        . "white-space:nowrap;overflow:hidden;text-overflow:ellipsis'>{$filename}</p>"
                                        . "<p style='margin:2px 0 0;font-size:0.75em;color:#6b7280'>Tải tệp mới để thay thế</p>"
                                        . "</div>"
                                        . "<div style='display:flex;gap:6px;flex-shrink:0'>"
                                        . "<a href='" . e($absUrl) . "' download title='Tải xuống' style='{$btnBase}' {$btnHover}>"
                                        . $dlIcon . "Tải</a>"
                                        . "</div>"
                                        . "</div>"
                                    );
                                })
                                ->rules([
                                    fn (Forms\Get $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get) {
                                        if (blank($value) && blank($get('url'))) {
                                            $fail('Vui lòng chọn file cần tải lên.');
                                        }
                                    },
                                ])
                                ->validationMessages([
                                    'mimes'       => 'Định dạng file không hợp lệ.',
                                    'max'         => 'Dung lượng file vượt quá giới hạn cho phép.',
                                ])
                                ->dehydrated(false)
                                ->columnSpanFull(),
                        ])
                        ->columns(2)
                        ->addActionLabel('Thêm tài liệu')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function resolveAttachmentUploads(array $data): array
    {
        $data['attachments'] = collect($data['attachments'] ?? [])
            ->map(function (array $item): array {
                $fileState = $item['_file_upload'] ?? null;

                if ($fileState !== null) {
                    $path = is_array($fileState)
                        ? (array_values(array_filter($fileState))[0] ?? null)
                        : (is_string($fileState) && $fileState !== '' ? $fileState : null);

                    if ($path) {
                        $item['url'] = str_starts_with($path, '/storage/') || str_starts_with($path, 'http')
                            ? $path
                            : '/storage/' . ltrim($path, '/');
                    }
                }

                unset($item['_file_upload']);
                return $item;
            })
            ->values()
            ->toArray();

        return $data;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                AdminImageColumn::make('user.avatar')
                    ->label('Ảnh')
                    ->circular()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Họ và tên')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.role')
                    ->label('Vai trò')
                    ->formatStateUsing(fn ($state) => $state instanceof UserRole ? $state->label() : ($state ? UserRole::from((int) $state)->label() : '—'))
                    ->badge()
                    ->color(fn ($state): string => match (is_int($state) ? $state : (int) ($state?->value ?? 0)) {
                        UserRole::EMPLOYEE->value  => 'gray',
                        UserRole::MANAGER->value   => 'info',
                        UserRole::DIRECTOR->value  => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('user.branch.name')
                    ->label('Chi nhánh')
                    ->placeholder('—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.departmentRel.name')
                    ->label('Phòng ban')
                    ->placeholder('—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.jobPosition.name')
                    ->label('Chức vụ')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('employee_title')
                    ->label('Danh hiệu')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('user.phone')
                    ->label('Số điện thoại'),

                Tables\Columns\TextColumn::make('user.email')
                    ->label('Email')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('branch_id')
                    ->columnSpan(1)
                    ->form([
                        Forms\Components\Select::make('value')
                            ->label('Chi nhánh')
                            ->options(function (): array {
                                $query = Branch::query()->orderBy('sort')->orderBy('name');
                                $currentUser = auth()->user();
                                if (in_array($currentUser?->role, [UserRole::DIRECTOR, UserRole::MANAGER], true) && $currentUser->branch_id) {
                                    $query->where('id', $currentUser->branch_id);
                                }
                                return $query->pluck('name', 'id')->all();
                            })
                            ->placeholder('Tất cả chi nhánh')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('../department_id.value', null)),
                    ])
                    ->query(fn (Builder $query, array $data): Builder =>
                        $query->when($data['value'] ?? null, fn (Builder $q, string $v) =>
                            $q->whereHas('user', fn (Builder $u) => $u->where('branch_id', $v))
                        )
                    )
                    ->indicateUsing(function (array $data): ?string {
                        if (blank($data['value'] ?? null)) {
                            return null;
                        }
                        $branch = Branch::find($data['value']);
                        return $branch ? "Chi nhánh: {$branch->name}" : null;
                    }),

                Tables\Filters\Filter::make('department_id')
                    ->columnSpan(2)
                    ->form([
                        Forms\Components\Select::make('value')
                            ->label('Phòng ban')
                            ->options(function (Forms\Get $get): array {
                                $query = Department::query()->where('is_active', true)->orderBy('name');
                                $currentUser = auth()->user();
                                
                                if ($currentUser?->role === UserRole::DIRECTOR && $currentUser->branch_id) {
                                    $query->where('branch_id', $currentUser->branch_id);
                                } elseif ($currentUser?->role === UserRole::MANAGER && $currentUser->department_id) {
                                    $query->where('id', $currentUser->department_id);
                                } else {
                                    $branchId = $get('../branch_id.value');
                                    if ($branchId) {
                                        $query->where('branch_id', $branchId);
                                    } else {
                                        return [];
                                    }
                                }
                                return $query->pluck('name', 'id')->all();
                            })
                            ->placeholder(function (Forms\Get $get): string {
                                $currentUser = auth()->user();
                                if ($currentUser?->branch_id) {
                                    return 'Tất cả phòng ban';
                                }
                                return $get('../branch_id.value') ? 'Tất cả phòng ban' : 'Chọn chi nhánh trước';
                            })
                            ->disabled(function (Forms\Get $get): bool {
                                $currentUser = auth()->user();
                                if ($currentUser?->branch_id) {
                                    return false;
                                }
                                return !$get('../branch_id.value');
                            })
                            ->searchable()
                            ->preload()
                            ->live(),
                    ])
                    ->query(fn (Builder $query, array $data): Builder =>
                        $query->when($data['value'] ?? null, fn (Builder $q, string $v) =>
                            $q->whereHas('user', fn (Builder $u) => $u->where('department_id', $v))
                        )
                    )
                    ->indicateUsing(function (array $data): ?string {
                        if (blank($data['value'] ?? null)) {
                            return null;
                        }
                        $dept = Department::find($data['value']);
                        return $dept ? "Phòng ban: {$dept->name}" : null;
                    }),

                Tables\Filters\SelectFilter::make('role')
                    ->columnSpan(1)
                    ->label('Vai trò')
                    ->options([
                        UserRole::EMPLOYEE->value  => UserRole::EMPLOYEE->label(),
                        UserRole::MANAGER->value   => UserRole::MANAGER->label(),
                        UserRole::DIRECTOR->value  => UserRole::DIRECTOR->label(),
                    ])
                    ->query(fn (Builder $query, array $data): Builder =>
                        $query->when($data['value'] ?? null, fn (Builder $q, string $v) =>
                            $q->whereHas('user', fn (Builder $u) => $u->where('role', $v))
                        )
                    ),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(4)
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }


    public static function getEloquentQuery(): Builder
    {
        $allowedRoles = [UserRole::EMPLOYEE->value, UserRole::MANAGER->value, UserRole::DIRECTOR->value];

        $query = parent::getEloquentQuery()
            ->with(['user.branch', 'user.departmentRel', 'user.jobPosition'])
            ->whereHas('user', fn (Builder $userQuery) => $userQuery->whereIn('role', $allowedRoles));

        $currentUser = auth()->user();

        if (!$currentUser) {
            return $query;
        }

        if ($currentUser->role === UserRole::DIRECTOR && $currentUser->branch_id) {
            return $query->whereHas('user', fn (Builder $userQuery) => $userQuery->where('branch_id', $currentUser->branch_id));
        }

        if ($currentUser->role === UserRole::MANAGER && $currentUser->department_id) {
            return $query->whereHas('user', fn (Builder $userQuery) => $userQuery->where('department_id', $currentUser->department_id));
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListEmployeeProfiles::route('/'),
            'create' => Pages\CreateEmployeeProfile::route('/create'),
            'view'   => Pages\ViewEmployeeProfile::route('/{record}'),
            'edit'   => Pages\EditEmployeeProfile::route('/{record}/edit'),
        ];
    }
}
