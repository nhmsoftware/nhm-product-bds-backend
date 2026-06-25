<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\ConsultationMessageResource\Pages;
use App\Modules\Consultation\Models\ConsultationMessage;
use App\Modules\Consultation\Models\Enums\ConsultationStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ConsultationMessageResource extends Resource
{
    protected static ?string $model = ConsultationMessage::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationGroup = 'CRM/Tư vấn';

    protected static ?string $modelLabel = 'Yêu cầu tư vấn';

    protected static ?string $pluralModelLabel = 'Yêu Cầu Tư Vấn';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('full_name')
                    ->label('Họ tên khách hàng')
                    ->required(),
                Forms\Components\TextInput::make('phone')
                    ->label('Số điện thoại')
                    ->required(),
                Forms\Components\TextInput::make('email')
                    ->label('Email')
                    ->email(),
                Forms\Components\TextInput::make('project_name')
                    ->label('Dự án quan tâm'),
                Forms\Components\Select::make('status')
                    ->label('Trạng thái xử lý')
                    ->options(self::enumOptions(ConsultationStatus::class))
                    ->required(),
                Forms\Components\Textarea::make('content')
                    ->label('Nội dung yêu cầu')
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Họ tên khách hàng')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Số điện thoại')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('project_name')
                    ->label('Dự án quan tâm')
                    ->placeholder('—')
                    ->limit(30),

                Tables\Columns\TextColumn::make('content')
                    ->label('Nội dung yêu cầu')
                    ->limit(50)
                    ->placeholder('—')
                    ->tooltip(fn (?string $state): ?string => $state),

                Tables\Columns\TextColumn::make('status')
                    ->label('Trạng thái xử lý')
                    ->formatStateUsing(fn ($state): string => $state instanceof ConsultationStatus
                        ? $state->label()
                        : (ConsultationStatus::tryFrom((int) $state)?->label() ?? '—'))
                    ->badge()
                    ->color(fn ($state): string => match (
                        $state instanceof ConsultationStatus ? $state : ConsultationStatus::tryFrom((int) $state)
                    ) {
                        ConsultationStatus::PENDING   => 'warning',
                        ConsultationStatus::PROCESSED => 'success',
                        ConsultationStatus::CANCELLED => 'danger',
                        default                       => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Ngày gửi yêu cầu')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options(self::enumOptions(ConsultationStatus::class)),
            ])
            ->actions([
                Tables\Actions\Action::make('change_status')
                    ->label('Trạng thái')
                    ->icon('heroicon-o-arrow-path')
                    ->color(fn (ConsultationMessage $record): string => match ($record->status) {
                        ConsultationStatus::PENDING   => 'warning',
                        ConsultationStatus::PROCESSED => 'success',
                        ConsultationStatus::CANCELLED => 'danger',
                        default                       => 'gray',
                    })
                    ->form([
                        Forms\Components\Select::make('status')
                            ->label('Trạng thái xử lý')
                            ->options(self::enumOptions(ConsultationStatus::class))
                            ->default(fn (ConsultationMessage $record): int => $record->status->value)
                            ->required(),
                    ])
                    ->action(function (ConsultationMessage $record, array $data): void {
                        $record->update(['status' => $data['status']]);
                    })
                    ->modalHeading('Thay đổi trạng thái')
                    ->modalSubmitActionLabel('Lưu'),
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
            'index'  => Pages\ListConsultationMessages::route('/'),
            'create' => Pages\CreateConsultationMessage::route('/create'),
            'edit'   => Pages\EditConsultationMessage::route('/{record}/edit'),
        ];
    }

    private static function enumOptions(string $enum): array
    {
        return collect($enum::cases())
            ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
            ->all();
    }
}
