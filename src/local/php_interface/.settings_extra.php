<?php
$isDevelopmentMode = getenv('MODE') === 'DEVELOPMENT' || ($_ENV['MODE'] ?? '') === 'DEVELOPMENT';

$config = [
  // Конфигурация модуля защиты структуры
  'hotcom_iblock_restrictions' => [
    'value' => [
      // Блокировка разделов (секций)
      'section_restrict_all' => true,
      'section_restricted_masks' => [
        // '/^page/',
      ],
      'section_allowed_masks' => [
        // '/^page/',
      ],
      // Блокировка элементов — отдельное правило
      'element_restrict_all' => false,
      'element_restricted_masks' => [
        // '/^page/',
      ],
      'element_allowed_masks' => [
        // '/^page/',
      ],
    ],
    'readonly' => false,
  ],

  // Конфигурация кэширования API
  'hotcom_api_cache' => [
    'value' => [
      'enabled' => $isDevelopmentMode ? false : true,
    ],
    'readonly' => false,
  ],
];

// Локальный оверрайд конфигурации (файл .settings_extra.local.php не в git):
// например, чтобы включить кэш API в dev-режиме локально:
//   return ['hotcom_api_cache' => ['value' => ['enabled' => true]]];
$localSettingsPath = __DIR__ . '/.settings_extra.local.php';
if (file_exists($localSettingsPath)) {
  $config = array_replace_recursive($config, require $localSettingsPath);
}

return $config;
