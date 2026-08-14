#!/usr/bin/env bash
set -e

NC='\033[0m'
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
BLUE='\033[0;34m'
LIGHT_BLUE='\033[38;5;117m'

echo -e "${BLUE}Подготовка директорий и разрешений..${NC}"

if [ ! -f .env ] && [ ! -f ../.env ]; then
    echo -e "${RED}.env не существует${NC}"
    exit 1;
fi

mkdir -p src/upload src/local/logs docker/database/data backups src/local/migrations src/config src/local/php_interface

if [ "$(uname)" != "Darwin" ]; then
    chmod -R u+rwX,go+rX,go-w src/upload src/local/logs docker/database/data backups 2>/dev/null || true
fi

echo -e "${LIGHT_BLUE} - Разрешения для директорий установлены.${NC}"

echo -e "${BLUE}Настройка дополнительных файлов конфигурации на Mac..${NC}"

if [ -f src/config/.settings_extra.php.example ] && [ ! -f src/local/php_interface/.settings_extra.php ]; then
    cp src/config/.settings_extra.php.example src/local/php_interface/.settings_extra.php
fi

echo -e "${LIGHT_BLUE} - Локальные файлы настроены.${NC}"
