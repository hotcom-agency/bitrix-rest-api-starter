<?php

namespace Hotcom\Helpers;

use Bitrix\Main\Application;
use Bitrix\Main\Config\Configuration;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\Data\TaggedCache;

/**
 * Компонент кэширования для API сервисов с поддержкой тегирования и гранулярной очистки
 * 
 * @package Hotcom\Helpers
 */
class ApiCache
{
  private const PREFIX = 'apicache_';
  private const TAG_PREFIX = 'api_';
  private const CACHE_DIR = 'apicache/';

  private Cache $cache;
  private TaggedCache $taggedCache;
  private bool $isEnabled;

  /**
   * Инициализация базовых компонентов кэширования Битрикса
   */
  public function __construct()
  {
    $config = Configuration::getInstance()->get('hotcom_api_cache');

    $this->cache = Application::getInstance()->getCache();
    $this->taggedCache = Application::getInstance()->getTaggedCache();
    $this->isEnabled = (bool)($config['enabled'] ?? true);
  }

  /**
   * Получение данных из кэша по ключу или вычисление через callback при их отсутствии
   *
   * @param string   $key      Ключ кэша
   * @param callable $callback Функция для получения данных при отсутствии в кэше
   * @param array    $tags     Теги для кэширования
   * @param int      $ttl      Время жизни кэша в секундах
   *
   * @return mixed
   */
  public function get(string $key, callable $callback, array $tags = [], int $ttl = 3600): mixed
  {
    if (!$this->isEnabled) {
      return $callback();
    }

    $cacheKey = self::PREFIX . md5($key);

    // Попытка чтения данных из кэша
    if ($this->cache->initCache($ttl, $cacheKey, self::CACHE_DIR)) {
      return $this->cache->getVars();
    }

    // Вычисление данных через callback при отсутствии кэша
    if ($this->cache->startDataCache()) {
      try {
        $data = $callback();

        // Запуск процесса тегирования
        $this->taggedCache->startTagCache(self::CACHE_DIR);
        $this->taggedCache->registerTag(self::TAG_PREFIX . 'all');

        foreach ($tags as $tag) {
          $this->taggedCache->registerTag(self::TAG_PREFIX . $tag);
        }

        $this->taggedCache->endTagCache();
        $this->cache->endDataCache($data);

        return $data;
      } catch (\Exception $e) {
        $this->cache->abortDataCache();
        throw $e;
      }
    }

    return null;
  }

  /**
   * Сборка универсального набора тегов для информационных блоков
   *
   * @param int $iblockId Идентификатор информационного блока
   * @param array<string|int> $customTags Дополнительные пользовательские теги кэша
   * @return array<string|int>
   */
  public function getIblockTags(int $iblockId, array $customTags = []): array
  {
    $baseTags = [
      'iblock_' . $iblockId
    ];

    return array_merge($baseTags, $customTags);
  }

  /**
   * Сохранение произвольных данных в кэш с привязкой к тегам
   *
   * @param string $key Ключ кэша
   * @param mixed $data Данные для сохранения
   * @param array<string|int> $tags Теги для кэширования
   * @param int $ttl Время жизни кэша в секундах
   * @return bool
   */
  public function set(string $key, $data, array $tags = [], int $ttl = 7200): bool
  {
    if (!$this->isEnabled) {
      return false;
    }

    $cacheKey = self::PREFIX . md5($key);

    if ($this->cache->startDataCache($ttl, $cacheKey, self::CACHE_DIR)) {
      $this->taggedCache->startTagCache(self::CACHE_DIR);

      // Регистрация общего системного тега для всех API кэшей
      $this->taggedCache->registerTag(self::TAG_PREFIX . 'all');

      foreach ($tags as $tag) {
        $this->taggedCache->registerTag(self::TAG_PREFIX . $tag);
      }

      $this->taggedCache->endTagCache();

      $this->cache->endDataCache($data);
      return true;
    }

    return false;
  }

  /**
   * Очистка кэша по строковому тегу
   *
   * @param string $tag Тег для очистки кэша
   */
  public function clearByTag(string $tag): void
  {
    $this->taggedCache->clearByTag(self::TAG_PREFIX . $tag);
  }

  /**
   * Очистка кэша по идентификатору информационного блока
   *
   * @param int $iblockId Идентификатор информационного блока
   */
  public function clearByIblock(int $iblockId): void
  {
    $this->clearByTag('iblock_' . $iblockId);
  }

  /**
   * Очистка кэша по символьному коду раздела
   *
   * @param string $sectionCode Символьный код раздела
   */
  public function clearBySection(string $sectionCode): void
  {
    $this->clearByTag('section_' . $sectionCode);
  }

  /**
   * Очистка кэша по комбинации информационного блока и символьного кода раздела
   *
   * @param int $iblockId Идентификатор информационного блока
   * @param string $sectionCode Символьный код раздела
   */
  public function clearByIblockAndSection(int $iblockId, string $sectionCode): void
  {
    $this->clearByTag('iblock_' . $iblockId);
    $this->clearByTag('section_' . $sectionCode);
    $this->clearByTag('iblock_' . $iblockId . '_section_' . $sectionCode);
  }

  /**
   * Полная очистка всего API кэша приложения и удаление файлов из директории
   */
  public function clearAll(): void
  {
    // Очистка по общему системному тегу для сброса связанных записей
    $this->taggedCache->clearByTag(self::TAG_PREFIX . 'all');

    // Очистка целевой физической директории для удаления остаточных файлов кэша
    $this->cache->cleanDir(self::CACHE_DIR);
  }
}
