## CHANGES

 - [BC-BREAK] Reflector parameter `$method` is no longer nullable. Only `ReflectionMethod` should be constructed.
 - [BC-BREAK] Default values in parameters are not resolved.
 - [BC-BREAK] Given `$make` function doesn't retrieve dependencies, just make object without cache.
 - New `Registry::freeze` to disallow new values or factories to be assigned.

### 0.14.0

 - [BC-BREAK] Drop PSR-11 support.
 - [BC-BREAK] Require PHP 7.2 minimum.
 - [BC-BREAK] Doesn't resolve parameters for factories anymore.
 - [BC-BREAK] Replace `Registry::withDelegate` with `Registry::setDelegate`.
 - New `Registry::setReflector` to assign a function to cache the creation of `Reflection` objects.

### 0.13.0

  - [BC-BREAK] Factory call to make without parameters use make of key class.
  - [BC-BREAK] Call a function will resolve parameters from it's own instead of delegate.
  - [BC-BREAK] Resolve parameters default values.
  - [BC-BREAK] Factory make with dependency can be configured in delegate.

### 0.12.0

  - [BC-BREAK] Factories are given an instance of delegate instead it's own.

### 0.11.0

  - [BC-BREAK] Mark `Registry::make` as private.
  - [BC-BREAK] Doesn't call automagically params that are callables.
  - Factories parameters are resolved and injected.
  - Call functions and inject parameters with `Registry::__invoke`.

### 0.10.0

  - Delegate in PSR-11 is done with `Container::withDelegate`.
  - [BC-BREAK] Change PSR-11 support to be a wrapper around `Registry` instead of extend it.

### 0.9.0

  - PSR-11 support.
  - [BC-BREAK] Arguments are not revolved as parameter unless dollar sign is prefix.
  - [BC-BREAK] Removed resolve by parameter name.

### 0.8.0

  - Cyclic dependency detection.
  - Stack factories.
  - Delegate can be changed with `Registry::withDelegate`.
  - Define concrete class with arguments.
  - Resolve given arguments from delegate.

### 0.7.0

New library incompatible with previous versions.
