<?php

namespace Hotcom\Helpers;

use CFile;
use Bitrix\Highloadblock as HL;
use Bitrix\Iblock\Iblock;
use Hotcom\Helpers\Localization;
use Hotcom\Helpers\Image;

/**
 * Вспомогательный класс для работы с API Bitrix, ORM и форматированием данных.
 */
class Bitrix
{

  private static array $clearedIblocks = [];

  /**
   * Возвращаем путь к файлу и его MIME-тип по ID.
   *
   * @param int|string $ID ID файла.
   * @return array{url: string, mime: string}|null
   */
  public static function getFilePathByID(int|string $ID): ?array
  {
    if ($ID) {
      $file = CFile::GetByID((int)$ID);
      $return_file = $file->Fetch();
      if ($return_file) {
        $return_url = '/upload/' . $return_file['SUBDIR'] . '/' . $return_file['FILE_NAME'];
        return ['url' => $return_url, 'mime' => (string)($return_file['CONTENT_TYPE'] ?? '')];
      }
    }
    return null;
  }

  /**
   * Фильтруем и переименовываем ключи массива на основе текущей локали приложения.
   *
   * @param array<string, mixed> $array Исходный массив свойств.
   * @return array<string, mixed>
   */
  public static function localizedProps(array $array): array
  {
    $propsLocalized = [];
    $currentLang = Localization::getLang();
    $allLocales = Localization::locales();

    $allPostfixes = [];
    foreach ($allLocales as $loc) {
      if (!empty($loc['postfix'])) {
        $allPostfixes[] = (string)$loc['postfix'];
      }
    }

    foreach ($array as $key => $value) {
      foreach ($allLocales as $locale) {
        $postfix = (string)($locale['postfix'] ?? '');
        $lang = (string)($locale['lang'] ?? '');

        if (!isset($propsLocalized[$key])) {
          if ($currentLang === $lang && $postfix !== '') {
            $isOtherPostfix = false;
            foreach ($allPostfixes as $p) {
              if (str_ends_with($key, $p)) {
                $isOtherPostfix = true;
                break;
              }
            }
            if (!$isOtherPostfix) {
              $propsLocalized[$key] = $value;
            }
          }

          if ($currentLang === $lang && $postfix === '' && $key !== 'NAME') {
            $propsLocalized[$key] = $value;
          }

          if ($currentLang === $lang && $postfix !== '' && str_ends_with($key, $postfix)) {
            $k = str_replace($postfix, '', $key);
            $propsLocalized[$k] = $value;
          }
        }

        if ($currentLang !== $lang && $postfix !== '' && str_ends_with($key, $postfix)) {
          unset($propsLocalized[$key]);
        }
      }
    }

    return $propsLocalized;
  }

  /**
   * Удаляем префикс UF_ из ключей массива пользовательских полей.
   *
   * @param array<string, mixed> $array Массив данных (раздела или HL-блока).
   * @param bool $uf_only Если true, вернет только те ключи, которые содержали UF_.
   * @return array<string, mixed>
   */
  public static function sectionUfReplace(array $array, bool $uf_only = false): array
  {
    $filtered = $uf_only ? array_filter($array, fn($key) => str_contains((string)$key, 'UF_'), ARRAY_FILTER_USE_KEY) : $array;

    return array_combine(
      array_map(fn($key) => str_replace('UF_', '', (string)$key), array_keys($filtered)),
      $filtered
    ) ?: [];
  }

  /**
   * Декодируем HTML-сущности и очищаем строку от управляющих символов.
   *
   * @param string|null $html Исходная строка.
   * @param bool $nl2br Преобразовывать ли переносы строк в <br>.
   * @return string|null
   */
  public static function htmlTypeDecode(?string $html = '', bool $nl2br = false): ?string
  {
    if (!$html) return null;
    return htmlspecialchars_decode(preg_replace('/[\\\\\r\n\t]/', '', $nl2br ? nl2br($html) : $html));
  }

