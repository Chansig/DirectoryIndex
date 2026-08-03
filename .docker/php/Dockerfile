ARG PHP_IMAGE=mirror.gcr.io/library/php:8.3-cli-bookworm@sha256:107f022053b2222ffd64f957c2a3b4c724c506301c84638e537f9027ed6468f5
FROM ${PHP_IMAGE}

ARG TARGETARCH
ARG ACTIONLINT_VERSION=1.7.7

RUN apt-get -o Acquire::Retries=5 -o Acquire::http::No-Cache=true update \
    && apt-get -o Acquire::Retries=5 -o Acquire::http::No-Cache=true install -y --no-install-recommends \
        ca-certificates curl shellcheck unzip \
    && php -r "foreach (['dom', 'mbstring', 'simplexml', 'xml', 'xmlwriter'] as \$extension) { \
        if (!extension_loaded(\$extension)) { \
            fwrite(STDERR, 'Missing PHP extension: ' . \$extension . PHP_EOL); exit(1); \
        } \
    }" \
    && pecl install xdebug-3.5.3 \
    && docker-php-ext-enable xdebug \
    && case "${TARGETARCH}" in \
        amd64) actionlint_checksum="023070a287cd8cccd71515fedc843f1985bf96c436b7effaecce67290e7e0757" ;; \
        arm64) actionlint_checksum="401942f9c24ed71e4fe71b76c7d638f66d8633575c4016efd2977ce7c28317d0" ;; \
        *) echo "Unsupported architecture: ${TARGETARCH}" >&2; exit 1 ;; \
    esac \
    && actionlint_archive="actionlint_${ACTIONLINT_VERSION}_linux_${TARGETARCH}.tar.gz" \
    && curl --fail --location --retry 5 \
        --output "/tmp/${actionlint_archive}" \
        "https://github.com/rhysd/actionlint/releases/download/v${ACTIONLINT_VERSION}/${actionlint_archive}" \
    && printf '%s  %s\n' "${actionlint_checksum}" "/tmp/${actionlint_archive}" > /tmp/actionlint.sha256 \
    && sha256sum --check /tmp/actionlint.sha256 \
    && tar -xzf "/tmp/${actionlint_archive}" -C /usr/local/bin actionlint \
    && rm -rf /var/lib/apt/lists/*

COPY --from=mirror.gcr.io/library/composer:2@sha256:4d71c3c2109c61d5415544264b59ad4087e4c5b7244481723664138fd36d5040 /usr/bin/composer /usr/local/bin/composer

ENV COMPOSER_HOME=/tmp/composer
ENV XDEBUG_MODE=coverage

WORKDIR /app
