<?php

use Bitrix\Main\EventManager;

/**
 * Подключение файлов с классами кастомных свойств
 */
require_once __DIR__ . '/../properties/CUserPropertyElementBoolean.php';
require_once __DIR__ . '/../properties/CUserPropertyElementKeyValue.php';
require_once __DIR__ . '/../properties/CUserPropertySectionHtmlType.php';

/**
 * Регистрация кастомных свойств элементов инфоблока
 */
EventManager::getInstance()->addEventHandler(
  "iblock", 
  "OnIBlockPropertyBuildList", 
  ['CUserPropertyElementBoolean', 'GetUserTypeDescription']
);

EventManager::getInstance()->addEventHandler(
  "iblock", 
  "OnIBlockPropertyBuildList", 
  ['CUserPropertyElementKeyValue', 'GetUserTypeDescription']
);

/**
 * Регистрация кастомных пользовательских полей (UFX) для разделов инфоблока
 */
EventManager::getInstance()->addEventHandler(
  "main", 
  "OnUserTypeBuildList", 
  ['CUserPropertySectionHtmlType', 'GetUserTypeDescription']
);
