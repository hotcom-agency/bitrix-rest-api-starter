<?php

namespace Hotcom\Dto;

/**
 * Объект передачи данных для создания или изменения пользовательских полей разделов в миграциях
 * 
 * @internal
 */
class MigrationSectionFieldDto
{
  /**
   * Инициализация объекта DTO параметров пользовательского поля раздела
   *
   * @param string $code Символьный код свойства (с префиксом UF_)
   * @param string|array{ru:string, en?:string} $label Название свойства или массив языковых названий
   * @param 'string'|'integer'|'double'|'boolean'|'datetime'|'date'|'file'|'enumeration'|'iblock_section'|'iblock_element'|'hlblock'|'video'|'url'|'money' $type Тип данных пользовательского свойства
   * @param bool $localized Необходимость создания локализованных копий свойства
   * @param bool $multiple Флаг множественного значения свойства
   * @param bool $required Обязательность заполнения свойства
   * @param array{
   *   'SIZE'?: int,
   *   'ROWS'?: int,
   *   'DISPLAY'?: 'LIST'|'CHECKBOX',
   *   'IBLOCK_ID'?: int|string,
   *   'HLBLOCK_ID'?: int|string,
   *   'TABLE_NAME'?: string,
   *   'EXTENSIONS'?: array<string, bool>,
   *   'LIST_HEIGHT'?: int,
   *   'LABEL_CHECKBOX'?: string,
   *   'DEFAULT_VALUE'?: mixed
   * } $settings Дополнительные специфические настройки отображения и валидации свойства
   */
  public function __construct(
    public string $code,
    public string|array $label,
    public string $type,
    public bool $localized = false,
    public bool $multiple = false,
    public bool $required = false,
    public array $settings = [],
  ) {}
}
