<?php

namespace Hotcom\Dto;

/**
 * Объект передачи данных для создания или изменения полей инфоблока в миграциях
 * 
 * @internal
 */
class MigrationElementFieldDto
{
  /**
   * Инициализация объекта DTO параметров поля инфоблока
   *
   * @param string $code Символьный код
   * @param string $label Название
   * @param bool $localized Необходимость создания локализованных копий свойства
   * @param string|int $sort Индекс сортировки
   * @param bool $required Обязательность заполнения
   * @param bool $multiple Флаг множественного значения свойства
   * @param 'S'|'N'|'L'|'F'|'G'|'E' $type Базовый тип свойства Битрикса
   * @param string $file_type Допустимые расширения файлов (для типа F)
   * @param string|int $link_iblock_id Идентификатор связанного инфоблока (для типов G, E)
   * @param mixed $default_value Значение по умолчанию
   * @param 'key_value'|'checkbox_flag'|'UserID'|'DateTime'|'Date'|'HTML'|'video'|'map_yandex'|'Money'|'directory'|string|null $user_type Идентификатор кастомного пользовательского типа
   * @param string $row_count Количество строк отображения текстового поля в административной части
   * @param string $multiple_count Количество полей ввода, отображаемых по умолчанию для множественного свойства
   * @param string $col_count Количество колонок отображения текстового поля в административной части
   * @param 'L'|'C'|'D' $list_type Внешний вид элементов списка в интерфейсе формы
   * @param list<array{
   *   VALUE: string,
   *   XML_ID: string,
   *   SORT?: int|string,
   *   DEF?: 'Y'|'N'
   * }> $values Набор предустановленных элементов списка (вариантов перечисления)
   */
  public function __construct(
    public string $code,
    public string $label,
    public bool $localized = false,
    public string|int $sort = '500',
    public bool $required = false,
    public bool $multiple = false,
    public string $type = 'S',
    public string $file_type = '',
    public string|int $link_iblock_id = '',
    public mixed $default_value = null,
    public ?string $user_type = null,
    public string $row_count = '1',
    public string $multiple_count = '1',
    public string $col_count = '50',
    public string $list_type = 'L',
    public mixed $values = [],
  ) {}
}
