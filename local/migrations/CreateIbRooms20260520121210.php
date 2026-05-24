<?php

namespace Sprint\Migration;

use \Hotcom\Helpers\Migration;

class CreateIbRooms20260520121210 extends Migration
{
  protected $author = "hc_admin";

  protected $description = "Создание инфоблока Номера";

  protected $moduleVersion = "5.3.3";

  protected $iblockTypeId = 'content_elements';

  protected $iblockCode = 'rooms';

  protected $iblockApiCode = 'rooms';

  protected $entityId = 'IBLOCK_content_pages:rooms_SECTION';

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
      'Номера',
      1
    );

    // Настройки инфоблока
    $helper->Iblock()->saveIblockFields($iblockId, array(
      'CODE' => [
        'IS_REQUIRED' => 'Y',
        'DEFAULT_VALUE' => [
          'UNIQUE' => 'Y',
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
      'administrators' => 'X',
      'content-managers' => 'W',
    ));

    // Создание пользовательских полей элементов
    $this->createElementFields([
      $this->elementField(
        code: 'NAME',
        type: 'S',
        label: 'Название',
        localized: true,
        col_count: '50',
        row_count: '1'
      ),
      $this->elementField(
        code: 'DESCRIPTION',
        type: 'S',
        label: 'Описание',
        localized: true,
        col_count: '100',
        row_count: '4'
      ),
      $this->elementField(
        code: 'IMAGE',
        type: 'F',
        file_type: 'jpg,png,webp',
        label: 'Основное изображение'
      ),
      $this->elementField(
        code: 'GALLERY',
        multiple: true,
        type: 'F',
        file_type: 'jpg,png,webp',
        label: 'Фотогалерея'
      ),
      $this->elementField(
        code: 'CAPACITY',
        type: 'N',
        label: 'Вместительность (макс)',
        col_count: '30',
        row_count: '1'
      ),
      $this->elementField(
        code: 'AREA',
        type: 'N',
        label: 'Площадь',
        col_count: '30',
        row_count: '1'
      ),
      $this->elementField(
        code: 'BED_TYPE',
        type: 'L',
        label: 'Тип кроватей',
        multiple: true,
        required: true,
        values: [
          [
            'VALUE' => 'Односпальная',
            'DEF' => 'N',
            'SORT' => '1',
            'XML_ID' => 'single',
          ],
          [
            'VALUE' => 'Двуспальная',
            'DEF' => 'N',
            'SORT' => '2',
            'XML_ID' => 'double',
          ],
          [
            'VALUE' => 'Две раздельные',
            'DEF' => 'N',
            'SORT' => '3',
            'XML_ID' => 'twin',
          ],
          [
            'VALUE' => 'Двуспальная или Две раздельные',
            'DEF' => 'N',
            'SORT' => '4',
            'XML_ID' => 'double_or_twin',
          ]
        ]
      ),
    ], $iblockId);

    // Создание пользовательских полей разделов
    // $this->createSectionFields([
    //   $this->sectionField(
    //     code: 'UF_ABOUT_TITLE',
    //     label: 'О разделе (заголовок)',
    //     localized: true,
    //     type: 'string',
    //     settings: ['SIZE' => 50, 'ROWS' => 1]
    //   )
    // ], $this->entityId);

    // Настройка вида спика элементов
    $elementFormView = [];
    $elementFormView['Элемент|edit1'] =  array(
      'ACTIVE',
      'PROPERTY_IMAGE',
      'PROPERTY_GALLERY',
      'PROPERTY_AREA',
      'PROPERTY_CAPACITY',
      'PROPERTY_AREA',
      'PROPERTY_BED_TYPE',
      'CODE' => 'Символьный код'
    );

    foreach (array_filter($this->locales, fn($value) => $value['active'] === true) as $locale) {
      $elementFormView[strtoupper($locale['lang']) . '|edit_' . $locale['lang']] = [
        !$locale['postfix'] ? 'NAME' : 'PROPERTY_NAME' . strtoupper($locale['postfix']) => 'Название [' . strtoupper($locale['lang']) . ']',
        'PROPERTY_DESCRIPTION' . strtoupper($locale['postfix']),
      ];
    }

    $helper->UserOptions()->saveElementForm($iblockId, $elementFormView);

    // Настройка вида формы секции
    $sectionFormView = [];
    $sectionFormView['Настройки|settings_tab'] =
      array(
        'NAME' => 'Название',
        'CODE' => 'Символьный код'
      );

    $helper->UserOptions()->saveSectionForm($iblockId,  $sectionFormView);

    // Настройка вида списка элементов
    $helper->UserOptions()->saveElementGrid($iblockId, [
      'views' => [
        'default' => [
          'columns' => ['NAME', 'ACTIVE', 'SORT'],
          'last_sort_by' => 'sort',
          'last_sort_order' => 'asc',
        ],
      ],
    ]);

    // Настройка вида списка секций
    $helper->UserOptions()->saveSectionGrid($iblockId, [
      'views' => [
        'default' => [
          'columns' => ['NAME', 'ACTIVE', 'SORT'],
          'last_sort_by' => 'sort',
          'last_sort_order' => 'asc',
        ],
      ],
    ]);

    // Создание секции
    // $helper->Iblock()->saveSectionsFromTree(
    //   $iblockId,
    //   array(
    //     0 =>
    //     array(
    //       'NAME' => 'Настройки раздела',
    //       'CODE' => $this->iblockCode,
    //       'SORT' => '1',
    //       'ACTIVE' => 'Y'
    //     )
    //   )
    // );

    // Создание элементов
    // $this->getExchangeManager()
    //   ->IblockElementsImport()
    //   ->setLimit(100)
    //   ->execute(function ($item) {
    //     $this->getHelperManager()
    //       ->Iblock()
    //       ->saveElementByXmlId(
    //         $item['iblock_id'],
    //         $item['fields'],
    //         $item['properties']
    //       );
    //   });
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
