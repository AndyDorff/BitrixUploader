<?php

namespace Aniart\BitrixUploader\Providers;

use Aniart\BitrixUploader\Console\Commands\FillProductAttributes;
use Aniart\BitrixUploader\Console\Commands\UploadProducts;
use Aniart\Core\Console\Commands\Install;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;

class BitrixUploaderServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

        $this->loadRoutesFrom(__DIR__ . '/../Http/admin-routes.php');

        $this->loadRoutesFrom(__DIR__ . '/../Http/shop-routes.php');

        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'bitrixuploader');

        $this->publishes([
            __DIR__ . '/../../publishable/assets' => public_path('themes/default/assets'),
        ], 'public');

        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'bitrixuploader');

        Event::listen('bagisto.admin.layout.head', function($viewRenderEventManager) {
            $viewRenderEventManager->addTemplate('bitrixuploader::admin.layouts.style');
        });
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerConfig();
        $this->registerCommands();
    }

    /**
     * Register package config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->mergeConfigFrom(
            dirname(__DIR__) . '/Config/admin-menu.php', 'menu.admin'
        );

        $this->mergeConfigFrom(
            dirname(__DIR__) . '/Config/acl.php', 'acl'
        );

        $this->mergeConfigFrom(
            dirname(__DIR__) . '/Config/config.php', 'bitrix_uploader'
        );
    }

    private function registerCommands()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                UploadProducts::class,
                FillProductAttributes::class
            ]);
        }
    }

}