<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlanningResource\Pages;
use App\Filament\Support\AdminUploads;
use App\Modules\Planning\Models\Enums\PlanningStatus;
use App\Modules\Planning\Models\Planning;
use App\Modules\Planning\Models\PlanningSubArea;
use App\Services\ProvinceService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PlanningResource extends Resource
{
    protected static ?string $model = Planning::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationGroup = 'Nội dung';

    protected static ?string $modelLabel = 'Quy hoạch';

    protected static ?string $pluralModelLabel = 'Quản lý quy hoạch';

    public static function form(Form $form): Form
    {
        return $form->schema([

            // ─── Thông tin cơ bản ─────────────────────────────────────────────
            Forms\Components\Section::make('Thông tin cơ bản')
                ->schema([
                    Forms\Components\TextInput::make('title')
                        ->label('Tiêu đề')
                        ->required()
                        ->maxLength(500)
                        ->columnSpanFull(),

                    Forms\Components\Select::make('status')
                        ->label('Trạng thái')
                        ->options(self::enumOptions(PlanningStatus::class))
                        ->required()
                        ->default(PlanningStatus::DRAFT->value),

                    Forms\Components\TextInput::make('updated_year')
                        ->label('Năm cập nhật')
                        ->numeric()
                        ->minValue(1900)
                        ->maxValue(2100)
                        ->default(now()->year)
                        ->required(),
                ])
                ->columns(2),

            // ─── Vị trí địa lý ────────────────────────────────────────────────
            Forms\Components\Section::make('Vị trí địa lý')
                ->schema([
                    Forms\Components\Select::make('city')
                        ->label('Tỉnh/Thành')
                        ->options(fn () => ProvinceService::provinces())
                        ->searchable()
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn (Forms\Set $set) => $set('district', null)),

                    Forms\Components\Select::make('district')
                        ->label('Quận/Huyện')
                        ->options(fn (Forms\Get $get): array => $get('city')
                            ? ProvinceService::districts($get('city'))
                            : [])
                        ->searchable()
                        ->placeholder(fn (Forms\Get $get) => $get('city')
                            ? 'Chọn quận/huyện...'
                            : 'Chọn tỉnh/thành trước')
                        ->disabled(fn (Forms\Get $get) => !$get('city'))
                        ->dehydrated(),

                    Forms\Components\Select::make('sub_area')
                        ->label('Phân khu')
                        ->options(fn () => PlanningSubArea::activeOptions())
                        ->searchable()
                        ->placeholder('Chọn phân khu...')
                        ->allowHtml()
                        ->getOptionLabelUsing(function (string $value): string {
                            $sub = PlanningSubArea::where('name', $value)->first();
                            if (!$sub) return $value;
                            return '<span style="display:inline-flex;align-items:center;gap:6px;">' .
                                   '<span style="width:12px;height:12px;border-radius:3px;background:' . e($sub->color) . ';display:inline-block;"></span>' .
                                   e($sub->name) . '</span>';
                        })
                        ->nullable(),
                ])
                ->columns(2),

            // ─── Chỉ tiêu quy hoạch ───────────────────────────────────────────
            Forms\Components\Section::make('Chỉ tiêu quy hoạch')
                ->schema([
                    Forms\Components\TextInput::make('symbol')
                        ->label('Ký hiệu ô đất')
                        ->maxLength(50),

                    Forms\Components\TextInput::make('density')
                        ->label('Mật độ xây dựng')
                        ->placeholder('Ví dụ: 65%')
                        ->maxLength(50),

                    Forms\Components\TextInput::make('max_height')
                        ->label('Chiều cao tối đa (tầng)')
                        ->maxLength(50),

                    Forms\Components\TextInput::make('land_use_ratio')
                        ->label('Hệ số sử dụng đất')
                        ->placeholder('Ví dụ: 4.5')
                        ->maxLength(50),

                    Forms\Components\TextInput::make('setback')
                        ->label('Khoảng lùi')
                        ->placeholder('Ví dụ: 6-10m')
                        ->maxLength(100),

                    Forms\Components\Repeater::make('land_type_notes')
                        ->label('Chú giải loại đất')
                        ->helperText('Thêm từng loại đất kèm màu sắc. App mobile sẽ hiển thị thành danh sách chú giải.')
                        ->schema([
                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\TextInput::make('label')
                                    ->label('Tên loại đất')
                                    ->placeholder('Ví dụ: Đất ở cao tầng')
                                    ->required()
                                    ->maxLength(200),

                                Forms\Components\ColorPicker::make('color')
                                    ->label('Màu sắc')
                                    ->default('#3B82F6'),
                            ]),
                        ])
                        ->addActionLabel('Thêm loại đất')
                        ->reorderable()
                        ->collapsible()
                        ->defaultItems(0)
                        ->columnSpanFull(),
                ])
                ->columns(2),

            // ─── Tài liệu & Bản đồ ───────────────────────────────────────────
            Forms\Components\Section::make('Tài liệu & Bản đồ')
                ->schema([
                    AdminUploads::image('map_image', 'Ảnh bản đồ quy hoạch', 'admin/plannings')
                        ->required()
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('pdf_url')
                        ->label('Đường dẫn file PDF')
                        ->url()
                        ->placeholder('https://...')
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('check_planning_link')
                        ->label('Link kiểm tra quy hoạch')
                        ->url()
                        ->placeholder('https://...')
                        ->columnSpanFull(),
                ])
                ->columns(2),

            // ─── Nội dung mô tả ───────────────────────────────────────────────
            Forms\Components\Section::make('Nội dung mô tả')
                ->schema([
                    Forms\Components\RichEditor::make('description')
                        ->label('Mô tả ngắn')
                        ->required()
                        ->columnSpanFull(),

                    Forms\Components\RichEditor::make('content')
                        ->label('Nội dung chi tiết')
                        ->columnSpanFull(),
                ]),

        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Quy hoạch')
                    ->searchable()
                    ->sortable()
                    ->limit(60),

                Tables\Columns\TextColumn::make('city')
                    ->label('Tỉnh/Thành')
                    ->searchable(),

                Tables\Columns\TextColumn::make('district')
                    ->label('Quận/Huyện')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Trạng thái')
                    ->formatStateUsing(fn ($state) => $state instanceof PlanningStatus
                        ? $state->label()
                        : PlanningStatus::tryFrom((int) $state)?->label())
                    ->badge()
                    ->color(fn ($state): string => match ($state instanceof PlanningStatus ? $state : PlanningStatus::tryFrom((int) $state)) {
                        PlanningStatus::DRAFT    => 'gray',
                        PlanningStatus::PUBLIC   => 'success',
                        PlanningStatus::ARCHIVED => 'warning',
                        default                  => 'gray',
                    }),

                Tables\Columns\TextColumn::make('updated_year')
                    ->label('Năm')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options(self::enumOptions(PlanningStatus::class)),
            ])
            ->actions([
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
            'index'  => Pages\ListPlannings::route('/'),
            'create' => Pages\CreatePlanning::route('/create'),
            'edit'   => Pages\EditPlanning::route('/{record}/edit'),
        ];
    }

    private static function enumOptions(string $enum): array
    {
        return collect($enum::cases())
            ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
            ->all();
    }
}
