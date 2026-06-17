<?php
namespace App\Filament\Resources;

use App\Filament\Resources\AreaResource\Pages;
use App\Modules\Area\Models\Area;
use App\Modules\Area\Models\Enums\AreaStatus;
use App\Modules\Auth\Models\User;
use App\Modules\Auth\Models\Enums\UserRole;
use App\Filament\Support\AdminUploads;
use App\Filament\Support\AdminOptions;
use Illuminate\Support\Str;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AreaResource extends Resource
{
    protected static ?string $model = Area::class;
    protected static ?string $navigationIcon = 'heroicon-o-map';
    protected static ?string $navigationGroup = 'Kho hàng';
    protected static ?string $modelLabel = 'Khu đất';
    protected static ?string $pluralModelLabel = 'Quản lý khu đất';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Wizard::make([
                Forms\Components\Wizard\Step::make('Thông tin cơ bản')
                    ->description('Thông tin định danh và vị trí')
                    ->schema([
                        Forms\Components\TextInput::make('name')->label('Tên khu')->required()->maxLength(255),
                        Forms\Components\Select::make('branch_id')->relationship('branch', 'name')->label('Chi nhánh')->searchable()->preload(),
                        Forms\Components\TextInput::make('location')->label('Vị trí')->maxLength(255),
                        Forms\Components\TextInput::make('type')->label('Loại hình')->maxLength(255),
                        Forms\Components\Select::make('status')->label('Trạng thái')->options(self::enumOptions(AreaStatus::class)),
                        Forms\Components\TextInput::make('total_lots')->label('Tổng lô')->numeric()->default(0),
                        Forms\Components\TextInput::make('remaining_lots')->label('Còn hàng')->numeric()->default(0),
                        Forms\Components\TextInput::make('area_size')->label('Diện tích')->numeric(),
                        Forms\Components\TextInput::make('direction')->label('Hướng'),
                    ])->columns(2),
                Forms\Components\Wizard\Step::make('Giá & Tài liệu')
                    ->description('Định giá và thông tin pháp lý, bản đồ')
                    ->schema([
                        Forms\Components\Toggle::make('is_featured')->label('Nổi bật'),
                        Forms\Components\Toggle::make('is_locked')->label('Khóa khu đất'),
                        Forms\Components\Toggle::make('is_public')->label('Hiển thị công khai')->default(true),
                        Forms\Components\TextInput::make('planning_check_url')->label('Link kiểm tra quy hoạch')->columnSpanFull(),
                        Forms\Components\TextInput::make('google_maps_url')->label('Google Maps URL')->url()->columnSpanFull(),
                        Forms\Components\TextInput::make('brochure')->label('Brochure')->url()->columnSpanFull(),
                        Forms\Components\Textarea::make('description')->label('Mô tả')->columnSpanFull(),
                    ])->columns(2),
                Forms\Components\Wizard\Step::make('Hình ảnh & Phân quyền')
                    ->description('Ảnh bảng hàng và phân quyền truy cập')
                    ->schema([
                        AdminUploads::image('sales_board_image', 'Ảnh bảng hàng', 'admin/area-sales-boards')->columnSpanFull(),
                        AdminUploads::images('sales_board_images', 'Danh sách ảnh bảng hàng', 'admin/area-sales-board-gallery')->columnSpanFull(),
                        Forms\Components\TextInput::make('sales_board_iframe')->label('Iframe bảng hàng')->columnSpanFull(),
                        Forms\Components\Repeater::make('assignments')
                            ->label('Phân quyền truy cập cho nhân sự')
                            ->schema([
                                Forms\Components\Select::make('user_id')
                                    ->label('Nhân viên')
                                    ->options(fn () => User::query()
                                        ->where('is_active', true)
                                        ->where('role', '!=', UserRole::BUYER->value)
                                        ->where(fn ($query) => $query->where('role', '!=', UserRole::EMPLOYEE->value)->orWhereNotNull('job_position_id'))
                                        ->orderBy('name')
                                        ->pluck('name', 'id')
                                        ->all()
                                    )
                                    ->required()
                                    ->searchable(),
                                Forms\Components\CheckboxList::make('permissions')
                                    ->label('Quyền hạn')
                                    ->options([
                                        'view_area' => 'Xem khu đất',
                                        'view_lot' => 'Xem lô đất',
                                        'lock_lot' => 'Khóa lô đất',
                                        'deposit_lot' => 'Đặt cọc lô đất',
                                    ])
                                    ->columns(2)
                                    ->required(),
                            ])
                            ->columns(2)
                            ->dehydrated(false)
                            ->afterStateHydrated(function (Forms\Components\Repeater $component, ?Area $record) {
                                if (!$record) return;
                                $assignments = \DB::table('area_assignments')
                                    ->where('area_id', $record->id)
                                    ->whereNull('deleted_at')
                                    ->get()
                                    ->map(fn($row) => [
                                        'user_id' => $row->user_id,
                                        'permissions' => json_decode($row->permissions ?? '[]', true) ?: [],
                                    ])
                                    ->toArray();
                                $component->state($assignments);
                            })
                            ->saveRelationshipsUsing(function (Area $record, ?array $state): void {
                                $next = $state ?? [];
                                
                                \DB::table('area_assignments')
                                    ->where('area_id', $record->id)
                                    ->delete();
                                    
                                foreach ($next as $item) {
                                    if (empty($item['user_id'])) continue;
                                    \DB::table('area_assignments')->insert([
                                        'id' => (string) Str::uuid(),
                                        'area_id' => $record->id,
                                        'user_id' => $item['user_id'],
                                        'assignable_id' => $item['user_id'],
                                        'assignable_type' => 'user',
                                        'permissions' => json_encode($item['permissions'] ?? [], JSON_UNESCAPED_UNICODE),
                                        'created_at' => now(),
                                        'updated_at' => now(),
                                    ]);
                                }
                            })
                            ->columnSpanFull(),
                    ]),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->label('Khu đất')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('branch.name')->label('Chi nhánh')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('status')->label('Trạng thái')->formatStateUsing(fn ($state) => $state instanceof AreaStatus ? $state->label() : AreaStatus::tryFrom((int) $state)?->label())->badge(),
            Tables\Columns\TextColumn::make('remaining_lots')->label('Còn hàng')->sortable(),
            Tables\Columns\IconColumn::make('is_featured')->label('Nổi bật')->boolean(),
            Tables\Columns\IconColumn::make('is_locked')->label('Khóa')->boolean(),
            Tables\Columns\TextColumn::make('assignedUsers.name')->label('Nhân viên')->badge()->limitList(3),
        ])->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }

    public static function getPages(): array { return ['index'=>Pages\ListAreas::route('/'), 'create'=>Pages\CreateArea::route('/create'), 'edit'=>Pages\EditArea::route('/{record}/edit')]; }
    private static function enumOptions(string $enum): array { return collect($enum::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])->all(); }
}
