<?php

namespace Dvlpp\Merx\Console;

use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Filesystem\ClassFinder;
use Illuminate\Filesystem\Filesystem;

class MigrateDb extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'merx:migrate {--refresh : if present existing tables will be deleted first}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate Merx DB table if needed.';

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var ClassFinder
     */
    private $classFinder;

    /**
     * Create a new command instance.
     * @param Filesystem $filesystem
     * @param ClassFinder $classFinder
     */
    public function __construct(Filesystem $filesystem, ClassFinder $classFinder)
    {
        parent::__construct();

        $this->filesystem = $filesystem;
        $this->classFinder = $classFinder;
    }

    protected function disableForeignKeyChecks()
    {
        switch(\DB::getDriverName()) {
            case 'mysql':
                \DB::statement('SET FOREIGN_KEY_CHECKS=0');
                break;
            case 'sqlite':
                \DB::statement('PRAGMA foreign_keys = OFF');
                break;
        }
    }

    protected function enableForeignKeyChecks()
    {
        switch(\DB::getDriverName()) {
            case 'mysql':
                \DB::statement('SET FOREIGN_KEY_CHECKS=1');
                break;
            case 'sqlite':
                \DB::statement('PRAGMA foreign_keys = ON');
                break;
        }
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->disableForeignKeyChecks();

        $migrationCount = 0;

        foreach ($this->filesystem->files(__DIR__ . "/../../../../database/migrations") as $file) {
            $this->filesystem->requireOnce($file);
            $migrationClass = $this->classFinder->findClass($file);

            $migration = new $migrationClass;

            if ($this->option("refresh")) {
                $migration->down();
            }

            try {
                $migration->up();
                $migrationCount++;

            } catch (QueryException $ex) {
                if (!$this->isTableAlreadyExistError($ex)) {
                    throw $ex;
                }
            }
        }

        $this->enableForeignKeyChecks();

        $this->info("$migrationCount table(s) migrated.");
    }

    private function isTableAlreadyExistError(\Exception $ex)
    {
        return $ex->getCode() == "42S01";
    }
}