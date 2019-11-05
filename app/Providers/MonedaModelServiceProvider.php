<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Mesas\Moneda;
use App\Observers\Mesas\MonedaObserver;

class MonedaModelServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        Moneda::observe(MonedaObserver::class);
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
