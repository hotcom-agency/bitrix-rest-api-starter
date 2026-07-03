#!/usr/bin/env bash
set -e

NC='\033[0m'
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
BLUE='\033[0;34m'
LIGHT_BLUE='\033[38;5;117m'

echo -e "${YELLOW}⏳ Начинаем конфигурацию Bitrix после установки${NC}"

# Удаляем установочные и временные файлы
echo -e "${BLUE}Удаление установочных и временных файлов..${NC}"
INSTALL_FILES=(
  bitrixsetup.php
  restore.php
  readme.html
  index.php
  install.config
  favicon.ico
  urlrewrite.php
  .section.php
)

for f in "${INSTALL_FILES[@]}"; do
  if [ -f "$f" ]; then
    rm -f "$f"
    echo -e "${LIGHT_BLUE} - $f${NC}"
  fi
done

# Копируем index.php из шаблона
if [ -f "config/index.php.template" ]; then
  if [ ! -f "index.php" ]; then
    cp config/index.php.template index.php
    echo -e "${LIGHT_BLUE} - config/index.php.template -> index.php${NC}"
  else
    echo -e "${LIGHT_BLUE} - index.php уже существует, пропускаем копирование.${NC}"
  fi
else
  echo -e "${YELLOW} - config/index.php.template не найден, пропускаем.${NC}"
fi

# Копируем .settings.php из шаблона внутри именованного тома если отсутствует
echo -e "${BLUE}Проверяем .settings.php..${NC}"
docker compose exec -T php sh -c '
  if [ -f "/var/www/html/config/.settings.php.template" ] && [ ! -f "/var/www/html/bitrix/.settings.php" ]; then
    cp /var/www/html/config/.settings.php.template /var/www/html/bitrix/.settings.php
    chown 82:82 /var/www/html/bitrix/.settings.php
    echo -e "\033[38;5;117m - Скопирован .settings.php из шаблона\033[0m"
  else
    echo -e "\033[38;5;117m - Файл .settings.php готов.\033[0m"
  fi
'

# Копируем .settings_extra.php из шаблона если отсутствует
echo -e "${BLUE}Проверяем .settings_extra.php..${NC}"
if [ -f "config/.settings_extra.php.example" ] && [ ! -f "local/php_interface/.settings_extra.php" ]; then
  cp config/.settings_extra.php.example local/php_interface/.settings_extra.php
  echo -e "${LIGHT_BLUE} - Скопирован .settings_extra.php из шаблона${NC}"
elif [ ! -f "config/.settings_extra.php.example" ]; then
  echo -e "${YELLOW} - config/.settings_extra.php.template не найден, пропускаем.${NC}"
else
  echo -e "${LIGHT_BLUE} - Файл .settings_extra.php готов.${NC}"
fi

# Проверяем подключение к Redis
echo -e "${BLUE}Проверка подключения к Redis..${NC}"
REDIS_CHECK=$(docker compose exec -T php php << 'PHPEOF'
<?php
$host = getenv('REDIS_HOST') ?: ($_ENV['REDIS_HOST'] ?? 'redis');
$port = (int)(getenv('REDIS_PORT') ?: ($_ENV['REDIS_PORT'] ?? 6379));

if (!extension_loaded('redis')) {
    echo "EXT_MISSING";
    exit(1);
}

$redis = new Redis();
try {
    if (!$redis->connect($host, $port, 2)) {
        echo "CONN_FAILED";
        exit(1);
    }
} catch (\Throwable $e) {
    echo "CONN_FAILED";
    exit(1);
}

$testKey = 'bitrix:healthcheck:' . uniqid();
if (!$redis->setex($testKey, 10, 'OK')) {
    echo "WRITE_FAILED";
    exit(1);
}

if ($redis->get($testKey) !== 'OK') {
    echo "READ_FAILED";
    exit(1);
}

echo "OK";
exit(0);
PHPEOF
)

case "$REDIS_CHECK" in
  "OK")
    echo -e "${LIGHT_BLUE} - Redis готов к работе и функционирует.${NC}"
    ;;
  "EXT_MISSING")
    echo -e "${RED} - Расширение PHP redis не загружено.${NC}"
    echo -e "${RED} - Добавьте в Dockerfile: RUN pecl install redis && docker-php-ext-enable redis${NC}"
    exit 1
    ;;
  "CONN_FAILED")
    echo -e "${RED} - Не удается подключиться к серверу Redis.${NC}"
    echo -e "${RED} - Проверьте, запущен ли контейнер redis и убедитесь в правильности REDIS_HOST/PORT в .env.${NC}"
    exit 1
    ;;
  "WRITE_FAILED" | "READ_FAILED")
    echo -e "${RED} - Операция чтения/записи Redis завершилась ошибкой.${NC}"
    echo -e "${RED} - Проверьте логи redis: docker compose logs redis${NC}"
    exit 1
    ;;
  *)
    echo -e "${RED} - Неизвестная ошибка проверки Redis. Вывод: '$REDIS_CHECK'${NC}"
    exit 1
    ;;
