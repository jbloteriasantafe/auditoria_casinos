<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Mesas\JuegoMesa;
use App\Observers\Mesas\JuegoMesaObserver;

class JuegoMesaModelServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        JuegoMesa::observe(JuegoMesaObserver::class);
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
