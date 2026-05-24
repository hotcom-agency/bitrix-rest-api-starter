<?php


return array(
  'migration_dir' => '/local/migrations',
  'show_admin_interface' => true,
  'console_user'  => 'hc_admin',
  'version_builders' => array_merge(
    [
     'MigrationBuilder' => '\Hotcom\Helpers\MigrationBuilder'
    ],
    Sprint\Migration\VersionConfig::getDefaultBuilders()
  ),
);
