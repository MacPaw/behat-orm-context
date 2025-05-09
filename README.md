# Behat ORM Context Bundle

## Installation

### Step 1: Download the Bundle

Open a command console, enter your project directory and execute:

#### Applications that use Symfony Flex [in progress](https://github.com/MacPaw/BehatRedisContext/issues/2)

```bash
composer require --dev macpaw/behat-orm-context
```

#### Applications that don't use Symfony Flex

Open a command console, enter your project directory and execute the following command to download the latest stable version of this bundle:

```bash
composer require --dev macpaw/behat-orm-context
```

This command requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.


Then, enable the bundle by adding it to the list of registered bundles
in the `app/AppKernel.php` file of your project:

```php
<?php
// app/AppKernel.php

// ...
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            // ...
            BehatOrmContext\BehatOrmContextBundle::class => ['test' => true],
        );

        // ...
    }

    // ...
}
```

---

### Step 2: Configure Behat

Go to `behat.yml`:

```yaml
# ...
  contexts:
    - BehatOrmContext\Context\OrmContext
# ...
```

---

## Configuration

By default, the bundle has the following configuration:

```yaml
behat_orm_context:
```

You can override it manually in your `config/packages/test/behat_orm_context.yaml`:

---

