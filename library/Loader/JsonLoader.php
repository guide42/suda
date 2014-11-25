<?php

namespace Guide42\Suda\Loader;

class JsonLoader extends Loader
{
    public function load($resource) {
        $text = file_get_contents($resource);
        $json = json_decode($text);

        if (!is_object($json)) {
            throw new \RuntimeException('Could not decode JSON');
        }

        // TODO
    }

    public function supports($resource) {
        return is_file($resource) && substr($resource, -5) === '.json';
    }
}