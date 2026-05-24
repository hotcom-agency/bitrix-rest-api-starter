<?php

namespace Hotcom\Controllers;

use Hotcom\Controllers\ApiController;
use Hotcom\Services\RequestService;
use Hotcom\Dto\RequestCreateDto;
use Hotcom\Helpers\ApiCache;

/**
 * Контроллер для обработки входящих заявок и запросов
 * 
 * @package Hotcom\Controllers
 */
class RequestController extends ApiController
{
  private RequestService $requestService;

  /**
   * Инициализация контроллера и подключение почтового сервиса заявок
   */
  public function __construct()
  {
    parent::__construct();
    $this->requestService = new RequestService(new ApiCache());
  }

  /**
   * Создание новой заявки на основе входящих параметров формы
   *
   * @return never
   */
  public function create(): never
  {
    $params = array_merge(
      $this->request->getQueryList()->toArray(),
      $this->request->getPostList()->toArray()
    );

    $dto = RequestCreateDto::fromArray($params);

    $this->validateDto($dto);

    $result = $this->requestService->create($dto);

    if ($result) {
      $this->apiResponse($result);
    }

    $this->apiResponseError(400);
  }
}
