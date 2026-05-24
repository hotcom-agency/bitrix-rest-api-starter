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

for dir in cache managed_cache stack_cache compiled; do
    mkdir -p "/var/www/html/bitrix/$dir"
done
mkdir -p /var/www/html/upload /var/www/html/local/logs
chown -R 33:33 /var/www/html/bitrix /var/www/html/upload /var/www/html/local
umask 0022

exec "$@"
