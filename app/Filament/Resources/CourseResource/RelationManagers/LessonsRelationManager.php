<?php

namespace App\Filament\Resources\CourseResource\RelationManagers;

use App\Filament\Resources\CourseLessonResource;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class LessonsRelationManager extends RelationManager
{
    protected static string $relationship = 'lessons';
    protected static ?string $title = 'Bài học, video và tài liệu';
    protected static ?string $modelLabel = 'Bài học';
    protected static ?string $pluralModelLabel = 'Bài học';

    public function form(Form $form): Form
    {
        return $form->schema(CourseLessonResource::lessonFormSchema())->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('order')->label('Thứ tự')->sortable(),
                Tables\Columns\TextColumn::make('title')->label('Bài học')->searchable()->limit(45),
                Tables\Columns\TextColumn::make('duration_seconds')->label('Thời lượng')->sortable()
                    ->formatStateUsing(function (?int $state): string {
                        if (!$state || $state <= 0) return '—';
                        $days    = intdiv($state, 86400);
                        $hours   = intdiv($state % 86400, 3600);
                        $minutes = intdiv($state % 3600, 60);
                        $secs    = $state % 60;
                        $parts = array_filter([
                            $days    ? "{$days} ngày"   : '',
                            $hours   ? "{$hours} giờ"   : '',
                            $minutes ? "{$minutes} phút" : '',
                            $secs    ? "{$secs} giây"   : '',
                        ]);
                        return implode(' ', $parts) ?: '—';
                    }),
                Tables\Columns\IconColumn::make('is_active')->label('Mở')->boolean(),
                Tables\Columns\TextColumn::make('attachments')->label('Tài liệu')
                    ->getStateUsing(fn ($record) => is_array($record->attachments) ? count($record->attachments) : 0)
                    ->formatStateUsing(fn ($state) => $state . ' file'),
                Tables\Columns\TextColumn::make('quizzes_count')->label('Câu hỏi')->counts('quizzes'),
            ])
            ->defaultSort('order')
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Thêm bài học')
                    ->mutateFormDataUsing(fn (array $data) => self::resolveAttachmentUploads($data)),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Sửa bài học')
                    ->mutateFormDataUsing(fn (array $data) => self::resolveAttachmentUploads($data))
                    ->after(function ($record): void {
                        if (!blank($record->video_url) && $record->wasChanged('video_url')) {
                            \App\Jobs\ExtractVideoDurationJob::dispatch($record->id);
                        }
                    }),
                Tables\Actions\DeleteAction::make()->label('Xóa bài học'),
            ]);
    }

    private static function resolveAttachmentUploads(array $data): array
    {
        $data['attachments'] = collect($data['attachments'] ?? [])
            ->map(function (array $item): array {
                $fileState = $item['_file_upload'] ?? null;

                if ($fileState !== null) {
                    $path = is_array($fileState)
                        ? (array_values(array_filter($fileState))[0] ?? null)
                        : (is_string($fileState) && $fileState !== '' ? $fileState : null);

                    if ($path) {
                        $item['url'] = str_starts_with($path, '/storage/') || str_starts_with($path, 'http')
                            ? $path
                            : '/storage/' . ltrim($path, '/');
                    }
                }

                unset($item['_file_upload']);
                return $item;
            })
            ->values()
            ->toArray();

        return $data;
    }
}
