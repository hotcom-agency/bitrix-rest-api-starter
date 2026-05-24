<?php

namespace Sprint\Migration;

use Hotcom\Helpers\Migration;

class CreateIbPageHome20251023101201 extends Migration
{
  protected $author = "hc_admin";

  protected $description = "Создание инфоблока главной страницы";

  protected $moduleVersion = "5.3.3";

  protected $iblockTypeId = 'content_pages';

  protected $iblockCode = 'page-home';

  protected $iblockApiCode = 'pageHome';

  protected $entityId = 'IBLOCK_content_pages:page-home_SECTION';

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
      'Главная',
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
      'administrators' => 'X',
      'content-managers' => 'W',
    ));

    // Создание пользовательских полей элементов
    $this->createElementFields([
      $this->elementField(
        code: 'VIDEO',
        type: 'F',
        file_type: 'mp4',
        label: 'Видео'
      ),
      $this->elementField(
        code: 'IMAGE',
        type: 'F',
        file_type: 'jpg,png,webp',
        label: 'Изображение'
      ),
      $this->elementField(
        code: 'NAME',
        type: 'S',
        label: 'Заголовок',
        localized: true,
        col_count: '50',
        row_count: '1'
      ),
      $this->elementField(
        code: 'SUBTITLE',
        type: 'S',
        label: 'Подзаголовок',
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
        code: 'LINK',
        type: 'S',
        label: 'Ссылка',
        col_count: '50',
        row_count: '1'
      ),
      $this->elementField(
        code: 'LINK_TEXT',
        type: 'S',
        label: 'Текст ссылки',
        localized: true,
        col_count: '50',
        row_count: '1'
      ),
      $this->elementField(
        code: 'TYPE',
        type: 'L',
        label: 'Тип элемента',
        required: true,
        values: [
          [
            'VALUE' => 'Слайд',
            'DEF' => 'N',
            'SORT' => '1',
            'XML_ID' => 'slide',
          ],
          [
            'VALUE' => 'Преимущество',
            'DEF' => 'N',
            'SORT' => '2',
            'XML_ID' => 'advantage',
          ],
          [
            'VALUE' => 'Впечатление',
            'DEF' => 'N',
            'SORT' => '3',
            'XML_ID' => 'impression',
          ],
          [
            'VALUE' => 'Акция',
            'DEF' => 'N',
            'SORT' => '4',
            'XML_ID' => 'specials',
          ]
        ]
      ),
    ], $iblockId);

    // Настройка вида спика элементов
    $elementFormView = [];

    $elementFormView['Элемент|edit1'] =  array(
      'ACTIVE',
      'SORT',
      'PROPERTY_IMAGE',
      'PROPERTY_VIDEO',
      'PROPERTY_TYPE',
      'PROPERTY_LINK'
    );
    foreach (array_filter($this->locales, fn($value) => $value['active'] === true) as $locale) {
      $elementFormView[strtoupper($locale['lang']) . '|edit_' . $locale['lang']] = [
        !$locale['postfix'] ? 'NAME' : 'PROPERTY_NAME' . strtoupper($locale['postfix']) => 'Заголовок [' . strtoupper($locale['lang']) . ']',
        'PROPERTY_SUBTITLE' . strtoupper($locale['postfix']),
        'PROPERTY_DESCRIPTION' . strtoupper($locale['postfix']),
        'PROPERTY_LINK_TEXT' . strtoupper($locale['postfix']),
      ];
    }

    $helper->UserOptions()->saveElementForm($iblockId, $elementFormView);

    // Создание пользовательских полей разделов
    $this->createSectionFields([
      $this->sectionField(
        code: 'UF_TITLE',
        label: 'Заголовок',
        localized: true,
        type: 'string',
        settings: ['SIZE' => 50, 'ROWS' => 1]
      ),
      $this->sectionField(
        code: 'UF_CONTENT',
        label: 'Описание',
        localized: true,
        type: 'section_html',
        settings: ['ROWS' => 10]
      ),
      $this->sectionField(
        code: 'UF_IMAGE',
        label: 'Изображение',
        localized: false,
        multiple: false,
        type: 'file',
        settings: [
          'EXTENSIONS' => [
            'jpg' => true,
            'png' => true,
            'webp' => true,
          ]
        ]
      ),
      // SEO
      $this->sectionField(
        code: 'UF_SEO_TITLE',
        label: 'TITLE',
        localized: true,
        type: 'string',
        settings: ['SIZE' => 50, 'ROWS' => 1]
      ),
      $this->sectionField(
        code: 'UF_SEO_DESCRIPTION',
        label: 'DESCRIPTION',
        localized: true,
        type: 'string',
        settings: ['SIZE' => 100, 'ROWS' => 4]
      )
    ], $this->entityId);


    // Настройка вида формы секции
    $sectionFormView = [];

    $sectionFormView['Раздел|edit_main'] =
      array(
        'ACTIVE',
        'edit_main_section_about' => 'Раздел',
        'UF_IMAGE',
      );

    foreach (array_filter($this->locales, fn($value) => $value['active'] === true) as $locale) {
      $sectionFormView[strtoupper($locale['lang']) . '|edit_' . $locale['lang']] = array(
        'edit_' . $locale['lang'] . '_section_about' => 'Раздел',
        'UF_TITLE' . strtoupper($locale['postfix']),
        'UF_CONTENT' . strtoupper($locale['postfix']),
        'edit_' . $locale['lang'] . '_section_seo' => 'SEO',
        'UF_SEO_TITLE' . strtoupper($locale['postfix']),
        'UF_SEO_DESCRIPTION' . strtoupper($locale['postfix']),
      );
    }

    $sectionFormView['Настройки|settings_tab'] =
      array(
        'NAME' => 'Название',
        'CODE' => 'Символьный код'
      );

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
            1 => 'PROPERTY_TYPE',
            2 => 'ACTIVE',
            3 => 'SORT',
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
          'last_sort_by' => 'sort',
          'last_sort_order' => 'asc',
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
            1 => 'ACTIVE',
            2 => 'SORT',
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

    // Создание секции
    $helper->Iblock()->saveSectionsFromTree(
      $iblockId,
      array(
        0 =>
        array(
          'NAME' => 'Настройки раздела',
          'CODE' => $this->iblockCode,
          'SORT' => '1',
          'ACTIVE' => 'Y',
        )
      )
    );
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
