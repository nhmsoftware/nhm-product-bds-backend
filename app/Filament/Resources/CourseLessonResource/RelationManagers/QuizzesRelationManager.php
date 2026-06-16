<?php

namespace App\Filament\Resources\CourseLessonResource\RelationManagers;

use App\Filament\Resources\CourseQuizResource;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class QuizzesRelationManager extends RelationManager
{
    protected static string $relationship = 'quizzes';
    protected static ?string $title = 'Quiz và câu hỏi';
    protected static ?string $modelLabel = 'Câu hỏi';
    protected static ?string $pluralModelLabel = 'Câu hỏi';

    public function form(Form $form): Form
    {
        return $form->schema(CourseQuizResource::quizFormSchema())->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('question')
            ->columns([
                Tables\Columns\TextColumn::make('order')->label('Thứ tự')->sortable(),
                Tables\Columns\TextColumn::make('type')->label('Loại')->formatStateUsing(fn (?string $state) => $state === 'essay' ? 'Tự luận' : 'Trắc nghiệm')->badge(),
                Tables\Columns\TextColumn::make('title')->label('Tiêu đề')->limit(30),
                Tables\Columns\TextColumn::make('question')->label('Câu hỏi')->searchable()->limit(70),
                Tables\Columns\TextColumn::make('correct_option')->label('Đáp án đúng'),
            ])
            ->defaultSort('order')
            ->headerActions([Tables\Actions\CreateAction::make()->label('Thêm câu hỏi')])
            ->actions([
                Tables\Actions\EditAction::make()->label('Sửa câu hỏi'),
                Tables\Actions\DeleteAction::make()->label('Xóa câu hỏi'),
            ]);
    }
}
