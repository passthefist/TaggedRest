<?php
require_once __DIR__."/../vendor/autoload.php";
set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__);

class testRouter extends PHPUnit_Framework_TestCase {

    public function setUp() {
        Phockito::include_hamcrest();

        $this->url_root = "/routing";
        $this->controller_path = 'fixtures';
        $this->controller_namespace = 'Kleinbottle\tests\fixtures';

        $this->routes = array(
            "/test/[i:var]" => "json",
        );

        $this->router = Phockito::spy('Kleinbottle\router',
            $this->url_root,
            $this->routes,
            $this->controller_path,
            $this->controller_namespace
        );

        $this->router->initAutoload();
        $this->router->loadRoutes();
    }

    public function mockController() {
        $this->controller = Phockito::spy('Kleinbottle\tests\fixtures\json');
        Phockito::when($this->router)->loadController('json')->return($this->controller);
    }

    /**
     * For resources
     *
     * GET->fetch
     * PUT->update
     * DELETE->delete
     *
     * anything else is an HTTP 405 err
     * (POST not allowed)
     */
    public function testGETisFETCH() {
        $this->mockController();

        $_GET['var2'] = json_decode(json_encode(array('hello' => array('goodbye' => 'hi', 'robert'))));

        $response = $this->router->routeRequest('/routing/test/5', 'GET');
    }
}

