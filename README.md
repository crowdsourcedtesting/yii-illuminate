This package makes easy the integration with some [Laravel components](https://github.com/illuminate)

## Requirements

The following versions of PHP are supported.

* PHP 5.6
* HHVM (not tested)

## Installation

You can install the package using the Composer package manager. You can install it by running this command in your project root:

```
composer require cst/yii-illuminate
```

## Components

### Migrate command

Supercharges Yii `MigrateCommand` with all the functionality of the [Laravel Schema Builder](http://laravel.com/docs/5.0/schema)

Add the following block to your config file:

```
'commandMap'=> [
    'migrate'=> [
        'class' => '\CST\Yii\Illuminate\Console\MigrateCommand',
        'migrationTable' => 'yii_migrations',
        'connectionID' => 'db',
    ],
],
```

### Queues

Queues allow you to defer the processing of a time consuming task, such as sending an e-mail, until a later time, thus drastically speeding up the web requests to your application.

**RedisQueue**

Add the following block to your config file:

```
'queue' => [
    'class' => '\CST\Yii\Illuminate\Queue\RedisQueue',
    'encryptionKey' => '<random-string-of-16bytes>',
    'config' => [
        'cluster' => false,
        'default' => [
            'host'     => '<HOST>',
            'port'     => 6379,
            'database' => 0,
        ],
    ]
],
```

Queuing jobs:

```php
Yii::app()->queue->push(new SendEmail($message));
```
