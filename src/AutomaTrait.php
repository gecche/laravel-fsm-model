<?php namespace App\Models;

use App\Events\OrdineStatoCambiato;
use App\Scopes\OrdineScope;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Trait SearchableTrait
 * @package Nicolaslopezj\Searchable
 */
trait AutomaTrait
{

    public function changeStatus($statusCode = null, $statusData = [], $save = false, $options = [])
    {
        $automa = $this->getAutoma();
        if (is_null($statusCode)) {
            $statusCode = $automa->getRootState();
            $prevStatusCode = null;
        } else {
            $prevStatusCode = $this->stato;
            if (is_null($prevStatusCode)) {
                throw new \Exception("Stato attuale ordine non impostato");
            }
        }
        if (!$automa->checkTransizione($prevStatusCode, $statusCode)) {
            Log::info("PROBLEMA IN TRANSIZIONE STATO");
            Log::info($prevStatusCode);
            Log::info($statusCode);
            throw new \Exception("Transizione da stato " . $prevStatusCode . " a " . $statusCode . " non permessa");
        }
        return $this->setStatus($statusCode, $statusData, $save, $options);
    }

    public function changeStatusAndSave($statusCode = null, $statusData = [], $options = [])
    {
        return $this->changeStatus($statusCode, $statusData, true, $options);
    }

    public function setStatus($statusCode, $statusData = [], $save = false, $options = [])
    {

        $this->stato = $statusCode;

        $methodName = 'setStatus' . Str::studly($statusCode);
        if (method_exists($this, $methodName)) {
            $this->$methodName($statusData, $options);
        }

        $stati = $this->stati;
        $statoInfo = [
            'timestamp' => Carbon::now()->toDateTimeString(),
            'codice' => $statusCode,
            'data' => $statusData
        ];
//        Log::info("STATI: " . print_r($stati, true));
        array_unshift($stati, $statoInfo);
//        Log::info("STATI NEW: " . print_r($stati, true));
        $this->stati = $stati;

        if ($save) {
            $this->save();
            $this->fireChangeStatusEvent();
            // TODO errore
        }
        $this->logStato($statoInfo);

        return $this;
    }


    public function setInitialStatus($save = false, $options = [])
    {
        if (!$this->stato) {
            return $this->changeStatus(null, [], $save, $options);
        }
    }

    public function setInitialStatusAndSave($options = [])
    {
        return $this->setInitialStatus(true, $options);
    }

//    public function changeState($statusCode = null,$statusData = []) {
//        \Event(new OrderStatusChanged($this->id, $statusId, $prevStatusId));
//    }


    public function getStatiAttribute($value)
    {
        return $this->fromJson($value) ?: [];
    }

    public function getLastStatoInfo()
    {
        $stati = $this->stati;
        $lastStato = Arr::first($stati);

        $lastStato = is_null($lastStato) ? [] : Arr::wrap($lastStato);
        return $lastStato;
    }

    public function fireChangeStatusEvent()
    {
        return true;
        event(new OrdineStatoCambiato($this));
    }


    public function logStato(array $statusInfo = null)
    {

        try {
            if (is_null($statusInfo)) {
                $statusInfo = $this->getLastStatoInfo();
            }

            $modelClass = get_class($this);
            $logConfig = Arr::get(Config::get('logging.automa.types', []), $modelClass);
            if (is_null($logConfig)) {
                Log::alert("AUTOMA MODEL CLASS -- " . $modelClass . " -- NOT FOUND FOR LOG");
            }

            if (!$this->getKey()) {
                return;
            }

            $path = Arr::get($logConfig, 'path');
            File::ensureDirectoryExists($path);

            $statusData = Arr::get($statusInfo, 'data', []);
            $logFormattedString = Arr::get($statusInfo, 'timestamp', Carbon::now()->toDateTimeString()) . " -- STATO -- " .
                Arr::get($statusInfo, 'codice', "NOCODE!!!") . "\n";
            $logFormattedString .= "------ STATO DATA ------ \n";
            $logFormattedString .= (is_string($statusData) ? $statusData : json_encode($statusData, JSON_PRETTY_PRINT)) . "\n";
            Arr::get($statusInfo, 'codice', "NOCODE!!!") . "\n";
            $logFormattedString .= "------ END STATO DATA ------ \n\n";

            $additionalData = $this->logStatoAdditionalData($statusInfo);
            if ($additionalData) {
                $logFormattedString .= "------ STATO ADDITIONAL DATA ------ \n";
                $logFormattedString .= (is_string($additionalData) ? $additionalData : json_encode($additionalData, JSON_PRETTY_PRINT)) . "\n";
                $logFormattedString .= "------ END STATO ADDITIONAL DATA ------ \n\n";
            }

            $logFormattedString .= "---------------------------- \n\n\n";

            $fileLogDay = ($this->created_at ? substr($this->created_at,0,10) : date('Y-m-d'));
            $fileLog = $path . $this->getKey() .  '_' . $fileLogDay . '.log';
            File::append($fileLog, $logFormattedString);
        } catch (\Throwable $e){
            Log::error("PROBLEMI NEL LOG DI STATO");
            Log::error($e->getMessage());
        }


    }

    protected function logStatoAdditionalData(array $statusInfo) {
        return null;
    }
}
