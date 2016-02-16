<?php

namespace Dvlpp\Merx\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\ClassFinder;
use Illuminate\Filesystem\Filesystem;

class MigrateDb extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'merx:migrate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate Merx DB table. Warning: existing Merx data will be lost.';

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

    protected function setForeignKeyChecks()
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

    protected function unsetForeignKeyChecks()
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
        $this->setForeignKeyChecks();

        foreach ($this->filesystem->files(__DIR__ . "/../../../../database/migrations") as $file) {
            $this->filesystem->requireOnce($file);
            $migrationClass = $this->classFinder->findClass($file);

            $migration = new $migrationClass;
            $migration->down();
            $migration->up();
        }

        $this->info("Merx tables migrated.");

        $this->unsetForeignKeyChecks();
    }
}
