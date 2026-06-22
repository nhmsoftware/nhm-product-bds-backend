<?php

declare(strict_types=1);

namespace App\Filament\Resources\QuizAttemptResource\Pages;

use App\Filament\Resources\QuizAttemptResource;
use App\Modules\Auth\Models\User;
use App\Modules\Learning\Models\QuizAttempt;
use App\Modules\Learning\Services\LearningService;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class GradeAttempts extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = QuizAttemptResource::class;
    protected static string $view = 'filament.resources.quiz-attempt-resource.pages.grade-attempts';

    public string $userId = '';
    public ?QuizAttempt $attemptRecord = null;
    public ?array $data = [];

    public function mount(string $record): void
    {
        $attempt = QuizAttempt::with(['quiz.lesson'])->find($record);
        if ($attempt) {
            $this->attemptRecord = $attempt;
            $this->userId = $attempt->user_id;
        } else {
            $this->userId = $record;
            $this->attemptRecord = QuizAttempt::with(['quiz.lesson'])
                ->where('user_id', $record)
                ->where('is_draft', false)
                ->first();
        }
        $this->form->fill([]);
    }

    public function getTitle(): string
    {
        /** @var User|null $user */
        $user = User::find($this->userId);
        return 'Chấm bài — ' . ($user?->name ?? '');
    }

    public function getBreadcrumbs(): array
    {
        return [
            QuizAttemptResource::getUrl() => 'Chấm bài quiz',
            $this->getTitle(),
        ];
    }

    public function form(Forms\Form $form): Forms\Form
    {
        $courseId = $this->attemptRecord?->quiz?->lesson?->course_id;

        // Load tất cả attempts (MC + essay) của user trong khóa học này, nhóm theo bài học
        $query = QuizAttempt::query()
            ->join('course_quizzes as cq', 'cq.id', '=', 'quiz_attempts.quiz_id')
            ->join('course_lessons as cl', 'cl.id', '=', 'cq.lesson_id')
            ->where('quiz_attempts.user_id', $this->userId)
            ->where('quiz_attempts.is_draft', false);

        if ($courseId) {
            $query->where('cl.course_id', $courseId);
        }

        $allAttempts = $query->select(
                'quiz_attempts.*',
                'cq.question',
                'cq.type as quiz_type',
                'cq.options',
                'cq.correct_option',
                'cq.order as quiz_order',
                'cl.title as lesson_title',
                'cl.order as lesson_order'
            )
            ->orderBy('cl.order')
            ->orderBy('cq.order')
            ->get();

        // Tính tổng điểm
        $mcCorrectCount = $allAttempts->where('quiz_type', 'multiple_choice')->where('is_correct', true)->count();
        $mcTotalCount = $allAttempts->where('quiz_type', 'multiple_choice')->count();

        $essayCorrectCount = $allAttempts->where('quiz_type', 'essay')->where('is_correct', true)->count();
        $essayTotalCount = $allAttempts->where('quiz_type', 'essay')->count();
        $essayPendingCount = $allAttempts->where('quiz_type', 'essay')->whereNull('is_correct')->count();

        $totalCorrect = $mcCorrectCount + $essayCorrectCount;
        $totalQuestions = $mcTotalCount + $essayTotalCount;

        $summaryText = "{$totalCorrect}/{$totalQuestions} câu đúng";
        if ($essayPendingCount > 0) {
            $summaryText .= " — <span style='color: #d97706; font-weight: 600;'>{$essayPendingCount} câu tự luận chờ chấm (điểm sẽ hiển thị sau khi chấm xong)</span>";
        } else {
            $finalScore = $totalQuestions > 0 ? round(($totalCorrect / $totalQuestions) * 10, 2) : 0;
            $scoreColor = $finalScore >= 8 ? '#16a34a' : ($finalScore >= 5 ? '#d97706' : '#dc2626');
            $summaryText .= " — <span style='color: {$scoreColor}; font-weight: 700; font-size: 1.05em;'>Điểm: {$finalScore}/10</span>"
                . " <span style='color: #16a34a; font-weight: 600;'>(Đã chấm xong)</span>";
        }

        $topPlaceholder = Forms\Components\Placeholder::make('summary')
            ->label('Tổng kết kết quả thi')
            ->content(new HtmlString("
                <div class='p-4 bg-gray-50 border border-gray-200 rounded-lg dark:bg-gray-800 dark:border-gray-700' style='font-size: 15px; line-height: 1.6;'>
                    <strong>Kết quả:</strong> {$summaryText}
                </div>
            "))
            ->columnSpanFull();

        $sections = [];
        $sections[] = $topPlaceholder;
        $questionNum = 0;

        foreach ($allAttempts as $attempt) {
            $questionNum++;
            $components = [];

            $typeLabel = $attempt->quiz_type === 'multiple_choice' ? 'Trắc nghiệm' : 'Tự luận';
            $lessonTitle = $attempt->lesson_title;

            if ($attempt->quiz_type === 'multiple_choice') {
                $components[] = $this->buildMcComponent($attempt);
            } else {
                foreach ($this->buildEssayComponents($attempt) as $component) {
                    $components[] = $component;
                }
            }

            $sections[] = Forms\Components\Section::make("Câu {$questionNum} ({$typeLabel}) — Bài học: {$lessonTitle}")
                ->schema($components)
                ->collapsible(false);
        }

        return $form->schema($sections)->statePath('data');
    }

    private function buildMcComponent(object $attempt): Forms\Components\Placeholder
    {
        $options = is_array($attempt->options)
            ? $attempt->options
            : (json_decode((string) ($attempt->options ?? '[]'), true) ?? []);

        $selectedIdx = $attempt->selected_option;
        $correctIdx = $attempt->correct_option;

        $rows = '';
        foreach ($options as $idx => $option) {
            $optLabel = is_array($option) ? ($option['label'] ?? '') : (string) $option;
            $optVal = is_array($option) ? ($option['value'] ?? $idx) : $idx;

            $isCorrect  = $correctIdx !== null && $correctIdx !== '' && (int) $optVal === (int) $correctIdx;
            $isSelected = $selectedIdx !== null && $selectedIdx !== '' && (int) $optVal === (int) $selectedIdx;

            if ($isCorrect) {
                $style = 'color:#15803d;font-weight:600;';
                $note  = ' <small style="opacity:.7">(đúng)</small>';
            } elseif ($isSelected) {
                $style = 'color:#dc2626;font-weight:600;';
                $note  = ' <small style="opacity:.7">(bạn chọn)</small>';
            } else {
                $style = 'color:#6b7280;';
                $note  = '';
            }

            $rows .= "<div style=\"{$style}padding:3px 0\">{$optLabel}{$note}</div>";
        }

        $badge = $attempt->is_correct
            ? '<span style="background:#dcfce7;color:#15803d;padding:2px 10px;border-radius:99px;font-size:12px;font-weight:600;">Đúng</span>'
            : '<span style="background:#fee2e2;color:#dc2626;padding:2px 10px;border-radius:99px;font-size:12px;font-weight:600;">Sai</span>';

        return Forms\Components\Placeholder::make("mc_{$attempt->id}")
            ->label('Câu hỏi: ' . $attempt->question)
            ->content(new HtmlString("<div style='line-height:1.7'>{$rows}</div><div style='margin-top:10px'>{$badge}</div>"))
            ->columnSpanFull();
    }

    /**
     * @return Forms\Components\Component[]
     */
    private function buildEssayComponents(object $attempt): array
    {
        $isPending = is_null($attempt->is_correct);

        $components = [
            Forms\Components\Placeholder::make("question_{$attempt->id}")
                ->label('Câu trả lời của học viên:')
                ->content($attempt->essay_answer ?? '—')
                ->columnSpanFull(),
        ];

        if ($isPending) {
            $components[] = Forms\Components\Radio::make("grade_{$attempt->id}")
                ->label('Kết quả chấm')
                ->options(['correct' => 'Đúng', 'incorrect' => 'Sai'])
                ->required()
                ->inline()
                ->columnSpanFull();
        } else {
            $badge = $attempt->is_correct
                ? '<span style="background:#dcfce7;color:#15803d;padding:2px 10px;border-radius:99px;font-size:12px;font-weight:600;">Đã chấm — Đúng</span>'
                : '<span style="background:#fee2e2;color:#dc2626;padding:2px 10px;border-radius:99px;font-size:12px;font-weight:600;">Đã chấm — Sai</span>';

            $components[] = Forms\Components\Placeholder::make("result_{$attempt->id}")
                ->label('Kết quả')
                ->content(new HtmlString($badge))
                ->columnSpanFull();
        }

        return $components;
    }

    public function save(): void
    {
        $state = $this->form->getState();
        $adminId = (string) Auth::id();
        $service = app(LearningService::class);

        foreach ($state as $key => $value) {
            if (!str_starts_with($key, 'grade_')) {
                continue;
            }
            $attemptId = substr($key, 6);
            $service->gradeEssayAttempt($attemptId, $value === 'correct', $adminId);
        }

        Notification::make()
            ->title('Đã lưu kết quả chấm bài')
            ->success()
            ->send();

        $this->redirect(QuizAttemptResource::getUrl('index'));
    }
}
