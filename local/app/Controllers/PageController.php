<?php

namespace Hotcom\Controllers;

use Hotcom\Controllers\ApiController;
use Hotcom\Services\PageService;

/**
 * Контроллер для управления страницами сайта
 * 
 * @package Hotcom\Controllers
 */
class PageController extends ApiController
{
  private PageService $pageService;

  /**
   * Инициализация контроллера и подключение сервиса страниц
   */
  public function __construct()
  {
    parent::__construct();
    $this->pageService = new PageService;
  }

  /**
   * Отображение детальной информации о странице по её символьному коду
   *  
   * @param string $slug Символьный код страницы
   * @return never
   */
  public function show(string $slug): never
  {
    $result = $this->pageService->find($slug);

    if ($result) {
      $this->apiResponse($result);
    }

    $this->apiResponseError(404);
  }
}
