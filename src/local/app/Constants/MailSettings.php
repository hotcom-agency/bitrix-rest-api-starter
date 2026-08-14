<?php

namespace Hotcom\Constants;

use Bitrix\Main\Config\Option;

/**
 * Управление настройками почтовых отправлений
 * 
 * @package Hotcom\Constants
 */
class MailSettings
{
  public const EMAIL_FROM = 'MS_EMAIL_FROM';
  public const EMAIL_TO = 'MS_EMAIL_TO';
  public const EMAIL_COPY = 'MS_EMAIL_COPY';
  public const EMAIL_SALES_TO = 'MS_EMAIL_SALES_TO';
  public const EMAIL_SALES_COPY = 'MS_EMAIL_SALES_COPY';

  private const OPTION_MODULE_ID = 'hotcom.main';

  private const ENV_MAPPING = [
    self::EMAIL_FROM       => 'BITRIX_MAIL_EMAIL_FROM',
    self::EMAIL_TO         => 'BITRIX_MAIL_EMAIL_TO',
    self::EMAIL_COPY       => 'BITRIX_MAIL_EMAIL_COPY',
    self::EMAIL_SALES_TO   => 'BITRIX_MAIL_EMAIL_SALES_TO',
    self::EMAIL_SALES_COPY => 'BITRIX_MAIL_EMAIL_SALES_COPY',
  ];

  /**
   * Получение значения из переменных окружения, глобальных констант, настроек модуля или значения по умолчанию
   * 
   * @param string $constName Название константы
   * @param string $default Значение по умолчанию
   * @return string
   */
  private static function getValue(string $constName, string $default = ''): string
  {
    // 1. Проверка переменных окружения из .env через $_ENV или getenv()
    $envKey = self::ENV_MAPPING[$constName] ?? '';
    if ($envKey !== '') {
      $envValue = $_ENV[$envKey] ?? getenv($envKey);
      if (is_string($envValue) && $envValue !== '') {
        return $envValue;
      }
    }

    // 2. Проверка глобальной константы
    if (defined($constName)) {
      $value = constant($constName);
      if (is_string($value) && $value !== '') {
        return $value;
      }
    }

    // 3. Получение значения из настроек модуля Битрикс
    $optionValue = Option::get(self::OPTION_MODULE_ID, $constName, '');
    if ($optionValue !== '') {
      return $optionValue;
    }

    // 4. Возврат значения по умолчанию
    return $default;
  }

  /**
   * Получение адреса отправителя писем
   * 
   * @param string|null $default Значение по умолчанию
   * @return string
   */
  public static function getEmailFromValue(?string $default = null): string
  {
    if ($default === null || $default === '') {
      $default = 'no-reply@localhost';
    }

    return self::getValue(self::EMAIL_FROM, $default);
  }

  /**
   * Получение основного адреса получателя писем
   * 
   * @param string $default Значение по умолчанию
   * @return string
   */
  public static function getEmailToValue(string $default = 'admin@localhost'): string
  {
    return self::getValue(self::EMAIL_TO, $default);
  }

  /**
   * Получение адреса для отправки копий писем
   * 
   * @param string $default Значение по умолчанию
   * @return string
   */
  public static function getEmailCopyValue(string $default = ''): string
  {
    return self::getValue(self::EMAIL_COPY, $default);
  }

  /**
   * Получение основного адреса отдела продаж
   * 
   * @param string $default Значение по умолчанию
   * @return string
   */
  public static function getEmailSalesToValue(string $default = 'sales@localhost'): string
  {
    return self::getValue(self::EMAIL_SALES_TO, $default);
  }

  /**
   * Получение адреса для отправки копий писем отдела продаж
   * 
   * @param string $default Значение по умолчанию
   * @return string
   */
  public static function getEmailSalesCopyValue(string $default = ''): string
  {
    return self::getValue(self::EMAIL_SALES_COPY, $default);
  }
}
