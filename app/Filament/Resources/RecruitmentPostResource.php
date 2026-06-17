<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RecruitmentPostResource\Pages;
use App\Modules\Recruitment\Models\Enums\RecruitmentPostStatus;
use App\Modules\Recruitment\Models\RecruitmentPost;
use App\Filament\Support\AdminOptions;
use App\Filament\Support\AdminUploads;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RecruitmentPostResource extends Resource
{
    protected static ?string $model = RecruitmentPost::class;
    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    protected static ?string $navigationGroup = 'Nội dung';
    protected static ?string $modelLabel = 'Bài tuyển dụng';
    protected static ?string $pluralModelLabel = 'Bài tuyển dụng';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')
                ->label('Tiêu đề')
                ->required(),
            Forms\Components\Select::make('branch_id')
                ->relationship('branch', 'name')
                ->label('Chi nhánh')
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\TextInput::make('job_position')
                ->label('Vị trí')
                ->required(),
            Forms\Components\Select::make('department')
                ->label('Phòng ban')
                ->options(AdminOptions::departments())
                ->searchable()
                ->required(),
            Forms\Components\Select::make('status')
                ->label('Trạng thái')
                ->options(self::enumOptions(RecruitmentPostStatus::class))
                ->required(),
            AdminUploads::image('image', 'Ảnh', 'admin/recruitment')
                ->columnSpanFull(),
            Forms\Components\Textarea::make('short_description')
                ->label('Mô tả ngắn')
                ->columnSpanFull(),
            Forms\Components\RichEditor::make('content')
                ->label('Nội dung')
                ->columnSpanFull(),
            Forms\Components\RichEditor::make('job_description')
                ->label('Mô tả công việc')
                ->columnSpanFull(),
            Forms\Components\RichEditor::make('candidate_requirements')
                ->label('Yêu cầu ứng viên')
                ->columnSpanFull(),
            Forms\Components\RichEditor::make('benefits')
                ->label('Quyền lợi')
                ->columnSpanFull()
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('title')
                ->label('Bài tuyển dụng')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('branch.name')
                ->label('Chi nhánh'),
            Tables\Columns\TextColumn::make('job_position')
                ->label('Vị trí'),
            Tables\Columns\TextColumn::make('status')
                ->label('Trạng thái')
                ->formatStateUsing(fn($state) => $state instanceof RecruitmentPostStatus ? $state->label() : RecruitmentPostStatus::tryFrom((int)$state)?->label())
                ->badge()
        ])->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make()
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRecruitmentPosts::route('/'),
            'create' => Pages\CreateRecruitmentPost::route('/create'),
            'edit' => Pages\EditRecruitmentPost::route('/{record}/edit')
        ];
    }

    private static function enumOptions(string $enum): array
    {
        return collect($enum::cases())->mapWithKeys(fn($case) => [$case->value => $case->label()])->all();
    }
}
