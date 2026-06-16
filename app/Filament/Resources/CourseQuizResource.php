<?php
namespace App\Filament\Resources;

use App\Filament\Resources\CourseQuizResource\Pages;
use App\Modules\Learning\Models\CourseQuiz;
use App\Filament\Support\AdminUploads;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CourseQuizResource extends Resource
{
    protected static ?string $model = CourseQuiz::class;
    protected static ?string $navigationIcon = 'heroicon-o-question-mark-circle';
    protected static ?string $navigationGroup = 'Đào tạo';
    protected static ?string $modelLabel = 'Câu hỏi quiz';
    protected static ?string $pluralModelLabel = 'Câu hỏi quiz';

    public static function form(Form $form): Form
    {
        return $form->schema(self::quizFormSchema())->columns(2);
    }

    public static function quizFormSchema(): array
    {
        return [
            Forms\Components\Select::make('lesson_id')->label('Bài học')->relationship('lesson', 'title')->searchable()->preload()->required(),
            Forms\Components\Select::make('type')->label('Loại câu hỏi')->options(['multiple_choice' => 'Trắc nghiệm', 'essay' => 'Tự luận'])->required()->live()->default('multiple_choice'),
            Forms\Components\TextInput::make('order')->label('Thứ tự')->numeric()->default(0),
            Forms\Components\TextInput::make('title')->label('Tiêu đề')->maxLength(255),
            Forms\Components\Textarea::make('question')->label('Nội dung câu hỏi')->required()->columnSpanFull(),
            AdminUploads::image('image_url', 'Ảnh minh họa', 'admin/course-quizzes')->columnSpanFull(),
            Forms\Components\Repeater::make('options')
                ->label('Đáp án trắc nghiệm')
                ->schema([
                    Forms\Components\TextInput::make('value')->label('Mã')->numeric()->required(),
                    Forms\Components\TextInput::make('label')->label('Nội dung đáp án')->required(),
                ])
                ->defaultItems(4)
                ->columns(2)
                ->visible(fn (Forms\Get $get): bool => $get('type') === 'multiple_choice')
                ->columnSpanFull(),
            Forms\Components\TextInput::make('correct_option')
                ->label('Mã đáp án đúng')
                ->numeric()
                ->visible(fn (Forms\Get $get): bool => $get('type') === 'multiple_choice'),
            Forms\Components\TextInput::make('placeholder')
                ->label('Gợi ý nhập câu tự luận')
                ->visible(fn (Forms\Get $get): bool => $get('type') === 'essay')
                ->columnSpanFull(),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('lesson.course.title')->label('Khóa học')->limit(35),
            Tables\Columns\TextColumn::make('lesson.title')->label('Bài học')->searchable()->limit(35),
            Tables\Columns\TextColumn::make('order')->label('Thứ tự')->sortable(),
            Tables\Columns\TextColumn::make('type')->label('Loại')->formatStateUsing(fn (?string $state) => $state === 'essay' ? 'Tự luận' : 'Trắc nghiệm')->badge(),
            Tables\Columns\TextColumn::make('question')->label('Câu hỏi')->searchable()->limit(60),
        ])->defaultSort('order')->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListCourseQuizzes::route('/'), 'create' => Pages\CreateCourseQuiz::route('/create'), 'edit' => Pages\EditCourseQuiz::route('/{record}/edit')];
    }
}
