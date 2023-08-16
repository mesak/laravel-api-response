<?php

namespace Mesak\LaravelApiResponse;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Response;
use Mesak\LaravelApiResponse\Exceptions\BadRequestException;
use Mesak\LaravelApiResponse\Exceptions\BaseException;

class ResponseServiceProvider extends ServiceProvider
{
  static $responseClass = \Mesak\LaravelApiResponse\Http\ApiResponse::class;
  static $responseSchema = \Mesak\LaravelApiResponse\Http\ApiResponseSchema::class;

  /**
   * Bootstrap the application services.
   *
   * @return void
   */
  public function boot(): void
  {
    if ($this->app->runningInConsole()) {
      $this->publishes([
        __DIR__ . '/../config/api-response.php' => config_path('api-response.php'),
      ], 'api-response');
    }
    $this->defaultResponseConfig();
    $this->registerResponseMacro();
    $this->registerErrorHandling();
  }

  /**
   * Register any application services.
   *
   * @return void
   */
  public function register(): void
  {
    //註冊 config 檔案
    $this->mergeConfigFrom(__DIR__ . '/../config/api-response.php', 'api-response');
  }

  /**
   * Register default response config
   *
   * @return void
   */
  public function defaultResponseConfig(): void
  {
    static::$responseClass = config('api-response.response', static::$responseClass);
    static::$responseSchema = config('api-response.schema', static::$responseSchema);
  }

  /**
   * Register the event listener for the event.
   *
   * @return void
   */
  public function registerResponseMacro(): void
  {
    Response::macro('success', function ($result = null, $statusCode = 200) {
      return tap(new ResponseServiceProvider::$responseClass($result), function ($response) use ($statusCode) {
        $response->setStatusCode($statusCode);
      });
    });

    Response::macro('error', function ($exception = null, $statusCode = 400) {
      if ($exception instanceof HttpExceptionInterface) {
        $statusCode = $exception->getStatusCode();
      } else if ($exception instanceof \Throwable) {
        $statusCode = property_exists($exception, 'status') ? $exception->status : $statusCode;
      } else {
        $exception = new BadRequestException((string)$exception);
      }
      return tap(new ResponseServiceProvider::$responseClass($exception), function ($response) use ($statusCode) {
        $response->setStatusCode($statusCode);
      });
    });
  }

  /**
   * Register Error Handling api render
   *
   * @return void
   */
  public function registerErrorHandling(): void
  {
    $this->app->bind(\Illuminate\Contracts\Debug\ExceptionHandler::class, function ($app) {
      return new Exceptions\Handler($app['Illuminate\Contracts\Container\Container']);
    });
  }
}
