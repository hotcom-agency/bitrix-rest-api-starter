<?php

namespace Sprint\Migration;

use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Hotcom\Helpers\Migration;

class CreateUserGroupContentManager20250526203231 extends Migration
{
  protected $author = "hc_admin";
  protected $description = "Создание группы \"Контент-менеджер\" и настройка прав доступа";
  protected $moduleVersion = "5.3.3";

  public function up()
  {
    $helper = $this->getHelperManager();

    $groupId = $helper->UserGroup()->saveGroup('content-managers', [
      'ACTIVE' => 'Y',
      'C_SORT' => '100',
      'ANONYMOUS' => 'N',
      'NAME' => 'Контент-менеджеры',
      'DESCRIPTION' => 'Группа с доступом в админ-панель для управления контентом',
      'SECURITY_POLICY' => [],
    ]);

    if (!$groupId) {
      $this->outError('Не удалось создать группу content-managers');
      return false;
    }

    $this->setModuleAdminTask('main', $groupId, 'P');
    $this->setFileAccessRights($groupId, 'R');
    $this->setMedialibCollectionRights($groupId, 'medialib_full', 0);
    if (Loader::includeModule('security')) {
      $this->setModuleAdminTask('security', $groupId, 'F');
    } else {
      $this->outWarning('Модуль "security" (Проактивная защита) не установлен');
    }

    $this->outSuccess("Группа content-managers (ID: {$groupId}) создана и права установлены.");
  }

  public function down()
  {
    $this->outInfo('Откат не предусмотрен');
  }

  /**
   * Права модуля через стандартный API Битрикса
   */
  protected function setModuleRights(string $moduleId, int $groupId, string $rightCode): void
  {
    if ($moduleId !== 'main' && !Loader::includeModule($moduleId)) return;

    $key = "G{$groupId}";
    $raw = \Bitrix\Main\Config\Option::get($moduleId, 'GROUP_RIGHTS', '');

    $rights = [];
    if ($raw !== '') {
      $unserialized = @unserialize($raw, ['allowed_classes' => false]);
      if (is_array($unserialized)) {
        $rights = $unserialized;
      }
    }

    $rights[$key] = $rightCode;

    \Bitrix\Main\Config\Option::set($moduleId, 'GROUP_RIGHTS', serialize($rights));

    $this->out("→ Права '{$rightCode}' на {$moduleId} для группы {$groupId} установлены");
  }

  /**
   * Права на папку /bitrix/admin/ через .access.php
   */
  protected function setFileAccessRights(int $groupId, string $rightCode): void
  {
    $path = Application::getDocumentRoot() . '/bitrix/.access.php';

    /** @var array{admin?: string, user?: string} $PERM */
    $PERM = [];

    if (file_exists($path)) {
      include $path;
    }

    $PERM['admin'] = $PERM['admin'] ?? [];
    if (($PERM['admin'][$groupId] ?? '') === $rightCode) return;

    $PERM['admin'][$groupId] = $rightCode;
    file_put_contents($path, "<?php\n\$PERM = " . var_export($PERM, true) . ";\n", LOCK_EX);
    $this->out("→ Права '{$rightCode}' на admin для группы {$groupId}");
  }

  /**
   * Права на коллекции Медиабиблиотеки (UPSERT через REPLACE)
   */
  protected function setMedialibCollectionRights(int $groupId, string $taskName, int $collectionId = 0): void
  {
    if (!Loader::includeModule('fileman')) return;

    /** @var \Bitrix\Main\DB\Connection $connection */
    $connection = Application::getConnection();
    $sqlHelper = $connection->getSqlHelper();

    $task = $connection->query("
            SELECT ID FROM b_task 
            WHERE MODULE_ID = 'fileman' AND SYS = 'Y' AND NAME = '" . $sqlHelper->forSql($taskName) . "'
            LIMIT 1
        ")->fetch();

    if (!$task) {
      $this->outWarning("Задача '{$taskName}' не найдена в b_task");
      return;
    }

    $connection->queryExecute("
            REPLACE INTO b_group_collection_task (COLLECTION_ID, GROUP_ID, TASK_ID) 
            VALUES ({$collectionId}, {$groupId}, {$task['ID']})
        ");

    $this->out("→ Права '{$taskName}' (ID: {$task['ID']}) для группы {$groupId} установлены");
  }

  /**
   * Права к административным частям модуля через стандартный API Битрикса
   */
  protected function setModuleAdminTask(string $moduleId, int $groupId, string $taskLetter): void
  {
    if (!Loader::includeModule('main')) return;

    $currentTasks = \CGroup::GetTasks($groupId);

    /** @var \Bitrix\Main\DB\Connection $connection */
    $connection = Application::getConnection();
    $sqlHelper = $connection->getSqlHelper();

    if ($moduleId === 'main' && $taskLetter === 'R') {
      $task = $connection->query("
            SELECT ID, NAME, LETTER FROM b_task 
            WHERE MODULE_ID = 'main' 
              AND SYS = 'Y' 
              AND NAME = 'main_view_all_settings'
            LIMIT 1
        ")->fetch();
    } else {
      $task = $connection->query("
            SELECT ID, NAME, LETTER FROM b_task 
            WHERE MODULE_ID = '" . $sqlHelper->forSql($moduleId) . "' 
              AND SYS = 'Y' 
              AND LETTER = '" . $sqlHelper->forSql($taskLetter) . "'
            LIMIT 1
        ")->fetch();
    }

    if (!$task) {
      $this->outWarning("Задача '{$taskLetter}' для модуля '{$moduleId}' не найдена");
      return;
    }

    $taskId = (int)$task['ID'];

    if (!in_array($taskId, $currentTasks)) {
      $currentTasks[] = $taskId;
      \CGroup::SetTasks($groupId, $currentTasks);
      $this->out("→ Задача '{$taskLetter}' (ID: {$taskId}, NAME: {$task['NAME']}) для модуля '{$moduleId}' добавлена группе {$groupId}");
    } else {
      $this->out("→ Задача '{$taskLetter}' (ID: {$taskId}) уже есть у группы {$groupId}");
    }
  }
}