  /**
   * Рекурсивно переводим все ключи массива в нижний регистр.
   *
   * @param array<mixed> $arr Исходный массив.
   * @return array<mixed>
   */
  public static function arrKeysToLower(array $arr): array
  {
    $result = array_change_key_case($arr);
    return array_map(function ($item) {
      if (is_array($item)) {
        $item = self::arrKeysToLower($item);
      }
      return $item;
    }, $result) ?: [];
  }

  /**
   * Форматируем массив элемента со всеми привязками и пользовательскими полями.
   *
   * @param array<string, mixed> $element Данные элемента.
   * @param array<string, array<string, mixed>> $properties Метаданные свойств (SETTINGS, USER_TYPE_ID).
   * @return array<string, mixed>|null
   */
  public static function sectionUfFormat(array $element = [], array $properties = []): ?array
  {
    $propertiesMap = [];
    foreach ($properties as $value) {
      if (isset($value['FIELD_NAME'])) {
        $propertiesMap[$value['FIELD_NAME']] = $value;
      }
    }

    foreach ($element as $key => $value) {
      $elInfo = $propertiesMap[$key] ?? null;
      if (!$elInfo) continue;

      $elArray = [];

      // Обработка файлов (File)
      if (($elInfo['USER_TYPE_ID'] ?? null) == 'file' && $value) {
        $extensions = $elInfo['SETTINGS']['EXTENSIONS'] ?? [];
        if (is_array($extensions) && !empty(array_intersect(['jpg', 'jpeg', 'png', 'webp'], array_keys($extensions)))) {
          if (is_array($value)) {
            foreach ($value as $el) $elArray[] = Image::getThumbs($el);
            $value = $elArray;
          } else {
            $value = Image::getThumbs($value);
          }
        } else {
          if (is_array($value)) {
            foreach ($value as $el) $elArray[] = self::getFilePathByID($el);
            $value = $elArray;
          } else {
            $value = self::getFilePathByID($value);
          }
        }
      }
      // Обработка строк (String)
      elseif (($elInfo['USER_TYPE_ID'] ?? null) == 'string') {
        if (is_array($value)) {
          foreach ($value as $el) $elArray[] = self::htmlTypeDecode($el);
          $value = $elArray;
        } else {
          $value = self::htmlTypeDecode($value);
        }
      }
      // Привязка к элементам инфоблока (Iblock Element)
      elseif (($elInfo['USER_TYPE_ID'] ?? null) == 'iblock_element' && $value) {
        $IblockId = $elInfo['SETTINGS']['IBLOCK_ID'] ?? null;
        if ($IblockId) {
          /** @var class-string<\Bitrix\Main\ORM\Data\DataManager> $dataManager */
          $dataManager = Iblock::wakeUp($IblockId)->getEntityDataClass();
          $IblockElArray = $dataManager::getList(['filter' => ['ID' => $value]])->fetchAll();
          $IblockElUfArray = [];

          $ids = array_column($IblockElArray, 'ID');
          if (!empty($ids)) {
            \CIBlockElement::GetPropertyValuesArray($IblockElUfArray, $IblockId, ['ID' => $ids, 'IBLOCK_ID' => $IblockId]);
            $IblockOutput = [];
            foreach ($IblockElArray as $ibEl) {
              $id = $ibEl['ID'] ?? null;
              if ($id !== null) {
                // @phpstan-ignore nullCoalesce.offset
                $ufData = $IblockElUfArray[$id] ?? [];
                $IblockOutput[] = self::IbElementResponse($ibEl, $ufData);
              }
            }
            $value = $IblockOutput;
          }
        }
      }
      // Обработка Highload-блоков (HL-block) с оптимизацией файлов
      elseif (($elInfo['USER_TYPE_ID'] ?? null) == 'hlblock' && $value) {
        $hlblockId = $elInfo['SETTINGS']['HLBLOCK_ID'] ?? null;
        if ($hlblockId) {
          $hlblock = HL\HighloadBlockTable::getById($hlblockId)->fetch();
          if ($hlblock) {
            /** @var class-string<\Bitrix\Main\ORM\Data\DataManager> $entity */
            $entity = HL\HighloadBlockTable::compileEntity($hlblock)->getDataClass();
            $list = $entity::getList(["filter" => ["ID" => $value]])->fetchAll();

            if (is_array($list)) {
              $imageExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
              $allFileIds = [];
              foreach ($list as $el) {
                foreach ($el as $k => $v) {
                  if (str_starts_with((string)$k, 'UF_') && !empty($v)) {
                    if (is_array($v)) $allFileIds = array_merge($allFileIds, $v);
                    else $allFileIds[] = $v;
                  }
                }
              }
              $allFileIds = array_unique(array_filter($allFileIds, 'is_numeric'));

              $fileDataMap = [];
              if (!empty($allFileIds)) {
                $rsFiles = \CFile::GetList([], ['@ID' => implode(',', $allFileIds)]);
                while ($f = $rsFiles->Fetch()) {
                  $ext = strtolower(pathinfo((string)$f['FILE_NAME'], PATHINFO_EXTENSION));
                  $fileDataMap[$f['ID']] = in_array($ext, $imageExtensions) ? 'image' : 'file';
                }
              }

              $output = [];
              foreach ($list as $el) {
                foreach ($el as $k => $v) {
                  if (str_starts_with((string)$k, 'UF_') && !empty($v)) {
                    $isMultiple = is_array($v);
                    $fIds = $isMultiple ? $v : [$v];
                    $processed = [];
                    foreach ($fIds as $fId) {
                      if (isset($fileDataMap[$fId])) {
                        $processed[] = ($fileDataMap[$fId] === 'image') ? Image::getThumbs($fId) : self::getFilePathByID($fId);
                      }
                    }
                    if (!empty($processed)) $el[$k] = $isMultiple ? $processed : $processed[0];
                  }
                }
                $output[] = self::localizedProps(self::sectionUfReplace($el));
              }
              $value = is_array($value) ? $output : ($output[0] ?? null);
            }
          }
        }
      }
      $element[$key] = $value;
    }
    return self::localizedProps(self::sectionUfReplace($element));
  }

