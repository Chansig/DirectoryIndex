# Directory Index

Directory Index is a secure directory browser distributed as one standalone PHP file. Drop
`dist/index.php` into a directory served by PHP to get an Apache AutoIndex-like listing with sorting,
subdirectory navigation, a parent link, responsive rendering, and no runtime dependency.

## Requirements

- PHP 8.3 or newer on the web server.
- Docker with Docker Compose and Make for development.

The distributed file does not require Composer, `intl`, JavaScript, a CDN, or separate assets.

## Installation

Copy the generated file into the directory to publish:

```shell
cp dist/index.php /var/www/downloads/index.php
```

The containing directory is the published root. Navigation can descend into its subdirectories and
return through `Parent directory`, but it can never move above that root. Files are served directly by
the web server; the script only renders the index.

## Configuration

The beginning of `dist/index.php` contains the editable configuration:

```php
$directoryIndexConfig = [
    'forced_locale' => null,
    'fallback_locale' => 'en',
    'show_hidden' => false,
    'follow_symlinks' => false,
    'blocked_names' => [
        '.env', '.git', '.hg', '.svn', '.ssh', '.htaccess', '.htpasswd',
        'directory-index.config.php',
    ],
    'blocked_extensions' => ['key', 'pem', 'p12', 'pfx'],
];
```

When `forced_locale` is `null`, the first supported language from `Accept-Language` is selected.
English and French are supported. If no supported language is present, `fallback_locale` is used and
defaults to English. Set `forced_locale` to `en` or `fr` to ignore browser preferences.

Symbolic links and hidden files are disabled by default. A followed symbolic link is still required to
resolve inside the published root.

## Security model

Directory Index constrains resolved paths to its installation directory, URL-encodes every path
segment, HTML-escapes every displayed value, filters sensitive names, and sends a restrictive Content
Security Policy and related browser headers.

It is not an authentication system. Protect private listings with authentication at the Apache,
Nginx, reverse-proxy, or hosting-platform layer. Do not place the script above application source,
credentials, private keys, backups, or other content that should not be downloadable.

## Development

All development commands run in Docker. PHP, Composer, Node.js, and Playwright do not need to be
installed on the host.

```shell
make init       # build images and install locked development dependencies
make infra      # ShellCheck, Hadolint, actionlint, and Compose validation
make qa         # PHP lint, style, static analysis, tests, coverage, security, distribution
make mutation   # mutation testing
make e2e        # Playwright functional and visual tests
make e2e-apache # the same Playwright suite against Apache
make smoke      # standalone bundle in a minimal dependency-free PHP image
make ci         # complete local equivalent of GitHub Actions
```

`make coverage` enforces 100% class, method, branch, and line coverage over the PHP application
source. Xdebug path coverage remains visible as a diagnostic metric, but it is not presented or gated
as total path coverage: loop and condition combinations make that metric combinatorial. `make mutation`
requires a 90% Mutation Score Indicator, keeping the gate strict without treating equivalent mutants
as product defects.

### Visual E2E development

The E2E environment copies deterministic fixtures into an isolated container, installs the standalone
`index.php`, and opens it with the official Playwright Chromium image in desktop and mobile profiles.

```shell
make e2e-update  # intentionally update committed screenshots
make e2e-ui      # Playwright UI at http://localhost:9323
make e2e-dev     # interactive fixture site at http://localhost:8080
make e2e-dev-stop
make e2e-report  # last HTML report at http://localhost:9323
```

All published development ports are configurable, which avoids collisions with other services:

```shell
E2E_WEB_PORT=18080 E2E_UI_PORT=19323 make e2e-ui
# Playwright UI: http://localhost:19323/
# Interactive fixture site: http://localhost:18080/

E2E_APACHE_PORT=18081 make e2e-apache
```

The web, Apache, and Playwright UI ports default to `8080`, `8081`, and `9323`. They only bind to
`127.0.0.1`; communication between containers continues to use fixed internal ports.

The page displayed inside Playwright UI is a recorded snapshot of a test step, so its links do not
provide free navigation. Use `http://localhost:8080/` while `make e2e-ui` is running, or start it
independently with `make e2e-dev`, to browse the fixture directories interactively.

Visual references live in `e2e/snapshots/`. Traces, error contexts, and the HTML report are generated on
failure and uploaded by CI.

## Build

The modular source lives in `src/DirectoryIndex`. CSS and SVG symbols remain reviewable in `assets/`.
The build embeds all of them into one deterministic file:

```shell
make build
make verify-dist
```

`verify-dist` fails when the committed distribution differs from a fresh build.

## Quality tools

Development dependencies are locked in `composer.lock` and include PHPUnit, PHPStan at maximum level,
PHP_CodeSniffer with PSR-12, PHPMD, PHP Parallel Lint, Infection, and Roave Security Advisories.
`composer audit`, adversarial security tests, ShellCheck, Hadolint, actionlint, Dependabot, dependency
review, immutable action/image references, and job timeouts complete the checks. The PHP 8.3–8.5 CI
matrix runs the standalone smoke test and Playwright suite on every supported PHP version; PHP 8.3 is
also exercised through Apache.

## License

Directory Index and its original embedded SVG icons are released under the MIT License.
