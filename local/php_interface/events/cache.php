<?php

use Bitrix\Main\EventManager;
use Hotcom\Helpers\Bitrix;
use Hotcom\Helpers\ApiCache;

$eventManager = EventManager::getInstance();

/**
 * Список событий инфоблоков для автоматического сброса кэша при изменении данных
 */
$iblockEvents = [
  'OnAfterIBlockElementAdd',
  'OnAfterIBlockElementUpdate',
  'OnAfterIBlockElementDelete',
  'OnAfterIBlockSectionAdd',
  'OnAfterIBlockSectionUpdate',
  'OnAfterIBlockSectionDelete'
];

/**
 * Регистрация обработчиков событий для автоматического сброса API-кэша соответствующего инфоблока
 */
foreach ($iblockEvents as $eventName) {
  $eventManager->addEventHandler('iblock', $eventName, function (array $val) {
    if (isset($val['IBLOCK_ID']) && Bitrix::multipleRequestHandler($val)) {
      $apiCache = new ApiCache();
      $apiCache->clearByIblock((int)$val['IBLOCK_ID']);
    }
  });
}
