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

                    // url lưu giá trị cuối — được cập nhật bởi _file_upload (qua mutateFormData*)
                    // hoặc bởi _link_input (qua afterStateUpdated)
                    Forms\Components\Hidden::make('url'),

                    // FileUpload tên riêng — dehydrated:true để Filament tự move temp→final disk
                    Forms\Components\FileUpload::make('_file_upload')
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
                            // Đã có state array non-empty → tệp vừa upload đang pending, giữ nguyên
                            if (is_array($state) && !empty(array_filter($state))) {
                                $component->state($state);
                                return;
                            }
                            // Hiển thị file hiện có từ sibling 'url' khi edit
                            $itemState = $component->getContainer()->getRawState();
                            $url = $itemState['url'] ?? null;
                            if (is_string($url) && $url !== '' && !str_starts_with($url, 'http')) {
                                $path = (string) preg_replace('#^/?storage/#', '', $url);
                                $component->state([(string) \Illuminate\Support\Str::uuid() => $path]);
                                return;
                            }
                            $component->state([]);
                        })
                        ->visible(fn (Forms\Get $get) => in_array($get('type'), ['pdf', 'docx', 'image']))
                        ->columnSpanFull(),

                    // Link ngoài — cập nhật sibling Hidden('url') qua afterStateUpdated
                    Forms\Components\TextInput::make('_link_input')
                        ->label('URL liên kết')
                        ->required(fn (Forms\Get $get) => $get('type') === 'link')
                        ->url()
                        ->live()
                        ->afterStateHydrated(function (Forms\Components\TextInput $component): void {
                            $itemState = $component->getContainer()->getRawState();
                            $url = $itemState['url'] ?? null;
                            if (is_string($url) && str_starts_with($url, 'http')) {
                                $component->state($url);
                            }
                        })
                        ->afterStateUpdated(fn (Forms\Set $set, ?string $state) => $set('url', $state ?? ''))
                        ->dehydrated(false)
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
