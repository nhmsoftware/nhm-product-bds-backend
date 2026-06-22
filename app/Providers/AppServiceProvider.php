<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (str_starts_with((string) config('app.url'), 'https://')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        \Illuminate\Database\Eloquent\Factories\Factory::guessFactoryNamesUsing(function (string $modelName) {
            return 'Database\\Factories\\' . class_basename($modelName) . 'Factory';
        });

        \Filament\Forms\Components\Field::configureUsing(function (\Filament\Forms\Components\Field $field): void {
            $traits = class_uses_recursive($field);
            if (in_array(\Filament\Forms\Components\Concerns\HasExtraInputAttributes::class, $traits)) {
                $field->extraInputAttributes(['required' => false]);
            }

            $field->validationMessages([
                'required' => __('common.error.required'),
            ]);
        });
    }
}
