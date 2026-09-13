ARG BUILD_FROM=ghcr.io/hassio-addons/bookstack/amd64:4.0.2
# hadolint ignore=DL3006
FROM ${BUILD_FROM}

# QMS-Audit-Theme (Freigabe-Workflow, 4-Augen-Prinzip) einspielen
COPY rootfs /

# Theme aktivieren, deutsche Standardsprache und Zeitzone setzen.
# Diese Werte können über die Add-on-Option "envvars" übersteuert werden.
ENV APP_THEME="qms-audit" \
    APP_LANG="de" \
    APP_TIMEZONE="Europe/Berlin"

# Lesbarkeit der Theme-Dateien für den Webserver-Benutzer sicherstellen
RUN chmod -R a+rX /var/www/bookstack/themes/qms-audit
