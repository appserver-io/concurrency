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

### The ExecutorService

It allows you to hold plain PHP objects persistent as singleton in memory accessible everywhere in your code even in different thread contexts. You can keep those plain PHP objects easily thread-safe or call certain methods asynchronous by using simple annotations like `@Synchronized` or `@Asynchronous` in method docblocks.

#### How it works

If you want to use the concurrency toolkit in your project you have to add it to the composer dependencies and do a `composer update` afterwards.

```javascript
{
    "require": {
        "appserver-io/concurrency": "~0.1"
    },
}
```

At first you have to initialise the `ExecutorService` in you main PHP script.
```php
<?php

define(AUTOLOADER, 'vendor/autoload.php');
require_once(AUTOLOADER);

// init executor service
\AppserverIo\Concurrecy\ExecutorService::__init(AUTOLOADER);
```

Lets say you want to build a simple storage PHP object to share data all over your multithreaded implementations. The storage object could look like this.

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
     * @Asynchronous
     */
    public function dump() {
        error_log(var_export($this, true));
    }
}
```

Maybe you've noticed the method docblock annotations we used here. Methods marked with `@Synchronized` can not be called more than once at the same time. That means the `$data` array will always be synchronized when using `all()`, `set()`, `get()` or `del()`. Methods using `@Asynchronous` are called asynchronously. So if the `dump()` method is called it will dump the `$data` array to error_log while not blocking your main logic execution.




## Issues
In order to bundle our efforts we would like to collect all issues regarding this package in [the main project repository's issue tracker](https://github.com/appserver-io/appserver/issues).
Please reference the originating repository as the first element of the issue title e.g.:
`[appserver-io/<ORIGINATING_REPO>] A issue I am having`
