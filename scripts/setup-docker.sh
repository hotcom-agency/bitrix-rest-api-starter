#!/usr/bin/env bash
set -e

DOCKER_DIR="${1:-$(dirname "$(dirname "$0")")}"

NC='\033[0m'
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
BLUE='\033[0;34m'
LIGHT_BLUE='\033[38;5;117m'

echo -e "⏳ ${YELLOW}Начинаем настройку docker для bitrix${NC}"

bash "$(dirname "$0")/setup.sh"

if [ -z "$1" ]; then
    if [ -f config/nginx/default.conf.example ] && [ ! -f docker/nginx/default.conf ]; then
        cp config/nginx/default.conf.example docker/nginx/default.conf
    fi
    if [ -f config/nginx/default.dev.conf.example ] && [ ! -f docker/nginx/default.dev.conf ]; then
        cp config/nginx/default.dev.conf.example docker/nginx/default.dev.conf
    fi
fi

echo -e "${BLUE}Сборка и запуск контейнеров..${NC}"
docker compose -f "$DOCKER_DIR/docker-compose.yml" --project-directory "$DOCKER_DIR" up -d --build --force-recreate --quiet-pull >/dev/null 2>&1
echo -e "${LIGHT_BLUE} - Контейнеры созданы${NC}"

until docker compose -f "$DOCKER_DIR/docker-compose.yml" --project-directory "$DOCKER_DIR" exec -T php php -r "exit(0);" 2>/dev/null; do sleep 1; done

echo -e "${BLUE}Очистка старого кэша Redis..${NC}"
until docker compose -f "$DOCKER_DIR/docker-compose.yml" --project-directory "$DOCKER_DIR" exec -T redis redis-cli ping 2>/dev/null | grep -q "PONG"; do sleep 1; done
docker compose -f "$DOCKER_DIR/docker-compose.yml" --project-directory "$DOCKER_DIR" exec -T redis redis-cli flushall >/dev/null 2>&1
echo -e "${LIGHT_BLUE} - База данных Redis успешно очищена${NC}"

echo -e "${BLUE}Синхронизация шаблонов конфигурации в именованный том..${NC}"
docker compose -f "$DOCKER_DIR/docker-compose.yml" --project-directory "$DOCKER_DIR" exec -T php mkdir -p /var/www/html/bitrix/php_interface

if [ -f config/.settings.php.template ]; then
    docker compose -f "$DOCKER_DIR/docker-compose.yml" --project-directory "$DOCKER_DIR" exec -T php sh -c "cp -n /var/www/html/config/.settings.php.template /var/www/html/bitrix/.settings.php || true"
fi
if [ -f config/dbconn.php.template ]; then
    docker compose -f "$DOCKER_DIR/docker-compose.yml" --project-directory "$DOCKER_DIR" exec -T php sh -c "cp -n /var/www/html/config/dbconn.php.template /var/www/html/bitrix/php_interface/dbconn.php || true"
fi
echo -e "${LIGHT_BLUE} - Синхронизация завершена.${NC}"

echo -e "${BLUE}Установка зависимостей composer..${NC}"
docker compose -f "$DOCKER_DIR/docker-compose.yml" --project-directory "$DOCKER_DIR" exec -T php composer install --no-interaction --optimize-autoloader --quiet >/dev/null 2>&1
echo -e "${LIGHT_BLUE} - Зависимости composer установлены${NC}"

bash "$(dirname "$0")/init.sh"

echo -e "${GREEN}Проект успешно установлен! После мастера установки: выполните 'make setup-finish' для удаления файлов из корня.${NC}"
