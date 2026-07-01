<?php
namespace App\Filament\Resources;

use App\Filament\Resources\AreaResource\Pages;
use App\Modules\Area\Models\Area;
use App\Modules\Area\Models\Enums\AreaStatus;
use App\Modules\Auth\Models\Role;
use App\Filament\Support\AdminImageColumn;
use App\Filament\Support\AdminUploads;
use App\Filament\Support\AdminOptions;
use App\Filament\Support\GoongLocationInput;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;

class AreaResource extends Resource
{
    protected static ?string $model = Area::class;
    protected static ?string $navigationIcon = 'heroicon-o-map';
    protected static ?string $navigationGroup = 'Kho hàng';
    protected static ?string $modelLabel = 'Khu đất';
    protected static ?string $pluralModelLabel = 'Khu đất';
    protected static ?string $navigationLabel = 'Danh sách khu đất';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Wizard::make([
                Forms\Components\Wizard\Step::make('Thông tin cơ bản')
                    ->description('Thông tin định danh và vị trí')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Tên khu')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->extraInputAttributes(['required' => false])
                            ->validationMessages([
                                'unique' => 'Tên khu đất này đã tồn tại trong hệ thống.',
                            ])
                            ->maxLength(255),
                        Forms\Components\Select::make('branch_id')->relationship('branch', 'name')->label('Chi nhánh')->searchable()->preload(),
                        GoongLocationInput::make('location')->label('Vị trí')->maxLength(255),
                        Forms\Components\Select::make('area_type_id')->relationship('areaType', 'name')->label('Loại hình')->searchable()->preload(),
                        Forms\Components\Select::make('status')->label('Trạng thái')->options(self::enumOptions(AreaStatus::class)),
                        Forms\Components\TextInput::make('total_lots')->label('Tổng lô')->numeric()->default(0),
                        Forms\Components\TextInput::make('remaining_lots')->label('Còn hàng')->numeric()->default(0),
                        Forms\Components\TextInput::make('area_size')
                            ->label('Diện tích')
                            ->rules([
                                'nullable',
                                fn () => function (string $attribute, $value, \Closure $fail) {
                                    if ($value !== null && $value !== '') {
                                        if (AdminOptions::parseDecimal($value) === null) {
                                            $fail('Diện tích phải là một số hợp lệ.');
                                        }
                                    }
                                }
                            ])
                            ->dehydrateStateUsing(fn ($state) => AdminOptions::parseDecimal($state)),
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
                        AdminUploads::file('brochure', 'Tài liệu Brochure', 'admin/area-brochures')->columnSpanFull(),
                        Forms\Components\RichEditor::make('legal_text')->label('Thông tin pháp lý')->columnSpanFull(),
                        Forms\Components\RichEditor::make('description')->label('Mô tả')->columnSpanFull(),
                    ])->columns(2),
                Forms\Components\Wizard\Step::make('Hình ảnh & Phân quyền')
                    ->description('Ảnh bảng hàng và phân quyền truy cập')
                    ->schema([
                        AdminUploads::image('image', 'Ảnh đại diện khu đất', 'admin/area-thumbnails')
                            ->helperText('Ảnh thumbnail hiển thị trên danh sách và trang chủ mobile.')
                            ->columnSpanFull(),
                        AdminUploads::images('banner', 'Ảnh banner chi tiết (slider)', 'admin/area-banners')
                            ->helperText('Các ảnh hiển thị dạng slideshow ở trang chi tiết khu đất.')
                            ->columnSpanFull(),
                        AdminUploads::image('location_image', 'Ảnh bản đồ vị trí', 'admin/area-location-maps')
                            ->helperText('Ảnh minh họa vị trí địa lý của khu đất.')
                            ->columnSpanFull(),
                        Forms\Components\Fieldset::make('Bảng hàng')
                            ->schema([
                                AdminUploads::image('sales_board_image', 'Ảnh bảng hàng (sơ đồ lô)', 'admin/area-sales-boards')->columnSpanFull(),
                                Forms\Components\TextInput::make('sales_board_iframe')->label('Iframe bảng hàng')->columnSpanFull(),
                            ]),
                        Forms\Components\CheckboxList::make('role_access')
                            ->label('Vai trò được phép xem khu đất')
                            ->helperText('Nếu không chọn vai trò nào → tất cả mọi người đều xem được')
                            ->options(function (): array {
                                $currentUser = Filament::auth()->user();
                                $currentRoleLevel = $currentUser?->role?->level ?? 999;

                                return Role::where('is_active', true)
                                    ->where('name', '!=', 'buyer')
                                    ->where('name', '!=', 'super_admin')
                                    ->where('level', '>', $currentRoleLevel)
                                    ->orderBy('level')
                                    ->pluck('label', 'id')
                                    ->all();
                            })
                            ->columns(2)
                            ->dehydrated(false)
                            ->afterStateHydrated(function (Forms\Components\CheckboxList $component, ?Area $record) {
                                if (!$record) {
                                    $component->state([]);
                                    return;
                                }
                                $roles = $record->roleAssignments()
                                    ->pluck('assignable_id')
                                    ->map(fn ($v) => (int) $v)
                                    ->values()
                                    ->toArray();
                                $component->state($roles);
                            })
                            ->saveRelationshipsUsing(function (Area $record, ?array $state): void {
                                $record->roleAssignments()->delete();

                                foreach ($state ?? [] as $roleValue) {
                                    $record->roleAssignments()->create([
                                        'user_id' => null,
                                        'assignable_id' => (string) $roleValue,
                                        'assignable_type' => 'role',
                                        'permissions' => ['view_area'],
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
            AdminImageColumn::make('sales_board_image')->label('Hình ảnh')->square()->size(50),
            Tables\Columns\TextColumn::make('name')
                ->label('Tên dự án')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('branch.name')
                ->label('Tên chi nhánh')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('total_lots')
                ->label('Tổng số lô')
                ->sortable(),
            Tables\Columns\TextColumn::make('remaining_lots')
                ->label('Số lô còn lại')
                ->sortable(),
            Tables\Columns\TextColumn::make('status')
                ->label('Trạng thái')
                ->formatStateUsing(fn ($state) => $state instanceof AreaStatus ? $state->label() : AreaStatus::tryFrom((int) $state)?->label())
                ->badge(),
            Tables\Columns\IconColumn::make('is_featured')
                ->label('Nổi bật')
                ->boolean(),
            Tables\Columns\IconColumn::make('is_locked')
                ->label('Khóa')
                ->boolean(),
            Tables\Columns\TextColumn::make('role_permissions')
                ->label('Phân quyền')
                ->getStateUsing(function (Area $record): string {
                    $roleIds = $record->roleAssignments()
                        ->pluck('assignable_id')
                        ->all();
                    $roles = Role::whereIn('id', $roleIds)
                        ->pluck('label')
                        ->implode(', ');
                    return $roles ?: 'Tất cả';
                })
                ->badge(),
        ])
        ->defaultSort('created_at', 'desc')
        ->filters([
            Tables\Filters\SelectFilter::make('branch_id')
                ->relationship('branch', 'name')
                ->label('Chi nhánh')
                ->preload(),
            Tables\Filters\SelectFilter::make('status')
                ->options(self::enumOptions(AreaStatus::class))
                ->label('Trạng thái'),
            Tables\Filters\TernaryFilter::make('is_featured')
                ->label('Nổi bật'),
        ])
        ->actions([
            Tables\Actions\Action::make('toggle_lock')
                ->label(fn (Area $record): string => $record->is_locked ? 'Mở khóa' : 'Khóa')
                ->icon(fn (Area $record): string => $record->is_locked ? 'heroicon-o-lock-open' : 'heroicon-o-lock-closed')
                ->color(fn (Area $record): string => $record->is_locked ? 'success' : 'danger')
                ->visible(function (Area $record): bool {
                    $currentUser = auth()->user();
                    if (!$currentUser) return false;
                    if ($currentUser->hasPermission('manage_all')) return true;
                    if ($currentUser->role?->name === 'gdkd') {
                        return $currentUser->branch_id && $record->branch_id === $currentUser->branch_id;
                    }
                    return false;
                })
                ->requiresConfirmation()
                ->modalHeading(fn (Area $record): string => $record->is_locked ? 'Xác nhận mở khóa dự án' : 'Xác nhận khóa dự án')
                ->modalDescription(fn (Area $record): string => $record->is_locked 
                    ? "Bạn có chắc chắn muốn mở khóa dự án '{$record->name}'? Sau khi mở khóa, người dùng có thể thực hiện giao dịch." 
                    : "Bạn có chắc chắn muốn khóa dự án '{$record->name}'? Sau khi khóa, người dùng sẽ không thể thao tác trên bảng hàng.")
                ->action(function (Area $record): void {
                    $record->is_locked = !$record->is_locked;
                    $record->save();

                    Notification::make()
                        ->title($record->is_locked ? 'Đã khóa dự án thành công.' : 'Đã mở khóa dự án thành công.')
                        ->success()
                        ->send();
                }),
            Tables\Actions\Action::make('view_lots')
                ->label('Xem lô đất')
                ->icon('heroicon-o-squares-2x2')
                ->color('info')
                ->url(fn (Area $record): string => LotResource::getUrl('index', [
                    'tableFilters' => [
                        'area' => [
                            'value' => $record->id,
                        ],
                    ],
                ])),
            Tables\Actions\Action::make('assign_permissions')
                ->label('Phân quyền')
                ->icon('heroicon-o-shield-check')
                ->color('success')
                ->modalHeading(fn (Area $record) => "Phân quyền khu đất: {$record->name}")
                ->fillForm(fn (Area $record): array => [
                    'role_access' => $record->roleAssignments()
                        ->pluck('assignable_id')
                        ->map(fn ($v) => (int) $v)
                        ->values()
                        ->toArray()
                ])
                ->form([
                    Forms\Components\CheckboxList::make('role_access')
                        ->label('Vai trò được phép xem khu đất')
                        ->helperText('Nếu không chọn vai trò nào → tất cả mọi người đều xem được')
                        ->options(function (): array {
                            $currentUser = Filament::auth()->user();
                            $currentRoleLevel = $currentUser?->role?->level ?? 999;

                            return Role::where('is_active', true)
                                ->where('name', '!=', 'buyer')
                                ->where('name', '!=', 'super_admin')
                                ->where('level', '>', $currentRoleLevel)
                                ->orderBy('level')
                                ->pluck('label', 'id')
                                ->all();
                        })
                        ->columns(2),
                ])
                ->action(function (Area $record, array $data): void {
                    DB::transaction(function () use ($record, $data) {
                        $record->roleAssignments()->delete();

                        foreach ($data['role_access'] ?? [] as $roleValue) {
                            $record->roleAssignments()->create([
                                'user_id' => null,
                                'assignable_id' => (string) $roleValue,
                                'assignable_type' => 'role',
                                'permissions' => ['view_area'],
                            ]);
                        }
                    });

                    Notification::make()
                        ->title('Cập nhật phân quyền khu đất thành công.')
                        ->success()
                        ->send();
                }),
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make()
        ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        // General Director của chi nhánh nào thì chỉ hiển thị dự án của chi nhánh đó
        if ($user && $user->role?->name === 'gdkd' && $user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        }

        return $query;
    }

    public static function getPages(): array { return ['index'=>Pages\ListAreas::route('/'), 'create'=>Pages\CreateArea::route('/create'), 'edit'=>Pages\EditArea::route('/{record}/edit')]; }
    private static function enumOptions(string $enum): array { return collect($enum::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])->all(); }
}
