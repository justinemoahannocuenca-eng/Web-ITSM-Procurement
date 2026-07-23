<?php

namespace Modules\BusinessIntelligence\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\BusinessIntelligence\Console\Commands\InstallBusinessIntelligenceSchema;

class BusinessIntelligenceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->commands([InstallBusinessIntelligenceSchema::class]);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'bi');

        Route::middleware('web')->prefix('bi')->group(__DIR__.'/../../routes/web.php');
    }
}
