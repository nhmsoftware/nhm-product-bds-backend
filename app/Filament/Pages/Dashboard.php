<?php

namespace App\Filament\Pages;

use App\Modules\Area\Models\Area;
use App\Modules\Auth\Models\Enums\UserRole;
use Filament\Facades\Filament;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    public function getHeading(): string
    {
        return '';
    }

    public function filtersForm(Form $form): Form
    {
        return $form->schema([
            Section::make('Bộ lọc dashboard')
                ->schema([
                    Select::make('month')
                        ->label('Tháng')
                        ->options(collect(range(1, 12))->mapWithKeys(fn (int $month): array => [$month => 'Tháng ' . $month])->all())
                        ->native(false)
                        ->placeholder('Tất cả'),
                    Select::make('quarter')
                        ->label('Quý')
                        ->options([
                            1 => 'Quý 1',
                            2 => 'Quý 2',
                            3 => 'Quý 3',
                            4 => 'Quý 4',
                        ])
                        ->native(false)
                        ->placeholder('Tất cả'),
                    Select::make('year')
                        ->label('Năm')
                        ->options($this->yearOptions())
                        ->native(false)
                        ->placeholder('Tất cả'),
                    Select::make('area')
                        ->label('Khu vực')
                        ->options(fn (): array => $this->areaOptions())
                        ->default(fn (): ?string => $this->directorArea())
                        ->disabled(fn (): bool => $this->isDirector())
                        ->searchable()
                        ->native(false)
                        ->placeholder('Toàn công ty'),
                ])
                ->columns(4),
        ]);
    }

    private function areaOptions(): array
    {
        if ($this->isDirector()) {
            $area = $this->directorArea();

            return filled($area) ? [$area => $area] : [];
        }

        return Area::query()->orderBy('name')->pluck('name', 'name')->all();
    }

    private function isDirector(): bool
    {
        return Filament::auth()->user()?->role === UserRole::DIRECTOR;
    }

    private function directorArea(): ?string
    {
        $area = Filament::auth()->user()?->area;

        return filled($area) ? (string) $area : null;
    }

    private function yearOptions(): array
    {
        $currentYear = (int) now()->year;

        return collect(range($currentYear - 3, $currentYear + 1))
            ->reverse()
            ->mapWithKeys(fn (int $year): array => [$year => (string) $year])
            ->all();
    }
}
