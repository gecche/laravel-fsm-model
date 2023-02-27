<?php

namespace Gecche\FSM\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Database\Eloquent\Model;

class StatusTransitionDone
{
    use Dispatchable, SerializesModels;

    /** @var Model */
    public $model;
    public $prevStatusCode;
    public $statusCode;
    public $statusData;
    public $saved;
    public $params;

    public function __construct(Model $model, string|null $prevStatusCode, string|null $statusCode, array $statusData = [], bool $saved = true, array $params = [])
    {
        $this->model = $model;
        $this->prevStatusCode = $prevStatusCode;
        $this->statusCode = $statusCode;
        $this->statusData = $statusData;
        $this->saved = $saved;
        $this->params = $params;

    }
}
