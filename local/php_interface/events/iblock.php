<?php

use Bitrix\Iblock\ElementTable;
use Bitrix\Main\EventManager;
use Bitrix\Main\Config\Configuration;
use Bitrix\Main\Page\Asset;
use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\SectionTable;
use Bitrix\Iblock\PropertyTable;
use Bitrix\Main\Loader;
use Bitrix\Main\Application;

$eventManager = EventManager::getInstance();

/**
 * Получение конфигурации ограничений инфоблоков из ядра Bitrix D7.
 * Ищет ключ 'iblock_restrictions' в файле .settings_extra.php
 * @var array
 */
$restrictionsConfig = Configuration::getInstance()->get('hotcom_iblock_restrictions') ?? [];

/**
 * Глобальный флаг блокировки: 
 * true — запретить для всех, кроме Белого списка, 
 * false — запрет только по Черному списку
 * @var bool 
 */
$restrictAll = (bool)($restrictionsConfig['restrict_all'] ?? true);

/**
 * Черный список: инфоблоки, которые заблокированы всегда
 * @var array<int, string>
 */
$restrictedIblockMasks = (array)($restrictionsConfig['restricted_masks'] ?? []);

/**
 * Белый список:  инфоблоки, в которых разрешено управление разделами
 * @var array<int, string>
 */
$allowedIblockMasks = (array)($restrictionsConfig['allowed_masks'] ?? []);

/**
 * Проверяет, соответствует ли код маске
 * @param string|null $code
 * @param array $masks
 * @return bool
 */
function isCodeMatchMasks(?string $code, array $masks): bool
{
  if (empty($code)) return false;
  foreach ($masks as $mask) {
    if (@preg_match($mask . 'i', $code)) {
      return true;
    }
  }
  return false;
}

/**
 * Проверяет, выполняется ли контекст в миграции
 * @return bool
 */
function isMigrationContext(): bool
{
  if (PHP_SAPI === 'cli') return true;
  if (defined('SPRINT_MIGRATION_IN_PROGRESS') && SPRINT_MIGRATION_IN_PROGRESS === true) return true;
  $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
  foreach ($trace as $step) {
    if (isset($step['class']) && str_starts_with($step['class'], 'Sprint\Migration')) return true;
  }
  return false;
}

/**
 * Получает символьный код инфоблока по ID
 * @param int $iblockId
 * @return string|null
 */
function getIblockCodeById(int $iblockId): ?string
{
  static $cache = [];
  if (isset($cache[$iblockId])) return $cache[$iblockId];
  if (!Loader::includeModule('iblock')) return null;
  $row = IblockTable::getRow([
    'select' => ['CODE'],
    'filter' => ['=ID' => $iblockId],
    'cache' => ['ttl' => 3600]
  ]);
  $code = $row ? $row['CODE'] : null;
  $cache[$iblockId] = $code;
  return $code;
}

/**
 * Обработчик события OnPageStart - блокировка действий в админке
 */
