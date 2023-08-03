<?php

namespace Mesak\LaravelApiResponse\Http;

use JsonSerializable;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
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
  protected $resultMeta;
  protected $message;
  protected $exception;
  protected $errorCode;

  /**
   * 初始化 result
   *
   * @param mixed $result
   */
  public function __construct(mixed $result)
  {
    /**
     * @see \Illuminate\Database\Eloquent\Collection
     */
    if ($result instanceof Throwable) {
      $this->setException($result);
    } else {
      $this->setResult($result);
    }
  }

  /**
   * 設定 Exception
   * 
   * @param Throwable $exception
   * @return void
   */
  public function setException(Throwable $exception): void
  {
    $this->exception = $exception;
    $this->message = $exception->getMessage();
    if ($this->message === '') {
      $this->message = config('api-response.exception_empty_show_title', true)
        ? Str::of(class_basename($exception))->headline()
        : 'Internal Server Error';
    }
    $errorCode = ($this->exception instanceof BaseException) ? $this->exception->getErrorCode() : '0';
    $this->setFail((string) $errorCode);
  }

  /**
   * 取得 Exception
   *
   * @return Throwable|null
   */
  public function getException(): ?Throwable
  {
    return $this->exception;
  }

  /**
   * 取得錯誤訊息
   * 
   * @return string|null
   */
  public function getExceptionError(): ?array
  {
    $errors = null;
    if (config('app.env') !== 'production' && is_null($this->getException()) === false) {
      $errors = [
        'file' => $this->getException()->getFile(),
        'line' => $this->getException()->getLine(),
        'code' => $this->getException()->getCode(),
        'params' => request()->all()
      ];

      if ($limit = config('api-response.exception_trace_limit', 0)) {
        $errors['trace'] = array_slice($this->getException()->getTrace(), 0, $limit);
      } else {
        $errors['trace'] = $this->getException()->getTrace();
      }
    }
    return $errors;
  }

  /**
   * 設定 錯誤並設定錯誤代碼
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
   * 設定 錯誤代碼
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
   * 取得 成功狀態
   *
   * @return boolean
   */
  public function getSuccess(): bool
  {
    return $this->success;
  }

  /**
   * 設定 結果
   *
   * @param mixed $data
   * @return void
   */
  public function setResult(mixed $data): void
  {
    if ($data instanceof \ArrayObject && $data->count() === 0) {
      //JsonResponse will set data to empty ArrayObject so do nothing
    } else {

      $this->result = $data;
    }
  }

  /**
   * 取得 結果
   *
   * @return mixed
   */
  public function getResult(): mixed
  {
    $result = $this->result;
    if (
      is_object($this->result) &&
      $this->result instanceof ResourceCollection &&
      ($this->result->resource instanceof AbstractPaginator || $this->result->resource instanceof AbstractCursorPaginator)
    ) {
      $result = data_get($this->result->response()->getData(), 'data', []);
    }
    return $result;
  }

  /**
   * 取得 結果Meta
   *
   * @return mixed|null
   */
  public function getResultMeta(): mixed
  {
    $resultMeta = $this->resultMeta;

    if (
      is_object($this->result) &&
      $this->result instanceof ResourceCollection &&
      ($this->result->resource instanceof AbstractPaginator || $this->result->resource instanceof AbstractCursorPaginator)
    ) {

      $resultMeta = data_get($this->result->response()->getData(), 'meta');
    }

    return $resultMeta;
  }

  /**
   * 取得 結果類型
   *
   * @return string|null
   */
  public function getResultType(): ?string
  {
    $resultType = gettype($this->result);

    if ($resultType === 'NULL') {

      return null;
    }

    if (in_array($resultType, ['integer', 'double'])) {

      return 'number';
    }

    if (is_object($this->result)) {

      $resultType = 'resource';

      if ($this->result instanceof Collection || $this->result instanceof ResourceCollection) {

        $resultType = 'collection';
      } elseif ($this->result instanceof Model ||  $this->result instanceof \stdClass || $this->result instanceof JsonSerializable || (is_array($this->result) && !Arr::isList($this->result))) {

        $resultType = 'resource';
      } elseif ($this->result instanceof \ArrayObject || $this->result instanceof Arrayable) {

        $resultType = 'collection';
      }
    } elseif (is_array($this->result)) {

      $resultType = Arr::isList($this->result) ? 'collection' : 'resource';
    }

    return $resultType;
  }

  /**
   * 設定 訊息
   *
   * @param string $message
   * @return void
   */
  public function setMessage(string $message): void
  {
    $this->message = $message;
  }

  /**
   * 取得 訊息
   *
   * @return string|null
   */
  public function getMessage(): ?string
  {
    return $this->message;
  }

  /**
   * 產生 結果內容
   *
   * @return array
   */
  public function toArray(): array
  {
    return Arr::where([
      'status'           => $this->getSuccess() ? 'success' : 'error',
      'error_code'       => $this->getErrorCode(),
      'message'          => $this->getMessage(),
      'result_type'      => $this->getResultType(),
      'result'           => $this->getResult(),
      'result_meta'      => $this->getResultMeta(),
      'exception'        => $this->getExceptionError(),
    ], function ($value) {
      return !is_null($value);
    });
  }
}
