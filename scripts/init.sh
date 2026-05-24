#!/usr/bin/env bash
set -e

NC='\033[0m'
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
BLUE='\033[0;34m'
LIGHT_BLUE='\033[38;5;117m'

echo -e "${BLUE}Bitrix Setup:${NC}"
echo -e "${LIGHT_BLUE} 1 Fresh install"
echo -e " 2 Restore backup"
echo -e " 3 Skip${NC}"

read -p "$(echo -e "${BLUE}Select [${NC}${LIGHT_BLUE}1-3${NC}${BLUE}]: ${NC}")" opt

if [ "$opt" = "1" ]; then
  if [ ! -f bitrixsetup.php ]; then
    if [ -f config/bitrixsetup.php ]; then
      cp config/bitrixsetup.php ./bitrixsetup.php
      echo -e "${BLUE}Copied from config/${NC}"
    else
      echo -e "${RED}Error: config/bitrixsetup.php not found${NC}"
      exit 1
    fi
  fi
  echo -e "${BLUE}Open: {{host}}/bitrixsetup.php${NC}"

elif [ "$opt" = "2" ]; then
  if [ ! -f restore.php ]; then
    if [ -f config/restore.php ]; then
      cp config/restore.php ./restore.php
      echo -e "${BLUE}Copied from config/${NC}"
    else
      echo -e "${RED}Error: config/restore.php not found${NC}"
      exit 1
    fi
  fi
  echo -e "${BLUE}Open: {{host}}/restore.php${NC}"

elif [ "$opt" = "3" ]; then
  exit 0
else
  echo -e "${YELLOW}Invalid option selected${NC}"
  exit 0
fi
