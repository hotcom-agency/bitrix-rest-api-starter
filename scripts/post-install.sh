#!/usr/bin/env bash
set -e

NC='\033[0m'
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
BLUE='\033[0;34m'
LIGHT_BLUE='\033[38;5;117m'

echo -e "${YELLOW}⏳ Starting post-installation configuration${NC}"

# Remove installation and temporary files
echo -e "${BLUE}Removing installation and temporary files...${NC}"
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

# Copy index.php from template
if [ -f "config/index.php.template" ]; then
  if [ ! -f "index.php" ]; then
    cp config/index.php.template index.php
    echo -e "${LIGHT_BLUE} - config/index.php.template -> index.php${NC}"
  else
    echo -e "${LIGHT_BLUE} - index.php already exists, skipping copy.${NC}"
  fi
else
  echo -e "${YELLOW} - config/index.php.template not found, skipping.${NC}"
fi

# Copy .settings.php from template if missing
echo -e "${BLUE}Checking .settings.php...${NC}"
if [ -f "bitrix/.settings.php.template" ] && [ ! -f "bitrix/.settings.php" ]; then
  cp bitrix/.settings.php.template bitrix/.settings.php
  echo -e "${LIGHT_BLUE} - Copied .settings.php from template${NC}"
elif [ ! -f "bitrix/.settings.php" ]; then
  echo -e "${YELLOW} - bitrix/.settings.php not found, skipping.${NC}"
else
  echo -e "${LIGHT_BLUE} - The settings are ready.${NC}"
fi

# Fix directory permissions
echo -e "${BLUE}Fixing directory permissions...${NC}"
if docker compose exec -T php sh -c "chown -R 33:33 /var/www/html/bitrix/cache /var/www/html/bitrix/managed_cache /var/www/html/bitrix/stack_cache /var/www/html/bitrix/compiled /var/www/html/upload /var/www/html/local/logs 2>/dev/null"; then
  echo -e "${LIGHT_BLUE} - Directory permissions are configured.${NC}"
else
  echo -e "${RED} - Could not set some permissions.${NC}"
fi

# Verify Redis connectivity
echo -e "${BLUE}Verifying Redis connectivity...${NC}"
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
    echo -e "${LIGHT_BLUE} - Redis is ready and operational.${NC}"
    ;;
  "EXT_MISSING")
    echo -e "${RED} - PHP redis extension is not loaded.${NC}"
    echo -e "${RED} - Add to Dockerfile: RUN pecl install redis && docker-php-ext-enable redis${NC}"
    exit 1
    ;;
  "CONN_FAILED")
    echo -e "${RED} - Cannot connect to Redis server.${NC}"
    echo -e "${RED} - Check if redis container is running and verify REDIS_HOST/PORT in .env.${NC}"
    exit 1
    ;;
  "WRITE_FAILED" | "READ_FAILED")
    echo -e "${RED} - Redis read/write operation failed.${NC}"
    echo -e "${RED} - Check redis logs: docker compose logs redis${NC}"
    exit 1
    ;;
  *)
    echo -e "${RED} - Unknown Redis check error. Output: '$REDIS_CHECK'${NC}"
    exit 1
    ;;
esac

# Optimize Composer autoloader and verify output
echo -e "${BLUE}Optimizing Composer autoloader...${NC}"
if docker compose exec -T php composer dump-autoload --optimize --quiet 2>/dev/null; then
  if docker compose exec -T php test -f vendor/autoload.php 2>/dev/null; then
    echo -e "${LIGHT_BLUE} - Composer autoloader optimized and verified.${NC}"
  else
    echo -e "${YELLOW} - Autoloader command succeeded, but vendor/autoload.php is missing.${NC}"
  fi
else
  echo -e "${RED} - Composer optimization skipped or failed.${NC}"
fi

# Graceful reload PHP-FPM
docker compose exec -T php kill -USR2 1 >/dev/null 2>&1 || true
for i in $(seq 1 10); do
  docker compose exec -T php php -r "exit(0);" 2>/dev/null && break
  sleep 1
done

# Uninstall and remove demo solution bitrix.sitecorporate
echo -e "${BLUE}Uninstalling and removing demo solution bitrix.sitecorporate...${NC}"
docker compose exec -T php php -d display_errors=0 << 'PHPEOF'
<?php
$NC = "\033[0m";
$LB = "\033[38;5;117m";

$_SERVER['DOCUMENT_ROOT'] = '/var/www/html';
define('NOT_CHECK_PERMISSIONS', true);
define('NO_KEEP_STATISTIC', true);
define('BX_NO_ACCELERATOR_RESET', true);

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

use Bitrix\Main\ModuleManager;
use Bitrix\Main\Application;

$moduleId = 'bitrix.sitecorporate';

if (ModuleManager::isModuleInstalled($moduleId)) {
    $connection = Application::getConnection();
    $demoTables = ['b_site_corporate'];
    
    foreach ($demoTables as $table) {
        if ($connection->isTableExists($table)) {
            $connection->dropTable($table);
        }
    }

    ModuleManager::unRegisterModule($moduleId);
    
    $modulePath = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $moduleId;
    if (is_dir($modulePath)) {
        exec("rm -rf " . escapeshellarg($modulePath));
    }

    echo "{$LB} - Module bitrix.sitecorporate un-registered and removed cleanly via CLI.{$NC}\n";
} else {
    echo "{$LB} - Module bitrix.sitecorporate is not installed. Nothing to delete.{$NC}\n";
}
PHPEOF

# Automatically install and activate sprint.migration module
echo -e "${BLUE}Installing and activating sprint.migration module...${NC}"
docker compose exec -T php php -d display_errors=0 << 'PHPEOF'
<?php
$NC = "\033[0m";
$LB = "\033[38;5;117m";
$GREEN = "\033[0;32m";
$RED = "\033[0;31m";

$_SERVER['DOCUMENT_ROOT'] = '/var/www/html';
define('NOT_CHECK_PERMISSIONS', true);
define('NO_KEEP_STATISTIC', true);
define('BX_NO_ACCELERATOR_RESET', true);

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;

$moduleId = 'sprint.migration';

if (!ModuleManager::isModuleInstalled($moduleId)) {
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
            echo "{$LB} - Module sprint.migration activated successfully.{$NC}\n";
        } else {
            echo "{$RED} - Installer class $class not found.{$NC}\n";
        }
    } else {
        echo "{$RED} - Module installer file not found on disk.{$NC}\n";
    }
} else {
    echo "{$LB} - Module sprint.migration is already installed.{$NC}\n";
}
PHPEOF

# Run all pending project migrations
echo -e "${BLUE}Running pending migrations...${NC}"
docker compose exec -T php sh -c "
  cd /var/www/html && \
  ( php local/modules/sprint.migration/tools/migrate.php up --quiet >/dev/null 2>&1 )
"
if [ $? -eq 0 ]; then
  echo -e "${LIGHT_BLUE} - Migrations applied successfully.${NC}"
else
  echo -e "${YELLOW} - Migration runner not found or already up-to-date.${NC}"
fi

echo -e "${GREEN}Post-installation configuration completed successfully!${NC}"
