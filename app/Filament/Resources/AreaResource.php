<?php
namespace App\Filament\Resources;

use App\Filament\Resources\AreaResource\Pages;
use App\Modules\Area\Models\Area;
use App\Modules\Area\Models\Enums\AreaStatus;
use App\Modules\Auth\Models\User;
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
    protected static ?string $pluralModelLabel = 'Khu đất';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->label('Tên khu')->required()->maxLength(255),
            Forms\Components\Select::make('branch')->label('Chi nhánh')->options(fn () => AdminOptions::branches())->searchable(),
            Forms\Components\TextInput::make('location')->label('Vị trí')->maxLength(255),
            Forms\Components\TextInput::make('type')->label('Loại hình')->maxLength(255),
            Forms\Components\Select::make('status')->label('Trạng thái')->options(self::enumOptions(AreaStatus::class)),
            Forms\Components\TextInput::make('total_lots')->label('Tổng lô')->numeric()->default(0),
            Forms\Components\TextInput::make('remaining_lots')->label('Còn hàng')->numeric()->default(0),
            Forms\Components\TextInput::make('area_size')->label('Diện tích')->numeric(),
            Forms\Components\TextInput::make('direction')->label('Hướng'),
            Forms\Components\TextInput::make('price')->label('Giá')->mask('999,999,999,999,999.99')->stripCharacters(',')->dehydrateStateUsing(fn (mixed $state) => AdminOptions::normalizeMoney($state)),
            Forms\Components\TextInput::make('unit_price')->label('Đơn giá')->mask('999,999,999,999,999.99')->stripCharacters(',')->dehydrateStateUsing(fn (mixed $state) => AdminOptions::normalizeMoney($state)),
            Forms\Components\Toggle::make('is_featured')->label('Nổi bật'),
            Forms\Components\Toggle::make('is_locked')->label('Khóa khu đất'),
            Forms\Components\Toggle::make('is_public')->label('Hiển thị công khai')->default(true),
            AdminUploads::image('sales_board_image', 'Ảnh bảng hàng', 'admin/area-sales-boards')->columnSpanFull(),
            AdminUploads::images('sales_board_images', 'Danh sách ảnh bảng hàng', 'admin/area-sales-board-gallery')->columnSpanFull(),
            Forms\Components\TextInput::make('sales_board_iframe')->label('Iframe bảng hàng')->columnSpanFull(),
            Forms\Components\TextInput::make('planning_check_url')->label('Link kiểm tra quy hoạch')->columnSpanFull(),
            Forms\Components\TextInput::make('google_maps_url')->label('Google Maps URL')->url()->columnSpanFull(),
            Forms\Components\TextInput::make('brochure')->label('Brochure')->url()->columnSpanFull(),
            Forms\Components\Textarea::make('description')->label('Mô tả')->columnSpanFull(),
            Forms\Components\Select::make('assigned_user_ids')
                ->label('Nhân viên được cấp quyền')
                ->multiple()
                ->options(fn () => User::query()->orderBy('name')->pluck('name', 'id')->all())
                ->preload()
                ->searchable()
                ->dehydrated(false)
                ->afterStateHydrated(fn (Forms\Components\Select $component, ?Area $record) => $component->state($record?->assignedUsers()->pluck('users.id')->all() ?? []))
                ->saveRelationshipsUsing(function (Area $record, ?array $state): void {
                    $existing = $record->assignedUsers()->pluck('users.id')->all();
                    $next = $state ?? [];
                    $record->assignedUsers()->detach(array_values(array_diff($existing, $next)));
                    foreach (array_diff($next, $existing) as $userId) {
                        $record->assignedUsers()->attach($userId, [
                            'id' => (string) Str::uuid(),
                            'assignable_id' => $userId,
                            'assignable_type' => 'user',
                            'permissions' => json_encode(['view_project', 'view_area', 'view_lot', 'lock_lot', 'deposit_lot'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        ]);
                    }
                })
                ->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->label('Khu đất')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('branch')->label('Chi nhánh')->searchable()->sortable(),
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
