<?php

declare(strict_types=1);

namespace App\Filament\Resources\NewsCommentResource\Pages;

use App\Filament\Resources\NewsCommentResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListNewsComments extends ListRecords
{
    protected static string $resource = NewsCommentResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Tất cả'),
            'public' => Tab::make('Tin công khai')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('news', function ($q) {
                    $q->whereNull('department')->whereNull('branch_id');
                })),
            'internal' => Tab::make('Tin nội bộ')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('news', function ($q) {
                    $q->where(function ($q) {
                        $q->whereNotNull('department')->orWhereNotNull('branch_id');
                    });
                })),
        ];
    }

    public function getDefaultActiveTab(): ?string
    {
        return 'all';
    }
}
