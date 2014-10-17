SUDA
====

Suda is a lightweight container for your services.

This library is compatible with [Container Interoperability][].

[Container Interoperability]: https://github.com/container-interop/container-interop

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

You can get an array with the name as key and the service as value for all the
services that implement a given interface.

```php
$loggers = $registry->getAll('\Psr\Log\LoggerInterface');
```

### Register Service by Definition

You can also give *Suda* the power to create the instances of your services.
For that you need to "define" the class of your service and the arguments it
takes when it going to be created.

```php
$registry->registerDefinition(
    '\Symfony\Component\Routing\Matcher\UrlMatcher',
    '',
    array(
        '\Symfony\Component\Routing\RouteCollection',
        '\Symfony\Component\Routing\RequestContext'
    )
);
```

And you retrieve as a normal service with a third argument of the context that
replace the original arguments you define. If you skip one argument in the
context, *Suda* will try to get it from itself.

```php
$urlMatcher = $registry->get(
    '\Symfony\Component\Routing\Matcher\UrlMatcherInterface',
    '',
    array(
        new \Symfony\Component\Routing\RouteCollection(),
        new \Symfony\Component\Routing\RequestContext(),
    )
);
```

### Register Service Factory

Finally, you can register a closure that will create the service you need. You
need to specify which interfaces it provides and a closure that takes it
requirements as parameters.

```php
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\MessageSelector;

$registry->register(new MessageSelector());
$registry->registerFactory(
    array('\Symfony\Component\Translation\TranslatorInterface'),
    function(MessageSelector $messageSelector, $locale='fr_FR') {
        return new Translator($locale, $messageSelector);
    }
);

$translator = $registry->get('\Symfony\Component\Translation\TranslatorInterface');
$translator->getLocale() === 'fr_FR'; // TRUE
```

Badges
------

[![Latest Stable Version](https://poser.pugx.org/guide42/suda/v/stable.svg)](https://packagist.org/packages/guide42/suda)
[![Build Status](https://travis-ci.org/guide42/suda.svg?branch=master)](https://travis-ci.org/guide42/suda)