$eventManager->addEventHandler('main', 'OnPageStart', function (): void {
  if (isMigrationContext()) return;

  $request = Application::getInstance()->getContext()->getRequest();
  /** @var \Bitrix\Main\HttpRequest $request */
  $pageSelf = (string)($_SERVER['SCRIPT_NAME'] ?? '');

  if (str_contains($pageSelf, 'userfield_edit.php') || str_contains($pageSelf, 'userfield_admin.php')) {
    if ($request->isPost() || $request->get('action') === 'delete' || $request->get('action_button') === 'delete') {
      @http_response_code(403);
      header('Content-Type: text/html; charset=utf-8');
      die('<h1>403 Forbidden</h1><p>Создание, изменение или удаление пользовательских полей заблокировано архитектурой проекта.</p>');
    }
  }

  if (str_contains($pageSelf, 'iblock_type_edit.php') || str_contains($pageSelf, 'iblock_type_admin.php')) {
    if ($request->isPost() || $request->get('action') === 'delete' || $request->get('action_button') === 'delete') {
      @http_response_code(403);
      header('Content-Type: text/html; charset=utf-8');
      die('<h1>403 Forbidden</h1><p>Создание, изменение или удаление типов инфоблоков заблокировано архитектурой проекта.</p>');
    }
  }

  if (str_contains($pageSelf, 'user_options.php') || str_contains($pageSelf, 'form_settings.php')) {

    $requestDataString = json_encode($_REQUEST);
    $shouldBlock = false;


    if (str_contains($requestDataString, 'form_element_') || str_contains($requestDataString, 'form_section_')) {
      $shouldBlock = true;
    }

    if ($shouldBlock) {
      if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        @http_response_code(200);
        header('Content-Type: application/json; charset=utf-8');
        die(json_encode(['ERROR' => 'Изменение или сброс внешнего вида форм детальных карточек полностью заблокирован архитектурой проекта.']));
      }

      @http_response_code(403);
      header('Content-Type: text/html; charset=utf-8');
      die('<h1>403 Forbidden</h1><p>Сброс или изменение внешнего вида форм детальных карточек заблокирован архитектурой проекта.</p>');
    }
  }
});

/**
 * События для ограничения изменения опций пользователей
 */
$restrictOptionEvents = ['OnBeforeUserOptionSet', 'OnBeforeUserOptionDelete'];
foreach ($restrictOptionEvents as $optionEvent) {
  $eventManager->addEventHandler('main', $optionEvent, function ($arFields = null) use ($restrictAll): bool {
    if (isMigrationContext()) return true;

    $args = func_get_args();
    $category = '';

    if (is_array($arFields) && isset($arFields['CATEGORY'])) {
      $category = (string)$arFields['CATEGORY'];
    } elseif (isset($args[0])) {
      $category = (string)$args[0];
    }

    if (!str_starts_with($category, 'form_element_') && !str_starts_with($category, 'form_section_')) {
      return true;
    }

    preg_match('/_(\d+)$/', $category, $matches);
    $iblockId = isset($matches[1]) ? (int)$matches[1] : 0;

    if ($iblockId > 0) {
      if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        @http_response_code(200);
        header('Content-Type: application/json; charset=utf-8');
        die(json_encode(['ERROR' => 'Изменение внешнего вида форм заблокировано архитектурой проекта.']));
      }
      return false;
    }

    return true;
  });
}

/**
 * События для ограничения управления пользовательскими свойствами (UF)
 */
$restrictMainEvents = ['OnBeforeUserTypeEntityAdd', 'OnBeforeUserTypeEntityUpdate', 'OnBeforeUserTypeEntityDelete'];
foreach ($restrictMainEvents as $eventName) {
  $eventManager->addEventHandler('main', $eventName, function (&$val) use ($restrictedIblockMasks, $restrictAll): bool {
    if (isMigrationContext()) return true;

    $entityId = '';
    if (is_array($val) && isset($val['ENTITY_ID'])) {
      $entityId = (string)$val['ENTITY_ID'];
    }

    global $APPLICATION;
    if ($restrictAll) {
      $APPLICATION->ThrowException('Управление пользовательскими свойствами (UF) заблокировано глобальной архитектурой проекта.');
      return false;
    }

    if (str_starts_with($entityId, 'IBLOCK_') && Loader::includeModule('iblock')) {
      preg_match('/^IBLOCK_(\d+)_(SECTION|ELEMENT)$/', $entityId, $matches);
      $iblockId = isset($matches[1]) ? (int)$matches[1] : 0;

      if ($iblockId > 0) {
        $iblockCode = getIblockCodeById($iblockId);
        $checkString = ($iblockCode !== null && $iblockCode !== '') ? strtolower(trim($iblockCode)) : (string)$iblockId;

        $isRestricted = false;
        /** @var array<int, string> $restrictedIblockMasks */
        foreach ($restrictedIblockMasks as $mask) {
          if (@preg_match($mask . 'i', $checkString)) {
            $isRestricted = true;
            break;
          }
        }

        if ($isRestricted) {
          $APPLICATION->ThrowException('Управление пользовательскими свойствами (UF) в данном инфоблоке запрещено.');
          return false;
        }
      }
    } else {
      $APPLICATION->ThrowException('Создание, изменение или удаление пользовательских полей (UF) запрещено архитектурой проекта.');
      return false;
    }
    return true;
  });
}

