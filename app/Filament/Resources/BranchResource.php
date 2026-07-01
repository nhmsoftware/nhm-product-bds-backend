<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BranchResource\Pages;
use App\Modules\Branch\Models\Branch;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class BranchResource extends Resource
{
    protected static ?string $model = Branch::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $navigationGroup = 'Nhân sự';
    protected static ?string $modelLabel = 'Chi nhánh';
    protected static ?string $pluralModelLabel = 'Quản lý chi nhánh';
    protected static ?string $navigationLabel = 'Quản lý chi nhánh';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Tên chi nhánh')
                ->required()
                ->unique(ignoreRecord: true)
                ->extraInputAttributes(['required' => false])
                ->validationMessages([
                    'required' => __('common.error.required'),
                    'unique'   => 'Tên chi nhánh đã tồn tại.',
                ])
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(fn (?string $state, Forms\Set $set) => $set('code', strtoupper(Str::slug((string) $state, '_')))),
            Forms\Components\TextInput::make('code')
                ->label('Mã chi nhánh')
                ->required()
                ->unique(ignoreRecord: true)
                ->extraInputAttributes(['required' => false])
                ->validationMessages([
                    'required' => __('common.error.required'),
                    'unique'   => 'Mã chi nhánh đã tồn tại.',
                ])
                ->maxLength(50),
            Forms\Components\Select::make('director_id')
                ->label('Giám đốc phụ trách')
                ->relationship('director', 'name', function (Builder $query) {
                    $query->whereHas('role', fn($q) => $q->where('name', 'gdkd'))
                        ->where('is_active', true);
                })
                ->searchable()
                ->preload()
                ->placeholder('Chưa gán')
                ->nullable()
                ->rules(function (?string $state, Forms\Get $get) {
                    return [
                        function (string $attribute, $value, \Closure $fail) use ($get) {
                            if (blank($value)) {
                                return;
                            }
                            // A3: check director already assigned to another branch
                            $branchId = $get('id');
                            $exists = Branch::where('director_id', $value)
                                ->when($branchId, fn ($q) => $q->where('id', '!=', $branchId))
                                ->exists();
                            if ($exists) {
                                $fail('Giám đốc đã được gán cho chi nhánh khác.');
                            }
                        },
                    ];
                })
                ->validationMessages([
                    'unique' => 'Giám đốc đã được gán cho chi nhánh khác.',
                ]),
            Forms\Components\TextInput::make('area')->label('Khu vực quản lý')->maxLength(255),
            Forms\Components\TextInput::make('sort')
                ->label('Thứ tự')
                ->default(1)
                ->rules(['integer', 'min:1'])
                ->validationMessages([
                    'integer' => 'Thứ tự phải là số nguyên.',
                    'min' => 'Thứ tự phải lớn hơn hoặc bằng 1.',
                ]),
            Forms\Components\Toggle::make('is_active')->label('Đang sử dụng')->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->label('Chi nhánh')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('code')->label('Mã')->searchable(),
            Tables\Columns\TextColumn::make('area')->label('Khu vực'),
            Tables\Columns\TextColumn::make('director.name')
                ->label('Giám đốc')
                ->placeholder('—'),
            Tables\Columns\IconColumn::make('is_active')->label('Đang dùng')->boolean(),
            Tables\Columns\TextColumn::make('sort')->label('Thứ tự')->sortable(),
        ])->defaultSort('sort')->actions([
                Tables\Actions\Action::make('toggleActive')
                    ->label(fn ($record) => $record->is_active ? 'Khóa' : 'Mở khóa')
                    ->icon(fn ($record) => $record->is_active ? 'heroicon-o-lock-closed' : 'heroicon-o-lock-open')
                    ->color(fn ($record) => $record->is_active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->modalHeading(fn ($record) => $record->is_active ? 'Khóa chi nhánh' : 'Mở khóa chi nhánh')
                    ->modalSubmitActionLabel(fn ($record) => $record->is_active ? 'Xác nhận khóa' : 'Xác nhận mở khóa')
                    ->action(function ($record): void {
                        $hasUsers = \App\Modules\Auth\Models\User::where('branch_id', $record->id)->exists();

                        if ($hasUsers) {
                            \Filament\Notifications\Notification::make()
                                ->title('Chi nhánh đang có người dùng hoạt động')
                                ->warning()
                                ->send();
                            return;
                        }

                        $record->update(['is_active' => !$record->is_active]);

                        \Filament\Notifications\Notification::make()
                            ->title($record->is_active ? 'Mở khóa chi nhánh thành công.' : 'Khóa chi nhánh thành công.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBranches::route('/'),
            'create' => Pages\CreateBranch::route('/create'),
            'edit' => Pages\EditBranch::route('/{record}/edit'),
        ];
    }
}