  /**
   * Форматируем стандартный элемент инфоблока (поля и свойства).
   *
   * @param array<string, mixed> $element Поля элемента (ID, NAME и т.д.).
   * @param array<string, mixed> $property Массив свойств элемента.
   * @return array<string, mixed>
   */
  public static function IbElementResponse(array $element, array $property): array
  {
    $props = [];
    foreach ($property as $prop) {
      $code = (string)($prop['CODE'] ?? '');
      if ($code === '') continue;

      $value = $prop['VALUE'] ?? null;
      $isMultiple = ($prop['MULTIPLE'] ?? 'N') === 'Y';
      $type = $prop['PROPERTY_TYPE'] ?? 'S';

      $values = $isMultiple && is_array($value) ? $value : [$value];
      $result = [];

      if ($type === 'F') {
        $fileType = (string)($prop['FILE_TYPE'] ?? '');
        $isImage = (bool)preg_match('/jpg|jpeg|png|webp/i', $fileType);
        foreach ($values as $v) {
          if ($v) $result[] = $isImage ? Image::getThumbs($v) : self::getFilePathByID($v);
        }
      } elseif ($type === 'L') {
        foreach ($values as $v) {
          if ($v === null) continue;
          $result[] = ['VALUE' => $v === "Y" ? true : $v, 'XML_VALUE' => $prop['VALUE_XML_ID'] ?? null];
        }
      } elseif ($type === 'E') {
        foreach ($values as $v) {
          if (!$v) continue;
          $linkedEl = \CIBlockElement::GetByID((int)$v)->Fetch();
          if ($linkedEl) {
            $result[] = ['id' => (int)$linkedEl['ID'], 'name' => $linkedEl['NAME'], 'slug' => $linkedEl['CODE'], 'iblock_id' => (int)$linkedEl['IBLOCK_ID']];
          }
        }
      } elseif (is_array($value) && isset($value['TYPE'])) {
        $result[] = self::htmlTypeDecode($value['TEXT'] ?? null, false);
      } else {
        foreach ($values as $v) {
          if ($v === null || $v === '') continue;
          $v = $v === "Y" ? true : $v;
          $result[] = ($type === 'S') ? self::htmlTypeDecode((string)$v) : $v;
        }
      }
      $props[$code] = $isMultiple ? $result : ($result[0] ?? null);
    }

    return [
      'id' => (int)($element['ID'] ?? 0),
      'active' => ($element['ACTIVE'] ?? "N") === "Y",
      'sort' => (int)($element['SORT'] ?? 0),
      'name' => $element['NAME'] ?? '',
      'slug' => $element['CODE'] ?? '',
      ...self::localizedProps($props)
    ];
  }
  /**
   * HTTP-запрос через cURL (GET или POST).
   * Поддерживает работу через прокси и передачу параметров.
   *
   * @param string $queryUrl URL запроса.
   * @param array<mixed> $queryData Массив передаваемых параметров.
   * @param bool $post Выполнить запрос методом POST (по умолчанию false).
   * @param string $proxy Адрес прокси-сервера.
   * @param string $proxy_login Данные для авторизации на прокси (user:pass).
   * @return string|bool Ответ сервера или false в случае ошибки.
   */
  public static function curl(
    string $queryUrl,
    array $queryData = [],
    bool $post = false,
    string $proxy = '',
    string $proxy_login = ''
  ): string|bool {
    $curl = curl_init();

    if ($post !== true) {
      if (count($queryData)) {
        $sep = str_contains($queryUrl, '?') ? '&' : '?';
        $queryUrl .= $sep . http_build_query($queryData);
      }
    } else {
      curl_setopt($curl, CURLOPT_POST, true);
      curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($queryData));
    }

