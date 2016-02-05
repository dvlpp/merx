<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Filesystem\ClassFinder;
use Illuminate\Filesystem\Filesystem;
use Faker\Generator as Faker;
use Illuminate\Support\Facades\Schema;

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
            "article_id" => uniqid(),
            "article_type" => TestArticle::class,
            "name" => $this->faker->word,
            "price" => $this->faker->numberBetween(100, 10000),
            "quantity" => $this->faker->numberBetween(1, 10),
            "details" => $this->faker->sentence,
        ];
    }

    protected function migrateDatabase()
    {
        // First create fake users and articles tables
        (new CreateTestUsersTable())->up();
        (new CreateTestArticlesTable())->up();

        // Then migrate Merx tables
        $fileSystem = new Filesystem;
        $classFinder = new ClassFinder;

        foreach ($fileSystem->files(__DIR__ . "/../database/migrations") as $file) {
            $fileSystem->requireOnce($file);
            $migrationClass = $classFinder->findClass($file);

            (new $migrationClass)->up();
        }
    }

    protected function loginClient()
    {
        $user = TestUser::create();

        auth()->login($user);

        return $user;
    }
}

class TestUser extends \Illuminate\Database\Eloquent\Model implements Illuminate\Contracts\Auth\Authenticatable
{

    use Illuminate\Auth\Authenticatable;

    protected $table = 'users';
    protected $fillable = ['id'];

    public function isMerxUser()
    {
        return true;
    }
}

class TestArticle extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'articles';
    protected $fillable = ["id"];

    public static $merxCartItemAttributesExceptions = [
        "custom_out_of_id"
    ];
}

class CreateTestUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
        });
    }
}

class CreateTestArticlesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
        });
    }
}