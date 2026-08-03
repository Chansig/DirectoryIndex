#!/bin/sh

set -eu

mode="${1:-test}"
web_port="${E2E_WEB_PORT:-8080}"
ui_port="${E2E_UI_PORT:-9323}"

if [ "$mode" = "report" ]; then
    exec docker compose run --rm --no-deps --service-ports e2e npm run report
fi

service="web"
base_url="http://web:8080"
if [ "$mode" = "apache" ]; then
    service="apache"
    base_url="http://apache"
fi

docker compose up -d --wait "$service"
trap 'docker compose stop "$service" >/dev/null' EXIT INT TERM

case "$mode" in
    test)
        docker compose run --rm --no-deps -e PLAYWRIGHT_BASE_URL="$base_url" e2e npm test
        ;;
    apache)
        docker compose run --rm --no-deps -e PLAYWRIGHT_BASE_URL="$base_url" e2e npm test
        ;;
    update)
        docker compose run --rm --no-deps -e PLAYWRIGHT_BASE_URL="$base_url" e2e npm run test:update
        ;;
    ui)
        echo "Playwright UI: http://localhost:$ui_port/"
        echo "Interactive fixture site: http://localhost:$web_port/"
        docker compose run --rm --no-deps --service-ports -e PLAYWRIGHT_BASE_URL="$base_url" e2e npm run test:ui
        ;;
    *)
        echo "Unknown E2E mode: $mode" >&2
        exit 2
        ;;
esac
