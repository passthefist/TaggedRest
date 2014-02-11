<?php
namespace Kleinbottle\Api;
use \Kleinbottle\api;

class RawWrapper extends Base {
    private $controller;

    public function __construct($controller) {
        $this->controller = $controller;
    }

    public function getCollectionMethods() {
        return array_intersect(
            get_class_methods($this->controller),
            array_keys($this->collectionMapping)
        );
    }

    public function getResourceMethods() {
        return array_intersect(
            get_class_methods($this->controller),
            array_keys($this->resourceMapping)
        );
    }

    public function __call($method, $args) {
        if (!method_exists($this->controller,$method)) {
            throw new \BadMethodCallException("Method '$method' does not exist");
        }

        if ($this->controller->respondsTo($method)) {
            $args = json_decode(json_encode($args[0]));

            $result = $this->controller->invoke($method, $args);
            return  json_decode(json_encode($result));
        }

        return call_user_func_array(
            array($this->controller,$method),
            $args
        );
    }
}
