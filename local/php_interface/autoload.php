<?php if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$docRoot = $_SERVER['DOCUMENT_ROOT'] ?? dirname(__FILE__, 3);

$autoloadPath = $docRoot . '/vendor/autoload.php';

if (is_file($autoloadPath)) {
  require_once $autoloadPath;
}
