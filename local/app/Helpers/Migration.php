<?php

namespace Hotcom\Helpers;

use Hotcom\Dto\MigrationElementFieldDto;
use Hotcom\Dto\MigrationSectionFieldDto;
use Hotcom\Helpers\Localization;
use Sprint\Migration\Version;
use Bitrix\Main\Application;

class Migration extends Version
{
  public array $locales;

  public function __construct()
  {
    $locales = Localization::locales() ?: [];

    $this->locales = array_map(
      fn($v) => ['lang' => $v['lang'], 'postfix' => $v['postfix'], 'active' => $v['active']],
      array_values($locales)
    );
  }

  /**
   * Деструктор вызывается автоматически PHP, когда миграция полностью отработала
   */
  public function __destruct()
  {
    $this->runBitrixOrmAnnotations();
  }

  /**
   * Автоматический запуск генерации аннотаций ORM Битрикс через CLI
   */
  protected function runBitrixOrmAnnotations(): void
  {
    static $scheduled = false;
    if ($scheduled) {
      return;
    }
    $scheduled = true;

    $envMode = getenv('MODE');
    $mode = (!empty($_ENV['MODE']))

      ? $_ENV['MODE']
      : (($envMode !== false) ? $envMode : '');

    if (!in_array(strtolower($mode), ['development', 'dev', 'local'], true)) {
      return;
    }

    register_shutdown_function(function () {
      try {
        $docRoot = Application::getDocumentRoot() ?: ($_SERVER['DOCUMENT_ROOT'] ?? null);
        if (!$docRoot) return;

        $cliPath = $docRoot . '/bitrix/modules/main/cli/bitrix.php';
        if (!is_file($cliPath)) return;

        $command = sprintf(
          'cd %s && php -d memory_limit=512M %s orm:annotate -m all 2>&1',
          escapeshellarg($docRoot),
          escapeshellarg($cliPath)
        );

        exec($command, $output, $returnCode);

        if ($returnCode === 0) {
        } else {
          error_log('[ORM] Error: ' . implode("\n", $output));
        }
      } catch (\Throwable $e) {
        error_log('[ORM] Exception: ' . $e->getMessage());
      }
    });
  }

  /**
   * DTO для поля элемента инфоблока
   *
   * @param string $code Символьный код
   * @param string $label Название
   * @param bool $localized Локализация (копии с постфиксами)
   * @param string|int $sort Сортировка
   * @param bool $required Обязательное
   * @param bool $multiple Множественное
   * @param 'S'|'N'|'L'|'F'|'G'|'E' $type S-строка, N-число, L-список, F-файл, G-раздел, E-элемент
   * @param 'jpg, png, jpeg, webp'|'mp4'|'pdf, doc, docx'|string $file_type Допустимые расширения (для типа F)
   * @param string|int $link_iblock_id ID инфоблока (для типов G, E)
   * @param mixed $default_value Значение по умолчанию
   * @param 'key_value'|'checkbox_flag'|'boolean'|'UserID'|'DateTime'|'Date'|'HTML'|'video'|'map_yandex'|'Money'|'directory'|'section_html'|'section_images'|'integer'|'double'|'datetime'|'date'|'enumeration'|'iblock_section'|'iblock_element'|'hlblock'|'url'|null $user_type Специальный тип (directory = HL-справочник, boolean = флажок да/нет)
   * @param string $row_count Количество строк для текстовых полей
   * @param string $multiple_count Количество элементов для множественного поля
   * @param string $col_count Количество колонок для текстовых полей
   * @param 'L'|'C'|'D' $list_type Вид списка: L-список, C-флажки, D-диалог выбора
   * @param list<array{
   *   VALUE: string,
   *   XML_ID: string,
   *   SORT?: int|string,
   *   DEF?: 'Y'|'N'
   * }> $values Элементы списка
   * @return MigrationElementFieldDto
   */
  public function elementField(
    string $code,
    string $label,
    bool $localized = false,
    string|int $sort = '500',
    bool $required = false,
    bool $multiple = false,
    string $type = 'S',
    string $file_type = '',
    string|int $link_iblock_id = '',
    mixed $default_value = null,
    ?string $user_type = null,
    string $row_count = '1',
    string $multiple_count = '1',
    string $col_count = '50',
    string $list_type = 'L',
    mixed $values = [],
  ): MigrationElementFieldDto {
    return new MigrationElementFieldDto(...func_get_args());
  }

