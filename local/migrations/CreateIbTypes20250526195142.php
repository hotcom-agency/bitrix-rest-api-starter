<?php

namespace Sprint\Migration;

use Hotcom\Helpers\Migration;

class CreateIbTypes20250526195142 extends Migration
{
  protected $author = "hc_admin";

  protected $description = "Создание типа инфоблока Контент (элементы)";

  protected $moduleVersion = "5.3.3";

  /**
   * @throws Exceptions\HelperException
   * @return bool|void
   */
  public function up()
  {
    $helper = $this->getHelperManager();

    // Разделы
    $helper->Iblock()->saveIblockType(array(
      'ID' => 'content_pages',
      'SECTIONS' => 'Y',
      'EDIT_FILE_BEFORE' => '',
      'EDIT_FILE_AFTER' => '',
      'IN_RSS' => 'N',
      'SORT' => '1',
      'LANG' =>
      array(
        'ru' =>
        array(
          'NAME' => 'Разделы',
        ),
        'en' =>
        array(
          'NAME' => 'Pages',
        ),
      ),
    ));

    // Элементы
    $helper->Iblock()->saveIblockType(array(
      'ID' => 'content_elements',
      'SECTIONS' => 'Y',
      'EDIT_FILE_BEFORE' => '',
      'EDIT_FILE_AFTER' => '',
      'IN_RSS' => 'N',
      'SORT' => '2',
      'LANG' =>
      array(
        'ru' =>
        array(
          'NAME' => 'Элементы',
        ),
        'en' =>
        array(
          'NAME' => 'Elements',
        ),
      ),
    ));


    // Удаление rest_entity
    $helper->Iblock()->deleteIblockTypeIfExists('rest_entity');
  }

  /**
   * @throws Exceptions\HelperException
   * @return bool|void
   */
  public function down()
  {
    $this->outWarning('В данной миграции откат не предусмотрен');
  }
}
