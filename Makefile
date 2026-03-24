.PHONY: phpunit phpstan phpcs cs-fix rector dev-checks

phpunit:
	composer phpunit

phpstan:
	composer phpstan

phpcs:
	composer code-style

cs-fix:
	composer code-style-fix

rector:
	composer rector-fix

dev-checks:
	composer dev-checks
