<?php
require_once __DIR__."/../vendor/autoload.php";
set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__);

class testRawWrapper extends PHPUnit_Framework_TestCase {

    public function setUp() {
        Phockito::include_hamcrest();

        $this->controller = Phockito::spy('Tagged\Rest\tests\fixtures\controller');
    }

    public function testRawAccess() {
        $result = $this->controller->find(array());
        $this->assertEqual($result,"hello");
    }
}

