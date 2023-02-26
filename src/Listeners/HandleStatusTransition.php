<?php

namespace Gecche\FSM\Listeners;

use Illuminate\Support\Str;
use Gecche\FSM\Events\StatusTransitionDone;

class HandleStatusTransition
{

    protected $model;
    protected $prevStatusCode;
    protected $statusCode;
    protected $statusData;
    protected $saved;
    protected $params;


    public function handle(StatusTransitionDone $event)
    {

        $modelListenerClass = config('fsm.models_listeners.'.get_class($event->model),'');

        if (class_exists($modelListenerClass)) {
            return (new $modelListenerClass())->handle($event);
        }

        $this->model = $event->model;
        $this->prevStatusCode = $event->prevStatusCode;
        $this->statusCode = $event->statusCode;
        $this->statusData = $event->statusData;
        $this->saved = $event->saved;
        $this->params = $event->params;

        $methodName = 'handleTransitionFrom'.Str::studly($event->prevStatusCode).'To'.Str::studly($event->statusCode);
        if (method_exists($this,$methodName)) {
            return $this->$methodName();
        }

        $this->handleTransition();
    }

    protected function handleTransition() {

    }
}