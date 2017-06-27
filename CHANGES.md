## CHANGES

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
