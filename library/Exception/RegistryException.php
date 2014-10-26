<?php

namespace Guide42\Suda\Exception;

use LogicException;
use Interop\Container\Exception\ContainerException;

class RegistryException extends LogicException implements ContainerException
{
    const MUST_IMPLEMENT_INTERFACE   = 314;
    const CYCLIC_DEPENDENCY_DETECTED = 3141;
}