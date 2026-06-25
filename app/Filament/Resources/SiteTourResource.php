<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SiteTourResource\Pages;
use App\Filament\Support\AdminUploads;
use App\Filament\Support\GoongLocationInput;
use App\Modules\Area\Models\Lot;
use App\Modules\Auth\Models\Enums\UserRole;
use App\Modules\SiteTour\Models\SiteTour;
use Filament\Forms;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SiteTourResource extends Resource
{
    protected static ?string $model = SiteTour::class;
    protected static ?string $navigationIcon = 'heroicon-o-map';
    protected static ?string $navigationGroup = 'Hoạt động bán hàng';
    protected static ?string $modelLabel = 'Dẫn khách';
    protected static ?string $pluralModelLabel = 'Quản lý dẫn khách';
    protected static ?string $navigationLabel = 'Quản lý dẫn khách';

    public static function form(Form $form): Form
    {
        return $form->schema([
            // Nhân viên
            Forms\Components\Select::make('user_id')
                ->label('Nhân viên')
                ->relationship('user', 'name', function (Builder $query) {
                    $currentUser = auth()->user();
                    if (!$currentUser) return $query;

                    $query->where('id', '!=', $currentUser->id)
                        ->where('role', '!=', UserRole::BUYER->value)
                        ->where('role', '!=', UserRole::SUPER_ADMIN->value)
                        ->whereNotNull('job_position_id');

                    if ($currentUser->role !== UserRole::SUPER_ADMIN) {
                        $query->where('role', '<=', $currentUser->role->value);
                    }
                    if ($currentUser->role === UserRole::DIRECTOR && $currentUser->branch_id) {
                        $query->where('branch_id', $currentUser->branch_id);
                    }
                    if ($currentUser->role === UserRole::MANAGER && $currentUser->department_id) {
                        $query->where('department_id', $currentUser->department_id);
                    }
                    return $query;
                })
                ->searchable()
                ->preload()
                ->required(),

            // Khu đất — live() để trigger reload lô đất bên dưới
            Forms\Components\Select::make('project_id')
                ->label('Khu đất')
                ->relationship('project', 'name')
                ->searchable()
                ->preload()
                ->required()
                ->live()
                ->afterStateUpdated(fn (Forms\Set $set) => $set('unit_code', null)),

            // Lô đất — chỉ hiển thị khi đã chọn Khu đất, lọc theo project_id
            Forms\Components\Select::make('unit_code')
                ->label('Lô đất')
                ->required()
                ->options(function (Get $get): array {
                    $projectId = $get('project_id');
                    if (!$projectId) {
                        return [];
                    }
                    return Lot::query()
                        ->where('area_id', $projectId)
                        ->orderBy('code')
                        ->pluck('code', 'code')
                        ->toArray();
                })
                ->searchable()
                ->disabled(fn (Get $get): bool => blank($get('project_id')))
                ->placeholder(fn (Get $get): string => blank($get('project_id'))
                    ? 'Chọn Khu đất trước'
                    : 'Chọn lô đất')
                ->live()
                ->helperText(fn (Get $get): ?string => blank($get('project_id'))
                    ? 'Vui lòng chọn Khu đất trước để lọc danh sách lô đất.'
                    : null),

            // Tên khách
            Forms\Components\TextInput::make('customer_name')
                ->label('Tên khách')
                ->required(),

            GoongLocationInput::make('address_lookup')
                ->label('Vị trí')
                ->latitudeField('latitude')
                ->longitudeField('longitude')
                ->placeholder('Nhập địa chỉ dự án để lấy tọa độ tự động...')
                ->dehydrated(false)
                ->columnSpanFull(),

            Hidden::make('latitude')
                ->required(),

            Hidden::make('longitude')
                ->required(),

            // Minh chứng
            AdminUploads::image('image_path', 'Minh chứng', 'admin/site-tours')
                ->columnSpanFull(),

        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('user.name')->label('Nhân viên'),
            Tables\Columns\TextColumn::make('project.name')->label('Khu đất'),
            Tables\Columns\TextColumn::make('unit_code')->label('Lô đất'),
            Tables\Columns\TextColumn::make('customer_name')->label('Khách'),
            Tables\Columns\TextColumn::make('created_at')->label('Thời gian')->dateTime('d/m/Y H:i'),
        ])->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSiteTours::route('/'),
            'create' => Pages\CreateSiteTour::route('/create'),
            'edit'   => Pages\EditSiteTour::route('/{record}/edit'),
        ];
    }
}
