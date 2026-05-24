<?php

use Bitrix\Main\EventManager;
use Bitrix\Main\Context;

/**
 * Регистрация глобального перехватчика страниц на старте загрузки ядра Битрикса
 */
EventManager::getInstance()->addEventHandler(
  "main",
  "OnPageStart",
  "BlockFilemanAdminForEveryone"
);

/**
 * Блокирование прямого административного доступа к файловому менеджеру для всех пользователей
 * 
 * @return void
 */
function BlockFilemanAdminForEveryone()
{
  $request = Context::getCurrent()->getRequest();
  $requestUri = $request->getRequestedPage();

  // Проверка обращения к системному файлу управления структурой и файлами
  if (strpos($requestUri, '/bitrix/admin/fileman_admin.php') !== false) {

    /** @var \Bitrix\Main\HttpResponse $response */
    $response = Context::getCurrent()->getResponse();
    $response->setStatus(403);

    // Подключение штатной страницы ошибки модуля проактивной защиты Битрикса
    if (file_exists($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/security/admin/security_403.php")) {
      include($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/security/admin/security_403.php");
    }

    die("403 Forbidden. Access Denied.");
  }
}
