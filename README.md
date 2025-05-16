# Behat ORM Context Bundle

|  Version  |                       Build Status                        |                              Code Coverage                               |
|:---------:|:---------------------------------------------------------:|:------------------------------------------------------------------------:|
| `main`    |  [![CI][main Build Status Image]][main Build Status]      |   [![Coverage Status][main Code Coverage Image]][main Code Coverage]     |
| `develop` | [![CI][develop Build Status Image]][develop Build Status] | [![Coverage Status][develop Code Coverage Image]][develop Code Coverage] |

Behat context for testing Doctrine ORM integration.

## Description

This bundle provides a Behat context for testing your application's interaction with a database using Doctrine ORM.

## Installation

See the [installation instructions](docs/install.md).

## Features

The bundle provides several Behat step definitions for ORM testing:

* [See X entities in repository](docs/ORMContext/see-count-in-repository.md) - Check if the count of entities in the repository matches expected
* [See entity with ID](docs/ORMContext/see-entity-in-repository-with-id.md) - Check if an entity with a specific ID exists
* [See entity with properties](docs/ORMContext/see-entity-in-repository-with-properties.md) - Check if an entity with specific properties exists

## License

This bundle is released under the MIT license. See the included [LICENSE](LICENSE) file for more information.

[main Build Status]: https://github.com/macpaw/behat-orm-context/actions?query=workflow%3ACI+branch%main
[main Build Status Image]: https://github.com/macpaw/behat-orm-context/workflows/CI/badge.svg?branch=main
[develop Build Status]: https://github.com/macpaw/behat-orm-context/actions?query=workflow%3ACI+branch%3Adevelop
[develop Build Status Image]: https://github.com/macpaw/behat-orm-context/workflows/CI/badge.svg?branch=develop
[main Code Coverage]: https://codecov.io/gh/macpaw/behat-orm-context/branch/main
[main Code Coverage Image]: https://img.shields.io/codecov/c/github/macpaw/behat-orm-context/main?logo=codecov
[develop Code Coverage]: https://codecov.io/gh/macpaw/behat-orm-context/branch/develop
[develop Code Coverage Image]: https://img.shields.io/codecov/c/github/macpaw/behat-orm-context/develop?logo=codecov

