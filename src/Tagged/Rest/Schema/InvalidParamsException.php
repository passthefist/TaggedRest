<?php
namespace Tagged\Rest\Schema;

class InvalidParamsException extends \Exception {
    public function __construct($errorMap) {
        $this->message = $errorMap[0]->message;
    }

}
