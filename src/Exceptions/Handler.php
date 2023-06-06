<?php

namespace Mesak\LaravelApiResponse\Exceptions;

use App\Exceptions\Handler as ExceptionHandler;
use Mesak\LaravelApiResponse\Exceptions\BaseException;
use Illuminate\Http\Request;
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

      $e = $this->prepareException($this->mapException($exception));

      if (method_exists($this, 'renderViaCallbacks')) {
        $response = $this->renderViaCallbacks($request, $e);
        if ($response) {
          return $response;
        }
      } else {
        foreach ($this->renderCallbacks as $renderCallback) {
          foreach ($this->firstClosureParameterTypes($renderCallback) as $type) {
            if (is_a($e, $type)) {
              $response = $renderCallback($e, $request);

              if (!is_null($response)) {
                return $response;
              }
            }
          }
        }
      }

      return response()->error($e);
    }
    return parent::render($request, $exception);
  }
}
