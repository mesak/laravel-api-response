<?php

namespace Mesak\LaravelApiResponse\Http;

use Illuminate\Http\JsonResponse;
use Mesak\LaravelApiResponse\ResponseServiceProvider;

class ApiResponse extends JsonResponse
{
  /**
   * {@inheritdoc}
   *
   * @return static
   */
  public function setData(mixed $data = []): static
  {
    return parent::setData(tap(new ResponseServiceProvider::$responseSchema($data))->toArray());
  }
}
