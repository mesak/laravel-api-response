<?php

namespace Mesak\LaravelApiResponse\Http;

use JsonSerializable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Stringable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Pagination\AbstractCursorPaginator;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Exception;
use Mesak\LaravelApiResponse\Exceptions\BaseException;

class ApiResponseSchema implements Arrayable
{
  protected $success = true;
  protected $result;
  protected $resultType;
  protected $resultMeta;
  protected $message;
  protected $exception;
  protected $errorCode;

  /**
   * 初始化 result
   *
   * @param [type] $result
   */
  public function __construct($result = null)
  {
    /**
     * @see \Illuminate\Database\Eloquent\Collection
     */
    if ($result instanceof Exception) {
      $this->setException($result);
    } else {
      $this->setResult($result);
    }
  }

  /**
   * 設定 exception
   */
  public function setException(Exception $exception): void
  {
    $this->exception = $exception;
    $this->message  = $exception->getMessage();
    $errorCode = ($this->exception instanceof BaseException) ? $this->exception->getErrorCode() : 500;
    $this->setFail((string) $errorCode);
  }

  public function getException(): ?Exception
  {
    return $this->exception;
  }

  /**
   * 取得錯誤訊息
   */
  public function getExceptionError(): ?array
  {
    $errors = null;
    if (config('app.env') !== 'production' && is_null($this->getException()) === false) {
      $errors =   [
        'file' => $this->getException()->getFile(),
        'line' => $this->getException()->getLine(),
        'code' => $this->getException()->getCode(),
        'params' => request()->all()
      ];
      if ($limit =  config('api-response.exception_trace_limit', 0)) {
        $errors['trace'] = array_slice($this->getException()->getTrace(), 0, $limit);
      } else {
        $errors['trace'] = $this->getException()->getTrace();
      }
    }
    return $errors;
  }

  /**
   * 設定 Error Code
   *
   * @param integer $code
   * @return void
   */
  public function setFail(string $code = '100'): void
  {
    $this->success = false;
    $this->errorCode = $code;
  }

  /**
   * set errorCode
   *
   * @param [type] $errorCode
   * @return void
   */
  public function setErrorCode($errorCode): void
  {
    $this->errorCode = $errorCode;
  }

  /**
   * 取得 errorCode
   *
   * @return int
   */
  public function getErrorCode(): ?string
  {
    return $this->errorCode;
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
   * @param [type] $data
   * @return void
   */
  public function setResult($data = null): void
  {
    if ($data instanceof AbstractPaginator || $data instanceof AbstractCursorPaginator) {
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
    } else {
      // dd(gettype($data));
      if (gettype($data) !== 'NULL') {
        $this->resultType = gettype($data);
      }
      $this->result = $data;
    }
  }

  /**
   * get result
   *
   * @return null|array
   */
  public function getResult(): null|array|object
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
   * Undocumented function
   *
   * @return void
   */
  public function getResultType(): ?string
  {
    return $this->resultType;
  }

  /**
   * set message
   *
   * @param string $data
   * @return void
   */
  public function setMessage(string $data): void
  {
    $this->message =  $data;
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
      'exception' => $this->getExceptionError(),
    ], function ($value, $key) {
      return !is_null($value);
    });
  }
}
