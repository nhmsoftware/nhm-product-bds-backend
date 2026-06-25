<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LegalTopicResource\Pages;
use App\Modules\LegalVideo\Models\LegalTopic;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class LegalTopicResource extends Resource
{
    protected static ?string $model = LegalTopic::class;
    protected static ?string $navigationIcon = 'heroicon-o-list-bullet';
    protected static ?string $navigationGroup = 'Nội dung';
    protected static ?string $modelLabel = 'Chủ đề pháp lý';
    protected static ?string $pluralModelLabel = 'Chủ đề pháp lý';
    protected static ?string $navigationLabel = 'Chủ đề pháp lý';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Tên chủ đề')
                ->required()
                ->live(onBlur: true)
                ->afterStateUpdated(fn ($state, Forms\Set $set) => $set('slug', Str::slug((string) $state)))
                ->unique(ignoreRecord: true)
                ->validationMessages([
                    'required' => 'Tên chủ đề là bắt buộc.',
                    'unique' => 'Tên chủ đề này đã tồn tại.',
                ])
                ->maxLength(255),

            Forms\Components\TextInput::make('slug')
                ->label('Slug')
                ->required()
                ->unique(ignoreRecord: true),

            Forms\Components\Toggle::make('is_active')
                ->label('Đang hoạt động')
                ->default(true),

            Forms\Components\TextInput::make('sort')
                ->label('Thứ tự sắp xếp')
                ->numeric()
                ->default(0),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')
                ->label('Tên chủ đề')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('slug')
                ->label('Slug')
                ->searchable(),
            Tables\Columns\IconColumn::make('is_active')
                ->label('Hoạt động')
                ->boolean(),
            Tables\Columns\TextColumn::make('sort')
                ->label('Thứ tự')
                ->sortable()
                ->alignCenter(),
            Tables\Columns\TextColumn::make('legal_videos_count')
                ->label('Số bài viết pháp lý')
                ->counts('legalVideos')
                ->sortable()
                ->alignCenter(),
            Tables\Columns\TextColumn::make('created_at')
                ->label('Ngày tạo')
                ->dateTime('d/m/Y H:i')
                ->sortable(),
        ])
        ->defaultSort('sort', 'asc')
        ->actions([
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
            'index' => Pages\ListLegalTopics::route('/'),
        ];
    }
}
