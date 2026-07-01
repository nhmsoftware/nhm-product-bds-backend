<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use App\Modules\Auth\Models\Role;
use App\Modules\Auth\Models\Permission;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Get;
use Illuminate\Database\Eloquent\Builder;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationGroup = 'Hệ thống';
    protected static ?string $modelLabel = 'Vai trò';
    protected static ?string $pluralModelLabel = 'Vai trò & phân quyền';
    protected static ?string $navigationLabel = 'Vai trò & phân quyền';
    protected static ?int $navigationSort = 100;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasPermission('manage_all') ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasPermission('manage_all') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasPermission('manage_all') ?? false;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        $user = auth()->user();
        if (!$user || !$user->hasPermission('manage_all')) return false;
        if ($record->name === 'super_admin') return false;
        return true;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        $user = auth()->user();
        if (!$user || !$user->hasPermission('manage_all')) return false;
        if ($record->name === 'super_admin') return false;
        if ($record->is_system) return false;
        return true;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Thông tin vai trò')->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Mã vai trò (slug)')
                    ->required()
                    ->maxLength(100)
                    ->unique(ignoreRecord: true)
                    ->regex('/^[a-z][a-z0-9_]*$/')
                    ->validationMessages([
                        'regex' => 'Mã vai trò chỉ gồm chữ thường, số và gạch dưới, bắt đầu bằng chữ.',
                        'unique' => 'Mã vai trò đã tồn tại.',
                    ])
                    ->disabled(fn ($record) => $record?->is_system)
                    ->helperText('Ví dụ: gdcn, gdkd, tp_kd. Không sửa đối với role hệ thống.'),
                Forms\Components\TextInput::make('label')
                    ->label('Tên hiển thị')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->label('Mô tả')
                    ->maxLength(1000)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('level')
                    ->label('Cấp bậc (0 = cao nhất)')
                    ->disabled()
                    ->helperText('Cấp bậc do hệ thống quản lý, không thể chỉnh sửa.'),
                Forms\Components\Toggle::make('is_system')
                    ->label('Role hệ thống')
                    ->disabled()
                    ->helperText('Role hệ thống không thể xóa hoặc đổi mã.'),
                Forms\Components\TextInput::make('sort')
                    ->label('Thứ tự sắp xếp')
                    ->numeric()
                    ->default(0),
                Forms\Components\Toggle::make('is_active')
                    ->label('Đang hoạt động')
                    ->default(true),
            ])->columns(2),

            Forms\Components\Section::make('Phân quyền')->schema([
                Forms\Components\Toggle::make('manage_all')
                    ->label('Quản lý toàn hệ thống')
                    ->helperText('Bật tùy chọn này để gán toàn bộ quyền hạn trong hệ thống cho vai trò này.')
                    ->live()
                    ->afterStateHydrated(function ($component, $state, $record) {
                        if ($record) {
                            $component->state($record->permissions()->where('name', 'manage_all')->exists());
                        } else {
                            $component->state(false);
                        }
                    })
                    ->dehydrated(false)
                    ->columnSpanFull(),

                Forms\Components\ViewField::make('permissions')
                    ->label('Quyền hạn chi tiết')
                    ->view('filament.forms.components.permission-matrix')
                    ->afterStateHydrated(function ($component, $state, $record) {
                        if ($record) {
                            $component->state($record->permissions()->where('name', '!=', 'manage_all')->pluck('permissions.id')->toArray());
                        } else {
                            $component->state([]);
                        }
                    })
                    ->dehydrated(false)
                    ->columnSpanFull()
                    ->disabled(fn (Get $get) => $get('manage_all') === true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')
                ->label('Mã vai trò')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('label')
                ->label('Tên hiển thị')
                ->searchable(),
            Tables\Columns\TextColumn::make('level')
                ->label('Cấp bậc')
                ->sortable()
                ->alignment('center'),
            Tables\Columns\IconColumn::make('is_system')
                ->label('Hệ thống')
                ->boolean()
                ->alignment('center'),
            Tables\Columns\IconColumn::make('is_active')
                ->label('Hoạt động')
                ->boolean()
                ->alignment('center'),
            Tables\Columns\TextColumn::make('permissions_count')
                ->label('Số quyền')
                ->state(function (Role $record) {
                    $hasManageAll = $record->permissions()->where('name', 'manage_all')->exists();
                    if ($hasManageAll) {
                        return 'Toàn bộ hệ thống';
                    }
                    $count = $record->permissions()->where('name', '!=', 'manage_all')->count();
                    return $count . ' quyền';
                })
                ->badge()
                ->color(fn (string $state): string => $state === 'Toàn bộ hệ thống' ? 'success' : 'primary')
                ->alignment('center'),
        ])
        ->defaultSort('level')
        ->actions([
            Tables\Actions\EditAction::make(),
        ])
        ->bulkActions([
            Tables\Actions\BulkActionGroup::make([]),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount('permissions')
            ->where('name', '!=', 'super_admin');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
