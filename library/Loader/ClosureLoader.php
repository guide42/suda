<?php

namespace Guide42\Suda\Loader;

class ClosureLoader extends Loader
{
    public function load($resource) {
        call_user_func($resource, $this->getRegistry());
    }

    public function supports($resource) {
        return $resource instanceof \Closure;
    }
}