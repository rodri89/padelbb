<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);

        // Compartir sponsors con la plantilla del home (footer)
        View::composer('bahia_padel.home.plantilla', function ($view) {
            try {
                $view->with('sponsors', \App\Sponsor::where('active', 1)->orderBy('orden')->get());
            } catch (\Throwable $e) {
                $view->with('sponsors', collect());
            }
        });
    }
}
