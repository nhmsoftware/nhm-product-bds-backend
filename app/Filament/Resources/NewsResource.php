<?php
namespace App\Filament\Resources;
use App\Filament\Resources\NewsResource\Pages; use App\Modules\News\Models\News; use App\Filament\Support\AdminOptions; use App\Filament\Support\AdminUploads; use Filament\Forms; use Filament\Forms\Form; use Filament\Resources\Resource; use Filament\Tables; use Filament\Tables\Table; use Illuminate\Support\Str;
class NewsResource extends Resource
{
    protected static ?string $model=News::class; protected static ?string $navigationIcon='heroicon-o-newspaper'; protected static ?string $navigationGroup='Nội dung'; protected static ?string $modelLabel='Tin tức'; protected static ?string $pluralModelLabel='Tin tức';
    public static function form(Form $form): Form { return $form->schema([
        Forms\Components\TextInput::make('title')->label('Tiêu đề')->required()->live(onBlur:true)->afterStateUpdated(fn($state, Forms\Set $set)=>$set('slug', Str::slug((string)$state))),
        Forms\Components\TextInput::make('slug')->label('Slug')->required(), Forms\Components\Select::make('author_id')->label('Tác giả')->relationship('author','name')->searchable()->preload()->required(),
        Forms\Components\Select::make('category')->label('Danh mục')->options(AdminOptions::newsCategories())->required()->searchable(), Forms\Components\Select::make('department')->label('Phòng ban')->options(AdminOptions::departments())->searchable(), Forms\Components\Select::make('area')->label('Khu vực')->options(AdminOptions::areas())->searchable(),
        Forms\Components\Toggle::make('is_published')->label('Đã xuất bản')->default(true), Forms\Components\Toggle::make('is_featured')->label('Nổi bật'), Forms\Components\TextInput::make('sort')->label('Thứ tự')->numeric()->default(0),
        Forms\Components\DateTimePicker::make('published_at')->label('Ngày xuất bản'), AdminUploads::image('thumbnail', 'Ảnh thumbnail', 'admin/news')->columnSpanFull(),
        Forms\Components\Textarea::make('summary')->label('Tóm tắt')->columnSpanFull(), Forms\Components\RichEditor::make('content')->label('Nội dung')->columnSpanFull(),
    ])->columns(2); }
    public static function table(Table $table): Table { return $table->columns([
        Tables\Columns\TextColumn::make('title')->label('Tiêu đề')->searchable()->sortable()->limit(50), Tables\Columns\TextColumn::make('category')->label('Danh mục')->badge(),
        Tables\Columns\TextColumn::make('author.name')->label('Tác giả'), Tables\Columns\IconColumn::make('is_published')->label('Xuất bản')->boolean(), Tables\Columns\IconColumn::make('is_featured')->label('Nổi bật')->boolean(), Tables\Columns\TextColumn::make('published_at')->label('Ngày đăng')->dateTime('d/m/Y H:i')->sortable(),
    ])->actions([Tables\Actions\EditAction::make(),Tables\Actions\DeleteAction::make()]); }
    public static function getPages(): array { return ['index'=>Pages\ListNews::route('/'),'create'=>Pages\CreateNews::route('/create'),'edit'=>Pages\EditNews::route('/{record}/edit')]; }
}
