<?php

namespace App\Providers;

use App\Listeners\ConfigureAdminLteMenu;
use App\Models\StrukturPengurus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use App\Support\ActiveKelurahan;
use JeroenNoten\LaravelAdminLte\Events\BuildingMenu;

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
        if ($this->app->environment('production') && str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        Event::listen(BuildingMenu::class, ConfigureAdminLteMenu::class);

        Route::bind('struktur', fn (string $value) => StrukturPengurus::query()->findOrFail($value));

        View::composer('layouts.app', function ($view): void {
            $user = auth()->user();
            $activeKelurahan = app(ActiveKelurahan::class);

            $view->with([
                'kelurahanAktif' => $activeKelurahan->resolve($user),
                'daftarKelurahanUntukPemilih' => $user?->hasRole('superadmin')
                    ? $activeKelurahan->daftarUntukPemilih()
                    : collect(),
            ]);
        });
    }
}
