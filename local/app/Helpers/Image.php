<?php

namespace Hotcom\Helpers;

use CFile;
use Imagick;

/**
 * Помощник для обработки, масштабирования и конвертации изображений
 * 
 * @package Hotcom\Helpers
 */
class Image
{
  /** @var array<string, array> Кэш результатов внутри текущего хит-запроса */
  private static array $cache = [];

  /**
   * Масштабирование и оптимизация изображения с генерацией WebP-копии
   * 
   * @param int|string|null $id Идентификатор файла в Битриксе
   * @param int $width Максимальная ширина
   * @param int $height Максимальная высота (по умолчанию избыточна для авторасчета)
   * @param bool $crop Флаг жесткой обрезки по заданным размерам
   * @return array{url: string, url_webp: string|false, width: int, height: int}|null
   */
  public static function get(int|string|null $id = null, int $width = 2400, int $height = 1600, bool $crop = false): ?array
  {
    if (!$id) return null;

    $key = "{$id}_{$width}_{$height}_" . ($crop ? 'c' : 'f');
    if (isset(self::$cache[$key])) return self::$cache[$key];

    $file = CFile::GetFileArray($id);
    if (!$file) return null;

    $ext = strtolower(pathinfo((string)$file['SRC'], PATHINFO_EXTENSION));

    $resizeType = $crop ? 2 : 1;
    $jpgQuality = ($ext === 'png') ? false : 80;

    $img = CFile::ResizeImageGet(
      $file,
      ['width' => $width, 'height' => $height],
      $resizeType,
      true,
      false,
      false,
      $jpgQuality
    );

    if (!$img) return null;

    $src = (string)$img['src'];
    $path = $_SERVER['DOCUMENT_ROOT'] . $src;

    if ($ext === 'png' && file_exists($path) && filesize($path) > 500_000) {
      $testImg = @imagecreatefrompng($path);
      if ($testImg) {
        $isTrueColor = imageistruecolor($testImg);
        imagedestroy($testImg);

        if ($isTrueColor === true) {
          self::compressPng($src);
        }
      }
    }

    // Генерация WebP через Imagick/GD
    $webp = self::makeWebp($src);

    return self::$cache[$key] = [
      'url' => $src,
      'url_webp' => $webp,
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
   * Оптимизация PNG файла через консольную утилиту pngquant
   * 
   * @param string $srcPath Относительный путь к файлу изображения
   * @return void
   */
  private static function compressPng(string $srcPath): void
  {
    $fullPath = $_SERVER['DOCUMENT_ROOT'] . $srcPath;

    if (strtolower(pathinfo($fullPath, PATHINFO_EXTENSION)) !== 'png' || !file_exists($fullPath)) {
      return;
    }

    $command = "pngquant --force --quality 65-80 --ext .png " . escapeshellarg($fullPath) . " 2>&1";
    exec($command);
  }

  /**
   * Создание и сохранение копии графического файла в формате WebP через Imagick/GD
   * 
   * @param string|null $src Относительный путь к исходному файлу
   * @param bool $rewrite Флаг принудительной перезаписи существующего файла
   * @return string|false Путь к созданному файлу или false при ошибке
   */
  public static function makeWebp(?string $src, bool $rewrite = false): string|false
  {
    if (!$src) return false;

    $webp = preg_replace('/\.[^.]+$/', '.webp', $src);
    if ($webp === null) return false;

    $root = $_SERVER['DOCUMENT_ROOT'];
    $full = $root . $src;
    $fullWebp = $root . $webp;

    if (file_exists($fullWebp) && !$rewrite) return $webp;
    if (!file_exists($full)) return false;

    // Попытка конвертации через Imagick
    if (class_exists(Imagick::class) && in_array('WEBP', Imagick::queryFormats(), true)) {
      try {
        $im = new Imagick($full);

        /** @var Imagick $im */
        $im->setImageFormat('webp');
        $im->setImageCompressionQuality(80);
        $im->stripImage();

        if ($im->writeImage($fullWebp)) {
          $im->clear();
          $im->destroy();
          return $webp;
        }
      } catch (\Throwable) {
        // Фоллбек на GD при любой ошибке Imagick
      }
    }

    // Фоллбек на встроенную библиотеку GD
    if (!function_exists('imagewebp')) return false;
    $info = @getimagesize($full);
    if (!$info) return false;

    $type = $info[2];
    $img = match ($type) {
      IMAGETYPE_JPEG => @imagecreatefromjpeg($full),
      IMAGETYPE_GIF  => @imagecreatefromgif($full),
      IMAGETYPE_PNG  => @imagecreatefrompng($full),
      default => null
    };

    if (!$img) return false;

    if ($type === IMAGETYPE_PNG) {
      @imagepalettetotruecolor($img);
      @imagealphablending($img, true);
      @imagesavealpha($img, true);
    }

    @imagewebp($img, $fullWebp, 80);
    imagedestroy($img);

    return file_exists($fullWebp) ? $webp : false;
  }

  /**
   * Обработка графических файлов через механизм агентов Битрикса в фоновом режиме
   * 
   * @param int $fileId Идентификатор файла в Битриксе
   * @return string
   */
  public static function getThumbsAgent(int $fileId): string
  {
    self::getThumbs($fileId);
    return "";
  }
}
