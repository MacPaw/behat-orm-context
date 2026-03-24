.PHONY: phpunit phpstan phpcs cs-fix rector dev-checks bc-check

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

bc-check:
	composer backward-compatibility-check
