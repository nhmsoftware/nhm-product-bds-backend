<?php
namespace App\Filament\Resources;

use App\Filament\Resources\AreaCommentResource\Pages;
use App\Modules\Area\Models\AreaComment;
use App\Modules\Auth\Models\Enums\UserRole;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AreaCommentResource extends Resource
{
    protected static ?string $model = AreaComment::class;
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-bottom-center-text';
    protected static ?string $navigationGroup = 'Bình luận';
    protected static ?string $modelLabel = 'Bình luận khu đất';
    protected static ?string $pluralModelLabel = 'Bình luận khu đất';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('area_id')
                ->label('Khu đất')
                ->relationship('area', 'name', function (Builder $query) {
                    $user = auth()->user();
                    if ($user && $user->role === UserRole::DIRECTOR && $user->branch_id) {
                        $query->where('branch_id', $user->branch_id);
                    }
                    return $query;
                })
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\Select::make('user_id')
                ->label('Người viết')
                ->relationship('user', 'name', function (Builder $query) {
                    $currentUser = auth()->user();
                    if (!$currentUser) return $query;
                    $query->where('id', '!=', $currentUser->id)
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
                    return $query;
                })
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\Textarea::make('content')
                ->label('Nội dung')
                ->required()
                ->columnSpanFull()
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('area.name')->label('Khu đất'),
            Tables\Columns\TextColumn::make('user.name')->label('Người viết'),
            Tables\Columns\TextColumn::make('content')->label('Nội dung')->limit(70),
            Tables\Columns\TextColumn::make('created_at')->label('Ngày')->dateTime('d/m/Y H:i')
        ])->actions([
            Tables\Actions\ViewAction::make(),
            Tables\Actions\DeleteAction::make()
        ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user && $user->role === UserRole::DIRECTOR && $user->branch_id) {
            $query->whereHas('area', function ($q) use ($user) {
                $q->where('branch_id', $user->branch_id);
            });
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAreaComments::route('/'),
            'create' => Pages\CreateAreaComment::route('/create')
        ];
    }
}
