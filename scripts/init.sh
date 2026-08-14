#!/usr/bin/env bash
set -e

NC='\033[0m'
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
BLUE='\033[0;34m'
LIGHT_BLUE='\033[38;5;117m'

echo -e "${BLUE}Настройка Bitrix:${NC}"
echo -e "${LIGHT_BLUE} 1 Чистая установка"
echo -e " 2 Восстановление из резервной копии"
echo -e " 3 Пропустить${NC}"

read -p "$(echo -e "${BLUE}Выберите [${NC}${LIGHT_BLUE}1-3${NC}${BLUE}]: ${NC}")" opt

# Функция подготовки прав внутри именованного тома
prepare_volume_permissions() {
    echo -e "${BLUE}Установка безопасных разрешений внутри именованного тома...${NC}"
    docker compose exec -T php chown -R www-data:www-data /var/www/html/bitrix 2>/dev/null || true
    docker compose exec -T php rm -rf /var/www/html/bitrix/cache/* /var/www/html/bitrix/managed_cache/* /var/www/html/bitrix/stack_cache/* 2>/dev/null || true
}

if [ "$opt" = "1" ]; then
  if [ ! -f src/bitrixsetup.php ]; then
    if [ -f src/config/bitrixsetup.php ]; then
      cp src/config/bitrixsetup.php src/bitrixsetup.php
      echo -e "${BLUE}Скопировано из src/config/${NC}"
    else
      echo -e "${RED}Ошибка: src/config/bitrixsetup.php не найден${NC}"
      exit 1
    fi
  fi
  prepare_volume_permissions
  echo -e "${YELLOW}Откройте: {{host}}/bitrixsetup.php${NC}"

elif [ "$opt" = "2" ]; then
  if [ ! -f src/restore.php ]; then
    if [ -f src/config/restore.php ]; then
      cp src/config/restore.php src/restore.php
      echo -e "${BLUE}Скопировано из src/config/${NC}"
    else
      echo -e "${RED}Ошибка: src/config/restore.php не найден${NC}"
      exit 1
    fi
  fi
  prepare_volume_permissions
  echo -e "${YELLOW}Откройте: {{host}}/restore.php${NC}"

elif [ "$opt" = "3" ]; then
  prepare_volume_permissions
  exit 0
else
  echo -e "${YELLOW}Выбран неверный параметр${NC}"
  exit 1
fi
