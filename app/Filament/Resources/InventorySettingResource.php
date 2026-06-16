<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventorySettingResource\Pages;
use App\Modules\Area\Models\InventorySetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class InventorySettingResource extends Resource
{
    protected static ?string $model = InventorySetting::class;
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationGroup = 'Kho hàng';
    protected static ?string $modelLabel = 'Cấu hình kho hàng';
    protected static ?string $pluralModelLabel = 'Cấu hình kho hàng';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('key')->label('Khóa cấu hình')->required()->disabledOn('edit'),
            Forms\Components\KeyValue::make('value')->label('Giá trị')->keyLabel('Thuộc tính')->valueLabel('Giá trị')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('key')->label('Khóa')->searchable(),
            Tables\Columns\TextColumn::make('value')->label('Giá trị')->formatStateUsing(fn ($state) => json_encode($state, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
            Tables\Columns\TextColumn::make('updated_at')->label('Cập nhật')->dateTime('d/m/Y H:i')->sortable(),
        ])->actions([
            Tables\Actions\EditAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventorySettings::route('/'),
            'edit' => Pages\EditInventorySetting::route('/{record}/edit'),
        ];
    }
}
