<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BranchResource\Pages;
use App\Modules\Branch\Models\Branch;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class BranchResource extends Resource
{
    protected static ?string $model = Branch::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $navigationGroup = 'Nhân sự';
    protected static ?string $modelLabel = 'Chi nhánh';
    protected static ?string $pluralModelLabel = 'Quản lý chi nhánh';
    protected static ?string $navigationLabel = 'Quản lý chi nhánh';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Tên chi nhánh')
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(fn (?string $state, Forms\Set $set) => $set('code', strtoupper(Str::slug((string) $state, '_')))),
            Forms\Components\TextInput::make('code')->label('Mã chi nhánh')->maxLength(50),
            Forms\Components\TextInput::make('area')->label('Khu vực quản lý')->maxLength(255),
            Forms\Components\TextInput::make('sort')->label('Thứ tự')->numeric()->default(0),
            Forms\Components\Toggle::make('is_active')->label('Đang sử dụng')->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->label('Chi nhánh')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('code')->label('Mã')->searchable(),
            Tables\Columns\TextColumn::make('area')->label('Khu vực'),
            Tables\Columns\IconColumn::make('is_active')->label('Đang dùng')->boolean(),
            Tables\Columns\TextColumn::make('sort')->label('Thứ tự')->sortable(),
        ])->defaultSort('sort')->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBranches::route('/'),
            'create' => Pages\CreateBranch::route('/create'),
            'edit' => Pages\EditBranch::route('/{record}/edit'),
        ];
    }
}
