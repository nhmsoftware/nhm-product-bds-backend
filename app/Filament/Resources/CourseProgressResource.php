<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CourseProgressResource\Pages;
use App\Modules\Auth\Models\User;
use App\Modules\Auth\Models\Enums\UserRole;
use App\Modules\Learning\Models\Course;
use App\Modules\Learning\Models\CourseEnrollment;
use App\Modules\Learning\Models\Enums\CourseEnrollmentStatus;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class CourseProgressResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationGroup = 'Đào tạo';
    protected static ?string $navigationLabel = 'Quản lý tiến độ khóa học';
    protected static ?string $modelLabel = 'Tiến độ khóa học bắt buộc';
    protected static ?string $pluralModelLabel = 'Quản lý tiến độ khóa học';

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('staff_code')
                    ->label('Mã nhân viên')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Họ tên')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Chi nhánh')
                    ->placeholder('-')
                    ->searchable(),
                Tables\Columns\TextColumn::make('departmentRel.name')
                    ->label('Phòng ban')
                    ->placeholder('-')
                    ->searchable(),
                Tables\Columns\TextColumn::make('role.label')
                    ->label('Vai trò')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('required_courses_progress')
                    ->label('Tiến độ khóa học bắt buộc')
                    ->alignCenter()
                    ->getStateUsing(function (User $record): string {
                        $role = $record->role?->name;
                        $requiredCourseIds = Course::query()
                            ->where('is_active', true)
                            ->where('is_required', true)
                            ->where(function ($query) use ($role) {
                                $query->whereNull('allowed_roles')
                                    ->orWhereJsonLength('allowed_roles', 0);

                                if ($role !== null) {
                                    $query->orWhereJsonContains('allowed_roles', $role);
                                }
                            })
                            ->pluck('id');
// ... [rest of the function is unmodified]

                        if ($requiredCourseIds->isEmpty()) {
                            return '0/0';
                        }

                        $completedCount = 0;
                        foreach ($requiredCourseIds as $courseId) {
                            $enrollment = CourseEnrollment::where('user_id', $record->id)
                                ->where('course_id', $courseId)
                                ->first();

                            if ($enrollment && in_array($enrollment->status, [
                                CourseEnrollmentStatus::PENDING_ONBOARDING,
                                CourseEnrollmentStatus::COMPLETED
                            ], true)) {
                                $lessonIds = \App\Modules\Learning\Models\CourseLesson::where('course_id', $courseId)->pluck('id');
                                if ($lessonIds->isNotEmpty()) {
                                    $essayQuizIds = \App\Modules\Learning\Models\CourseQuiz::whereIn('lesson_id', $lessonIds)
                                        ->where('type', 'essay')
                                        ->pluck('id');

                                    if ($essayQuizIds->isNotEmpty()) {
                                        $ungradedCount = \App\Modules\Learning\Models\QuizAttempt::where('user_id', $record->id)
                                            ->whereIn('quiz_id', $essayQuizIds)
                                            ->where('is_draft', false)
                                            ->whereNull('is_correct')
                                            ->count();

                                        if ($ungradedCount > 0) {
                                            continue;
                                        }
                                    }
                                }
                                $completedCount++;
                            }
                        }

                        return "{$completedCount} / " . $requiredCourseIds->count();
                    })
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('onboarding_status')
                    ->label('Trạng thái onboarding')
                    ->alignCenter()
                    ->getStateUsing(function (User $record): string {
                        $role = $record->role?->value;
                        $requiredCourseIds = Course::query()
                            ->where('is_active', true)
                            ->where('is_required', true)
                            ->where(function ($query) use ($role) {
                                $query->whereNull('allowed_roles')
                                    ->orWhereJsonLength('allowed_roles', 0);

                                if ($role !== null) {
                                    $query->orWhereJsonContains('allowed_roles', $role);
                                }
                            })
                            ->pluck('id');

                        if ($requiredCourseIds->isEmpty()) {
                            return 'Không yêu cầu';
                        }

                        $completedCount = 0;
                        $allEnrollments = [];
                        foreach ($requiredCourseIds as $courseId) {
                            $enrollment = CourseEnrollment::where('user_id', $record->id)
                                ->where('course_id', $courseId)
                                ->first();

                            if ($enrollment && in_array($enrollment->status, [
                                CourseEnrollmentStatus::PENDING_ONBOARDING,
                                CourseEnrollmentStatus::COMPLETED
                            ], true)) {
                                $lessonIds = \App\Modules\Learning\Models\CourseLesson::where('course_id', $courseId)->pluck('id');
                                if ($lessonIds->isNotEmpty()) {
                                    $essayQuizIds = \App\Modules\Learning\Models\CourseQuiz::whereIn('lesson_id', $lessonIds)
                                        ->where('type', 'essay')
                                        ->pluck('id');

                                    if ($essayQuizIds->isNotEmpty()) {
                                        $ungradedCount = \App\Modules\Learning\Models\QuizAttempt::where('user_id', $record->id)
                                            ->whereIn('quiz_id', $essayQuizIds)
                                            ->where('is_draft', false)
                                            ->whereNull('is_correct')
                                            ->count();

                                        if ($ungradedCount > 0) {
                                            continue;
                                        }
                                    }
                                }
                                $completedCount++;
                                $allEnrollments[] = $enrollment;
                            }
                        }

                        $totalCount = $requiredCourseIds->count();

                        if ($completedCount < $totalCount) {
                            return 'Đang học';
                        }

                        $allFullyCompleted = collect($allEnrollments)->every(fn ($e) => $e->status === CourseEnrollmentStatus::COMPLETED);

                        return $allFullyCompleted ? 'Đã duyệt' : 'Chờ duyệt';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Không yêu cầu' => 'gray',
                        'Đang học' => 'warning',
                        'Chờ duyệt' => 'danger',
                        'Đã duyệt' => 'success',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role_id')
                    ->label('Vai trò')
                    ->relationship('role', 'label'),
                Tables\Filters\SelectFilter::make('branch_id')
                    ->label('Chi nhánh')
                    ->relationship('branch', 'name'),
            ])
            ->actionsPosition(ActionsPosition::BeforeColumns)
            ->actionsAlignment('left')
            ->actions([
                Tables\Actions\Action::make('confirmOnboarding')
                    ->label('Duyệt onboarding')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(function (User $record): bool {
                        $role = $record->role?->name;
                        $requiredCourseIds = Course::query()
                            ->where('is_active', true)
                            ->where('is_required', true)
                            ->where(function ($query) use ($role) {
                                $query->whereNull('allowed_roles')
                                    ->orWhereJsonLength('allowed_roles', 0);

                                if ($role !== null) {
                                    $query->orWhereJsonContains('allowed_roles', $role);
                                }
                            })
                            ->pluck('id');

                        if ($requiredCourseIds->isEmpty()) {
                            return false;
                        }

                        $completedCount = 0;
                        $allEnrollments = [];
                        foreach ($requiredCourseIds as $courseId) {
                            $enrollment = CourseEnrollment::where('user_id', $record->id)
                                ->where('course_id', $courseId)
                                ->first();

                            if ($enrollment && in_array($enrollment->status, [
                                CourseEnrollmentStatus::PENDING_ONBOARDING,
                                CourseEnrollmentStatus::COMPLETED
                            ], true)) {
                                $lessonIds = \App\Modules\Learning\Models\CourseLesson::where('course_id', $courseId)->pluck('id');
                                if ($lessonIds->isNotEmpty()) {
                                    $essayQuizIds = \App\Modules\Learning\Models\CourseQuiz::whereIn('lesson_id', $lessonIds)
                                        ->where('type', 'essay')
                                        ->pluck('id');

                                    if ($essayQuizIds->isNotEmpty()) {
                                        $ungradedCount = \App\Modules\Learning\Models\QuizAttempt::where('user_id', $record->id)
                                            ->whereIn('quiz_id', $essayQuizIds)
                                            ->where('is_draft', false)
                                            ->whereNull('is_correct')
                                            ->count();

                                        if ($ungradedCount > 0) {
                                            continue;
                                        }
                                    }
                                }
                                $completedCount++;
                                $allEnrollments[] = $enrollment;
                            }
                        }

                        $totalCount = $requiredCourseIds->count();

                        if ($completedCount < $totalCount) {
                            return false;
                        }

                        $allFullyCompleted = collect($allEnrollments)->every(fn ($e) => $e->status === CourseEnrollmentStatus::COMPLETED);

                        return !$allFullyCompleted;
                    })
                    ->requiresConfirmation()
                    ->action(function (User $record): void {
                        $role = $record->role?->value;
                        $requiredCourseIds = Course::query()
                            ->where('is_active', true)
                            ->where('is_required', true)
                            ->where(function ($query) use ($role) {
                                $query->whereNull('allowed_roles')
                                    ->orWhereJsonLength('allowed_roles', 0);

                                if ($role !== null) {
                                    $query->orWhereJsonContains('allowed_roles', $role);
                                }
                            })
                            ->pluck('id');

                        $learningService = app(\App\Modules\Learning\Interfaces\LearningServiceInterface::class);
                        $adminId = (string) auth()->user()?->getAuthIdentifier();

                        $errors = [];
                        foreach ($requiredCourseIds as $courseId) {
                            $result = $learningService->adminConfirmOnboarding(
                                (string) $courseId,
                                (string) $record->id,
                                $adminId
                            );

                            if ($result->isError()) {
                                $errors[] = $result->getMessage();
                            }
                        }

                        if (!empty($errors)) {
                            Notification::make()
                                ->title('Có lỗi khi duyệt onboarding')
                                ->body(implode("\n", array_unique($errors)))
                                ->danger()
                                ->send();
                        } else {
                            Notification::make()
                                ->title("Duyệt hoàn thành onboarding cho \"{$record->name}\" thành công.")
                                ->success()
                                ->send();
                        }
                    }),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->whereHas('role', fn ($q) => $q->whereNotIn('name', ['super_admin', 'buyer']))
            ->whereNotNull('job_position_id')
            ->where('is_active', true);

        $currentUser = auth()->user();
        if ($currentUser) {
            if ($currentUser->role?->name === 'gdkd' && $currentUser->branch_id) {
                $query->where('branch_id', $currentUser->branch_id);
            } elseif ($currentUser->role?->name === 'tp_kd' && $currentUser->department_id) {
                $query->where('department_id', $currentUser->department_id);
            }
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCourseProgresses::route('/'),
        ];
    }
}
