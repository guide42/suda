<?php

namespace Guide42\Suda;

class Registry
{
    public $settings = array();

    private $services = array();

    public function register($service) {
        $interfaces = class_implements($service);

        if (empty($interfaces)) {
            throw new \LogicException(
                'Service must implement at least one interface'
            );
        }
    }
}