<?php

use Bitrix\Main\EventManager;

/**
 * Регистрация кастомных свойств элементов инфоблока
 */
EventManager::getInstance()->addEventHandler(
  "iblock", 
  "OnIBlockPropertyBuildList", 
  ['Hotcom\CustomProperties\ElementBoolean', 'GetUserTypeDescription']
);

EventManager::getInstance()->addEventHandler(
  "iblock", 
  "OnIBlockPropertyBuildList", 
  ['Hotcom\CustomProperties\ElementKeyValue', 'GetUserTypeDescription']
);

/**
 * Регистрация кастомных пользовательских полей (UFX) для разделов инфоблока
 */
EventManager::getInstance()->addEventHandler(
  "main", 
  "OnUserTypeBuildList", 
  ['Hotcom\CustomProperties\SectionHtmlType', 'GetUserTypeDescription']
);
