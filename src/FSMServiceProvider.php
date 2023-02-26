<?php

namespace Gecche\FSM;

use Illuminate\Support\ServiceProvider;

class FSMServiceProvider extends ServiceProvider
{


    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('fsm', function($app)
        {
            return new FSMManager($app['config']->get('fsm'));
        });
    }

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {

        $this->publishes([
            __DIR__.'/config/fsm.php' => config_path('fsm.php'),
        ]);

    }

}
