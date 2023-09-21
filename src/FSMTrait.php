<?php namespace Gecche\FSM;

use Gecche\FSM\Contracts\FSMInterface;
use Gecche\FSM\Events\StatusTransitionDone;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Gecche\FSM\Facades\FSM;

trait FSMTrait
{

    public FSMInterface $fsm;


    public function initializeFSMTrait()
    {

        $this->fsm = FSM::getFSM($this);

    }

    /**
     * Get the casts array.
     *
     * @return array
     */
    public function getCasts()
    {
        $this->casts[$this->getStatusHistoryFieldname()] = 'array';
        return parent::getCasts();
    }

    public function getFSM()
    {
        return $this->fsm;
    }

    public function getStatusFieldname()
    {
        return 'status';
    }

    public function getStatusHistoryFieldname()
    {
        return 'status_history';
    }

    public function makeTransition($statusCode = null, $statusData = [], $save = true, $params = [])
    {
        $rootState = $this->fsm->getRootState();
        if (is_null($statusCode)) {
            $statusCode = $rootState;
            $prevStatusCode = null;
        } else {
            $prevStatusCode = $this->{$this->getStatusFieldname()};
            if (is_null($prevStatusCode) && ($statusCode !== $rootState)) {
                throw new \Exception("Previous status code not available");
            }
        }
        if (!$this->fsm->checkTransition($prevStatusCode, $statusCode)) {
            $msg = "FSM error::: State transition from " . $prevStatusCode . " to " . $statusCode . " not allowed";
            Log::info($msg);
            throw new \Exception($msg);
        }

        $methodName = 'checkTransitionFrom' . Str::studly($prevStatusCode) . 'To' . Str::studly($statusCode);
        if (method_exists($this, $methodName) && !$this->$methodName($statusData, $params)) {
            $msg = "FSM error::: State transition from " . $prevStatusCode . " to " . $statusCode . " not allowed for this item";
            Log::info($msg);
            throw new \Exception($msg);
        }

        return $this->setStatus($prevStatusCode, $statusCode, $statusData, $save, $params);
    }

    public function makeTransitionAndSave($statusCode = null, $statusData = [], $params = [])
    {
        return $this->makeTransition($statusCode, $statusData, true, $params);
    }

    public function startFSM($save = false, $statusData = [], $params = [])
    {
        return $this->makeTransition($this->fsm->getRootState(), $statusData, $save, $params);
    }

    public function startFSMAndSave($statusData = [], $params = [])
    {
        return $this->startFSM(true, $params);
    }

    protected function setStatus($prevStatusCode, $statusCode, $statusData = [], $save = false, $params = [])
    {

        //Set the new status in the field
        $this->{$this->getStatusFieldname()} = $statusCode;

        //Perform additional operations if needed using a method
        // setStatus<StudlyCode>($prevStatusCode,$statusData,$params)"
        $methodName = 'setStatus' . Str::studly($statusCode);
        if (method_exists($this, $methodName)) {
            $this->$methodName($prevStatusCode, $statusData, $params);
        }

        //Update history
        $this->updateStatusHistory($statusCode, $statusData, $prevStatusCode, $params);

        if ($save) {
            $this->save(Arr::get($params,'saveOptions',[]));
        }

        if (Arr::get($params,'fireEvent',true)) {
            $this->fireMakeTransitionEvent($prevStatusCode, $statusCode, $statusData, $save, $params);
        }

        $this->logStatus($prevStatusCode, $statusCode, $statusData, $save, $params);

        return $this;
    }


    protected function updateStatusHistory($statusCode, $statusData, $prevStatusCode = null, $params = [])
    {
        $statusHistoryFieldname = $this->getStatusHistoryFieldname();
        $states = $this->$statusHistoryFieldname;
        if (is_null($states)) {
            $states = [];
        }
        $statusInfo = $this->buildStatusInfo($statusCode, $statusData, $prevStatusCode, $params);
//        Log::info("STATI: " . print_r($states, true));
        array_unshift($states, $statusInfo);
//        Log::info("STATI NEW: " . print_r($states, true));
        $this->$statusHistoryFieldname = $states;

    }

    protected function buildStatusInfo($statusCode, $statusData, $prevStatusCode, $params) {
        return [
            'timestamp' => Carbon::now()->toDateTimeString(),
            'status_code' => $statusCode,
            'info' => $statusData
        ];
    }

    protected function fireMakeTransitionEvent($prevStatusCode, $statusCode, $statusData, $saved, $params)
    {
        event(new StatusTransitionDone($this, $prevStatusCode, $statusCode, $statusData, $saved, $params));
    }

    protected function logStatus($prevStatusCode, $statusCode, $statusData, $saved, $params = [])
    {
    }

    public function getStatusHistory() {
        $statusHistoryName = $this->getStatusHistoryFieldname();
        return $this->$statusHistoryName;
    }
    public function getLastStatus() {

        $statusHistory = $this->getStatusHistory();
        return Arr::get($statusHistory,0,[]);
        
    }

}
