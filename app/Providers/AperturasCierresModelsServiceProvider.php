<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Mesas\Apertura;
use App\Mesas\Cierre;
use App\Observers\Mesas\AperturaObserver;
use App\Observers\Mesas\CierreObserver;

class AperturasCierresModelsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        Apertura::observe(AperturaObserver::class);
        Cierre::observe(CierreObserver::class);
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
