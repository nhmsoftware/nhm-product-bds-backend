<?php
namespace App\Filament\Resources;
use App\Filament\Resources\LotResource\Pages;
use App\Modules\Area\Models\Enums\LotStatus;
use App\Modules\Area\Models\Lot;
use App\Filament\Support\AdminUploads;
use App\Filament\Support\AdminOptions;
use Filament\Forms; use Filament\Forms\Form; use Filament\Resources\Resource; use Filament\Tables; use Filament\Tables\Table;
class LotResource extends Resource
{
    protected static ?string $model = Lot::class;
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';
    protected static ?string $navigationGroup = 'Kho hàng';
    protected static ?string $modelLabel = 'Lô đất';
    protected static ?string $pluralModelLabel = 'Lô đất';
    protected static ?string $navigationLabel = 'Danh sách lô đất';
    public static function form(Form $form): Form { return $form->schema([
        Forms\Components\Select::make('area_id')
            ->label('Khu đất')
            ->relationship('area', 'name')
            ->searchable()
            ->preload()
            ->required()
            ->default(function () {
                if (request()->has('area_id')) {
                    return request()->query('area_id');
                }
                $referer = request()->headers->get('referer');
                if ($referer) {
                    $parts = parse_url($referer);
                    if (isset($parts['query'])) {
                        parse_str($parts['query'], $query);
                        if (isset($query['tableFilters']['area']['value'])) {
                            return $query['tableFilters']['area']['value'];
                        }
                    }
                }
                return null;
            })
            ->disabled(function () {
                if (request()->has('area_id')) {
                    return true;
                }
                $referer = request()->headers->get('referer');
                if ($referer) {
                    $parts = parse_url($referer);
                    if (isset($parts['query'])) {
                        parse_str($parts['query'], $query);
                        if (isset($query['tableFilters']['area']['value'])) {
                            return true;
                        }
                    }
                }
                return false;
            })
            ->dehydrated(true),
        Forms\Components\TextInput::make('code')->label('Mã lô')->required(),
        Forms\Components\Select::make('status')->label('Trạng thái')->options(self::enumOptions(LotStatus::class))->required(),
        Forms\Components\TextInput::make('area_size')->label('Diện tích')->numeric(),
        Forms\Components\TextInput::make('direction')->label('Hướng'),
        Forms\Components\TextInput::make('price')->label('Giá')->mask(\Filament\Support\RawJs::make('$money($input, \'.\', \',\', 0)'))->stripCharacters(',')->dehydrateStateUsing(fn (mixed $state) => AdminOptions::normalizeMoney($state)),
        Forms\Components\TextInput::make('unit_price')->label('Đơn giá/m²')->mask(\Filament\Support\RawJs::make('$money($input, \'.\', \',\', 0)'))->stripCharacters(',')->dehydrateStateUsing(fn (mixed $state) => AdminOptions::normalizeMoney($state)),
        Forms\Components\TextInput::make('legal')->label('Pháp lý'),
        Forms\Components\TextInput::make('frontage')->label('Mặt tiền')->numeric(),
        Forms\Components\Toggle::make('is_corner')->label('Lô góc'),
        Forms\Components\Toggle::make('is_locked')->label('Đã lock'),
        Forms\Components\Textarea::make('description')->label('Mô tả')->columnSpanFull(),
        AdminUploads::image('image_url', 'Ảnh đại diện', 'admin/lots')->columnSpanFull(),
        AdminUploads::images('images', 'Danh sách ảnh lô đất', 'admin/lot-gallery')->columnSpanFull(),
    ])->columns(2); }
    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('code')->label('Mã lô')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('area.name')->label('Khu đất')->searchable(),
            Tables\Columns\TextColumn::make('status')->label('Trạng thái')->formatStateUsing(fn($state)=>$state instanceof LotStatus?$state->label():LotStatus::tryFrom((int)$state)?->label())->badge(),
            Tables\Columns\TextColumn::make('price')->label('Giá')->money('VND')->sortable(),
            Tables\Columns\IconColumn::make('is_locked')->label('Lock')->boolean(),
        ])
        ->filters([
            Tables\Filters\SelectFilter::make('area')
                ->relationship('area', 'name')
                ->label('Khu đất')
                ->preload(),
            Tables\Filters\SelectFilter::make('status')
                ->label('Trạng thái')
                ->options(self::enumOptions(LotStatus::class))
        ])
        ->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make()
        ]);
    }
    public static function getPages(): array { return ['index'=>Pages\ListLots::route('/'),'create'=>Pages\CreateLot::route('/create'),'edit'=>Pages\EditLot::route('/{record}/edit')]; }
    private static function enumOptions(string $enum): array { return collect($enum::cases())->mapWithKeys(fn($case)=>[$case->value=>$case->label()])->all(); }
}
