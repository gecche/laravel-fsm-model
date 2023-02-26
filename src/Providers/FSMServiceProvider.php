<?php

namespace Gecche\FSM\Providers;

use Gecche\FSM\Commands\FSMMigrationCommand;
use Illuminate\Support\ServiceProvider;
use Gecche\FSM\FSMManager;

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

        $this->app->register(EventServiceProvider::class);
    }

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {

        $this->registerCommands();
        $this->publishes([
            __DIR__.'/config/fsm.php' => config_path('fsm.php'),
            __DIR__.'/App/Listeners/HandleStatusTransition.php' => app_path('Listeners/HandleStatusTransition.php'),
        ]);

    }

    public function registerCommands() {
        if ($this->app->runningInConsole()) {
            $this->commands([
                FSMMigrationCommand::class,
            ]);
        }
    }

}
