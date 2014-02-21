<?php
namespace Tagged\Rest\Api;
use \Tagged\Rest\api;

class RawWrapper extends Base {
    private $controller;

    public function __construct($controller) {
        $this->controller = $controller;
    }

    public function getCollectionMethods() {
        return $this->controller->getCollectionMethods();
    }

    public function getResourceMethods() {
        return $this->controller->getResourceMethods();
    }

    public function __call($method, $args) {
        if (!method_exists($this->controller,$method)) {
            throw new \BadMethodCallException("Method '$method' does not exist");
        }

        if ($this->controller->respondsTo($method)) {
            $result = $this->controller->invoke($method, $args[0]);
            return  json_decode(json_encode($result));
        }

        return call_user_func_array(
            array($this->controller,$method),
            $args
        );
    }
}
