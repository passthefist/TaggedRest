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
            "/test/[i:var]" => "controller",
            "/empty/[i:var]" => "emptycontroller",
            "/empty/[i:var]/things" => "emptycontroller",
            "/test/[i:var]/tests" => "controller",
            "/nested/[i:var]" => array(
                "/child/[i:var2]" => 'controller',
                "/child/[i:var2]/tests" => 'controller',
                "/child/[i:var2]/json" => 'jsoncontroller',
            )
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

    public function testLoadController() {
        $ctrl = $this->router->loadController('controller');
        $this->assertEquals(get_class($ctrl), 'Kleinbottle\tests\fixtures\controller');
    }

    public function testIsResourceRoute() {
        $basic = "/test/[i:var]/aGain/[:vaR]";
        $typed = "/test/[i:var]/again/[a:Var]";
        $optional = "/test/[i:var]/again/[:var]?";
        $collection = "/test/[i:var]/again/collection";
        $broken = "/test/[i:var]/again/[:]";

        $this->assertTrue($this->router->isResourceRoute($basic));
        $this->assertTrue($this->router->isResourceRoute($typed));
        $this->assertFalse($this->router->isResourceRoute($optional));
        $this->assertFalse($this->router->isResourceRoute($collection));
        $this->assertFalse($this->router->isResourceRoute($broken));
    }

    public function testIsCollectionRoute() {
        $basic = "/test/[i:var]/again/[:var]";
        $typed = "/test/[i:var]/again/[a:var]";
        $optional = "/test/[i:var]/again/[:var]?";
        $collection = "/test/[i:var]/again/collEctIon";
        $alphanum = "/test/[i:var]/again/collect_ion123";
        $base = "/test";
        $broken = "/test/[i:var]/again/[:]";

        $this->assertFalse($this->router->isCollectionRoute($basic));
        $this->assertFalse($this->router->isCollectionRoute($typed));
        $this->assertFalse($this->router->isCollectionRoute($optional));
        $this->assertTrue($this->router->isCollectionRoute($collection));
        $this->assertTrue($this->router->isCollectionRoute($alphanum));
        $this->assertTrue($this->router->isCollectionRoute($base));
        $this->assertFalse($this->router->isCollectionRoute($broken));
    }

    public function mockController() {
        $this->controller = Phockito::spy('Kleinbottle\tests\fixtures\controller');
        Phockito::when($this->router)->loadController('controller')->return($this->controller);
        $this->router->captureOutput();
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

        $response = $this->router->routeRequest('/routing/test/5', 'GET');
        Phockito::verify($this->controller)->fetch(anything(),anything());
        Phockito::reset($this->controller);

        $this->assertEquals(
            $response->body(),
            '{"action":"fetch","var":"5","var2":null}'
        );

        $response = $this->router->routeRequest('/routing/nested/5/child/70', 'GET');
        Phockito::verify($this->controller)->fetch(anything(),anything());

        $this->assertEquals(
            $response->body(),
            '{"action":"fetch","var":"5","var2":"70"}'
        );
    }

    public function testPUTisUPDATE() {
        $this->mockController();

        $response = $this->router->routeRequest('/routing/test/5', 'PUT');
        Phockito::verify($this->controller)->update(anything(),anything());
        Phockito::reset($this->controller);

        $this->assertEquals(
            $response->body(),
            '{"action":"update","var":"5","var2":null}'
        );

        $response = $this->router->routeRequest('/routing/nested/5/child/70', 'PUT');
        Phockito::verify($this->controller)->update(anything(),anything());

        $this->assertEquals(
            $response->body(),
            '{"action":"update","var":"5","var2":"70"}'
        );
    }

    public function testDELETEisDELETE() {
        $this->mockController();

        $response = $this->router->routeRequest('/routing/test/5', 'DELETE');
        Phockito::verify($this->controller)->delete(anything(),anything());
        Phockito::reset($this->controller);

        $this->assertEquals(
            $response->body(),
            '{"action":"delete","var":"5","var2":null}'
        );

        $response = $this->router->routeRequest('/routing/nested/5/child/70', 'DELETE');
        Phockito::verify($this->controller)->delete(anything(),anything());

        $this->assertEquals(
            $response->body(),
            '{"action":"delete","var":"5","var2":"70"}'
        );
    }

    public function testResourceUnmatchedIs404() {
        $this->router->captureOutput();

        $response = $this->router->routeRequest('/bad/route', 'GET');
        $this->assertEquals($response->status()->getCode(), 404);
        $response = $this->router->routeRequest('/routing/bad', 'GET');
        $this->assertEquals($response->status()->getCode(), 404);
        $response = $this->router->routeRequest('/routing/test', 'GET');
        $this->assertEquals($response->status()->getCode(), 404);
        $response = $this->router->routeRequest('/routing/test/5/bad', 'GET');
        $this->assertEquals($response->status()->getCode(), 404);
    }

    public function testResourceActionUnmatchedIs405() {
        $this->router->captureOutput();

        $response = $this->router->routeRequest('/routing/empty/5', 'GET');
        $this->assertEquals($response->status()->getCode(), 405);

        $response = $this->router->routeRequest('/routing/empty/5', 'PUT');
        $this->assertEquals($response->status()->getCode(), 200);
    }


    /**
     * For collections
     *
     * GET->index
     * POST->create
     * PUT->bulkUpdate
     * DELETE->deleteAll
     *
     * Also, collections support arbitrary actions
     *
     * i.e: /messages/search
     *
     * will call the method search on the messages controller.
     *
     * anything else is an HTTP 405 err
     */
    public function testGETisINDEX() {
        $this->mockController();

        $response = $this->router->routeRequest('/routing/test/5/tests', 'GET');
        Phockito::verify($this->controller)->index(anything(),anything());
        Phockito::reset($this->controller);

        $this->assertEquals(
            $response->body(),
            '{"action":"index","var":"5","var2":null}'
        );

        $response = $this->router->routeRequest('/routing/nested/5/child/70/tests', 'GET');
        Phockito::verify($this->controller)->index(anything(),anything());

        $this->assertEquals(
            $response->body(),
            '{"action":"index","var":"5","var2":"70"}'
        );
    }

    public function testPOSTisCREATE() {
        $this->mockController();

        $response = $this->router->routeRequest('/routing/test/5/tests', 'POST');
        Phockito::verify($this->controller)->create(anything(),anything());
        Phockito::reset($this->controller);

        $this->assertEquals(
            $response->body(),
            '{"action":"create","var":"5","var2":null}'
        );

        $response = $this->router->routeRequest('/routing/nested/5/child/70/tests', 'POST');
        Phockito::verify($this->controller)->create(anything(),anything());

        $this->assertEquals(
            $response->body(),
            '{"action":"create","var":"5","var2":"70"}'
        );
    }

    public function testPUTisBULKUPDATE() {
        $this->mockController();

        $response = $this->router->routeRequest('/routing/test/5/tests', 'PUT');
        Phockito::verify($this->controller)->bulkUpdate(anything(),anything());
        Phockito::reset($this->controller);

        $this->assertEquals(
            $response->body(),
            '{"action":"bulkUpdate","var":"5","var2":null}'
        );

        $response = $this->router->routeRequest('/routing/nested/5/child/70/tests', 'PUT');
        Phockito::verify($this->controller)->bulkUpdate(anything(),anything());

        $this->assertEquals(
            $response->body(),
            '{"action":"bulkUpdate","var":"5","var2":"70"}'
        );
    }

    public function testDELETEisDELETEALL() {
        $this->mockController();

        $response = $this->router->routeRequest('/routing/test/5/tests', 'DELETE');
        Phockito::verify($this->controller)->deleteAll(anything(),anything());
        Phockito::reset($this->controller);

        $this->assertEquals(
            $response->body(),
            '{"action":"deleteAll","var":"5","var2":null}'
        );

        $response = $this->router->routeRequest('/routing/nested/5/child/70/tests', 'DELETE');
        Phockito::verify($this->controller)->deleteAll(anything(),anything());

        $this->assertEquals(
            $response->body(),
            '{"action":"deleteAll","var":"5","var2":"70"}'
        );
    }

    public function testActionMethods() {
        $this->mockController();

        $response = $this->router->routeRequest('/routing/test/5/tests/custom', 'POST');
        Phockito::verify($this->controller)->custom(anything(),anything());
        Phockito::reset($this->controller);

        $this->assertEquals(
            $response->body(),
            '{"action":"custom","var":"5","var2":null}'
        );

        $response = $this->router->routeRequest('/routing/nested/5/child/70/tests/custom', 'POST');
        Phockito::verify($this->controller)->custom(anything(),anything());

        $this->assertEquals(
            $response->body(),
            '{"action":"custom","var":"5","var2":"70"}'
        );
    }
    public function testCollectionUnmatchedIs404() {
        $this->router->captureOutput();

        $response = $this->router->routeRequest('/bad/routes', 'GET');
        $this->assertEquals($response->status()->getCode(), 404);
        $response = $this->router->routeRequest('/routing/bads', 'GET');
        $this->assertEquals($response->status()->getCode(), 404);
        $response = $this->router->routeRequest('/routing/tests', 'GET');
        $this->assertEquals($response->status()->getCode(), 404);
        $response = $this->router->routeRequest('/routing/test/5/bads', 'GET');
        $this->assertEquals($response->status()->getCode(), 404);
    }

    public function testCollectionMethodUnmatchedIs405() {
        $this->router->captureOutput();

        $response = $this->router->routeRequest('/routing/empty/5/things', 'GET');
        $this->assertEquals($response->status()->getCode(), 405);

        $response = $this->router->routeRequest('/routing/empty/5/things', 'POST');
        $this->assertEquals($response->status()->getCode(), 200);

        $response = $this->router->routeRequest('/routing/empty/5/things', 'PUT');
        $this->assertEquals($response->status()->getCode(), 405);
    }

    public function testCollectionActionUnmatchedIs404() {
        $response = $this->router->routeRequest('/routing/empty/5/things', 'POST');
        $this->assertEquals($response->status()->getCode(), 200);

        $response = $this->router->routeRequest('/routing/empty/5/things/action', 'POST');
        $this->assertEquals($response->status()->getCode(), 404);
    }

    /**
     * Test the chain rules and json stuff.
     **/
    public function testJsonValidation() {
        $this->mockController();

        $_GET['json'] = json_encode( array(
            'testParams' => array(
                'testParam' => 55
            ),
            'anotherParam' => 'This is a string',
        ));

        $response = $this->router->routeRequest('/routing/nested/5/child/70/json', 'GET');

        $this->assertEquals(
            $response->body(),
            json_encode( array(
                'action' => 'index',
                'var' => '5',
                'var2' => '70',
                'testParam' => 55,
                'anotherParam' => 'This is a string'
            ))
        );

        $_GET['json'] = json_encode( array(
            'testParams' => new stdClass,
            'anotherParam' => 'This is a string',
        ));

        $response = $this->router->routeRequest('/routing/nested/5/child/70/json', 'GET');

        $this->assertEquals(
            $response->body(),
            json_encode( array(
                'action' => 'index',
                'var' => '5',
                'var2' => '70',
                'testParam' => 256,
                'anotherParam' => 'This is a string'
            ))
        );
    }
}

