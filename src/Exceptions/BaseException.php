<?php

namespace Mesak\LaravelApiResponse\Exceptions;

use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

class BaseException extends HttpException
{
  protected $errorCode = SymfonyResponse::HTTP_INTERNAL_SERVER_ERROR; // 500
  protected $statusCode = SymfonyResponse::HTTP_INTERNAL_SERVER_ERROR; // 500

  public function __construct(?string $message = '' ,$errorCode = null){

    if($errorCode){
      $this->errorCode = $errorCode;
    }
    parent::__construct($this->statusCode, $message);
  }

  public function getErrorCode(): string
  {
    return (string)$this->errorCode;
  }
}