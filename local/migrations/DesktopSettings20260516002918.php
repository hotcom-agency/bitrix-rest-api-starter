<?php

namespace Sprint\Migration;

use Hotcom\Helpers\Migration;

class DesktopSettings20260516002918 extends Migration
{
  protected $author = "hc_admin";
  protected $description = "Настройки рабочего стола";
  protected $moduleVersion = "5.6.4";

  private function getDesktopData(): array
  {
    $handoverDate = date('d.m.Y');

    return [
      0 => [
        'GADGETS' => [
          'HTML_AREA@233927441' => [
            'COLUMN' => 0,
            'ROW' => 0,
            'USERDATA' => [
              'content' => '<table class="bx-gadgets-info-site-table" cellspacing="0">
                                <tbody>
                                    <tr><td class="bx-gadget-gray">Создатель сайта:</td>
                                    <td><a href="https://hotcom.agency" target="_blank">hotcom.agency</a></td>
                                    <td class="bx-gadgets-info-site-logo" rowspan="5"></td></tr>
                                    <tr><td class="bx-gadget-gray">Адрес сайта:</td>
                                    <td><a href="/" target="_blank">localhost</a></td></tr>
                                    <tr><td class="bx-gadget-gray">Сайт сдан:</td>
                                    <td>' . $handoverDate . ' г.</td></tr>
                                </tbody>
                            </table>',
            ],
            'HIDE' => 'N',
            'SETTINGS' => ['TITLE_STD' => 'Информация о проекте'],
          ],
          'DASHBOARD@496559380' => ['COLUMN' => 0, 'ROW' => 1, 'HIDE' => 'N', 'USERDATA' => null],
          'ADMIN_PERFMON@666666666' => ['COLUMN' => 1, 'ROW' => 0, 'HIDE' => 'N', 'USERDATA' => null],
          'ADMIN_CHECKLIST@835884592' => ['COLUMN' => 1, 'ROW' => 1, 'HIDE' => 'N', 'USERDATA' => null],
        ],
        'COLS' => 2,
        'arCOLUMN_WIDTH' => ['50%', '50%'],
        'NAME' => 'Панель Bitrix',
      ],
    ];
  }

  public function up()
  {
    \CUserOptions::DeleteOption('intranet', '~gadgets_admin_index', false, 0);
    \CUserOptions::DeleteOption('intranet', 'admin_index', false, 0);

    \CUserOptions::DeleteOption('intranet', '~gadgets_admin_index', true, 0);
    \CUserOptions::DeleteOption('intranet', 'admin_index', true, 0);

    \CUserOptions::SetOption('intranet', '~gadgets_admin_index', $this->getDesktopData(), true);
    \CUserOptions::SetOption('intranet', 'admin_index', ['~gadgets_admin_index'], true);

    \Bitrix\Main\Application::getInstance()->getManagedCache()->cleanAll();
    \Bitrix\Main\Application::getInstance()->getManagedCache()->cleanDir("/main/user_options/");
  }

  public function down()
  {
    \CUserOptions::DeleteOption('intranet', '~gadgets_admin_index', true, 0);
    \CUserOptions::DeleteOption('intranet', 'admin_index', true, 0);

    \Bitrix\Main\Application::getInstance()->getManagedCache()->cleanAll();
    \Bitrix\Main\Application::getInstance()->getManagedCache()->cleanDir("/main/user_options/");
  }
}
