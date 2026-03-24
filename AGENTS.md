# Agents

## Cursor Cloud specific instructions

PHP library (Symfony bundle) providing Behat contexts for Doctrine ORM testing. No runtime services or databases needed — all tests use mocked EntityManager.

### Dev commands

All defined in `composer.json` scripts section:

- `composer dev-checks` — runs validate + phpstan + phpcs + phpunit (use this as the full CI check)
- `composer phpunit` — unit tests only
- `composer phpstan` — static analysis (level max)
- `composer code-style` — PHP_CodeSniffer (PSR-12 + Slevomat rules)
- `composer code-style-fix` — auto-fix code style issues

### Notes

- PHP 8.3 is installed from the `ondrej/php` PPA. The package supports PHP 7.4–8.4.
- No `composer.lock` is committed — `composer install` resolves from `composer.json` each time.
- PHPUnit config uses `convertErrorsToExceptions`/`convertNoticesToExceptions`/`convertWarningsToExceptions` which emit deprecation notices on PHPUnit 9.6 but tests still pass.
- `phpcs.xml.dist` has a deprecation warning about comma-separated array syntax for `forbiddenFunctions` property — cosmetic only, does not affect results.
