<?php

namespace App\Filament\Resources\CourseResource\RelationManagers;

use App\Filament\Resources\CourseQuizResource;
use App\Filament\Resources\CourseLessonResource;
use Filament\Forms;
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
                Tables\Columns\TextColumn::make('duration_seconds')->label('Giây')->sortable(),
                Tables\Columns\IconColumn::make('is_active')->label('Mở')->boolean(),
                Tables\Columns\TextColumn::make('attachments')->label('Tài liệu')->formatStateUsing(fn ($state) => is_array($state) ? count($state) . ' file' : '0 file'),
                Tables\Columns\TextColumn::make('quizzes_count')->label('Câu hỏi')->counts('quizzes'),
            ])
            ->defaultSort('order')
            ->headerActions([Tables\Actions\CreateAction::make()->label('Thêm bài học')])
            ->actions([
                Tables\Actions\EditAction::make()->label('Sửa bài học'),
                Tables\Actions\DeleteAction::make()->label('Xóa bài học'),
            ]);
    }
}
