<?php

namespace Dvlpp\Merx;

use Dvlpp\Merx\Console\MigrateDb;
use Illuminate\Support\ServiceProvider;

class MerxServiceProvider extends ServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    public function boot()
    {
        if(method_exists($this, 'loadMigrationsFrom')) {
            $this->loadMigrationsFrom(__DIR__ . '/../../../database/migrations/');

        } else {
            // Publish migrations old-school way
            $this->publishes([
                __DIR__ . '/../../../database/migrations/' => database_path('migrations')
            ], 'migrations');
        }

        // Publish config
        if(class_exists('Illuminate\Foundation\Application', false)) {
            $this->publishes([
                __DIR__ . '/../../../config/merx.php' => config_path('merx.php')
            ], 'config');
        }
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        // Register Facade
        $this->app->bind('merx', Merx::class);

        if(class_exists('Illuminate\Filesystem\ClassFinder')) {
            $this->commands(MigrateDb::class);
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array();
    }

}
