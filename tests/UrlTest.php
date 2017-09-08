<?php

namespace CliffordJames\LaravelUrls\Tests;

use CliffordJames\LaravelUrls\HasUrl;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model;
use Route;

class UrlTest extends TestCase
{
    /**
     * Setup the database schema.
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $db = new DB;

        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $db->bootEloquent();
        $db->setAsGlobal();

        $this->createSchema();
    }

    protected function createSchema()
    {
        $this->schema('default')->create('users', function ($table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('email');
            $table->timestamps();
        });
    }

    /**
     * Tear down the database schema.
     *
     * @return void
     */
    public function tearDown()
    {
        $this->schema('default')->drop('users');
    }

    public function testDefaultBaseRoute()
    {
        $user = new User;

        $this->assertEquals('users.show', $user->route());
        $this->assertEquals('users.show', $user->route('show'));
        $this->assertEquals('users.edit', $user->route('edit'));
    }

    public function testDifferentBaseRoute()
    {
        $user = new UserWithDifferentBaseRoute;

        $this->assertEquals('profiles.show', $user->route());
        $this->assertEquals('profiles.show', $user->route('show'));
        $this->assertEquals('profiles.edit', $user->route('edit'));
    }

    public function testDefaultBaseRouteUrl()
    {
        Route::get('users/{user}', function (User $user) { })->name('users.show');
        Route::get('users/{user}/edit', function (User $user) { })->name('users.edit');

        app('router')->getRoutes()->refreshNameLookups();

        $user = User::create([
            'id' => 1,
            'name' => 'CliffordJames',
            'email' => 'clifford.james@me.com',
        ]);

        $this->assertEquals('users.show', $user->route());
        $this->assertEquals(url('users/1'), $user->url());

        $this->assertEquals('users.edit', $user->route('edit'));
        $this->assertEquals(url('users/1/edit'), $user->url('edit'));
    }

    public function testDifferentBaseRouteUrl()
    {
        Route::get('profiles/{user}', function (UserWithDifferentBaseRoute $user) { })->name('profiles.show');
        Route::get('profiles/{user}/edit', function (UserWithDifferentBaseRoute $user) { })->name('profiles.edit');

        app('router')->getRoutes()->refreshNameLookups();

        $user = UserWithDifferentBaseRoute::create([
            'id' => 2,
            'name' => 'CliffordJames',
            'email' => 'clifford.james@me.com',
        ]);

        $this->assertEquals('profiles.show', $user->route());
        $this->assertEquals(url('profiles/2'), $user->url());

        $this->assertEquals('profiles.edit', $user->route('edit'));
        $this->assertEquals(url('profiles/2/edit'), $user->url('edit'));
    }

    public function testDifferentRouteKeyNameUrl()
    {
        Route::get('users/{user}', function (UserWithDifferentRouteKeyName $user) { })->name('users.show');
        Route::get('users/{user}/edit', function (UserWithDifferentRouteKeyName $user) { })->name('users.edit');

        app('router')->getRoutes()->refreshNameLookups();

        $user = UserWithDifferentRouteKeyName::create([
            'id' => 3,
            'name' => 'CliffordJames',
            'email' => 'clifford.james@me.com',
        ]);

        $this->assertEquals('users.show', $user->route());
        $this->assertEquals(url('users/CliffordJames'), $user->url());

        $this->assertEquals('users.edit', $user->route('edit'));
        $this->assertEquals(url('users/CliffordJames/edit'), $user->url('edit'));
    }

    /**
     * Helpers...
     */

    /**
     * Get a database connection instance.
     *
     * @return \Illuminate\Database\Connection
     */
    protected function connection($connection = 'default')
    {
        return Model::getConnectionResolver()->connection($connection);
    }

    /**
     * Get a schema builder instance.
     *
     * @return \Illuminate\Database\Schema\Builder
     */
    protected function schema($connection = 'default')
    {
        return $this->connection($connection)->getSchemaBuilder();
    }
}

/**
 * Eloquent Models...
 */
class User extends Model
{
    use HasUrl;

    protected $guarded = [];

    protected $table = 'users';
}

class UserWithDifferentBaseRoute extends User
{
    protected $baseRoute = 'profiles';
}

class UserWithDifferentRouteKeyName extends User
{
    protected $baseRoute = 'users';

    public function getRouteKeyName()
    {
        return 'name';
    }
}
