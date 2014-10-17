## CHANGES

### Last Version

  * [Delegate lookup](https://github.com/container-interop/container-interop/blob/delegate-lookup/docs/Delegate-lookup.md).

### 0.5.0 (2014-10-17)

  * New `registerFactory` method that let you use a closure to create
    the service.
  * Sort parameters before call factory/class constructor.

### 0.4.0 (2014-10-15)

  * [BC BREAK] Rename `registerFactory` to `registerDefinition`.

### 0.3.0 (2014-10-14)

  * Interface as contract for the Registry class.
  * Cyclic dependency detection.

### 0.2.0 (2014-10-04)

  * Store reflection objects separate from it's definition.
  * [BC BREAK] Change `registerFactory` arguments order.
  * New `has` method.
  * Factory arguments can have literals.

### 0.1.0 (2014-10-03)

  * Initial release
