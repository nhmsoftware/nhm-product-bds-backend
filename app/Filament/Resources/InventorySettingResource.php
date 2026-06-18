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
    protected static ?string $navigationGroup = 'Cấu hình';
    protected static ?string $modelLabel = 'Cấu hình';
    protected static ?string $pluralModelLabel = 'Cấu hình';

    public static function form(Form $form): Form
    {
        return $form->schema([
            // Hiển thị tên cấu hình (readonly khi edit)
            Forms\Components\TextInput::make('key')
                ->label('Khóa cấu hình')
                ->required()
                ->disabledOn('edit')
                ->dehydratedWhenDisabled(),

            // ======== lot_lock_approval_timeout ========
            Forms\Components\Section::make('⏱ Thời gian chờ duyệt Lock lô đất')
                ->description('Sau khoảng thời gian này, yêu cầu lock chưa được duyệt sẽ tự động hết hạn và lô đất được trả về trạng thái khả dụng.')
                ->schema([
                    Forms\Components\TextInput::make('value.amount')
                        ->label('Số lượng')
                        ->numeric()
                        ->minValue(1)
                        ->suffix(fn (Forms\Get $get) => match ($get('value.unit')) {
                            'hours' => 'giờ',
                            'days'  => 'ngày',
                            default => '',
                        })
                        ->required()
                        ->validationMessages([
                            'min'      => 'Thời gian cấu hình phải lớn hơn 0.',
                            'required' => 'Vui lòng nhập số lượng thời gian.',
                        ]),
                    Forms\Components\Select::make('value.unit')
                        ->label('Đơn vị')
                        ->options([
                            'hours' => 'Giờ',
                            'days'  => 'Ngày',
                        ])
                        ->live()
                        ->required()
                        ->validationMessages([
                            'required' => 'Vui lòng chọn đơn vị thời gian.',
                        ]),
                ])
                ->columns(2)
                ->columnSpanFull()
                ->visible(fn (?InventorySetting $record) => !$record || $record?->key === 'lot_lock_approval_timeout'),

            // Fallback KeyValue cho các key khác
            Forms\Components\KeyValue::make('value')
                ->label('Giá trị')
                ->keyLabel('Thuộc tính')
                ->valueLabel('Giá trị')
                ->columnSpanFull()
                ->hidden(fn (?InventorySetting $record) => !$record || $record?->key === 'lot_lock_approval_timeout'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('key')
                    ->label('Cấu hình')
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'lot_lock_approval_timeout' => '⏱ Thời gian chờ duyệt Lock',
                        default => $state,
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('value')
                    ->label('Giá trị hiện tại')
                    ->formatStateUsing(function ($state, InventorySetting $record) {
                        if ($record->key === 'lot_lock_approval_timeout') {
                            $amount = data_get($state, 'amount', '?');
                            $unit   = data_get($state, 'unit') === 'hours' ? 'giờ' : 'ngày';
                            return "{$amount} {$unit}";
                        }
                        return is_array($state)
                            ? json_encode($state, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                            : $state;
                    }),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Cập nhật lần cuối')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Chỉnh sửa'),
            ])
            // Không cho tạo mới tùy tiện — cấu hình được seed sẵn
            ->headerActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventorySettings::route('/'),
            'edit'  => Pages\EditInventorySetting::route('/{record}/edit'),
        ];
    }
}
