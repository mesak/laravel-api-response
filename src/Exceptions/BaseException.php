<?php

namespace Mesak\LaravelApiResponse\Exceptions;

use Exception;

class BaseException extends Exception
{
  protected $errorCode = 500;
  protected $statusCode = 500;

  public function getStatusCode(): int
  {
    return $this->statusCode;
  }
  public function getErrorCode(): int
  {
    return $this->errorCode;
  }
}
