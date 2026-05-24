<?php

namespace Hotcom\Helpers;

use Bitrix\Main\Context;
use Bitrix\Main\ORM\Objectify\EntityObject;

/**
 * Помощник для управления локализацией, мультиязычностью и переводами ORM-сущностей
 * 
 * @package Hotcom\Helpers
 */
class Localization
{
  /**
   * Получение списка всех поддерживаемых языковых локалей системы
   * 
   * @return array<string, array{lang: string, name: string, locale: string, postfix: string, active: bool, default?: bool}>
   */
  public static function locales(): array
  {
    return array(
      'ru' => array(
        'lang' => 'ru',
        'name' => 'Русский',
        'locale' => 'ru_RU',
        'postfix' => '',
        'active' => true,
        'default' => true
      ),
      'en' => array(
        'lang' => 'en',
        'name' => 'English',
        'locale' => 'en_GB',
        'postfix' => '_EN',
        'active' => true
      )
    );
  }

  /**
   * Получение информации о выбранном текущем языке на основе HTTP-заголовков или параметров запроса
   * 
   * @return array{lang: string, name: string, locale: string, postfix: string, active: bool}
   */
  public static function current(): array
  {
    $request = Context::getCurrent()->getRequest();
    $lang = $request->getServer()->get('HTTP_X_LANGUAGE');

    if (!$lang) {
      $lang = $request->get('lang');
    }

    if (!$lang) {
      $acceptLanguage = $request->getServer()->get('HTTP_ACCEPT_LANGUAGE') ?: 'ru';
      $lang = substr((string)$acceptLanguage, 0, 2);
    }

    $lang = strtolower((string)$lang);
    $locales = self::locales();

    return $locales[$lang] ?? $locales['ru'];
  }

  /**
   * Получение двухсимвольного кода текущего языка
   * 
   * @return string
   */
  public static function getLang(): string
  {
    return self::current()['lang'];
  }

  /**
   * Получение читаемого названия текущего языка
   * 
   * @return string
   */
  public static function getName(): string
  {
    return self::current()['name'];
  }

  /**
   * Получение языкового регионального постфикса для названий полей базы данных
   * 
   * @return string
   */
  public static function getPostfix(): string
  {
    return self::current()['postfix'];
  }

  /**
   * Получение системного кода локали для текущего контекста исполнения
   * 
   * @return string
   */
  public static function getLocale(): string
  {
    return self::current()['locale'];
  }

  /**
   * Получение массива символьных ключей поля для всех зарегистрированных языков
   *
   * @param string $key Базовый символьный код поля инфоблока
   * @return string[] Массив мультиязычных ключей для выборки
   */
  public static function getlocalizedFieldsByKey($key): array
  {
    $keys = [];
    foreach (self::locales() as $locale) {
      $keys[] = $key . strtoupper($locale['postfix']);
    }
    return array_values($keys);
  }

  /**
   * Получение локализованного значения свойства из плоского массива полей элемента
   *
   * @param array<string, mixed> $elementFields Полный массив полей и свойств элемента
   * @param string $baseKey Базовый символьный код поля без языкового префикса
   * @return mixed|null
   */
  public static function getLocalizedField(array $elementFields, string $baseKey)
  {
    $postfix = self::getPostfix();
    $localizedKey = $baseKey . $postfix;

    if (isset($elementFields[$localizedKey]) && $elementFields[$localizedKey] !== '') {
      return $elementFields[$localizedKey];
    }

    return null;
  }

  /**
   * Получение локализованного значения свойства напрямую из ORM-объекта сущности Битрикса
   *
   * @param EntityObject $element ORM-объект тестируемой сущности
   * @param string $baseMethod Базовое название метода-геттера без языкового префикса
   * @return mixed Локализованное значение свойства или null
   */
  public static function getOrmValue(EntityObject $element, string $baseMethod): mixed
  {
    $postfix = self::getPostfix();
    $cleanPostfix = !empty($postfix) ? ucfirst(strtolower(ltrim($postfix, '_'))) : '';
    $method = $baseMethod . $cleanPostfix;

    return method_exists($element, $method) || method_exists($element, '__call')
      ? $element->$method()?->getValue()
      : null;
  }
}
