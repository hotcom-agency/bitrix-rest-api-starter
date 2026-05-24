<?php

namespace Sprint\Migration;

use Hotcom\Helpers\Migration;

class CreateIbRequests20251022101103 extends Migration
{
  protected $author = "hc_admin";

  protected $description = "Создание инфоблока \"Обращения\"";

  protected $moduleVersion = "5.3.3";

  protected $iblockTypeId = 'content_elements';

  protected $iblockCode = 'requests';

  protected $iblockApiCode = 'requests';

  protected $entityId = 'IBLOCK_content_elements:requests_SECTION';

  /**
   * @throws Exceptions\HelperException
   * @return bool|void
   */
  public function up()
  {
    $helper = $this->getHelperManager();

    // Создание инфоблока
    $iblockId = $this->createIb(
      $this->iblockTypeId,
      $this->iblockCode,
      $this->iblockApiCode,
      'Обращения',
      100
    );

    // Настройки инфоблока
    $helper->Iblock()->saveIblockFields($iblockId, array(
      'CODE' => [
        'IS_REQUIRED' => 'N',
        'DEFAULT_VALUE' => [
          'UNIQUE' => 'N',
          'TRANSLITERATION' => 'Y',
          'TRANS_SPACE' => '-',
          'TRANS_OTHER' => '-',
        ],
      ],
      'SECTION_CODE' => [
        'IS_REQUIRED' => 'N',
        'DEFAULT_VALUE' => [
          'UNIQUE' => 'N',
          'TRANSLITERATION' => 'Y',
          'TRANS_SPACE' => '-',
          'TRANS_OTHER' => '-',
        ],
      ]
    ));

    // Установка прав
    $helper->Iblock()->saveGroupPermissions($iblockId, array(
      'administrators' => 'S',
      'content-managers' => 'S',
    ));

    // Создание пользовательских полей элементов
    $this->createElementFields([
      $this->elementField(
        code: 'EMAIL',
        type: 'S',
        label: 'E-mail',
        col_count: '70',
        row_count: '1'
      ),
      $this->elementField(
        code: 'PHONE',
        type: 'S',
        label: 'Телефон',
        col_count: '70',
        row_count: '1'
      ),
      $this->elementField(
        code: 'MESSAGE',
        type: 'S',
        label: 'Сообщение',
        col_count: '95',
        row_count: '4'
      ),
      $this->elementField(
        code: 'LOCATION',
        type: 'S',
        label: 'URL запроса',
        col_count: '70',
        row_count: '1'
      ),
      $this->elementField(
        code: 'TYPE',
        type: 'L',
        label: 'Тип обращенния',
        required: true,
        values: [
          [
            'VALUE' => 'Общие вопросы',
            'DEF' => 'N',
            'SORT' => '2',
            'XML_ID' => 'other',
          ],
          [
            'VALUE' => 'Бронирование',
            'DEF' => 'N',
            'SORT' => '2',
            'XML_ID' => 'booking',
          ],
          [
            'VALUE' => 'Мероприятие',
            'DEF' => 'N',
            'SORT' => '3',
            'XML_ID' => 'event',
          ]
        ]
      )
    ], $iblockId);

    // Настройка вида элемента
    $elementFormView = [];

    $elementFormView['Элемент|edit1'] =  array(
      'NAME',
      'PROPERTY_EMAIL',
      'PROPERTY_PHONE',
      'PROPERTY_MESSAGE',
      'PROPERTY_TYPE',
      'PROPERTY_LOCATION'
    );

    $helper->UserOptions()->saveElementForm($iblockId, $elementFormView);

    // Настройка вида формы секции
    $sectionFormView['Настройки|settings_tab'] = array(
      'NAME' => 'Название',
      'CODE' => 'Символьный код'
    );;;

    $helper->UserOptions()->saveSectionForm($iblockId,  $sectionFormView);

    // Настройка вида списка элементов
    $helper->UserOptions()->saveElementGrid($iblockId, array(
      'views' =>
      array(
        'default' =>
        array(
          'columns' =>
          array(
            0 => 'NAME',
            1 => 'PROPERTY_PHONE',
            2 => 'PROPERTY_EMAIL',
            3 => 'PROPERTY_TYPE',
            4 => 'DATE_CREATED',
          ),
          'columns_sizes' =>
          array(
            'expand' => 1,
            'columns' =>
            array(),
          ),
          'sticked_columns' =>
          array(),
          'custom_names' =>
          array(),
          'last_sort_by' => 'date_created',
          'last_sort_order' => 'desc',
        ),
      ),
      'filters' =>
      array(),
      'current_view' => 'default',
    ));

    // Настройка вида списка секций
    $helper->UserOptions()->saveSectionGrid($iblockId, array(
      'views' =>
      array(
        'default' =>
        array(
          'name' => NULL,
          'columns' =>
          array(
            0 => 'NAME',
            1 => 'SORT'
          ),
          'sort_by' => NULL,
          'sort_order' => NULL,
          'page_size' => NULL,
          'saved_filter' => NULL,
          'custom_names' =>
          array(),
          'columns_sizes' =>
          array(
            'expand' => 1,
            'columns' =>
            array(),
          ),
          'sticked_columns' =>
          array(),
        ),
      ),
      'filters' =>
      array(),
      'current_view' => 'default',
    ));
  }

  /**
   * @throws Exceptions\HelperException
   * @return bool|void
   */
  public function down()
  {
    $helper = $this->getHelperManager();

    $helper->Iblock()->deleteIblockIfExists($this->iblockCode, $this->iblockTypeId);
  }
}
