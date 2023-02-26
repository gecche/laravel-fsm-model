<?php namespace Gecche\FSM;

use Gecche\FSM\Contracts\FSMInterface;
use Illuminate\Support\Facades\Config;

use App\Models\Orderstatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

trait FSMTrait
{



    public FSMInterface $fsm;

    public static function bootSearchableTrait()
    {
        static::created(function ($item) {

            $item->fsm = FSM::getFSM($item);
            // Index the item
        });

    }

    public function getFSMStates() {
        return $this->fsm->getStates();
    }


    /**
     * @return array
     */
    public function getFSMStateInfo(string $stateCode, $info = null) {
        return $this->fsm->getStateInfo($stateCode, $info);
    }

    /**
     * @return array
     */
    public function getFSMStatesCodes() {
        return $this->fsm->getStatesCodes();
    }

    /**
     * Ritorna gli states successivi possibili a partire da uno stato $key
     * @param string $code
     * @return array(key,descrizione)
     */
    public function getFSMNextStatesFromCode(string $code) {
        return $this->fsm->getNextStatesFromCode($code);
    }

    /**
     * Ritorna gli states successivi possibili a partire da un array di states $codearray
     * @param array $keyarray (i codici degli states di partenza)
     * @return array(array(key,descrizione))
     */
    public function getFSMNextStatesFromCodes(array $codes = []) {
        return $this->fsm->getNextStatesFromCodes($codes);
    }

    public function getFSMRootState() {
        return $this->fsm->getRootState();
    }

    /**
     * controlla che il passaggio di stato sia lecito.
     * @param string $startCode
     * @param string $endCode
     * @return bool
     */
    public function checkTransition($startCode, $endCode) {
        return $this->fsm->checkTransition($startCode, $endCode);
    }

    public function getFSMStateDescription($stateCode) {
        return $this->fsm->getStateDescription($stateCode);
    }

    public function isFinalCode($stateCode) {
        return $this->fsm->isFinalCode($stateCode);
    }

    public function getFSMPreviousStatesFromCode(string $code) {
        return $this->fsm->getPreviousStatesFromCode($code);
    }

    public function getFSMPreviousStatesFromCodes(array $codes) {
        return $this->fsm->getPreviousStatesFromCodes($codes);
    }

    /**
     * Trova un path dallos tato scelto allo stato root;
     * @param string $code
     * @return array
     */
    public function getFSMReversePath($code) {
        return $this->fsm->getReversePath($code);
    }





}
