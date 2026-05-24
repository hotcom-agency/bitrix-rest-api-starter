<?php

namespace Hotcom\Controllers;

use Bitrix\Main\Context;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\HttpResponse;
use Hotcom\Helpers;

/**
 * Базовый контроллер для обработки запросов REST API
 * 
 * @package Hotcom\Controllers
 */
class ApiController
{
  /**
   * @var \Bitrix\Main\Server 
   */
  public $server;

  /**
   * @var \Bitrix\Main\HttpRequest
   */
  public \Bitrix\Main\HttpRequest $request;

  /**
   * @var HttpResponse 
   */
  public $response;

  /**
   * @var \Bitrix\Main\Web\HttpHeaders
   */
  public $headers;

  /** 
   * @var mixed 
   */
  protected $result = '';

  /** 
   * @var int 
   */
  protected $responseCode = 200;

  /**
   * Инициализация контекста Битрикса и базовых свойств контроллера
   */
  public function __construct()
  {
    $context = Context::getCurrent();

    if (!$context) {
      throw new \RuntimeException('Bitrix context not found');
    }

    /** 
     * @var HttpResponse $response 
     */
    $response = $context->getResponse();

    $this->response = $response;
    $this->headers = $this->response->getHeaders();
    $this->server = $context->getServer();

    /** @var \Bitrix\Main\HttpRequest $request */

    $request = $context->getRequest();
    $this->request = $request;

    if ($this->access() !== true) {
      $this->apiResponseError(401);
    }
  }

  /**
   * Проверка прав доступа по авторизационному токену Api-Token
   * 
   * @return bool
   */
  protected function access(): bool
  {
    $apiToken = (string)$this->server->get('BITRIX_API_TOKEN');

    if ($apiToken === '') {
      return false;
    }

    $headerApiToken = '';
    if ($this->request instanceof HttpRequest) {
      $headerApiToken = (string)$this->request->getHeader('Api-Token');
    }

    return hash_equals($apiToken, $headerApiToken);
  }

  /**
   * Формирование и отправка ошибочного ответа API
   * 
   * @param int $status
   * @param array<mixed> $details
   * @return never
   */
  public function apiResponseError(int $status, array $details = [])
  {
    /** @var array<int, array{name: string, message: string}> $errors */
    $errors = [
      400 => [
        'name' => 'BAD_REQUEST',
        'message' => 'This response is sent when a request conflicts with the current state of the server.'
      ],
      401 => [
        'name' => 'UNAUTHORIZED',
        'message' => 'The request was valid, but authentication is required and has failed or has not yet been provided.'
      ],
      403 => [
        'name' => 'FORBIDDEN',
        'message' => 'The request was valid, but the server is refusing action.'
      ],
      404 => [
        'name' => 'NOT_FOUND',
        'message' => 'The requested resource could not be found but may be available in the future. Subsequent requests by the client are permissible.'
      ],
      405 => [
        'name' => 'METHOD_NOT_ALLOWED',
        'message' => 'The server knows the request method, but the target resource doesn`t support this method.'
      ],
      409 => [
        'name' => 'CONFCLICT',
        'message' => 'The request was well-formed but was unable to be followed due to semantic errors.'
      ],
      422 => [
        'name' => 'UNPROCESSABLE_ENTITY',
        'message' => 'The request was well-formed but was unable to be followed due to semantic errors.'
      ],
      429 => [
        'name' => 'TOO_MANY_REQUESTS',
        'message' => 'The user has sent too many requests in a given amount of time ("rate limiting").'
      ],
      500 => [
        'name' => 'INTERNAL_ERROR',
        'message' => 'The server encountered an unexpected condition that prevented it from fulfilling the request.'
      ]
    ];

    $this->responseCode = $status;

    $errorInfo = $errors[$status] ?? ['name' => 'UNKNOWN_ERROR', 'message' => 'Unknown error occurred'];

    $this->result = [
      'data' => null,
      'error' => [
        'status' => $this->responseCode,
        'name' => $errorInfo['name'],
        'message' => $errorInfo['message'],
        'details' => $details,
      ]
    ];

    $this->result();
  }

  /**
   * Формирование и отправка успешного ответа API
   * 
   * @param mixed $data
   * @param mixed $meta
   * @return never
   */
  public function apiResponse(mixed $data, mixed $meta = null)
  {
    if (is_array($data)) {
      $data = Helpers\Bitrix::arrKeysToLower($data);
    }

    $this->result = ['data' => $data];

    if ($meta !== null) {
      $this->result['meta'] = $meta;
    }

    $this->result();
  }

  /**
   * Вывод итогового JSON-результата и завершение выполнения скрипта
   * 
   * @return never
   */
  public function result(): never
  {
    $this->response->setStatus($this->responseCode);
    $this->response->addHeader('Content-Type', 'application/json; charset=utf-8');

    $json = json_encode($this->result, JSON_UNESCAPED_UNICODE);
    $this->response->setContent($json ?: '');

    $this->response->send();

    require_once $this->server->getDocumentRoot() . '/bitrix/modules/main/include/epilog_after.php';
    exit;
  }

  /**
   * Автоматическая валидация переданного DTO-объекта
   * 
   * @param object $dto Объект DTO для проверки
   * @return void
   */
  protected function validateDto(object $dto): void
  {
    if (method_exists($dto, 'isValid') && !$dto->isValid()) {

      $errors = method_exists($dto, 'getErrors')
        ? $dto->getErrors()
        : ['fields' => 'The given data was invalid.'];

      $this->apiResponseError(422, $errors);
    }
  }
}
