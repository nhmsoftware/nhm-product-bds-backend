<?php


use App\Modules\Attendance\Providers\AttendanceServiceProvider;
use App\Modules\Auth\Providers\AuthServiceProvider;
use App\Modules\Consultation\Providers\ConsultationServiceProvider;
use App\Modules\CustomerMeeting\Providers\CustomerMeetingServiceProvider;
use App\Modules\Dashboard\Providers\DashboardServiceProvider;
use App\Modules\LegalVideo\Providers\LegalVideoServiceProvider;
use App\Modules\News\Providers\NewsServiceProvider;
use App\Modules\Planning\Providers\PlanningServiceProvider;
use App\Modules\Project\Providers\ProjectServiceProvider;
use App\Modules\SiteTour\Providers\SiteTourServiceProvider;
use App\Modules\ActivityEvidence\Providers\EvidenceServiceProvider;
use App\Modules\Leave\Providers\LeaveServiceProvider;
use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    AuthServiceProvider::class,
    ConsultationServiceProvider::class,
    DashboardServiceProvider::class,
    LegalVideoServiceProvider::class,
    NewsServiceProvider::class,
    PlanningServiceProvider::class,
    ProjectServiceProvider::class,
    AttendanceServiceProvider::class,
    CustomerMeetingServiceProvider::class,
    SiteTourServiceProvider::class,
    EvidenceServiceProvider::class,
    LeaveServiceProvider::class,
    \App\Modules\DepartmentTransfer\Providers\DepartmentTransferServiceProvider::class,
    \App\Modules\Learning\Providers\LearningServiceProvider::class,
    \App\Modules\Area\Providers\AreaServiceProvider::class,
    \App\Modules\EmployeeReferral\Providers\EmployeeReferralServiceProvider::class,
];
