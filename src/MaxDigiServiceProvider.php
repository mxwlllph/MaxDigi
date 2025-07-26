<?php

declare(strict_types=1);

namespace Mxwlllph\MaxDigi;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Mxwlllph\MaxDigi\Services\MaxDigiService;

/**
 * @author Maxwell Alpha
 */
class MaxDigiServiceProvider extends ServiceProvider
{
    /**
     * Mendaftarkan layanan aplikasi.
     *
     * @return void
     */
    public function register(): void
    {
        // Menggabungkan konfigurasi paket dengan konfigurasi aplikasi
        $this->mergeConfigFrom(
            __DIR__.'/../config/maxdigi.php',
            'maxdigi'
        );

        // Mendaftarkan MaxDigiService sebagai singleton
        $this->app->singleton('maxdigi', function ($app) {
            return new MaxDigiService(
                $app['config']['maxdigi']['username'],
                $app['config']['maxdigi']['api_key']
            );
        });
    }

    /**
     * Bootstrap layanan aplikasi.
     *
     * @return void
     */
    public function boot(): void
    {
        // Memungkinkan publikasi file konfigurasi
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/maxdigi.php' => config_path('maxdigi.php'),
            ], 'maxdigi-config');
        }

        // Mendaftarkan route untuk webhook
        $this->registerRoutes();
    }

    /**
     * Mendaftarkan route yang dibutuhkan oleh paket.
     *
     * @return void
     */
    protected function registerRoutes(): void
    {
        Route::group($this->routeConfiguration(), function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        });
    }

    /**
     * Konfigurasi grup route.
     *
     * @return array
     */
    protected function routeConfiguration(): array
    {
        // Anda bisa menambahkan prefix atau middleware di sini jika perlu
        return [
            'prefix' => 'api/maxdigi',
            // 'middleware' => 'api',
        ];
    }
}
