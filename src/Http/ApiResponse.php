<?php

namespace Mesak\LaravelApiResponse\Http;

use Illuminate\Http\JsonResponse as BaseJsonResponse;
class ApiResponse extends BaseJsonResponse
{
  public function setData(mixed $data = []): static
  {
    return parent::setData(tap(new \Mesak\LaravelApiResponse\ResponseServiceProvider::$responseSchema($data))->toArray());
  }
}
