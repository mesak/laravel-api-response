<?php

namespace Mesak\LaravelApiResponse\Http;

use JsonSerializable;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Stringable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Pagination\AbstractCursorPaginator;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Mesak\LaravelApiResponse\Exceptions\BaseException;
use Throwable;

class ApiResponseSchema implements Arrayable
{
  protected $success = true;
  protected $result;
  protected $resultType;
  protected $message;
  protected $throwable;
  protected $errorCode;

  /**
   * 初始化 result
   *
   * @param [type] $result
   */
  public function __construct(mixed $result)
  {
    /**
     * @see \Illuminate\Database\Eloquent\Collection
     */
    if ($result instanceof Throwable) {
      $this->setThrowable($result);
    } else {
      $this->setResult($result);
    }
  }


  /**
   * 設定 Throwable
   */
  public function setThrowable(Throwable $throwable): void
  {
    $this->throwable = $throwable;
    $this->message  = $throwable->getMessage();
    if ($this->message === '') {
      $this->message = config('api-response.exception_empty_show_title', true) ? Str::of(class_basename($throwable))->headline() : 'Internal Server Error';
    }
    $errorCode = ($this->throwable instanceof BaseException) ? $this->throwable->getErrorCode() : '0';
    $this->setFail((string) $errorCode);
  }

  public function getThrowable(): ?Throwable
  {
    return $this->throwable;
  }

  /**
   * 取得錯誤訊息
   */
  public function getThrowableError(): ?array
  {
    $errors = null;
    if (config('app.env') !== 'production' && is_null($this->getThrowable()) === false) {
      $errors = [
        'file' => $this->getThrowable()->getFile(),
        'line' => $this->getThrowable()->getLine(),
        'code' => $this->getThrowable()->getCode(),
        'params' => request()->all()
      ];
      if ($limit = config('api-response.exception_trace_limit', 0)) {
        $errors['trace'] = array_slice($this->getThrowable()->getTrace(), 0, $limit);
      } else {
        $errors['trace'] = $this->getThrowable()->getTrace();
      }
    }
    return $errors;
  }

  /**
   * 設定 Error Code
   *
   * @param string $code
   * @return void
   */
  public function setFail(string $code = '0'): void
  {
    $this->success = false;
    $this->errorCode = $code;
  }

  /**
   * set errorCode
   *
   * @param string $errorCode
   * @return void
   */
  public function setErrorCode(string $errorCode): void
  {
    $this->errorCode = $errorCode;
  }

  /**
   * 取得 errorCode
   *
   * @return ?string
   */
  public function getErrorCode(): ?string
  {
    return $this->errorCode == '0' ? null : $this->errorCode;
  }

  /**
   * get success
   *
   * @return boolean
   */
  public function getSuccess(): bool
  {
    return $this->success;
  }

  /**
   * set result
   *
   * @param mixed $data
   * @return void
   */
  public function setResult(mixed $data): void
  {
    if ($data instanceof \ArrayObject && $data->count() === 0) {
      //JsonResponse will set data to empty ArrayObject so do nothing
    } else if ($data instanceof AbstractPaginator || $data instanceof AbstractCursorPaginator) {
      $this->resultType = 'collection';
      $this->result = AnonymousResource::collection($data);
    } elseif ($data instanceof Collection || $data instanceof ResourceCollection) {
      $this->resultType = 'collection';
      $this->result = $data;
    } elseif (
      $data instanceof Model ||
      $data instanceof \stdClass ||
      $data instanceof JsonSerializable ||
      (is_array($data) && !Arr::isList($data)
      )
    ) {
      $this->resultType = 'resource';
      $this->result = $data;
    } elseif (
      $data instanceof \ArrayObject ||
      $data instanceof Arrayable ||
      is_array($data)
    ) {
      $this->resultType = 'collection';
      $this->result = $data;
    } elseif (is_string($data) || $data instanceof Stringable) {
      $this->resultType = 'string';
      $this->message = $data;
    } else if (is_bool($data)) {
      $this->resultType = 'boolean';
      $this->result = $data;
    } else {
      if (gettype($data) !== 'NULL') {
        $this->resultType = gettype($data);
      }
      $this->result = $data;
    }
  }

  /**
   * get result
   *
   * @return mixed
   */
  public function getResult(): mixed
  {
    if ($this->resultType === 'collection') {
      $result = $this->result;
      if (
        $this->result instanceof \ArrayObject ||
        $this->result instanceof Arrayable ||
        is_array($this->result)
      ) {
        //is array
      } else if (
        $this->result instanceof AbstractPaginator || $this->result instanceof AbstractCursorPaginator ||
        ($this->result->resource &&
          $this->result->resource instanceof AbstractPaginator ||
          $this->result->resource instanceof AbstractCursorPaginator
        )
      ) {
        $response = $this->result->response()->getData();
        $result = [
          'data' => $response->data,
          'meta' => $response->meta,
        ];
      }
      return $result;
    }
    return $this->result;
  }

  /**
   * get result type
   *
   * @return ?string
   */
  public function getResultType(): ?string
  {
    return $this->resultType;
  }

  /**
   * set message
   *
   * @param string $message
   * @return void
   */
  public function setMessage(string $message): void
  {
    $this->message = $message;
  }

  /**
   * get message
   *
   * @return string
   */
  public function getMessage(): ?string
  {
    return $this->message;
  }

  /**
   * 產生 array 資料表
   *
   * @return
   */
  public function toArray(): array
  {
    return Arr::where([
      'status' => $this->getSuccess() ? 'success' : 'error',
      'error_code' => $this->getErrorCode(),
      'message' => $this->getMessage(),
      'result_type' => $this->getResultType(),
      'result' => $this->getResult(),
      'exception' => $this->getThrowableError(),
    ], function ($value, $key) {
      return !is_null($value);
    });
  }
}