/**
 * События для ограничения создания/изменения/удаления инфоблоков
 */
foreach (['OnBeforeIBlockAdd', 'OnBeforeIBlockUpdate', 'OnBeforeIBlockDelete'] as $eventName) {
  $eventManager->addEventHandler('iblock', $eventName, function () use ($eventName): bool {
    if (isMigrationContext()) return true;
    global $APPLICATION;
    $action = match ($eventName) {
      'OnBeforeIBlockAdd' => 'Создание',
      'OnBeforeIBlockUpdate' => 'Изменение',
      'OnBeforeIBlockDelete' => 'Удаление'
    };
    $APPLICATION->ThrowException("$action инфоблоков запрещено архитектурой проекта.");
    return false;
  });
}

/**
 * События для ограничения управления свойствами инфоблоков
 */
foreach (['OnBeforeIBlockPropertyAdd', 'OnBeforeIBlockPropertyUpdate', 'OnBeforeIBlockPropertyDelete'] as $eventName) {
  $eventManager->addEventHandler('iblock', $eventName, function (&$arFields) use ($eventName, $restrictedIblockMasks, $allowedIblockMasks, $restrictAll): bool {
    if (isMigrationContext()) return true;
    if (!Loader::includeModule('iblock')) return true;

    $iblockId = 0;
    if (is_array($arFields)) {
      if (isset($arFields['IBLOCK_ID'])) $iblockId = (int)$arFields['IBLOCK_ID'];
      elseif (isset($arFields['ID'])) {
        $p = PropertyTable::getRow(['select' => ['IBLOCK_ID'], 'filter' => ['=ID' => (int)$arFields['ID']]]);
        $iblockId = $p ? (int)$p['IBLOCK_ID'] : 0;
      }
    }

    if ($iblockId <= 0) return true;
    $iblockCode = getIblockCodeById($iblockId);
    $checkString = ($iblockCode !== null && $iblockCode !== '') ? strtolower(trim($iblockCode)) : (string)$iblockId;

    $isAllowed = false;
    /** @var array<int, string> $allowedIblockMasks */
    foreach ($allowedIblockMasks as $mask) {
      if (@preg_match($mask . 'i', $checkString)) {
        $isAllowed = true;
        break;
      }
    }

    $isRestricted = false;
    /** @var array<int, string> $restrictedIblockMasks */
    foreach ($restrictedIblockMasks as $mask) {
      if (@preg_match($mask . 'i', $checkString)) {
        $isRestricted = true;
        break;
      }
    }

    $isProtected = !$isAllowed && ($isRestricted || $restrictAll);
    if (!$isProtected) return true;

    global $APPLICATION;
    $action = match ($eventName) {
      'OnBeforeIBlockPropertyAdd' => 'Создание',
      'OnBeforeIBlockPropertyUpdate' => 'Изменение',
      'OnBeforeIBlockPropertyDelete' => 'Удаление'
    };
    $APPLICATION->ThrowException("$action свойств в инфоблоке \"" . ($iblockCode ?? $iblockId) . "\" запрещено архитектурой проекта.");
    return false;
  });
}

/**
 * События для ограничения управления разделами инфоблоков
 */
