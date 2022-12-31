<?php

namespace Mesak\LaravelApiResponse;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Response;


class ResponseServiceProvider extends ServiceProvider
{
  static $responseClass = \Mesak\LaravelApiResponse\Http\ApiResponse::class;
  static $responseSchema = \Mesak\LaravelApiResponse\Http\ApiResponseSchema::class;
  /**
   * Bootstrap the application services.
   *
   * @return void
   */
  public function boot()
  {
    if ($this->app->runningInConsole()) {
      $this->publishes([
        __DIR__ . '/../config/api-response.php' => config_path('api-response.php'),
      ], 'api-response');
    }
    static::$responseClass = config('api-response.response', static::$responseClass);
    static::$responseSchema = config('api-response.schema', static::$responseSchema);
    $this->registerResponseMacro();
  }

  /**
   * Register any application services.
   *
   * @return void
   */
  public function register()
  {
    //註冊 config 檔案
    $this->mergeConfigFrom(__DIR__ . '/../config/api-response.php', 'api-response');
  }

  /**
   * Register the event listener for the event.
   *
   * @return void
   */
  public function registerResponseMacro(): void
  {
    Response::macro('success', function ($result, $status = 200) {
      return tap(new \Mesak\LaravelApiResponse\ResponseServiceProvider::$responseClass($result), function ($response) use ($status) {
        $response->setStatusCode($status);
      });
    });
    Response::macro('error', function ($exception, $status = 500) {
      return tap(new \Mesak\LaravelApiResponse\ResponseServiceProvider::$responseClass($exception), function ($response) use ($status) {
        $response->setStatusCode($status);
      });
    });
  }
}