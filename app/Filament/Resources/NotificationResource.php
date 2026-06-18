<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NotificationResource\Pages;
use App\Modules\Notification\Models\Notification;
use App\Modules\Auth\Models\Enums\UserRole;
use App\Filament\Support\AdminOptions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification as FilamentNotification;

class NotificationResource extends Resource
{
    protected static ?string $model = Notification::class;
    protected static ?string $navigationIcon = 'heroicon-o-bell';
    protected static ?string $navigationGroup = 'Hệ thống';
    protected static ?string $modelLabel = 'Thông báo';
    protected static ?string $pluralModelLabel = 'Thông báo';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Nội dung thông báo')
                ->schema([
                    Forms\Components\TextInput::make('title')
                        ->label('Tiêu đề')
                        ->required()
                        ->placeholder('Nhập tiêu đề thông báo...'),
                    Forms\Components\Textarea::make('body')
                        ->label('Nội dung chi tiết')
                        ->required()
                        ->rows(4)
                        ->placeholder('Nhập nội dung chi tiết thông báo...'),
                ])
                ->visible(fn (string $operation): bool => $operation === 'create'),

            Forms\Components\Section::make('Đối tượng nhận thông báo')
                ->schema([
                    Forms\Components\Select::make('target_type')
                        ->label('Gửi tới')
                        ->options([
                            'all' => 'Tất cả nhân sự (CEO, Giám đốc, Trưởng phòng, Nhân viên)',
                            'role' => 'Theo vai trò / chức vụ',
                            'department' => 'Theo phòng ban',
                            'area' => 'Theo chi nhánh / khu vực',
                            'users' => 'Chọn đích danh các nhân sự',
                        ])
                        ->required()
                        ->live()
                        ->default('all'),

                    Forms\Components\Select::make('target_role')
                        ->label('Chọn vai trò')
                        ->options(collect(UserRole::cases())->mapWithKeys(fn ($r) => [$r->value => $r->label()])->toArray())
                        ->visible(fn (Forms\Get $get): bool => $get('target_type') === 'role')
                        ->required(fn (Forms\Get $get): bool => $get('target_type') === 'role'),

                    Forms\Components\Select::make('target_department')
                        ->label('Chọn phòng ban')
                        ->options(AdminOptions::departments())
                        ->visible(fn (Forms\Get $get): bool => $get('target_type') === 'department')
                        ->required(fn (Forms\Get $get): bool => $get('target_type') === 'department'),

                    Forms\Components\Select::make('target_area')
                        ->label('Chọn chi nhánh')
                        ->options(function () {
                            return \App\Modules\Branch\Models\Branch::where('is_active', true)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->toArray();
                        })
                        ->searchable()
                        ->preload()
                        ->visible(fn (Forms\Get $get): bool => $get('target_type') === 'area')
                        ->required(fn (Forms\Get $get): bool => $get('target_type') === 'area'),

                    Forms\Components\Select::make('target_users')
                        ->label('Chọn nhân sự nhận')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->options(function () {
                            $currentUser = auth()->user();
                            if (!$currentUser) return [];
                            $query = \App\Modules\Auth\Models\User::query()
                                ->where('is_active', true)
                                ->where('id', '!=', $currentUser->id)
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

                            return $query->pluck('name', 'id')->toArray();
                        })
                        ->visible(fn (Forms\Get $get): bool => $get('target_type') === 'users')
                        ->required(fn (Forms\Get $get): bool => $get('target_type') === 'users'),
                ])
                ->visible(fn (string $operation): bool => $operation === 'create'),

            Forms\Components\Section::make('Thông tin chi tiết thông báo')
                ->schema([
                    Forms\Components\TextInput::make('type')->label('Loại')->disabled(),
                    Forms\Components\TextInput::make('notifiable_type')->label('Loại đối tượng nhận')->disabled(),
                    Forms\Components\TextInput::make('recipient_name')
                        ->label('Nhân sự nhận')
                        ->afterStateHydrated(function ($component, $record) {
                            $component->state($record?->notifiable?->name ?? 'Không xác định');
                        })
                        ->disabled(),
                    Forms\Components\KeyValue::make('data')->label('Nội dung payload (JSON)')->disabled()->columnSpanFull(),
                    Forms\Components\DateTimePicker::make('read_at')->label('Đã đọc lúc')->disabled(),
                ])
                ->columns(2)
                ->visible(fn (string $operation): bool => $operation !== 'create'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('data.title')->label('Tiêu đề')->searchable()->limit(40),
                Tables\Columns\TextColumn::make('data.body')->label('Nội dung')->searchable()->limit(60),
                Tables\Columns\TextColumn::make('notifiable.name')->label('Người nhận')->default('-'),
                Tables\Columns\IconColumn::make('read_at')
                    ->label('Đã đọc')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),
                Tables\Columns\TextColumn::make('created_at')->label('Ngày gửi')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('read_status')
                    ->label('Trạng thái đọc')
                    ->placeholder('Tất cả')
                    ->trueLabel('Đã đọc')
                    ->falseLabel('Chưa đọc')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('read_at'),
                        false: fn ($query) => $query->whereNull('read_at'),
                    ),
            ])
            ->actions([
                Tables\Actions\Action::make('mark_read')
                    ->label('Đọc')
                    ->icon('heroicon-o-envelope-open')
                    ->color('success')
                    ->visible(fn ($record) => $record->read_at === null && $record->notifiable_id === auth()->id())
                    ->action(function ($record) {
                        $record->markAsRead();
                        FilamentNotification::make()->title('Đã đánh dấu thông báo là đã đọc.')->success()->send();
                    }),
                Tables\Actions\EditAction::make()->label('Xem chi tiết'),
                Tables\Actions\DeleteAction::make()->label('Xóa'),
            ])
            ->headerActions([
                Tables\Actions\Action::make('mark_all_read')
                    ->label('Đọc tất cả của tôi')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function () {
                        Notification::where('notifiable_id', auth()->id())
                            ->whereNull('read_at')
                            ->update(['read_at' => now()]);
                        FilamentNotification::make()->title('Đã đánh dấu tất cả thông báo của bạn là đã đọc.')->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNotifications::route('/'),
            'create' => Pages\CreateNotification::route('/create'),
            'edit' => Pages\EditNotification::route('/{record}/edit'),
        ];
    }
}
