<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\NewsCommentResource\Pages;
use App\Modules\News\Models\NewsComment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class NewsCommentResource extends Resource
{
    protected static ?string $model = NewsComment::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationGroup = 'Bình luận';

    protected static ?string $modelLabel = 'Bình luận tin tức';

    protected static ?string $pluralModelLabel = 'Bình luận tin tức';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('news_id')
                ->label('Tin tức')
                ->relationship('news', 'title')
                ->searchable()
                ->preload()
                ->required(),

            Forms\Components\Select::make('user_id')
                ->label('Người viết')
                ->relationship('user', 'name', function (Builder $query) {
                    $currentUser = auth()->user();
                    if (!$currentUser) return $query;

                    $query->where('id', '!=', $currentUser->id)
                        ->whereHas('role', fn($q) => $q->where('name', '!=', 'buyer'))
                        ->whereHas('role', fn($q) => $q->where('name', '!=', 'super_admin'))
                        ->whereNotNull('job_position_id');

                    if (!$currentUser->hasAnyPermission(['manage_all'])) {
                        $query->whereHas('role', fn($q) => $q->whereRaw('weight >= (SELECT weight FROM roles WHERE id = ?)', [$currentUser->role_id]));
                    }
                    if ($currentUser->role?->name === 'gdkd' && $currentUser->branch_id) {
                        $query->where('branch_id', $currentUser->branch_id);
                    }
                    if ($currentUser->role?->name === 'tp_kd' && $currentUser->department_id) {
                        $query->where('department_id', $currentUser->department_id);
                    }

                    return $query;
                })
                ->searchable()
                ->preload()
                ->required(),

            Forms\Components\Textarea::make('content')
                ->label('Nội dung')
                ->required()
                ->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('news.title')
                    ->label('Tin tức')
                    ->limit(40)
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Người viết'),
                Tables\Columns\TextColumn::make('content')
                    ->label('Nội dung')
                    ->limit(70),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Ngày')
                    ->dateTime('d/m/Y H:i'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('news_id')
                    ->label('Tin tức')
                    ->relationship('news', 'title')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Người viết')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
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
            'index' => Pages\ListNewsComments::route('/'),
            'create' => Pages\CreateNewsComment::route('/create'),
        ];
    }
}
