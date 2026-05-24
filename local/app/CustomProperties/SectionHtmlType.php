<?php

namespace Hotcom\CustomProperties;

/**
 * Кастомное пользовательское свойство (UFX) для вывода HTML-редактора у разделов инфоблока
 * 
 * @package Hotcom\CustomProperties
 */
class SectionHtmlType
{
  /**
   * Получение описания кастомного типа свойства для регистрации в Битриксе
   * 
   * @return array{
   *   USER_TYPE_ID: string,
   *   CLASS_NAME: string,
   *   DESCRIPTION: string,
   *   BASE_TYPE: string
   * }
   */
  public static function GetUserTypeDescription(): array
  {
    return [
      'USER_TYPE_ID' => 'section_html',
      'CLASS_NAME'   => self::class,
      'DESCRIPTION'  => 'HTML поле для раздела',
      'BASE_TYPE'    => 'string',
    ];
  }

  /**
   * Определение типа колонки в базе данных для хранения значения свойства
   * 
   * @return string
   */
  public static function GetDBColumnType(): string
  {
    return 'text';
  }

  /**
   * Валидация и проверка корректности заполнения полей перед сохранением
   * 
   * @param array<string, mixed> $arUserField Массив с конфигурацией пользовательского поля
   * @param mixed $value Текущее значение поля
   * @return array
   */
  public static function CheckFields(array $arUserField, mixed $value): array
  {
    return [];
  }

  /**
   * Вывод HTML-редактора или текстовой области в форме редактирования раздела в админке
   * 
   * @param array<string, mixed> $arUserField Массив с конфигурацией пользовательского поля
   * @param array<string, mixed> $arHtmlControl Массив с именами и значениями элементов управления для формы
   * @return string
   */
  public static function GetEditFormHTML(array $arUserField, array $arHtmlControl): string
  {
    if (\CModule::IncludeModule('fileman')) {
      ob_start();
      \CFileMan::AddHTMLEditorFrame(
        $arHtmlControl['NAME'],
        $arHtmlControl['VALUE'],
        $arHtmlControl['NAME'] . '_TYPE',
        'html',
        ['height' => 200, 'width' => '100%'],
        'N',
        0,
        '',
        ''
      );
      return (string)ob_get_clean();
    }

    $value = (string)($arHtmlControl['VALUE'] ?? '');
    return '<textarea name="' . $arHtmlControl['NAME'] . '" style="width:100%; height:200px;">'
      . (function_exists('htmlspecialcharsbx') ? htmlspecialcharsbx($value) : $value)
      . '</textarea>';
  }

  /**
   * Обработка и модификация значения поля перед его непосредственным сохранением в базу данных
   * 
   * @param array<string, mixed> $arUserField Массив с конфигурацией пользовательского поля
   * @param mixed $value Текущее несформированное значение поля
   * @return mixed
   */
  public static function OnBeforeSave(array $arUserField, mixed $value): mixed
  {
    return $value;
  }
}
