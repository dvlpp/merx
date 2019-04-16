<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Faker\Generator as Faker;
use Illuminate\Support\Facades\Schema;

abstract class BrowserKitCase extends \Laravel\BrowserKitTesting\TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    /**
     * @var Faker
     */
    protected $faker;

    public function setUp(): void
    {
        parent::setUp();

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

        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');

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
        // Create fake articles table
        (new CreateTestArticlesTable())->up();
    }

    protected function loginClient()
    {
        $user = factory(\App\User::class)->create();

        auth()->login($user);

        return $user;
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