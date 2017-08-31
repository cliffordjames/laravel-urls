## Easy model routes for Laravel Eloquent models ##
This package allows you to create easy model routes for you Eloquent models.

## Installing ##

```bash
$ composer require cliffordjames/laravel-urls
```

## Usage ##
Let's say you have the following routes for displaying a user:

```php
Route::get('/users/{user}', function (\App\User $user) {
    return $user;
})->name('users.show');
```

All you have to do is add the trait to the User model:

```php
<?php

namespace App;

use CliffordJames\LaravelUrls\HasUrl;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable, HasUrl;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];
}
```

And you can generate the url to this route by using the traits `url()` function:

```php
$user = User::first();
//
$user->url(); // -> /users/1
// vs
route('users.show', $user); // -> /users/1
```

### Resource routes ###
You can specify the route name as the first parameter in de `url()` function:

```php
Route::resource('users', 'UserController');

$user = User::first();

$user->url(); // -> /users/1
$user->url('show'); // -> /users/1
$user->url('edit'); // -> /users/1/edit
$user->url('update'); // -> /users/1
...
```

### Additional parameters ###
Let's say you have the following route:

```php
Route::get('/threads/{channel}/{thread}', 'ThreadsController@show')->name('threads.show');
```

And you have added the trait and set the relationship from the `Thread` model to the `Channel` model, the following examples have the same outcome:

```php
$thread->url(); // -> /threads/*channel_id*/*thread_id*
$thread->url($thread->channel); // same as above
$thread->url($thread, $thread->channel); // same as above
$thread->url([$thread, $thread->channel]); // same as above
$thread->url(['thread' => $thread, 'channel' => $thread->channel]); // same as above

route('threads.show', ['thread' => $thread, 'channel' => $thread->channel]); // same as above
```

## Configurations ##
There is only one configuration you can do for now and it is setting the `$baseRoute` on the model itself, it defaults to the same method of how the table name for a model is generated.

`User` model example, when not set it defaults to `user`:

```php
protected $baseRoute = 'profile';
```

Now `$user->url()` is looking for the `profile.show` route.
