<?php

namespace Mesak\LaravelApiResponse\Exceptions;

use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class BadRequestException extends BaseException
{
  protected $errorCode = SymfonyResponse::HTTP_BAD_REQUEST; // 400
  protected $statusCode = SymfonyResponse::HTTP_BAD_REQUEST; // 400
}
