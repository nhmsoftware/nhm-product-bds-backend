<?php
namespace App\Filament\Resources; use App\Filament\Resources\CustomerMeetingResource\Pages; use App\Modules\CustomerMeeting\Models\CustomerMeeting; use App\Filament\Support\AdminUploads; use App\Modules\Auth\Models\Role; use Filament\Forms; use Filament\Forms\Form; use Filament\Resources\Resource; use Filament\Tables; use Filament\Tables\Table;
class CustomerMeetingResource extends Resource {
    protected static ?string $model = CustomerMeeting::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'Hoạt động bán hàng';
    protected static ?string $modelLabel = 'Gặp khách';
    protected static ?string $pluralModelLabel = 'Quản lý gặp khách';
    protected static ?string $navigationLabel = 'Quản lý gặp khách';

    public static function form(Form $form): Form { return $form->schema([Forms\Components\Select::make('user_id')->label('Nhân viên')->relationship('user','name', function (\Illuminate\Database\Eloquent\Builder $query) { $currentUser = auth()->user(); if (!$currentUser) return $query; $query->where('id', '!=', $currentUser->id)->where('role_id', '!=', Role::where('name', 'buyer')->value('id'))->where('role_id', '!=', Role::where('name', 'super_admin')->value('id'))->whereNotNull('job_position_id'); if (!$currentUser->hasAnyPermission(['manage_all'])) { $query->whereHas('role', fn($q) => $q->where('level', '>=', $currentUser->role?->level ?? 999)); } if ($currentUser->role?->name === 'gdkd' && $currentUser->branch_id) { $query->where('branch_id', $currentUser->branch_id); } if ($currentUser->role?->name === 'tp_kd' && $currentUser->department_id) { $query->where('department_id', $currentUser->department_id); } return $query; })->searchable()->preload()->required(), Forms\Components\Select::make('project_id')->label('Khu đất')->relationship('project','name')->searchable()->preload()->required(), Forms\Components\TextInput::make('customer_name')->label('Tên khách')->required(), Forms\Components\TextInput::make('customer_phone')->label('SĐT khách')->required(), AdminUploads::image('image_path', 'Minh chứng', 'admin/customer-meetings')->columnSpanFull()])->columns(2); } public static function table(Table $table): Table { return $table->columns([Tables\Columns\TextColumn::make('user.name')->label('Nhân viên'), Tables\Columns\TextColumn::make('project.name')->label('Khu đất'), Tables\Columns\TextColumn::make('customer_name')->label('Khách'), Tables\Columns\TextColumn::make('created_at')->label('Thời gian')->dateTime('d/m/Y H:i')])->actions([Tables\Actions\EditAction::make(),Tables\Actions\DeleteAction::make()])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]); } public static function getPages(): array { return ['index'=>Pages\ListCustomerMeetings::route('/'),'create'=>Pages\CreateCustomerMeeting::route('/create'),'edit'=>Pages\EditCustomerMeeting::route('/{record}/edit')]; } }
