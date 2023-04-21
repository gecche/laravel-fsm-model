<?php namespace Gecche\FSM;

use Gecche\FSM\Contracts\FSMInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class FSM implements FSMInterface
{


    protected $config;


    public function __construct(array $config)
    {
        $this->config = $config;
        $this->init();
    }

    public function getConfig() {
        return $this->config;
    }

    protected function init()
    {

        $this->config['states_codes'] = array_keys(Arr::get($this->config, 'states', []));
        $this->config['final_states_codes'] = array_keys(array_filter(Arr::get($this->config, 'states', []), function ($item) {
            return Arr::get($item, 'final', false);
        }));
        $this->config['previous_states_codes'] = array_fill_keys($this->config['states_codes'], []);

        foreach ($this->config['final_states_codes'] as $stateCode) {
            $this->config['previous_states_codes'][$stateCode] = [];
        }

        $groupsIds = array_keys(Arr::get($this->config,'groups',[]));
        $this->config['group_codes'] = array_fill_keys($groupsIds, []);
        foreach ($this->config['states'] as $code => $state) {
            foreach (Arr::get($state, 'groups', []) as $group) {
                if (in_array($group, $groupsIds)) {
                    $this->config['group_codes'][$group][$code] = $code;
                }
            }
        }

        $this->setPreviousStates(Arr::get($this->config, 'root'));
    }

    protected function setPreviousStates($stateCode)
    {
        if (in_array($stateCode, Arr::get($this->config, 'final_states_codes', []))) {
            return;
        }
        $nextStateCodes = Arr::get(Arr::get($this->config, 'transitions', []), $stateCode, []);
        foreach ($nextStateCodes as $nextStateCode) {
            $this->config['previous_states_codes'][$nextStateCode][$stateCode] = $stateCode;
            if ($nextStateCode != $stateCode) {
                static::setPreviousStates($nextStateCode);
            }
        }
    }

    /**
     * @return array
     */
    public function getStates()
    {
        return $this->config['states'];
    }

    /**
     * @return array
     */
    public function getStateInfo(string $stateCode, $info = null)
    {
        $fullStateInfo = Arr::get($this->config['states'], $stateCode, []);
        return is_null($info) ? $fullStateInfo : Arr::get($fullStateInfo, $info);

    }


    /**
     * @return array
     */
    public function getStatesCodes()
    {
        return $this->config['states_code'];
    }

    /**
     * @return array
     */
    protected function getTransitions()
    {
        return Arr::get($this->config, 'transitions', []);
    }


    /**
     * Ritorna gli states successivi possibili a partire da uno stato $key
     * @param string $code
     * @return array(key,descrizione)
     */
    public function getNextStatesFromCode(string $code)
    {
        $transitions = $this->getTransitions();
        return Arr::get($transitions, $code, []);
    }

    /**
     * Ritorna gli states successivi possibili a partire da un array di states $codearray
     * @param array $keyarray (i codici degli states di partenza)
     * @return array(array(key,descrizione))
     */
    public function getNextStatesFromCodes(array $codes = [])
    {
        $stateCodes = [];
        foreach ($codes as $code) {
            $nextStates = $this->getNextStatesFromCode($code);
            $stateCodes = array_merge($stateCodes, $nextStates);
        }
        return $stateCodes;
    }


    public function getRootState()
    {
        return Arr::get($this->config, 'root');
    }

    /**
     * controlla che il passaggio di stato sia lecito.
     * @param string|null $startCode
     * @param string $endCode
     * @return bool
     */
    public function checkTransition($startCode, $endCode)
    {
        if ($endCode === $this->getRootState()) {
            if (is_null($startCode)) {
                return true;
            }
            if ($startCode !== $endCode) {
                return false;
            }
        }
        return in_array($endCode, $this->getNextStatesFromCode($startCode));
    }

    public function getStateDescription($stateCode)
    {
        return $this->getStateInfo($stateCode, 'description');
    }

    public function isFinalCode($stateCode)
    {
        return array_key_exists($stateCode, $this->config['final_states_codes']);
    }


    public function getPreviousStatesFromCode(string $code)
    {
        return Arr::get($this->config['previous_states_codes'], $code, []);
    }

    public function getPreviousStatesFromCodes(array $codes)
    {
        $stateCodes = [];
        foreach ($codes as $code) {
            $prevStates = $this->getPreviousStatesFromCode($code);
            $stateCodes = array_merge($stateCodes, $prevStates);
        }
        return $stateCodes;
    }

//
//    public function getStatiSelect($all = false, $none = false)
//    {
//        if ($all) {
//            $states_select_ante = array('all' => 'Qualsiasi stato');
//        } elseif ($none) {
//            $states_select_ante = array('none' => 'Nessuno stato');
//        } else {
//            $states_select_ante = array();
//        }
//
//        foreach ($this->states_id as $stato_codice => $stato_id) {
//            $states_select[$stato_id] = $stato_codice . ' - ' . $this->states[$stato_codice]['nome'];
//        }
//
//        $states_select = array_merge($states_select_ante, $states_select);
//        return $states_select;
//    }


    /**
     * Trova un path dallos tato scelto allo stato root;
     * @param string $code
     * @return array
     */
    public function getReversePath($code)
    {

        $statesCodes = [];

        $path = $this->getReversePathStep($code, $statesCodes);

        $path[] = $this->getRootState();

        $frontPath = array_reverse($path);

        return $frontPath;


    }


    protected function getReversePathStep($code, $states)
    {
        //Caso base: sono arrivato a root!
        if ($code == $this->getRootState())
            return $states;

        //Caso base: ciclo
        if (in_array($code, $states))
            return false;

        $prevStates = $this->getPreviousStatesFromCode($code);

        array_push($states, $code);
        foreach ($prevStates as $prevCode) {

            $resultStep = $this->getReversePathStep($prevCode, $states);

            if ($resultStep === false)
                continue;

            return $resultStep;
        }


    }

    public function isInGroup($code,$group) {
        return array_key_exists($code,$this->getAllCodesInGroup($group));
    }

    public function getAllCodesInGroup($group) {
        return Arr::get($this->config['group_codes'],$group,[]);
    }


}
