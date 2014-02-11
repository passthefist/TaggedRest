<?php
namespace \Tagged\Rest\Schema;

class InvalidParamsException extends \Klein\ValidationException {
    public function __construct($errorMap) {
        $this->message = $errorMap['message'];
    }

}
