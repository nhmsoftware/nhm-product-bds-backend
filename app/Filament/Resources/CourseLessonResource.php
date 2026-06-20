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
            Forms\Components\Hidden::make('duration_seconds')->default(0),
            Forms\Components\TextInput::make('order')->label('Thứ tự')->numeric()->default(0),
            Forms\Components\Toggle::make('is_active')->label('Mở khóa bài học')->default(true),
            Forms\Components\Repeater::make('attachments')
                ->label('Tài liệu đính kèm')
                ->schema([
                    Forms\Components\Select::make('type')
                        ->label('Loại')
                        ->options(['pdf' => 'PDF', 'docx' => 'Word', 'image' => 'Ảnh', 'link' => 'Liên kết ngoài'])
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (Forms\Set $set, ?string $state): void {
                            $set('url', '');
                            $mimeMap = [
                                'pdf'  => 'application/pdf',
                                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'image' => 'image/jpeg',
                                'link' => '',
                            ];
                            $set('mime_type', $mimeMap[$state] ?? '');
                        }),

                    Forms\Components\TextInput::make('name')
                        ->label('Tên tài liệu')
                        ->required(),

                    // FileUpload viết thẳng vào trường 'url' — Filament tự xử lý move temp→final
                    Forms\Components\FileUpload::make('url')
                        ->label('Chọn tệp')
                        ->disk('public')
                        ->directory('learning/attachments')
                        ->visibility('public')
                        ->acceptedFileTypes([
                            'application/pdf',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'image/jpeg', 'image/png', 'image/gif', 'image/webp',
                        ])
                        ->maxSize(50 * 1024)
                        ->downloadable()
                        ->openable()
                        ->afterStateHydrated(function (Forms\Components\FileUpload $component, mixed $state): void {
                            // Nếu state đã là array (tệp vừa upload hoặc đã set sẵn) → giữ nguyên
                            if (is_array($state)) {
                                $component->state($state);
                                return;
                            }
                            // Chuỗi path nội bộ (không phải link http) → chuyển sang format array
                            if (is_string($state) && $state !== '' && !str_starts_with($state, 'http')) {
                                $path = (string) preg_replace('#^/?storage/#', '', $state);
                                $component->state([(string) \Illuminate\Support\Str::uuid() => $path]);
                                return;
                            }
                            $component->state([]);
                        })
                        ->dehydrateStateUsing(function (mixed $state): string {
                            if (!is_array($state)) {
                                return '';
                            }
                            $values = array_values(array_filter($state));
                            if (empty($values)) {
                                return '';
                            }
                            $path = $values[0];
                            if (!str_starts_with($path, '/storage/') && !str_starts_with($path, 'http')) {
                                return '/storage/' . ltrim($path, '/');
                            }
                            return $path;
                        })
                        ->dehydrated(fn (Forms\Get $get) => in_array($get('type'), ['pdf', 'docx', 'image']))
                        ->visible(fn (Forms\Get $get) => in_array($get('type'), ['pdf', 'docx', 'image']))
                        ->columnSpanFull(),

                    // TextInput cùng tên 'url' — chỉ active (dehydrated) khi type = link
                    Forms\Components\TextInput::make('url')
                        ->label('URL liên kết')
                        ->required(fn (Forms\Get $get) => $get('type') === 'link')
                        ->url()
                        ->dehydrated(fn (Forms\Get $get) => $get('type') === 'link')
                        ->visible(fn (Forms\Get $get) => $get('type') === 'link')
                        ->columnSpanFull(),

                    Forms\Components\Hidden::make('mime_type'),
                    Forms\Components\Hidden::make('size'),
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
