# Installation

## Step 1: Install the Bundle

Run the following command in your project directory to install the bundle as a development dependency:

```bash
  composer require --dev macpaw/behat-orm-context
````

> If you are using Symfony Flex, the bundle will be registered automatically.
> Otherwise, follow Step 2 to register the bundle manually.

## Step 2: Register the Bundle

If your project does **not** use Symfony Flex or the bundle does not provide a recipe, manually register it in `config/bundles.php`:

```php
<?php
// config/bundles.php

return [
    // ...
    BehatOrmContext\BehatOrmContextBundle::class => ['test' => true],
];
```

> ℹ️ The bundle should only be enabled in the `test` environment.

## Step 3: Configure Behat

Add the ORM context to your `behat.yml`:

```yaml
default:
  suites:
    default:
      contexts:
        - BehatOrmContext\Context\ORMContext
```

## Step 4 (Optional): Inject a Custom ObjectManager

By default, `ORMContext` uses the `doctrine.orm.entity_manager` service.
To override this and inject a custom Doctrine ObjectManager (which implements `Doctrine\ORM\EntityManagerInterface`), 
update your service configuration in `config/services.yaml` under the `test` environment:

```yaml
when@test:
  services:
    BehatOrmContext\Context\ORMContext:
      arguments:
        $manager: '@doctrine.orm.other_entity_manager'
```

This allows you to swap the ObjectManager used by the context without modifying the class itself.
