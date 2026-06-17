<?php

namespace App\Filament\Resources\NotificationResource\Pages;

use App\Filament\Resources\NotificationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListNotifications extends ListRecords
{
    protected static string $resource = NotificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Gửi thông báo mới'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Tất cả thông báo hệ thống')
                ->icon('heroicon-m-globe-alt'),
            'mine' => Tab::make('Thông báo của tôi')
                ->icon('heroicon-m-user')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('notifiable_id', auth()->id())),
        ];
    }
}
