<?php

namespace Hotcom\Services;

use Hotcom\Helpers\Image;
use Hotcom\Helpers\Bitrix;
use Hotcom\Helpers\ApiCache;
use Bitrix\Main\Loader;
use Bitrix\Main\SystemException;

/**
 * Сервис для сборки и вывода структуры динамических текстовых страниц
 * 
 * @package Hotcom\Services
 */
class PageService extends AbstractService
{
  protected string $iblockCode = 'page_dynamic_code';

  /**
   * Текущий объект HTTP-запроса Битрикса
   * 
   * @var \Bitrix\Main\Request
   */
  public $request;

  /**
   * Инициализация сервиса и сохранение глобального объекта запроса
   */
  public function __construct(ApiCache $apiCache)
  {
    $this->request = \Bitrix\Main\Context::getCurrent()->getRequest();
    parent::__construct($apiCache);
  }

  /**
   * Поиск страницы по символьному коду с агрегацией данных раздела и его элементов
   * 
   * @param string $slug Символьный код страницы
   * @param string|null $property_id Идентификатор связанного объекта
   * @return array|null Структурированный массив данных страницы или null
   * @throws SystemException При отсутствии обязательного модуля инфоблоков
   */
  public function find(string $slug, string|null $property_id = null)
  {
    if (!Loader::includeModule('iblock')) {
      throw new SystemException('Отсутствует обязательный модуль "iblock".');
    }

    $sectionCode = 'page-' . (!empty($property_id) ? 'property-' : null) . $slug;

    $sectionData = $this->apiCache->get(
      key: $this->buildCacheKey('meta', $sectionCode),
      callback: function () use ($sectionCode) {
        $data = \Bitrix\Iblock\SectionTable::getList([
          'select' => ['ID', 'IBLOCK_ID'],
          'filter' => ['=CODE' => $sectionCode]
        ])->fetch();

        return $data ?: null;
      },
      tags: ['section_meta'],
      ttl: 86400
    );

    if (!$sectionData) {
      return null;
    }

    $iblockId = (int)$sectionData['IBLOCK_ID'];
    $sectionId = (int)$sectionData['ID'];

    $this->iblockId = $iblockId;
    $this->iblockCode = $sectionCode;

    $cacheKey = $this->buildCacheKey('find', $sectionCode);
    $cacheTags = $this->getCacheTags(['section_' . $sectionCode]);

    return $this->apiCache->get(
      key: $cacheKey,
      callback: function () use ($iblockId, $sectionId) {
        $entity = \Bitrix\Iblock\Iblock::wakeUp($iblockId)->getEntityDataClass();

        // Получение данных раздела инфоблока
        $section = \Bitrix\Iblock\Model\Section::compileEntityByIblock($iblockId)::getList(array(
          'select' => ['ID', 'CODE', 'PICTURE', 'DESCRIPTION', 'UF_*'],
          'filter' => ['ID' => $sectionId, 'ACTIVE' => 'Y'],
        ))->fetch();

        if (!$section) {
          return null;
        }

        $sectionUf = \Bitrix\Main\UserFieldTable::getList(array(
          'filter' => ['ENTITY_ID' => 'IBLOCK_' . $iblockId . '_SECTION'],
          'select' => ['ID', 'FIELD_NAME', 'SETTINGS', 'MULTIPLE', 'USER_TYPE_ID']
        ))->fetchAll();

        $sectionProps = Bitrix::sectionUfFormat($section, $sectionUf);

        // Получение списка связанных элементов раздела
        $elements = [];
        if ($entity) {
          $elementsDbQuery = $entity::getList(array(
            'filter' => array('IBLOCK_ID' => $iblockId, 'ACTIVE' => 'Y'),
            'order' => array('SORT' => 'ASC')
          ));

          $elementsArray = $elementsDbQuery->fetchAll();

          $elementIds = [];
          foreach ($elementsArray as $element) {
            $elementIds[] = (int)$element['ID'];
          }

          /** @var array<int, array> $ufArray */
          $ufArray = [];

          if (!empty($elementIds)) {
            \CIBlockElement::GetPropertyValuesArray($ufArray, $iblockId, array(
              'ID' => $elementIds,
              'IBLOCK_ID' => $iblockId,
            ));
          }

          foreach ($elementsArray as $element) {
            $elements[] = Bitrix::IbElementResponse($element, $ufArray[$element['ID']] ?? []);
          }
        }

        // Формирование и очистка результирующего массива от системных ключей
        return array_diff_key(
          array_merge(
            [
              'id' => (int) $section['ID'],
              'image' => Image::getThumbs($section['PICTURE']) ?: null,
            ],
            $sectionProps,
            [
              'elements' => $elements
            ]
          ),
          [
            'PICTURE' => true,
            'ID' => true,
            'CODE' => true
          ]
        );
      },
      tags: $cacheTags,
    );
  }
}
