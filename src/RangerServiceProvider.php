<?php

namespace Laravel\Ranger;

// TODO: Temp fix, gotta figure this out...
// ini_set('memory_limit', '1G');

use Illuminate\Support\ServiceProvider;
use Laravel\Ranger\Collectors\BroadcastChannels;
use Laravel\Ranger\Collectors\BroadcastEvents;
use Laravel\Ranger\Collectors\Enums;
use Laravel\Ranger\Collectors\Models;
use Laravel\Ranger\Collectors\Routes;

class RangerServiceProvider extends ServiceProvider
{
    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(Enums::class);
        $this->app->singleton(Models::class);
        $this->app->singleton(Routes::class);
        $this->app->singleton(BroadcastChannels::class);
        $this->app->singleton(BroadcastEvents::class);
    }

    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPublishing();
    }

    /**
     * Register the package's publishable resources.
     *
     * @return void
     */
    protected function registerPublishing()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/ranger.php' => config_path('ranger.php'),
            ], 'ranger-config');
        }
    }
}
