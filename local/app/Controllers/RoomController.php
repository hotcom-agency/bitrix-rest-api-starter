<?php
namespace Hotcom\Controllers;

use Hotcom\Services\RoomService;
use Hotcom\Dto\RoomFilterDto;
use Hotcom\Dto\PaginationDto;
use Hotcom\Helpers\ApiCache;

/**
 * Контроллер для управления номерами и получения агрегированных данных фильтрации
 * 
 * @package Hotcom\Controllers
 */
class RoomController extends ApiController
{
  private RoomService $roomService;

  /**
   * Инициализация контроллера и подключение сервиса номеров
   */
  public function __construct()
  {
    parent::__construct();
    $this->roomService = new RoomService(new ApiCache());
  }

  /**
   * Получение списка номеров с учетом фильтрации и пагинации
   * 
   * @return never
   */
  public function index(): never
  {
    $filterDto = $this->buildFilterDto();

    $paginationDto = PaginationDto::fromArray([
      'page' => $this->request->get('page'),
      'limit' => $this->request->get('limit')
    ]);

    $result = $this->roomService->getRooms($filterDto, $paginationDto);

    $this->apiResponse($result['data'], $result['pagination']);
  }

  /**
   * Отображение детальной информации о номере по его символьному коду
   * 
   * @param string $slug Символьный код номера
   * @return never
   */
  public function show(string $slug): never
  {
    $room = $this->roomService->getRoomByCode($slug);

    if ($room) {
      $this->apiResponse($room);
    }

    $this->apiResponseError(404);
  }

  /**
   * Формирование фасетов и агрегированных статистических данных для фильтров
   * 
   * @return never
   */
  public function facets(): never
  {
    $filterDto = $this->buildFilterDto();

    $result = $this->roomService->getFacets($filterDto);

    $this->apiResponse($result);
  }

  /**
   * Сборка DTO фильтра из параметров текущего запроса
   * 
   * @return RoomFilterDto
   */
  protected function buildFilterDto(): RoomFilterDto
  {
    return RoomFilterDto::fromArray([
      'capacity_min' => $this->request->get('capacity_min'),
      'capacity_max' => $this->request->get('capacity_max'),
      'area_min' => $this->request->get('area_min'),
      'area_max' => $this->request->get('area_max'),
      'bed_types' => $this->request->get('bed_types') ? explode(',', $this->request->get('bed_types')) : [],
      'search' => $this->request->get('search')
    ]);
  }
}
