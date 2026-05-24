<?php

use Bitrix\Main\EventManager;

$eventManager = EventManager::getInstance();

/**
 * Автоматическое добавление фоновых агентов для генерации миниатюр после сохранения файла
 */
$eventManager->addEventHandler("main", "OnAfterFileSave", function ($arFile) {
  // Проверка типа контента на принадлежность к графическим файлам
  if (!str_contains($arFile["CONTENT_TYPE"], "image/")) return;

  $fileId = (int)($arFile['ID'] ?? $arFile['file_id'] ?? 0);
  if ($fileId > 0) {
    \CAgent::AddAgent(
      "\\Hotcom\\Helpers\\Image::getThumbsAgent($fileId);",
      "main",
      "N",
      0,
      "",
      "Y",
      "",
      30
    );
  }
});

/**
 * Полное удаление всех сгенерированных миниатюр и копий WebP при удалении оригинального файла
 */
$eventManager->addEventHandler("main", "OnFileDelete", function ($arFile) {
  $uploadDir = \Bitrix\Main\Config\Option::get("main", "upload_dir", "upload");
  $docRoot = $_SERVER['DOCUMENT_ROOT'];
  $originalPath = $docRoot . "/" . $uploadDir . "/" . $arFile["SUBDIR"] . "/" . $arFile["FILE_NAME"];
  $webpOriginal = str_ireplace(['.jpg', '.jpeg', '.gif', '.png'], '.webp', $originalPath);

  // Удаление сопутствующего графического файла в формате WebP
  if (file_exists($webpOriginal)) @unlink($webpOriginal);

  $originalNameNoExt = pathinfo($arFile["FILE_NAME"], PATHINFO_FILENAME);
  $resizePattern = $docRoot . "/" . $uploadDir . "/resize_cache/" . $arFile["SUBDIR"] . "/*/" . $originalNameNoExt . ".*";
  $files = glob($resizePattern);

  // Поиск и очистка кэшированных уменьшенных копий в директории resize_cache
  if (is_array($files)) {
    foreach ($files as $file) {
      @unlink($file);
      @rmdir(dirname($file));
    }
  }

  @rmdir($docRoot . "/" . $uploadDir . "/resize_cache/" . $arFile["SUBDIR"]);
});
