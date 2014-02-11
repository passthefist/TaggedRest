<?php
namespace Kleinbottle\tests\fixtures;
use Kleinbottle\Middleware;

class jsoncontroller {
    public function beforeIndex() {
        return array(
            new \Kleinbottle\Middleware\ValidateJson(
                'json',
                array(
                    "title" => "Testing the json stuff",
                    "type" => "object",
                    "properties" => array(
                        "testParams"=> array(
                            "type"=>"object",
                            "properties"=>array(
                                "testParam"=> array(
                                    "type"=>"number",
                                    "default"=>256,
                                )
                            ),
                            "required" => array('testParam'),
                        ),
                        "anotherParam"=> array(
                            "type"=>"string",
                        )
                    ),
                )
            )
        );
    }

    public function index($request, $response) {
        $response->json(array(
            'action' => 'index',
            'var' => $request->var,
            'var2' => $request->var2,
            'testParam' => $request->json->testParams->testParam,
            'anotherParam' => $request->json->anotherParam
        ));
    }
}



