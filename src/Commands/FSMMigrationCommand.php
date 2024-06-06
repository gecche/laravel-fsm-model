<?php

namespace Gecche\FSM\Commands;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;

/**
 * Class CompileRelationsCommand
 * @package Gecche\Breeze
 *
 * This command compiles the relations of Breeze models defined in their relational array.
 *
 * For each model encountered, it creates a correspondent relational trait in a "relations" subfolder and adds the
 * use of that trait to the Breeze model class.
 *
 * The relational trait contains all the relational methods with the standard Eloquent signature.
 *
 */
class FSMMigrationCommand extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fsm:migration
                    {model   : Eloquent model class name to which apply the migration}
                    {--status=status  : Status fieldname}
                    {--history=status_history : Status history fieldname}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add fields for FSM to the specified Eloquent model\'s table';


    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {

        $modelClass = $this->argument('model');
        if (!class_exists($modelClass)) {
            throw new \Exception("Class " . $modelClass . " does not exist");
        }

        $model = new $modelClass();
        if (!($model instanceof Model)) {
            throw new \Exception("Class " . $modelClass . " is not an Eloquent Model class");
        }


        $migrationTable = $model->getTable();
        $stringsToReplace = [
            '{{migrationTable}}' => $migrationTable,
            '{{statusFieldname}}' => $this->option('status'),
            '{{statusHistoryFieldname}}' => $this->option('history'),
        ];

        /*
         * We check for the package's relations stub.
         */
        if (!($migrationStub = $this->getStub('migration'))) {
            $this->info('migration FSM stub not found');
            return;
        };

        $migrationStub = str_replace(array_keys($stringsToReplace), array_values($stringsToReplace), $migrationStub);

        $migrationFilename = date('Y_m_d_His') . '_add_fsm_to_' . Str::snake($migrationTable) . '_table.php';


        $migrationFullFilename = database_path('migrations/' . $migrationFilename);

        file_put_contents(
            $migrationFullFilename,
            $migrationStub
        );

        $this->info('FSM Migration for model ' . $modelClass . ' generated successfully.');


    }


    /**
     * Get the path to the stubs.
     *
     * @return string
     */
    public function stubPath()
    {
        return Config::get('fsm.stub-path') ?: __DIR__ . '/../resources/stubs';
    }


    /**
     * @param $stubName
     * @param bool $relationName
     * @return bool|false|string
     */
    protected function getStub($stubName)
    {
        $stubFileName = $this->stubPath() . '/' . $stubName . '.stub';
        if (file_exists($stubFileName))
            return file_get_contents($stubFileName);

        return false;
    }


}
