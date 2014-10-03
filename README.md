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

You have a free storage for your config under the `settings` variable. There
you can store whatever your application needs.

```php
$registry->settings['debug'] = true;
$registry->settings['env'] = 'dev';
```

### Register Services

Any instance of a class that implements at least one interface, is considered a
service in *Suda*. Most of today's OO libraries use interfaces as design by
contract.

```php
$db = DriverManager::getConnection($params, $config);
```

You can register your services with a simple call to the `Registry::register`
function.

```php
$registry->register($db);
```

And retrieve them by calling `Registry::get` with the name of the interface
they implement.

```php
$db = $registry->get('\Doctrine\DBAL\Driver\Connection');
```

If you use several services with the same interfaces, the last one you
register will be the one you get. If can fix this by using an optional name
for the service you register.

```php
$registry->register(new \Monolog\Logger('main'), 'main');
$registry->register(new \Monolog\Logger('dev'), 'dev');
```

And retrieve them by the interface and name.

```php
$registry->get('\Psr\Log\LoggerInterface', 'main')->addWarning('Help!');
$registry->get('\Psr\Log\LoggerInterface', 'dev')->addError('Help!');
```

Badges
------

[![Latest Stable Version](https://poser.pugx.org/guide42/suda/v/stable.svg)](https://packagist.org/packages/guide42/suda)
[![Build Status](https://travis-ci.org/guide42/suda.svg?branch=master)](https://travis-ci.org/guide42/suda)