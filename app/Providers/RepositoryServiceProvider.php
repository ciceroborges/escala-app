<?php

namespace App\Providers;

use App\Repositories\ActivityLogRepository;
use App\Repositories\Contracts\IActivityLogRepository;
use App\Repositories\Contracts\IMemberRepository;
use App\Repositories\Contracts\IMinistryRepository;
use App\Repositories\Contracts\IPositionRepository;
use App\Repositories\Contracts\IScheduleRepository;
use App\Repositories\Contracts\ISetlistRepository;
use App\Repositories\Contracts\ISongRepository;
use App\Repositories\Contracts\IUserRepository;
use App\Repositories\MemberRepository;
use App\Repositories\MinistryRepository;
use App\Repositories\PositionRepository;
use App\Repositories\ScheduleRepository;
use App\Repositories\SetlistRepository;
use App\Repositories\SongRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(IUserRepository::class, UserRepository::class);
        $this->app->bind(IActivityLogRepository::class, ActivityLogRepository::class);
        $this->app->bind(IMinistryRepository::class, MinistryRepository::class);
        $this->app->bind(IMemberRepository::class, MemberRepository::class);
        $this->app->bind(IPositionRepository::class, PositionRepository::class);
        $this->app->bind(IScheduleRepository::class, ScheduleRepository::class);
        $this->app->bind(ISongRepository::class, SongRepository::class);
        $this->app->bind(ISetlistRepository::class, SetlistRepository::class);
    }
}
