#!/usr/bin/env bash
set -e

NC='\033[0m'
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
BLUE='\033[0;34m'
LIGHT_BLUE='\033[38;5;117m'

echo -e "⏳ ${YELLOW}Starting docker setup${NC}"

bash "$(dirname "$0")/setup.sh"

if [ -f config/nginx/default.conf.example ] && [ ! -f docker/nginx/default.conf ]; then
    cp config/nginx/default.conf.example docker/nginx/default.conf
fi

if [ -f config/nginx/default.dev.conf.example ] && [ ! -f docker/nginx/default.dev.conf ]; then
    cp config/nginx/default.dev.conf.example docker/nginx/default.dev.conf
fi

echo -e "${BLUE}Building & starting containers..${NC}"
docker compose up -d --build --force-recreate --quiet-pull >/dev/null 2>&1

until docker compose exec -T php php -r "exit(0);" 2>/dev/null; do sleep 1; done

echo -e "${BLUE}Installing composer dependencies..${NC}"
docker compose exec -T php composer install --no-interaction --optimize-autoloader --quiet >/dev/null 2>&1

bash "$(dirname "$0")/init.sh"

echo -e "${GREEN}Project successfully installed! After wizard: run 'make setup-finish' to remove files from root.${NC}"
