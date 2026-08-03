ARG PHP_IMAGE=mirror.gcr.io/library/php:8.3-cli-bookworm@sha256:107f022053b2222ffd64f957c2a3b4c724c506301c84638e537f9027ed6468f5
FROM ${PHP_IMAGE}

WORKDIR /srv
COPY dist/index.php /srv/index.php

RUN php -l /srv/index.php \
    && php /srv/index.php > /tmp/index.html \
    && grep -Fq '<!doctype html>' /tmp/index.html \
    && grep -Fq 'Index of /' /tmp/index.html

CMD ["php", "-l", "/srv/index.php"]
