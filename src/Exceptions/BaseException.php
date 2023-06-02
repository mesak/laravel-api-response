<?php

namespace Mesak\LaravelApiResponse\Exceptions;

use Exception;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class BaseException extends Exception
{
  protected $errorCode = SymfonyResponse::HTTP_INTERNAL_SERVER_ERROR; // 500
  protected $statusCode = SymfonyResponse::HTTP_INTERNAL_SERVER_ERROR; // 500

  public function getStatusCode(): int
  {
    return $this->statusCode;
  }
  public function getErrorCode(): string
  {
    return (string)$this->errorCode;
  }
}
