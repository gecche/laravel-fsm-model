<?php

namespace Gecche\FSM;

use Gecche\Breeze\Breeze;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FSMManager
{

    protected $fsmConfig;

    protected $fsms = [];

    /**
     * FormList constructor.
     * @param array $input
     * @param Breeze $model
     * @param array $params
     */
    public function __construct($fsmConfig)
    {
        $this->fsmConfig = $fsmConfig;
    }

    /**
     * @return mixed
     */
    public function getFSM(Model $model)
    {
        $modelName = get_class($model);
        $fsmType = Arr::get(Arr::get($this->fsmConfig, 'models', []), $modelName);
        if (array_key_exists($fsmType, $this->fsms)) {
            return $this->fsms[$fsmType];
        }
        $fsm = $this->buildFSM($fsmType);
        $this->fsms[$fsmType] = $fsm;
        return $fsm;
    }


    public function buildFSM($fsmType)
    {

        $fsmTypes = Arr::get($this->fsmConfig,'types',[]);
        $fsm = Arr::get($fsmTypes, $fsmType);
        if (!$fsm || !is_array($fsm)) {
            throw new \Exception("Fsm configuration not found");
        }
        return new FSM($fsm);
    }


}
