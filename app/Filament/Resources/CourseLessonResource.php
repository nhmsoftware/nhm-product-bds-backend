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
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class CourseLessonResource extends Resource
{
    protected static ?string $model = CourseLesson::class;
    protected static ?string $navigationIcon = 'heroicon-o-play-circle';
    protected static ?string $navigationGroup = 'Đào tạo';
    protected static ?string $modelLabel = 'Bài học';
    protected static ?string $pluralModelLabel = 'Bài học';
    protected static ?string $navigationLabel = 'Quản lý bài học';

    public static function form(Form $form): Form
    {
        return $form->schema(self::lessonFormSchema())->columns(2);
    }

    public static function lessonFormSchema(): array
    {
        return [
            Forms\Components\Select::make('course_id')
                ->label('Khóa học')
                ->relationship('course', 'title')
                ->searchable()
                ->preload()
                ->required()
                ->extraInputAttributes(['required' => false])
                ->validationMessages(['required' => __('common.error.required')]),
            Forms\Components\TextInput::make('title')
                ->label('Tên bài học')
                ->required()
                ->maxLength(255)
                ->extraInputAttributes(['required' => false])
                ->unique(
                    table: 'course_lessons',
                    column: 'title',
                    ignoreRecord: true,
                    modifyRuleUsing: function (\Illuminate\Validation\Rules\Unique $rule, Forms\Get $get, $record, $livewire) {
                        $courseId = $get('course_id');
                        if (!$courseId && $livewire && method_exists($livewire, 'getOwnerRecord')) {
                            $courseId = $livewire->getOwnerRecord()?->id;
                        }
                        return $rule->where('course_id', $courseId);
                    }
                )
                ->validationMessages([
                    'required' => __('common.error.required'),
                    'unique' => 'Tên bài học đã tồn tại trong khóa học này.',
                ]),
            Forms\Components\RichEditor::make('content')->label('Mô tả/Nội dung bài học')->columnSpanFull(),
            AdminUploads::video('video_url', 'Video đào tạo', 'admin/lessons/videos')->columnSpanFull(),
            Forms\Components\Hidden::make('duration_seconds')->default(0),
            Forms\Components\TextInput::make('order')
                ->label('Thứ tự')
                ->default(1)
                ->rules(['integer', 'min:1'])
                ->validationMessages([
                    'integer' => 'Thứ tự phải là số nguyên.',
                    'min' => 'Thứ tự phải lớn hơn hoặc bằng 1.',
                ]),
            Forms\Components\Toggle::make('is_active')->label('Mở khóa bài học')->default(true),
            Forms\Components\Repeater::make('attachments')
                ->label('Tài liệu đính kèm')
                ->schema([
                    Forms\Components\Select::make('type')
                        ->label('Loại')
                        ->options(['pdf' => 'PDF', 'docx' => 'Word', 'image' => 'Ảnh', 'link' => 'Liên kết ngoài'])
                        ->required()
                        ->extraInputAttributes(['required' => false])
                        ->validationMessages(['required' => __('common.error.required')])
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
                        ->required()
                        ->extraInputAttributes(['required' => false])
                        ->validationMessages(['required' => __('common.error.required')]),

                    // url lưu giá trị cuối — được cập nhật bởi _file_upload (qua mutateFormData*)
                    // hoặc bởi _link_input (qua afterStateUpdated)
                    Forms\Components\Hidden::make('url'),

                    // FileUpload tên riêng — dehydrated:true để Filament tự move temp→final disk
                    Forms\Components\FileUpload::make('_file_upload')
                        ->label('Chọn tệp')
                        ->disk('public')
                        ->directory('learning/attachments')
                        ->visibility('public')
                        ->acceptedFileTypes(function (Forms\Get $get): array {
                            $type = $get('type');
                            if ($type === 'pdf') {
                                return ['application/pdf'];
                            }
                            if ($type === 'docx') {
                                return [
                                    'application/msword',
                                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                ];
                            }
                            if ($type === 'image') {
                                return ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                            }
                            return [];
                        })
                        ->rules([
                            fn (Forms\Get $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get) {
                                if (blank($value)) {
                                    return;
                                }
                                $type = $get('type');
                                $extension = null;
                                if ($value instanceof \Illuminate\Http\UploadedFile) {
                                    $extension = $value->getClientOriginalExtension();
                                } else {
                                    $extension = pathinfo((string) $value, PATHINFO_EXTENSION);
                                }

                                if (empty($extension)) {
                                    return;
                                }

                                $extension = strtolower($extension);

                                if ($type === 'pdf' && $extension !== 'pdf') {
                                    $fail('Định dạng tệp phải là PDF (.pdf).');
                                }
                                if ($type === 'docx' && !in_array($extension, ['doc', 'docx'])) {
                                    $fail('Định dạng tệp phải là Word (.doc, .docx).');
                                }
                                if ($type === 'image' && !in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                                    $fail('Định dạng tệp phải là hình ảnh (jpg, jpeg, png, gif, webp).');
                                }
                            },
                        ])
                        ->maxSize(50 * 1024)
                        ->downloadable()
                        ->openable()
                        ->afterStateHydrated(function (Forms\Components\FileUpload $component): void {
                            $livewire = $component->getContainer()->getLivewire();
                            // Đọc trực tiếp từ Livewire data — tránh dùng $state param (có thể stale từ record)
                            $currentState = data_get($livewire, $component->getStatePath());
                            if (is_array($currentState) && !empty(array_filter($currentState))) {
                                $component->state($currentState);
                                return;
                            }
                            $component->state([]);
                        })
                        ->helperText(function (Forms\Components\FileUpload $component): HtmlString {
                            $livewire = $component->getContainer()->getLivewire();
                            $itemPath = Str::beforeLast($component->getStatePath(), '._file_upload');
                            $storedUrl = data_get($livewire, $itemPath . '.url');
                            if (!is_string($storedUrl) || blank($storedUrl)) {
                                return new HtmlString('');
                            }
                            $fileType = (string) (data_get($livewire, $itemPath . '.type') ?? 'pdf');
                            $absUrl = str_starts_with($storedUrl, 'http')
                                ? $storedUrl
                                : request()->getSchemeAndHttpHost() . '/' . ltrim($storedUrl, '/');
                            $filename = e(basename($storedUrl));
                            $icons = [
                                'pdf'   => ['label' => 'PDF', 'color' => '#ef4444', 'bg' => '#fef2f2'],
                                'docx'  => ['label' => 'DOC', 'color' => '#3b82f6', 'bg' => '#eff6ff'],
                                'image' => ['label' => 'IMG', 'color' => '#10b981', 'bg' => '#f0fdf4'],
                            ];
                            $icon = $icons[$fileType] ?? ['label' => 'FILE', 'color' => '#6b7280', 'bg' => '#f3f4f6'];
                            $btnBase = "display:inline-flex;align-items:center;gap:4px;padding:6px 10px;"
                                . "border-radius:6px;border:1px solid #e5e7eb;background:#fff;"
                                . "font-size:12px;font-weight:500;color:#374151;text-decoration:none;";
                            $btnHover = "onmouseover=\"this.style.background='#f3f4f6';this.style.borderColor='#d1d5db'\" "
                                . "onmouseout=\"this.style.background='#fff';this.style.borderColor='#e5e7eb'\"";
                            $dlIcon = "<svg width='14' height='14' fill='none' stroke='currentColor' viewBox='0 0 24 24'>"
                                . "<path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' "
                                . "d='M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4'/></svg>";

                            return new HtmlString(
                                "<div style='display:flex;align-items:center;gap:12px;padding:12px 16px;"
                                . "border:1px solid #e5e7eb;border-radius:8px;background:#fff;margin-top:8px'>"
                                . "<div style='width:40px;height:40px;border-radius:8px;"
                                . "background:{$icon['bg']};display:flex;align-items:center;"
                                . "justify-content:center;font-weight:700;font-size:11px;color:{$icon['color']};flex-shrink:0'>"
                                . $icon['label']
                                . "</div>"
                                . "<div style='flex:1;min-width:0'>"
                                . "<p style='margin:0;font-size:0.875em;font-weight:500;color:#111827;"
                                . "white-space:nowrap;overflow:hidden;text-overflow:ellipsis'>{$filename}</p>"
                                . "<p style='margin:2px 0 0;font-size:0.75em;color:#6b7280'>Tải tệp mới để thay thế</p>"
                                . "</div>"
                                . "<div style='display:flex;gap:6px;flex-shrink:0'>"
                                . "<a href='" . e($absUrl) . "' download title='Tải xuống' style='{$btnBase}' {$btnHover}>"
                                . $dlIcon . "Tải</a>"
                                . "</div>"
                                . "</div>"
                            );
                        })
                        ->visible(fn (Forms\Get $get) => in_array($get('type'), ['pdf', 'docx', 'image']))
                        ->columnSpanFull(),

                    // Link ngoài — cập nhật sibling Hidden('url') qua afterStateUpdated
                    Forms\Components\TextInput::make('_link_input')
                        ->label('URL liên kết')
                        ->required(fn (Forms\Get $get) => $get('type') === 'link')
                        ->extraInputAttributes(['required' => false])
                        ->validationMessages(['required' => __('common.error.required')])
                        ->url()
                        ->live()
                        ->afterStateHydrated(function (Forms\Components\TextInput $component): void {
                            $livewire = $component->getContainer()->getLivewire();
                            $itemPath = Str::beforeLast($component->getStatePath(), '._link_input');
                            $url = data_get($livewire, $itemPath . '.url');
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
            Tables\Columns\TextColumn::make('title')->label('Bài học')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('order')->label('Thứ tự')->sortable(),
            Tables\Columns\TextColumn::make('course.title')->label('Khóa học')->searchable()->limit(40),
            Tables\Columns\TextColumn::make('duration_seconds')->label('Giây')->sortable(),
            Tables\Columns\IconColumn::make('is_active')->label('Mở')->boolean(),
            Tables\Columns\TextColumn::make('quizzes_count')->label('Câu hỏi')->counts('quizzes'),
        ])->defaultSort('order')->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
