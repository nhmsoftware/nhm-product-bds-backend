<?php
namespace App\Filament\Resources;

use App\Filament\Resources\CourseResource\Pages;
use App\Filament\Resources\CourseResource\RelationManagers\EnrollmentsRelationManager;
use App\Filament\Resources\CourseResource\RelationManagers\LessonsRelationManager;
use App\Modules\Learning\Models\Course;
use App\Modules\Auth\Models\Enums\UserRole;
use App\Filament\Support\AdminImageColumn;
use App\Filament\Support\AdminUploads;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CourseResource extends Resource
{
    protected static ?string $model = Course::class;
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationGroup = 'Đào tạo';
    protected static ?string $navigationLabel = 'Quản lý khóa học';
    protected static ?string $modelLabel = 'Khóa học';
    protected static ?string $pluralModelLabel = 'Khóa học';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Thông tin khóa học')->schema([
                Forms\Components\TextInput::make('title')->label('Tên khóa học')->required()->maxLength(255)->unique(ignoreRecord: true, modifyRuleUsing: fn ($rule) => $rule->whereNull('deleted_at'))->extraInputAttributes(['required' => false])->validationMessages(['required' => __('common.error.required'), 'unique' => 'Tên khóa học đã tồn tại']),
                AdminUploads::image('thumbnail', 'Ảnh khóa học', 'admin/courses')->columnSpanFull(),
                Forms\Components\RichEditor::make('description')->label('Mô tả')->columnSpanFull(),
                Forms\Components\Toggle::make('is_required')->label('Khóa học bắt buộc')->helperText('Có thể có nhiều khóa học bắt buộc trong hệ thống.')->default(false),
                Forms\Components\CheckboxList::make('allowed_roles')
                    ->label('Vai trò được phép làm khóa học')
                    ->options([
                        UserRole::EMPLOYEE->value => 'Nhân viên',
                        UserRole::MANAGER->value => 'Trưởng phòng',
                        UserRole::DIRECTOR->value => 'Giám đốc',
                        UserRole::CEO->value => 'Tổng giám đốc',
                    ])
                    ->columns(2)
                    ->helperText('Nếu không chọn vai trò nào -> tất cả mọi người đều làm được')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('order')
                    ->label('Thứ tự hiển thị')
                    ->default(1)
                    ->rules(['integer', 'min:1'])
                    ->validationMessages([
                        'integer' => 'Thứ tự hiển thị phải là số nguyên.',
                        'min' => 'Thứ tự hiển thị phải lớn hơn hoặc bằng 1.',
                    ]),
                Forms\Components\Toggle::make('is_active')->label('Mở khóa học')->default(true),
                Forms\Components\Toggle::make('has_certificate')->label('Cấp chứng chỉ khi hoàn thành')->default(true),
            ])->columns(2),
            Forms\Components\Section::make('Luồng học trên mobile')->schema([
                Forms\Components\Placeholder::make('learning_rule')->label('Quy tắc học')->content('Mobile đang dùng luồng học tuần tự: xem đủ video bài trước mới mở bài tiếp theo, quiz mở sau khi hoàn thành toàn bộ bài học.'),
                Forms\Components\Placeholder::make('lesson_hint')->label('Bài học/Tài liệu/Quiz')->content('Sau khi lưu khóa học, mở tab Bài học trong trang chỉnh sửa để thêm video, tài liệu đính kèm và câu hỏi quiz.'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            AdminImageColumn::make('thumbnail')->label('Ảnh')->square(),
            Tables\Columns\TextColumn::make('title')->label('Khóa học')->searchable()->sortable()->limit(45),
            Tables\Columns\IconColumn::make('is_required')->label('Bắt buộc')->boolean()->alignCenter(),
            Tables\Columns\TextColumn::make('allowed_roles')
                ->label('Vai trò được học')
                ->formatStateUsing(fn ($state): string => collect($state ?: [])->map(fn ($role) => UserRole::tryFrom((int) $role)?->label())->filter()->implode(', ') ?: 'Tất cả')
                ->toggleable(),
            Tables\Columns\IconColumn::make('is_active')->label('Mở')->boolean()->alignCenter(),
            Tables\Columns\IconColumn::make('has_certificate')->label('Chứng chỉ')->boolean()->alignCenter(),
            Tables\Columns\TextColumn::make('lessons_count')->label('Bài học')->counts('lessons')->alignCenter(),
            Tables\Columns\TextColumn::make('enrollments_count')->label('Nhân viên đã học')->counts('enrollments')->alignCenter(),
        ])->filters([
            Tables\Filters\TernaryFilter::make('is_required')->label('Bắt buộc'),
            Tables\Filters\TernaryFilter::make('is_active')->label('Đang mở'),
        ])->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            LessonsRelationManager::class,
            EnrollmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListCourses::route('/'), 'create' => Pages\CreateCourse::route('/create'), 'edit' => Pages\EditCourse::route('/{record}/edit')];
    }
}
