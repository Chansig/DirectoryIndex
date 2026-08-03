SHELL := /bin/sh
.DEFAULT_GOAL := help

export LOCAL_UID := $(shell id -u)
export LOCAL_GID := $(shell id -g)
export PHP_IMAGE ?= mirror.gcr.io/library/php:8.3-cli-bookworm@sha256:107f022053b2222ffd64f957c2a3b4c724c506301c84638e537f9027ed6468f5
export APACHE_IMAGE ?= mirror.gcr.io/library/php:8.3-apache-bookworm@sha256:73ec03963e2c1d20500a17326479f4d761843109c952aca44f89064924092d97
export E2E_WEB_PORT ?= 8080
export E2E_APACHE_PORT ?= 8081
export E2E_UI_PORT ?= 9323

COMPOSE := docker compose
QA := $(COMPOSE) run --rm qa

.PHONY: help init install images dependencies php-install e2e-install lint cs cs-fix static mess test coverage mutation security infra build verify-dist smoke qa qa-fix ci e2e e2e-apache e2e-update e2e-ui e2e-dev e2e-dev-stop e2e-report clean

help: ## Display available targets
	@awk 'BEGIN {FS = ":.*## "} /^[a-zA-Z0-9_-]+:.*## / {printf "%-18s %s\n", $$1, $$2}' $(MAKEFILE_LIST)

init: images dependencies ## Build containers and install all development dependencies

images: ## Build the local PHP development image
	$(COMPOSE) build qa web

dependencies: php-install e2e-install ## Install all locked development dependencies

php-install: ## Install locked Composer dependencies in Docker
	$(QA) composer install --no-interaction --prefer-dist

e2e-install: ## Install locked Playwright dependencies in Docker
	$(COMPOSE) run --rm --no-deps e2e npm ci --prefer-offline

install: init ## Alias for init

lint: ## Lint every PHP file in Docker
	XDEBUG_MODE=off $(QA) vendor/bin/parallel-lint --exclude vendor --exclude build .

cs: ## Check the coding standard
	XDEBUG_MODE=off $(QA) vendor/bin/phpcs

cs-fix: ## Fix coding-standard violations
	XDEBUG_MODE=off $(QA) vendor/bin/phpcbf

static: ## Run PHPStan at maximum level
	XDEBUG_MODE=off $(QA) vendor/bin/phpstan analyse --memory-limit=1G

mess: ## Run PHPMD
	XDEBUG_MODE=off $(QA) vendor/bin/phpmd src,tools text phpmd.xml

test: ## Run the PHPUnit suite
	XDEBUG_MODE=off $(QA) vendor/bin/phpunit --no-coverage

coverage: ## Enforce 100% line, method, and branch coverage
	$(QA) vendor/bin/phpunit --coverage-clover build/coverage/clover.xml --coverage-cobertura build/coverage/cobertura.xml
	XDEBUG_MODE=off $(QA) php tools/check-coverage.php build/coverage/clover.xml build/coverage/cobertura.xml

mutation: ## Run mutation testing with strict thresholds
	$(QA) vendor/bin/infection --threads=max --min-msi=90 --min-covered-msi=90

security: ## Audit dependencies and run security-focused tests
	XDEBUG_MODE=off $(QA) composer validate --strict --check-lock
	XDEBUG_MODE=off $(QA) composer audit --locked --abandoned=fail
	XDEBUG_MODE=off $(QA) vendor/bin/phpunit --testsuite security --no-coverage

infra: ## Validate shell, Docker, Compose, and GitHub Actions files
	$(COMPOSE) config --quiet
	XDEBUG_MODE=off $(QA) shellcheck tools/run-e2e.sh
	$(COMPOSE) --profile tools run --rm --no-deps hadolint .docker/php/Dockerfile .docker/apache/Dockerfile .docker/smoke/Dockerfile
	XDEBUG_MODE=off $(QA) actionlint

build: ## Generate the standalone dist/index.php
	XDEBUG_MODE=off $(QA) php tools/build.php

verify-dist: build ## Verify that the committed distribution is reproducible
	git diff --exit-code -- dist/index.php

smoke: build ## Verify the bundle alone in a minimal pinned PHP image
	docker build --build-arg PHP_IMAGE="$(PHP_IMAGE)" --file .docker/smoke/Dockerfile --tag directoryindex-smoke:local .
	docker run --rm --network none directoryindex-smoke:local

qa: lint cs static mess test coverage security verify-dist ## Run all PHP quality gates

qa-fix: cs-fix build ## Apply safe automatic fixes and rebuild the distribution

e2e: build ## Run Playwright functional and visual tests
	./tools/run-e2e.sh test

e2e-apache: build ## Run the same Playwright suite against Apache
	./tools/run-e2e.sh apache

e2e-update: build ## Update Playwright visual snapshots
	./tools/run-e2e.sh update

e2e-ui: build ## Open Playwright UI on its configured host port
	./tools/run-e2e.sh ui

e2e-dev: build ## Start the interactive fixture site on its configured host port
	$(COMPOSE) up -d --wait web
	@echo "Interactive fixture site: http://localhost:$(E2E_WEB_PORT)/"

e2e-dev-stop: ## Stop the interactive fixture site
	$(COMPOSE) stop web

e2e-report: ## Open the last Playwright report on its configured host port
	./tools/run-e2e.sh report

ci: infra qa mutation smoke e2e e2e-apache ## Run the complete local equivalent of CI

clean: ## Remove generated reports and stop containers
	$(COMPOSE) down --remove-orphans
	$(COMPOSE) run --rm --no-deps qa sh -c 'rm -rf build .phpunit.cache'
	$(COMPOSE) run --rm --no-deps e2e sh -c 'rm -rf playwright-report test-results'
