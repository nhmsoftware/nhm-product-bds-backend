<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NewsResource\Pages;
use App\Modules\News\Models\News;
use App\Modules\Auth\Models\Enums\UserRole;
use App\Filament\Support\AdminImageColumn;
use App\Filament\Support\AdminOptions;
use App\Filament\Support\AdminUploads;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class NewsResource extends Resource
{
    protected static ?string $model = News::class;
    protected static ?string $navigationIcon = 'heroicon-o-newspaper';
    protected static ?string $navigationGroup = 'Bình luận';
    protected static ?string $navigationLabel = 'Quản lý tin tức';
    protected static ?string $modelLabel = 'Tin tức';
    protected static ?string $pluralModelLabel = 'Tin tức';

    public static function form(Form $form): Form
    {
        return $form->schema([
            // ── Loại tin ─────────────────────────────────────────────────────
            Forms\Components\ToggleButtons::make('news_type')
                ->label('Loại tin')
                ->options(['public' => 'Tin công khai', 'internal' => 'Tin nội bộ'])
                ->icons(['public' => 'heroicon-o-globe-alt', 'internal' => 'heroicon-o-lock-closed'])
                ->colors(['public' => 'success', 'internal' => 'warning'])
                ->grouped()
                ->default(fn ($record) => $record?->category === 'internal' ? 'internal' : 'public')
                ->live()
                ->dehydrated(false)
                ->columnSpanFull()
                ->afterStateUpdated(function (string $state, Forms\Set $set): void {
                    if ($state === 'internal') {
                        $set('category', 'internal');
                    } else {
                        $set('category', null);
                        $set('branch_id', null);
                        $set('department', null);
                    }
                }),

            // ── Thông tin chung ───────────────────────────────────────────────
            Forms\Components\TextInput::make('title')
                ->label('Tiêu đề')
                ->required()
                ->live(onBlur: true)
                ->afterStateUpdated(fn ($state, Forms\Set $set) => $set('slug', Str::slug((string) $state))),
            Forms\Components\TextInput::make('slug')
                ->label('Slug')
                ->required(),

            Forms\Components\Hidden::make('author_id')
                ->default(fn () => auth()->id()),

            // Danh mục: chỉ cho tin công khai, ẩn khi nội bộ (category='internal' được set tự động)
            Forms\Components\Select::make('category')
                ->label('Danh mục')
                ->options(AdminOptions::newsCategories())
                ->required(fn (Forms\Get $get) => $get('news_type') !== 'internal')
                ->hidden(fn (Forms\Get $get) => $get('news_type') === 'internal')
                ->searchable(),

            // ── Phân phối tin nội bộ (chỉ hiện khi news_type = internal) ──────
            Forms\Components\Select::make('branch_id')
                ->label('Chi nhánh')
                ->relationship('branch', 'name', function (Builder $query) {
                    $user = auth()->user();
                    if ($user && $user->role === UserRole::DIRECTOR && $user->branch_id) {
                        $query->where('id', $user->branch_id);
                    }
                    return $query;
                })
                ->searchable()
                ->preload()
                ->placeholder('Tất cả chi nhánh')
                ->live()
                ->visible(fn (Forms\Get $get) => $get('news_type') === 'internal')
                ->afterStateUpdated(function ($state, Forms\Set $set): void {
                    if (blank($state)) {
                        $set('department', null);
                    }
                }),

            Forms\Components\Select::make('department')
                ->label('Phòng ban')
                ->options(AdminOptions::departments())
                ->searchable()
                ->placeholder('Tất cả phòng ban')
                ->visible(fn (Forms\Get $get) => $get('news_type') === 'internal')
                ->disabled(fn (Forms\Get $get) => blank($get('branch_id')))
                ->helperText(fn (Forms\Get $get) => blank($get('branch_id'))
                    ? 'Chọn chi nhánh trước để lọc theo phòng ban'
                    : null),

            // ── Cài đặt xuất bản ──────────────────────────────────────────────
            Forms\Components\Toggle::make('is_published')
                ->label('Đã xuất bản')
                ->default(true),
            Forms\Components\Toggle::make('is_featured')
                ->label('Nổi bật'),
            Forms\Components\TextInput::make('sort')
                ->label('Thứ tự')
                ->numeric()
                ->default(0),
            Forms\Components\DateTimePicker::make('published_at')
                ->label('Ngày xuất bản'),

            AdminUploads::image('thumbnail', 'Ảnh thumbnail', 'admin/news')
                ->columnSpanFull(),
            Forms\Components\Textarea::make('summary')
                ->label('Tóm tắt')
                ->columnSpanFull(),
            Forms\Components\RichEditor::make('content')
                ->label('Nội dung')
                ->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            AdminImageColumn::make('thumbnail')->label('Ảnh')->square()->size(50),
            Tables\Columns\TextColumn::make('title')
                ->label('Tiêu đề')
                ->searchable()
                ->sortable()
                ->limit(50),
            Tables\Columns\TextColumn::make('category')
                ->label('Danh mục')
                ->badge()
                ->formatStateUsing(fn ($state) => AdminOptions::newsCategoryLabels()[$state] ?? $state),
            Tables\Columns\TextColumn::make('branch.name')
                ->label('Chi nhánh')
                ->placeholder('—'),
            Tables\Columns\TextColumn::make('author.name')
                ->label('Tác giả'),
            Tables\Columns\IconColumn::make('is_published')
                ->label('Xuất bản')
                ->boolean(),
            Tables\Columns\IconColumn::make('is_featured')
                ->label('Nổi bật')
                ->boolean(),
            Tables\Columns\TextColumn::make('published_at')
                ->label('Ngày đăng')
                ->dateTime('d/m/Y H:i')
                ->sortable(),
        ])->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user && $user->role === UserRole::DIRECTOR && $user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListNews::route('/'),
            'create' => Pages\CreateNews::route('/create'),
            'edit'   => Pages\EditNews::route('/{record}/edit'),
        ];
    }
}
