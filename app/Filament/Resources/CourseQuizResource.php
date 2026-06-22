<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\CourseQuizResource\Pages;
use App\Modules\Learning\Models\Course;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CourseQuizResource extends Resource
{
    protected static ?string $model = Course::class;
    protected static ?string $navigationIcon = 'heroicon-o-question-mark-circle';
    protected static ?string $navigationGroup = 'Đào tạo';
    protected static ?string $navigationLabel = 'Quản lý quiz';
    protected static ?string $modelLabel = 'Quiz';
    protected static ?string $pluralModelLabel = 'Quản lý quiz';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')
                ->label('Tên khóa học')
                ->disabled(),
            Forms\Components\Textarea::make('description')
                ->label('Mô tả khóa học')
                ->disabled()
                ->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('title')
                ->label('Khóa học')
                ->searchable()
                ->sortable()
                ->limit(60),
            Tables\Columns\TextColumn::make('quizzes_count')
                ->label('Số câu hỏi')
                ->counts('quizzes')
                ->badge(),
        ])
        ->defaultSort('order')
        ->actions([
            Tables\Actions\EditAction::make()
                ->label('Quản lý câu hỏi')
                ->icon('heroicon-o-pencil-square'),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            CourseLessonResource\RelationManagers\QuizzesRelationManager::class,
        ];
    }

    public static function quizFormSchema(mixed $context = null): array
    {
        $schema = [];
        if ($context instanceof \Filament\Resources\RelationManagers\RelationManager) {
            $schema[] = Forms\Components\Select::make('lesson_id')
                ->label('Bài học')
                ->options(function () use ($context): array {
                    $courseId = $context->getOwnerRecord()->id;
                    return \App\Modules\Learning\Models\CourseLesson::where('course_id', $courseId)
                        ->orderBy('order')
                        ->pluck('title', 'id')
                        ->toArray();
                })
                ->searchable()
                ->preload()
                ->required()
                ->extraInputAttributes(['required' => false])
                ->validationMessages(['required' => __('common.error.required')]);
        } elseif ($context === true || $context === false) {
            if (!$context) {
                $schema[] = Forms\Components\Select::make('lesson_id')
                    ->label('Bài học')
                    ->relationship('lesson', 'title')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->extraInputAttributes(['required' => false])
                    ->validationMessages(['required' => __('common.error.required')]);
            }
        } else {
            $schema[] = Forms\Components\Select::make('lesson_id')
                ->label('Bài học')
                ->relationship('lesson', 'title')
                ->searchable()
                ->preload()
                ->required()
                ->extraInputAttributes(['required' => false])
                ->validationMessages(['required' => __('common.error.required')]);
        }

        return array_merge($schema, [
            Forms\Components\Select::make('type')
                ->label('Loại câu hỏi')
                ->options(['multiple_choice' => 'Trắc nghiệm', 'essay' => 'Tự luận'])
                ->required()
                ->extraInputAttributes(['required' => false])
                ->validationMessages(['required' => __('common.error.required')])
                ->live()
                ->default('multiple_choice'),
            Forms\Components\TextInput::make('order')
                ->label('Thứ tự')
                ->default(1)
                ->rules(['integer', 'min:1'])
                ->validationMessages([
                    'integer' => 'Thứ tự phải là số nguyên.',
                    'min' => 'Thứ tự phải lớn hơn hoặc bằng 1.',
                ]),
            Forms\Components\TextInput::make('title')
                ->label('Tiêu đề (không bắt buộc)')
                ->maxLength(255),
            Forms\Components\Textarea::make('question')
                ->label('Nội dung câu hỏi')
                ->required()
                ->extraInputAttributes(['required' => false])
                ->validationMessages(['required' => __('common.error.required')])
                ->columnSpanFull(),
            \App\Filament\Support\AdminUploads::image('image_url', 'Ảnh minh họa', 'admin/course-quizzes')
                ->columnSpanFull(),
            Forms\Components\Repeater::make('options')
                ->label('Đáp án trắc nghiệm')
                ->schema([
                    Forms\Components\TextInput::make('value')
                        ->label('Mã')
                        ->required()
                        ->extraInputAttributes(['required' => false])
                        ->validationMessages(['required' => __('common.error.required')])
                        ->rules(['integer', 'min:0'])
                        ->validationMessages([
                            'integer' => 'Mã phải là số nguyên.',
                            'min' => 'Mã không được nhỏ hơn 0.',
                        ]),
                    Forms\Components\TextInput::make('label')
                        ->label('Nội dung đáp án')
                        ->required()
                        ->extraInputAttributes(['required' => false])
                        ->validationMessages(['required' => __('common.error.required')]),
                ])
                ->defaultItems(4)
                ->columns(2)
                ->visible(fn (Forms\Get $get): bool => $get('type') === 'multiple_choice')
                ->columnSpanFull(),
            Forms\Components\TextInput::make('correct_option')
                ->label('Mã đáp án đúng (ví dụ: 0 cho đáp án 1, 1 cho đáp án 2)')
                ->rules(['integer', 'min:0'])
                ->validationMessages([
                    'integer' => 'Mã đáp án đúng phải là số nguyên.',
                    'min' => 'Mã đáp án đúng không được nhỏ hơn 0.',
                ])
                ->visible(fn (Forms\Get $get): bool => $get('type') === 'multiple_choice'),
            Forms\Components\TextInput::make('placeholder')
                ->label('Gợi ý nhập câu tự luận')
                ->visible(fn (Forms\Get $get): bool => $get('type') === 'essay')
                ->columnSpanFull(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCourseQuizzes::route('/'),
            'edit' => Pages\EditCourseQuiz::route('/{record}/edit'),
        ];
    }
}
