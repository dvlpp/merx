<?php

namespace Dvlpp\Merx;

use Dvlpp\Merx\Models\Cart;
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
        // Publish migrations
        $this->publishes([
            __DIR__ . '/../../../database/migrations/' => database_path('migrations')
        ], 'migrations');

        // Publish config
        $this->publishes([
            __DIR__ . '/../../../config/merx.php' => config_path('merx.php')
        ], 'config');
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

        $this->commands(MigrateDb::class);
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
