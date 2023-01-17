<?php

namespace Mesak\LaravelApiResponse\Exceptions;

use Exception;

class BaseException extends Exception
{
  protected $errorCode = 400;
  protected $statusCode = 400;

  public function getStatusCode(): int
  {
    return $this->statusCode;
  }
  public function getErrorCode(): int
  {
    return $this->errorCode;
  }
}
