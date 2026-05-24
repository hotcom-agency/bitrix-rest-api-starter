<?php

namespace Hotcom\Services;

use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Query\QueryHelper;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Iblock\Elements\ElementRoomsTable;
use Bitrix\Iblock\Elements\EO_ElementRooms;
use Hotcom\Dto\RoomFilterDto;
use Hotcom\Dto\PaginationDto;
use Hotcom\Dto\RoomFacetsDto;
use Hotcom\Dto\RoomDto;
use Hotcom\Helpers\Bitrix;
use Hotcom\Helpers\Image;
use Hotcom\Helpers\Localization;

/**
 * Сервис для работы с инфоблоком "Номера"
 *
 * @package Hotcom\Services
 */
class RoomService extends AbstractService
{
  protected string $iblockCode = 'rooms';

  protected array $defaultFields = [
    'ID',
    'CODE',
    'ACTIVE',
    'SORT',
    'IMAGE',
    'GALLERY',
    'CAPACITY',
    'AREA',
    'BED_TYPE.ITEM',
    'NAME' => 'localized',
    'DESCRIPTION' => 'localized',
  ];

  /**
   * Получение списка элементов с фильтрацией и пагинацией
   */
  public function getRooms(RoomFilterDto $filterDto, PaginationDto $paginationDto): array
  {
    return $this->apiCache->get(
      key: $this->buildCacheKey('list', [$filterDto, $paginationDto->page, $paginationDto->limit]),
      callback: function () use ($filterDto, $paginationDto) {
        $baseFilter = $this->buildFilter($filterDto);
        $query = ElementRoomsTable::query()
          ->setFilter($baseFilter)
          ->setSelect($this->getSelect())
          ->setLimit($paginationDto->limit)
          ->setOffset($paginationDto->getOffset())
          ->setOrder(['SORT' => 'ASC', 'ID' => 'ASC']);

        $collection = QueryHelper::decompose($query, true);
        $totalCount = ElementRoomsTable::getCount($baseFilter);
        $rooms = [];

        foreach ($collection as $room) {
          $rooms[] = $this->mapToDto($room);
        }

        return [
          'data' => $rooms,
          'pagination' => [
            'page' => $paginationDto->page,
            'limit' => $paginationDto->limit,
            'total' => $totalCount,
            'pages' => (int)ceil($totalCount / $paginationDto->limit),
          ],
        ];
      },
      tags: $this->getCacheTags()
    );
  }

  /**
   * Получение элемента по символьному коду (slug)
   */
  public function getRoomByCode(string $slug): ?RoomDto
  {
    return $this->apiCache->get(
      key: $this->buildCacheKey('detail', $slug),
      callback: function () use ($slug) {
        $query = ElementRoomsTable::query()
          ->setFilter($this->buildFilter(new RoomFilterDto(search: null), ['=CODE' => $slug]))
          ->setSelect($this->getSelect());

        $collection = QueryHelper::decompose($query, true);
        $collection->rewind();
        $room = $collection->valid() ? $collection->current() : null;

        return $room ? $this->mapToDto($room) : null;
      },
      tags: $this->getCacheTags()
    );
  }

