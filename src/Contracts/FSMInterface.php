<?php

namespace Gecche\FSM\Contracts;

use Gecche\FSM\unknown_type;

interface FSMInterface
{
    /**
     * @return array
     */
    public function getStates();

    /**
     * @return array
     */
    public function getStateInfo(string $stateCode, $info = null);

    /**
     * @return array
     */
    public function getStatesCodes();

    /**
     * Ritorna gli states successivi possibili a partire da uno stato $key
     * @param string $code
     * @return array(key,descrizione)
     */
    public function getNextStatesFromCode(string $code);

    /**
     * Ritorna gli states successivi possibili a partire da un array di states $codearray
     * @param array $keyarray (i codici degli states di partenza)
     * @return array(array(key,descrizione))
     */
    public function getNextStatesFromCodes(array $codes = []);

    public function getRootState();

    /**
     * controlla che il passaggio di stato sia lecito.
     * @param string $startCode
     * @param string $endCode
     * @return bool
     */
    public function checkTransition($startCode, $endCode);

    public function getStateDescription($stateCode);

    public function isFinalCode($stateCode);

    public function getPreviousStatesFromCode(string $code);

    public function getPreviousStatesFromCodes(array $codes);

    public function isInGroup($code,$group);

    public function getAllCodesInGroup($group);
    /**
     * Trova un path dallos tato scelto allo stato root;
     * @param string $code
     * @return array
     */
    public function getReversePath($code);
}