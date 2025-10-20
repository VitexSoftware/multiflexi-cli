# WARP.md

This file provides guidance to WARP (warp.dev) when working with code in this repository.

- Repository: multiflexi-cli (PHP, Symfony Console-based CLI)
- Primary entrypoint: src/multiflexi-cli.php
- Package manager: Composer
- CI: GitHub Actions (.github/workflows/php.yml)
- Formatting: PHP-CS-Fixer (.php-cs-fixer.dist.php)
- Static analysis: PHPStan (invoked via Makefile target)
- Tests: PHPUnit (tests/, tests/bootstrap.php)
- Agent rules: .github/copilot-instructions.md (see below: Important Copilot rules)

Common commands

- Install dependencies
  - composer install
  - Or via Makefile: make vendor

- Run the CLI locally
  - php -d detect_unicode=0 src/multiflexi-cli.php <command> [options]
  - Example: php src/multiflexi-cli.php describe --format json

- Lint / format
  - PHP-CS-Fixer: vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php --diff --verbose
  - Or: make cs

- Static analysis
  - vendor/bin/phpstan analyse --configuration=phpstan-default.neon.dist --memory-limit=-1
  - Or: make static-code-analysis

- Run tests
  - All tests: vendor/bin/phpunit tests
  - Or: make tests
  - Single test file: vendor/bin/phpunit tests/src/Command/JobCommandTest.php
  - With bootstrap (auto via Composer autoload + tests/bootstrap.php if configured): vendor/bin/phpunit -c tests/configuration.xml tests/

- CI validation (locally simulate parts of CI)
  - composer validate --strict
  - composer install --prefer-dist --no-progress

Project structure and architecture

- CLI application
  - Entrypoint: src/multiflexi-cli.php
    - Boots autoload, initializes Ease\Shared with environment (.env path selectable via -e/--environment), sets up logging (EASE_LOGGER), and user context.
    - Registers Symfony Console commands and runs the Application.
  - Installed binary wrapper: bin/multiflexi-cli (Debian packaging) delegates to /usr/share/multiflexi-cli/multiflexi-cli.php.

- Commands (Symfony Console)
  - Located under src/Command/*.php. Each command handles CRUD-like actions against MultiFlexi domain models (provided by vitexsoftware/multiflexi-core) and supports --format json for machine-readable output.
  - Base class: MultiFlexiCommand
    - Provides utilities: outputTable(array), outputResult(), jsonError(), jsonSuccess(), parseBoolOption().
    - Shared table output uses LucidFrame\Console\ConsoleTable.
  - Notable commands:
    - AppStatusCommand: High-level system status (DB, migrations, services, counts).
    - ApplicationCommand: Manage "apps" including JSON import/export/validate and config schema inspection.
    - ArtifactCommand: Manage job artifacts; list, get, and save artifact content to files with job filtering.
    - RunTemplateCommand: Manage and schedule run templates; supports config/env overrides and cron validation.
    - JobCommand: Manage jobs; status aggregation, CRUD, and field filtering via --fields.
    - CompanyCommand: Manage companies with lookup by id/ic/name/slug and slug derivation.
    - TokenCommand: Manage/generate tokens.
    - QueueCommand: Inspect and truncate scheduler queue (handles SQLite vs others).
    - CredentialCommand: Manage credentials; CRUD operations for credential instances based on credential types.
    - CredentialTypeCommand: List/get/update credential types.
    - CompanyAppCommand: List runtemplates for a given company+app context (partial implementation for non-list actions).
    - DescribeCommand: Introspect all commands and emit JSON/YAML schemas of arguments/options (used to keep external test scripts in sync).
    - UserDataErasureCommand: GDPR user data erasure management; create, approve, reject, and process deletion requests with audit trail.

- Domain and persistence
  - Domain classes (Application, RunTemplate, Job, Company, Token, etc.) are provided by the vitexsoftware/multiflexi-core dependency.
  - Database access is via Ease and FluentPDO (through multiflexi-core). DB connection and other parameters come from Ease\Shared::init reading .env.

- Internationalization and logging
  - I18n via _() calls; locale initialization occurs in tests/bootstrap.php and in the runtime setup.
  - Logging pipeline is composed via EASE_LOGGER (console/syslog/SQL/Zabbix depending on config and environment variables).

- Tests
  - PHPUnit tests are located in tests/ and tests/src/Command. Some tests are skeletons; JobCommandTest demonstrates command-level testing via Symfony CommandTester.
  - tests/bootstrap.php prepares autoload, sessions, locale, loggers, and user context.

Important Copilot rules in this repo

- All messages and comments in English; PHP 8.4+; follow PSR-12.
- For every command, ensure that --format json returns JSON for errors, status, and results.
- Keep tests/test-cli.sh consistent with the multiflexi-cli describe output, and keep delete-action tests at the bottom of that file.
- Update reST docs and manpage files when CLI features change (doc/multiflexi-cli.rst, debian/multiflexi-cli.1).
- IMPORTANT: Always update debian/multiflexi-cli.1 when:
  - Adding new commands (add to COMMANDS section with actions and options)
  - Adding new command options or actions
  - Adding new examples (add to EXAMPLES section)
  - Changing command behavior that affects user interaction

Environment and configuration

- The CLI reads DB and related settings from .env (default ../.env from src/multiflexi-cli.php unless overridden).
  - Override with: -e /path/to/.env or --environment=/path/to/.env
- For tests, tests/bootstrap.php uses .env at repo root (../.env) and sets up EASE_LOGGER with syslog and SQL (and Zabbix if configured).

Notes for future changes

- When adding or modifying commands under src/Command:
  - Inherit from MultiFlexiCommand when feasible to get consistent output helpers.
  - Support --format json comprehensively, including error paths, and consider adding --fields filtering where appropriate.
  - If adding scheduling or external process execution, prefer Symfony\Component\Process and respect environment merging patterns used in RunTemplateCommand.


