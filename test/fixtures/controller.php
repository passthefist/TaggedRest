<?php
namespace Kleinbottle\tests\fixtures;

class controller {
    public function index($request, $response) {
        $response->json(array(
            'action' => 'index',
            'var' => $request->var,
            'var2' => $request->var2,
        ));
    }
    public function create($request, $response) {
        $response->json(array(
            'action' => 'create',
            'var' => $request->var,
            'var2' => $request->var2,
        ));
    }
    public function bulkUpdate($request, $response) {
        $response->json(array(
            'action' => 'bulkUpdate',
            'var' => $request->var,
            'var2' => $request->var2,
        ));
    }
    public function deleteAll($request, $response) {
        $response->json(array(
            'action' => 'deleteAll',
            'var' => $request->var,
            'var2' => $request->var2,
        ));
    }
    public function fetch($request, $response) {
        $response->json(array(
            'action' => 'fetch',
            'var' => $request->var,
            'var2' => $request->var2,
        ));
    }
    public function update($request, $response) {
        $response->json(array(
            'action' => 'update',
            'var' => $request->var,
            'var2' => $request->var2,
        ));
    }
    public function delete($request, $response) {
        $response->json(array(
            'action' => 'delete',
            'var' => $request->var,
            'var2' => $request->var2,
        ));
    }
    public function custom($request, $response) {
        $response->json(array(
            'action' => 'custom',
            'var' => $request->var,
            'var2' => $request->var2,
        ));
    }
}



