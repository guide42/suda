<?php declare(strict_types=1);

namespace suda\psr11;

use Psr\Container\ContainerInterface;
use suda\Registry as SudaRegistry;

final class Container implements ContainerInterface
{
    /** @var SudaRegistry */
    private $registry;

    function __construct(SudaRegistry $registry) {
        $this->registry = $registry;
    }

    function withDelegate(ContainerInterface $container) {
        $new = clone $this;
        $new->registry = $this->registry->withDelegate(new class($container) extends SudaRegistry {
            /** @var ContainerInterface */
            private $container;

            function __construct(ContainerInterface $container) {
                $this->container = $container;
            }

            function offsetGet($key) {
                if ($this->container->has($key)) {
                    return $this->container->get($key);
                }
                return parent::offsetGet($key);
            }

            function offsetExists($key) {
                if ($this->container->has($key)) {
                    return true;
                }
                return parent::offsetExists($key);
            }
        });

        return $new;
    }

    function get($id) {
        try {
            $ret = $this->registry[$id];
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
        return isset($this->registry[$id]);
    }
}