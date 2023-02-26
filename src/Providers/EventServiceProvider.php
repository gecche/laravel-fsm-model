<?php

namespace Gecche\FSM\Providers;

use Gecche\FSM\Events\StatusTransitionDone;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();
    }

    /**
     * Get the events and handlers.
     *
     * @return array
     */
    public function listens()
    {
        $fsmListener = $this->app['config']->get('fsm.listener','');
        return class_exists($fsmListener)
            ? [
                StatusTransitionDone::class => [
                    $fsmListener,
                ]
            ]
            : [];
    }
}