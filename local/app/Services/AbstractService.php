<?php

namespace Hotcom\Services;

use Bitrix\Main\Loader;
use Bitrix\Main\SystemException;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Hotcom\Helpers\ApiCache;
use Hotcom\Helpers\Bitrix;
use Hotcom\Helpers\Localization;

/**
 * Базовый абстрактный класс для всех сервисов информационных блоков
 * 
 * @package Hotcom\Services
 */
abstract class AbstractService
{
  protected int $iblockId;
  protected ApiCache $apiCache;
  protected string $iblockCode = '';

  protected array $defaultFields = [
    'ID',
    'CODE',
    'ACTIVE',
    'SORT'
  ];

  public function __construct(ApiCache $apiCache)
  {
    if (!Loader::includeModule('iblock')) {
      throw new SystemException('Ошибка подключения модуля инфоблоков');
    }

    if (empty($this->iblockCode)) {
      throw new SystemException(sprintf('Свойство "iblockCode" должно быть определено в %s', static::class));
    }

    $this->apiCache = $apiCache;

    $this->iblockId = (int)$this->apiCache->get(
      key: "iblock_{$this->iblockCode}_id",
      callback: function () {
        $iblock = \Bitrix\Iblock\IblockTable::getList([
          'filter' => ['CODE' => $this->iblockCode],
          'select' => ['ID']
        ])->fetchObject();

        return $iblock ? (int)$iblock->getId() : 0;
      },
      tags: ['iblock_config'],
      ttl: 86400
    );
  }

  /**
   * Формирование массива полей SELECT с учётом локализации.
   * Исключение конфликтов дублирования системных свойств в Битрикс ORM.
   *
   * @return list<string> Список кодов полей для ORM-запроса
   */
  protected function getSelect(): array
  {
    $fields = $this->defaultFields;

    $localizedData = [];
    $plainFields   = [];

    foreach ($fields as $key => $value) {
      if ($value === 'localized') {
        $localized = Localization::getlocalizedFieldsByKey($key);
        if ($localized) {
          $localizedData = array_merge($localizedData, $localized);
        }
      } else {
        $plainFields[] = is_string($key) ? $key : $value;
      }
    }

    return array_merge($localizedData, $plainFields);
  }

  /**
   * Получение локализованного значения свойства из объекта сущности Битрикса
   * 
   * @param EntityObject $element Объект элемента
   * @param string $baseMethod Базовый метод геттера
   * @return mixed
   */
  protected function getLocalizedValue(EntityObject $element, string $baseMethod): mixed
  {
    return Localization::getOrmValue($element, $baseMethod);
  }

  /**
   * Получение массива базовых тегов кеша инфоблока
   * 
   * @param array $additionalTags Дополнительные теги кеша
   * @return array
   */
  protected function getCacheTags(array $additionalTags = []): array
  {
    $baseTags = ['iblock_' . $this->iblockId];
    return array_merge($baseTags, $additionalTags);
  }

  /**
   * Получение уникального хэша строки фильтрации на основе DTO
   * 
   * @param object $filterDto Объект DTO фильтра
   * @return string
   */
  protected function getFilterHash(object $filterDto): string
  {
    return Bitrix::getDtoHash($filterDto);
  }

  /**
   * Построение стандартизированного детерминированного ключа кеша для любого метода.
   * Автоматическое добавление текущего языка и кода инфоблока.
   *
   * @param string $method Имя метода сервиса
   * @param mixed  $context Специфичные параметры (DTO, строка или массив)
   * @return string
   */
  protected function buildCacheKey(string $method, mixed $context = null): string
  {
    $lang = Localization::getPostfix();
    $hashPart = '';

    if ($context !== null) {
      if (is_object($context)) {
        $hashPart = '_' . Bitrix::getDtoHash($context);
      } elseif (is_array($context)) {
        ksort($context);
        $hashPart = '_' . md5(json_encode($context));
      } else {
        $hashPart = '_' . (string)$context;
      }
    }

    return "api_{$this->iblockCode}_{$method}{$hashPart}_{$lang}";
  }
}
