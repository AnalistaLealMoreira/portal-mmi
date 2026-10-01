<?php

namespace App\Providers;

use App\Support\DjangoPbkdf2Hasher;
use App\Support\NavegacaoPortal;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Hash::extend('django', fn () => new DjangoPbkdf2Hasher(config('portal.pbkdf2_iterations')));

        Carbon::setLocale(config('app.locale'));
        Paginator::defaultView('partials.paginacao');

        View::composer('layouts.app', function ($view) {
            $view->with(NavegacaoPortal::contexto(request()));
        });
    }
}
