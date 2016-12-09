# Hgraca\MicroOrm
[![Author](http://img.shields.io/badge/author-@hgraca-blue.svg?style=flat-square)](https://www.herbertograca.com)
[![Software License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE)
[![Latest Version](https://img.shields.io/github/release/hgraca/php-micro-orm.svg?style=flat-square)](https://github.com/hgraca/php-micro-orm/releases)
[![Total Downloads](https://img.shields.io/packagist/dt/hgraca/micro-orm.svg?style=flat-square)](https://packagist.org/packages/hgraca/micro-orm)

[![Build Status](https://img.shields.io/scrutinizer/build/g/hgraca/php-micro-orm.svg?style=flat-square)](https://scrutinizer-ci.com/g/hgraca/php-micro-orm/build)
[![Coverage Status](https://img.shields.io/scrutinizer/coverage/g/hgraca/php-micro-orm.svg?style=flat-square)](https://scrutinizer-ci.com/g/hgraca/php-micro-orm/code-structure)
[![Quality Score](https://img.shields.io/scrutinizer/g/hgraca/php-micro-orm.svg?style=flat-square)](https://scrutinizer-ci.com/g/hgraca/php-micro-orm)

A very small ORM library.
It doesnt have any kind of caching, nor instance management. 
I've built it as a learning tool and maybe at some point it will be usable, but always as a very thin layer.

## Usage

The config can be something like:

```php
[
    'repositoryFqcn' => MySqlPdoRepository::class,
    'dateTimeFormat' => 'Y-m-d H:i:s',
    'collectionFqcn' => Collection::class
    'dataMapper' => [
        Entity::class => [
            'entityFcqn'                 => Entity::class,
            // if not set, it will be inferred from the entity name,
            // if it doesnt exit, the default Repository will be used
            'repositoryFqcn'             => EntityRepository::class,
            'table'                      => ClassHelper::extractCanonicalClassName(Entity::class),
            'propertyToColumnNameMapper' => array_combine($properties, $properties),
            'collectionFqcn'             => Collection::class,
            'attributes' => [
                'id' => [ // by convention its always 'id'
                    'column' => 'id',
                    'type' => 'integer', // by convention its always an integer
                ],
                'aProperty' => [
                    'column' => 'aColumn_name',
                    'type' => 'integer', // integer, float, boolean, string, text, datetime
                ],
            ],
        ],
    ],
]
```

### Conventions

- All entities have an ID, who's property name is 'id', column name is 'id' and type is int
- The default DB to be used is the first registered

## Installation

To install the library, run the command below and you will get the latest version:

```
composer require hgraca/micro-orm
```

## Tests

To run the tests run:
```bash
make test
```
Or just one of the following:
```bash
make test-acceptance
make test-functional
make test-integration
make test-unit
make test-humbug
```
To run the tests in debug mode run:
```bash
make test-debug
```

## Coverage

To generate the test coverage run:
```bash
make coverage
```

## Code standards

To fix the code standards run:
```bash
make cs-fix
```

## Todo

- Document how to use repositories and query classes, and how not to
- Create a relational config format, like the Doctrine yml config, but with arrays
- Implement lazy loading
- Create an EntityManager, management so we only save entities in the end of the request and works as a 1st level cache
- Implement 2nd level caching
- Implement eager loading
