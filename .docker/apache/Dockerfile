ARG APACHE_IMAGE=mirror.gcr.io/library/php:8.3-apache-bookworm@sha256:73ec03963e2c1d20500a17326479f4d761843109c952aca44f89064924092d97
FROM ${APACHE_IMAGE}

COPY dist/index.php /var/www/html/index.php
COPY tests/Fixtures/e2e/ /var/www/html/

RUN find /var/www/html -exec touch -t 202601020304.05 {} + \
    && ln -s /etc /var/www/html/outside-link

HEALTHCHECK --interval=1s --timeout=2s --retries=20 \
    CMD php -r "exit(@file_get_contents('http://127.0.0.1/') === false ? 1 : 0);"
