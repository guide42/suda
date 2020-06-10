<?php declare(strict_types=1);

interface Engine {}

abstract class BaseEngine implements Engine {}

class V8 implements Engine {
    function __invoke(string $prefix='') {
        return "${prefix}World";
    }
}

class W16 implements Engine {
    public $left;
    public $right;

    function __construct(Engine $left, Engine $right) {
        $this->left = $left;
        $this->right = $right;
    }
}

class Turbine extends BaseEngine {
    public $power;

    function __construct(?int $power) {
        $this->power = $power ?? 60000;
    }
}

class Car {
    public $engine;
    public $color;

    function __construct(Engine $engine, string $color='red') {
        $this->engine = $engine;
        $this->color = $color;
    }
}
