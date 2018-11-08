<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Mesas\TipoMesa;
use App\Observers\Mesas\TipoMesaObserver;

class TipoMesaModelServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        TipoMesa::observe(TipoMesaObserver::class);
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
