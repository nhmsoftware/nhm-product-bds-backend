<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlanningSubAreaResource\Pages;
use App\Modules\Planning\Models\PlanningSubArea;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PlanningSubAreaResource extends Resource
{
    protected static ?string $model = PlanningSubArea::class;

    protected static ?string $navigationIcon  = 'heroicon-o-squares-2x2';
    protected static ?string $navigationGroup = 'Nội dung';
    protected static ?string $modelLabel      = 'Phân khu';
    protected static ?string $pluralModelLabel = 'Quản lý phân khu';
    protected static ?int    $navigationSort  = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Thông tin phân khu')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Tên phân khu')
                        ->placeholder('Ví dụ: Khu A, Khu thương mại, Khu dân cư...')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    Forms\Components\ColorPicker::make('color')
                        ->label('Màu sắc đại diện')
                        ->default('#3B82F6')
                        ->required(),

                    Forms\Components\Textarea::make('description')
                        ->label('Mô tả ngắn')
                        ->placeholder('Mô tả đặc điểm của phân khu (tuỳ chọn)...')
                        ->rows(2)
                        ->columnSpanFull(),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Đang hoạt động')
                        ->default(true)
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ColorColumn::make('color')
                    ->label('Màu')
                    ->tooltip(fn ($record) => $record->color),

                Tables\Columns\TextColumn::make('name')
                    ->label('Tên phân khu')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Mô tả')
                    ->placeholder('—')
                    ->limit(60),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Hoạt động')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Ngày tạo')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Trạng thái'),
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
            'index'  => Pages\ListPlanningSubAreas::route('/'),
            'create' => Pages\CreatePlanningSubArea::route('/create'),
            'edit'   => Pages\EditPlanningSubArea::route('/{record}/edit'),
        ];
    }
}
