<?php
class Api {
    private $filters;

    public function instance() {
        $controller = new static();
    }

    //TODO ensure method is protected
    protected function register($method, $schema) {
    }

    public function __call($method, $args) {
    }

    protected
}
