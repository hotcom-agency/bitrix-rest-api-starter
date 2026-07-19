#!/bin/sh
set -e

if [ -n "${SMTP_HOST}" ] && [ -n "${SMTP_EMAIL}" ] && [ -f /etc/msmtprc.template ]; then
    sed -e "s|\#SMTP_HOST\#|${SMTP_HOST}|g" \
        -e "s|\#SMTP_PORT\#|${SMTP_PORT}|g" \
        -e "s|\#SMTP_EMAIL\#|${SMTP_EMAIL}|g" \
        -e "s|\#SMTP_USER\#|${SMTP_USER}|g" \
        -e "s|\#SMTP_PASSWORD\#|${SMTP_PASSWORD}|g" \
        -e "s|\#SMTP_AUTH\#|${SMTP_AUTH:-off}|g" \
        -e "s|\#SMTP_TLS\#|${SMTP_TLS:-off}|g" \
        -e "s|\#SMTP_TLS_STARTTLS\#|${SMTP_TLS_STARTTLS:-off}|g" \
        -e "s|\#SMTP_TLS_CERTCHECK\#|${SMTP_TLS_CERTCHECK:-off}|g" \
        /etc/msmtprc.template > /etc/msmtprc
    chmod 644 /etc/msmtprc
fi

CONTAINER_USER_ID=$(id -u www-data)

for dir in cache managed_cache stack_cache compiled updates modules php_interface; do
    mkdir -p "/var/www/html/bitrix/$dir"
done
mkdir -p /var/www/html/upload /var/www/html/local/logs

chown ${CONTAINER_USER_ID}:${CONTAINER_USER_ID} /var/www/html /var/www/html/bitrix /var/www/html/upload

find /var/www/html -maxdepth 1 -type f -exec chmod 664 {} \; 2>/dev/null || true
find /var/www/html -maxdepth 1 -type f -exec chown ${CONTAINER_USER_ID}:${CONTAINER_GROUP_ID} {} \; 2>/dev/null || true

if [ ! -d /var/www/html/bitrix/modules ]; then
    chown -R ${CONTAINER_USER_ID}:${CONTAINER_USER_ID} /var/www/html/bitrix 2>/dev/null || true
fi

umask 0022

exec "$@"
