<?php

namespace Hotcom\Helpers;

use Sprint\Migration\VersionBuilder;

class MigrationBuilder extends VersionBuilder
{
  protected string $name = 'MigrationBuilder';

  protected function isBuilderEnabled()
  {
    return true;
  }

  protected function initialize()
  {
    $this->addVersionFields();
    $this->setTitle('HC DTO Миграция');
    $this->addField('builder_name', ['bind' => 1, 'type' => 'hidden']);
    $this->addField('description',  ['bind' => 1, 'title' => 'Описание', 'width' => 250]);
    $this->addField('iblock_code',  ['bind' => 1, 'title' => 'Код инфоблока', 'width' => 250]);
    $this->addField('iblock_api',   ['bind' => 1, 'title' => 'Код инфоблока (api)', 'width' => 250]);
    $this->addField('iblock_type_id', [
      'title'    => 'Тип инфоблока',
      'width'    => 250,
      'multiple' => 0,
      'bind'     => 1,
      'select'   => [
        [
          'title' => 'Раздел',
          'value' => 'content_pages',
        ],
        [
          'title' => 'Коллекция',
          'value' => 'content_elements',
        ]
      ]
    ]);
  }

  protected function execute(): void
  {
    $rawDesc = $this->getFieldValue('description');
    $rawCode = $this->getFieldValue('iblock_code');
    $rawApi  = $this->getFieldValue('iblock_api');
    $rawType = $this->getFieldValue('iblock_type_id');

    $iblockCode = !empty($rawCode) ? strtolower($rawCode) : 'default_code';
    $iblockApi  = !empty($rawApi)  ? $rawApi : $iblockCode . '_api';
    $description = !empty($rawDesc) ? $rawDesc : 'Миграция HC';
    $iblockTypeId = (!empty($rawType) && is_string($rawType)) ? trim($rawType) : 'content_elements';
    
    $vars = [
      'version'         => $this->getVersionName(),
      'description'     => $description,
      'iblockCode'      => $iblockCode,
      'iblockApiCode'   => $iblockApi,
      'iblockTypeId'  => $iblockTypeId,
    ];

    $this->createVersionFile(
      $this->getTemplatePath(),
      $vars,
      false
    );
  }

  private function getTemplatePath(): string
  {
    return dirname(__DIR__, 2) . '/php_interface/migration_template.php';
  }
}