  /**
   * DTO для поля раздела инфоблока
   *
   * @param string $code Символьный код (UF_...)
   * @param string|array{ru:string, en?:string} $label Название
   * @param 'string'|'file'|'boolean'|'section_html'|'section_images'|'integer'|'double'|'datetime'|'date'|'enumeration'|'iblock_section'|'iblock_element'|'hlblock'|'video'|'url' $type Тип поля
   * @param bool $localized Локализация (копии с постфиксами)
   * @param bool $multiple Множественное
   * @param bool $required Обязательное
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
   * } $settings Настройки поля
   * @return MigrationSectionFieldDto
   */
  public function sectionField(
    string $code,
    string|array $label,
    string $type,
    bool $localized = false,
    bool $multiple = false,
    bool $required = false,
    array $settings = [],
  ): MigrationSectionFieldDto {
    return new MigrationSectionFieldDto(...func_get_args());
  }

  /**
   * Создаем пользовательские поля для элементов инфоблока
   *
   * @param MigrationElementFieldDto[] $fields Массив DTO полей
   * @param int $iblockId ID инфоблока
   * @return void
   */
  public function createElementFields(array $fields, int $iblockId): void
  {
    foreach ($fields as $field) {
      if (!$field instanceof MigrationElementFieldDto) continue;

      $isLocalized = $field->localized === true;
      foreach ($this->locales as $loc) {
        if (!$loc['active']) continue;

        $code = $isLocalized ? $field->code . strtoupper($loc['postfix']) : $field->code;
        $name = $isLocalized ? $field->label . ' [' . strtoupper($loc['lang']) . ']' : $field->label;

        $this->getHelperManager()->Iblock()->saveProperty($iblockId, [
          'CODE' => $code,
          'NAME' => $name,
          'SORT' => $field->sort,
          'IS_REQUIRED' => $field->required ? 'Y' : 'N',
          'MULTIPLE' => $field->multiple ? 'Y' : 'N',
          'PROPERTY_TYPE' => $field->type,
          'FILE_TYPE' => $field->file_type,
          'LINK_IBLOCK_ID' => $field->link_iblock_id,
          'DEFAULT_VALUE' => $field->default_value,
          'USER_TYPE' => $field->user_type,
          'ROW_COUNT' => $field->row_count,
          'MULTIPLE_CNT' => $field->multiple_count,
          'COL_COUNT' => $field->col_count,
          'LIST_TYPE' => $field->list_type,
          'VALUES' => $field->values,
          'ACTIVE' => 'Y'
        ]);
        if (!$isLocalized) break;
      }
    }
  }

  /**
   * Создаем пользовательские поля для разделов инфоблока
   *
   * @param MigrationSectionFieldDto[] $fields Массив DTO полей
   * @param string $entityId ID сущности (например, IBLOCK_1_SECTION)
   * @return void
   */
  public function createSectionFields(array $fields, string $entityId): void
  {
    foreach ($fields as $field) {
      if (!$field instanceof MigrationSectionFieldDto) continue;

      $labels = is_array($field->label) ? $field->label : ['ru' => $field->label];
      $isLocalized = $field->localized === true;

      foreach ($this->locales as $loc) {
        if (!$loc['active']) continue;

        $fName = $isLocalized ? $field->code . strtoupper($loc['postfix']) : $field->code;
        $sfx = $isLocalized ? ' [' . strtoupper($loc['lang']) . ']' : '';

        $ruLabel = $labels['ru'] ?? reset($labels);
        $enLabel = $labels['en'] ?? $ruLabel;

        $this->getHelperManager()->UserTypeEntity()->saveUserTypeEntity([
          'ENTITY_ID' => $entityId,
          'FIELD_NAME' => $fName,
          'USER_TYPE_ID' => $field->type,
          'MULTIPLE' => $field->multiple ? 'Y' : 'N',
          'MANDATORY' => $field->required ? 'Y' : 'N',
          'SETTINGS' => $field->settings,
          'EDIT_FORM_LABEL' => ['ru' => $ruLabel . $sfx, 'en' => $enLabel . $sfx],
          'LIST_COLUMN_LABEL' => ['ru' => $ruLabel . $sfx, 'en' => $enLabel . $sfx],
          'LIST_FILTER_LABEL' => ['ru' => $ruLabel . $sfx, 'en' => $enLabel . $sfx],
        ]);
        if (!$isLocalized) break;
      }
    }
  }

  /**
   * Создание инфоблока
   *
   * @param string $id ID типа инфоблока
   * @param string $code Символьный код
   * @param string $api API код для D7
   * @param string $name Название инфоблока
   * @param int $sort Сортировка
   * @return int ID созданного инфоблока
   */
  public function createIb(string $id, string $code, string $api, string $name = '', int $sort = 1): int
  {
    return $this->getHelperManager()->Iblock()->saveIblock([
      'IBLOCK_TYPE_ID' => $id,
      'LID' => ['s1'],
      'CODE' => $code,
      'API_CODE' => $api,
      'NAME' => $name,
      'SORT' => $sort,
      'ACTIVE' => 'Y',
      'VERSION' => '1',
    ]);
  }
}
