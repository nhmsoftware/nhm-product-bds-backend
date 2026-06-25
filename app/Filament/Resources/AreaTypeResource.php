<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AreaTypeResource\Pages;
use App\Modules\Area\Models\AreaType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AreaTypeResource extends Resource
{
    protected static ?string $model = AreaType::class;
    protected static ?string $navigationIcon = 'heroicon-o-list-bullet';
    protected static ?string $navigationGroup = 'Kho hàng';
    protected static ?string $modelLabel = 'Loại hình khu đất';
    protected static ?string $pluralModelLabel = 'Quản lý loại hình khu đất';
    protected static ?string $navigationLabel = 'Loại hình khu đất';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Tên loại hình')
                ->required()
                ->unique(ignoreRecord: true)
                ->extraInputAttributes(['required' => false])
                ->validationMessages([
                    'required' => 'Tên loại hình là bắt buộc.',
                    'unique' => 'Tên loại hình này đã tồn tại.',
                ])
                ->maxLength(255),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')
                ->label('Tên loại hình')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('areas_count')
                ->label('Số khu đất sử dụng')
                ->counts('areas')
                ->sortable(),
            Tables\Columns\TextColumn::make('created_at')
                ->label('Ngày tạo')
                ->dateTime('d/m/Y H:i')
                ->sortable(),
        ])->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAreaTypes::route('/'),
        ];
    }
}
