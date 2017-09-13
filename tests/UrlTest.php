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
        $this->schema()->create('users', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email');
            $table->timestamps();
        });

        $this->schema()->create('channels', function ($table) {
            $table->increments('id');
            $table->string('slug');
            $table->timestamps();
        });

        $this->schema()->create('threads', function ($table) {
            $table->increments('id');
            $table->unsignedInteger('channel_id');
            $table->string('slug');
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

    public function testUsingNonExistentRouteNameWillThrowAnException()
    {
        $this->expectException(\InvalidArgumentException::class);

        $user = new User;
        $user->url('foobar');
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

    public function testDefaultBaseRouteUrlWithMultipleParameters()
    {
        Route::get('threads/{channel}/{thread}', function (Channel $channel, Thread $thread) { })->name('threads.show');

        app('router')->getRoutes()->refreshNameLookups();

        $channel = Channel::create([
            'id' => 1,
            'slug' => 'laravel',
        ]);

        $thread = Thread::create([
            'id' => 10,
            'channel_id' => $channel->id,
            'slug' => 'testing-trait',
        ]);

        $this->assertEquals('threads.show', $thread->route());
        $this->assertEquals(url('threads/1/10'), $thread->url());
    }

    public function testDifferentRouteKeyNameUrlWithMultipleParameters()
    {
        Route::get('threads/{channel}/{thread}', function (ChannelWithDifferentRouteKeyName $channel, ThreadWithDifferentRouteKeyName $thread) { })->name('threads.show');

        app('router')->getRoutes()->refreshNameLookups();

        $channel = ChannelWithDifferentRouteKeyName::create([
            'id' => 1,
            'slug' => 'laravel',
        ]);

        $thread = ThreadWithDifferentRouteKeyName::create([
            'id' => 10,
            'channel_id' => $channel->id,
            'slug' => 'testing-trait',
        ]);

        $this->assertEquals('threads.show', $thread->route());
        $this->assertEquals(url('threads/laravel/testing-trait'), $thread->url());
    }

    /**
     * Get a database connection instance.
     *
     * @param string $connection
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
     * @param string $connection
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

class Channel extends Model
{
    use HasUrl;

    protected $guarded = [];

    protected $table = 'channels';
}

class ChannelWithDifferentRouteKeyName extends Channel
{
    protected $baseRoute = 'threads';

    public function getRouteKeyName()
    {
        return 'slug';
    }
}

class Thread extends Model
{
    use HasUrl;

    protected $guarded = [];

    protected $table = 'threads';

    public function channel()
    {
        return $this->belongsTo(Channel::class);
    }
}

class ThreadWithDifferentRouteKeyName extends Thread
{
    protected $baseRoute = 'threads';

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function channel()
    {
        return $this->belongsTo(ChannelWithDifferentRouteKeyName::class);
    }
}
