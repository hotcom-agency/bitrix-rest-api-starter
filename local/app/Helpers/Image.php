<?php

namespace Hotcom\Helpers;

use CFile;

/**
 * Помощник для обработки, масштабирования и конвертации изображений
 * 
 * @package Hotcom\Helpers
 */
class Image
{
  /**
   * Масштабирование и оптимизация изображения с генерацией WebP-копии
   * 
   * @param int|string|null $id Идентификатор файла в Битриксе
   * @param int $width Максимальная ширина
   * @param int $height Максимальная высота
   * @param bool $crop Флаг жесткой обрезки по заданным размерам
   * @return array{url: string, url_webp: string|false, width: int, height: int}|null
   */
  public static function get(int|string|null $id = null, int $width = 2400, int $height = 1600, bool $crop = false): ?array
  {
    if (!$id) return null;

    $img = CFile::ResizeImageGet(
      $id,
      array("width" => $width, "height" => $height),
      ($crop ? 1 : 2), // 1 — EXACT, 2 — PROPORTIONAL_ALT
      true,
      false,
      false,
      80
    );

    if (!$img) return null;

    $imgWebp = self::makeWebp((string)$img['src']);

    return [
      'url' => (string)$img['src'],
      'url_webp' => $imgWebp,
      'width' => (int)$img['width'],
      'height' => (int)$img['height']
    ];
  }

  /**
   * Генерация набора стандартных миниатюр (адаптивных размеров) для фронтенда
   * 
   * @param int|string|null $id Идентификатор файла в Битриксе
   * @return array<string, array|null>|null
   */
  public static function getThumbs(int|string|null $id = null): ?array
  {
    if (!$id) return null;

    return [
      'xs' => self::get($id, 620),
      'sm' => self::get($id, 992),
      'md' => self::get($id, 1440),
      'lg' => self::get($id, 2400)
    ];
  }

  /**
   * Создание и сохранение копии графического файла в формате WebP
   * 
   * @param string|null $src Относительный путь к исходному файлу
   * @param bool $rewrite Флаг принудительной перезаписи существующего файла
   * @return string|false Путь к созданному файлу или false при ошибке
   */
  public static function makeWebp(?string $src, bool $rewrite = false)
  {
    if ($src && function_exists('imagewebp')) {
      $newImgPath = str_ireplace(array('.jpg', '.jpeg', '.gif', '.png'), '.webp', $src);
      $fullPath = $_SERVER['DOCUMENT_ROOT'] . $src;
      $fullNewPath = $_SERVER['DOCUMENT_ROOT'] . $newImgPath;

      if (!file_exists($fullNewPath) || $rewrite) {
        if (!file_exists($fullPath)) return false;

        $info = getimagesize($fullPath);
        if ($info !== false && ($type = $info[2])) {
          $newImg = null;
          switch ($type) {
            case IMAGETYPE_JPEG:
              $newImg = imagecreatefromjpeg($fullPath);
              break;
            case IMAGETYPE_GIF:
              $newImg = imagecreatefromgif($fullPath);
              break;
            case IMAGETYPE_PNG:
              $newImg = imagecreatefrompng($fullPath);
              if ($newImg) {
                imagepalettetotruecolor($newImg);
                imagealphablending($newImg, true);
                imagesavealpha($newImg, true);
              }
              break;
          }

          if ($newImg) {
            imagewebp($newImg, $fullNewPath, 80);
            imagedestroy($newImg);
          }
        }
      }

      if (file_exists($fullNewPath)) {
        return $newImgPath;
      }
    }

    return false;
  }

  /**
   * Обработка графических файлов через механизм агентов Битрикса в фоновом режиме
   * 
   * @param int $fileId Идентификатор файла в Битриксе
   * @return null
   */
  public static function getThumbsAgent(int $fileId): null
  {
    self::getThumbs($fileId);
    return null;
  }
}
