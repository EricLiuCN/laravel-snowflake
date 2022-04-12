<?php

namespace Ericliucn\LaravelSnowflake\Providers;

use Illuminate\Support\ServiceProvider;
use Ericliucn\LaravelSnowflake\Snowflake;

class SnowflakeServiceProvider extends ServiceProvider
{
    protected $defer = true; // 延迟加载服务
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('snowflake', function ($app) {
            $config = config('snowflake.config', array());
            return new Snowflake($config);
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/config.php' => config_path('snowflake/config.php'),
            __DIR__.'/sequenceId.lock' => config_path('snowflake/sequenceId.lock'),
        ], 'config');
    }


    public function provides()
    {
        return ['snowflake'];
    }
}
