<?php

namespace Hotcom\CustomProperties;

/**
 * Кастомное свойство инфоблока для реализации структуры "Ключ - значение"
 * 
 * @package Hotcom\CustomProperties
 */
class ElementKeyValue
{
  /**
   * Получение описания кастомного типа свойства для регистрации в Битриксе
   * 
   * @return array{
   *   PROPERTY_TYPE: string,
   *   USER_TYPE: string,
   *   DESCRIPTION: string,
   *   GetPropertyFieldHtml: array{string, string},
   *   ConvertToDB: array{string, string},
   *   ConvertFromDB: array{string, string},
   *   GetPublicViewHTML: array{string, string}
   * }
   */
  public static function GetUserTypeDescription(): array
  {
    return [
      'PROPERTY_TYPE' => 'S',
      'USER_TYPE' => 'key_value',
      'DESCRIPTION' => 'Ключ - значение (Key/Value)',
      'GetPropertyFieldHtml' => [self::class, 'GetPropertyFieldHtml'],
      'ConvertToDB' => [self::class, 'ConvertToDB'],
      'ConvertFromDB' => [self::class, 'ConvertFromDB'],
      'GetPublicViewHTML' => [self::class, 'GetPublicViewHTML'],
    ];
  }

  /**
   * Вывод HTML-поля свойства в административной панели управления элементами
   * 
   * @param array<string, mixed> $arProperty Массив с настройками свойства
   * @param array{'VALUE': mixed} $val Массив с текущим значением свойства
   * @param array{'VALUE': string} $strHTMLControlName Массив с именами элементов управления для формы
   * @return string
   */
  public static function GetPropertyFieldHtml(array $arProperty, array $val, array $strHTMLControlName): string
  {
    $valueArr = $val['VALUE'] ?? [];
    $key = is_array($valueArr) ? ($valueArr['KEY'] ?? '') : '';
    $value = is_array($valueArr) ? ($valueArr['VALUE'] ?? '') : '';

    $inputName = function_exists('htmlspecialcharsbx')
      ? htmlspecialcharsbx($strHTMLControlName['VALUE'])
      : $strHTMLControlName['VALUE'];

    return '
            <div style="display: flex; gap: 5px; align-items: center; margin-bottom: 4px;">
                <input type="text" name="' . $inputName . '[KEY]" value="' . (function_exists('htmlspecialcharsbx') ? htmlspecialcharsbx((string)$key) : $key) . '" size="25" placeholder="Название">
                <span>&mdash;</span>
                <input type="text" name="' . $inputName . '[VALUE]" value="' . (function_exists('htmlspecialcharsbx') ? htmlspecialcharsbx((string)$value) : $value) . '" size="25" placeholder="Значение">
            </div>
        ';
  }

  /**
   * Преобразование значения перед сохранением в базу данных
   * 
   * @param array<string, mixed> $arProperty Массив с настройками свойства
   * @param array{'VALUE': mixed} $value Массив с текущим значением свойства
   * @return array{'VALUE': string}
   */
  public static function ConvertToDB(array $arProperty, array $value): array
  {
    $val = $value['VALUE'] ?? null;
    if (is_array($val)) {
      if (!empty($val['KEY']) || !empty($val['VALUE'])) {
        $value['VALUE'] = serialize($val);
      } else {
        $value['VALUE'] = '';
      }
    }

    return ['VALUE' => (string)($value['VALUE'] ?? '')];
  }

  /**
   * Преобразование значения после извлечения из базы данных
   * 
   * @param array<string, mixed> $arProperty Массив с настройками свойства
   * @param array{'VALUE': mixed} $value Массив с текущим значением свойства
   * @return array{'VALUE': mixed}
   */
  public static function ConvertFromDB(array $arProperty, array $value): array
  {
    $val = $value['VALUE'] ?? '';
    if ($val !== '' && !is_array($val)) {
      $unserialized = unserialize((string)$val, ['allowed_classes' => false]);
      $value['VALUE'] = is_array($unserialized) ? $unserialized : [];
    }
    return ['VALUE' => $value['VALUE'] ?? []];
  }

  /**
   * Генерация HTML-представления значения для вывода в публичной части сайта
   * 
   * @param array<string, mixed> $arProperty Массив с настройками свойства
   * @param array{'VALUE': mixed} $value Массив с текущим значением свойства
   * @param array{'VALUE': string} $strHTMLControlName Массив с именами элементов управления для формы
   * @return string
   */
  public static function GetPublicViewHTML(array $arProperty, array $value, array $strHTMLControlName): string
  {
    $valArr = $value['VALUE'] ?? null;
    if (!is_array($valArr)) {
      return '';
    }

    $key = (string)($valArr['KEY'] ?? '');
    $val = (string)($valArr['VALUE'] ?? '');

    if (function_exists('htmlspecialcharsbx')) {
      return htmlspecialcharsbx($key) . ' / ' . htmlspecialcharsbx($val);
    }

    return $key . ' / ' . $val;
  }
}
