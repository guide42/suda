<?php

namespace Guide42\Suda\Exception;

use RuntimeException;
use Interop\Container\Exception\NotFoundException as InteropNotFoundException;

class NotFoundException extends RuntimeException
                        implements InteropNotFoundException
{
}