<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\BranchRevenueChart;
use App\Filament\Widgets\BranchSummaryTable;
use App\Filament\Widgets\CompanyStatsOverview;
use App\Filament\Widgets\DepartmentPerformance;
use App\Filament\Widgets\TopDepartmentsByKpi;
use App\Filament\Widgets\TopEmployeesByKpi;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\RevenueReport;
use App\Filament\Pages\EmployeeReport;
use App\Filament\Pages\DepartmentReport;
use App\Filament\Pages\ManageSettings;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

use Filament\Navigation\NavigationGroup;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        app()->setLocale('vi');

        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('NHM BĐS Admin')
            ->brandLogo(asset('assets/images/icons/header.png'))
            ->brandLogoHeight('4rem')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->pages([
                Dashboard::class,
                RevenueReport::class,
                EmployeeReport::class,
                DepartmentReport::class,
                ManageSettings::class,
            ])
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('Kho hàng'),
                NavigationGroup::make()
                    ->label('Giao dịch'),
                NavigationGroup::make()
                    ->label('Nhân sự'),
                NavigationGroup::make()
                    ->label('Bình luận'),
                NavigationGroup::make()
                    ->label('Giới thiệu'),
                NavigationGroup::make()
                    ->label('Nội dung'),
                NavigationGroup::make()
                    ->label('Đào tạo'),
                NavigationGroup::make()
                    ->label('CRM/Tư vấn'),
                NavigationGroup::make()
                    ->label('Hệ thống'),
                NavigationGroup::make()
                    ->label('Cấu hình'),
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                BranchRevenueChart::class,
                BranchSummaryTable::class,
                CompanyStatsOverview::class,
                TopEmployeesByKpi::class,
                TopDepartmentsByKpi::class,
                DepartmentPerformance::class,
                // Widgets\AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
