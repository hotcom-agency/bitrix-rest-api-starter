<?php

namespace Sprint\Migration;

use Bitrix\Main\ModuleManager;
use Hotcom\Helpers\Migration;

class ModulesSettings20250526185847 extends Migration
{
  protected $author = "hc_admin";

  protected $description = "Настройки модулей";

  protected $moduleVersion = "5.3.3";

  protected $uninstalledModules = [];

  public function up()
  {
    $helper = $this->getHelperManager();

    // Удаление неиспользуемых модулей
    $modules_installed = ModuleManager::getInstalledModules();

    $modules_uninstalled = [];

    $modules_uninstalled_filter = [
      'location',
      'b24connector',
      'bitrixcloud',
      'clouds',
      'seo',
      'landing',
      'messageservice',
      'socialservices'
    ];

    $skipModules = is_array($modules_uninstalled_filter) ? $modules_uninstalled_filter : [$modules_uninstalled_filter];

    $modules_uninstalled = array_filter($modules_installed, function ($module) use ($skipModules) {
      return in_array($module['ID'], $skipModules);
    });

    foreach ($modules_uninstalled as $key => $value) {
      ModuleManager::delete($value['ID']);
      ModuleManager::unRegisterModule($value['ID']);
      $this->uninstalledModules[] = $value['ID'];
    }

    // Настройка main
    $helper->Option()->saveOption(array(
      'MODULE_ID' => 'main',
      'NAME' => '~PARAM_CLIENT_LANG',
      'VALUE' => 'ru',
    ));
    $helper->Option()->saveOption(array(
      'MODULE_ID' => 'main',
      'NAME' => '~PARAM_COMPOSITE',
      'VALUE' => 'N',
    ));
    $helper->Option()->saveOption(array(
      'MODULE_ID' => 'main',
      'NAME' => 'control_file_duplicates',
      'VALUE' => 'Y',
    ));
    $helper->Option()->saveOption(array(
      'MODULE_ID' => 'main',
      'NAME' => 'convert_mail_header',
      'VALUE' => 'Y',
    ));
    $helper->Option()->saveOption(array(
      'MODULE_ID' => 'main',
      'NAME' => 'convert_original_file_name',
      'VALUE' => 'Y',
    ));
    $helper->Option()->saveOption(array(
      'MODULE_ID' => 'main',
      'NAME' => 'CONVERT_UNIX_NEWLINE_2_WINDOWS',
      'VALUE' => 'N',
    ));
    $helper->Option()->saveOption(array(
      'MODULE_ID' => 'main',
      'NAME' => 'cookie_name',
      'VALUE' => 'BITRIX_SM',
    ));
    $helper->Option()->saveOption(array(
      'MODULE_ID' => 'main',
      'NAME' => 'dump_auto_enable_auto',
      'VALUE' => '0',
    ));
    $helper->Option()->saveOption(array(
      'MODULE_ID' => 'main',
      'NAME' => 'dump_base_auto',
      'VALUE' => '1',
    ));
    $helper->Option()->saveOption(array(
      'MODULE_ID' => 'main',
      'NAME' => 'dump_base_skip_log_auto',
      'VALUE' => '0',
    ));
    $helper->Option()->saveOption(array(
      'MODULE_ID' => 'main',
      'NAME' => 'email_from',
      'VALUE' => 'mail@localhost',
    ));
    $helper->Option()->saveOption(array(
      'MODULE_ID' => 'main',
      'NAME' => 'event_log_block_user',
      'VALUE' => 'Y',
    ));
    $helper->Option()->saveOption(array(
      'MODULE_ID' => 'main',
      'NAME' => 'event_log_cleanup_days',
      'VALUE' => '365',
    ));
    $helper->Option()->saveOption(array(
      'MODULE_ID' => 'main',
      'NAME' => 'event_log_file_access',
      'VALUE' => 'Y',
    ));
    $helper->Option()->saveOption(array(
      'MODULE_ID' => 'main',
      'NAME' => 'event_log_filelog',
      'VALUE' => 'Y',
    ));
    $helper->Option()->saveOption(array(
      'MODULE_ID' => 'main',
      'NAME' => 'event_log_filelog_path',
      'VALUE' => '',
    ));
    $helper->Option()->saveOption(array(
      'MODULE_ID' => 'main',
      'NAME' => 'event_log_group_edit',
      'VALUE' => 'Y',
    ));
    $helper->Option()->saveOption(array(
      'MODULE_ID' => 'main',
      'NAME' => 'event_log_group_policy',
      'VALUE' => 'Y',
    ));
    $helper->Option()->saveOption(array(
      'MODULE_ID' => 'main',
      'NAME' => 'event_log_login_fail',
      'VALUE' => 'Y',
    ));
    $helper->Option()->saveOption(array(
      'MODULE_ID' => 'main',
      'NAME' => 'event_log_login_success',
      'VALUE' => 'Y',
    ));
    $helper->Option()->saveOption(array(
      'MODULE_ID' => 'main',
      'NAME' => 'event_log_logout',
      'VALUE' => 'Y',
    ));
    $helper->Option()->saveOption(array(
      'MODULE_ID' => 'main',
      'NAME' => 'event_log_marketplace',
      'VALUE' => 'Y',
    ));
    $helper->Option()->saveOption(array(
      'MODULE_ID' => 'main',
      'NAME' => 'event_log_module_access',
      'VALUE' => 'Y',
    ));
    $helper->Option()->saveOption(array(
      'MODULE_ID' => 'main',
      'NAME' => 'event_log_password_change',
      'VALUE' => 'Y',
    ));
    $helper->Option()->saveOption(array(
      'MODULE_ID' => 'main',
      'NAME' => 'event_log_password_request',
      'VALUE' => 'Y',
    ));
    $helper->Option()->saveOption(array(
      'MODULE_ID' => 'main',
      'NAME' => 'event_log_permissions_fail',
      'VALUE' => 'Y',
    ));
    $helper->Option()->saveOption(array(
      'MODULE_ID' => 'main',
      'NAME' => 'event_log_register',
      'VALUE' => 'Y',
    ));
    $helper->Option()->saveOption(array(
      'MODULE_ID' => 'main',
      'NAME' => 'event_log_register_fail',
      'VALUE' => 'Y',
    ));
    $helper->Option()->saveOption(array(
      'MODULE_ID' => 'main',
      'NAME' => 'event_log_syslog',
      'VALUE' => 'Y',
    ));
    $helper->Option()->saveOption(array(
      'MODULE_ID' => 'main',
      'NAME' => 'event_log_task',
      'VALUE' => 'Y',
    ));
    $helper->Option()->saveOption(array(
      'MODULE_ID' => 'main',
      'NAME' => 'event_log_user_delete',
      'VALUE' => 'Y',
    ));
    $helper->Option()->saveOption(array(
      'MODULE_ID' => 'main',
      'NAME' => 'event_log_user_edit',
      'VALUE' => 'Y',
    ));
    $helper->Option()->saveOption(array(
      'MODULE_ID' => 'main',
      'NAME' => 'event_log_user_groups',
      'VALUE' => 'Y',
    ));

    // Настройка fileman
    $helper->Option()->saveOption(array(
      'MODULE_ID' => 'fileman',
      'NAME' => 'ml_max_height',
      'VALUE' => '2600',
    ));
    $helper->Option()->saveOption(array(
      'MODULE_ID' => 'fileman',
      'NAME' => 'ml_max_width',
      'VALUE' => '2600',
    ));
    $helper->Option()->saveOption(array(
      'MODULE_ID' => 'fileman',
      'NAME' => 'ml_media_available_ext',
      'VALUE' => 'jpg,jpeg,gif,png,pdf,flv,mp4,wmv,wma,mp3,ppt,doc,docx,xls,xlsx,pptx',
    ));
    $helper->Option()->saveOption(array(
      'MODULE_ID' => 'fileman',
      'NAME' => 'ml_media_extentions',
      'VALUE' => 'jpg,jpeg,gif,png,pdf,flv,mp4,wmv,wma,mp3,ppt',
    ));

    if ($this->uninstalledModules && count($this->uninstalledModules) > 0) {
      $this->outSuccess('Удаленные модули: ' . json_encode($this->uninstalledModules));
    }
  }

  /**
   * @throws Exceptions\HelperException
   * @return bool|void
   */
  public function down()
  {
    $this->outWarning('В данной миграции откат не предусмотрен');
  }
}
