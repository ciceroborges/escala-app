<?php

namespace App\Providers;

use App\Enums\Permission;
use App\Models\Member;
use App\Services\ActivityLogService;
use App\Services\AuthService;
use App\Services\Contracts\IActivityLogService;
use App\Services\Contracts\IAuthService;
use App\Services\Contracts\IMemberService;
use App\Services\Contracts\IMessageService;
use App\Services\Contracts\IMinistryService;
use App\Services\Contracts\IOnboardingService;
use App\Services\Contracts\IPositionService;
use App\Services\Contracts\IScheduleGenerator;
use App\Services\Contracts\IScheduleService;
use App\Services\Contracts\ISetlistService;
use App\Services\Contracts\ISongService;
use App\Services\MemberService;
use App\Services\MessageService;
use App\Services\MinistryService;
use App\Services\OnboardingService;
use App\Services\PositionService;
use App\Services\ScheduleService;
use App\Services\Scheduling\ScheduleGenerator;
use App\Services\SetlistService;
use App\Services\SongService;
use App\Support\CurrentMinistry;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(CurrentMinistry::class);

        $this->app->bind(IActivityLogService::class, ActivityLogService::class);
        $this->app->bind(IAuthService::class, AuthService::class);
        $this->app->bind(IMinistryService::class, MinistryService::class);
        $this->app->bind(IMemberService::class, MemberService::class);
        $this->app->bind(IPositionService::class, PositionService::class);
        $this->app->bind(IScheduleGenerator::class, ScheduleGenerator::class);
        $this->app->bind(IScheduleService::class, ScheduleService::class);
        $this->app->bind(ISongService::class, SongService::class);
        $this->app->bind(ISetlistService::class, SetlistService::class);
        $this->app->bind(IMessageService::class, MessageService::class);
        $this->app->bind(IOnboardingService::class, OnboardingService::class);
    }

    public function boot(): void
    {
        $member = fn (): ?Member => app(CurrentMinistry::class)->member();

        Gate::define('admin', fn () => (bool) $member()?->isAdmin());
        Gate::define('manage-schedule', fn () => (bool) $member()?->hasPermission(Permission::ManageSchedule));
        Gate::define('manage-repertoire', fn () => (bool) $member()?->hasPermission(Permission::ManageRepertoire));
        Gate::define('view-full-schedule', fn () => $member() !== null && ! $member()->hasPermission(Permission::ViewOwnScheduleOnly));
    }
}
