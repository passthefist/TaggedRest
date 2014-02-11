<?php
namespace Kleinbottle\tests\fixtures;

class json {
    public function fetch($request, $response) {
        print_r($request->var2);
        print_r($request->var2->hello->goodbye);

        $response->json(array(
            'action' => 'fetch',
            'var' => $request->var,
            'var2' => $request->var2->wargarbl,
        ));
    }
}