    curl_setopt_array($curl, [
      CURLOPT_URL => $queryUrl,
      CURLOPT_SSL_VERIFYPEER => false,
      CURLOPT_HEADER => false,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_CONNECTTIMEOUT => 10,
    ]);

    if ($proxy) {
      curl_setopt($curl, CURLOPT_PROXY, $proxy);
      if ($proxy_login) {
        curl_setopt($curl, CURLOPT_PROXYUSERPWD, $proxy_login);
      }
    }

    $result = curl_exec($curl);
    curl_close($curl);

    return $result;
  }

  /**
   * Защита от дублирования очистки кеша инфоблока за один PHP-хит.
   * Гарантирует, что кеш конкретного IBLOCK_ID сбросится ровно 1 раз,
   * даже если в цикле обновляется 10 000 элементов.
   *
   * @param array<string, mixed> $val Данные из обработчика событий Битрикса
   * @return bool True, если этот инфоблок на текущем хите еще ТРЕБУЕТСЯ очистить
   */
  public static function multipleRequestHandler(array $val): bool
  {
    $iblockId = isset($val['IBLOCK_ID']) ? (int)$val['IBLOCK_ID'] : 0;
    if ($iblockId <= 0) {
      return false;
    }

    if (isset($_REQUEST['URL_DATA_FILE']) || defined('SESS_SEARCHER')) {
      return false;
    }

    if (isset(self::$clearedIblocks[$iblockId])) {
      return false;
    }

    self::$clearedIblocks[$iblockId] = true;

    return true;
  }

  /**
   * Универсальная генерация детерминированного хэша для любого DTO
   * 
   * @param object $dto Объект фильтра (RoomFilterDto, ServiceFilterDto и т.д.)
   * @return string
   */
  public static function getDtoHash(object $dto): string
  {
    // Превращаем public-свойства DTO в ассоциативный массив
    $params = get_object_vars($dto);

    // Рекурсивно сортируем по ключам, чтобы порядок не влиял на хэш
    self::ksortRecursive($params);

    return md5(json_encode($params, JSON_UNESCAPED_UNICODE));
  }

  /**
   * Рекурсивная сортировка массива по ключам
   */
  private static function ksortRecursive(array &$array): void
  {
    ksort($array);
    foreach ($array as &$value) {
      if (is_array($value)) {
        self::ksortRecursive($value);
      }
    }
  }
}
