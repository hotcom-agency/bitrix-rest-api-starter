<?php

/**
 * Кастомное свойство инфоблока для реализации логики "Да / Нет" через чекбокс
 */
class CUserPropertyElementBoolean
{
  /**
   * Получение описания кастомного типа свойства для регистрации в Битриксе
   *
   * @return array{
   *   PROPERTY_TYPE: string,
   *   USER_TYPE: string,
   *   DESCRIPTION: string,
   *   GetPropertyFieldHtml: array{string, string},
   *   GetSettingsHTML?: array{string, string}
   * }
   */
  public static function GetUserTypeDescription(): array
  {
    return [
      'PROPERTY_TYPE' => 'S',
      'USER_TYPE' => 'checkbox_flag',
      'DESCRIPTION' => 'Да / Нет',
      'GetPropertyFieldHtml' => [self::class, 'GetPropertyFieldHtml'],
      'GetSettingsHTML' => [self::class, 'GetSettingsHTML'],
    ];
  }

  /**
   * Вывод HTML-поля свойства в административной панели управления элементами
   *
   * @param array<string, mixed> $arProperty Массив с настройками свойства
   * @param array{'VALUE': mixed} $value Массив с текущим значением свойства
   * @param array{'VALUE': string} $strHTMLControlName Массив с именами элементов управления для формы
   * @return string
   */
  public static function GetPropertyFieldHtml(array $arProperty, array $value, array $strHTMLControlName): string
  {
    $checked = ((int)$value['VALUE'] > 0) ? ' checked' : '';
    $colCount = (int)($arProperty['COL_COUNT'] ?? 30);

    $html = '<input type="hidden" value="" name="' . $strHTMLControlName["VALUE"] . '">';
    $html .= '<input type="checkbox" size="' . $colCount . '" value="1" name="' . $strHTMLControlName["VALUE"] . '"' . $checked . '>';

    return $html;
  }

  /**
   * Возврат HTML-строки настроек свойства при редактировании инфоблока
   *
   * @return string
   */
  public static function GetSettingsHTML(): string
  {
    return '';
  }
}
