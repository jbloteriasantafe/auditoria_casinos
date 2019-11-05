<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Mesas\SectorMesas;
use App\Observers\Mesas\SectorMesasObserver;

class SectorMesasModelServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        SectorMesas::observe(SectorMesasObserver::class);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
