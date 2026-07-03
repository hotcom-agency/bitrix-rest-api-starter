#!/usr/bin/env bash
set -e

NC='\033[0m'
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
BLUE='\033[0;34m'
LIGHT_BLUE='\033[38;5;117m'

echo -e "⏳ ${YELLOW}Начинаем локальную настройку${NC}"

bash "$(dirname "$0")/setup.sh"

echo -e "${BLUE}Установка зависимостей composer..${NC}"
composer install --no-interaction --optimize-autoloader >/dev/null 2>&1

echo -e "${BLUE}Проверка расширений PHP..${NC}"
if ! php -r "
\$exts = ['mysqli','pdo_mysql','pdo_pgsql','json','mbstring','intl','gd','zip','xsl','opcache','bcmath','pcntl','sockets','gmp','redis','imagick'];
foreach(\$exts as \$e) {
    if(!extension_loaded(\$e)) { 
        fwrite(STDERR, 'Отсутствует: '.\$e.'\n'); 
        exit(1); 
    }
}
" 2>/dev/null; then
    echo -e "${RED}Ошибка: Некоторые необходимые расширения PHP отсутствуют!${NC}"
    exit 1
fi

bash "$(dirname "$0")/init.sh"

echo -e "${GREEN}Проект успешно установлен! После мастера установки: выполните 'make setup-finish' для удаления файлов из корня.${NC}"
