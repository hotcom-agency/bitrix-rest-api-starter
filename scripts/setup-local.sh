#!/usr/bin/env bash
set -e

NC='\033[0m'
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
BLUE='\033[0;34m'
LIGHT_BLUE='\033[38;5;117m'

echo -e "⏳ ${YELLOW}Starting local setup${NC}"

bash "$(dirname "$0")/setup.sh"

echo -e "${BLUE}Installing composer dependencies..${NC}"
composer install --no-interaction --optimize-autoloader >/dev/null 2>&1

echo -e "${BLUE}Checking PHP extensions...${NC}"
if ! php -r "
\$exts = ['mysqli','pdo_mysql','json','mbstring','intl','gd','zip'];
foreach(\$exts as \$e) {
    if(!extension_loaded(\$e)) { 
        fwrite(STDERR, 'Missing: '.\$e.'\n'); 
        exit(1); 
    }
}
" 2>/dev/null; then
    echo -e "${RED}Error: Some required PHP extensions are missing!${NC}"
    exit 1
fi

bash "$(dirname "$0")/init.sh"

echo -e "${GREEN}Project successfully installed! After wizard: run 'make setup-finish' to remove files from root.${NC}"
