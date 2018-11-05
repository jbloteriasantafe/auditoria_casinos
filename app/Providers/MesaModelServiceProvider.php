<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Mesas\Mesa;
use App\Observers\Mesas\MesaObserver;

class MesaModelServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        Mesa::observe(MesaObserver::class);
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
