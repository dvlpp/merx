<?php

use Illuminate\Filesystem\ClassFinder;
use Illuminate\Filesystem\Filesystem;
use Faker\Generator as Faker;

abstract class TestCase extends Illuminate\Foundation\Testing\TestCase
{
    /**
     * @var Faker
     */
    protected $faker;

    public function setUp()
    {
        parent::setUp();

        $this->app['config']->set('database.default', 'sqlite');
        $this->app['config']->set('database.connections.sqlite.database', ':memory:');

        $this->faker = \Faker\Factory::create();

        $this->migrateDatabase();
    }

    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__ . '/../vendor/laravel/laravel/bootstrap/app.php';

        $app->register(\Dvlpp\Merx\MerxServiceProvider::class);

        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        return $app;
    }

    protected function itemAttributes()
    {
        return [
            "ref" => uniqid(),
            "name" => $this->faker->word,
            "price" => $this->faker->numberBetween(100, 10000),
            "quantity" => $this->faker->numberBetween(1, 10),
            "details" => $this->faker->sentence,
        ];
    }

    protected function migrateDatabase()
    {
        $fileSystem = new Filesystem;
        $classFinder = new ClassFinder;

        foreach ($fileSystem->files(__DIR__ . "/../database/migrations") as $file) {
            $fileSystem->requireOnce($file);
            $migrationClass = $classFinder->findClass($file);

            (new $migrationClass)->up();
        }
    }
}
