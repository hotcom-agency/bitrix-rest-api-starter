<?php
/**
 * @var $version
 * @var $description
 * @var $moduleVersion
 * @var $author
 * @var $iblockCode
 * @var $iblockApiCode
 * @var $iblockTypeId
 */
?><?php echo "<?php\n" ?>

namespace Sprint\Migration;

use \Hotcom\Helpers\Migration;

class <?php echo $version ?> extends Migration
{
  protected $author = "<?php echo $author ?>";

  protected $description = "<?php echo $description ?>";

  protected $moduleVersion = "<?php echo $moduleVersion ?>";

  protected $iblockTypeId = '<?php echo $iblockTypeId ?>'; // content_pages | content_elements 

  protected $iblockCode = '<?php echo $iblockCode ?>';

  protected $iblockApiCode = '<?php echo $iblockApiCode ?>';

  protected string $entityId;

  public function __construct()
  {
    parent::__construct();
    $this->entityId = 'IBLOCK_' . $this->iblockTypeId . ':' . $this->iblockCode . '_SECTION';
  }

  /**
   * @throws Exceptions\HelperException
   * @return bool|void
   */
  public function up()
  {
    $helper = $this->getHelperManager();

    /**
     * Создание инфоблока
     */
    $iblockId = $this->createIb(
      $this->iblockTypeId,
      $this->iblockCode,
      $this->iblockApiCode,
      '<?php echo $description ?>', // Название инфоблока, например "Акции"
      1 // Сортировка
    );

    // Получить ID существующего 
    // $iblockId = $helper->Iblock()->getIblockIdIfExists('{{iblockCode}}', '{{iblockTypeId}}');
   
    /**
     * Настройки инфоблока
     */
    $helper->Iblock()->saveIblockFields($iblockId, array(
      'CODE' => [
        'IS_REQUIRED' => 'N',
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

    /**
     * Установка прав
     */
    $helper->Iblock()->saveGroupPermissions($iblockId, array(
      'administrators' => 'X',
      'content-managers' => 'W',
    ));

    /**
     * Настройка полей элементов, форм и списка
     */
    $this->createElementFields([
      // $this->elementField(
      //   code: 'NAME',
      //   type: 'S',
      //   label: 'Название',
      //   localized: true,
      //   col_count: '50',
      //   row_count: '1'
      // ),
      // $this->elementField(
      //   code: 'DESCRIPTION',
      //   type: 'S',
      //   label: 'Название',
      //   localized: true,
      //   col_count: '100',
      //   row_count: '4'
      // ),
      // $this->elementField(
      //   code: 'PROPERTY_IMAGE',
      //   multiple: false,
      //   type: 'F',
      //   file_type: 'jpg,png,webp',
      //   label: 'Изображение'
      // ),
    ], $iblockId);

    // Настройка вида формы элементов
    $elementFormView = [];

    // Настройка полей без локализации
    $elementFormView['Элемент|edit1'] =  array(
      'ACTIVE',
      // 'PROPERTY_IMAGE',
      // 'CODE' => 'Символьный код'
    );

    // Настройка полей с локализацией
    // foreach (array_filter($this->locales, fn($value) => $value['active'] === true) as $locale) {
    //   $elementFormView[strtoupper($locale['lang']) . '|edit_' . $locale['lang']] = [
    //     !$locale['postfix'] ? 'NAME' : 'PROPERTY_NAME' . strtoupper($locale['postfix']) => 'Название [' . strtoupper($locale['lang']) . ']',
    //     'PROPERTY_DESCRIPTION' . strtoupper($locale['postfix']),
    //   ];
    // }

    // Сохранение настроек
    $helper->UserOptions()->saveElementForm($iblockId, $elementFormView);

    /**
     * Настройка полей раздела, форм и списка
     */
    $this->createSectionFields([
      // $this->sectionField(
      //   code: 'UF_TITLE',
      //   label: 'Заголовок раздела',
      //   localized: true,
      //   type: 'string',
      //   settings: ['SIZE' => 50, 'ROWS' => 1]
      // ),
      // $this->sectionField(
      //   code: 'UF_IMAGES',
      //   label: 'Изображения раздела',
      //   localized: false,
      //   multiple: true,
      //   type: 'file',
      //   settings: [
      //     'EXTENSIONS' => [
      //       'jpg' => true,
      //       'png' => true,
      //       'webp' => true,
      //     ]
      //   ]
      // ),
      // // SEO
      // $this->sectionField(
      //   code: 'UF_SEO_TITLE',
      //   label: 'TITLE',
      //   localized: true,
      //   type: 'string',
      //   settings: ['SIZE' => 50, 'ROWS' => 1]
      // ),
      // $this->sectionField(
      //   code: 'UF_SEO_DESCRIPTION',
      //   label: 'DESCRIPTION',
      //   localized: true,
      //   type: 'string',
      //   settings: ['SIZE' => 100, 'ROWS' => 4]
      // )
    ], $this->entityId);

    // Настройка вида формы секции
    $sectionFormView = [];

    // Настройка полей без локализации
    $sectionFormView['Раздел|edit_main'] =
      array(
        'ACTIVE',
        'edit_main_section_header' => 'Header',
        'UF_IMAGES',
      );

    // Настройка полей с локализацией
    // foreach (array_filter($this->locales, fn($value) => $value['active'] === true) as $locale) {
    //   $sectionFormView[strtoupper($locale['lang']) . '|edit_' . $locale['lang']] = [
    //     'edit_' . $locale['lang'] . '_section_header' => 'Header',
    //     'UF_TITLE' . strtoupper($locale['postfix']),
    //     'edit_' . $locale['lang'] . '_section_seo' => 'SEO',
    //     'UF_SEO_TITLE' . strtoupper($locale['postfix']),
    //     'UF_SEO_DESCRIPTION' . strtoupper($locale['postfix']),
    //   ];
    // }

    // Системные настройки 
    $sectionFormView['Настройки|settings_tab'] = array(
      'NAME' => 'Название',
      'CODE' => 'Символьный код'
    );

    // Сохранение настроек
    $helper->UserOptions()->saveSectionForm($iblockId,  $sectionFormView);

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
