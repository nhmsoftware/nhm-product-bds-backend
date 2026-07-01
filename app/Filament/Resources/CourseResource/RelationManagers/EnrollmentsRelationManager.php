<?php

namespace App\Filament\Resources\CourseResource\RelationManagers;

use App\Modules\Auth\Models\Enums\UserRole;
use App\Modules\Auth\Models\User;
use App\Modules\Learning\Models\Course;
use App\Modules\Learning\Models\CourseEnrollment;
use App\Modules\Learning\Models\CourseLesson;
use App\Modules\Learning\Models\CourseQuiz;
use App\Modules\Learning\Models\QuizAttempt;
use App\Modules\Learning\Models\Enums\CourseEnrollmentStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;

class EnrollmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'enrollments';
    protected static ?string $title = 'Tiến độ học';
    protected static ?string $modelLabel = 'Lượt học';
    protected static ?string $pluralModelLabel = 'Lượt học';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('user_id')
                ->label('Nhân viên')
                ->relationship('user', 'name', function (\Illuminate\Database\Eloquent\Builder $query) {
                    $currentUser = auth()->user();
                    if (!$currentUser) return $query;
                    $query->where('id', '!=', $currentUser->id)
                          ->where('role_id', '!=', \App\Modules\Auth\Models\Role::where('name', 'buyer')->value('id'))
                          ->where('role_id', '!=', \App\Modules\Auth\Models\Role::where('name', 'super_admin')->value('id'))
                          ->whereNotNull('job_position_id');
                    if (!$currentUser->hasAnyPermission(['manage_all'])) {
                        $query->whereHas('role', fn($q) => $q->where('level', '>=', $currentUser->role?->level ?? 999));
                    }
                    if ($currentUser->role?->name === 'gdkd' && $currentUser->branch_id) {
                        $query->where('branch_id', $currentUser->branch_id);
                    }
                    if ($currentUser->role?->name === 'tp_kd' && $currentUser->department_id) {
                        $query->where('department_id', $currentUser->department_id);
                    }
                    return $query;
                })
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\Select::make('status')->label('Trạng thái học')->options($this->enumOptions(CourseEnrollmentStatus::class))->required(),
            Forms\Components\TextInput::make('progress_percent')->label('Tiến độ (%)')->numeric()->minValue(0)->maxValue(100)->default(0),
            Forms\Components\DateTimePicker::make('completed_at')->label('Hoàn thành lúc'),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        $isRequiredCourse = $this->getOwnerRecord()->is_required ?? false;

        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('Nhân viên')->searchable(),
                Tables\Columns\TextColumn::make('user.department')->label('Phòng ban'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Trạng thái')
                    ->formatStateUsing(fn ($state) => $state instanceof CourseEnrollmentStatus ? $state->label() : CourseEnrollmentStatus::tryFrom((int) $state)?->label())
                    ->badge()
                    ->color(function ($state): string {
                        $status = $state instanceof CourseEnrollmentStatus
                            ? $state
                            : CourseEnrollmentStatus::tryFrom((int) $state);
                        return match ($status) {
                            CourseEnrollmentStatus::NOT_STARTED        => 'gray',
                            CourseEnrollmentStatus::IN_PROGRESS        => 'warning',
                            CourseEnrollmentStatus::PENDING_GRADING    => 'info',
                            CourseEnrollmentStatus::PENDING_ONBOARDING => 'primary',
                            CourseEnrollmentStatus::COMPLETED          => 'success',
                            default                                    => 'gray',
                        };
                    }),
                Tables\Columns\TextColumn::make('progress_percent')->label('Tiến độ')->suffix('%')->sortable(),
                Tables\Columns\TextColumn::make('quiz_status')
                    ->label('Quiz')
                    ->getStateUsing(function (CourseEnrollment $record): string {
                        if ($record->quiz_status === 'in_progress') {
                            return 'Đang làm';
                        }
                        return match ($record->status) {
                            CourseEnrollmentStatus::PENDING_GRADING,
                            CourseEnrollmentStatus::PENDING_ONBOARDING,
                            CourseEnrollmentStatus::COMPLETED => 'Đã nộp',
                            default => 'Chưa làm',
                        };
                    })
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'Đang làm' => 'warning',
                        'Đã nộp'   => 'success',
                        default    => 'gray',
                    }),
                Tables\Columns\TextColumn::make('quiz_score')
                    ->label('Điểm')
                    ->getStateUsing(fn ($record) => $this->calculateQuizScore($record))
                    ->formatStateUsing(fn ($state) => $state !== null ? number_format($state, 2) . '/10' : '—')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state === null => 'gray',
                        $state >= 8.0  => 'success',
                        $state >= 5.0  => 'warning',
                        default        => 'danger',
                    }),
                Tables\Columns\TextColumn::make('completed_at')->label('Hoàn thành')->dateTime('d/m/Y H:i'),
            ])
            ->filters([Tables\Filters\SelectFilter::make('status')->label('Trạng thái')->options($this->enumOptions(CourseEnrollmentStatus::class))])
            ->headerActions([
                Tables\Actions\Action::make('assignRoles')
                    ->label('Gán vai trò học')
                    ->icon('heroicon-o-user-group')
                    ->color('warning')
                    ->fillForm(function (): array {
                        $fresh = \App\Modules\Learning\Models\Course::query()->findOrFail($this->getOwnerRecord()->id);
                        $roles = $fresh->allowed_roles;
                        $roles = is_string($roles) ? json_decode($roles, true) : $roles;
                        return ['allowed_roles' => is_array($roles) ? $roles : []];
                    })
                    ->form(function () {
                        $courseId = $this->getOwnerRecord()->id;
                        $enrollmentsByRole = $this->getEnrollmentsByRole($courseId);

                        $roleOptions = [
                            'employee' => 'Nhân viên',
                            'tp_kd' => 'Trưởng phòng',
                            'gdkd' => 'Giám đốc',
                            'ceo' => 'Tổng giám đốc',
                        ];

                        $helperLines = collect($roleOptions)
                            ->map(fn (string $label, string $value) => [
                                'label' => $label,
                                'count' => $enrollmentsByRole[$value] ?? 0,
                            ])
                            ->filter(fn (array $item) => $item['count'] > 0)
                            ->map(fn (array $item) => "  • {$item['label']}: {$item['count']} nhân viên đang học")
                            ->values()
                            ->implode("\n");

                        return [
                            Forms\Components\CheckboxList::make('allowed_roles')
                                ->label('Vai trò được phép làm khóa học')
                                ->options($roleOptions)
                                ->columns(2)
                                ->required()
                                ->validationMessages([
                                     'required' => 'Vui lòng chọn ít nhất một vai trò.',
                                ]),
                            Forms\Components\Placeholder::make('enrollment_info')
                                ->label('Tình trạng nhân viên theo vai trò')
                                ->content($helperLines !== '' ? $helperLines : 'Chưa có nhân viên nào được gán.'),
                        ];
                    })
                    ->action(function (array $data): void {
                        $courseId = $this->getOwnerRecord()->id;

                        $newRoles = collect($data['allowed_roles'] ?? [])
                            ->map(fn ($role) => (string) $role)
                            ->filter(fn ($role) => in_array($role, ['employee', 'tp_kd', 'gdkd', 'ceo'], true))
                            ->unique()
                            ->values()
                            ->all();

                        $course = Course::query()->findOrFail($courseId);
                        $oldRoles = $course->allowed_roles;
                        $oldRoles = is_string($oldRoles) ? json_decode($oldRoles, true) : $oldRoles;
                        $oldRoles = is_array($oldRoles) ? $oldRoles : [];

                        $course->update([
                            'allowed_roles' => $newRoles,
                        ]);

                        $removedRoles = array_values(array_diff($oldRoles, $newRoles));
                        if (!empty($removedRoles)) {
                            $affectedEnrollments = CourseEnrollment::query()
                                ->where('course_id', $courseId)
                                ->whereHas('user.role', fn ($q) => $q->whereIn('name', $removedRoles))
                                ->whereNot('status', CourseEnrollmentStatus::COMPLETED)
                                ->get();

                            if ($affectedEnrollments->isNotEmpty()) {
                                $removedLabels = \App\Modules\Auth\Models\Role::whereIn('name', $removedRoles)
                                    ->pluck('label')
                                    ->implode(', ');

                                Notification::make()
                                    ->title('Cảnh báo: Có nhân viên đang học bị ảnh hưởng')
                                    ->body("Các vai trò [{$removedLabels}] đã bị bỏ chọn. "
                                        . $affectedEnrollments->count()
                                        . ' lượt học của nhân viên trong các vai trò này sẽ bị xóa.')
                                    ->warning()
                                    ->send();

                                $affectedEnrollments->each->delete();
                            }
                        }

                        $users = User::query()
                            ->whereHas('role', fn ($q) => $q->whereIn('name', $newRoles))
                            ->where('is_active', true)
                            ->whereNotNull('job_position_id')
                            ->get(['id']);

                        foreach ($users as $user) {
                            CourseEnrollment::firstOrCreate(
                                [
                                    'course_id' => $courseId,
                                    'user_id' => $user->id,
                                ],
                                [
                                    'status' => CourseEnrollmentStatus::NOT_STARTED,
                                    'progress_percent' => 0,
                                ]
                            );
                        }

                        Notification::make()
                            ->title('Đã gán vai trò học')
                            ->body('Đã cập nhật vai trò được phép học và tạo lượt học cho '
                                . $users->count() . ' nhân sự phù hợp.')
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Cập nhật tiến độ'),
                Tables\Actions\DeleteAction::make()->label('Xóa lượt học'),
            ]);
    }

    private function countUngradedEssays(CourseEnrollment $record): int
    {
        $lessonIds = CourseLesson::query()
            ->where('course_id', $record->course_id)
            ->pluck('id');

        if ($lessonIds->isEmpty()) {
            return 0;
        }

        $essayQuizIds = CourseQuiz::query()
            ->whereIn('lesson_id', $lessonIds)
            ->where('type', 'essay')
            ->pluck('id');

        if ($essayQuizIds->isEmpty()) {
            return 0;
        }

        return QuizAttempt::query()
            ->where('user_id', $record->user_id)
            ->whereIn('quiz_id', $essayQuizIds)
            ->where('is_draft', false)
            ->whereNull('is_correct')
            ->count();
    }

    private function calculateQuizScore(CourseEnrollment $record): ?float
    {
        $lessonIds = CourseLesson::query()
            ->where('course_id', $record->course_id)
            ->pluck('id');

        if ($lessonIds->isEmpty()) {
            return null;
        }

        $quizIds = CourseQuiz::query()
            ->whereIn('lesson_id', $lessonIds)
            ->pluck('id');

        if ($quizIds->isEmpty()) {
            return null;
        }

        $gradedAttempts = QuizAttempt::query()
            ->where('user_id', $record->user_id)
            ->whereIn('quiz_id', $quizIds)
            ->where('is_draft', false)
            ->whereNotNull('is_correct')
            ->get(['is_correct']);

        if ($gradedAttempts->isEmpty()) {
            return null;
        }

        $totalQuestions = $quizIds->count();
        $correctCount   = $gradedAttempts->where('is_correct', true)->count();

        return round(($correctCount / $totalQuestions) * 10, 2);
    }

    private function enumOptions(string $enum): array
    {
        return collect($enum::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])->all();
    }

    private function getEnrollmentsByRole(string $courseId): array
    {
        $results = CourseEnrollment::query()
            ->join('users', 'users.id', '=', 'course_enrollments.user_id')
            ->join('roles', 'roles.id', '=', 'users.role_id')
            ->where('course_enrollments.course_id', $courseId)
            ->groupBy('roles.name')
            ->pluck(\Illuminate\Support\Facades\DB::raw('COUNT(*)'), 'roles.name')
            ->all();

        return array_map('intval', $results);
    }
}
