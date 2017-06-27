<?php declare(strict_types=1);

namespace suda\psr11;

use Psr\Container\ContainerInterface;
use suda\Registry as SudaContainer;

class Container extends SudaContainer implements ContainerInterface
{
    function get($id) {
        try {
            $ret = $this[$id];
        } catch (\InvalidArgumentException $e) {
            throw new ContainerException($e->getMessage());
        } catch (\LogicException $e) {
            throw new ContainerException($e->getMessage());
        } catch (\RuntimeException $e) {
            if (strncmp($e->getMessage(), 'Entry', 5) === 0) {
                throw new NotFoundException($e->getMessage());
            }
            throw new ContainerException($e->getMessage());
        }

        return $ret;
    }

    function has($id) {
        return isset($this[$id]);
    }
}