<?php

namespace Mesak\LaravelApiResponse;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Response;
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
    Response::macro('success', function ($result = null, $status = 200) {
      return tap(new ResponseServiceProvider::$responseClass($result), function ($response) use ($status) {
        $response->setStatusCode($status);
      });
    });
    Response::macro('error', function ($exception = null, $status = 400) {
      $exception = $exception ?? new BaseException();
      return tap(new ResponseServiceProvider::$responseClass($exception), function ($response) use ($status) {
        $response->setStatusCode($status);
      });
    });
  }
}
