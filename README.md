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

Supercharges Yii `MigrateCommand` with all the functionality of the [Laravel Schema Builder](http://laravel.com/docs/5.1/migrations)

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

**Configuring queue command**

Add the following block to your config file:

```
'commandMap'=> [
    ...,
    'queue'=> [
        'class' => '\CST\Yii\Illuminate\Console\QueueCommand',
    ],
],
```

Queuing jobs:

```php
Yii::app()->queue->push(new SendEmail($message));
```

or 

```php
use CST\Yii\Illuminate\Queue\DispatchesJobs;

$this->dispatch(new SendEmail($message));
```

## Helper Functions

##### app(string $component = null)

Get the Yii App instance. It's a shortcut for *Yii::app()*. You can also pass the component name to get the instance.

```php
app('clientScript')->registerScriptFile(...);
```

##### t(string $category, string $message, array $params = [], string $source = null, string $language = null)

*Yii::t()* shortcut for translating messages.

```php
t('Project', 'Save changes');
```

##### view(string $path, array $data, bool $return)

Renders evaluated view contents for the given view. Replaces the typical *$this->render(...)*

```php
view('user/view', ['user' => $user]);
```

##### viewPartial(string $path, array $data, bool $return)

Renders evaluated view contents for the given view and it does not apply a layout to the rendered result. Replaces the typical *$this->renderPartial(...)*

```php
viewPartial('user/pic', ['user' => $user]);
```

##### request(string $key, $default = null)

Get an instance of the current request or an input item from the request.

```php
$modelId = request('id')
```

##### asset(string $path)

Generate an asset path for the application theme.

```php
asset('js/main.js');
asset('images/logo.png');
```

##### url(string $path, array $parameters, bool $secure)

Generate a url for the application.

```php
url('project/view', ['id' => 1]);
```
