<?php

use Bitrix\Main\Config\Option;
use Bitrix\Main\EventManager;
use Bitrix\Main\Type\DateTime;

/**
 * Автоматическое добавление фоновых агентов для генерации миниатюр после сохранения файла
 */
EventManager::getInstance()->addEventHandler("main", "OnAfterFileSave", function (&$arFile) {
  static $isProcessing = false;
  if ($isProcessing) return;

  if (empty($arFile["CONTENT_TYPE"]) || !str_contains((string)$arFile["CONTENT_TYPE"], "image/")) return;

  $fileId = (int)($arFile['ID'] ?? 0);
  if ($fileId <= 0) return;

  $agentName = "\\Hotcom\\Helpers\\Image::getThumbsAgent({$fileId});";
  $escapedName = addcslashes($agentName, '_%\\');

  if (!\CAgent::GetList([], ['NAME' => $escapedName, 'USER_ID' => false])->Fetch()) {
    $isProcessing = true;
    $nextExecTime = (new DateTime())->add('+5 seconds')->toString();
    \CAgent::AddAgent($agentName, "main", "N", 0, "", "Y", $nextExecTime, 30);
    $isProcessing = false;
  }
});

/**
 * Полное удаление всех сгенерированных миниатюр и копий WebP при удалении оригинального файла
 */
EventManager::getInstance()->addEventHandler('main', 'OnFileDelete', static function ($arFile) {
  $fileId = (int)($arFile['ID'] ?? 0);
  if ($fileId <= 0) return;

  $docRoot = $_SERVER['DOCUMENT_ROOT'];
  $uploadDir = Option::get('main', 'upload_dir', 'upload');

  $subdir = (string)($arFile["SUBDIR"] ?? '');
  $fileName = (string)($arFile["FILE_NAME"] ?? '');
  if (empty($subdir) || empty($fileName)) return;

  $originalPath = "{$docRoot}/{$uploadDir}/{$subdir}/{$fileName}";
  clearstatcache(true, $originalPath);

  if (file_exists($originalPath)) return;

  $webpOriginal = preg_replace('/\.[^.]+$/', '.webp', $originalPath);
  if ($webpOriginal !== null && file_exists($webpOriginal)) {
    @chmod($webpOriginal, 0777);
    @unlink($webpOriginal);
  }

  $markerPath = $originalPath . '.pngquant';
  if (file_exists($markerPath)) @unlink($markerPath);

  $rootCacheDir = "{$docRoot}/{$uploadDir}/resize_cache/{$subdir}";
  if (is_dir($rootCacheDir)) exec('rm -rf ' . escapeshellarg($rootCacheDir));
});
