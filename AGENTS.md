# Agents

## Cursor Cloud specific instructions

PHP library (Symfony bundle) providing Behat contexts for Doctrine ORM testing. No runtime services or databases needed — all tests use mocked EntityManager.

### Dev commands

All defined in `composer.json` scripts section:

- `composer dev-checks` — runs validate + phpstan + phpcs + phpunit (use this as the full CI check)
- `composer phpunit` — unit tests only (uses `scripts/run-phpunit.sh`: PHPUnit 11+ gets extra flags; PHPUnit 9 unchanged)
- `composer phpstan` — static analysis (level max)
- `composer code-style` — PHP_CodeSniffer (PSR-12 + Slevomat rules)
- `composer code-style-fix` — auto-fix code style issues
- `composer rector` / `composer rector-fix` — Rector (see `rector.php`)
- `make phpunit` / `make dev-checks` / `make cs-fix` — thin wrappers around the same scripts

### Notes

- PHP 8.3 is installed from the `ondrej/php` PPA. The package requires **PHP ^8.0** and **Symfony ^6.0** (Symfony 4.x and 5.x are not supported).
- `composer.lock` is gitignored. `config.platform.php` defaults to **8.0.2** so dependency resolution matches Symfony 6’s minimum PHP.
- `phpcs.xml.dist` has a deprecation warning about comma-separated array syntax for `forbiddenFunctions` property — cosmetic only, does not affect results.
