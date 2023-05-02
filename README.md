# Laravel Api Response

Laravel Api Response

Control the api format with a unified callback method

# installation

```bash
composer require mesak/laravel-api-response
```

Or you could directly reference it into your `composer.json` file as a dependency

```json
{
  "require": {
    "mesak/laravel-api-response": "^1.0.0"
  }
}
```

## Controller

change your api controller, to extend `Mesak\LaravelApiResponse\Http\Controllers\Controller`

```php
namespace App\Http\Controllers;

use Mesak\LaravelApiResponse\Http\Controllers\ApiController as BaseController;

class MainController extends BaseController
{

}
```

then you can return your all response

# Example

## Use Pagination

```php
class MainController extends BaseController
{

    function index(Request $request)
    {
      return \App\Models\User::paginate(15);
    }
}
```

## Use Resources

```php
class MainController extends BaseController
{

    function index(Request $request)
    {
      $users = \App\Models\User::paginate(15);
      return \App\Http\Resources\User::collection($users);
    }
}
```

## Use Exception

```php
class MainController extends BaseController
{

  function index(Request $request)
  {
    $users = \App\Models\User::paginate(15);
    if( $users->isEmpty() )
    {
      throw new \Exception('No users found');
    }
    return \App\Http\Resources\User::collection($users);
  }
}
```

## Use Extends Exception

```php

class MainController extends BaseController
{
  function index(Request $request)
  {
    $users = \App\Models\User::paginate(15);
    if( $users->isEmpty() )
    {
      throw new \Mesak\LaravelApiResponse\Exceptions\BaseException('No users found' ); //statusCode 500
    }
    return \App\Http\Resources\User::collection($users);
  }
}
```


## Use Custom Exception

create your custom exception class `app/Exceptions/BadRequestException.php`
```php
namespace App\Exceptions;

class BadRequestException extends \Mesak\LaravelApiResponse\Exceptions\BaseException
{
  protected $errorCode = 400;
  protected $statusCode = 400;
  public function __construct($message = 'Bad Request')
  {
    parent::__construct($message);
  }
}
```

then you can use it in your controller

```php
use App\Exceptions\BadRequestException;
class MainController extends BaseController
{
  function index(Request $request)
  {
    $users = \App\Models\User::paginate(15);
    if( $users->isEmpty() )
    {
      throw new BadRequestException(); //statusCode 400
    }
    return \App\Http\Resources\User::collection($users);
  }
}
```


## without ApiController

if you don't want to extend `Mesak\LaravelApiResponse\Http\Controllers\ApiController` you can use 

`response()->success($data,$statusCode);`

`response()->error($data,$statusCode);`

```php
class MainController extends \App\Http\Controllers\Controller
{
  function index(Request $request)
  {
    $users = \App\Models\User::paginate(15);
    if( $users->isEmpty() )
    {
      return response()->error('no data',400);
    }
    $result = \App\Http\Resources\User::collection($users);
    return response()->success($result);
  }
}
```


## custom config

You can customise your api schema content with a custom config.php.

```bash
php artisan vendor:publish --tag=api-response --force
```

