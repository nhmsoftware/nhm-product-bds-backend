<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ConsultationSettingResource\Pages;
use App\Modules\Consultation\Models\ConsultationSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ConsultationSettingResource extends Resource
{
    protected static ?string $model = ConsultationSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-phone';

    protected static ?string $navigationGroup = 'CRM/Tư vấn';

    protected static ?string $modelLabel = 'Cấu hình tư vấn';

    protected static ?string $pluralModelLabel = 'Cấu hình tư vấn';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('hotline')
                    ->label('Hotline')
                    ->required()
                    ->maxLength(20),

                Forms\Components\TextInput::make('email')
                    ->label('Email liên hệ')
                    ->email()
                    ->maxLength(100),

                Forms\Components\TextInput::make('working_hours')
                    ->label('Giờ làm việc')
                    ->placeholder('Ví dụ: Thứ 2 - Thứ 7: 8:00 - 18:00')
                    ->maxLength(100),

                Forms\Components\TextInput::make('address')
                    ->label('Địa chỉ')
                    ->columnSpanFull()
                    ->maxLength(255),

                Forms\Components\Toggle::make('is_callback_enabled')
                    ->label('Cho phép yêu cầu gọi lại')
                    ->default(true),

                Forms\Components\Toggle::make('is_message_form_enabled')
                    ->label('Cho phép gửi form tư vấn')
                    ->default(true),

                Forms\Components\Toggle::make('is_active')
                    ->label('Đặt làm cấu hình đang hoạt động')
                    ->helperText('Chỉ một cấu hình được hoạt động tại một thời điểm. Bật tùy chọn này sẽ tự động tắt các cấu hình khác.')
                    ->default(false),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('hotline')
                    ->label('Hotline')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('working_hours')
                    ->label('Giờ làm việc')
                    ->placeholder('—')
                    ->limit(30),

                Tables\Columns\IconColumn::make('is_callback_enabled')
                    ->label('Gọi lại')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_message_form_enabled')
                    ->label('Form tư vấn')
                    ->boolean(),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Đang hoạt động')
                    ->afterStateUpdated(function (ConsultationSetting $record, bool $state) {
                        if ($state) {
                            // Tắt tất cả cấu hình khác khi bật cái này
                            ConsultationSetting::where('id', '!=', $record->id)
                                ->where('is_active', true)
                                ->update(['is_active' => false]);

                            Notification::make()
                                ->title('Đã kích hoạt cấu hình')
                                ->body("Cấu hình hotline {$record->hotline} đang được sử dụng. Các cấu hình khác đã được tắt.")
                                ->success()
                                ->send();
                        }
                        return redirect(request()->header('Referer'));
                    }),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Cập nhật lúc')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Chưa có cấu hình tư vấn')
            ->emptyStateDescription('Tạo cấu hình đầu tiên để bắt đầu nhận tư vấn từ khách hàng.');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListConsultationSettings::route('/'),
            'create' => Pages\CreateConsultationSetting::route('/create'),
            'edit'   => Pages\EditConsultationSetting::route('/{record}/edit'),
        ];
    }
}