  /**
   * Формирование фасетов (агрегированных данные) для фильтров
   */
  public function getFacets(RoomFilterDto $filterDto): RoomFacetsDto
  {
    return $this->apiCache->get(
      key: $this->buildCacheKey('facets', $filterDto),
      callback: function () use ($filterDto) {
        $baseFilter = $this->buildFilter($filterDto);
        $totalCount = ElementRoomsTable::getCount($baseFilter);

        $bedTypesResult = ElementRoomsTable::getList([
          'filter' => $baseFilter,
          'select' => ['BED_TYPE_XML_ID' => 'BED_TYPE.ITEM.XML_ID'],
          'group' => ['BED_TYPE_XML_ID'],
          'order' => ['BED_TYPE_XML_ID' => 'ASC'],
        ]);
        $availableBedTypes = [];
        while ($bedType = $bedTypesResult->fetch()) {
          if (!empty($bedType['BED_TYPE_XML_ID'])) {
            $availableBedTypes[] = $bedType['BED_TYPE_XML_ID'];
          }
        }

        $stats = ElementRoomsTable::getList([
          'filter' => $baseFilter,
          'runtime' => [
            new ExpressionField('MIN_CAPACITY', 'MIN(%s)', ['CAPACITY.VALUE']),
            new ExpressionField('MAX_CAPACITY', 'MAX(%s)', ['CAPACITY.VALUE']),
            new ExpressionField('MIN_AREA', 'MIN(%s)', ['AREA.VALUE']),
            new ExpressionField('MAX_AREA', 'MAX(%s)', ['AREA.VALUE']),
          ],
          'select' => ['MIN_CAPACITY', 'MAX_CAPACITY', 'MIN_AREA', 'MAX_AREA'],
        ])->fetch();

        return new RoomFacetsDto(
          totalCount: $totalCount,
          bedTypes: array_values(array_unique($availableBedTypes)),
          minCapacity: $stats['MIN_CAPACITY'] ? (int)$stats['MIN_CAPACITY'] : null,
          maxCapacity: $stats['MAX_CAPACITY'] ? (int)$stats['MAX_CAPACITY'] : null,
          minArea: isset($stats['MIN_AREA']) ? (float)$stats['MIN_AREA'] : null,
          maxArea: isset($stats['MAX_AREA']) ? (float)$stats['MAX_AREA'] : null,
        );
      },
      tags: $this->getCacheTags()
    );
  }

  /**
   * Сборка массива фильтрации на основе входящего DTO
   */
  private function buildFilter(RoomFilterDto $filterDto, array $extraFilter = []): array
  {
    $postfix = Localization::getPostfix();
    $nameConditionKey = empty($postfix) ? '!NAME' : '!NAME' . $postfix . '.VALUE';

    $filter = [
      'ACTIVE' => 'Y',
      $nameConditionKey => '',
    ];

    if ($filterDto->capacityMin !== null) {
      $filter[] = ['>=CAPACITY.VALUE' => $filterDto->capacityMin];
    }
    if ($filterDto->capacityMax !== null) {
      $filter[] = ['<=CAPACITY.VALUE' => $filterDto->capacityMax];
    }
    if ($filterDto->areaMin !== null) {
      $filter[] = ['>=AREA.VALUE' => $filterDto->areaMin];
    }
    if ($filterDto->areaMax !== null) {
      $filter[] = ['<=AREA.VALUE' => $filterDto->areaMax];
    }
    if (!empty($filterDto->bedTypes)) {
      $filter['BED_TYPE.ITEM.XML_ID'] = $filterDto->bedTypes;
    }
    if ($filterDto->search !== null && $filterDto->search !== '') {
      $searchKey = empty($postfix) ? '%NAME' : '%NAME' . $postfix . '.VALUE';
      $filter[] = [$searchKey => $filterDto->search];
    }

    return array_merge($filter, $extraFilter);
  }

  /**
   * Преобразование объекта сущности Битрикса в объект RoomDto
   */
  private function mapToDto(EO_ElementRooms&EntityObject $element): RoomDto
  {
    return new RoomDto(
      id: (int) $element->getId(),
      slug: (string) $element->getCode(),
      sort: (int) $element->getSort(),
      name: (string) Localization::getPostfix() ? $this->getLocalizedValue($element, 'getName') : $element->getName(),
      description: (string) Bitrix::htmlTypeDecode($this->getLocalizedValue($element, 'getDescription')),
      image: ($img = $element->getImage()?->getValue()) ? Image::getThumbs((int)$img) : null,
      gallery: $element->getGallery() ? array_map(fn($id) => Image::getThumbs((int)$id), $element->getGallery()->getValueList() ?? []) : [],
      capacity: (int) $element->getCapacity()?->getValue(),
      area: (int) $element->getArea()?->getValue(),
      bedTypes: $element->getBedType() ? array_map(fn($item) => (string)$item->getXmlId(), $element->getBedType()->getItemList() ?? []) : [],
    );
  }
}
