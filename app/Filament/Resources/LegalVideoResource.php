<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LegalVideoResource\Pages;
use App\Modules\LegalVideo\Models\LegalVideo;
use App\Filament\Support\AdminOptions;
use App\Filament\Support\AdminUploads;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class LegalVideoResource extends Resource
{
    protected static ?string $model = LegalVideo::class;

    protected static ?string $navigationIcon = 'heroicon-o-video-camera';

    protected static ?string $navigationGroup = 'Nội dung';

    protected static ?string $modelLabel = 'Video pháp lý';

    protected static ?string $pluralModelLabel = 'Video pháp lý';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->label('Tiêu đề')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, Forms\Set $set) => $set('slug', Str::slug((string) $state))),

                Forms\Components\TextInput::make('slug')
                    ->label('Slug')
                    ->required(),

                Forms\Components\Select::make('category')
                    ->label('Chủ đề')
                    ->options(AdminOptions::legalVideoCategories())
                    ->searchable()
                    ->required(),

                Forms\Components\TextInput::make('duration_seconds')
                    ->label('Thời lượng giây')
                    ->numeric(),

                Forms\Components\Toggle::make('is_active')
                    ->label('Đang hiển thị')
                    ->default(true),

                Forms\Components\DateTimePicker::make('published_at')
                    ->label('Ngày xuất bản'),

                AdminUploads::video('video_url', 'URL video', 'admin/legal-videos')
                    ->required()
                    ->columnSpanFull(),

                AdminUploads::image('thumbnail_url', 'Ảnh thumbnail', 'admin/legal-videos')
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('short_description')
                    ->label('Mô tả ngắn')
                    ->columnSpanFull(),

                Forms\Components\RichEditor::make('description')
                    ->label('Nội dung')
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Video')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('category')
                    ->label('Chủ đề')
                    ->badge()
                    ->options(AdminOptions::legalVideoCategories()),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Hiển thị')
                    ->boolean(),

                Tables\Columns\TextColumn::make('published_at')
                    ->label('Ngày đăng')
                    ->dateTime('d/m/Y H:i'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLegalVideos::route('/'),
            'create' => Pages\CreateLegalVideo::route('/create'),
            'edit' => Pages\EditLegalVideo::route('/{record}/edit'),
        ];
    }
}
