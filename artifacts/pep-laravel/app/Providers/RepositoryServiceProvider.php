<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\Contracts\FoundItemRepositoryInterface;
use App\Repositories\Contracts\LostReportRepositoryInterface;
use App\Repositories\FoundItemRepository;
use App\Repositories\LostReportRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register repository bindings.
     */
    public function register(): void
    {
        $this->app->bind(
            FoundItemRepositoryInterface::class,
            FoundItemRepository::class,
        );

        $this->app->bind(
            LostReportRepositoryInterface::class,
            LostReportRepository::class,
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
