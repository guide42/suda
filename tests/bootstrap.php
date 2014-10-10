<?php

$loader = require_once __DIR__ . '/../vendor/autoload.php';
$loader->setPsr4('Guide42\\Suda\\Tests\\', __DIR__);

return $loader;