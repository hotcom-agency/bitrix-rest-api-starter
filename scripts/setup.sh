#!/usr/bin/env bash
set -e

# Цветовые коды ANSI
NC='\033[0m'
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
BLUE='\033[0;34m'

echo -e "${BLUE}Preparing directories and permissions...${NC}"

if [ ! -f .env ];  then
    echo -e "${RED}.env not exists${NC}"
    exit 1;
fi

mkdir -p upload local/logs docker/database/data backups local/migrations config bitrix/php_interface

if [ "$(uname)" = "Linux" ]; then
    chown -R 33:33 upload local/logs docker/database/data >/dev/null 2>&1 || true
fi

chmod -R 775 upload local/logs >/dev/null 2>&1 || true
chmod -R 755 docker/database/data local/migrations config backups >/dev/null 2>&1 || true

echo -e "${BLUE}Configuring environment and settings files...${NC}"

if [ -f config/.settings.php.template ] && [ ! -f bitrix/.settings.php ]; then
    cp config/.settings.php.template bitrix/.settings.php
fi

if [ -f config/dbconn.php.template ] && [ ! -f bitrix/php_interface/dbconn.php ]; then
    cp config/dbconn.php.template bitrix/php_interface/dbconn.php
fi