foreach (['OnBeforeIBlockSectionAdd', 'OnBeforeIBlockSectionUpdate', 'OnBeforeIBlockSectionDelete'] as $eventName) {
  $eventManager->addEventHandler('iblock', $eventName, function (&$arFields) use ($eventName, $restrictedIblockMasks, $allowedIblockMasks, $restrictAll): bool {
    if (isMigrationContext()) return true;
    if (!Loader::includeModule('iblock')) return true;
    global $APPLICATION;

    $iblockId = 0;

    if ($eventName === 'OnBeforeIBlockSectionDelete' && !is_array($arFields)) {
      $sectionId = (int)$arFields;
      if ($sectionId > 0) {
        $s = SectionTable::getRow(['select' => ['IBLOCK_ID'], 'filter' => ['=ID' => $sectionId]]);
        $iblockId = $s ? (int)$s['IBLOCK_ID'] : 0;
      }
    } elseif (is_array($arFields)) {
      if (isset($arFields['IBLOCK_ID'])) {
        $iblockId = (int)$arFields['IBLOCK_ID'];
      } elseif (isset($arFields['ID'])) {
        $s = SectionTable::getRow(['select' => ['IBLOCK_ID'], 'filter' => ['=ID' => (int)$arFields['ID']]]);
        $iblockId = $s ? (int)$s['IBLOCK_ID'] : 0;
      }
    }

    if ($iblockId <= 0) return true;

    $rawCode = getIblockCodeById($iblockId);
    $iblockCode = $rawCode ? strtolower(trim($rawCode)) : '';
    $checkString = ($iblockCode !== '') ? $iblockCode : (string)$iblockId;

    $isAllowed = false;
    /** @var array<int, string> $allowedIblockMasks */
    foreach ($allowedIblockMasks as $mask) {
      if (@preg_match($mask . 'i', $checkString)) {
        $isAllowed = true;
        break;
      }
    }

    $isRestricted = false;
    /** @var array<int, string> $restrictedIblockMasks */
    foreach ($restrictedIblockMasks as $mask) {
      if (@preg_match($mask . 'i', $checkString)) {
        $isRestricted = true;
        break;
      }
    }

    $isProtected = !$isAllowed && ($isRestricted || $restrictAll);
    if (!$isProtected) return true;

    $iblockLabel = ($rawCode !== null && $rawCode !== '') ? $rawCode : (string)$iblockId;

    if ($eventName === 'OnBeforeIBlockSectionAdd') {
      $APPLICATION->ThrowException("Создание разделов в инфоблоке \"$iblockLabel\" запрещено.");
      return false;
    }
    if ($eventName === 'OnBeforeIBlockSectionDelete') {
      $APPLICATION->ThrowException("Удаление разделов в инфоблоке \"$iblockLabel\" запрещено.");
      return false;
    }

    if ($eventName === 'OnBeforeIBlockSectionUpdate' && is_array($arFields) && isset($arFields['ID'])) {
      $current = SectionTable::getRow([
        'select' => ['NAME', 'CODE'],
        'filter' => ['=ID' => (int)$arFields['ID']]
      ]);

      if ($current) {
        foreach (['NAME', 'CODE'] as $field) {
          if (isset($arFields[$field])) {
            $newVal = trim((string)$arFields[$field]);
            $oldVal = trim((string)$current[$field]);
            if ($newVal !== '' && $newVal !== $oldVal) {
              $APPLICATION->ThrowException(sprintf('Изменение поля %s раздела в инфоблоке "%s" запрещено.', $field, $iblockLabel));
              return false;
            }
          }
        }
      }
    }
    return true;
  });
}

/**
 * События для скрытия элементов интерфейса в админке
 */
$adminVisualEvents = [
  'main:OnAdminListDisplay',
  'main:OnAdminTabControlBegin'
];

