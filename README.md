# Toolkit for Concurrent Programming in PHP with pthreads

[![Latest Stable Version](https://img.shields.io/packagist/v/appserver-io/concurrency.svg?style=flat-square)](https://packagist.org/packages/appserver-io/concurrency) 
 [![Total Downloads](https://img.shields.io/packagist/dt/appserver-io/concurrency.svg?style=flat-square)](https://packagist.org/packages/appserver-io/concurrency)
 [![License](https://img.shields.io/packagist/l/appserver-io/concurrency.svg?style=flat-square)](https://packagist.org/packages/appserver-io/concurrency)
 [![Build Status](https://img.shields.io/travis/appserver-io/concurrency/master.svg?style=flat-square)](http://travis-ci.org/appserver-io/concurrency)
 [![Code Coverage](https://img.shields.io/codeclimate/github/appserver-io/concurrency.svg?style=flat-square)](https://codeclimate.com/github/appserver-io/concurrency)
 [![Code Quality](https://img.shields.io/codeclimate/coverage/github/appserver-io/concurrency.svg?style=flat-square)](https://codeclimate.com/github/appserver-io/concurrency)

## What is it?

The concurrency toolkit introduces abstract services and objects that provides easy handling for thread-safety, concurrency programming and data sharing all written in PHP-Userland.

## Why?

If you are implementing multithreaded functionality you're always fighting against the same problems called Race-Conditions, Deadlocks etc... When sharing simple data or even plain PHP objects between threads you have to make sure, that everything is synchronized (Thread-safe) in any logic of your code. This Toolkit mostly helps you to avoid those problems by providing abstract services and object written in PHP-Userland.

## How to use?

This toolkit is in early-stage development. Please use with caution.

### The ExecutorService

It allows you to hold plain PHP objects persistent as singleton in memory accessible everywhere in your code even in different thread contexts. You can keep those plain PHP objects easily thread-safe or call certain methods asynchronous by using simple annotations like `@Synchronized` or `@Asynchronous` in method docblocks.

#### How it works

If you want to use the concurrency toolkit in your project you have to add it to the composer dependencies `composer.json` and do a `composer update` afterwards.

```javascript
{
    "require": {
        "appserver-io/concurrency": "~0.1"
    },
}
```

At first you have to initialise the `ExecutorService` in a main PHP script `main.php`.
```php
<?php

define(AUTOLOADER, 'vendor/autoload.php');
require_once(AUTOLOADER);

// init executor service
\AppserverIo\Concurrecy\ExecutorService::__init(AUTOLOADER);
```

Lets say you want to build a simple storage PHP object to share data all over your multithreaded implementations. The storage object could look like this. Create `Storage.php` and add:

```php
<?php

class Storage
{
    public $data = array();

    /**
     * @Synchronized
     */
    public function all() {
        return $this->data;
    }

    /**
     * @Synchronized
     */
    public function set($key, $value) {
        $this->data[$key] = $value;
    }

    /**
     * @Synchronized
     */
    public function get($key) {
        if ($this->has($key)) {
            return $this->data[$key];
        }
    }

    /**
     * @Synchronized
     */
    public function has($key) {
        return isset($this->data[$key]);
    }

    /**
     * @Synchronized
     */
    public function del($key) {
        unset($this->data[$key]);
    }

    /**
     * @Synchronized
     */
    public function inc($key) {
        if ($this->has($key)) {
            ++$this->data[$key];
        }
    }

    /**
     * @Asynchronous
     */
    public function dump() {
        echo var_export($this->all(), true) . PHP_EOL;
    }
}
```

Maybe you've noticed the method docblock annotations we used here. Methods marked with `@Synchronized` can not be called more than once at the same time. That means the `$data` array will always be synchronized when using `all()`, `set()`, `get()`, `del()` or `inc()`. Methods using `@Asynchronous` are called asynchronously. So if the `dump()` method is called it will dump the `$data` array while not blocking your main logic execution.

To make use of the storage object we have to create a multithreaded task simulation that should represent multithreaded business logic. So create `Task.php` and add:

```php
<?php
class Task extends Thread {

    public static function simulate($maxThreads) {
        $t = array();
        for ($i=0; $i<$maxThreads; $i++) {
            $t[$i] = new self();
            $t[$i]->start(PTHREADS_INHERIT_ALL | PTHREADS_ALLOW_GLOBALS);
        }
        for ($i=0; $i<$maxThreads; $i++) {
            $t[$i]->join();
        }
    }

    public function run() {
        // get the storage object
        $storage = \AppserverIo\Concurrency\ExecutorService::__getEntity('data');
        // add thread signature
        $storage->set($this->getThreadId(), __METHOD__);
        // increase internal counter
        $storage->inc('counter');
    }
}
```

Now bring all together and enhance the main script `main.php`:

```php
<?php

define('AUTOLOADER', 'vendor/autoload.php');
require_once(AUTOLOADER);
require_once('Storage.php');
require_once('Task.php');

use \AppserverIo\Concurrency\ExecutorService as ExS;

// init executor service
ExS\Core::init(AUTOLOADER);

// create storage instance with alias data
$data = ExS\Core::newFromEntity('Storage', 'data');

// preinit counter
$data->set('counter', 0);

// simulate multithreaded tasks
Task::simulate(10);

// dump data async
$data->dump();

echo 'finished' . PHP_EOL;

// shutdown executor service and its entities
ExS\Core::shutdown();
```

If you call `main.php` with a thread-safe compiled php version where pthreads ext is installed as
it`s provided by appserver.io runtime for example the result should look like this:

```bash
$ /opt/appserver/bin/php bootstrap.php 
finished
array (
  'counter' => 10,
  140470559000320 => 'Task::run',
  140470550284032 => 'Task::run',
  140470541891328 => 'Task::run',
  140470327965440 => 'Task::run',
  140470319572736 => 'Task::run',
  140470311180032 => 'Task::run',
  140470302787328 => 'Task::run',
  140470294394624 => 'Task::run',
  140470286001920 => 'Task::run',
  140470277609216 => 'Task::run',
)
```

The executor service has some internal function as described here:

| Method | Description |
| ---------- | ----------- |
| `__return` | Returns the plain entity object from executor thread context if its serializable in its actual state |
| `__invoke(closure)` | Executes the callable in executor thread context. It will provide $self as function argument which references the plain entity object. Example usage: <pre lang="php">$executorServiceEntity->__invoke(function($self) { $self->doSomething() }); </pre> |
| `__reset()` | Resets the plaing entity object in executor service context
| `__shutdown()` | Shutdown the executor service thread 

## Issues
In order to bundle our efforts we would like to collect all issues regarding this package in [the main project repository's issue tracker](https://github.com/appserver-io/appserver/issues).
Please reference the originating repository as the first element of the issue title e.g.:
`[appserver-io/<ORIGINATING_REPO>] A issue I am having`
