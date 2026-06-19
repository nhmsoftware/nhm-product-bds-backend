<?php
namespace App\Filament\Resources;

use App\Filament\Resources\CourseLessonResource\Pages;
use App\Filament\Resources\CourseLessonResource\RelationManagers\QuizzesRelationManager;
use App\Filament\Support\AdminUploads;
use App\Modules\Learning\Models\CourseLesson;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CourseLessonResource extends Resource
{
    protected static ?string $model = CourseLesson::class;
    protected static ?string $navigationIcon = 'heroicon-o-play-circle';
    protected static ?string $navigationGroup = 'Đào tạo';
    protected static ?string $modelLabel = 'Bài học';
    protected static ?string $pluralModelLabel = 'Bài học';

    public static function form(Form $form): Form
    {
        return $form->schema(self::lessonFormSchema())->columns(2);
    }

    public static function lessonFormSchema(): array
    {
        return [
            Forms\Components\Select::make('course_id')->label('Khóa học')->relationship('course', 'title')->searchable()->preload()->required(),
            Forms\Components\TextInput::make('title')->label('Tên bài học')->required()->maxLength(255),
            Forms\Components\RichEditor::make('content')->label('Mô tả/Nội dung bài học')->columnSpanFull(),
            AdminUploads::video('video_url', 'Video đào tạo', 'admin/lessons/videos')->columnSpanFull(),
            Forms\Components\TextInput::make('duration_seconds')->label('Thời lượng video (giây)')->numeric()->minValue(0)->default(0)->helperText('Nhập thủ công thời lượng video tính bằng giây.'),
            Forms\Components\TextInput::make('order')->label('Thứ tự')->numeric()->default(0),
            Forms\Components\Toggle::make('is_active')->label('Mở khóa bài học')->default(true),
            Forms\Components\Repeater::make('attachments')
                ->label('Tài liệu đính kèm')
                ->schema([
                    Forms\Components\Select::make('type')->label('Loại')->options(['pdf' => 'PDF', 'docx' => 'Word', 'image' => 'Ảnh', 'link' => 'Liên kết'])->required(),
                    Forms\Components\TextInput::make('name')->label('Tên tài liệu')->required(),
                    Forms\Components\TextInput::make('url')->label('URL/File path')->required()->columnSpanFull(),
                    Forms\Components\TextInput::make('mime_type')->label('MIME type'),
                    Forms\Components\TextInput::make('size')->label('Dung lượng'),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('course.title')->label('Khóa học')->searchable()->limit(40),
            Tables\Columns\TextColumn::make('order')->label('Thứ tự')->sortable(),
            Tables\Columns\TextColumn::make('title')->label('Bài học')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('duration_seconds')->label('Giây')->sortable(),
            Tables\Columns\IconColumn::make('is_active')->label('Mở')->boolean(),
            Tables\Columns\TextColumn::make('quizzes_count')->label('Câu hỏi')->counts('quizzes'),
        ])->defaultSort('order')->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }

    public static function getRelations(): array
    {
        return [
            QuizzesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListCourseLessons::route('/'), 'create' => Pages\CreateCourseLesson::route('/create'), 'edit' => Pages\EditCourseLesson::route('/{record}/edit')];
    }
}
