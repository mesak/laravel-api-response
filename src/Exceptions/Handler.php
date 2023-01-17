<?php

namespace Mesak\LaravelApiResponse\Exceptions;

use Illuminate\Http\Request;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Mesak\LaravelApiResponse\Exceptions\BaseException;
use Throwable;

class Handler extends ExceptionHandler
{
  /**
   * Check if the given request is an API request.
   */
  public function isApiRequest(Request $request): bool
  {
    return $request->is(config('api-response.paths', []));
  }
  /**
   * Render an exception into an HTTP response.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  \Throwable  $e
   * @return \Symfony\Component\HttpFoundation\Response
   *
   * @throws \Throwable
   */
  public function render($request, Throwable $exception)
  {
    if ($this->isApiRequest($request)) {
      $statusCode = 400;
      if ($exception instanceof BaseException) {
        $statusCode = $exception->getStatusCode();
      }
      return response()->error($exception, $statusCode);
    }
    return parent::render($request, $exception);
  }
}