esac

# Оптимизация автозагрузчика Composer и проверка вывода
echo -e "${BLUE}Оптимизация автозагрузчика Composer..${NC}"
if docker compose exec -T php composer dump-autoload --optimize --quiet 2>/dev/null; then
  if docker compose exec -T php test -f vendor/autoload.php 2>/dev/null; then
    echo -e "${LIGHT_BLUE} - Автозагрузчик Composer оптимизирован и проверен.${NC}"
  else
    echo -e "${YELLOW} - Команда автозагрузчика выполнена успешно, но vendor/autoload.php отсутствует.${NC}"
  fi
else
  echo -e "${RED} - Оптимизация Composer пропущена или завершилась ошибкой.${NC}"
fi

# Плавный перезапуск PHP-FPM
docker compose exec -T php kill -USR2 1 >/dev/null 2>&1 || true
for i in $(seq 1 10); do
  docker compose exec -T php php -r "exit(0);" 2>/dev/null && break
  sleep 1
done

# Деинсталляция и удаление демонстрационного решения bitrix.sitecorporate
echo -e "${BLUE}Деинсталляция и удаление демонстрационного решения bitrix.sitecorporate..${NC}"
docker compose exec -T php php -d display_errors=0 << 'PHPEOF'
<?php
$_SERVER['DOCUMENT_ROOT'] = '/var/www/html';
define('NOT_CHECK_PERMISSIONS', true);
define('NO_KEEP_STATISTIC', true);
define('BX_NO_ACCELERATOR_RESET', true);

if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php')) {
    require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

    $moduleId = 'bitrix.sitecorporate';
    $LB = "\033[38;5;117m";
    $NC = "\033[0m";

    if (\Bitrix\Main\ModuleManager::isModuleInstalled($moduleId)) {
        $connection = \Bitrix\Main\Application::getConnection();
        $demoTables = ['b_site_corporate'];
        
        foreach ($demoTables as $table) {
            if ($connection->isTableExists($table)) {
                $connection->dropTable($table);
            }
        }

        \Bitrix\Main\ModuleManager::unRegisterModule($moduleId);
        
        $modulePath = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $moduleId;
        if (is_dir($modulePath)) {
            exec("rm -rf " . escapeshellarg($modulePath));
        }

        echo "${LB} - Модуль bitrix.sitecorporate успешно деинсталлирован и удален через CLI.${NC}\n";
    } else {
        echo "${LB} - Модуль bitrix.sitecorporate не установлен. Удалять нечего.${NC}\n";
    }
}
PHPEOF

# Автоматическая установка и активация модуля sprint.migration
echo -e "${BLUE}Установка и активация модуля sprint.migration..${NC}"
docker compose exec -T php php -d display_errors=0 << 'PHPEOF'
<?php
$_SERVER['DOCUMENT_ROOT'] = '/var/www/html';
define('NOT_CHECK_PERMISSIONS', true);
define('NO_KEEP_STATISTIC', true);
define('BX_NO_ACCELERATOR_RESET', true);

if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php')) {
    require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

    $moduleId = 'sprint.migration';
    $LB = "\033[38;5;117m";
    $NC = "\033[0m";
    $RED = "\033[0;31m";

    if (!\Bitrix\Main\ModuleManager::isModuleInstalled($moduleId)) {
        $installFile = $_SERVER['DOCUMENT_ROOT'] . '/local/modules/' . $moduleId . '/install/index.php';
        if (!file_exists($installFile)) {
            $installFile = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $moduleId . '/install/index.php';
        }

        if (file_exists($installFile)) {
            include_once($installFile);
            $class = str_replace('.', '_', $moduleId);
            if (class_exists($class)) {
                $moduleObject = new $class();
                $moduleObject->DoInstall();
                echo "${LB} - Модуль sprint.migration успешно активирован.${NC}\n";
            } else {
                echo "${RED} - Класс установщика $class не найден.${NC}\n";
            }
        } else {
            echo "${RED} - Файл установщика модуля не найден на диске.${NC}\n";
        }
    } else {
        echo "${LB} - Модуль sprint.migration уже установлен.${NC}\n";
    }
}
PHPEOF

# Запуск всех ожидающих миграций проекта
echo -e "${BLUE}Выполнение ожидающих миграций..${NC}"
docker compose exec -T php sh -c '
  if [ -f "/var/www/html/local/modules/sprint.migration/tools/migrate.php" ]; then
    php /var/www/html/local/modules/sprint.migration/tools/migrate.php up --quiet >/dev/null 2>&1
    echo -e "\033[38;5;117m - Миграции успешно применены.\033[0m"
  else
    echo -e "\033[38;5;117m - Средство выполнения миграций не найдено или все миграции уже применены.\033[0m"
  fi
'

echo -e "${GREEN}Конфигурация после установки успешно завершена!${NC}"
