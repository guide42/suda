SUDA
====

Suda is a lightweight container for your services.

Usage
-----

To create a container you just need to create a new instance of the `Registry`
class. From there you can start to storing *services* and *settings*.

```php
$registry = new \Guide42\Suda\Registry();
```

You have a free storage for your config under the settings variable. There you
can store whatevery your application need.

```php
$registry->settings['debug'] = true;
$registry->settings['env'] = 'dev';
```

Any instance of a class that implements at least one interface, is considered a
service in Suda.

```php
$db = DriverManager::getConnection($params, $config);
```

You can register your services with a simple call to the `Registry::register`
function.

```php
$registry->register($db);
```

You retrieve the stored services by calling `Registry::get` with the name of
the interface they implement.

```php
$db = $registry->get('\Doctrine\DBAL\Driver\Connection');
```

Badges
------

[![Latest Stable Version](https://poser.pugx.org/guide42/suda/v/stable.svg)](https://packagist.org/packages/guide42/suda)
[![Build Status](https://travis-ci.org/guide42/suda.svg?branch=master)](https://travis-ci.org/guide42/suda)