foreach ($adminVisualEvents as $visualEvent) {
  [$module, $eventName] = explode(':', $visualEvent);

  $eventManager->addEventHandler($module, $eventName, function (&$param) use ($restrictedIblockMasks, $allowedIblockMasks, $restrictAll): void {
    if (isMigrationContext()) return;

    $request = Application::getInstance()->getContext()->getRequest();
    $currentIblockId = (int)$request->get('IBLOCK_ID');
    $pageSelf = (string)($_SERVER['SCRIPT_NAME'] ?? '');

    if ($currentIblockId <= 0 && Loader::includeModule('iblock')) {
      $elementId = (int)$request->get('ID');
      $findSection = (int)$request->get('find_section_section');

      if (str_contains($pageSelf, 'iblock_edit') && $elementId > 0) {
        $currentIblockId = $elementId;
      } elseif ($findSection > 0) {
        $section = SectionTable::getRow(['select' => ['IBLOCK_ID'], 'filter' => ['=ID' => $findSection]]);
        $currentIblockId = $section ? (int)$section['IBLOCK_ID'] : 0;
      } elseif ($elementId > 0) {
        if (str_contains($pageSelf, 'iblock_section_edit')) {
          $entity = SectionTable::getRow(['select' => ['IBLOCK_ID'], 'filter' => ['=ID' => $elementId]]);
        } else {
          $entity = ElementTable::getRow(['select' => ['IBLOCK_ID'], 'filter' => ['=ID' => $elementId]]);
        }
        $currentIblockId = $entity ? (int)$entity['IBLOCK_ID'] : 0;
      }
    }

    if ($currentIblockId <= 0) return;

    $rawCode = getIblockCodeById($currentIblockId);
    $iblockCode = $rawCode ? strtolower(trim($rawCode)) : '';
    $checkString = ($iblockCode !== '') ? $iblockCode : (string)$currentIblockId;

    $isAllowed = false;
    /** @var array<int, string> $allowedIblockMasks */
    foreach ($allowedIblockMasks as $mask) {
      if (@preg_match($mask . 'i', $checkString)) {
        $isAllowed = true;
        break;
      }
    }

    $isRestricted = false;
    /** @var array<int, string> $restrictedIblockMasks */
    foreach ($restrictedIblockMasks as $mask) {
      if (@preg_match($mask . 'i', $checkString)) {
        $isRestricted = true;
        break;
      }
    }

    $shouldHide = !$isAllowed && ($isRestricted || $restrictAll);

    if ($shouldHide) {
      Asset::getInstance()->addString('<style>
        .ui-toolbar-right-buttons a[href*="iblock_section_edit.php"]{
          display: none !important;
        }
        .ui-toolbar-right-buttons a[href*="iblock_section_edit.php"]+button,
        .ui-toolbar-right-buttons a[href*="iblock_element_edit.php"]+button {
          border-left: inherit !important;
          border-radius: var(--ui-btn-radius);
        }
        .adm-detail-toolbar-right a[href*="iblock_section_edit.php"],
        .adm-detail-toolbar-right a[href*="iblock_edit.php"], 
        .adm-detail-toolbar-right [href*="action=delete"],
        .menu-popup a[href*="iblock_section_admin.php"],
        .menu-popup .menu-popup-item[onclick*="\'delete\'"].menu-popup-item[onclick*="block_section"],
        .menu-popup .menu-popup-item[onclick*="\'code_translit\'"] {
          display: none !important;
        }
        #tabs_cont_tab_edit2, #tabs_cont_tab_edit2_title {
          display: none !important;
        }
        .main-dropdown-item-edit-properties, 
        a[href*="iblock_property_admin.php"],
        a[href*="userfield_edit.php"],
        a[href*="type_id="], 
        a[href*="iblock_type_edit.php"] {
          display: none !important;
        }
        .adm-detail-title-setting, .adm-detail-title-setting-active {
          display: none !important;
        }
        #sett_save, input[name="sett_save"], .adm-workarea #sett_save, #sett_common, #sett_reset {
          display: none !important;
        }
        </style>');
    }
  });
}
